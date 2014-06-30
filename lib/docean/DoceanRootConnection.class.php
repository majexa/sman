<?php

class DoceanRootConnection extends Ssh2PasswordConnection {

  function __construct($serverName) {
    $host = Docean::get()->server($serverName)['ip_address'];
    parent::__construct($host, 'root', Config::getSubVar('doceanServers', $serverName, true));
  }

}