<?php

require __DIR__.'/init.php';

class ObjectCacherCodeGen {

  function getObjectClass($chachedClass) {
    $r = new ReflectionMethod($chachedClass, 'getObject');
    $strings = file(Lib::getClassPath($chachedClass));
    for ($i = $r->getStartLine(); $i <= $r->getEndLine(); $i++) {
      if (preg_match('/return new (\w+)/', $strings[$i], $m)) return $m[1];
    }
    return false;
  }

  function gen() {
    //die2(Lib::getClassesList());
    //die2(ClassCore::hasAncestor('DigitaloceanApiCached', 'ObjectCacher'));
    die2(ClassCore::getDescendants('ObjectCacher', false));
  }

}


//(new DigitaloceanApiObjectCacher)->

//(new ObjectCacherCodeGen)->a();

print "$".ObjectCacher::getObjectClass('DigitaloceanApiCached').'$';

//$o->api->createServer('dnsMaster', ['sshKeys' => ['installer']]);
//$o->api->genKey('dnsMaster'); // gen key on "dnsMaster"

// login to git. add key to authorized_keys
// add key to Digital Ocean
// install dnsMaster env from git
// createDroplet "dnsSlave" with key "dnsMaster"


// -o StrictHostKeyChecking=no
