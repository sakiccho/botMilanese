<?php
//==========================
// DB Info
//==========================
$db_hostname = 'mysql111.phy.lolipop.lan'; // 	mysql1.php.xdomain.ne.jp
$db_database = 'LAA0736454-data';
$db_username = 'LAA0736454';
$db_password = 'Mtj2rZe682Vx';

//$db_hostname = 'localhost'; // 	mysql1.php.xdomain.ne.jp
//$db_database = 'lolipop';
//$db_username = 'root';
//$db_password = 'root';

$db_server = mysql_connect($db_hostname, $db_username, $db_password);
if (!$db_server) {
    die('connection failed'.mysql_error());
}
mysql_select_db($db_database);
mysql_set_charset('utf8');

//==========================
// Bot settings
//==========================
$accessToken = 'ZLXujrYwwY1leuDg5zms46WQCf+D9geBmRPT41N/HORWxcRZuQnCWT5gE1gaSOCvSPEC3MtGh2SdPIQowdN6+rkYHRorOfkXHCZOKm834vNIxrsIBJ4fTEWPNQMC8k/ufuxXJ4QZORbm123yDlGHewdB04t89/1O/w1cDnyilFU=';
