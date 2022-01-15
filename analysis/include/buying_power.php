<?php
ini_set("auto_detect_line_endings", true);
error_reporting('E_ERROR');
set_time_limit(0);
ini_set('display_errors', 'On');


include '../db_config.php';
global $pdo;
pdoconnect_db();

if (!function_exists('getCurlCookie')) {
    function getCurlCookie($url, $b = '', $arrayhead = '')
    {
        $cookie_path = ABSPATH . 'cookies/cookies.txt';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);

        $proxy = '127.0.0.1:8118';


        // curl_setopt($ch, CURLOPT_PROXY, $proxy);

        if ($arrayhead) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $arrayhead);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);


        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookiePath);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookiePath);

        if (strstr($url, 'https')) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        }

        if ($b) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $b);

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
function prepare_number($data)
{
    $data = str_replace('$','',$data) ;
    $data=str_replace(',','',$data) ;
    $data  =trim($data);


    if (strstr($data,'million'))
    {
        $data =  str_replace('million','',$data) ;
        $data  =trim($data);
        $data = $data*1000000;
    }
    if (strstr($data,'billion'))
    {
        $data =  str_replace('billion','',$data) ;
        $data  =trim($data);
        $data = $data*1000000000;
    }
    if (strstr($data,'trillion'))
    {
        $data =  str_replace('trillion','',$data) ;
        $data  =trim($data);
        $data = $data*1000000000000;
    }



    return $data;
}


function add_to_db_buying_power($url,$type=1)
{
    global $pdo;
    $result = getCurlCookie($url);

    $pos = '<table class';

    if (strpos($result, $pos)) {
        $result = substr($result, strpos($result, $pos));
    }
    if (strpos($result, '</table>')) {
        $result = substr($result, 0, strpos($result, '</table>'));
    }

    $array_total = explode('</tr>',$result);

    $regv='#<tr[^\/]+\/[^\/]+\/[^\<]+\<i class\=\"flag flag-([a-z]+)[^\<]+\<[^\<]+\<[^\>]+\>([^\<]+)\<[^\<]+\<[^\<]+\<[^\<]+\<[^\>]+\>([^\<]+)\<[^\>]+\>[^\>]+\>([^\<]+)#';

    foreach ($array_total as $val)
    {
        if (preg_match($regv,$val,$mach))
        {
            $array_country[$mach[1]] = array($mach[2],prepare_number($mach[3]),$mach[4]);

        }


    }

  //  var_dump($array_country);

    foreach ( $array_country as $country_code =>$array)
    {
        $country_code =strtoupper($country_code);

        $sql = "SELECT *  FROM `data_population_country`  WHERE `data_population_country`.`cca2` = '" . $country_code . "'";
        $q = $pdo->prepare($sql);
        $q->execute();
        $q->setFetchMode(PDO::FETCH_ASSOC);
        $r = $q->fetch();

        if ($r['id'] > 0) {

            if ($array[0] != $r['country_name']) {
               /// echo $country_code . ' ' . $array[0] . ' replace ' . $r['country_name'] . ' <br>';
            }

            $array[0]=$r['country_name'];



            $sql = "SELECT *  FROM `data_buying_power`  WHERE `data_buying_power`.`cca2` = '" . $country_code . "'";
            $q = $pdo->prepare($sql);
            $q->execute();
            $q->setFetchMode(PDO::FETCH_ASSOC);
            $r = $q->fetch();


            if ($type == 1) {

                if ($r['id'] > 0) {
                    $sql = "UPDATE `data_buying_power` SET `name` = ?, `per_capita` = ? ,  `date` = ?   WHERE `data_buying_power`.`cca2` = '" . $country_code . "'";
                    $q = $pdo->prepare($sql);
                    $q->execute($array);
                } else {
                    $sql = "INSERT INTO `data_buying_power` VALUES (NULL,?,'" . $country_code . "', ?,'', ? )";
                    $q = $pdo->prepare($sql);
                    $q->execute($array);
                }

            } else if ($type == 2) {

                if ($r['id'] > 0) {
                    $sql = "UPDATE `data_buying_power` SET `total` = ?  WHERE `data_buying_power`.`cca2` = '" . $country_code . "'";
                    $q = $pdo->prepare($sql);
                    $q->execute(array($array[1]));
                }
            }

        }
    }

}



if ($_GET['add'] == 1) {
    global $pdo;
    $url = 'https://www.nationmaster.com/country-info/stats/Economy/GDP/Purchasing-power-parity-per-capita';
    add_to_db_buying_power($url,1);

    $url = 'https://www.nationmaster.com/country-info/stats/Economy/GDP/Purchasing-power-parity';
    add_to_db_buying_power($url,2);
}