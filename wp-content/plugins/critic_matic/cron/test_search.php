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

$query = '';
$sql = "SELECT GROUPBY() as id, COUNT(*) as cnt FROM movie_an WHERE id>0 AND year_int >=0 AND year_int < 2023 GROUP BY dirsrace ORDER BY cnt DESC LIMIT 0,10";
//$sql = "SELECT GROUPBY() as id, COUNT(*) as cnt FROM movie_an WHERE id>0 AND year_int >=0 AND year_int < 2023 GROUP BY writersrace ORDER BY cnt DESC LIMIT 0,10";
//$sql = "SELECT GROUPBY() as id, COUNT(*) as cnt FROM movie_an WHERE id>0 AND year_int >=0 AND year_int < 2023 GROUP BY castdirrace ORDER BY cnt DESC LIMIT 0,10";
//$sql = "SELECT GROUPBY() as id, COUNT(*) as cnt FROM movie_an WHERE id>0 AND year_int >=0 AND year_int < 2023 GROUP BY producerrace ORDER BY cnt DESC LIMIT 0,10";
//$result = $cs->movies_facet_single_get($sql, $query);


$facets = array('dirrace','race_dir');
$search_front = new CriticFront();
$filters = $search_front->get_search_filters();
$max_count = 10;
$keywords = '';

$result = $cs->front_search_movies_multi($keywords, $facets, 0, array(), $filters, $facets, true, true, false);


print '<pre>';
print_r($result);
print '</pre>';