<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

if (!defined('ABSPATH')) {
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');
}


!class_exists('CustomHooks') ? include ABSPATH . "wp-content/plugins/critic_matic/CustomHooks.php" : '';


$new_rating = array('rating'=>10);
CustomHooks::do_action('erating', $new_rating);