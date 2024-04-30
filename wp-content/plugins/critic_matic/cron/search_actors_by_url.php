<?php
/*
 * /wp-content/plugins/critic_matic/cron/search_actors_by_url.php?p=D_23_2D0FS0-vbb
 */
require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-config.php');
set_time_limit(0);

if (!class_exists('CriticMatic')) {
    return;
}

$p = 'D_23_2D0FS0-vbb';

if ($_GET['p'] != $p) {
    return;
}


$url = '/search/rimdb_75-100';
#$url = '/search/release_1878-1958/type_movies';
// Init url
$last_req = $_SERVER['REQUEST_URI'];

$_SERVER['REQUEST_URI'] = $url;
$search_front = new CriticFront();
$search_front->init_search_filters();

$page =  1;
$search_limit = 100;


$start = 0;
if ($page > 1) {
    $start = ($page - 1) * $search_limit;
}
$filters = $search_front->get_search_filters();
print_r(array($search_limit, $start));

// Find movies in AN base
$group_field='actor_star';
$result = $search_front->cs->front_search_actors_list($search_front->keywords, $search_limit, $start,$group_field, $filters);

// Deinit url
$_SERVER['REQUEST_URI'] = $last_req;

print '<pre>';
print_r($filters);
print_r($result);
print '</pre>';
