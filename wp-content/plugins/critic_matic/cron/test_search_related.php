<?php

require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-config.php');
set_time_limit(0);


if (!class_exists('CriticMatic')) {
    return;
}

$p = 'D_23_2D0FS0-vbb';

if ($_GET['p'] != $p) {
    return;
}

$mid = $_GET['mid'] ? $_GET['mid'] : 20769;

if (!$mid) {
    return;
}

$debug = false;
if ($_GET['debug']) {
    $debug = true;
}


$count = 100;
if ($_GET['c']) {
    $count = (int) $_GET['c'];
}

$cm = new CriticMatic();
$cs = $cm->get_cs();


$strict_type = 0;
if ($_GET['t']) {
    // t - media type:
    // 0 - any type
    // 1 - source type
    // 2 - movie and tv
    $strict_type = (int) $_GET['t'];
}

$result = $cs->related_movies($mid, $count, $strict_type, $debug);

print '<pre>';
print_r($result);
print '</pre>';
