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

$cid = $_GET['cid'] ? (int) $_GET['cid'] : 0;

if (!$cid){
    return;
}

$type = $_GET['type'] ?  $_GET['type'] : '';

$custom_url_id= $_GET['url'] ? (int) $_GET['url'] : 0;

$debug = false;
if ($_GET['debug']) {
    $debug=true;
}

if (!class_exists('MoviesLinks')) {

    require_once( MOVIES_LINKS_PLUGIN_DIR . 'db/MoviesAbstractFunctions.php' );
    require_once( MOVIES_LINKS_PLUGIN_DIR . 'db/MoviesAbstractDB.php' );

    require_once( MOVIES_LINKS_PLUGIN_DIR . 'MoviesLinks.php' );
    require_once( MOVIES_LINKS_PLUGIN_DIR . 'MoviesParserCron.php' );
}

$ml = new MoviesLinks();

$cron_name = 'async_cron_'.$cid.'_'.$type;
if ($ml->cron_already_run($cron_name, 10, $debug)) {
    exit();
}

$ml->register_cron($cron_name);

$mpc = new MoviesParserCron($ml);
$mpc->run_cron_async($cid, $type, $debug, $custom_url_id);

$ml->unregister_cron($cron_name);