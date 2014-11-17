<?php

class DoceanApi {
  use DebugOutput;

  public $outputResult = false;

  function api($uri, array $data = []) {
    $data = array_merge($data, Config::getVar('doceanAccess'));
    $r = (new Curl)->get("https://api.digitalocean.com/v1/$uri?".http_build_query($data));
    $r = json_decode($r, true);
    if ($r['status'] == 'ERROR') throw new Exception('DigitalOcean: '.$r['error_message']);
    if ($this->outputResult) print_r($r);
    return $r;
  }

  function createServer($name, array $opts = []) {
    if (Arr::getValueByKey($this->servers(), 'name', $name)) throw new Exception("Server with name '$name' already exists");
    output("Creating server '$name'");
    return $this->api('droplets/new', array_merge([
      'size_id'   => 66,
      'image_id'  => Arr::getValueByKey($this->api('images')['images'], 'slug', 'ubuntu-10-04-x64')['id'],
      'region_id' => 5, // Amsterdam: 5, US: 1, 4
      'name'      => $name
    ], $opts));
  }

  function createSshKey($name, $sshKey) {
    return $this->api('ssh_keys/new', [
      'name' => $name,
      'ssh_pub_key' => $sshKey
    ]);
  }

  function servers() {
    return $this->api('droplets')['droplets'];
  }

  function deleteServer($id) {
    return $this->api("droplets/$id/destroy");
  }

  function sshKeys() {
    return $this->api('ssh_keys')['ssh_keys'];
  }

  function deleteSshKey($id) {
    $this->api("ssh_keys/$id/destroy");
  }

}
