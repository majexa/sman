<?php

abstract class SmanInstaller {

  protected $sshConnection, $scp, $sftp, $gitUrl, $user, $disable = false;
  public $ssh;

  function __construct(Ssh2Connection $sshConnection) {
    $this->sshConnection = $sshConnection;
    $this->scp = new Ssh2Scp($sshConnection);
    $this->sftp = new Ssh2Sftp($sshConnection);
    $this->ssh = new Ssh2($sshConnection);
  }

  function install() {
    $this->_install();
  }

  abstract protected function _install();

  static $classPrefix;


  // ----------- sh commands generation -------------

  function getShCmds() {
    if (!$this->disable) throw new Exception('You can get sh commands only in disabled class instance. See constructor');
    $this->install();
    return $this->shCmds;
  }

  protected $shCmds = [];

  protected function exec($cmd) {
    if ($this->disable) $this->shCmds[] = $cmd;
    else return $this->ssh->exec($cmd);
  }


}