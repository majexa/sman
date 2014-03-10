<?php

class SmanInstanceManager extends SmanInstanceAbstract {

  protected function _install() {
    $this->installPhp();
    $this->installMail();
  }

}