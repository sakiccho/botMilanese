<?php
/**
 * [API]LINE Messaging API コントローラークラス
 *
 * LINEのMessagigAPIの実行関数をまとめたコントローラークラス
 *
 * @access public
 * @author sakiccho <sakiccho@gmail.com>
 * @copyright  sakiccho Corporation All Rights Reserved
 * @category Message
 * @package Controller
 */
class doFunc {

  private $header = array(
     'Content-Type: application/json; charser=UTF-8',
     'Authorization: Bearer ZLXujrYwwY1leuDg5zms46WQCf+D9geBmRPT41N/HORWxcRZuQnCWT5gE1gaSOCvSPEC3MtGh2SdPIQowdN6+rkYHRorOfkXHCZOKm834vNIxrsIBJ4fTEWPNQMC8k/ufuxXJ4QZORbm123yDlGHewdB04t89/1O/w1cDnyilFU='
   );


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


  /**
   * ログを出力する
   * @param var $_arr
   * @return bool false
   */
  function getLog($_arr){
     ob_start();
     var_dump($_arr);
     $_log = ob_get_contents();
     ob_end_clean();
     $fp = fopen('log.txt','w');
     fwrite($fp,$_log);
     fclose($fp);
     return false;
  }
}

/**
 * [API]MySQL操作系 コントローラークラス
 *
 * インサート、セレクトなどの実行関数をまとめたコントローラークラス
 *
 * @access public
 * @author sakiccho <sakiccho@gmail.com>
 * @copyright  sakiccho Corporation All Rights Reserved
 * @category Message
 * @package Controller
 */
class doSqlFunc{
  private $pdo;

  function __construct(){
    $db_username = 'LAA0736454';
    $db_password = 'Mtj2rZe682Vx';
    $dsn = 'mysql:dbname=LAA0736454-data;host=mysql111.phy.lolipop.lan;charset=utf8';

    //$db_username = 'root';
    //$db_password = 'root';
    //$dsn = 'mysql:localhost;host=lolipop;charset=utf8';
    try {
      $this->pdo = new PDO($dsn, $db_username, $db_password);
    } catch (PDOException $e){
      die();
    }
  }

  function insertMessage($userId, $text, $eventId){
    $insertSql = "INSERT INTO `tbl_message`(`eventId`, `userId`, `message`) VALUES ({$eventId}, '{$userId}', '{$text}');";
    $insertResult = $this->pdo->query($insertSql);
  }

  function selectUserData($userId){

    //ユーザー存在チェック
    $sql = "SELECT * FROM `tbl_user` where `userId` = '{$userId}';";
    $result = $this->pdo->query($sql);

    $userResult = $result -> fetch(PDO::FETCH_ASSOC);
    $dbProfile = array(
      'userId' => $userResult['userId'],
      'displayName' => $userResult['displayName'],
      'gender' => $userResult['gender'],
      'birthDate' => $userResult['birthDate'],
      'nickName' => $userResult['nickName']
    );
    return $dbProfile;
  }

  /**
   * メッセージ登録処理用の関数
   *
   * メッセージ登録後、送信者情報も更新する。
   *
   * @access public
   * @param string $userId
   *          送信者の識別ID
   * @param string $displayName
   *          送信者の表示名
   * @todo executeに変更、Injection対策追加
   */
  function insertUserData($userId, $displayName){
    //存在しないユーザーの場合インサートする
    $inserSql = "INSERT INTO `tbl_user`(`userId`, `displayName`) VALUES ('{$userId}', '{$displayName}');";
    $insertResult = $this->pdo->query($inserSql);
  }

}
