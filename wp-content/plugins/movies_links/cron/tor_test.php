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

$curl = false;
if ($_GET['curl']) {
    $curl = true;
}


$url_test = 'https://rightwingtomatoes.com/service/request.php?p=dfs_WFDS-32FhGSD6';
if ($_GET['url_test']) {
    $url_test = urldecode($_GET['url_test']);
}

if (!class_exists('MoviesLinks')) {
    require_once( MOVIES_LINKS_PLUGIN_DIR . 'db/MoviesAbstractFunctions.php' );
    require_once( MOVIES_LINKS_PLUGIN_DIR . 'db/MoviesAbstractDB.php' );
    require_once( MOVIES_LINKS_PLUGIN_DIR . 'MoviesLinks.php' );
    require_once( MOVIES_LINKS_PLUGIN_DIR . 'TorParser.php' );
}

$tp = new TorParser();

$content = $tp->get_url_content($url_test, $header, array(), $curl, true);
