<?php
ini_set("auto_detect_line_endings", true);
error_reporting('E_ERROR');
set_time_limit(0);
ini_set('display_errors','On');

if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');

//DB config
!defined('DB_HOST_AN') ? include ABSPATH . 'analysis/db_config.php' : '';
//Abstract DB
!class_exists('Pdoa') ? include ABSPATH . "analysis/include/Pdoa.php" : '';
//Curl
!class_exists('GETCURL') ? include ABSPATH . "analysis/include/get_curl.php" : '';



///echo  $_SERVER['DOCUMENT_ROOT'];
for ($time = 1950; $time <= date('Y',time()); $time += 1) {

    $inflation=1;

    $url='https://data.bls.gov/cgi-bin/cpicalc.pl?cost1=1.00&year1='.$time.'01&year2='.date('Y',time()).'01';


   $content = GETCURL::getCurlCookie($url);


    $content =substr($content,strpos($content,'span id="answer"'),40);

    if (preg_match('#\>\$([\d\.]+)\<#',$content,$mach))
    {
        $inflation =$mach[1];


        $sql = "select id from `data_inflation` where `Year` = ".$time;
        $result =Pdo_an::db_fetch_row($sql);
        if ($result->id)
        {

            $sql = "UPDATE `data_inflation` set `value` = ? where  id = ? ";
            $result2 =Pdo_an::db_results_array($sql,array($inflation,$result->id));
            echo 'updated '.$time.' - '.$inflation.'<br>';
        }
        else
        {
            $sql="INSERT INTO `data_inflation` (`id`, `Year`, `value`) VALUES (NULL, '".$time."', ?);";
            Pdo_an::db_results_array($sql,array($inflation));
            echo 'add '.$time.' - '.$inflation.'<br>';
        }

    }

}