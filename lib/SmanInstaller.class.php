<?php

abstract class SmanInstaller {

  protected $sshConnection, /*$scp, */$gitUrl, $user, $disable = false;

  /**
   * @var Ssh2ShellInterface
   */
  public $shell;

  /**
   * @var Ssh2SftpInterface
   */
  public $ftp;

  function __construct() {
    $this->ftp = $this->ftp();
    $this->shell = $this->shell();
  }

  abstract protected function ftp();
  abstract protected function shell();
  abstract protected function serverHost();

  function install() {
    $this->_install();
  }

  abstract protected function _install();

  static $classPrefix;


  // ----------- sh commands generation -------------

  protected function _getShCmds() {
    if (!$this->disable) {
      throw new Exception('You can get sh commands only in disabled class instance. See constructor');
    }
    $this->install();
    return $this->shCmds;
  }

  function getShCmds() {
    $r = [];
    foreach ($this->_getShCmds() as $cmd) {
      if (is_array($cmd)) {
        foreach ($cmd as $v) $r[] = $v;
      }
      else {
        $r[] = $cmd;
      }
    }
    return $r;
  }

  protected $shCmds = [];

  protected function exec($cmd) {
    if ($this->disable) $this->shCmds[] = $cmd;
    else return $this->shell->exec($cmd);
  }


}