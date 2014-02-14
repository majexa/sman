<?php

class SmanCore {

  static function serverType($name) {
    return Misc::checkEmpty(preg_replace('/(\D+)\d+/', '$1', $name), "Name '$name' has wrong format");
  }

}