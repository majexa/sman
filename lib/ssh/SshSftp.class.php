<?php

class SshSftp {

  protected $sftp;

  function __construct(SshConnection $connection) {
    $this->sftp = ssh2_sftp($connection());
  }

  public function __call($func, $args) {
    $func = "ssh2_sftp_$func";
    if (function_exists($func)) {
      array_unshift($args, $this->sftp);
      return call_user_func_array($func, $args);
    }
    else {
      throw new Exception($func.' is not a valid SFTP function');
    }
  }

}