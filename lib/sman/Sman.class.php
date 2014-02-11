<?php

if (!defined('SMAN_PATH')) throw new Exception('sman not initialized');

class Sman {

  /**
   * Создаёт сервер, инсталирует среду
   *
   * @param string        projects|serverManager|dnsMaster|dnsSlave
   * @param integer|null  Уникальный идентификатор сервера
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
    SmanInstance::get($name)->install();
    SmanEnv::get($name)->install();
  }

  function info($name) {
    $server = Docean::get()->server($name);
    $host = $server['ip_address'];
    $rootPassword = Config::getSubVar('doceanServers', $name);
    $userPassword = Config::getSubVar('userPasswords', $host);
    print <<<TEXT
*-- Server: $name --*
Host: $host
Root password: $rootPassword
User password: $userPassword

TEXT;
  }

  function lst() {
    print implode("\n* ", Arr::get(Docean::get()->servers(), 'name'))."\n";
  }

  function delete($name) {
    $this->deleteDns($name);
    $host = Docean::get()->server($name)['ip_address'];
    `ssh-keygen -f "/home/user/.ssh/known_hosts" -R $host`;
    SmanConfig::removeSubVar('userPasswords', $host); // user password
    SmanConfig::removeSubVar('doceanServers', $name); // root password
    Docean::get()->deleteServer($name);
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
    return $ssh = (new SshStrict((new SshDefaultConnection(Config::getSubVar('servers', 'dnsMaster'), 'root'))));
  }

  protected function createZone($name) {
    $this->_createZone($name, Docean::get()->server($name)['ip_address']);
  }

  protected function deleteDns($name) {
    $domain = $name.'.'.Config::getVar('baseDomain');
    $cmd = str_replace('"', '\\"', '(new DnsServer)->deleteZone(["'.$domain.'", "*.'.$domain.'"])');
    print $this->dnsSsh()->exec(Cli::addRunPaths($cmd, 'NGN_ENV_PATH/dns-server/lib'));
  }

  protected function _createZone($name, $host) {
    $domain = $name.'.'.Config::getVar('baseDomain');
    $cmd = str_replace('"', '\\"', '(new DnsServer)->replaceZone(["'.$domain.'", "*.'.$domain.'"], "'.$host.'")');
    print $this->dnsSsh()->exec(Cli::addRunPaths($cmd, 'NGN_ENV_PATH/dns-server/lib'));
    return $domain;
  }

  protected function createEnv($name) {
    $host = Docean::get()->server($name)['ip_address'];
    $sshConnection = new SshPasswordConnection($host, 'user', Config::getSubVar('userPasswords', $host, true));
    SmanEnv::get('projects', $sshConnection)->install();
  }

}