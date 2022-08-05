<?php

/*
 * 1. Get actors data by movie id
 */

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

$mid = 21055;
if ($_GET['m']) {
    $mid = (int) $_GET['m'];
}

$debug = false;
if ($_GET['debug']) {
    $debug = true;
}

$cf = new CriticFront();
$count=10;
$results = $cf->search_last_critics($mid, $count);


print '<pre>';
print_r($results);
print '</pre>';
