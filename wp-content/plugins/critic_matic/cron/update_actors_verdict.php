<?php

/*
 * 1. Get actors list
 * 2. Calculate verdicts
 * 3. Update verdicts
 */

require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-config.php');
set_time_limit(0);

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

require_once( CRITIC_MATIC_PLUGIN_DIR . 'CriticTransit.php' );

$cm = new CriticMatic();
$cr = new CriticTransit($cm);

$cr->get_actors_meta($count, $debug , $force);