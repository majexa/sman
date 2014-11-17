<?php

class DoceanServer {
  use DebugOutput;

  protected $name, $docean, $botEmail, $botEmailFolder;

  function __construct($name) {
    $this->name = $name;
    $this->docean = Docean::get();
  }

  function create() {
    if (!`sudo run "(new BotEmail)->check()" sman`) {
      throw new Exception('Email check failed');
    }
    $this->docean->createServer($this->name);
    if (!`sudo run "(new BotEmailServer('$this->name'))->storePass()" sman`) {
      throw new Exception('Storing pass problems. Check errors log');
    }
    //output('Waiting 30 sec after creation');
    //sleep(30);
  }

  protected function output($s) {
    output($s);
  }

}