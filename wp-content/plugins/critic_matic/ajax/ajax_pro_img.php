<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");

/*
  if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
  exit();
  }
 */

if (!defined('ABSPATH')) {
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');
}

if (!defined('CRITIC_MATIC_PLUGIN_DIR')) {
    define('CRITIC_MATIC_PLUGIN_DIR', ABSPATH . 'wp-content/plugins/critic_matic/');
    require_once( CRITIC_MATIC_PLUGIN_DIR . 'critic_matic_ajax_inc.php' );
}


$cm = new CriticMatic();
$cav = $cm->get_cav();
if ($_POST['remove']) {
    print $cav->ajax_remove_img();
} else if ($_POST['upload_file']) {
    print $cav->ajax_upload_img();
} else {
    print $cav->ajax_pro_img();
}

