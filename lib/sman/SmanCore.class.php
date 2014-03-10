<?php

class SmanCore {

  static function serverTypes() {
    return array_values(ClassCore::getNames('SmanEnv'));
  }

  static function serverType($name) {
    $type = Misc::checkEmpty(preg_replace('/(\D+)\d+/', '$1', $name), "Name '$name' has wrong format");
    if (!in_array($type, self::serverTypes())) throw new Exception("Undefined env/instance type '$type'");
    return $type;
  }

}