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

$cf = new CriticFront();

$mid = 22077;
$limit = 1000;
$result = $cf->get_movie_tags_facet($mid, $limit);


print '<pre>';
print_r($result);
print '</pre>';
