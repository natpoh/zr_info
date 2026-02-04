<?php

if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    exit();
}

if (!defined('ABSPATH')) {
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');
}

if (!defined('CRITIC_MATIC_PLUGIN_DIR')) {
    define('CRITIC_MATIC_PLUGIN_DIR', ABSPATH . 'wp-content/plugins/critic_matic/');
    require_once( CRITIC_MATIC_PLUGIN_DIR . 'critic_matic_ajax_inc.php' );
}

$cm = new CriticMatic();

if (isset($_POST['request'])) {
    $data = '';
    $wl = $cm->get_wl();
    if ($_POST['request'] == 'get_lists') {
        // Get lists
        $mid = intval($_POST['mid']);
        print $wl->ajax_get_user_lists($mid);
    } else if ($_POST['request'] == 'add_new') {
        // Add new list
        $title = $_POST['title'];
        $content = $_POST['content'];
        $publish = intval($_POST['publish']);
        $mid = intval($_POST['mid']);
        print $wl->ajax_add_new_list($title, $content, $publish, $mid);
    } else if ($_POST['request'] == 'update') {
        // Add new list
        $title = $_POST['title'];
        $content = $_POST['content'];
        $publish = intval($_POST['publish']);
        $id = intval($_POST['id']);
        print $wl->ajax_update_list($title, $content, $publish, $id);
    } else if ($_POST['request'] == 'select') {
        // Select list
        $act = $_POST['act'];
        $id = intval($_POST['id']);
        $mid = intval($_POST['mid']);
        print $wl->ajax_select_list($id, $mid, $act);
    } else if ($_POST['request'] == 'list_menu') {
        // List dropdown menu
        $act = $_POST['act'];
        $id = intval($_POST['id']);
        $parent = intval($_POST['parent']);
        print $wl->ajax_list_menu($id, $parent, $act);
    } else if ($_POST['request'] == 'select_list') {
        // Fast select list
        $mid = intval($_POST['mid']);
        $activate = intval($_POST['activate']);
        $type = intval($_POST['type']);
        print $wl->ajax_select($mid, $activate, $type);
    }
}

