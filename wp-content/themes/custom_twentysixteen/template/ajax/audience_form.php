<?php

if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    exit();
}

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
    $cid = isset($_GET['cid']) ? (int) $_GET['cid'] : 0;

    if ($cid) {
        $au_data = $ca->get_audata($cid);
        print $ca->audience_form($id, $au_data);
        exit;
    }

    $avoted = $ca->already_voted($id, $edit);

    $ret = $avoted['ret'];
    /*
     * return: 
     * 0 - no vote
     * 1 - voted
     * 2 - can edit
     * 3 - can add new review
     */
    if ($ret == 0) {
        print $ca->audience_form($id);
    } else if ($ret == 1) {
        // Already voted
        $ca->already_voted_msg();
    } else if ($ret == 2) {
        // Can edit        
        print $ca->audience_form($id, $avoted['au_data']);
    } else if ($ret == 3) {
        print $ca->audience_form($id);
    }
}

