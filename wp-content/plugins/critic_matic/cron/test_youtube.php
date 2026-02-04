<?php
/*
 * /wp-content/plugins/critic_matic/cron/test_youtube.php?p=8ggD_23_2D0DSF-F&url=https://www.youtube.com/watch?v=aKgZd2xjw6k
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

$json = false;
if ($_GET['json']) {
    $json = true;
}



if ($_GET['url']) {
    $url = $_GET['url'];
}

if ($_GET['cid']) {
    $cid = $_GET['cid'];
}


if (!class_exists('CriticParser')) {
    require_once( CRITIC_MATIC_PLUGIN_DIR . 'CriticParser.php' );
}

$cp = new CriticParser();
$cpyoutube = $cp->get_cpyoutube();
if ($url) {
    $data = $cpyoutube->yt_video_data($url);
} else if ($cid) {
    $data = $cpyoutube->youtube_get_channel_info($cid);
}
if ($data) {

    if ($json) {

        echo json_encode($data);
    } else {
        print '<pre>';
        print_r($data);
        print '</pre>';
    }
}
