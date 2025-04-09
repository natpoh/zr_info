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


$id = $_GET['id'];
$func = $_GET['func'];
$keys = $_GET['keys'];

$cfront = new CriticFront();
if ($_GET['debug']&&$_GET['debug']==1){
    $cfront->debug=true;
}

if ($func == 'theme_card_author') {
    $cfront->theme_card_author('', $id, false);
} else if ($func == 'post_meta_block') {  
    $info_link = $cfront->theme_post_meta_block($id, $keys);    
    print $info_link;
    
} else if ($func == 'audience_carousel') {    
    print $cfront->audience_carousel($id, $keys,false);
    
} else if ($func == 'actor_carousel') {    
    print $cfront->actors_carousel($id, $keys);
    
}  else if ($func == 'custom_carousel') {
    if (preg_match('#([0-9]+)$#', $id, $match)){       
        print $cfront->get_custom_carousel($match[1]);
    }    
} else if ($func == 'review_img_block'){
    $cfront->get_reveiw_img($id);
} else if ($func == 'movie_cache') {     
    $cfront->ajax_load_movie($id, $keys);
}



