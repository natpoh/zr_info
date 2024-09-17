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
$comments = $cm->get_comments();
$action = $_POST['action'];

if ($action == 'respond') {
    $comments->ajax_respond($_POST);
} else if ($action == 'get_childs') {
    $comments->ajax_get_childs($_POST);
} else if ($action == 'simple_edit_comment') {
    $comments->ajax_simple_edit_comment($_POST);
} else if ($action == 'get_ban_info') {
    $comments->ajax_get_ban_info($_POST);
} else if ($action == 'flag_cmt') {
    $comments->ajax_flag_cmt($_POST);
} else if ($action == 'spam_cmt') {
    $comments->ajax_spam_cmt($_POST);
} else if ($action == 'change_cmt') {
    $comments->ajax_change_cmt($_POST);
} else if ($action == 'get_comments_page') {
    $comments->ajax_get_comments_page($_POST);
} else if ($action == 'get_three') {
    $comments->ajax_get_three($_POST);
} else if ($action == 'respond_form') {
    $comments->ajax_respond_form($_POST);
} 