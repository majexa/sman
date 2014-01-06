<?php

class SmanInstanceServerManager extends SmanInstance {

  function _install() {
    $this->installCore();
    $this->installPhp();
    $this->installGit();
    $this->installMail();
    $this->createUser();
  }

}