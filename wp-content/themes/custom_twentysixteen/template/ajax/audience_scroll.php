<?php

error_reporting('E_ALL');
ini_set('display_errors', 'On');

require('../include/custom_connect.php');


/*
 * New api after 23.07.2021
 */


if (class_exists('Pdo_wp')) {
    if (!defined('CRITIC_MATIC_PLUGIN_DIR')) {
        define('CRITIC_MATIC_PLUGIN_DIR', ABSPATH . 'wp-content/plugins/critic_matic/');
    }

    if (!class_exists('CriticFront')) {
        require_once( CRITIC_MATIC_PLUGIN_DIR . 'critic_matic_ajax_inc.php' );
    }

    $cfront = new CriticFront();

    if (isset($_GET['id'])) {
        $movie_id = (int) $_GET['id'];
    }
    
    print $cfront->get_audience_scroll_data($movie_id);
    exit();
}

