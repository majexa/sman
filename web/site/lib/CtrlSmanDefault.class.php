<?php

class CtrlSmanDefault extends CtrlCommonClearTpl {

  function action_default() {
    $form = new Form([
      [
        'type' => 'staticText',
        'text' => 'Majexa.ru предоставляет бесплатные домены 4-го уровня для установки Ngn-среды на ваш сервер. Введите желаемый логин ниже и вы получите скрипт для установки на веш сервер с уже привязанным доменом.'
      ],
      [
        'title' => 'Введите желаемый саб-домен',
        'name' => 'login',
        'type' => 'name',
      ],
      [
        'title' => 'Введите IP-адрес вашего сервера с голой Ubuntu/Debian',
        'name' => 'ip',
        'type' => 'text',
      ],
    ], [
      'submitTitle' => 'Получить команды для установки'
    ]);
    $this->d['form'] = $form->html();
    $this->d['tpl'] = 'common/form';
  }

}