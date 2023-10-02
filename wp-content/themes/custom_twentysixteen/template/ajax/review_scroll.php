<?php

error_reporting('E_ALL');
ini_set('display_errors', 'On');

if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    exit();
}

/*
 * New api after 23.07.2021
 */
if (!defined('ABSPATH')) {
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');
}

if (!defined('CRITIC_MATIC_PLUGIN_DIR')) {
    define('CRITIC_MATIC_PLUGIN_DIR', ABSPATH . 'wp-content/plugins/critic_matic/');
}

if (!class_exists('CriticFront')) {
    require_once( CRITIC_MATIC_PLUGIN_DIR . 'critic_matic_ajax_inc.php' );
}
!class_exists('RWT_RATING') ? include ABSPATH . "wp-content/themes/custom_twentysixteen/template/include/movie_rating.php" : '';

$cfront = new CriticFront();

// Enable new api

/* Author type
  0 => 'Staff',
  1 => 'Pro',
 */


$movie_id = 0;
if (isset($_GET['id'])) {
    $movie_id = (int) $_GET['id'];
}

$tags = array();
if (isset($_GET['tags'])) {
    $tags = $_GET['tags'];
    $tags_valid = array();
    foreach ($tags as $tag) {
        $tags_valid[] = (int) $tag;
    }
    $tags = $tags_valid;
}

print $cfront->get_review_scroll_data($movie_id, $tags);
exit();

