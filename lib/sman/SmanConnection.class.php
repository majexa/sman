<?php

class SmanConnection extends SshPasswordConnection {

  function __construct($host = 'localhost') {
    parent::__construct($host, 'user', Config::getSubVar('userPasswords', $host, true));
  }

}