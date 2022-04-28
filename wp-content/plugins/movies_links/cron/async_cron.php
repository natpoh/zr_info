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


if (!class_exists('MoviesLinks')) {

    require_once( MOVIES_LINKS_PLUGIN_DIR . 'db/MoviesAbstractFunctions.php' );
    require_once( MOVIES_LINKS_PLUGIN_DIR . 'db/MoviesAbstractDB.php' );

    require_once( MOVIES_LINKS_PLUGIN_DIR . 'MoviesLinks.php' );
    require_once( MOVIES_LINKS_PLUGIN_DIR . 'MoviesParserCron.php' );
}

$mpc = new MoviesParserCron();
$mpc->run_cron_async($cid, $type);