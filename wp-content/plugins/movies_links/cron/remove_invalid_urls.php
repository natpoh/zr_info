<?php

require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-config.php');
nocache_headers();

if (!function_exists('movies_links_init')) {
    return;
}

$p = '8ggD_23_2D0DSF-F';

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

$count = isset($_GET['c']) ? $_GET['c'] : 10;

if (!class_exists('MoviesLinks')) {

    require_once( MOVIES_LINKS_PLUGIN_DIR . 'db/MoviesAbstractFunctions.php' );
    require_once( MOVIES_LINKS_PLUGIN_DIR . 'db/MoviesAbstractDB.php' );

    require_once( MOVIES_LINKS_PLUGIN_DIR . 'MoviesLinks.php' );
    require_once( MOVIES_LINKS_PLUGIN_DIR . 'MoviesParserCron.php' );
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

$ml = new MoviesLinks();


$cron_name = 'remove_invalid_urls';
if ($ml->cron_already_run($cron_name, 10, $debug) && !$force) {
    exit();
}

$ml->register_cron($cron_name);

$mpc = new MoviesParserCron($ml);
$mpc->remove_invalid_urls($count, $debug, $force);

$ml->unregister_cron($cron_name);
