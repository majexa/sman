<?php

class SmanInstanceManager extends SmanInstance {

  protected function _install() {
    $this->installPhp();
    $this->installMail();
  }

}