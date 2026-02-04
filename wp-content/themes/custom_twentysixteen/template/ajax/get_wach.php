<?php
error_reporting(E_ERROR);
if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');

//Curl
!class_exists('GETCURL') ? include ABSPATH . "analysis/include/get_curl.php" : '';
//DB config
!defined('DB_HOST_AN') ? include ABSPATH . 'analysis/db_config.php' : '';
//Abstract DB
!class_exists('JustWatch') ? include ABSPATH . "analysis/include/justwatch.php" : '';


global $debug;
//$debug=1;
$movie_id = $_POST['id'];

$result = JustWatch::get_just_wach($movie_id);

if ($result)
{
    $result = json_encode($result);
}
$result =JustWatch::piracy_links($result);
echo $result;
return;



