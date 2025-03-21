<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");

if (!defined('ABSPATH')) {
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');
}

if (!defined('CRITIC_MATIC_PLUGIN_DIR')) {
    define('CRITIC_MATIC_PLUGIN_DIR', ABSPATH . 'wp-content/plugins/critic_matic/');
    require_once( CRITIC_MATIC_PLUGIN_DIR . 'critic_matic_ajax_inc.php' );
}


$ids = $_GET['ids'];
$type = $_GET['type'];

$cfront = new CriticFront();

if ($type == 'review_ratings') {
    print json_encode($cfront->ajax_review_ratings($ids, (int) $_GET['ftype']));
} else if ($type == 'movie_ratings') {   
    $cfront->ajax_load_movie_rating($ids);
}  