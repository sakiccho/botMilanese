<?php
require_once('doFunc.php');
require_once('doSqlFunc.php');
require_once('./settings/config.php');
use BotMilanese\Setting\config;
use BotMilanese\Controller\Message\doFunc;
use BotMilanese\Controller\SQL\doSqlFunc;

$today = date('y-m-d');
sendBirthMessage($today);

/**
 * 誕生日のユーザーにメッセージを送信する
 *
 * @access public
 * @param string $today
 */

function sendBirthMessage($today){
  $doFunc = new doFunc();
  $doSqlFunc = new doSqlFunc();
  $birthUserData = $doSqlFunc->getBirthUser($today);
  $birthMessage =  "ちゃん誕生日おめでとう！。その優しい笑顔でずっと元気でいてね。";
  $birthUserNum = count($birthUserData);
  for($i=0;$i<$birthUserNum;$i++){
    $doFunc->pushMessage(["to" => $birthUserData[$i]['userId'],"messages" => [["type" => "text","text" => $birthUserData[$i]['displayName'] . $birthMessage]]]);
  }
}
?>
