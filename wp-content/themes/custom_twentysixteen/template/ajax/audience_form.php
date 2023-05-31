<?php

// WP api
require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-config.php');
/*
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
 * 
 */
global $cfront;
if (!$cfront) {
    exit;
}

$ca = $cfront->get_ca();

// Get form
if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    $avoted = $ca->already_voted($id);
    $ret = $avoted['ret'];
    /*
     * return: 
     * 0 - no vote
     * 1 - voted
     * 2 - can edit
     */
    if ($ret == 0) {
        print $ca->audience_form($id);
    } else if ($ret == 1) {
        // Already voted
        $ca->already_voted_msg();
    } else if ($ret == 2) {
        // Can edit        
        print $ca->audience_form($id, $avoted['au_data']);
    }
}

