<?php

class DoceanSshConnection extends SshPasswordConnection {

  function __construct($serverName) {
    parent::__construct(Docean::get()->server($serverName)['ip_address'], 'root', Config::getSubVar('doceanServers', $serverName));
  }

}