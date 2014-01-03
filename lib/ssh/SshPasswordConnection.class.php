<?php

class SshPasswordConnection extends SshConnection {

  protected $username, $password;

  function __construct($host, $username, $password, $port = 22) {
    $this->username = $username;
    $this->password = $password;
    parent::__construct($host, $port);
  }

  protected function connect() {
    if (ssh2_auth_password($this->connection, $this->username, $this->password) === false) {
      throw new Exception('SSH2 login is invalid');
    }
  }

}