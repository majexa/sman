<?php

class DoceanRemoteSsh extends RemoteSshAbstract {

  /**
   * @var Digitalocean
   */
  public $api;

  function __construct( ) {
    $this->api = new Docean;
  }

  protected function password($serverName) {
    $password = Config::getFileVar(DATA_PATH.'/passwords.php', false)[$serverName];
    Misc::checkEmpty($password, "server '$serverName' password is empty");
    return $password;
  }

  protected function server($serverName) {
    return $this->api->server($serverName);
  }


}