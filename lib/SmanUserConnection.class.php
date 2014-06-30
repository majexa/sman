<?php

class SmanUserConnection2 extends Ssh2PasswordConnection {

  function __construct($host = 'localhost') {
    parent::__construct($host, 'user', Config::getSubVar('userPasswords', $host, true));
  }

}