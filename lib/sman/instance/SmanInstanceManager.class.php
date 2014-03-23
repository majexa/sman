<?php

class SmanInstanceManager extends SmanInstanceAbstract {

  protected function _install() {
    $this->installPhpBasic();
    $this->installMail();
  }

}