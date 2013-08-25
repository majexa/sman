<?php

/**
 * @method void api($uri, array $data = [])
 * @method void createServer($name, $opts)
 * @method void servers()
 * @method void destroyServer($id)
 */
class DigitaloceanApiCached extends ObjectCacher {

  protected function getObject() {
    return new DigitaloceanApi;
  }

  function cachedMethods() {
    return [
      'servers',
      'sshKeys'
    ];
  }

  function cleanupMethods() {
    return [
      'createServer' => ['servers'],
      'createSshKey' => ['sshKeys']
    ];
  }

}