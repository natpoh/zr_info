<?php
if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');


!class_exists('SimilarMovies') ? include ABSPATH . "analysis/include/similar_movies.php" : '';

function similar_movies($id='')
{
    if (!$id)
    {
        $id= $_GET['id'];
    }

    $data = SimilarMovies::get_movies($id);
    echo $data;

}




if (isset($_GET['id']))
{
    $id = intval($_GET['id']);
    [$title,$type] = SimilarMovies::get_title($id);
    $allow_types = array("Movie"=>'similar_shows', "TVseries"=>'similar_shows', "VideoGame"=>'similar_games');
    $stype = $allow_types[$type];
    if (!$stype)$stype='similar_shows';

    if (isset($_GET['test'])) {
        if (isset($_GET['debug'])) {global $debug; $debug=1;}

        similar_movies($id);
        return;
    }
    if (!function_exists('wp_custom_cache')) {
        require(ABSPATH . 'wp-content/themes/custom_twentysixteen/template/include/custom_cahe.php');
    }

    $cache = wp_custom_cache('p-'.$id.'_similar_movies_1', 'fastcache', 86400);
    $result =json_decode($cache,1);

            !class_exists('OptionData') ? include ABSPATH . "analysis/include/option.php" : '';
            $data =  OptionData::get_options('',$stype);
            $data =  str_replace('\\','',$data);

            $data =str_replace('$',$title,$data);


        if (!$result['content'])
        {
            $result['content']=	'<div class="in_fl_cnt"><p style="margin: 15px auto;">Similar recommendations will be imported soon...</p><p>In the meantime try exploring these links:</p>'.$data.'</div>';
        }
        else
        {
            $result['data']=$data;
        }

        $cache = json_encode($result,JSON_INVALID_UTF8_IGNORE );




    echo $cache;


}

