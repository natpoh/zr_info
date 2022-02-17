<?php
set_time_limit(0);
ini_set('display_errors', 'On');
error_reporting(E_ERROR);
if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');

//DB config
!defined('DB_HOST_AN') ? include ABSPATH . 'analysis/db_config.php' : '';
//Abstract DB
!class_exists('Pdoa') ? include ABSPATH . "analysis/include/Pdoa.php" : '';
//Curl
!class_exists('GETCURL') ? include ABSPATH . "analysis/include/get_curl.php" : '';



require_once('import_db.php');
// Database

///get commit
//////analysis/export/import.php?key=1R3W5T8s13t21a34f&action=last_commit&count=10
//////analysis/export/import.php?key=1R3W5T8s13t21a34f&action=get_commit&uid=t_add_movies1643729961,t_actor_meta1643729962



if (isset($_REQUEST['key'])) {



    $key = '1R3W5T8s13t21a34f';
    if ($_REQUEST['key']!=$key)
    {
        echo 'false key';
        return;
    }

    $data = $_REQUEST;

    $import = new Import;
    $result = $import->prepare_data($data);

    $result_json =  json_encode($result);
    if (!$result_json) {

        echo json_last_error_msg();
        var_dump($result);

        return;
    }
    header('Content-Type: application/json');
    echo $result_json;

    return;


}


