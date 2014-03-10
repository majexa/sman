<?php

abstract class SmanInstaller {

  protected $sshConnection, $scp, $sftp, $gitUrl, $user;
  public $ssh;

  function __construct(Ssh2Connection $sshConnection) {
    $this->sshConnection = $sshConnection;
    $this->scp = new Ssh2Scp($sshConnection);
    $this->sftp = new Ssh2Sftp($sshConnection);
    $this->ssh = new Ssh2($sshConnection);
    $this->gitUrl = Config::getVar('git');
  }

  function install() {
    $this->_install();
  }

  abstract protected function _install();

  static $classPrefix;

  /**
   * @param string
   * @return SmanInstanceAbstract
   */
  static function get($name) {
    $class = static::getClass($name);
    return new $class($name);
  }

  abstract static function getClass($name);

}