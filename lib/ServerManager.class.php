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
    output("============================ Install Soft");
    $this->wi->installSoft();
    output("============================ Install Env");
    $this->wi->installEnv();
    output("============================ Install Config");
    $this->wi->installConfig();
    output("============================ Install DNS");
    $this->wi->installDns();
  }

  function delete() {
    $this->wi->removeDns();
    $this->wi->ei->deleteServer($this->name);
  }

}