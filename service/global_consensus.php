<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");


if (!defined('ABSPATH')) {
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');
}
//DB config
!defined('DB_HOST_AN') ? include ABSPATH . 'analysis/db_config.php' : '';
//Abstract DB
!class_exists('Pdoa') ? include ABSPATH . "analysis/include/Pdoa.php" : '';


if (!defined('CRITIC_MATIC_PLUGIN_DIR')) {
    define('CRITIC_MATIC_PLUGIN_DIR', ABSPATH . 'wp-content/plugins/critic_matic/');
    require_once( CRITIC_MATIC_PLUGIN_DIR . 'critic_matic_ajax_inc.php' );
}




class Global_zeitgeist{
    private function to_block($val,$data){

        if ($val>$data['ratmax'])
        {
            $val = $val/10;
        }

        $title = $data['name'];
        if ($data['flag'])
        {
            if ($data['flag']=='mtcr'){
                $flag ='<img style="width:40px;height:40px" src="' .WP_SITEURL.'/wp-content/themes/custom_twentysixteen/images/metacritic-logo.svg" />';
            }
            else
            {
                $flag ='<img style="width:40px;height:40px" src="' .WP_SITEURL.'/wp-content/themes/custom_twentysixteen/images/flags/4x3/'.$data['flag'].'.svg" />';
            }

        }



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
        $cm = new CriticMatic();
        $si = $cm->get_si();


        $results = $si->get_images($id);

        [$title,$type] = $this->get_title($id);


        !class_exists('OptionData') ? include ABSPATH . "analysis/include/option.php" : '';
        $data =  OptionData::get_options('','global_zeitgeist');
        $data =  str_replace('\\','',$data);

        $data =str_replace('$',$title,$data);


        if (!$results)
        {
            $content= '<div class="in_fl_cnt"><p style="margin: 15px auto;">Global Reviews will be imported soon...</p><p>In the meantime try exploring these links:</p>'.$data.'</div>';
        }
        else
        {

            $content= '<details style="margin-top: 15px; width: 100%" class="trsprnt"><summary>Other sources</summary><div>'.$data.'</div></details>';
        }

        return ['result'=>$results,'other'=>$content];
    }

}


if (isset($_GET['mid'])) {
    $movie_id = intval($_GET['mid']);
    $Global_zeitgeist = new Global_zeitgeist();
    $result = $Global_zeitgeist->get_data($movie_id);

    print json_encode($result);

}


$p = 'D_23_2D0FS0-vbb';

if ($_GET['p'] != $p) {
    return;
}


$debug = false;
if ($_GET['debug']) {
    $debug = true;
}

$mid = isset($_GET['mid'])? (int)$_GET['mid']:0;

$cm = new CriticMatic();
$si = $cm->get_si();


$results = $si->get_images($mid, $debug);


print json_encode($results);