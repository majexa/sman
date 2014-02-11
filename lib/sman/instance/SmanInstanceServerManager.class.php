<?php

class SmanInstanceServerManager extends SmanInstance {

  protected function _install() {
    $this->installPhp();
    $this->installMail();
  }

}