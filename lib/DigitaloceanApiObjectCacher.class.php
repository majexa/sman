<?php

/**
 * @method void droplets()
 */
class DigitaloceanApiObjectCacher extends ObjectCacher {

  protected function getObject() {
    return new DigitaloceanApi;
  }

  function cachedMethods() {
    return [
      'droplets'
    ];
  }

}