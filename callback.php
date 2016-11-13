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

/** *=================================================================================================================
* データ取得処理
* ================================================================================================================== */
 $json_string = file_get_contents('php://input');
 $jsonObj = json_decode($json_string);
 $eventType = $jsonObj->{"events"}[0]->{"type"};

 define('TYPE', $jsonObj->{"events"}[0]->{"message"}->{"type"}); //text,stickerなど
 define('TEXT', $jsonObj->{"events"}[0]->{"message"}->{"text"}); //送信されたメッセージ
 define('PLACETYPE', $jsonObj->{"events"}[0]->{"source"}->{"type"}); //room,group,user
 if(PLACETYPE !== 'user'){
   define('SENDERID', $jsonObj->{"events"}[0]->{"source"}->{PLACETYPE."Id"}); //ルーム,グループID
 } else {
   define('SENDERID', $jsonObj->{"events"}[0]->{"source"}->{"userId"}); //送信者の識別ID
   define('DISPLAYNAME', $doFunc->getProfile(SENDERID)); //送信者のLINE上での表示名
 }
 define('REPLYTOKEN', $jsonObj->{"events"}[0]->{"replyToken"}); //一度のみ使用可の返信用トークン
 define('POSTBACK', $jsonObj->{"events"}[0]->{"postback"}->{"data"}); //ボタンテンプレートの戻り値


 /** *=================================================================================================================
 * フォローイベント時
 * ================================================================================================================== */
 if($eventType == 'unfollow'){
   $doSqlFunc->changeFollowStatus(SENDERID,0);
 } else if($eventType == 'follow'){
   $doSqlFunc->changeFollowStatus(SENDERID,1);
 }

 /** *=================================================================================================================
 * 返信内容作成処理
 * ================================================================================================================== */
 // if(SENDERID=='U015dc1cc36df8e76f4a313d8b1c3b769'){
 //
 // }
 $sendContent = [
   "type" => "template",
   "altText" => "ちょ、スマホのLINEでみてこれ",
   "template"=> [
            "type" => "carousel",
              "columns" => [
                "text" => "description",
                "actions" => [
                    [
                        "type" => "postback",
                        "label" => "Buy",
                        "data" => "action=buy&itemid=111"
                    ],
                    [
                        "type" => "postback",
                        "label" => "Buy",
                        "data" => "action=buy&itemid=111"
                    ],
                    [
                        "type" => "uri",
                        "label" => "Buy",
                        "uri" => "action=buy&itemid=111"
                    ]
                  ]
                ],[
                  "text" => "description",
                  "actions" => [
                      [
                          "type" => "postback",
                          "label" => "Buy",
                          "data" => "action=buy&itemid=111"
                      ],
                      [
                          "type" => "postback",
                          "label" => "Buy",
                          "data" => "action=buy&itemid=111"
                      ],
                      [
                          "type" => "uri",
                          "label" => "Buy",
                          "uri" => "action=buy&itemid=111"
                      ]
                    ]
                  ]
       ]

 ];
 $doFunc->pushMessage(["to" => 'U015dc1cc36df8e76f4a313d8b1c3b769',"messages" => [$sendContent]]);

 /**
 * #1 POSTBACKにデータが存在する場合
 */
 if(!is_null(POSTBACK)){
   $postData = explode("=",POSTBACK); //array0にカラム名, 2にインサート値が入る
   $sendContent = [
    "type" => "text",
    "text" => "おっけ、ありがとー"
   ];
   $doSqlFunc->insertPostData(SENDERID,$postData);

 /**
 * #2 通常の返信作成処理
 */
 } else {
   if(PLACETYPE == 'user'){
     //DBからユーザー情報を取得
     $GL_DbUserData = $doSqlFunc->getUserData(SENDERID);
     //DBから最終メッセージを取得
     $GL_LatestMessage = $doSqlFunc->getLatestMessage(SENDERID);
     //最終メッセージからユーザーステータスを取得する
     $statusId = (int)$GL_LatestMessage['statusId'];
   } else {
     //グループの場合には待ち状態などのイベントは発生しないので1をセットする
     $statusId = 1;
   }

   if(PLACETYPE == 'user'){
     if(!is_null($GL_DbUserData['nickName'])){
       $GL_CallName = $GL_DbUserData['nickName'];
     } else {
       $GL_CallName = DISPLAYNAME;
     }

   } else {
     $GL_CallName = 'みんな';
   }

   /**
   * tbl_userに空のカラムがある時は一定確率でユーザー情報を尋ねるイベントを発生させる
   */
   $nullColumnList = array();
   $columnList = array('gender','birthDate');
   $columnListNum = count($columnList);
   for($i = 0; $i < $columnListNum; $i++){
     if(is_null($GL_DbUserData[$columnList[$i]])){
       //値がNullのカラム名を取得して配列に入れる
       array_push($nullColumnList, $columnList[$i]);
     }
   }
   //nullカラム個数
   $nullColumnListNum = count($nullColumnList);

   /**
   * statusIdの属性
   * 1:通常状態
   * 2:Genderの返信待ち状態
   * 3:Birthdateの返信待ち状態
   * 4:Nicknameの処理中状態
   */
   //ユーザー情報がDBに存在 & イベントのレシーブ待ちではない & Nullのカラムが1つ以上存在 & グループではない
   if(!is_null($GL_DbUserData['userId']) && $statusId == 1 && $nullColumnListNum !== 0 && PLACETYPE == 'user'){
     $eventFrag = mt_rand(1,30);
   }

   //たまにニックネームを更新する
   if($eventFrag !== 1){
     if(!is_null($GL_DbUserData['userId']) && $statusId == 1 && PLACETYPE == 'user'){
       $nickNameFrag = mt_rand(1,30);
     }
   }

   if($eventFrag == 1){
     $targetColumn = mt_rand(0,$nullColumnListNum-1);
     $sendContent = createSpecialContent($nullColumnList[$targetColumn]);
   } else if ($nickNameFrag == 1){
     $sendContent = [
       "type" => "text",
       "text" => "私たちだいぶ仲良くなってきたからわたしが君のあだ名つけてあげるね！"
     ];
     $GLOBALS['GL_StatusId'] = 4;
   } else if($statusId !== 1){
       //イベント待ち状態のユーザーに対する処理
       switch($statusId){
         case 3: //Ask bitgh date
         list($birthDate, $replyMessage) = validateBirthDate();
         if(!is_null($birthDate)){
           $doSqlFunc->insertPostData(SENDERID,array('birthDate', $birthDate));
         }
         break;
       }
       $sendContent = [
         "type" => "text",
         "text" => $replyMessage
       ];
       $GL_StatusId = 1; //ユーザーを通常状態に戻す
     } else {
       //通常ユーザーに対する処理（メッセージを読み取って返す）
       switch(TYPE){
         case 'text':
         $sendContent = createTextContent();
         break;
         case 'sticker':
         $sendContent = createStickerContent();
         break;
       }
       $GL_StatusId = 1;
     }
 }

 /**
 * #3 送信処理
 */
 $post_data = [
 	"replyToken" => REPLYTOKEN,
 	"messages" => [$sendContent]
 	];

 $doFunc->sendMessage($post_data);

 //ニックネーム送信
 if($GL_StatusId == 4){
   $nickName = createNickName();
   $doFunc->pushMessage(["to" => SENDERID,"messages" => [["type" => "text","text" => 'えっとねー']]]);
   $doFunc->pushMessage(["to" => SENDERID,"messages" => [["type" => "text","text" => $nickName.'！きにいった？']]]);
   $doSqlFunc->insertPostData(SENDERID,array('nickName',$nickName));
   $GL_StatusId = 1;
 }

 /**
 * #4 送信後処理(DB操作etc)
 */
 //追い出し
 if($GL_TextType == 5){
   $doFunc->getLeave(PLACETYPE, SENDERID);
 }

 //メッセージをDBに格納
 $doSqlFunc->insertMessage(SENDERID, TEXT, $GL_StatusId); //Todo:ハッシュ化

 //DBにユーザーが存在しない場合は新規登録する
 if(PLACETYPE == 'user' && is_null($dbUserData['userId'])){
   $doSqlFunc->insertUserData(SENDERID, DISPLAYNAME);
 }


 /** *=================================================================================================================
 * ユーザー定義関数
 * ================================================================================================================== */
/**
 * テキストメッセージを作成する
 *
 * @return array $sendContent
 *          コンテンツタイプ, テキストメッセージ
 */
function createTextContent(){
  $mPtJsonObj = getDictJson('json/mPt.json'); //検索対象文字列
  $rPtJsonObj = getDictJson('json/rPt.json'); //返信候補文字列
  $matchTypeLength = count($mPtJsonObj['pattern']);
  $typeCount = 0;
  if(!is_null($GLOBALS['GL_DbUserData']['userId'])){
    //前回と同じメッセージの場合
    if($GLOBALS['GL_LatestMessage']['message'] == TEXT){ //Todo:グループの場合
      $textType = 7;
    } else {
      //感情タイプを内容から判定
      $textType = isMatch($mPtJsonObj['pattern'], TEXT, $matchTypeLength);
    };
  } else {
    $textType = isMatch($mPtJsonObj['pattern'], TEXT, $matchTypeLength);
  };



  $column = getColumnNumber($rPtJsonObj['pattern'], $textType);
  //返信メッセージ番号をランダムに選択
  $replyNumber = mt_rand($column['begin'], $column['end']);
  $replyMessage = $rPtJsonObj['pattern'][$replyNumber][1];

  //置換
  $replyMessage = str_replace("*name*", $GLOBALS['GL_CallName'], $replyMessage);
  $sendContent = [
  	"type" => "text",
  	"text" => $replyMessage
  ];
  $GLOBALS['GL_TextType'] = $textType;

  return $sendContent;
}

function createSpecialContent($targetCol){
  //$targetCol = 'gender'; //debug
  $conf = new config();
  $sendContent = array();
  switch($targetCol){
    case 'gender':
    $sendContent = [
      "type" => "template",
      "altText" => "ちょ、スマホのLINEでみてこれ",
      "template"=> [
          "type" => "confirm",
          "text" => "ところで君って女だっけ？男だっけ？おしえて！",
          "actions" => [
              [
                "type" => "postback",
                "label" => "男だよ",
                "data" => "gender=1"
              ],
              [
                "type" => "postback",
                "label" => "女よ",
                "data" => "gender=2"
              ]
          ]
      ]
    ];
    //$GLOBALS['GL_StatusId'] = 2; //まぁ本当は必要ないけど・・
    break;
    case 'birthDate':
    $sendContent = [
      "type" => "text",
      "text" => "ねー君誕生日いつだっけ？8桁で教えてね！"
    ];
    $GLOBALS['GL_StatusId'] = 3;
    break;
  }

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
    $text = $targetArray[$i][1];
    if(preg_match("/$text/",$targetString)){
      return $targetArray[$i][0];
    }
  }

  return 0;
}

function getColumnNumber($searchArray ,$typeNum){
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

function validateBirthDate(){
  $error = true;
  $validateDate = mb_convert_kana(TEXT, "n", "utf-8");
  if(strlen($validateDate) !== 8){
    $replyMsg = "8桁いうとるやろころすぞ";
    $error = false;
    $validateDate = Null;
  } else {
    $Y = substr($validateDate,0,4);
    $m = substr($validateDate,4,2);
    $d = substr($validateDate,6);

    if(checkdate($m, $d, $Y)){
      $replyMsg = 'おっけ、おぼえたよ！';
    } else {
      $replyMsg = "まじめにやれぼけ";
      $error = false;
      $validateDate = Null;
    }
  }
  return $validateMessage = array($validateDate, $replyMsg);
}

function createNickName(){
  $firstName = getDictJson('json/firstName.json');
  $lastName = getDictJson('json/lastName.json');
  $nickName = $firstName[mt_rand(0,count($firstName)-1)][0] . $lastName[mt_rand(0,count($lastName)-1)][0];

  return $nickName;
}

?>
