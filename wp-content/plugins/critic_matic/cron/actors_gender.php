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

// Check server load
!class_exists('CPULOAD') ? include ABSPATH . "service/cpu_load.php" : '';
$load = CPULOAD::check_load();
if ($load['loaded']) {
    if ($debug) {
        p_r($load);
    }
    exit();
}

$cm = new CriticMatic();

$secret = false;
if ($_GET['export']) {
   // Transit authors secret key
   // One time task 
   // $cr->export_csv();
   // exit;
}

$cron_name = 'actor_gender_auto';
if ($cm->cron_already_run($cron_name, 10, $debug, $force)) {
    exit();
}
$cm->register_cron($cron_name);

//One time transit data
require_once( CRITIC_MATIC_PLUGIN_DIR . 'CriticTransit.php' );
$cr = new CriticTransit($cm);
$cr->actor_gender_auto($count,$debug,$force);
//$cr->actor_transit_first_name($count,$debug,$force);


$cm->unregister_cron($cron_name);