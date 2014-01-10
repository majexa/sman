<?php

abstract class SshConnection {

  public $host, $port;
  protected $connection;

  function __construct($host, $port) {
    $this->host = $host;
    $this->port = $port;
    $this->connect();
    $this->auth();
  }

  protected $attempt = 0;

  protected function connect() {
    if ($this->attempt > 3) {
      throw new Exception("Reached max attempts ($this->attempt)");
      return;
    }
    output("Connecting ssh $this->host:$this->port");
    $this->attempt++;
    try {
      $this->connection = ssh2_connect($this->host, $this->port);
    } catch (Exception $e) {
      if (strstr($e->getMessage(), 'Error starting up SSH connection(-1): Unable to exchange encryption keys')) {
        output($e->getMessage());
        sleep(5);
        $this->connect();
        return;
      }
      throw $e;
    }
    if (!$this->connection) throw new Exception("Error connecting $this->host:$this->port");
  }

  abstract protected function auth();

  function __invoke() {
    return $this->connection;
  }

}