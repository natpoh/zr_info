<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");

if (isset($_POST['nowpapi'])) {    
    // No wp api
    if (!defined('ABSPATH')) {
        define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');
    }

    if (!defined('CRITIC_MATIC_PLUGIN_DIR')) {
        define('CRITIC_MATIC_PLUGIN_DIR', ABSPATH . 'wp-content/plugins/critic_matic/');
        require_once( CRITIC_MATIC_PLUGIN_DIR . 'critic_matic_ajax_inc.php' );
    }
} else {
    // Wp api
    require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-config.php');
}

$cm = new CriticMatic();

if (!class_exists('CriticAudience')) {
    require_once( CRITIC_MATIC_PLUGIN_DIR . 'CriticAudience.php' );
}
$ca = new CriticAudience($cm);

$action = $_POST['action'];

if ($action == 'spam_rev') {
    $ca->ajax_spam_rev($_POST);
}