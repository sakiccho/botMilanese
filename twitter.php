<?php
/**
 * [API]Twitter APIコントローラークラス
 *
 * TwitterAPIのトークン取得、ツイート取得などの実行関数をまとめたコントローラークラス
 *
 * @access public
 * @author sakiccho <sakiccho@gmail.com>
 * @copyright  sakiccho Corporation All Rights Reserved
 * @category Message
 * @package Controller
 */
  namespace BotMilanese\Controller\Twitter;
  require_once('./settings/config.php');
  use BotMilanese\Setting\config;

class twitter extends config{

  function getToken(){
  	// クレデンシャルを作成
  	$credential = base64_encode( $this->api_key . ':' . $this->api_secret ) ;

  	// リクエストURL
  	$request_url = 'https://api.twitter.com/oauth2/token' ;

  	// リクエスト用のコンテキストを作成する
  	$context = array(
  		'http' => array(
  			'method' => 'POST' , // リクエストメソッド
  			'header' => array(			  // ヘッダー
  				'Authorization: Basic ' . $credential ,
  				'Content-Type: application/x-www-form-urlencoded;charset=UTF-8' ,
  			) ,
  			'content' => http_build_query(	// ボディ
  				array(
  					'grant_type' => 'client_credentials' ,
  				)
  			) ,
  		) ,
  	);

  	// cURLを使ってリクエスト
  	$curl = curl_init() ;
  	curl_setopt( $curl , CURLOPT_URL , $request_url ) ;
  	curl_setopt( $curl , CURLOPT_HEADER, 1 ) ;
  	curl_setopt( $curl , CURLOPT_CUSTOMREQUEST , $context['http']['method'] ) ;			// メソッド
  	curl_setopt( $curl , CURLOPT_SSL_VERIFYPEER , false ) ;								// 証明書の検証を行わない
  	curl_setopt( $curl , CURLOPT_RETURNTRANSFER , true ) ;								// curl_execの結果を文字列で返す
  	curl_setopt( $curl , CURLOPT_HTTPHEADER , $context['http']['header'] ) ;			// ヘッダー
  	curl_setopt( $curl , CURLOPT_POSTFIELDS , $context['http']['content'] ) ;			// リクエストボディ
  	curl_setopt( $curl , CURLOPT_TIMEOUT , 5 ) ;										// タイムアウトの秒数
  	$res1 = curl_exec( $curl ) ;
  	$res2 = curl_getinfo( $curl ) ;
  	curl_close( $curl ) ;

  	// 取得したデータ
  	$json = substr( $res1, $res2['header_size'] ) ;				// 取得したデータ(JSONなど)
  	$header = substr( $res1, 0, $res2['header_size'] ) ;		// レスポンスヘッダー (検証に利用したい場合にどうぞ)

  	// [cURL]ではなく、[file_get_contents()]を使うには下記の通りです…
  	// $response = @file_get_contents( $request_url , false , stream_context_create( $context ) ) ;

  	// JSONをオブジェクトに変換
  	$obj = json_decode( $json ) ;

  	//$this->getLog($obj->access_token);
  }
/**************************************************

	GETメソッドのリクエスト [ベアラートークン]

	* URLとオプションを変えて色々と試してみて下さい

**************************************************/
  function getUserTweet($screenName,$count){
  	// 設定
  	$request_url = 'https://api.twitter.com/1.1/statuses/user_timeline.json' ;		// エンドポイント

  	// パラメータ
  	$params = array(
  		'screen_name' => '@' . $screenName ,
  		'count' => $count ,
  	) ;

  	// パラメータがある場合
  	if( $params )
  	{
  		$request_url .= '?' . http_build_query( $params ) ;
  	}

  	// リクエスト用のコンテキスト
  	$context = array(
  		'http' => array(
  			'method' => 'GET' , // リクエストメソッド
  			'header' => array(			  // ヘッダー
  				'Authorization: Bearer ' . $this->bearer_token ,
  			) ,
  		) ,
  	) ;

  	// cURLを使ってリクエスト
  	$curl = curl_init() ;
  	curl_setopt( $curl , CURLOPT_URL , $request_url ) ;
  	curl_setopt( $curl , CURLOPT_HEADER, 1 ) ;
  	curl_setopt( $curl , CURLOPT_CUSTOMREQUEST , $context['http']['method'] ) ;			// メソッド
  	curl_setopt( $curl , CURLOPT_SSL_VERIFYPEER , false ) ;								// 証明書の検証を行わない
  	curl_setopt( $curl , CURLOPT_RETURNTRANSFER , true ) ;								// curl_execの結果を文字列で返す
  	curl_setopt( $curl , CURLOPT_HTTPHEADER , $context['http']['header'] ) ;			// ヘッダー
  	curl_setopt( $curl , CURLOPT_TIMEOUT , 5 ) ;										// タイムアウトの秒数
  	$res1 = curl_exec( $curl ) ;
  	$res2 = curl_getinfo( $curl ) ;
  	curl_close( $curl ) ;

  	// 取得したデータ
  	$json = substr( $res1, $res2['header_size'] ) ;				// 取得したデータ(JSONなど)
  	$header = substr( $res1, 0, $res2['header_size'] ) ;		// レスポンスヘッダー (検証に利用したい場合にどうぞ)

  	// JSONをオブジェクトに変換
  	$obj = json_decode( $json );
    if(!array_key_exists('error',$obj)){
      $lastTweetArray = array(
          'name' => $obj[0]->{'user'}->{'name'},
          'tweet' => $obj[0]->{'text'}
        );
    } else {
      $lastTweetArray = array(
          'name' => 'false',
          'tweet' => 'こいつ鍵垢だからツイート見れなかったよ！'
        );
    }

    return $lastTweetArray;
  }
}
?>
