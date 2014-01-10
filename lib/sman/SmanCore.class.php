<?php

if (!defined('SMAN_PATH')) throw new Exception('sman not initialized');

class SmanCore {

  static function create($type, $id) {
    $name = $type.$id;
    (new DoceanServer($name))->create();
    output('Waiting 15 sec after creation');
    sleep(15);
    self::createInstance($type, new DoceanSshConnection($name));
  }

  static function createInstance($type, SshConnection $sshConnection) {
    self::checkConfig();
    SmanInstance::get($type, $sshConnection)->install();
    $host = $sshConnection->host;
    $sshConnection = new SshPasswordConnection($host, 'user', Config::getSubVar('userPasswords', $host, true));
    SmanEnv::get($type, $sshConnection)->install();
  }

  static function checkConfig() {
    Config::getVar('doceanAccess');
    Config::getSubVar('botEmail', 'domain');
    Config::getSubVar('servers', 'dnsMaster');
    Config::getVar('git');
    Config::getVar('baseDomain');
  }

}