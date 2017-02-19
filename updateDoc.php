<?php


$c = `sman pure projects`;
$c = preg_replace('/^(.*)$/m', '    $1', $c);
file_put_contents(dirname(__DIR__).'/projects/doc/site/data/docTpl/install.md', $c);