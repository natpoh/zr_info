<?php

require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-config.php');
nocache_headers();

if (!function_exists('movies_links_init')) {
    return;
}

$p = '8ggD_23_2D0DSF-F';

if ($_GET['p'] != $p) {
    return;
}


if (!class_exists('MoviesLinks')) {

    require_once( MOVIES_LINKS_PLUGIN_DIR . 'db/MoviesAbstractFunctions.php' );
    require_once( MOVIES_LINKS_PLUGIN_DIR . 'db/MoviesAbstractDB.php' );

    require_once( MOVIES_LINKS_PLUGIN_DIR . 'MoviesLinks.php' );
    require_once( MOVIES_LINKS_PLUGIN_DIR . 'MoviesParserCron.php' );
}

$ml = new MoviesLinks();
// get parser
$mp = $ml->get_mp();

$company_id = 12;
$start = 0;
$count = 100;

$arhives = $mp->get_last_arhives($company_id,$start,$count);


if ($arhives){
    foreach ($arhives as $item) {
        
       $file = $mp->get_arhive_file($company_id,$item->arhive_hash);
       print_r($item);
       print_r($file);
    }
}
