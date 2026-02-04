<?php
ini_set("auto_detect_line_endings", true);
error_reporting('E_ERROR');
set_time_limit(0);
ini_set('display_errors','On');


if (!function_exists('getCurlCookie')) {
    function getCurlCookie($url = '',$b=1)
    {
        $cookie_path = ABSPATH . 'wp-content/uploads/cookies.txt';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);


        if (!$b) {
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_NOBODY, true);
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);

        if ($cookiePath) {
            curl_setopt($ch, CURLOPT_COOKIEJAR, $cookiePath);
            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookiePath);
        }

        if (strstr($url, 'https')) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        }

        if ($b) {
            //     curl_setopt($ch, CURLOPT_POSTFIELDS, $b);

        }
        curl_setopt($ch, CURLOPT_ENCODING, 'deflate');
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2272.118 Safari/537.36');

        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }
}

include '../db_config.php';
global $pdo;
pdoconnect_db();


$sql="TRUNCATE TABLE data_inflation";
$q = $pdo->prepare($sql);
$q->execute();


///echo  $_SERVER['DOCUMENT_ROOT'];
for ($time = 1950; $time <= date('Y',time()); $time += 1) {

    $inflation=1;

    $url='https://data.bls.gov/cgi-bin/cpicalc.pl?cost1=1.00&year1='.$time.'01&year2='.date('Y',time()).'01';


   $content = getCurlCookie($url);


    $content =substr($content,strpos($content,'span id="answer"'),40);

    if (preg_match('#\>\$([\d\.]+)\<#',$content,$mach))
    {
        $inflation =$mach[1];

        echo $time.' - '.$inflation.'<br>';

        $sql="INSERT INTO `data_inflation` (`id`, `Year`, `value`) VALUES (NULL, '".$time."', ?);";
        $q = $pdo->prepare($sql);
        $q->execute(array($inflation));
    }

}