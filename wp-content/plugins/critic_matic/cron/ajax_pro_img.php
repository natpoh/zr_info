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


$cm = new CriticMatic();
$cav = $cm->get_cav();
print $cav->ajax_pro_img();