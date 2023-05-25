<?php

require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-config.php');
set_time_limit(0);
/*
 * Transit critcs wp_posts to critic_matic posts
 */

if (!class_exists('CriticMatic')) {
    return;
}

$p = 'D_23_2D0FS0-vbb';

if ($_GET['p'] != $p) {
    return;
}

$count = 10;
if ($_GET['c']) {
    $count = (int) $_GET['c'];
}


$debug = false;
if ($_GET['debug']) {
    $debug = true;
}

$force = false;
if ($_GET['force']) {
    $force = true;
}



$cm = new CriticMatic();



$ac= $cm->get_ac();
$ac->test_ethnic();

