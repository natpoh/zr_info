<?php

if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    exit();
}

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

    $movie_id = 0;
    if (isset($_GET['id'])) {
        $movie_id = (int) $_GET['id'];
    }

    // Default positive
    $vote = 1;
    if (isset($_GET['vote'])) {
        $vote = (int) $_GET['vote'];
    }

    $search = false;
    if (!$movie_id) {
        $search = true;
    }

    print $cfront->get_scroll('audience_scroll', $movie_id, $vote, $search);


    exit();
}

