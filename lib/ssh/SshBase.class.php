<?php

class SshBase {

  protected $connection;

  function __construct(SshConnection $connection) {
    $this->connection = $connection();
  }

}