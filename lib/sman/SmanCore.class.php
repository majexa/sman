<?php

if (!defined('SMAN_PATH')) throw new Exception('sman not initialized');

class SmanCore {

  /**
   * Создаёт сервер, инсталирует среду
   *
   * @param string        projects|serverManager|dnsMaster|dnsSlave
   * @param integer|null  Уникальный идентификатор сервера
   */
  static function create($type, $id = null) {
    if (!$id) $id = self::lastId($type) + 1;
    $name = $type.$id;
    (new DoceanServer($name))->create();
    output('Waiting 15 sec after creation');
    sleep(15);
    self::createDns($name);
    self::createInstance($type, $name);
  }

  // -------------------------------------------

  static function checkConfig() {
    Config::getVar('doceanAccess');
    Config::getSubVar('botEmail', 'domain');
    Config::getSubVar('servers', 'dnsMaster');
    Config::getVar('git');
    Config::getVar('baseDomain');
  }

  static function lastId($type) {
    $servers = Docean::get()->servers();
    $max = 0;
    foreach ($servers as $v) if (Misc::hasPrefix($type, $v['name'])) {
      $id = (int)Misc::removePrefix($type, $v['name']);
      if ($id > $max) $max = $id;
    };
    return $max;
  }

  static function a() {
    SmanInstance::get('projects', new DoceanRootConnection('projects3'))->install();
  }

  static function createInstance($type, $name) {
    self::checkConfig();
    SmanInstance::get($type, $name)->install();
    SmanEnv::get($type, $name)->install();
  }

  static function createDns($name) {
    $dnsHost = Config::getSubVar('servers', 'dnsMaster');
    $ssh = (new Ssh((new SshDefaultConnection($dnsHost, 'root'))));
    self::clearZone($name, Docean::get()->server($name)['ip_address'], $ssh);
    self::clearZone('*.'.$name, Docean::get()->server($name)['ip_address'], $ssh);
  }

  static protected function clearZone($name, $host, Ssh $ssh) {
    $cmd = str_replace('"', '\\"', '(new DnsServer)->createZone("'.$name.'.'.Config::getVar('baseDomain').'", "'.$host.'")');
    $cmd = Cli::addRunPaths($cmd, 'NGN_ENV_PATH/dns-server/lib');
    print $ssh->exec($cmd);
  }

  static function createEnv($name) {
    $host = Docean::get()->server($name)['ip_address'];
    $sshConnection = new SshPasswordConnection($host, 'user', Config::getSubVar('userPasswords', $host, true));
    SmanEnv::get('projects', $sshConnection)->install();
  }

}