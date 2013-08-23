<?php

define('NGN_PATH', dirname(__DIR__).'/ngn');
require_once NGN_PATH.'/init/core.php';
require_once NGN_PATH.'/init/cli.php';
define('LOGS_PATH', __DIR__.'/logs');
define('DATA_PATH', __DIR__.'/data');
//define('DATA_CACHE', false);

Lib::addFolder(__DIR__.'/lib');

function replaceIp($k, $ip) {
  file_put_contents('all', preg_replace("/^$k=.*$/m", "$k=$ip", file_get_contents('all')));
}
