<?php

require_once('doFunc.php');
require_once('doSqlFunc.php');
require_once('./settings/config.php');
use BotMilanese\Setting\config;
use BotMilanese\Controller\Message\doFunc;
use BotMilanese\Controller\SQL\doSqlFunc;

$doFunc = new doFunc();
$doSqlFunc = new doSqlFunc();
$conf = new config();

// $doSqlFunc = new doSqlFunc();
//
// $message = [
//   	"type" => "text",
//   	"text" => '11'
//   ];
//
// $post_data2 = [
//  "to" => 'U015dc1cc36df8e76f4a313d8b1c3b769',
//  "messages" => [$message]
//  ];


$today = date('y-m-d');
$birthMessage = "誕生日おめでとうございます。その優しい笑顔でずっと元気でいて下さいね。";
$birthUser = $doSqlFunc->getBirthUser($today);

$birthUserNum = count($birthUser);
for($i=0;$i<$birthUserNum;$i++){
  $doFunc->pushMessage(["to" => $birthUser[$i],"messages" => [["type" => "text","text" => $birthMessage]]]);
}



?>
