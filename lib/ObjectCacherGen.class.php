<?php

class ObjectCacherGen {

  function getObjectClass($chachedClass) {
    $r = new ReflectionMethod($chachedClass, 'getObject');
    $strings = file(Lib::getClassPath($chachedClass));
    for ($i = $r->getStartLine(); $i <= $r->getEndLine(); $i++) {
      if (preg_match('/return new (\w+)/', $strings[$i], $m)) return $m[1];
    }
    return false;
  }

}