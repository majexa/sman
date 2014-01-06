<?php

abstract class SshConnection {

  public $host;
  protected $connection;

  function __construct($host, $port) {
    $this->host = $host;
    $this->connection = ssh2_connect($host, $port);
    if (!$this->connection) throw new Exception("Error connecting $host:$port");
    $this->connect();
  }

  abstract protected function connect();

  function __invoke() {
    return $this->connection;
  }

  function close() {

  }

}