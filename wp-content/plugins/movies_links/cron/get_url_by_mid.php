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
global $debug;
$debug = $_GET['debug'];

$cid = $_GET['cid'];


if ($debug)
{
    echo '$cid='.$cid;
}

if (!strstr($cid,','))
{
    $cid = intval($cid);
}

$mid = (int) $_GET['mid'];

if (!class_exists('MoviesLinks')) {

    require_once( MOVIES_LINKS_PLUGIN_DIR . 'db/MoviesAbstractFunctions.php' );
    require_once( MOVIES_LINKS_PLUGIN_DIR . 'db/MoviesAbstractDB.php' );

    require_once( MOVIES_LINKS_PLUGIN_DIR . 'MoviesLinks.php' );
    require_once( MOVIES_LINKS_PLUGIN_DIR . 'MoviesParserCron.php' );
}

$ml = new MoviesLinks();
// get parser
$mp = $ml->get_mp();


$url = $mp->get_url_by_mid($mid, $cid);
if (!$url){
    $url = $mp->get_url_by_top_movie($mid,$cid);
}

$url_data = new stdClass();
$url_data->link=$url->link;
echo json_encode($url_data);


