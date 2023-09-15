<?php
/*
 * /wp-content/plugins/critic_matic/cron/movie_simpson_cron.php?p=8ggD_23sdf_DSF&debug=1
 */

if (!defined('ABSPATH')) {
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');
}

if (!defined('CRITIC_MATIC_PLUGIN_DIR')) {
    define('CRITIC_MATIC_PLUGIN_DIR', ABSPATH . 'wp-content/plugins/critic_matic/');
    require_once( CRITIC_MATIC_PLUGIN_DIR . 'critic_matic_ajax_inc.php' );
}

if (!class_exists('CriticMatic')) {
    return;
}

$p = '8ggD_23sdf_DSF';

if ($_GET['p'] != $p) {
    return;
}

$debug = false;
if ($_GET['debug']) {
    $debug = true;
}

$force = false;
if ($_GET['force']) {
    $force = true;
}

$count = 100;
if ($_GET['c']) {
    $count = (int) $_GET['c'];
}


// Check server load
!class_exists('CPULOAD') ? include ABSPATH . "service/cpu_load.php" : '';
$load = CPULOAD::check_load();
if ($load['loaded']) {
    if ($debug) {
        p_r($load);
    }
    exit();
}

$cm = new CriticMatic();
$cron_name = 'movie_simpson_cron';
if ($cm->cron_already_run($cron_name, 10, $debug, $force)) {
    exit();
}

$cm->register_cron($cron_name);


$ms = $cm->get_ms();
$ms->run_cron($count, $debug, $force);


$cm->unregister_cron($cron_name);

// Hook example

//$cm = new CriticMatic();
//$ms = $cm->get_ms();
//$ms->hook_update_movie($mid=10637, $debug=true);