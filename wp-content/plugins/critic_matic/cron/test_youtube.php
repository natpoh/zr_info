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

$url = 'https://www.youtube.com/watch?v=DapcHLXSLPo';
if ($_GET['url']) {
    $url = $_GET['url'];
}


if (!class_exists('CriticParser')) {
    require_once( CRITIC_MATIC_PLUGIN_DIR . 'CriticParser.php' );
}

$cp = new CriticParser();

$data = $cp->yt_video_data($url);

print '<pre>';
print_r($data);
print '</pre>';