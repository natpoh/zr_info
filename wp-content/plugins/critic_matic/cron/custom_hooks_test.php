<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

if (!defined('ABSPATH')) {
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');
}

if (!defined('CRITIC_MATIC_PLUGIN_DIR')) {
    define('CRITIC_MATIC_PLUGIN_DIR', ABSPATH . 'wp-content/plugins/critic_matic/');
}

!class_exists('CustomHooks') ? include CRITIC_MATIC_PLUGIN_DIR . "CustomHooks.php" : '';


$new_rating = array('rating'=>10);

CustomHooks::do_action('erating', $new_rating);