<?php

class SmanPasswordConnection extends SshPasswordConnection {

  function __construct($serverName, $port = 22) {
    $passwords = require DATA_PATH.'/userPasswords.php';
    parent::__construct('user', $passwords[$serverName], $port);
  }

}