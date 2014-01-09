<?php

class SmanInstanceServerManager extends SmanInstance {

  function _install() {
    $this->installPhp();
    $this->installMail();
    $this->createUser();
  }

}