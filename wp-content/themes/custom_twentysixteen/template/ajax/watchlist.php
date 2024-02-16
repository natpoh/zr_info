<?php

if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    exit();
}

// WP api
require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-config.php');
global $cfront;

if ($cfront) {
    if (isset($_POST['request'])) {
        $data = '';
        $wl = $cfront->get_wl();
        if ($_POST['request'] == 'get_lists') {
            $mid = intval($_POST['mid']);
            print $wl->ajax_get_user_lists($mid);
        } else if ($_POST['request'] == 'set_emtns') {
            $wl->get_ajax();
        }
    }
}
