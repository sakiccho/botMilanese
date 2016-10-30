<?php
/**
 * [API]メッセージ送信系APIコントローラークラス
 *
 * メッセージ送信に関するAPIをまとめたコントローラークラス。
 * エンドポイント単位でメソッドを定義する。
 *
 * @access public
 * @author itosho <hogehoge@example.com>
 * @copyright  hogehoge Corporation All Rights Reserved
 * @category Message
 * @package Controller
 */

class mySet {

  //==========================
  // DB Info
  //==========================
  function connectDB(){

    $db_username = 'LAA0736454';
    $db_password = 'Mtj2rZe682Vx';
    $dsn = 'mysql:dbname=LAA0736454-data;host=mysql111.phy.lolipop.lan;charset=utf8';

    //$db_username = 'root';
    //$db_password = 'root';
    //$dsn = 'mysql:localhost;host=lolipop;charset=utf8';
    try {
      $pdo = new PDO($dsn, $db_username, $db_password);
    } catch (PDOException $e){
      die();
    }
    return $pdo;
  }

  //==========================
  // Bot settings
  //==========================
}
