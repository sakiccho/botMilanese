<?php

require_once('setting.php');
require_once('doFunc.php');

$doFunc = new doFunc();

$message = [
  	"type" => "text",
  	"text" => '11'
  ];

$post_data2 = [
 "to" => 'U015dc1cc36df8e76f4a313d8b1c3b769',
 "messages" => [$message]
 ];

$doFunc->pushMessage($post_data2);



?>
