<?php
if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    exit();
}
$_GET['type'] = 'compilation';

if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');

if (isset($_GET['cid'])) {
    $_GET['id'] = $_GET['cid'];
}

ob_start();
if (!class_exists('TV_Scroll')) {
    require(ABSPATH . 'wp-content/themes/custom_twentysixteen/template/ajax/tv_scroll.php');
}

$data = ob_get_contents();
ob_clean();

// Get watchlists
global $cfront;

if (!$cfront) {
    if (!defined('CRITIC_MATIC_PLUGIN_DIR')) {
        define('CRITIC_MATIC_PLUGIN_DIR', ABSPATH . 'wp-content/plugins/critic_matic/');
    }

    if (!class_exists('CriticFront')) {
        require_once(CRITIC_MATIC_PLUGIN_DIR . 'critic_matic_ajax_inc.php');
    }
    $cfront = new CriticFront();
}


$user = $cfront->cm->get_current_user();

if ($user->ID) {
    $data = $cfront->append_watch_list_scroll_data($data);
}

print $data;
