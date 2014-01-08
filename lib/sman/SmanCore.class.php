<?php

if (!defined('SMAN_PATH')) throw new Exception('sman not initialized');

class SmanCore {

  static function create($type, $id) {
    $name = $type.$id;
    (new DoceanServer($name))->create();
    self::createInstance($type, new DoceanSshConnection($name));
  }

  static function createInstance($type, SshConnection $sshConnection) {
    self::checkConfig();
    SmanInstance::get($type, $sshConnection)->install();
    $host = $sshConnection->host;
    unset($sshConnection);
    $sshConnection = new SshPasswordConnection($host, 'user', Config::getSubVar('userPasswords', $host));
    SmanEnv::get($type, $sshConnection)->install();
  }

  static function checkConfig() {
    Config::getSubVar('botEmail', 'domain');
    Config::getVar('doceanAccess');
    Config::getVar('git');
    Config::getSubVar('servers', 'dnsMaster');
  }

}