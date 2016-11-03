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

 $json_string = file_get_contents('php://input');
 $jsonObj = json_decode($json_string);
 define('TYPE', $jsonObj->{"events"}[0]->{"message"}->{"type"}); //text,stickerなど
 define('TEXT', $jsonObj->{"events"}[0]->{"message"}->{"text"}); //送信されたメッセージ
 define('PLACETYPE', $jsonObj->{"events"}[0]->{"source"}->{"type"}); //room,group,user
 define('REPLYTOKEN', $jsonObj->{"events"}[0]->{"replyToken"}); //一度のみ使用可の返信用トークン
 define('USERID', $jsonObj->{"events"}[0]->{"source"}->{"userId"}); //送信者の識別ID
 define('DISPLAYNAME', $doFunc->getProfile(USERID)); //送信者の表示名

 //DBからユーザー情報を取得
 $dbUserData = $doSqlFunc->selectUserData(USERID);

 //DBにユーザーが存在しない場合は新規登録する
 if(PLACETYPE == 'user' && is_null($dbUserData['userId'])){
   $doSqlFunc->insertUserData(USERID, DISPLAYNAME);
 }

  //Typeごとに送信コンテンツを作成
  switch(TYPE){
    case 'text':
    $sendContent = createTextContent(TEXT);
    break;

    case 'sticker':
    $sendContent = createStickerContent();
    break;
  }

 $post_data = [
 	"replyToken" => REPLYTOKEN,
 	"messages" => [$sendContent]
 	];

 $doFunc->sendMessage($post_data);

//追い出し
if($globalTextType == 5){
  $doFunc->getLeave(PLACETYPE, $jsonObj->{"events"}[0]->{"source"}->{PLACETYPE."Id"});
}

//メッセージをDBに格納
$doSqlFunc->insertMessage(USERID, TEXT, 1);



function createTextContent($missiveText){
  $mPtJsonObj = getDictJson('json/mPt.json'); //検索対象文字列
  $rPtJsonObj = getDictJson('json/rPt.json'); //返信候補文字列
  $matchTypeLength = count($mPtJsonObj['pattern']);
  $typeCount = 0;


  //感情タイプを判定
  $textType = isMatch($mPtJsonObj, $missiveText, $matchTypeLength);

  $column = getColumnNumber($rPtJsonObj['pattern'], $textType);


  //返信メッセージ番号をランダムに選択
  $replyNumber = mt_rand($column['begin'], $column['end']);
  $replyMessage = $rPtJsonObj['pattern'][$replyNumber][1];
  //置換
  $replyMessage = str_replace("*name*", DISPLAYNAME,$replyMessage);
  $sendContent = [
  	"type" => "text",
  	"text" => $replyMessage
  ];
  $GLOBALS['globalTextType'] = $textType;

  return $sendContent;
}

function createStickerContent(){
  //ランダムにスタンプを選択
  $stkPkgId = mt_rand(1,4); //Todo:欠番対応
  switch($stkPkgId){
    case 1:
    $stkId = mt_rand(1,430);
    break;
    case 2:
    $stkId = mt_rand(18,527);
    break;
    case 3:
    $stkId = mt_rand(180,259);
    break;
    case 4:
    $stkId = mt_rand(260,632);
    break;
  }
  return [
    "type" => "sticker",
    "packageId" => $stkPkgId,
    "stickerId" => $stkId
  ];
}

function getDictJson($url) {
  $json = file_get_contents($url);
  return json_decode($json,true);
}

/**
 * ユーザーから送られたテキストの感情タイプをjsonファイルから検索する
 * 一致しない場合は0(その他)を返す
 * @param array $targetArray
 * @param string $targetString
 * @param int $length
 * @return int
 */
function isMatch($targetArray, $targetString, $length){

  for($i=0;$i<$length;$i++){
    $text = $targetArray['pattern'][$i][1];
    if(preg_match("/$text/",$targetString)){
      return $targetArray['pattern'][$i][0];
    }
  }
  return 0;
}

function getColumnNumber($searchArray ,$typeNum){
  $a = new doFunc();

  $arrayLength = count($searchArray);

  $beginPos = array();
  //返信タイプのJsonファイル内での開始と終了位置を取得
  for($i=0;$i<$arrayLength;$i++){
    if($searchArray[$i][0] == $typeNum){
     array_push($beginPos,$i);
    }
  }

  $pos = [
    "begin" => $beginPos[0],
    "end" => $beginPos[0] + count($beginPos) - 1
  ];
  return $pos;
}

?>
