<?php

$c = `sman pure projects`;
file_put_contents(dirname(__DIR__).'/projects/doc/site/data/docTpl/install.md', $c);
$c = preg_replace('/^(.*)$/m', '    $1', $c);
file_put_contents(dirname(__DIR__).'/projects/doc/install/default.sh', $c);
