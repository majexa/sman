<?php
/*

sudo apt-get -y purge nginx nginx-full nginx-common
sudo sman instance local installNginxFull
sudo sman instance local installPhpFull
sman env local install
sman env local createConfig june.majexa.ru
~/ngn-env/ci/ci update
pm localServer updateHosts
tst ... web

*/
if (!defined('SMAN_PATH')) throw new Exception('sman not initialized');

/**
 * Управление серверами на площадке DigitalOcean
 */
class Sman {

  function config() {
    foreach ([
      ['git', false, 'git URL'],
      ['botEmail', 'domain', 'Bot email domain'],
      ['botEmail', 'user', 'Bot email user'],
      ['doceanAccess', 'client_id', 'DigitalOcean Client ID'],
      ['doceanAccess', 'api_key', 'DigitalOcean API Key'],
      //['servers', 'dnsMaster', 'DNS Master host']
    ] as $v) {
      $current = $v[1] === false ? SmanConfig::getVar($v[0], true) : SmanConfig::getSubVar($v[0], $v[1], false, true);
      if (($r = $this->prompt(($current ? 'Reset' : 'Enter'), $v[2], $current))) {
        $v[1] === false ? SmanConfig::updateVar($v[0], $r) : SmanConfig::updateSubVar($v[0], $v[1], $r);
      }
    }
    $server = FileVar::getVar(NGN_ENV_PATH.'/config/server.php');
    if (($r = $this->prompt('Input', 'base domain', isset($server['baseDomain']) ? $server['baseDomain'] : null))) {
      FileVar::updateSubVar(NGN_ENV_PATH.'/config/server.php', 'baseDomain', $r);
    }
  }

  protected function prompt($action, $title, $current) {
    return Cli::prompt( //
      "$action $title: (press ENTER to skip)". //
      ($current ? " [Current value: ".CliColors::colored($current, 'yellow')."]" : '') //
    );
  }

  /**
   * Создаёт установщик себя для голой ubuntu/debian
   *
   * @param $type
   * @throws Exception
   */
  function pure($type) {
    $s = "# Ngn-env installation script for server type '$type':\n";
    $class = 'SmanInstance'.ucfirst($type).'Self'; //                                      [0 - pure]
    /* @var SmanInstanceAbstract $instance */
    $instance = new $class(false);
    foreach ($instance->_getShCmds() as $cmd) { //                                         [1 - soft]
      if (is_array($cmd)) foreach ($cmd as $v) $s .= "$v\n";
      else $s .= "$cmd\n";
    }
    $env = new SmanEnvManagerSelf(false, $instance->userPass);
    foreach ($env->_getShCmds() as $cmd) {
      if (is_array($cmd)) foreach ($cmd as $v) $s .= "$v\n";
      else $s .= "$cmd\n";
    }
    $s .= "cd ~/ngn-env/ci\n";
    $s .= "chmod +x ci\n";
    $s .= "sudo ./ci _updateBin\n";
    $s .= "./ci update\n";
    file_put_contents(SMAN_PATH.'/web/run.sh', str_replace($instance->userPass, 'CHANGE_PASSWORD', $s));
    print $s;
  }

  /**
   * Создаёт сервер
   *
   * @param string $type projects|manager|dnsMaster|dnsSlave
   * @param null $id
   * @return integer|null $id Уникальный идентификатор сервера
   */
  function create($type, $id = null) {
    $name = 'projects2';

/*    if (!SmanConfig::getVar('baseDomain', true)) {
      $baseDomain = Cli::prompt('Input server base domain');
      SmanConfig::updateVar('baseDomain', $baseDomain);
    }
    if (!SmanConfig::getVar('servers', true)) {
      $dnsMaster = Cli::prompt('Input Ngn DnsMaster server hostname');
      SmanConfig::updateVar('servers', [
        'dnsMaster' => $dnsMaster
      ]);
    }
    //if (!$id) $id = self::lastId($type) + 1;
    //$name = $type.$id;
    //(new DoceanServer($name))->create();
    $this->createZone($name);*/
    $this->createInstance($name);
    return $name;
  }

  protected function createInstance($name) {
    $this->checkConfig();
    SmanInstanceAbstract::get($name)->install();
    SmanEnvAbstract::get($name)->install();
  }

  /**
   * Выводит информацию о сервере
   *
   * @param $serverName
   */
  function info($serverName) {
    $server = Docean::get()->server($serverName);
    $host = $server['ip_address'];
    $rootPassword = Config::getSubVar('doceanServers', $serverName);
    $userPassword = Config::getSubVar('userPasswords', $host);
    print <<<TEXT
*-- Server: $serverName --*
Host: $host
Root password: $rootPassword
User password: $userPassword

TEXT;
  }

  /**
   * Удаляет сервер
   *
   * @param $serverName
   */
  function delete($serverName) {
    $this->deleteZone($serverName);
    $host = Docean::get()->server($serverName)['ip_address'];
    `ssh-keygen -f "/home/user/.ssh/known_hosts" -R $host`;
    SmanConfig::removeSubVar('userPasswords', $host); // user password
    SmanConfig::removeSubVar('doceanServers', $serverName); // root password
    Docean::get()->deleteServer($serverName);
  }

  protected function checkConfig() {
    Config::getVar('doceanAccess');
    Config::getSubVar('botEmail', 'domain');
    Config::getSubVar('servers', 'dnsMaster');
    Config::getVar('git');
    Config::getVar('baseDomain');
  }

  protected function lastId($type) {
    $servers = Docean::get()->servers();
    $max = 0;
    foreach ($servers as $v) if (Misc::hasPrefix($type, $v['name'])) {
      $id = (int)Misc::removePrefix($type, $v['name']);
      if ($id > $max) $max = $id;
    };
    return $max;
  }

  protected function dnsSsh() {
    static $ssh;
    if (isset($ssh)) return $ssh;
    return $ssh = (new Ssh2Strict((new Ssh2DefaultConnection(Config::getSubVar('servers', 'dnsMaster'), 'root'))));
  }

  /**
   * Создаёт базовую DNS-зону сервера
   *
   * @param $name
   */
  protected function createZone($name) {
    $this->_createZone($name, Docean::get()->server($name)['ip_address']);
  }

  /**
   * Удаляет базовую DNS-зону сервера
   *
   * @param $name
   */
  protected function deleteZone($name) {
    $domain = $name.'.'.SmanConfig::getVar('baseDomain');
    $cmd = str_replace('"', '\\"', '(new DnsServer)->deleteZone(["'.$domain.'", "*.'.$domain.'"])');
    try {
      print $this->dnsSsh()->exec(Cli::addRunPaths($cmd, 'dnss/lib'));
    } catch (Exception $e) {
    }
  }

  protected function _createZone($name, $host) {
    $domain = $name.'.'.SmanConfig::getVar('baseDomain');
    $cmd = str_replace('"', '\\"', '(new DnsServer)->replaceZone(["'.$domain.'", "*.'.$domain.'"], "'.$host.'")');
    print $this->dnsSsh()->exec(Cli::addRunPaths($cmd, 'dnss/lib'));
    return $domain;
  }

  protected function createEnv($name) {
    throw new Exception('something wrong here: SmanEnvAbstract::get...');
    $host = Docean::get()->server($name)['ip_address'];
    $sshConnection = new Ssh2PasswordConnection($host, 'user', Config::getSubVar('userPasswords', $host, true));
    SmanEnvAbstract::get('projects', $sshConnection)->install();
  }

  /**
   * Интерфейс для установки программных пакетов на сервер
   *
   * @param string $serverName local|...
   * @return CliAccessResultClass
   */
  function instance($serverName = 'local') {
    return new CliAccessResultClass(SmanInstanceAbstract::getClass($serverName), 'instance');
  }

  /**
   * Интерфейс для установки пакетов Ngn-env на сервер
   *
   * @param $serverName local|...
   * @return CliAccessResultClass
   */
  function env($serverName) {
    return new CliAccessResultClass(SmanEnvAbstract::getClass($serverName), 'env');
  }

  /*
  function uploadGitPublicKey() {
    if (trim(`command -v sshpass >/dev/null && echo "y" || echo "n"`) == 'n') {
      print `sudo apt-get -y install sshpass`;
    }
    $r = parse_url(SmanConfig::getVar('git'));
    $r['pass'] = Cli::prompt('punk how you take it to the curb');
    (new ShellSshKeyUploader(new ShellSshPasswordCmd($r)))->upload();
  }
  */

}