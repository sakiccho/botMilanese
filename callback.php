<?php

require_once('config.php');

 //output log
 function getLog($_arr){

    /*
        var_dumpの内容を$_logに格納
    /*---------------------------------------*/
    //var_dumpを出力せず、一旦寄せておく設定にする
    ob_start();

    //var_dumpでデバッグしたい内容を寄せておく
    var_dump($_arr);

    //寄せておいたvar_dumpの結果を文字列として取得
    $_log = ob_get_contents();

    //var_dumpの出力設定を解除
    ob_end_clean();

    /*
        log.txtに格納
    /*---------------------------------------*/
    //linebotディレクトリに用意したlog.txtに書き込みできるようにする
    $fp = fopen('log.txt','w');

    //var_dumpの内容を記録した$_logをlog.txtに上書き
    fwrite($fp,$_log);

    //log.txtを閉じる
    fclose($fp);

    //return
    return false;
}
 $globalTextType = 0;

 $requestHeader = array(
     'Content-Type: application/json; charser=UTF-8',
     'Authorization: Bearer ' . $accessToken
   );

 //ユーザーからのメッセージをjson形式で取得
 $json_string = file_get_contents('php://input');
 $jsonObj = json_decode($json_string);
 //メッセージ種別・テキスト本文・ユーザーID
 $type = $jsonObj->{"events"}[0]->{"message"}->{"type"};
 $text = $jsonObj->{"events"}[0]->{"message"}->{"text"};
 $userId = $jsonObj->{"events"}[0]->{"source"}->{"userId"};
 $placeType =  $userId = $jsonObj->{"events"}[0]->{"source"}->{"type"}; //room or user
 //ReplyToken
 $replyToken = $jsonObj->{"events"}[0]->{"replyToken"};

 $dbUserData = selectUserData($userId);

 if(is_null($dbUserData['userId'])){
   insertUserData($requestHeader, $userId);
 }

  //Typeによって返信内容を変える
  if($type == "text"){
    $sendContent = createTextContent($text, $dbUserData, $requestHeader, $replyToken);
  } else if($type == "sticker"){
    $sendContent = createStickerContent();
  }

 $post_data = [
 	"replyToken" => $replyToken,
 	"messages" => [$sendContent]
 	];

sendMessage($requestHeader, $post_data);
if($globalTextType == 5){
  getLeave($placeType, $jsonObj->{"events"}[0]->{"source"}->{$placeType."Id"}, $requestHeader);
}

function createTextContent($missiveText, $dbUserData, $requestHeader, $replyToken){
  $mPtJsonObj = getDictJson('json/mPt.json'); //検索対象文字列
  $rPtJsonObj = getDictJson('json/rPt.json'); //返信候補文字列
  $matchTypeLength = count($mPtJsonObj['pattern']);
  $recieveTypeLength = count($rPtJsonObj['pattern']);
  $typeCount = 0;
  $beginPos = array();

  //感情タイプを判定
  $textType = isMatch($mPtJsonObj, $missiveText, $matchTypeLength);

  //返信タイプのJsonファイル内での開始と終了位置を取得
  for($i=0;$i<$recieveTypeLength;$i++){
    if($rPtJsonObj['pattern'][$i][0] == $textType){
     array_push($beginPos,$i);
     $currentType = $rPtJsonObj['pattern'][$i][0];
    }
  }
  //返信メッセージ番号をランダムに選択
  $replyNumber = mt_rand($beginPos[0], $beginPos[count($beginPos) - 1]);
  $replyMessage = $rPtJsonObj['pattern'][$replyNumber][1];

  //置換
  $replyMessage = str_replace("*name*",$dbUserData['displayName'],$replyMessage);

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
 *
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

/**
 * ユーザーのプロフィールを取得して表示名を返す
 *
 * @param array $requestHeader
 * @param string $userId
 * @return string $displayName
 */
function getProfile($requestHeader, $userId){
  $ch = curl_init("https://api.line.me/v2/bot/profile/{$userId}");
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER, $requestHeader);
  $result = curl_exec($ch);
  $jsonObj = json_decode($result);
  $displayName = $jsonObj->displayName;
  return $displayName;
}

function sendMessage($requestHeader, $post_data){

  $ch = curl_init("https://api.line.me/v2/bot/message/reply");
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
  curl_setopt($ch, CURLOPT_HTTPHEADER, $requestHeader);
  $result = curl_exec($ch);
  curl_close($ch);
}

function getLeave($placeType, $placeId, $requestHeader){
  $ch = curl_init("https://api.line.me/v2/bot/{$placeType}/{$placeId}/leave");
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER, $requestHeader);
  $result = curl_exec($ch);
  curl_close($ch);
}

function selectUserData($userId){
  //ユーザー存在チェック
  $sql = "SELECT * FROM `tbl_user` where `userId` = '{$userId}';";
  $result = mysql_query($sql);
  $userResult = mysql_fetch_assoc($result);
  $dbProfile = array(
    'userId' => $userResult['userId'],
    'displayName' => $userResult['displayName']
  );
  return $dbProfile;
}

function insertUserData($requestHeader, $userId){

  //存在しないユーザーの場合インサートする

    $displayName = getProfile($requestHeader, $userId);
    $inserSql = "INSERT INTO `tbl_user`(`userId`, `displayName`) VALUES ('{$userId}', '{$displayName}');";
    $insertResult = mysql_query($inserSql);

}

?>
