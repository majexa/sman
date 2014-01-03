<?php

/**
 * @method bool send(string $localFile, string $remoteFile, int $createMode = 0644)
 * @method bool recv(string $remoteFile, string $localFile)
 */
class SshScp extends SshBase {

  public function __call($func, $args) {
    $func = "ssh2_scp_$func";
    if (function_exists($func)) {
      array_unshift($args, $this->connection);
      return call_user_func_array($func, $args);
    }
    else {
      throw new Exception($func.' is not a valid SCP function');
    }
  }

  function copy($file) {
    $this->send($this->connection, $file, $file);
  }

}