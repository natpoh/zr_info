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

$movies = array(21055, 14899, 10579);
if ($_GET['m']) {
    $mid = (int) $_GET['m'];
    $movies = array($mid);
}

$debug = false;
if ($_GET['debug']) {
    $debug = true;
}

$cm = new CriticMatic();
$af = $cm->get_af();

// User data
$verdict_mode = 'w';
$mode_key = 9;
$priority_string = '';

$showcast = array(1, 2);
/*  $showcast:
  1 = 'Stars'
  2 = 'Supporting'
  3 = 'Other'
  4 = 'Production'
 */

// Custom priority
$priority = '';

$ver_weight = false;
if ($verdict_mode == 'w') {
    // Weights logic
    $ver_weight = true;
    $weights_arr = $af->get_filter_mode($mode_key);
    if ($weights_arr['custom']) {
        $priority = $weights_arr['priority'];
    }
} else {
    // Priority logic
    $priority_arr = $af->get_filter_priority($priority_string);
    if ($priority_arr['custom']) {
        $priority = $priority_arr['priority'];
    }
}

$race_data = $af->get_movies_race_data($movies, $showcast, $ver_weight, $priority, $debug);
print '<pre>';
print_r($race_data);
print '</pre>';
