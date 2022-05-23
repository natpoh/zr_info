<?php

if ($_GET['test_google']) {

    if (!defined('ABSPATH'))
        define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');

    if (!defined('CRITIC_MATIC_PLUGIN_DIR')) {
        define('CRITIC_MATIC_PLUGIN_DIR', ABSPATH . 'wp-content/plugins/critic_matic/');
        require_once( CRITIC_MATIC_PLUGIN_DIR . 'critic_matic_ajax_inc.php' );
    }
    $cm = new CriticMatic();

//One time transit data
    require_once( CRITIC_MATIC_PLUGIN_DIR . 'CriticTranscriptions.php' );
    $ct = new CriticTranscriptions($cm);
    $ct->test_google();

    exit;
}

require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-config.php');
set_time_limit(0);
/*
 * Transit critcs wp_posts to critic_matic posts
 */
$debug = false;
if ($_GET['debug']) {
    $debug = true;
    error_reporting('E_ALL');
    ini_set('display_errors', 'On');

    define('WP_DEBUG', true);
    define('WP_DEBUG_LOG', true);
    define('WP_DEBUG_DISPLAY', false);
}

if (!class_exists('CriticMatic')) {
    return;
}

$p = 'D_23_2D0FS0-vbb';

if ($_GET['p'] != $p) {
    return;
}

$count = 10;
if ($_GET['c']) {
    $count = (int) $_GET['c'];
}

$force = false;
if ($_GET['force']) {
    $force = true;
}

$cm = new CriticMatic();

//One time transit data
require_once( CRITIC_MATIC_PLUGIN_DIR . 'CriticTranscriptions.php' );
$ct = new CriticTranscriptions($cm);

if ($_GET['install']) {
    $ct->install();
    exit();
}

//$ct->transit_youtube($count, $debug, $force);
//$ct->transit_bitchute($count, $debug, $force);
//$ct->transit_therightstuff($count, $debug, $force);

