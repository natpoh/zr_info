<?php

error_reporting('E_ALL');
ini_set('display_errors', 'On');
if (!defined('ABSPATH')){
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');
}

//DB config
!defined('DB_HOST_AN') ? include ABSPATH . 'analysis/db_config.php' : '';
//Abstract DB
!class_exists('Pdoa') ? include ABSPATH . "analysis/include/Pdoa.php" : '';

if (isset($_GET['id'])) {
    $movie_id = (int) $_GET['id'];


    $sql = "SELECT * FROM `data_movie_imdb` where `id` ='" . $movie_id . "' limit 1 ";
    $r = Pdo_an::db_fetch_row($sql);

    $movie_title = $r->title;


    $link = 'https://archive.4plebs.org/_/search/boards/pol.tv/text/%22'.$movie_title.'%22/';

    $array = array('4chanlink'=>$link);
    echo json_encode($array);
}