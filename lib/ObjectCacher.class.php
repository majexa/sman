<?php

abstract class ObjectCacher {

  protected $object;

  function __function() {
    $this->object = $this->getObject();
  }

  abstract protected function getObject();

  function __call($method, $args) {
    ClassCore::checkExistance($this, $method);
    if (in_array($method, $this->cachedMethods())) return $this->save(call_user_func_array([$this, $method], $args));
    return call_user_func_array([$this, $method], $args);
  }

  abstract function cachedMethods();

}