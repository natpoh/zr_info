<?php

error_reporting('E_ERROR');
set_time_limit(0);

if (!function_exists('getCurlCookieface')) {
    function getCurlCookieface($url, $b = '', $arrayhead = '',$proxy ='')
    {
        $cookie_path = ABSPATH . 'cookies/cookies.txt';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);

        $proxy = '127.0.0.1:8118';
        if ($proxy)
        {
            curl_setopt($ch, CURLOPT_PROXY, $proxy);
        }


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


////echo $base64; // Выведет base64-код изображения

function get_actor_race($base64)
{
    $url = 'https://www.betaface.com/demo.html';

    $result = getCurlCookieface($url,'','',1);

///var_dump($result);

    $regv = "#'api_key': '([^\,]+),#";

    if (preg_match($regv, $result, $mach)) {
        ///  var_dump($mach[1]);
        $key = substr($mach[1], 0, strlen($mach[1]) - 1);
        ///   echo 'key = ' . $key;
    }


    $arrayhead = array(

        'Host: www.betafaceapi.com',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:82.0) Gecko/20100101 Firefox/82.0',
        'Accept: application/json, text/javascript; q=0.01',
        'Accept-Language: en',
        'Accept-Encoding: gzip, deflate, br',
        'Content-Type: application/json',
        'Origin: https://www.betaface.com',
        'Connection: keep-alive',
        'Referer: https://www.betaface.com/demo.html',

    );


    $pos_data = array(
        'api_key' => $key,
        'detection_flags' => "cropface,recognition,classifiers",
        'file_base64' => $base64,
        'original_filename'=> time().'.jpg');

    $pos_data = json_encode($pos_data);

    $url = "https://www.betafaceapi.com/api/v2/media";
    $result = getCurlCookieface($url, $pos_data, $arrayhead,1);

    if ($result) {

        $arraay = json_decode($result);
    }

    $race = $arraay->media->faces[0]->tags[31]->value;
    $percent = $arraay->media->faces[0]->tags[31]->confidence;

    if ($race && $percent)
    {
        return array($race, $percent);
    }
    else{
      echo 'error parse<br>';
      echo $result;
    }



}

global $pdo;

if (!$pdo) {
    include '../db_config.php';
    pdoconnect_db();
}
if (!function_exists('create_image_64')) {
    function create_image_64($imgid)
    {
        $number = str_pad($imgid, 7, '0', STR_PAD_LEFT);
        $imgsource = $_SERVER['DOCUMENT_ROOT'] . '/analysis/img_final/' . $number . '.jpg';

        if (!file_exists($imgsource)) {
            echo ' file no exists, try get file '.PHP_EOL;

            $final_value = sprintf('%07d', $number);

            $url = 'https://www.imdb.com/name/nm'.$final_value.'/bio/';
            echo $url.PHP_EOL;

            $result=   getCurlCookieface($url);
            $array_result  = get_imdb_actor_parse_inner($result);
            $image=$array_result['image'];

            if (function_exists('check_image_on_server'))
            {
                echo 'add images'.PHP_EOL;


              if (!check_image_on_server(intval($number),$image))
              {

                  global $pdo;
                  $sql = "UPDATE `data_actors_imdb` SET `image` = 'N' WHERE `data_actors_imdb`.`id` = {intval($number)}";
                  $q = $pdo->prepare($sql);
                  $q->execute();

              }

            }



        }

        if (file_exists($imgsource)) {
            echo 'try get from ' . $imgsource;
            $data = file_get_contents($imgsource);
            $base64 = base64_encode($data);
        }
        return $base64;
    }
}
function set_option($id, $option)
{
    global $pdo;

    $sql = "DELETE FROM `options` WHERE `options`.`id` = " . $id;
    $q = $pdo->prepare($sql);
    $q->execute();

    $sql = "INSERT INTO `options`  VALUES ('" . $id . "',?)";
    $q = $pdo->prepare($sql);
    $q->execute(array($option));
//print_r($q->errorInfo());

}
function add_toracebd($actor_id, $array_race)
{
    global $pdo;
    $sql = "INSERT INTO `data_actors_face`  VALUES(NULL, '" . $actor_id . "', '" . $array_race[0] . "', '" . $array_race[1] . "')";
    $q = $pdo->prepare($sql);
    $q->execute();
}
function checkadd($actor_id)
{
    global $pdo;

    $sql = " SELECT * FROM `data_actors_face` where actor_id= ".$actor_id;
    $q = $pdo->prepare($sql);
    $q->execute();
    $r = $q->fetch();

    return $r['actor_id'];
}


if (isset($_GET['bettaface'])) {

    global $pdo;

///////check and create cache
    $options = array();
    $sql = "SELECT * FROM `options` ";

    $q = $pdo->prepare($sql);
    $q->execute();
    while ($r = $q->fetch()) {
        //  var_dump($r);
        $options[$r['id']] = $r['val'];
    }
    $last_id = 0;
    if ($options[5]) {
        $last_id = $options[5];
    }


    $sql = "SELECT data_actors_race.actor_id  FROM `data_actors_race` LEFT JOIN data_actors_face ON data_actors_race.actor_id=data_actors_face.actor_id
        WHERE data_actors_face.id IS NULL   and  data_actors_race.novo_imgAvail = 'Y' 
        and data_actors_race.actor_id > " . $last_id . "  order by actor_id limit 100 ";
    $q = $pdo->prepare($sql);
    $q->execute();
    $q->setFetchMode(PDO::FETCH_ASSOC);
    while ($r = $q->fetch()) {

        $actor_id = $r['actor_id'];
        if (!checkadd($actor_id)) {

            echo 'get data from ' . $actor_id . '<br>';
            $img_64 = create_image_64($actor_id);
            if ($img_64) {
                sleep(1);
                $array_race = get_actor_race($img_64);

                /// var_dump($array_race);

                //////update bd
                if ($array_race[0]) {
                    add_toracebd($actor_id, $array_race);

                    echo 'add<br>';

                    set_option(5, $actor_id);

                } else {
                    echo 'error get ethnic data <br>';
                }

            } else echo 'no img64<br>';
        } else echo 'actor alredy addeded<br>';

    }

}

