<?php
set_time_limit(0);

if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');

//DB config
!defined('DB_HOST_AN') ? include ABSPATH . 'analysis/db_config.php' : '';
//Abstract DB
!class_exists('Pdoa') ? include ABSPATH . "analysis/include/Pdoa.php" : '';
//Curl
!class_exists('GETCURL') ? include ABSPATH . "analysis/include/get_curl.php" : '';

!class_exists('TMDB') ? include ABSPATH . "analysis/include/tmdb.php" : '';

class GETNEWMOVIES{

    public static function get_new_tv()
    {
        ///get last tranding tmdb

        $tranding = TMDB::get_tranding_tv();

        $array_rwt_id=[];
        if ($tranding)
        {
            $tranding = json_decode($tranding,1);
            foreach ($tranding['results'] as $i =>$v)
            {
                $id='';
                ///print_r($v);

                $title=$v['name'];
                $otitle = $v['original_name'];
                $tmdb_id = $v['id'];
                $movie_date =  $v['first_air_date'];
                //    echo '$tmdb_id='.$tmdb_id.'<br>';

                ////check enable movie
                $id = TMDB::get_id_from_tmdbid($tmdb_id,'TVSeries');
                 if (!$id)
                {
                    ///try find movie

                    $imdb_id = TMDB::find_imdbid_from_tmdbid($tmdb_id,'tvseries',$title,$otitle);

                    if (!$id) {
                        ////find on imdb site
                        echo 'try add movie ' . $title . ' ' . PHP_EOL;

                        $array_movie_id = TMDB::get_data($title, 'tv');

                        //first_air_date
                        //print_r($array_movie_id);
                        $i1=0;
                        foreach ($array_movie_id as $temp_id =>$data)
                        {
                            echo 'try find $temp_id '.$temp_id.PHP_EOL;
                           $tmdb_result =  TMDB::get_tmdbid_from_imdbid($temp_id);
                           if ($tmdb_result==$tmdb_id)
                           {
                               $imdb_id=$temp_id;
                               echo 'finded imdb_id = '.$imdb_id.PHP_EOL;
                               ////add movie
                               $addeded = TMDB::check_imdb_id($imdb_id,$tmdb_id);
                               if (!$addeded)
                               {
                                   ////add movie to database
                                   $array_movie =  TMDB::get_content_imdb($imdb_id);
                                   $add =  TMDB::addto_db_imdb($imdb_id, $array_movie,'','','tmdbtv');
                                   echo $imdb_id.' adedded <br>'.PHP_EOL;
                               }

                              break;
                           }
                            $i1++;

                           if ($i1>5)
                           {
                               echo 'not found '.PHP_EOL;
                               break;
                           }

                        }

                    }


                    if ($imdb_id)
                    {
                        $id = TMDB::get_id_from_imdbid($imdb_id);

                    }
                }
                if ($id)
                {
                    $array_rwt_id[$id]=1;
                }

            }

        }



        if ($array_rwt_id)
        {
            $result = json_encode($array_rwt_id);
            !class_exists('OptionData') ? include ABSPATH . "analysis/include/option.php" : '';
            OptionData::set_option(14,$result,'get_new_tv',1);

            echo 'updated';
        }

    }

    public static     function htmlspecialchars_decode($string,$style=ENT_QUOTES)
    {
        $translation = array_flip(get_html_translation_table(HTML_SPECIALCHARS,$style));
        if($style === ENT_QUOTES){ $translation['&#39;'] = '\''; }
        return strtr($string,$translation);
    }
    public static function get_fandango($starttime='')
    {

        if (!$starttime)
        {
            $starttime= time();
        }
        if (!class_exists('CriticParser')) {
            //Critic feeds
            if (!defined('CRITIC_MATIC_PLUGIN_DIR')) {
                define('CRITIC_MATIC_PLUGIN_DIR', ABSPATH . 'wp-content/plugins/critic_matic/');
            }
            if (!class_exists('CriticFront')) {
                require_once(CRITIC_MATIC_PLUGIN_DIR . 'critic_matic_ajax_inc.php');
                require_once( CRITIC_MATIC_PLUGIN_DIR . 'CriticParser.php' );
            }

        }

        $cp = New CriticParser;
        $content = $cp->get_webdriver('https://www.fandango.com/movies-in-theaters');



      //      $content = GETCURL::getCurlCookie('https://www.fandango.com/movies-in-theaters');

        global $debug;
        if ($debug)
        {
          echo $content;
        }

            $regv = '#poster-card--title\"\>([^<]+)\<#';

            if (preg_match_all($regv, $content, $mach)) {
                foreach ($mach[1] as $m) {

                    //$m = replace_movie_text($m);
                    $m =  self::htmlspecialchars_decode($m);
                    if (preg_match('#doublefeature([^\/]+)\/(.+)#', $m, $dm)) {

                        $array_int[self::replace_movie_text($dm[1])] = $dm[1];
                        $array_int[self::replace_movie_text($dm[2])] = $dm[2];
                    } else {

                        $array_int[self::replace_movie_text($m)] = $m;
                    }

                }
            }

        return $array_int;
    }

    public static function get_new_movies()
    {
        global $debug;
        $array_title = [];


        $starttime = time();
        $date = date('Y', $starttime);
        $date2 = date('Y', strtotime('-1 year', $starttime));
        $date_main = date('Y-m-d', strtotime('-2 year', $starttime));
        $array_movies=[];
        //echo $date.' '.$date2;

        $sql = "SELECT * FROM `data_movie_imdb` WHERE `release`  >=  '" . $date_main . "' and `type`= 'Movie' order by `release` desc ";
        //echo $sql;
        $rows = Pdo_an::db_results_array($sql);

        foreach ($rows as $r) {

                $id = $r['id'];
                $add_time = $r['release'];
                $title = $r['title'];

          $array_title[self::replace_movie_text($title)] = array($id, $add_time);

        }
        //print_r($array_title);

        $array_int =self::get_fandango();

        if ($debug)
        {
            !class_exists('TMDB') ? include ABSPATH . "analysis/include/tmdb.php" : '';
             TMDB::var_dump_table(['array_int',$array_int]);
        }

        $i=0;
        foreach ($array_int as $movie_decoded => $movie_name) {

            if ($array_title[$movie_decoded]) {
                $addtime = strtotime($array_title[$movie_decoded][1]);

                $pid = $array_title[$movie_decoded][0];

                $array_movies[$pid]=$addtime;

            }
            else
                {
                   // if ($i>2)break;

                    ////try add movie
                    $movie_date='';

                    if (strstr($movie_name,'('.$date.')'))
                    {
                        $movie_date =$date;
                    }
                    else if (strstr($movie_name,'('.$date2.')'))
                    {
                        $movie_date =$date2;
                    }

                    if ($debug) {
                        echo 'try add movie ' . $movie_name . ' ' . $movie_decoded .'<br>'. PHP_EOL;
                    }
                    $array_movie_id =TMDB::get_data($movie_name,'ft');

                    $coincide = self::check_movie_coincidence($array_movie_id,$movie_name,$movie_date);

                    if ($coincide)
                    {

                        $addeded = TMDB::check_imdb_id($coincide);
                        if (!$addeded)
                        {
                            ////add movie to database
                            $array_movie =  TMDB::get_content_imdb($coincide);

                            $country = $array_movie['country'];
                            if (!strstr($country,'India'))
                            {
                                $add =  TMDB::addto_db_imdb($coincide, $array_movie,'','','fandango');

                                if ($debug) {
                                    echo $coincide . ' adedded <br>' . PHP_EOL;
                                }
                            }
                            else
                            {
                                echo $coincide.' adedded country filtered <br>'.PHP_EOL;
                            }


                        }
                        else
                        {
                            echo  $coincide.' already adedded <br>'.PHP_EOL;
                        }



                        $sql = "SELECT `id`,`release` FROM `data_movie_imdb` WHERE `movie_id`  =  '" . intval($coincide) . "'";
                        //echo $sql;
                        $rows = Pdo_an::db_fetch_row($sql);

                        $addtime = $rows->release;
                        $id = $rows->id;
                        if ($addtime)
                        {
                            $array_movies[$id]=strtotime($addtime);
                        }
                    }
                    $i++;
                }
        }

        if ($debug)
        {

            TMDB::var_dump_table(['array_movies',$array_movies]);
        }
        if ($array_movies)
        {

            $result = json_encode($array_movies);

            !class_exists('OptionData') ? include ABSPATH . "analysis/include/option.php" : '';
            OptionData::set_option(13,$result,'get_new_movies',1);

            echo 'updated';
        }

    }



    public static function check_movie_coincidence($array_movie_id,$movie_name,$movie_date='')
    {
        $maxcount =5;
        $name =  self::replace_movie_text($movie_name);
        $i=0;
        if (is_array($array_movie_id))
        {
         foreach ($array_movie_id as $imdb_id=>$movie_object)
         {

             $data = TMDB::get_content_imdb($imdb_id,'','');
             $reg_v = '#([0-9]{4})#';
             $imdb_name =  self::replace_movie_text($data['title']);
             if (preg_match($reg_v, $data['datePublished'], $mach)) {
                 $imdb_year = $mach[1];
             }

            if (($movie_date && $imdb_year) && $imdb_year==$movie_date)
            {
              if ($imdb_name == $name)
              {
                  return $imdb_id;
              }

            }
            else if ($imdb_name == $name)
                {
                    return $imdb_id;
                }
            else {
                echo 'not mach: '.$imdb_name.'!='.$name.'<br>'.PHP_EOL;
         }
             $i++;
            if ($i>$maxcount)break;
         }

        }

    }


    public static function replace_movie_text($m)
    {

        $year = date('Y', time());
        $y = '#(\(' . $year . '\))#';
        $m = preg_replace($y, '', $m);

        $y = '#(' . $year . ')#';
        $m = preg_replace($y, '', $m);
        $y = '#(' . ($year-1) . ')#';
        $m = preg_replace($y, '', $m);
        $y = '#(' . ($year-2) . ')#';
        $m = preg_replace($y, '', $m);

        $m = trim($m);
        $m = strtolower($m);
        $m = preg_replace('/[^a-z\d]/ui', '', $m);


        $m = str_replace(' ', '', $m);
        $m = str_replace(':', '', $m);
        $m = str_replace(',', '', $m);


        return $m;
    }


}

