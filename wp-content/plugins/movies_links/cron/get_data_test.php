<?php

if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');

$mid = (int) $_GET['mid'];
$cid = (int) $_GET['cid'];

if (!$mid || !$cid) {
    return;
}

//Movies links rating
if (!function_exists('include_movies_links')) {
    include ABSPATH . 'wp-content/plugins/movies_links/movies_links.php';
}

include_movies_links();

$ml = new MoviesLinks();

$post = $ml->get_post_data($mid, $cid);
print '<pre>';
print_r($post);
print '</pre>';

if ($post) {
    $mp = $ml->get_mp();
    $arhive = $mp->get_arhive_by_url_id($post->uid);

    if ($arhive) {
        $content = $mp->get_arhive_file($cid, $arhive->arhive_hash);
        print "Arhive len: " . strlen($content);
    }
}