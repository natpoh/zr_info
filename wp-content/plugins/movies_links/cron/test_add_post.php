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

$debug = true;

if (!class_exists('MoviesLinks')) {
    require_once( MOVIES_LINKS_PLUGIN_DIR . 'db/MoviesAbstractFunctions.php' );
    require_once( MOVIES_LINKS_PLUGIN_DIR . 'db/MoviesAbstractDB.php' );
    require_once( MOVIES_LINKS_PLUGIN_DIR . 'MoviesLinks.php' );
    require_once( MOVIES_LINKS_PLUGIN_DIR . 'TorParser.php' );
}

// Url id
$uid = 0;
if ($_GET['uid']) {
    $uid = (int) $_GET['uid'];
}

// Campaign id
$cid = 0;
if ($_GET['cid']) {
    $cid = (int) $_GET['cid'];
}

if ($uid && $cid){
    
}