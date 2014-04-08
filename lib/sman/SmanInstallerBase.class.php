<?php

abstract class SmanInstallerBase extends SmanInstaller {

  protected $serverName;

  /**
   * @param string|false Server name or FALSE to create disabled instance for getting sh commands
   */
  function __construct($serverName) {
    if ($serverName === false) $this->disable = true;
    if (!$this->disable) parent::__construct(new DoceanRootConnection($serverName));
    $this->serverName = $serverName;
  }

}