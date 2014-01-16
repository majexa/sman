<?php

class ServerManager {

  protected $name;

  function __construct($name) {
    $this->name = $name;
    $this->wi = new WebInstaller($this->name);
  }

  function create() {
    $this->wi->ei->deleteServer($this->name, false);
    $this->wi->ei->createServer($this->name);
    $this->wi->ei->addLocalSshKey($this->name);
    $this->wi->installSoft();
    $this->wi->installEnv();
    $this->wi->installConfig();
    $this->wi->installDns();
  }

  function delete() {
    $this->wi->removeDns();
    $this->wi->ei->deleteServer($this->name);
  }

}