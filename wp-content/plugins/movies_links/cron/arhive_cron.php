<?php
/*
 * /wp-content/plugins/movies_links/cron/arhive_cron.php?p=8ggD_23_2D0DSF-F&t=2&url=1962323&debug=1
 */
require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-config.php');
nocache_headers();

if (!function_exists('movies_links_init')) {
    return;
}

$p = '8ggD_23_2D0DSF-F';

if ($_GET['p'] != $p) {
    return;
}

// Arhive cron type
$cron_type = 1;
if ($_GET['t']) {
    $cron_type = (int) $_GET['t'];
}

$debug = false;
if ($_GET['debug']) {
    $debug = true;
}

$force = false;
if ($_GET['force']) {
    $force = true;
}

$custom_url_id= $_GET['url'] ? (int) $_GET['url'] : 0;

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

/*
 * $cron_type
  1 => 'arhive',
  2 => 'parsing',
  3 => 'links',
  4 => 'cron_urls',
  5 => 'gen_urls',
  6 => 'find_expired',
 */
$cron_name = 'arhive_cron_'.$cron_type;
if ($ml->cron_already_run($cron_name, 10, $debug)&&!$force) {
    exit();
}

$ml->register_cron($cron_name);

$mpc = new MoviesParserCron($ml);
$mpc->run_cron($cron_type, $debug, $force, $custom_url_id);

$ml->unregister_cron($cron_name);
