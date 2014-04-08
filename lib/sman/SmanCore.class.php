<?php

class SmanCore {

  static function serverTypes() {
    return array_values(ClassCore::getNames('SmanEnv'));
  }

  static function serverType($name) {
    if ($name == 'local') return trim(file_get_contents(SMAN_PATH.'/.type'));
    $type = Misc::checkEmpty(preg_replace('/(\D+)\d+/', '$1', $name), "Name '$name' has wrong format");
    if (!in_array($type, self::serverTypes())) throw new Exception("Undefined env/instance type '$type'");
    return $type;
  }


  /**
   * @return array
   */
  static function servers() {
    $servers = [
      [
        'name' => 'local',
        'ip_address' => '127.0.0.1'
      ]
    ];
    if (($r = Config::getVar('doceanAccess', true))) $servers = array_merge($servers, $r);
    return $servers;
  }

}