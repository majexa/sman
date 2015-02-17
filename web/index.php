<?php

define('PROJECT_KEY', 'sman');
define('WEBROOT_PATH', __DIR__);

require dirname(dirname(__DIR__)).'/ngn/init/web-standalone.php';
require dirname(__DIR__).'/init.php';

print (new DefaultRouter(['disableSession' => true]))->dispatch()->getOutput();