<?php

class ConfigStatic extends Config {

  static function path($name) {
    throw new Exception('define');
  }

  static function updateSubVar($name, $k, $v) {
    parent::updateSubVar(static::path($name), $k, $v);
  }

  static function removeSubVar($name, $k) {
    parent::removeSubVar(static::path($name), $k);
  }

}

class SmanConfig extends ConfigStatic {

  static function path($name) {
    return SMAN_PATH.'/config/vars/'.$name.'.php';
  }

}