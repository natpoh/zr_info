<?php

error_reporting('E_ALL');

if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');


if (!defined('CRITIC_MATIC_PLUGIN_DIR')) {
    define('CRITIC_MATIC_PLUGIN_DIR', ABSPATH . 'wp-content/plugins/critic_matic/');
}

if (!class_exists('CriticFront')) {
    require_once( CRITIC_MATIC_PLUGIN_DIR . 'critic_matic_ajax_inc.php' );
}

$cfront = new CriticFront();
if ($cfront->new_api) {
    if (isset($_POST['request'])) {
        $data = '';

        if ($_POST['request'] == 'get_emtns') {
            $id = intval($_POST['id']);
            print $cfront->ce->get_emotions($id);
        } else if ($_POST['request'] == 'set_emtns') {
            $cfront->ce->get_ajax();
        }
    }
    exit;
}

include (ABSPATH . '/wp-content/themes/custom_twentysixteen/template/include/custom_connect.php');
include (ABSPATH . '/wp-content/themes/custom_twentysixteen/template/include/emotiondata.php');

if (isset($_POST['request'])) {
    $data = '';

    if ($_POST['request'] == 'get_emtns') {

        $id = intval($_POST['id']);

        if (function_exists('get_emotions')) {
            echo get_emotions($id);
        } else {
            
        }
    } else if ($_POST['request'] == 'set_emtns') {
        get_ajax();
    }
}


