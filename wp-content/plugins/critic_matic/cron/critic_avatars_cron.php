<?php
/*
 * /wp-content/plugins/critic_matic/cron/critic_avatars_cron.php?p=8ggD_23_2D0DSF-F&debug=1&c=1&force=1
 */
require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-config.php');
nocache_headers();

if (!class_exists('CriticMatic')) {
    return;
}

$p = '8ggD_23_2D0DSF-F';

if ($_GET['p'] != $p) {
    return;
}
// Cron type
$cron_type = 1;
if ($_GET['t']) {
    $cron_type = (int) $_GET['t'];
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
$cav = $cm->get_cav();

$cron_name = 'critic_avatars';
if ($cm->cron_already_run($cron_name, 10, $debug, $force)) {
    exit();
}

$cm->register_cron($cron_name);
$cav->run_cron($cron_type, $force, $debug, $count);
$cm->unregister_cron($cron_name);