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

$count = 100;
if ($_GET['c']) {
    $count = (int) $_GET['c'];
}

$debug = false;
if ($_GET['debug']) {
    $debug=true;
}

$force = false;
if ($_GET['force']) {
    $force=true;
}

if (!class_exists('MoviesLinks')) {
    require_once( MOVIES_LINKS_PLUGIN_DIR . 'db/MoviesAbstractFunctions.php' );
    require_once( MOVIES_LINKS_PLUGIN_DIR . 'db/MoviesAbstractDBAn.php' );
    require_once( MOVIES_LINKS_PLUGIN_DIR . 'db/MoviesAbstractDB.php' );
    require_once( MOVIES_LINKS_PLUGIN_DIR . 'MoviesLinks.php' );
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

$cron_name = 'reviews';
if ($ml->cron_already_run($cron_name, 10, $debug)) {
    exit();
}
$ml->register_cron($cron_name);

$campaign = new stdClass();
$campaign->title = 'reviews_cron';

$fs = $ml->get_campaing_mlr($campaign);

$fs->reviews_cron_meta($count, $force, $debug);

$ml->unregister_cron($cron_name);