<?php

error_reporting('E_ALL');
ini_set('display_errors', 'On');




if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');



//Curl
!class_exists('GETCURL') ? include ABSPATH . "analysis/include/get_curl.php" : '';

!function_exists('wp_custom_cache') ? include ABSPATH . "wp-content/themes/custom_twentysixteen/template/include/custom_cahe.php" : '';

!class_exists('RWT_RATING') ? include ABSPATH . "wp-content/themes/custom_twentysixteen/template/include/movie_rating.php" : '';

!class_exists('CreateTsumbs') ? include ABSPATH . "analysis/include/create_tsumbs.php" : '';

global $pdo;
if (!$pdo)
{
    include(ABSPATH.'wp-content/themes/custom_twentysixteen/template/include/custom_connect.php');
}


class TV_Scroll {

    private static function prepare_movies($id,$content_result,$slug = '',$time =0)
    {

        if ($content_result['result'][$id])
        {
            return  $content_result;
        }

        global $cfront;

        try {
            $array_tsumb = CreateTsumbs::get_poster_tsumb_fast($id, array([220, 330], [440, 660]));
        } catch (Exception $ex) {

            ///  var_dump($ex);
            // return null;
        }
        $sql = "select * from data_movie_imdb where id = " . $id . " limit 1";
        $rows = Pdo_an::db_fetch_row($sql);

        $post_name = $rows->post_name;
        $title = $rows->title;
        $type = $rows->type;
        $release = strtotime($rows->release);

        if (!$slug)$slug=strtolower($type);

        if ($slug=='movie')$slug='movies';

            if (!$post_name) {
                if (!$cfront) {
                    if (!defined('CRITIC_MATIC_PLUGIN_DIR')) {
                        define('CRITIC_MATIC_PLUGIN_DIR', ABSPATH . 'wp-content/plugins/critic_matic/');
                    }

                    if (!class_exists('CriticFront')) {
                        require_once(CRITIC_MATIC_PLUGIN_DIR . 'critic_matic_ajax_inc.php');
                    }
                    $cfront = new CriticFront();
                }
                if ($cfront) {
                    $post_name = $cfront->get_or_create_ma_post_name($id);
                }
            }

            if ($release>$time)
            {
                $content_result['result'][$rows->id] = array(
                    'link' => '/' . $slug . '/' . $post_name,
                    'title' => $title,
                    'genre' => $rows->genre,
                    'poster_link_small' => $array_tsumb[0],
                    'poster_link_big' => $array_tsumb[1],
                    'type' => $slug
                );
            }




        return $content_result;
    }

    public static function show_scroll($type='TVSeries',$data='',$custom_data='') {
        $content_result=[];
        $array_movies_dop = [];
        $array_movies = [];

          if ($type== 'compilation' || $custom_data) {

              global $cfront;

              if (!$cfront) {
                  if (!defined('CRITIC_MATIC_PLUGIN_DIR')) {
                      define('CRITIC_MATIC_PLUGIN_DIR', ABSPATH . 'wp-content/plugins/critic_matic/');
                  }

                  if (!class_exists('CriticFront')) {
                      require_once(CRITIC_MATIC_PLUGIN_DIR . 'critic_matic_ajax_inc.php');
                  }                  
              }

              if ($custom_data)
              {
                  $url =    $custom_data;
              }
              else
              {
                  if ($data) $q="SELECT * FROM `meta_compilation_links` WHERE `enable` = 1 and id =".intval($data)." LIMIT 1";
                  $ru=Pdo_an::db_fetch_row($q);
                  $url = $ru->url;

              }



              $last_req = $_SERVER['REQUEST_URI'];
              $_SERVER['REQUEST_URI'] = $url;
              
              $search_front = new CriticFront();
              $search_front->init_search_filters();              
              $array_data= $search_front->find_results(0,array(),false, true);
              
              //var_dump($array_data["movies"]["list"]);

           /// var_dump($array_data["movies"] ["list"]);
              if ($array_data["movies"] ["list"])
              {
                  foreach ($array_data["movies"] ["list"] as $i=>$v)
                      {

                          $array_movies[ $v->id] = strtotime($v->release);
                      }
              }
              $_SERVER['REQUEST_URI']=$last_req;

                  foreach ($array_movies as $id => $enable) {

                      $content_result = self::prepare_movies($id, $content_result);
                      //else echo $imdb_id.'found<br>';


                      if (count($content_result['result']) >= 20) {
                          break;
                      }
                  }

          }
          else {

              if ($type == 'TVSeries') {
                  $sql = "SELECT * FROM `options` where id = 14";
                  $rows = Pdo_an::db_fetch_row($sql);

                  $array_movies = $rows->val;
                  if ($array_movies) {
                      $array_movies = json_decode($array_movies, 1);
                  }


              }

              $starttime = time();
              $date_current = date('Y-m-d', $starttime);
              $date_main = date('Y-m-d', strtotime('-6 month', $starttime));
              $sql = "SELECT * FROM `data_movie_imdb` WHERE `release`  >=  '" . $date_main . "' and `release`  <=  '" . $date_current . "' and `type`= '" . $type . "' order by `rating` desc , `release` desc LIMIT 30 ";

              $rows = Pdo_an::db_results_array($sql);
              foreach ($rows as $r) {
                  $movie_id = $r['id'];
                  $array_movies_dop[$movie_id] = strtotime($r['release']);
              }





              if (is_array($array_movies)) {
                  arsort($array_movies);
                  $i = 0;

                  $cfront = '';
                  foreach ($array_movies as $id => $enable) {


                      $content_result = self::prepare_movies($id, $content_result,'',time()-180);
                      //else echo $imdb_id.'found<br>';


                      if (count($content_result['result']) >= 20) {
                          break;
                      }
                  }
              }

              if (count($content_result['result']) < 20 ) {
                  arsort($array_movies_dop);
                  foreach ($array_movies_dop as $id => $enable) {


                      $content_result = self::prepare_movies($id, $content_result);
                      //else echo $imdb_id.'found<br>';


                      if (count($content_result['result']) >= 20) {
                          break;
                      }
                  }
              }
          }












            if (count($content_result['result']) ) {

                if ($type== 'TVSeries') {
                    $link = '/search/type_tv';
                    $title = 'Load more Streaming';
                }
                else if ($type== 'VideoGame') {
                    $link = '/search/tab_games';
                    $title = 'Load more Games';
                }
                else if ($type== 'compilation') {
                    $link = $url;
                    $title = 'Load more';
                }



                $content_result['result'][] = array('link' => $link, 'title' => $title, 'genre' => 'load_more', 'poster_link_small' => '', 'poster_link_big' => '', 'content_pro' => '');
                $content_result['count'] = count($content_result['result']);
                return $content_result;
            }

        else  {
            $content_result['count'] = 0;
            $content_result['message'] = 'no result';
            return $content_result;
        }
    }

}

function tv_scroll($type='TVSeries',$data='',$custom_data='') {
    global $video_template;
    $content_result = TV_Scroll::show_scroll($type,$data,$custom_data);
    include(ABSPATH . 'wp-content/themes/custom_twentysixteen/template/video_item_template.php');    
    $content_result['tmpl'] = $video_template;

    if ($content_result['result']) {
        $RWT_RATING = new RWT_RATING;
        $content_result['rating'] = $RWT_RATING->get_rating_data($content_result['result']);
    }

    $content_string = json_encode($content_result);

    return $content_string;
}

//echo tv_scroll();
//return;

if (isset($_GET['type']))
{
    if ($_GET['type'] =='games')
    {
        $cache = tv_scroll('VideoGame');
    }
    if ($_GET['type'] =='games')
    {
        $cache = tv_scroll('VideoGame');
    }
    if ($_GET['type'] =='compilation')
    {
        $cache = tv_scroll('compilation',intval($_GET['id']));
    }
}
else {

//    if (function_exists('wp_custom_cache')) {
//
//        $cache = wp_custom_cache('tv_scroll', 'fastcache', 3600);
//    } else {
    $year = date('Y',time());
    $last_year = $year -1;
    $custom_data ='/search/sort_crwt-desc/release_'.$last_year.'-'.$year.'/type_tv';
        $cache = tv_scroll('compilation','',$custom_data);
    //}
}


echo $cache;
