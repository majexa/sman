<?php

/**
 * @method void api($uri, $data)
 * @method void createServer($name, $opts)
 * @method void servers()
 * @method void _destroyServer($id)
 * @method void destroyDroplet($name)
 */
class DigitaloceanApiCached extends ObjectCacher {

  protected function getObject() {
    return new DigitaloceanApi;
  }

  function cachedMethods() {
    return [
      'servers'
    ];
  }

}