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

$cm = new CriticMatic();
$cs = $cm->get_cs();


$keywords='avatar';
//$keywords='Avatar: The Way of Water';
//$keywords='Avatar';
$result = $cs->find_in_newsfilter_raw($keywords, 20,  true);


print '<pre>';
print_r($result);
print '</pre>';