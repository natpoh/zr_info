<?php
error_reporting(E_ERROR);
if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');

//Curl
!class_exists('GETCURL') ? include ABSPATH . "analysis/include/get_curl.php" : '';
//DB config
!defined('DB_HOST_AN') ? include ABSPATH . 'analysis/db_config.php' : '';
//Abstract DB
!class_exists('Pdoa') ? include ABSPATH . "analysis/include/Pdoa.php" : '';

class Global_zeitgeist{
private function to_block($val,$data){

    if ($val>$data['ratmax'])
    {
        $val = $val/10;
    }

    $title = $data['name'];
    if ($data['flag'])$flag ='<img style="width:40px;height:40px" src="' .WP_SITEURL.'/wp-content/themes/custom_twentysixteen/images/flags/4x3/'.$data['flag'].'.svg" />';

$result='<div class="gl_zr_block exlink" id="'.$data['link'].'"><p class="gl_zr_title">'.$flag.$title.'</p><div class="gl_rating">'.$val.'/'.$data['ratmax'].'</div></div>';
return $result;
}

    public  function get_title($id)
    {

        $sql = "SELECT * from data_movie_imdb where id =".intval($id);
        $r = Pdo_an::db_results_array($sql);
        $title = $r[0]['title'];
        $type = $r[0]['type'];
        return [urlencode($title),$type];

    }

public  function get_data($id)
{

    [$title,$type] = $this->get_title($id);

    $url = 'https://info.antiwoketomatoes.com/service/global_consensus.php?mid=' . $id;
    $result = GETCURL::getCurlCookie($url);

    return $result;

}

}


if (isset($_GET['id'])) {
    $movie_id = intval($_GET['id']);
    $Global_zeitgeist = new Global_zeitgeist();
    $result = $Global_zeitgeist->get_data($movie_id);
    echo ($result);

}