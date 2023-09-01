<?php

// WP api
require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-config.php');
global $cfront;

/*
  // No WP api
  if (!defined('ABSPATH'))
  define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');


  if (!defined('CRITIC_MATIC_PLUGIN_DIR')) {
  define('CRITIC_MATIC_PLUGIN_DIR', ABSPATH . 'wp-content/plugins/critic_matic/');
  }

  if (!class_exists('CriticFront')) {
  require_once( CRITIC_MATIC_PLUGIN_DIR . 'critic_matic_ajax_inc.php' );
  }

  $cfront = new CriticFront();
 */
if ($cfront) {
    if (isset($_POST['request'])) {
        $data = '';
        if ($_POST['request'] == 'get_emtns') {
            $id = intval($_POST['id']);
            $ptype = intval($_POST['ptype']);
            print $cfront->ce->get_emotions($id, $ptype);
        } else if ($_POST['request'] == 'set_emtns') {
            $cfront->ce->get_ajax();
        }
    }
}

exit;
