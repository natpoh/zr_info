<?php

/*
 * /wp-content/plugins/movies_links/cron/tor_test.php?p=8ggD_23_2D0DSF-F
 */

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

$url_test = 'https://info.antiwoketomatoes.com/service/request.php?p=dfs_WFDS-32FhGSD6';
if ($_GET['url_test']) {
    $url_test = urldecode($_GET['url_test']);
}

if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');


//Movies links rating
if (!function_exists('include_movies_links')) {
    include ABSPATH . 'wp-content/plugins/movies_links/movies_links.php';
}

include_movies_links();

if (!class_exists('TorParser')) {
    require_once( MOVIES_LINKS_PLUGIN_DIR . 'TorParser.php' );
}

$tp = new TorParser();


// Example post vars
/*$post_vars = array(
    'id' => 1,
    'string' => 'test'
);*/
$post_vars = array();


$is_post = false;
if ($_GET['is_post']) {
    $is_post = true;
}

$ip_limit = array(
    'h' => 20, // 20 requests for one proxy per hour
    'd' => 200 // 200 requests for one proxy per day
);

/*
 * Curl
 * True - get from curl
 * False - get from webdriver
 */
$curl = true;
if ($_GET['webdriver']) {
    $curl = false;
}

$tor_mode = 0;
/* Use proxy:
 * 0 - tor and proxy
 * 1 - tor
 * 2 - proxy
 */
if ($_GET['mode']) {
    $tor_mode = (int) $_GET['mode'];
}

/*
 * $tor_agent
 * 1 - random agent
 * 2 - get agent from db
 */
$tor_agent=1;

$header_array=array();

$content = $tp->get_url_content($url_test, $header, $ip_limit, $curl, $tor_mode, $tor_agent, $is_post, $post_vars, $header_array, $debug);

print_r($content);
