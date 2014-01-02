<?php

abstract class ObjectMapper {

  function __call($method, array $args = []) {
    if (method_exists($this, $method)) {
      return call_user_func_array([$this, $method], $args);
    }
    return call_user_func_array([$this->getObject(), $method], $args);
  }

  abstract protected function getObject();

}
