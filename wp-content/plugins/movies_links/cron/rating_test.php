<?php
if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');
//DB config
!defined('DB_HOST_AN') ? include ABSPATH . 'analysis/db_config.php' : '';
//Abstract DB
!class_exists('Pdoa') ? include ABSPATH . "analysis/include/Pdoa.php" : '';

$id = (int)$_GET['id'];

if (!$id){
    return;
}

//Movies links rating
if (!function_exists('include_movies_links')) {
    include ABSPATH . 'wp-content/plugins/movies_links/movies_links.php';
}

include_movies_links();

$ml = new MoviesLinks();
$score_opt = array('tomatometerScore', 'audienceScore');
$score_result = $ml->get_post_options($id, $score_opt);

$total_tomatoes = $score_result['tomatometerScore'];
$total_tomatoes_audience = $score_result['audienceScore'];

print_r($score_result);
