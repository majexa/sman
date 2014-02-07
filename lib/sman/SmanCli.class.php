<?php

if (!defined('SMAN_PATH')) throw new Exception('sman not initialized');

class SmanCli extends CliHelpArgs {

  protected $oneEntry = 'sman';

  protected function prefix() {
    return 'sman';
  }

}