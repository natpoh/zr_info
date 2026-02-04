<?php
///not used
//return;
if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    exit();
}


error_reporting('E_ALL');
ini_set('display_errors', 'On');
if (!defined('ABSPATH')){
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');
}

//DB config
!defined('DB_HOST_AN') ? include ABSPATH . 'analysis/db_config.php' : '';
//Abstract DB
!class_exists('Pdoa') ? include ABSPATH . "analysis/include/Pdoa.php" : '';

if (isset($_GET['id'])) {
    $movie_id = (int) $_GET['id'];


    $sql = "SELECT * FROM `data_movie_imdb` where `id` ='" . $movie_id . "' limit 1 ";
    $r = Pdo_an::db_fetch_row($sql);

    $movie_title = $r->title;


    $link = 'https://archive.4plebs.org/_/search/boards/pol.tv/text/%22'.urlencode($movie_title).'%22/';

    $content=' <h3 class="column_header">4Chan:</h3>
                        <div class="s_container smoched">
                            <div ><iframe src="' . $link. '"></iframe></div>
                            <div class="s_container_smoth">
                                <div style="text-align: center"></div>
                            </div>
                        </div>


    ';
///        <div class="column_inner_bottom fchan_btn" data_title = "%22'.urlencode($movie_title).'%22">
//                    <input class="blue_btn" type="button" dataid="pool" value="/pool/" > <input class="blue_btn" type="button" dataid="tv"  value="/tv/" > <input class="blue_btn" type="button" dataid="pool.tv"  value="All" >
//</div>





    $array = array('chandata'=>$content);
    echo json_encode($array);
}