<?php

abstract class SmanInstaller {

  protected $sshConnection, $scp, $sftp, $gitUrl, $user;
  public $ssh;

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

  abstract protected function _install();

  static $classPrefix;

  /**
   * @return SmanInstaller
   */
  abstract static function get($name);

}