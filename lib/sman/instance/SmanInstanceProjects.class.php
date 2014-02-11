<?php

class SmanInstanceProjects extends SmanInstance {

  protected function _install() {
    $this->installPhpFull();
    $this->installNginx();
    //$this->installMysql(); @todo install Mysql
    //$this->installMail(); @todo install Mail
    //$this->installRabbitmq(); @todo install Rabbitmq
  }

  function installAsd() {}

}