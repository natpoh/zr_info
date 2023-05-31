<?php

if (isset($_POST['ajaxAct'])) {
    header('Access-Control-Allow-Origin:*');

    if (strstr($_SERVER['DOCUMENT_ROOT'], 'service')) {
        $root = str_replace('/service', '', $_SERVER['DOCUMENT_ROOT']);
    } else {
        $root = $_SERVER['DOCUMENT_ROOT'];
    }

    if (!defined('ABSPATH'))
        define('ABSPATH', $root . '/');

    if (!defined('CRITIC_MATIC_PLUGIN_DIR')) {
        define('CRITIC_MATIC_PLUGIN_DIR', ABSPATH . 'wp-content/plugins/critic_matic/');
    }

    $cm = new CriticMatic();
    $si = $cm->get_si();
    
    $debug = (int) $_GET['debug'];

    if ($post_id) {
        
    }
} 