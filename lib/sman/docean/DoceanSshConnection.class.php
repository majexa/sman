<?php

class DoceanSshConnection extends SshPasswordConnection {

  function __construct($name) {
    parent::__construct(Docean::get()->server($name)['ip_address'], 'root', Config::getSubVar('doceanServers', $name));
  }

}