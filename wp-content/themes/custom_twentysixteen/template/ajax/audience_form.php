<?php

if (isset($_POST['wpcr3_ajaxAct'])) {
    // WP api
    require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-config.php');
    // Post form data
    global $cfront;
    $cfront = new CriticFront();
    $ca = $cfront->get_ca();
    $ca->ajax();
} else {
    // NO wp api

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

    $ca = $cfront->get_ca();

    // Get form
    if ($_GET['id']) {
        $id = (int) $_GET['id'];
        if ($ca->already_voted($id)) {
            $ca->already_voted_msg();
        } else {
            print $ca->audience_form($id);
        }
    }
}
