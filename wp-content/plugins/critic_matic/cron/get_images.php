<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");



if (!defined('ABSPATH')) {
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');
}

if (!defined('CRITIC_MATIC_PLUGIN_DIR')) {
    define('CRITIC_MATIC_PLUGIN_DIR', ABSPATH . 'wp-content/plugins/critic_matic/');
    require_once( CRITIC_MATIC_PLUGIN_DIR . 'critic_matic_ajax_inc.php' );
}

$p = 'D_23_2D0FS0-vbb';

if ($_GET['p'] != $p) {
    return;
}


$debug = false;
if ($_GET['debug']) {
    $debug = true;
}

$mid = isset($_GET['mid'])? (int)$_GET['mid']:0;

$cm = new CriticMatic();
$si = $cm->get_si();


$results = $si->get_images($mid, $debug);


print json_encode($results);