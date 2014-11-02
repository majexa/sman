<?php

/*
sudo apt-get -y purge nginx nginx-full nginx-common
sudo sman instance local installNginxFull
*/

class SmanInstanceProjects extends SmanInstanceAbstract {

  protected function _install() {
    $this->installNginxFull();
    //$this->installPhpFull();
    //$this->installMysql(); @todo install Mysql
    //$this->installMail(); @todo install Mail
    //$this->installRabbitmq(); @todo install Rabbitmq
  }

  function installAsd() {
    print "\nASD\n";
  }

}