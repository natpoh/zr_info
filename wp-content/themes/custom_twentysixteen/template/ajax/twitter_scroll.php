<?php

if (!defined('ABSPATH')){
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');
}

require_once(ABSPATH. 'wp-load.php' );


//DB config
!defined('DB_HOST_AN') ? include ABSPATH . 'analysis/db_config.php' : '';
//Abstract DB
!class_exists('Pdoa') ? include ABSPATH . "analysis/include/Pdoa.php" : '';

if (isset($_GET['id'])) {
    $movie_id = (int)$_GET['id'];


    $sql = "SELECT * FROM `data_movie_imdb` where `id` ='" . $movie_id . "' limit 1 ";
    $r = Pdo_an::db_fetch_row($sql);

    $movie_title = $r->title;
    $year = $r->year;
//filter:verified



    $atts =array('search'=> '"'.$movie_title.'"  lang:en');
    $content= ctf_init( $atts );

    if (strstr($content,'Unable to load Tweets'))
    {
        $content='';
    }
    else {

        $content = '<div class="column_inner_content twitter_content">
<div class="popup-close"></div>
            <h3 class="column_header">Twitter:</h3>

            <div class="s_container smoched">
                <div class="column_inner_content_data">
                    <div  id="twitter_scroll?unverified" class="s_container_inner">'.$content.'</div>
                    <div class="s_container_load"></div>
                </div>
            </div>
        </div>';
    }

    echo $content;

}


?>