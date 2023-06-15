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

$acount = 100;
if ($_GET['ac']) {
    $acount = (int) $_GET['ac'];
}

$debug = false;
if ($_GET['debug']) {
    $debug = true;
}

$force = false;
if ($_GET['force']) {
    $force = true;
}
//if ($_GET['directors']) {
//
//    $cm = new CriticMatic();
//    require_once( CRITIC_MATIC_PLUGIN_DIR . 'CriticTransit.php' );
//
//    $cr = new CriticTransit($cm);
//    $cr->transit_directors($count, $debug);
//    return;
//}

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

//// Create actors slug
//$cr->actor_slug($acount, $debug);


$cron_name = 'critic_matic_transit';
if ($cm->cron_already_run($cron_name, 10, $debug, $force)) {
    exit();
}
$cm->register_cron($cron_name);

// Indie tags
$cr->transit_indie_tags($count, $debug, $force);

/*
// Transit countries
$cr->transit_countries($count, $debug, $force);

// Transit genres
$cr->transit_genres($count, $debug);

// Upload pro-user avatars for new authors
$cav = $cm->get_cav();
$cav->transit_pro_avatars($count, $debug, $force);
*/
$cm->unregister_cron($cron_name);


/*
 * UNUSED OLD TASKS

$cr->transit_actors($count, $debug);

// One time task. Complite
$cr->transit_providers();

// Remove meta type 2.
$cr->remove_unused_meta($count, $debug);

*/