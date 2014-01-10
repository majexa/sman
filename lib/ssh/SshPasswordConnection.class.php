<?php

class SshPasswordConnection extends SshConnection {

  protected $username, $password;

  function __construct($host, $username, $password, $port = 22) {
    $this->username = $username;
    $this->password = $password;
    parent::__construct($host, $port);
  }

  protected function auth() {
    try {
      $r = ssh2_auth_password($this->connection, $this->username, $this->password);
    } catch (Exception $e) {
      throw new Exception($e->getMessage()." ($this->connection, $this->host, $this->username, $this->password)");
    }
    if (!$r) throw new Exception('=(');
    output("Auth successfully");
  }

}