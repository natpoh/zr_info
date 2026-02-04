<?php

require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-config.php');
set_time_limit(0);
/*
 * Transit critcs wp_posts to critic_matic posts
 * https://rwt.4aoc.ru/wp-content/plugins/critic_matic/cron/an_transit_lang_cron.php?p=D_23_2D0FS0-vbb&debug=1
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


require_once( CRITIC_MATIC_PLUGIN_DIR . 'CriticTransit.php' );
$cr = new CriticTransit($cm);


$cron_name = 'critic_matic_transit_lang';
if ($cm->cron_already_run($cron_name, 10, $debug, $force)) {
    exit();
}
$cm->register_cron($cron_name);

// Transit lang
$cr->transit_lang($count, $debug, $force);

$cm->unregister_cron($cron_name);
