<?php

class SshPublicKeyConnection extends SshConnection {

  protected $username, $publicKey, $privateKey;

  function __construct($host, $port, $username, $publicKey, $privateKey) {
    $this->username = $username;
    $this->publicKey = $publicKey;
    $this->privateKey = $privateKey;
    parent::__construct($host, $port);
  }

  protected function connect() {
    if (ssh2_auth_pubkey_file($this->connection, $this->username, $this->publicKey, $this->privateKey) === false) {
      throw new Exception('SSH2 login is invalid');
    }
  }

}