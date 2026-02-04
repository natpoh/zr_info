<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");

if (isset($_POST['nowpapi'])) {    
    // No wp api
    if (!defined('ABSPATH')) {
        define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');
    }

    if (!defined('CRITIC_MATIC_PLUGIN_DIR')) {
        define('CRITIC_MATIC_PLUGIN_DIR', ABSPATH . 'wp-content/plugins/critic_matic/');
        require_once( CRITIC_MATIC_PLUGIN_DIR . 'critic_matic_ajax_inc.php' );
    }
} else {
    // Wp api
    require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-config.php');
}

$cm = new CriticMatic();
$comment_votes = $cm->get_comment_votes();
$action = $_POST['action'];

if ($action =='thumb') {
    $comment_votes->ajax_thumb($_POST);
} else if ($action =='vote_info_users') {
    $comment_votes->ajax_vote_info_users($_POST);
}else if ($action =='get_thumbs_vote_data') {
    $comment_votes->ajax_thumbs_vote_data($_POST);
}
    


    




