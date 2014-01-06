<?php

class SmanConfig extends Config {

  static function updateSubVar($name, $k, $v) {
    parent::updateSubVar(SMAN_PATH.'/config/vars/'.$name.'.php', $k, $v);
  }

}