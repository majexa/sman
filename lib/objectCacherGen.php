<?php

$r = new ReflectionMethod('DigitaloceanApiCached', 'getObject');
$r->getStartLine().' - '.$r->getEndLine();
return;
foreach (Lib::getClassesList() as $v) {
  if (ClassCore::hasAncestor($v['class'], 'ObjectCacher')) {
    //
  }
}


//ClassCore::getAncestorsByPrefix()