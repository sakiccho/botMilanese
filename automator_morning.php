<?php
require_once('doFunc.php');
require_once('doSqlFunc.php');
require_once('./settings/config.php');
use BotMilanese\Setting\config;
use BotMilanese\Controller\Message\doFunc;
use BotMilanese\Controller\SQL\doSqlFunc;

sendWeatherMessage();

/**
 * 明日の天気に注意喚起が必要な地域に住むユーザーにメッセージを送信する
 *
 * @access public
 */
function sendWeatherMessage(){
  $doFunc = new doFunc();
  $doSqlFunc = new doSqlFunc();
  $conf = new config();
  $hasLocateUser = $doSqlFunc->getUserInfoWeatherWarning();
  $userCount = count($hasLocateUser);
  for($i=0;$i<=$userCount;$i++){
    $getWeatherUrl = 'http://api.openweathermap.org/data/2.5/forecast/city?id='.$hasLocateUser[$i]['id'].'&APPID='.$conf->weatherApiKey;
    $weather = file_get_contents($getWeatherUrl);
    $weather = json_decode($weather);
    $description = $weather->{'list'}[0]->{'weather'}[0]->{'description'};
    $location = $conf->Roman2Kana($weather->{'city'}->{'name'}, 'kana');
    $location = str_replace('ー','',$location);
    if(!is_null($hasLocateUser[$i]['nickName'])){
      $userName = $hasLocateUser[$i]['nickName'];
    } else {
      $hasLocateUser[$i]['displayName'];
    }
    switch(true){
      case preg_match('/light rain/', $description):
      $weatherText = '今日の'.$location.'は小雨だよ。傘をもってったほうがいいかもね！';
      break;
      case preg_match('/heavy intensity rain/', $description):
      $weatherText = '今日の'.$location.'は大雨だよ。気をつけてね！'.$userName;
      break;
      case preg_match('/very heavy rain/', $description):
      $weatherText = '今日の'.$location.'は豪雨だよ。いきてかえってね！';
      break;
      case preg_match('/rain/', $description):
      $weatherText = '今日の'.$location.'は雨だよ。傘を忘れずにね'.$userName;
      break;
      case preg_match('/drizzle/', $description):
      $weatherText = '今日の'.$location.'は霧だよ。運転にはきおつけてね（；-；）';
      break;
      case preg_match('/snow/', $description):
      $weatherText = '今日の'.$location.'は雪だよ。'.$userName.'たすけて（；-；）';
      break;
      default:
      $weatherText = null;
      break;
    }
    if(!is_null($weatherText)){
      $doFunc->pushMessage(["to" => $hasLocateUser[$i]['userId'],"messages" => [["type" => "text","text" => $weatherText]]]);
    }
  }
}
?>
