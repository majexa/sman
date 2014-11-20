<?php

abstract class SmanInstallerBase extends SmanInstaller {

  protected $serverName;

  /**
   * @param string|false Server name or FALSE to create disabled instance for getting sh commands
   */
  function __construct($serverName) {
    if ($serverName === false) $this->disable = true;
    $this->serverName = $serverName;
    if ($this->serverName !== false and $this->serverName != 'local') {
      $this->sshConnection = new DoceanRootConnection($this->serverName);
    }
    parent::__construct();
  }

  protected function ftp() {
    if ($this->serverName === false) return false;
    // die2(get_class($this->sshConnection));
    return $this->serverName == 'local' ? new Ssh2SftpLocal : new Ssh2Sftp($this->sshConnection);
  }

  protected function shell() {
    if ($this->serverName === false) return false;
    return $this->serverName == 'local' ? new Ssh2Local : new Ssh2($this->sshConnection);
  }

  protected function serverHost() {
    if ($this->serverName == 'local') return 'localhost';
    return parent::serverHost();
  }

}