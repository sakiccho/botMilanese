<?php
/**
 * [API]LINE Messaging API コントローラークラス
 *
 * LINEのMessagigAPIの実行関数をまとめたコントローラークラス
 *
 * @access public
 * @author sakiccho <sakiccho01@gmail.com>
 * @copyright  sakiccho Corporation All Rights Reserved
 * @category Message
 * @package Controller
 */
 namespace BotMilanese\Controller\Message;
 require_once('./settings/config.php');
 use BotMilanese\Setting\config;

class doFunc extends config {

  /**
   * リプライメッセージを送信する
   * @return array $post_data
   */
  function sendMessage($post_data){

    $ch = curl_init("https://api.line.me/v2/bot/message/reply");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $this->header);
    $result = curl_exec($ch);
    curl_close($ch);
  }


  /**
   * botからメッセージを送信する
   * @param array $post_data
   */
  function pushMessage($post_data){

    $ch = curl_init("https://api.line.me/v2/bot/message/push");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $this->header);
    $result = curl_exec($ch);
    curl_close($ch);
  }


  /**
   * グループ / トークルームからbotを追い出す
   * @param string $placeType
   * @param string $placeId
   */
  function getLeave($placeType, $placeId){
    $ch = curl_init("https://api.line.me/v2/bot/{$placeType}/{$placeId}/leave");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $this->header);
    $result = curl_exec($ch);
    curl_close($ch);
  }


  /**
   * ユーザーのプロフィールを取得して表示名を返す
   * @param array $uerId
   * @return string $displayName
   */
  function getProfile($uerId){
    $ch = curl_init("https://api.line.me/v2/bot/profile/{$uerId}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $this->header);
    $result = curl_exec($ch);

    $jsonObj = json_decode($result);
    $displayName = $jsonObj->displayName;
    curl_close($ch);
    return $displayName;
  }
}
