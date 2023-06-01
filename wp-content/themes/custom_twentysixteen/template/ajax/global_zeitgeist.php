<?php
error_reporting(E_ERROR);
if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');

////Curl
//!class_exists('GETCURL') ? include ABSPATH . "analysis/include/get_curl.php" : '';
////DB config
//!defined('DB_HOST_AN') ? include ABSPATH . 'analysis/db_config.php' : '';
////Abstract DB
//!class_exists('Pdoa') ? include ABSPATH . "analysis/include/Pdoa.php" : '';
//
//class Global_zeitgeist{
//private function to_block($val,$data){
//
//    if ($val>$data['ratmax'])
//    {
//        $val = $val/10;
//    }
//
//    $title = $data['name'];
//    if ($data['flag'])$flag ='<img style="width:40px;height:40px" src="' .WP_SITEURL.'/wp-content/themes/custom_twentysixteen/images/flags/4x3/'.$data['flag'].'.svg" />';
//
//$result='<div class="gl_zr_block exlink" id="'.$data['link'].'"><p class="gl_zr_title">'.$flag.$title.'</p><div class="gl_rating">'.$val.'/'.$data['ratmax'].'</div></div>';
//return $result;
//}
//
//    public  function get_title($id)
//    {
//
//        $sql = "SELECT * from data_movie_imdb where id =".intval($id);
//        $r = Pdo_an::db_results_array($sql);
//        $title = $r[0]['title'];
//        $type = $r[0]['type'];
//        return [urlencode($title),$type];
//
//    }
//
//public  function get_data($id)
//{
//    $rating_providers = ['kinop_rating'=>['name'=>'Kinopoisk','flag'=>'ru','desc'=>'','link'=>'kinop','ratmax'=>10],'douban_rating'=>['name'=>'Douban','flag'=>'cn','desc'=>'','link'=>'douban','ratmax'=>10]];
//
//    !class_exists('RWT_RATING') ? include ABSPATH . "wp-content/themes/custom_twentysixteen/template/include/movie_rating.php" : "";
//    $data = new RWT_RATING;
//    $rating = $data->rwt_total_rating($id);
//
//$content ='';
//    foreach ($rating_providers as $i=>$v)
//    {
//        if ($rating[$i])
//        {
//            $content.=  $this->to_block($rating[$i],$v);
//        }
//    }
//
//    [$title,$type] = $this->get_title($id);
//
//
//    !class_exists('OptionData') ? include ABSPATH . "analysis/include/option.php" : '';
//    $data =  OptionData::get_options('','global_zeitgeist');
//    $data =  str_replace('\\','',$data);
//
//    $data =str_replace('$',$title,$data);
//
//
//    if (!$content)
//    {
//        $content=	'<div class="in_fl_cnt"><p style="margin: 15px auto;">Global Reviews will be imported soon...</p><p>In the meantime try exploring these links:</p>'.$data.'</div>';
//    }
//    else
//    {
//
//        $content.=  '<details style="margin-top: 15px; width: 100%" class="trsprnt"><summary>Other sources</summary><div>'.$data.'</div></details>';
//    }
//
//    return $content;
//}
//
//}
//
//
//if (isset($_GET['id'])) {
//    $movie_id = intval($_GET['id']);
//    $Global_zeitgeist = new Global_zeitgeist();
//    $result = $Global_zeitgeist->get_data($movie_id);
//    print_r($result);
//
//}