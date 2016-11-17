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

 if(PLACETYPE == 'user'){
   //DBからユーザー情報を取得
   $GL_DbUserData = $doSqlFunc->getUserData(SENDERID);
   //ユーザーステータスを取得する
   $statusId = (int)$GL_DbUserData['statusId'];
 }

 //DBから最終メッセージを取得
 $GL_LatestMessage = $doSqlFunc->getLatestMessage(SENDERID);

 /**
 * グループにおけるイベントフラグ
 * 1:通常状態
 * 2:天気イベント待ち状態
 */
 $groupEventStatus = 1;
 $whileWeather = false;
 if(preg_match('/天気|てんき/', TEXT)){
   $whileWeather = true;
   if(!is_null($GL_DbUserData['location'])){
     $weather = getWeather('明日', $GL_DbUserData['location']);
     $weather = str_replace('-','',$weather);
     $post_data = [
       "replyToken" => REPLYTOKEN,
       "messages" => [
         [
            "type" => "text",
            "text" => $weather
         ]
       ]
     ];
     $doFunc->sendMessage($post_data);
   } else {
     $post_data = [
       "replyToken" => REPLYTOKEN,
       "messages" => [
         [
            "type" => "text",
            "text" => "どこ住みだっけ"
         ]
       ]
     ];
     $doFunc->sendMessage($post_data);
     $groupEventStatus = 2;
   }
 }

 if($GL_LatestMessage['statusId'] == 2){
   $weather = getWeather('明日', TEXT);
   $weather = str_replace('-','',$weather);
   $post_data = [
     "replyToken" => REPLYTOKEN,
     "messages" => [
       [
          "type" => "text",
          "text" => $weather
       ]
     ]
   ];
   $doFunc->sendMessage($post_data);
   $groupEventStatus = 1;
 }

 // $sendContent = [
 //   "type" => "template",
 //   "altText" => "ちょ、スマホのLINEでみてこれ",
 //   "template"=> [
 //            "type" => "carousel",
 //              "columns" => [
 //                "text" => "description",
 //                "actions" => [
 //                    [
 //                        "type" => "postback",
 //                        "label" => "Buy",
 //                        "data" => "action=buy&itemid=111"
 //                    ],
 //                    [
 //                        "type" => "postback",
 //                        "label" => "Buy",
 //                        "data" => "action=buy&itemid=111"
 //                    ],
 //                    [
 //                        "type" => "uri",
 //                        "label" => "Buy",
 //                        "uri" => "action=buy&itemid=111"
 //                    ]
 //                  ]
 //                ],[
 //                  "text" => "description",
 //                  "actions" => [
 //                      [
 //                          "type" => "postback",
 //                          "label" => "Buy",
 //                          "data" => "action=buy&itemid=111"
 //                      ],
 //                      [
 //                          "type" => "postback",
 //                          "label" => "Buy",
 //                          "data" => "action=buy&itemid=111"
 //                      ],
 //                      [
 //                          "type" => "uri",
 //                          "label" => "Buy",
 //                          "uri" => "action=buy&itemid=111"
 //                      ]
 //                    ]
 //                  ]
 //       ]
 //
 // ];
 // $doFunc->pushMessage(["to" => 'U015dc1cc36df8e76f4a313d8b1c3b769',"messages" => [$sendContent]]);

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
   $doSqlFunc->changeEventStatus(SENDERID, 1);

 /**
 * #2 通常の返信作成処理
 */
 } else {

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
   if(PLACETYPE == 'user'){
     $nullColumnList = array();
     $columnList = array('gender','birthDate','location');
     $columnListNum = count($columnList);
     for($i = 0; $i < $columnListNum; $i++){
       if(is_null($GL_DbUserData[$columnList[$i]])){
         //値がNullのカラム名を取得して配列に入れる
         array_push($nullColumnList, $columnList[$i]);
       }
     }
     //nullカラム個数
     $nullColumnListNum = count($nullColumnList);
   }
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
   if($eventFrag !== 1 && PLACETYPE == 'user' && $GL_LatestMessage['statusId'] == 1 && $groupEventStatus == 1){
     if(!is_null($GL_DbUserData['userId']) && $statusId == 1 && PLACETYPE == 'user' && $whileWeather == false){
       $nickNameFrag = mt_rand(1,5);
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
   } else if($statusId !== 1 && PLACETYPE == 'user'){
       //イベント待ち状態のユーザーに対する処理
       switch($statusId){
         case 3: //Ask Bitgh Date
         list($birthDate, $replyMessage) = validateBirthDate();
         if(!is_null($birthDate)){
           $doSqlFunc->insertPostData(SENDERID,array('birthDate', $birthDate));
         }
         break;
         case 4: //Ask Location
         $cityInfo = $doSqlFunc->getCityInfo(Roman2Kana(TEXT, 'romaji'));
         if(!is_null($cityInfo[0]['name'])){
           $replyMessage = 'そうそう。'.Roman2Kana($cityInfo[0]['name'],'kana').'だったね。';
           $doSqlFunc->insertPostData(SENDERID,array('location', $cityInfo[0]['name']));
         } else {
           $replyMessage = 'どこやねん';
         }
         break;
       }
       $sendContent = [
         "type" => "text",
         "text" => $replyMessage
       ];
       $doSqlFunc->changeEventStatus(SENDERID, 1);
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
 if($nickNameFrag == 1){
   $nickName = createNickName();
   $doFunc->pushMessage(["to" => SENDERID,"messages" => [["type" => "text","text" => 'えっとねー']]]);
   $doFunc->pushMessage(["to" => SENDERID,"messages" => [["type" => "text","text" => $nickName.'！きにいった？']]]);
   $doSqlFunc->insertPostData(SENDERID,array('nickName',$nickName));
 }

 /**
 * #4 送信後処理(DB操作etc)
 */
 //追い出し
 if($GL_TextType == 5){
   $doFunc->getLeave(PLACETYPE, SENDERID);
 }

 //メッセージをDBに格納
 $doSqlFunc->insertMessage(SENDERID, TEXT, $groupEventStatus);

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
  $doSqlFunc = new doSqlFunc();
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
    break;
    case 'birthDate':
    $sendContent = [
      "type" => "text",
      "text" => "ねー君誕生日いつだっけ？8桁で教えてね！"
    ];
    $doSqlFunc->changeEventStatus(SENDERID, 3);
    break;
    case 'location':
    $sendContent = [
      "type" => "text",
      "text" => "ねー君の家どこやったっけ？平仮名でおしえてな"
    ];
    $doSqlFunc->changeEventStatus(SENDERID, 4);
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

function getWeather($date, $inputCityName){
  $doSqlFunc = new doSqlFunc();
  $conf = new config();

  $cityId = $doSqlFunc->getCityInfo(Roman2Kana($inputCityName,'romaji'));

  $getWeatherUrl = 'http://api.openweathermap.org/data/2.5/forecast/city?id='.$cityId[0]['id'].'&APPID='.$conf->weatherApiKey;
  $weather = file_get_contents($getWeatherUrl);
  $weather = json_decode($weather);
  $description = $weather->{'list'}[0]->{'weather'}[0]->{'description'};
  $location = Roman2Kana($weather->{'city'}->{'name'}, 'kana');

  switch(true){
    case preg_match('/light rain/', $description):
    $weatherText = $date.'の'.$location.'の天気は小雨だよ。傘を忘れずにね！';
    break;
    case preg_match('/heavy intensity rain/', $description):
    $weatherText = $date.'の'.$location.'の天気は大雨だよ。気をつけてね！';
    break;
    case preg_match('/very heavy rain/', $description):
    $weatherText = $date.'の'.$location.'の天気は豪雨だよ。いきてかえってね！';
    break;
    case preg_match('/rain/', $description):
    $weatherText = $date.'の'.$location.'の天気は雨だよ。傘を忘れずにね！';
    break;
    case preg_match('/drizzle/', $description):
    $weatherText = $date.'の'.$location.'の天気は霧だよ。運転には注意だね！';
    break;
    case preg_match('/snow/', $description):
    $weatherText = $date.'の'.$location.'の天気は雪だよ。こわいよー！';
    break;
    case preg_match('/clear sky/', $description):
    $weatherText = $date.'の'.$location.'の天気は晴れだよ。よかったね！';
    break;
    case preg_match('/clouds/', $description):
    $weatherText = $date.'の'.$location.'の天気は曇りだよ！';
    break;
    case is_null($description):
    $weatherText = 'そんな田舎しらんわぼけ';
    break;
  }
  return $weatherText;
}

function Roman2Kana($i, $type)
{
    $r =array('vv','kk','gg','ss','zz'
             ,'jj','tt','dd','hh','ff'
             ,'bb','pp','mm','yy','rr','ww'
             ,'cc','xx','ll',"n'",'nn'
             ,'xtsa','xtsi','xtsu','xtse','xtso'
             ,'xtu','ltsu','ltu'
             ,'kya','kyi','kyu','kye','kyo'
             ,'sya','syu','sye','syo'
             ,'sha','shi','shu','she','sho'
             ,'cha','chi','chu','che','cho'
             ,'tsa','tsi','tsu','tse','tso'
             ,'tha','thi','thu','the','tho'
             ,'tya','tyi','tyu','tye','tyo'
             ,'nya','nyu','nyo'
             ,'hya','hyu','hyo'
             ,'mya','myu','myo'
             ,'rya','ryu','ryo'
             ,'wha','whi','whe','who'
             ,'wyi','wye'
             ,'gya','gyu','gyo'
             ,'zya','zyu','zyo'
             ,'bya','byu','byo'
             ,'pya','pyu','pyo'
             ,'dha','dhi','dhu','dhe','dho'
             ,'dya','dyi','dyu','dye','dyo'
             ,'xya','lya','xyu','lyu','xyo','lyo'
             ,'xwa','lwa','xke','lke'
             ,'xa','xi','xu','xe','xo'
             ,'la','li','lu','le','lo'
             ,'ka','ki','ku','ke','ko'
             ,'sa','si','su','se','so'
             ,'ta','ti','tu','te','to'
             ,'na','ni','nu','ne','no'
             ,'ha','hi','fu','he','ho'
             ,'fa','fi','fu','fe','fo'
             ,'ma','mi','mu','me','mo'
             ,'ya','yu','yo','yi','ye'
             ,'ra','ri','ru','re','ro'
             ,'wa','wi','wu','we','wo'
             ,'ga','gi','gu','ge','go'
             ,'za','zi','zu','ze','zo'
             ,'ja','ji','ju','je','jo'
             ,'da','di','du','de','do'
             ,'ba','bi','bu','be','bo'
             ,'va','vi','vu','ve','vo'
             ,'pa','pi','pu','pe','po'
             ,'n','a','i','u','e','o','-');

    $s =array('っv','っk','っg','っs','っz'
             ,'っj','っt','っd','っh','っf'
             ,'っb','っp','っm','っy','っr','っw'
             ,'っc','っx','っl','ん','ん'
             ,'っぁ','っぃ','っ'  ,'っぇ','っぉ'
             ,'っ'  ,'っ'  ,'っ'
             ,'きゃ','きぃ','きゅ','きぇ','きょ'
             ,'しゃ','しゅ','しぇ','しょ'
             ,'しゃ','し'  ,'しゅ','しぇ','しょ'
             ,'ちゃ','ち'  ,'ちゅ','ちぇ','ちょ'
             ,'つぁ','つぃ','つ'  ,'つぇ','つぉ'
             ,'てゃ','てぃ','てゅ','てぇ','てょ'
             ,'ちゃ','ちぃ','ちゅ','ちぇ','ちょ'
             ,'にゃ','にゅ','にょ'
             ,'ひゃ','ひゅ','ひょ'
             ,'みゃ','みゅ','みょ'
             ,'りゃ','りゅ','りょ'
             ,'うぁ','うぃ','うぇ','うぉ'
             ,'ゐ'  ,'ゑ'
             ,'ぎゃ','ぎゅ','ぎょ'
             ,'じゃ','じゅ','じょ'
             ,'びゃ','びゅ','びょ'
             ,'ぴゃ','ぴゅ','ぴょ'
             ,'でゃ','でぃ','でゅ','でぇ','でょ'
             ,'ぢゃ','ぢぃ','ぢゅ','ぢぇ','ぢょ'
             ,'ゃ','ゃ','ゅ','ゅ','ょ','ょ'
             ,'ゎ','ゎ','ヶ','ヶ'
             ,'ぁ','ぃ','ぅ','ぇ','ぉ'
             ,'ぁ','ぃ','ぅ','ぇ','ぉ'
             ,'か','き','く','け','こ'
             ,'さ','し','す','せ','そ'
             ,'た','ち','つ','て','と'
             ,'な','に','ぬ','ね','の'
             ,'は','ひ','ふ','へ','ほ'
             ,'ふぁ','ふぃ','ふ','ふぇ','ふぉ'
             ,'ま','み','む','め','も'
             ,'や','ゆ','よ','ゐ','ゑ'
             ,'ら','り','る','れ','ろ'
             ,'わ','うぃ','う','うぇ','を'
             ,'が','ぎ','ぐ','げ','ご'
             ,'ざ','じ','ず','ぜ','ぞ'
             ,'じゃ','じ','じゅ','じぇ','じょ'
             ,'だ','ぢ','づ','で','ど'
             ,'ば','び','ぶ','べ','ぼ'
             ,'ば','び','ぶ','べ','ぼ'
             ,'ぱ','ぴ','ぷ','ぺ','ぽ'
             ,'ん','あ','い','う','え','お','ー');

    if($type == 'kana'){
      return str_ireplace($r,$s,$i);
    } else {
      return str_ireplace($s,$r,$i);
    }
}

?>
