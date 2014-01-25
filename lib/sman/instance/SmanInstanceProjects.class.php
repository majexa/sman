<?php

class SmanInstanceProjects extends SmanInstance {

  function _install() {
    $this->installPhpFull();
    $this->createUser();
    $this->installNginx();
    //$this->installMysql(); @todo install Mysql
    //$this->installMail(); @todo install Mail
    //$this->installRabbitmq(); @todo install Rabbitmq
  }

}