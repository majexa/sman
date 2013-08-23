<?php

class DigitaloceanApi {

  public $outputResult = false;

  function api($uri, array $data = []) {
    $r = json_decode(file_get_contents("https://api.digitalocean.com/$uri?client_id=aeOp6fwVrvbS0Sijbyj7A&api_key=WrgjH5DimPjU1NdG00VPWOR2vpShoRKoBZREHO2uD&".http_build_query($data)), true);
    if ($this->outputResult) print_r($r);
    return $r;
  }

  function createServer($name, array $opts = []) {
    $r = [
      'size_id'   => 66,
      'image_id'  => 284211,
      'region_id' => 2,
      'name'      => $name
    ];
    if (!empty($opts['sshKeys'])) {
      $r['ssh_key_ids'] = implode(',', Arr::filterBySubValues($this->api('ssh_keys')['ssh_keys'], 'id', 'name', $opts['sshKeys']));
    }
    return $this->api('droplets/new', $r);
  }

  function servers() {
    return $this->api('droplets')['droplets'];
  }

  function _destroyServer($id) {
    return $this->api("droplets/$id/destroy");
  }

  function destroyDroplet($name) {
    foreach ($this->servers() as $v) if ($v['name'] == $name) $this->_destroyServer($v['id']);
  }

}
