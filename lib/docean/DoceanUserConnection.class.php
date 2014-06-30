<?php

class DoceanUserConnection extends Ssh2PasswordConnection {

  function __construct($serverName) {
    $host = Docean::get()->server($serverName)['ip_address'];
    parent::__construct($host, 'user', Config::getSubVar('userPasswords', $host, true));
  }

}