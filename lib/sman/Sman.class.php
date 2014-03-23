<?php

if (!defined('SMAN_PATH')) throw new Exception('sman not initialized');

class Sman {

  /**
   * Создаёт установщик себя для голой ubuntu/debian
   */
  function genSelfInstaller() {
    $s = "# Install:\n";
    if (file_exists(NGN_ENV_PATH.'/config/server.php')) {
      $server = require NGN_ENV_PATH.'/config/server.php';
      $s .= "# wget -O - http://sman.{$server['baseDomain']}/run.sh | bash\n#\n";
    } else {
      $s .= "# wget -O - http://hostname/run.sh | bash\n#\n";
    }
    $instance = new SmanInstanceManagerSelf(false);
    foreach ($instance->getShCmds() as $cmd) {
      if (is_array($cmd)) foreach ($cmd as $v) $s .= "$v\n";
      else $s .= "$cmd\n";
    }
    $env = new SmanEnvManagerSelf(false, $instance->userPass);
    foreach ($env->getShCmds() as $cmd) {
      if (is_array($cmd)) foreach ($cmd as $v) $s .= "$v\n";
      else $s .= "$cmd\n";
    }
    $s.= "cd ~/ngn-env/ci\n";
    $s.= "chmod +x ci\n";
    $s.= "./ci update\n";
    $s.= "ci setup\n";
    file_put_contents(SMAN_PATH.'/web/run.sh', $s);
    print "Done.\n--\n$s";
  }

  /**
   * Создаёт сервер, инсталлирует среду
   *
   * @param string        projects|serverManager|dnsMaster|dnsSlave
   * @param integer|null Уникальный идентификатор сервера
   */
  function create($type, $id = null) {
    if (!$id) $id = self::lastId($type) + 1;
    $name = $type.$id;
    (new DoceanServer($name))->create();
    $this->createZone($name);
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
   * Выводит список серверов
   */
  function lst() {
    print "* ".Tt()->enumDddd(Docean::get()->servers(), '$name.` - `.$ip_address', "\n* ")."\n";
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
    $domain = $name.'.'.Config::getVar('baseDomain');
    $cmd = str_replace('"', '\\"', '(new DnsServer)->deleteZone(["'.$domain.'", "*.'.$domain.'"])');
    try {
      print $this->dnsSsh()->exec(Cli::addRunPaths($cmd, 'NGN_ENV_PATH/dns-server/lib'));
    } catch (Exception $e) {
    }
  }

  protected function _createZone($name, $host) {
    $domain = $name.'.'.Config::getVar('baseDomain');
    $cmd = str_replace('"', '\\"', '(new DnsServer)->replaceZone(["'.$domain.'", "*.'.$domain.'"], "'.$host.'")');
    print $this->dnsSsh()->exec(Cli::addRunPaths($cmd, 'NGN_ENV_PATH/dns-server/lib'));
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
   * @param $serverName
   * @return CliResultClass
   */
  function instance($serverName) {
    return new CliHelpResultClass(SmanInstanceAbstract::getClass($serverName), 'instance');
  }

  /**
   * Интерфейс для установки пакетов ngn-среды на сервер
   *
   * @param $serverName
   * @return CliResultClass
   */
  function env($serverName) {
    return new CliHelpResultClass(SmanEnvAbstract::getClass($serverName), 'env');
  }

}