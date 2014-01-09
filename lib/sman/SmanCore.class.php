<?php

if (!defined('SMAN_PATH')) throw new Exception('sman not initialized');

class SmanCore {

  static function create($type, $id) {
    $name = $type.$id;
    //(new DoceanServer($name))->create();
    self::createInstance($type, new DoceanSshConnection($name));
  }

  static function createInstance($type, SshConnection $sshConnection) {
    SmanEnv::get($type, $sshConnection)->_install();
    return;
    self::checkConfig();
    SmanInstance::get($type, $sshConnection)->install();
    $host = $sshConnection->host;
    unset($sshConnection);
    $sshConnection = new SshPasswordConnection($host, 'user', Config::getSubVar('userPasswords', $host));
  }

  static function checkConfig() {
    Config::getVar('doceanAccess');
    Config::getSubVar('botEmail', 'domain');
    Config::getSubVar('servers', 'dnsMaster');
    Config::getVar('git');
    Config::getVar('baseDomain');
  }

}