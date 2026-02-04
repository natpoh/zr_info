<?php

error_reporting('E_ALL');
ini_set('display_errors', 'On');

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

print $cfront->get_stuff_scroll_data($movie_id);

exit();
