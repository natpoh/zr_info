<?php

require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-config.php');
nocache_headers();

if (!class_exists('CriticMatic')) {
    return;
}

$p = '8ggD_23_2D0DSF-F';

if ($_GET['p'] != $p) {
    return;
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

if (!class_exists('CriticFeeds')) {
    //Critic feeds    
    require_once( CRITIC_MATIC_PLUGIN_DIR . 'CriticFeeds.php' );
}

$cm = new CriticMatic();

$cron_name = 'critic_feed';
if ($cm->cron_already_run($cron_name, 10, $debug)) {
    exit();
}

$cm->register_cron($cron_name);

$cf = new CriticFeeds($cm);
$cf->run_cron();

$cm->unregister_cron($cron_name);