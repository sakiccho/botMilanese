<?php
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
  namespace BotMilanese\Controller\SQL;
  require_once('./settings/config.php');
  use BotMilanese\Setting\config;
  use \PDO;
  use \PDOException;

class doSqlFunc extends config{
  private $pdo;

  function __construct(){
    try {
      $this->pdo = new PDO($this->dsn,$this->db_username,$this->db_password,array(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES => false));
    } catch (PDOException $e){
      $this->getLog($e->getMessage());
      //die();
    }
  }

  /**
   * ユーザーから送信されたメッセージをDBに登録する
   * インジェクション対策のためexecuteメソッドを使用している。
   *
   * @access public
   * @param int $userId
   *          送信者の識別ID
   * @param string $text
   *          送信されたメッセージ
   * @param int $statusId
   *          イベントフラグ
   */
  function insertMessage($userId, $text, $statusId){
    if(is_null($text)){$text='**exceptForText**';}
    try{
      $prepare = $this->pdo->prepare("INSERT INTO `tbl_message`(`statusId`, `userId`, `message`) VALUES (?, ?, ?);");
      $prepare->bindValue(1,$statusId,PDO::PARAM_INT);
      $prepare->bindParam(2,$userId);
      $prepare->bindParam(3,$text);
      $prepare->execute();
    } catch(PDOException $e){
      $this->getLog($e->getMessage());
    }
  }

  /**
   * ユーザー情報を更新する
   *
   * @access public
   * @param int $userId
   *          送信者の識別ID
   * @param array $postData
   *          送信されたPOSTDATA, 0:更新対象カラム, 1:更新内容
   * @param int $statusId
   *          イベントフラグ
   */
  function insertPostData($userId, $postData){
    try{
      $prepare = $this->pdo->prepare("UPDATE `tbl_user` SET {$postData[0]} = ? WHERE `userId` = ?;");$this->getLog($prepare);
      $prepare->bindValue(1,$postData[1],PDO::PARAM_INT);
      $prepare->bindParam(2,$userId);
      $prepare->execute();

    } catch(PDOException $e){
      $this->getLog($e->getMessage());
    }
  }

  /**
   * ユーザーIDからDBに登録されているユーザー情報を取得する
   *
   * @access public
   * @param int $userId
   *          送信者の識別ID
   * @return array $dbProfile, bool
   *          取得されたユーザー情報
   */
  function getUserData($userId){

    //ユーザー存在チェック
    $sql = "SELECT * FROM `tbl_user` WHERE `userId` = 'U015dc1cc36df8e76f4a313d8b1c3b769';";
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
   * ユーザーから最後に送られたメッセージを取得する
   *
   * @access public
   * @param int $userId
   *          送信者の識別ID
   * @return array $dbMessage
   *          イベントID,メッセージ
   */
  function getLatestMessage($userId){
    //ユーザー存在チェック
    $sql = "SELECT `statusId`, `message` FROM `tbl_message` WHERE `userId` = '{$userId}' ORDER BY `sendDate` DESC LIMIT 0,1;";
    $result = $this->pdo->query($sql);
    $lastMessageResult = $result -> fetch(PDO::FETCH_ASSOC);
    $dbMessage = array(
      'statusId' => $lastMessageResult['statusId'],
      'message' => $lastMessageResult['message'],
    );
    return $dbMessage;
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
