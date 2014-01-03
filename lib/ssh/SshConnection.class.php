<?php

abstract class SshConnection {

  protected $connection;

  function __construct($host, $port) {
    $this->connection = ssh2_connect($host, $port);
    if (!$this->connection) throw new Exception("Error connecting $host:$port");
    $this->connect();
  }

  abstract protected function connect();

  function __invoke() {
    return $this->connection;
  }

}