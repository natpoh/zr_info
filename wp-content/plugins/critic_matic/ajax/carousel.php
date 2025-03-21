<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");

if (!defined('ABSPATH')) {
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');
}

if (!defined('CRITIC_MATIC_PLUGIN_DIR')) {
    define('CRITIC_MATIC_PLUGIN_DIR', ABSPATH . 'wp-content/plugins/critic_matic/');
    require_once( CRITIC_MATIC_PLUGIN_DIR . 'critic_matic_ajax_inc.php' );
}


$p = (int) $_GET['page'];
$limit = (int) $_GET['limit'];
$type = $_GET['type'];
$per_page = (int) $_GET['per_page'];
$movie_id = (int) $_GET['movie_id'];

$cfront = new CriticFront();
if ($type =='pro_scroll') {
    $items = $cfront->get_pro_carousel_data($p, $per_page, $limit, $movie_id);
} else if (strstr($type, 'audience_scroll')) {   
    try {
        $type_arr = explode('_', $type);
        $vote_type =  $type_arr[2];
    } catch (Exception $exc) {
        $vote_type = 0;
    }

    $items = $cfront->get_audience_carousel_data($p, $per_page, $limit, $movie_id, $vote_type);
} else if (strstr($type, 'custom_carousel')) {      
    if (preg_match('#([0-9]+)$#', $type, $match)){               
        $items = $cfront->get_custom_carousel_data($match[1],$p, $per_page, $limit);
    } 
    
    
}else {
    // Sample
    $items = array();
    $num = $p * $limit;
    for ($i = 0; $i < $limit; $i++) {
        $el = $i + $num;
        $item = "<p>Element {$el} <br /> No {$i}. page {$p}</p>";
        $items[] = $item;
    }
}
$ret = array('items' => $items);
print json_encode($ret);
