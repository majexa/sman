<?php

abstract class SmanInstanceAbstract {

  protected $sshConnection, $scp, $sftp, $ssh, $gitUrl, $user;

  function __construct(SshConnection $sshConnection) {
    $this->sshConnection = $sshConnection;
    $this->scp = new SshScp($sshConnection);
    $this->sftp = new SshSftp($sshConnection);
    $this->ssh = new Ssh($sshConnection);
    $this->gitUrl = Config::getVar('git');
  }

  function install() {
    $this->_install();
  }

  abstract function _install();

  static $classPrefix;

  /**
   * @return SmanInstanceAbstract
   */
  static function get($type, SshConnection $sshConnection) {
    $class = static::$classPrefix.ucfirst($type);
    return new $class($sshConnection);
  }

}