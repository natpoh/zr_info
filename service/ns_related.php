<?php

header('Access-Control-Allow-Origin:*');

if (strstr($_SERVER['DOCUMENT_ROOT'], 'service')) {
    $root = str_replace('/service', '', $_SERVER['DOCUMENT_ROOT']);
} else {
    $root = $_SERVER['DOCUMENT_ROOT'];
}

if (!defined('ABSPATH'))
    define('ABSPATH', $root . '/');


// DB config
!defined('DB_HOST_AN') ? include ABSPATH . 'analysis/db_config.php' : '';
// Abstract DB
!class_exists('Pdoa') ? include ABSPATH . "analysis/include/Pdoa.php" : '';


// Critic matic
if (!defined('CRITIC_MATIC_PLUGIN_DIR')) {
    define('CRITIC_MATIC_PLUGIN_DIR', ABSPATH . 'wp-content/plugins/critic_matic/');
    require_once(CRITIC_MATIC_PLUGIN_DIR . 'critic_matic_ajax_inc.php');
}

global $cfront;
$cfront = new CriticFront();

$post_id = (int) $_GET['pid'];


$debug = (int) $_GET['debug'];

if ($post_id) {
    $cfront->related_newsfilter_movies($post_id, $debug);
}