<?php

if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');
//DB config
!defined('DB_HOST_AN') ? include ABSPATH . 'analysis/db_config.php' : '';
//Abstract DB
!class_exists('Pdoa') ? include ABSPATH . "analysis/include/Pdoa.php" : '';

class PgRatingCalculate {

     static $ma =null;
     static $rwt_array=[];
     static $array_convert=[];

    public static function getMa()
    {
        if (!self::$ma)
        {
            if (!defined('CRITIC_MATIC_PLUGIN_DIR')) {
                define('CRITIC_MATIC_PLUGIN_DIR', ABSPATH . 'wp-content/plugins/critic_matic/');
            }
            if (!class_exists('CriticFront')) {
                require_once(CRITIC_MATIC_PLUGIN_DIR . 'critic_matic_ajax_inc.php');
            }
            $cfront = new CriticFront();

            self::$ma = $cfront->get_ma();
        }

        return self::$ma;
    }


    public  function ckeck_cms_pg_rating($id)
    {
        $imdb_id = self::get_imdb_id_from_id($id);
        $array_family = self::get_family_rating_in_movie($imdb_id);
        $cur_total =  $array_family['rwt_pg_result'];

        $last_updated = $array_family['cms_date'];
        if ($last_updated < time()-86400)
        {
           !class_exists('PgRating') ? include ABSPATH . "analysis/include/pg_rating.php" : '';
           PgRating::update_pg_rating_cms($imdb_id );




           $total =  self::CalculateRating($imdb_id,$id);//ckeck_cms_pg_rating
           if ($cur_total!=$total){return(2);}

            return 1;
        }
        return 0;



    }


        public  function ckeck_imdb_pg_rating($id,$post_type)
    {


        $imdb_id = self::get_imdb_id_from_id($id);

        $array_family = self::get_family_rating_in_movie($imdb_id);
        if ($post_type=='last_imdb_pg_update') {
            $last_imdb_updated = $array_family['imdb_date'];
        }
        else if ($post_type=='last_pg_update')
        {
            $last_imdb_updated = $array_family['last_update'];
        }
        $cur_total =  $array_family['rwt_pg_result'];

       /// echo date('h: i d m Y',$last_imdb_updated);

        if ($last_imdb_updated < time()-86400)
        {
            !class_exists('PgRating') ? include ABSPATH . "analysis/include/pg_rating.php" : '';
          //update imdb data




            if ($post_type=='last_imdb_pg_update')
            {
                PgRating::update_pg_rating_imdb($imdb_id);
            }

           // else     if ($post_type=='last_pg_update')


           $total =  self::CalculateRating($imdb_id,$id);///ckeck_imdb_pg_rating


           if ($cur_total!=$total){return(2);}

           return 1;
        }
        return 0;


    }
    public static function get_rating_from_bd($mid, $type) {

        if ($type) {

            $array = [];

            $q = "SELECT `link` FROM `cache_rating_links` WHERE `mid` ={$mid} and  `type` = '{$type}'";
            $r = Pdo_an::db_results_array($q);
            if ($r[0]['link']) {
                $array[$type] = $r[0]['link'];
                return $array;
            }
        }
//        else
//        {
//
//            $q="SELECT `link`,  `type`  FROM `cache_rating_links` WHERE `mid` ={$mid}";
//            $r =Pdo_an::db_results_array($q);
//            foreach ($r as $row)
//            {
//                $array[$row['type']]  =$row['link'];
//            }
//
//        }
    }

    public static function get_curl_rating($mid, $type) {



        !class_exists('OptionData') ? include ABSPATH . "analysis/include/option.php" : '';
        $array_cid = OptionData::get_options('','get_curl_rating');

        if (!$array_cid)
        {
            $array_cid = array('thenumbers' => 1, 'rotten_mv' => 20, 'rotten_tv' => 21, 'douban' => 22, 'metacritic' => 23, 'kinop' => 24, 'animelist' => 27, 'moviemeter' => 38, 'eiga' => 35);
        }
        else
        {
            $array_cid = json_decode($array_cid,1);
        }


        if ($type == 'rt') {

            $array_type = array('Movie' => 'mv', 'TVSeries' => 'tv', 'TVEpisode' => 'tv');

            !class_exists('TMDB') ? include ABSPATH . "analysis/include/tmdb.php" : '';
            $movie_type = TMDB::get_movie_type_from_id($mid);

            $type = 'rotten_' . $array_type[$movie_type];
        }

        //Curl
        !class_exists('GETCURL') ? include ABSPATH . "analysis/include/get_curl.php" : '';

        $link = 'https://info.antiwoketomatoes.com/wp-content/plugins/movies_links/cron/get_url_by_mid.php?p=8ggD_23_2D0DSF-F&cid=' . $array_cid[$type] . '&mid=' . $mid;

        $result = GETCURL::getCurlCookie($link);
        return $result;
    }

    private static function set_rating_to_bd($mid, $rating_type, $link) {


        if (!self::get_rating_from_bd($mid, $rating_type)) {
            $sql = "INSERT INTO `cache_rating_links` (`id`, `mid`, `type`, `link`) VALUES (NULL,?,?,?)";
            Pdo_an::db_results_array($sql, [$mid, $rating_type, $link]);
        }
    }
    public static function create_search_url($id,$type)
    {

        $title = self::get_data_in_movie('title', '', $id);
        $title = urlencode($title);

        if ($type=='rt')
        {
            $link = 'https://www.rottentomatoes.com/search?search='.$title;
        }

      return $link;
    }

    public static function get_rating_url($mid, $rating_type = '') {

        $arating = self::get_rating_from_bd($mid, $rating_type);
        if ($arating[$rating_type]) {
            return ['url' => $arating[$rating_type]];
        }
        else

        {
            if ($rating_type == 'imdb') {
                !class_exists('TMDB') ? include ABSPATH . "analysis/include/tmdb.php" : '';
                $movie_id = TMDB::get_imdb_id_from_id($mid);
                $final_value = sprintf('%07d', $movie_id);
                $url = "https://www.imdb.com/title/tt" . $final_value . '/reviews';
                self::set_rating_to_bd($mid, $rating_type, $url);

                return ['url' => $url];
            } else if ($rating_type == 'tmdb') {

                $array_type = array('Movie' => 'movie', 'TVSeries' => 'tv', 'TVEpisode' => 'tv');

                !class_exists('TMDB') ? include ABSPATH . "analysis/include/tmdb.php" : '';
                $movie_type = TMDB::get_movie_type_from_id($mid);
                $tvtype = $array_type[$movie_type];

                $tmdbid = TMDB::get_tmdbid_from_id($mid);

                $url = 'https://www.themoviedb.org/' . $tvtype . '/' . $tmdbid;

                self::set_rating_to_bd($mid, $rating_type, $url);
                return ['url' => $url];
            } else {

                $result = self::get_curl_rating($mid, $rating_type);
                $link = self::prepare_resuts($rating_type, $result);
                if($link)self::set_rating_to_bd($mid, $rating_type, $link);
                if (!$link){$link = self::create_search_url($mid,$rating_type);}
                return ['url' => $link];
            }
        }
    }

    private static function prepare_resuts($rating_type, $result) {
        if ($result) {
            $result = json_decode($result);
            $link = $result->link;

            if ($link) {

                if ($rating_type == 'kinop') {
                    $str = substr($link, strpos($link, '/films/') + 7);
                    $link = 'https://www-kinopoisk-ru.translate.goog/film/' . $str . '/reviews/?_x_tr_sl=ru&_x_tr_tl=en&_x_tr_hl=en';
                } else if ($rating_type == 'douban') {
                    $link = str_replace('movie.douban.com/', 'movie-douban-com.translate.goog/', $link);
                    $link = $link . '?_x_tr_sl=zh-CN&_x_tr_tl=en&_x_tr_hl=en';
                }

                return $link;
            }
        }
    }

    public static function rwt_total_rating($id) {

        $data = [];

        !class_exists('OptionData') ? include ABSPATH . "analysis/include/option.php" : '';
        $value = OptionData::get_options('', 'movies_raiting_weight_convert');
        if ($value) {
            $value = json_decode($value, 1);
            $array_convert = $value;
        }


        $rating_weight_str = OptionData::get_options('','movies_raiting_weight');
        $rating_weight_ob = json_decode($rating_weight_str,1);
        $rating_weight = $rating_weight_ob['rwt'];

        //var_dump($rating_weight);

        $sql = "SELECT * FROM `data_movie_erating`  where `movie_id` = " . $id . " limit 1";
        //echo $sql;
        $r = Pdo_an::db_results_array($sql);
        if ($r) {
            foreach (  $r[0] as $i=>$v)
            {

                if ($rating_weight[$i]>0 || $i=='total_rating' || $i=='rt_gap')
                {
                    //echo $i.' : ';
                   // echo $rating_weight[$i].'<br>';


                    if ($v)
                    {
                    if ($array_convert[$i])
                    {
                        $v = $v* $array_convert[$i];

                    }
                        if ($i!='rt_rating' &&  $i!='rt_aurating' &&  $i!='rt_gap')
                        {
                            $v= $v/20;

                        }


//                        if ($i=='audience_rating' || $i=='total_rating')
//                        {
//                            $v= round($v/2,1);
//                        }

                        $data[$i]=$v;
                    }


                }
            }
           }
       /// var_dump($data);
        ksort($data);

        return $data;
    }

    public static function sync_update($data,$id,$db,$type='update',$sync=true,$sync_client=false){

        if (!$sync_client)
        {
             if (DB_SYNC_MODE==2)
             {
                 $sync_client=1;
             }
        }
        global $debug;
        if ($debug){

            TMDB::var_dump_table(['sync_update',$data, $id, $db, $type, $sync]);
        }
        $ma = self::getMa();


        if ($type=='update')
        {
            $ma->sync_update_data($data, $id, $db, $sync, 10);

        }
        else  if ($type=='insert')
        {
            $ma->sync_insert_data($data, $db, $sync_client,$sync , 10);
        }

    }

    public static function add_movie_rating($id, $rwt_array_last = '', $debug = '', $update = 1,$check_fields=0,$sync=1) {



        ///get option
        $rating_array =[];
        $array_convert=[];
        $force_sync=0;

        if (!self::$rwt_array)
        {
            !class_exists('OptionData') ? include ABSPATH . "analysis/include/option.php" : '';
            $value = OptionData::get_options('', 'movies_raiting_weight');

            if ($value) {
                $rwt_array_data = json_decode($value,1);
                $rwt_array = $rwt_array_data['rwt'];
            }
            self::$rwt_array= $rwt_array;
        }
        else
        {
            $rwt_array=self::$rwt_array;

        }

        if (!self::$array_convert) {
            !class_exists('OptionData') ? include ABSPATH . "analysis/include/option.php" : '';
            $value = OptionData::get_options('', 'movies_raiting_weight_convert');



            if ($value) {
                $value = json_decode($value, 1);
                $array_convert = $value;

                self::$array_convert =$array_convert;
            }

        }
        else
        {
            $array_convert =  self::$array_convert;
        }


        $sql = "SELECT * FROM `data_movie_erating` where `movie_id` = " . $id;
        $row_movie_erating= Pdo_an::db_results_array($sql);
        $main_data_ext  = $row_movie_erating[0];

        if ($main_data_ext)
        {
            $pos_id = $main_data_ext['id'];
        }

        $data_current_array=[];
        if ($check_fields && $pos_id) {



            if (!$main_data_ext['imdb_rating']) {
                $imdb = self::get_data_in_movie('rating', '', $id);

                if ($imdb) {
                    $main_data_ext['imdb_rating'] = $imdb * 10;

                    $data_current_array['imdb_rating'] = $main_data_ext['imdb_rating'];


                }


            }
            if (!$main_data_ext['title']) {
                $title = self::get_data_in_movie('title', '', $id);
                if ($title)
                {
                    $data_current_array['title'] = $title;
                }

            }
            $aud_array = self::get_audience_rating_in_movie($id);

            foreach  ($main_data_ext as $r_name=> $r_value)
                {
                    if (strstr($r_name,'audience_'))
                    {
                        $input_row = substr($r_name,9);



                        if ($input_row=='count' && $aud_array[$input_row] != $r_value )
                        {
                            $data_current_array[$r_name] = $aud_array[$input_row];
                            $data_current_array['audience_date'] =time();
                        }


                        else if ($aud_array[$input_row]* 20 != $r_value )
                            {
                                $data_current_array[$r_name] = $aud_array[$input_row]* 20;
                                $data_current_array['audience_date'] =time();
                            }

                    }
                /// `vote`, `rating`, `affirmative`, `god`, `hollywood`, `lgbtq`, `misandry`, `patriotism`, `count`,
//`audience_patriotism`, `audience_misandry`, `audience_lgbtq`, `audience_hollywood`, `audience_god`, `audience_affirmative`, `audience_rating_five`, `audience_vote`, `audience_rating`, `audience_count`

                }


            if (!$main_data_ext['audience_rating']) {


                $aud = $aud_array['rating'];
                if ($aud) {
                    $main_data_ext['audience_rating'] = $aud * 20;

                    $data_current_array['audience_rating'] = $main_data_ext['audience_rating'];
                    $data_current_array['audience_date'] =time();
                }
            }

            if ($data_current_array)

            {

                if ($pos_id)
                {
                    self::sync_update($data_current_array,$pos_id,'data_movie_erating','update',0);
                    $force_sync =1;
                }



            }


        }



        $array_db = [];
        foreach ($rwt_array as $i=>$v)
        {
            if (!$array_convert[$i]){
                $array_convert[$i]=1;
            }

            if ($main_data_ext[$i])
            {
                $array_db[$i]=$main_data_ext[$i];
            }

        }

        if ($debug)
            self::debug_table('s');

        if ($debug)
            self::debug_table('ZR Rating');

        $count = count($array_db);

        $array_converted = [];
        foreach ($array_convert as $key => $data) {
            if ($array_db[$key]) {
                $rt = $array_db[$key] * $data;
                $array_converted[$key] = $rt;
                if ($debug) {
                    $comment_converted .= $key . ' : ' . $array_db[$key] . ' * ' . $data . ' = ' . $rt . '<br>';
                }
            } else {
                unset($array_convert[$key]);
                unset($rwt_array[$key]);
            }
        }

        if ($debug)
            self::debug_table('Get the initial data rating', '', 'gray');
        if ($debug)
            self::debug_table('Array rating', $array_db);
        if ($debug)
            self::debug_table('Convert all data into the same format 5 points', '', 'gray');
        if ($debug)
            self::debug_table('Array convert', $array_convert, 'red');



        if ($debug)
            self::debug_table('We get an intermediate result', $comment_converted);
        if ($debug) {
            foreach ($array_converted as $key => $data) {

                self::debug_table('Converted ' . $key . ': ', $data, 'green');
            }
        }

        if ($debug)
            self::debug_table('Calculate the proportion of each rating using the ZR correction coefficients', '', 'gray');
        if ($debug)
            self::debug_table('Array ratings weight', $rwt_array, 'red');

        $total_proportion = 0;
        $comment_converted = '';

        foreach ($rwt_array as $key => $value) {
            if ($debug) {
                $comment_converted .= ' + ' . $value;
            }
            $total_proportion += $value;
        }
        if ($comment_converted) {
            $comment_converted = substr($comment_converted, 3) . ' = ' . $total_proportion;
        }
        if ($debug)
            self::debug_table('Take the amount of all coefficients', $comment_converted, 'gray');

        if (!$total_proportion)
            $total_proportion = 1;
        $array_proportion = [];

        $comment_converted = '';
        foreach ($rwt_array as $key => $value) {

            if ($debug) {
                $comment_converted .= $key . ' : ' . $value . ' / ' . $total_proportion . ' = ' . round($value / $total_proportion, 2) . '<br>';
            }
            $array_proportion[$key] = $value / $total_proportion;
        }
        if ($debug)
            self::debug_table('And divide the sum for each coefficient: ', $comment_converted, 'gray');

        $comment_converted = '';
        $comment_converted_summ = '';
        $total_rating = 0;
        foreach ($array_converted as $key => $data) {
            if ($debug) {
                $comment_converted .= $key . ' : ' . $data . ' * ' . round($array_proportion[$key], 2) . ' = ' . round($data * $array_proportion[$key], 2) . '<br>';
            }
            $rt = $data * $array_proportion[$key];
            $comment_converted_summ .= ' + ' . round($rt, 2);
            $total_rating += $rt;
        }

        if ($total_rating) {
            // $total_rating = ($total_rating)/$count;
            $total_rating = round($total_rating, 0);
        }

        $converted_rating = round($total_rating/20,1);


        if ($debug) {

            self::debug_table('Multiply the rating data on the weight coefficient: ', $comment_converted);

            if ($comment_converted_summ) {
                $comment_converted_summ = substr($comment_converted_summ, 3) . '  = ' . $total_rating;
            }
            self::debug_table('Add them to each other: ', $comment_converted_summ);
            self::debug_table('Total result: ', $total_rating.' / 20 = '.$converted_rating);

            self::debug_table('Total ZR Rating: ', $converted_rating, 'green');
        }

        if ($debug)
            self::debug_table('e'); ///end of table

        if ($update || $force_sync)
        {
            if (!$pos_id && ($sync || DB_SYNC_MODE==1))
            {
                //add
                if ($total_rating>0) {
                    $data_current_array['total_rating'] = $total_rating;


                    $data_current_array['movie_id'] = $id;
                    $data_current_array['date'] = time();
                    $data_current_array['last_upd'] = time();

                    self::sync_update($data_current_array, '', 'data_movie_erating', 'insert', 1);
                }


               // if (!$debug) echo 'add<br>';
            }
            else if ($total_rating!=$main_data_ext['total_rating'] || $force_sync)
            {
                //if (!$debug) echo 'update '.$total_rating.'!=' .$main_data_ext['total_rating'].' f='.$force_sync.'<br>';
                $data_current_array =['total_rating'=>$total_rating,'last_upd'=>time()];
                self::sync_update($data_current_array,$pos_id,'data_movie_erating','update',$sync);
            }




        }




        return $converted_rating;
    }

    public static function rating_to_comment($imdb, $imdbdesc, $maxrating = '') {


        $imdb_rating_colors = array("None" => 'gray', "Mild" => 'yelow', "Moderate" => 'orange', "Severe" => 'red',
            "0" => 'gray', "1" => 'green', "2" => 'yelow', "3" => 'orange', "4" => 'red', "5" => 'red');

        !class_exists('PgRating') ? include ABSPATH . "analysis/include/pg_rating.php" : '';

        $cms_rating_plus = PgRating::rating_cms_array(3);

        $content = '';

        if (is_array($imdbdesc)) {
            $imdbdesc_array = $imdbdesc;
        } else if ($imdbdesc) {
            $imdbdesc_array = json_decode($imdbdesc, 1);
        }

        if ($imdb) {

            if (!is_array($imdb)) {
                $imdb = json_decode($imdb, 1);
            }


            if ($maxrating) {
                $imdb_result = self::max_rating($imdb);
            } else {
                $imdb_result = $imdb;
            }

            if ($imdb_result) {

                foreach ($imdb_result as $type => $val) {



                    $content_comment = '';

                    if (is_array($imdbdesc_array[$type])) {
                        foreach ($imdbdesc_array[$type] as $comment) {
                            $content_comment .= '<p>' . $comment . '</p>';
                        }
                    } else if ($imdbdesc_array[$type]) {

                        $content_comment .= '<p>' . $imdbdesc_array[$type] . '</p>';
                    }

                    $color = $imdb_rating_colors[$val];
                    if (!$color)
                        $color = '';
                    if ($cms_rating_plus[$type]) {
                        $color = '';
                    }

                    $tname = ucfirst($type);
                    $tname = str_replace('_', ' ', $tname);
                    if (is_numeric($val)) {
                        $val = $val . '/5';
                    }
                    if (($val == 0 || $val == 'None') && !$content_comment) {
                        
                    } else {
                        $content .= self::debug_table($tname . '<br><span class="rating_row_verdict ' . $color . '">' . $val . '</span>', $content_comment);
                    }
                }
            }
        }
        return $content;
    }

    public static function get_movie_desc($id) {
        $content = self::debug_table('s');

        $movie_type = self::get_data_in_movie('type', '',$id);


        $imdb_id = self::get_imdb_id_from_id($id);
        $array_family = self::get_family_rating_in_movie('',$id);

        $array_rwt_rating_type = array("message" => 1, "nudity" => 1, "violence" => 1, "language" => 1, "drugs" => 1, "other" => 1);

        $croudsurce = self::get_family_rating_croud_in_movie($id, $array_rwt_rating_type, 1);

        if ($array_family['certification']) {
            $content .= self::debug_table('MPAA Certification', $array_family['certification']);
        }
        else
        {
            $content .= self::debug_table('MPAA Certification', 'No MPAA rating found yet.  <a href="#" data-value="'.$id.'" class="empty_ff_rating empty_ff_popup_rating">Add Family Friendly Rating?</a>');
        }


        $pg_cert = $array_family['certification_countries'];
        if ($pg_cert)
        {
        $cont_sert = '';
            $pg_cert_array = json_decode($pg_cert);

            foreach ($pg_cert_array as $country => $pg) {

                $cont_sert.='<span class="small_block">'.$country.' : ';
                $cont_sert.=implode(',',$pg).'</span>';

            }
            $content .= self::debug_table('Other Certification', $cont_sert);
        }

        if ($croudsurce['imdb_rating']) {

            $content .= self::debug_table('<h3>ZR Crowdsource</h3>');
            $content .= self::rating_to_comment($croudsurce['imdb_rating'], $croudsurce['imdb_rating_desc']);
        }


        if ($imdb_id) {

            //get last updated

            $last_imdb_updated  = $array_family['imdb_date'];

            $last_imdb_updated_string = date('Y-m-d',$last_imdb_updated);
            $update_link='';
            if ($last_imdb_updated < time()-86400)
            {
                $update_link = '<a href="#" id="last_imdb_pg_update" data-value="'.$id.'" class="update_data">Update data</a>';
            }

            $final_value = sprintf('%07d', $imdb_id);
            $url = "https://www.imdb.com/title/tt" . $final_value . '/parentalguide';

            $content .= self::debug_table('<h3>IMDb Rating</h3><a target="_blank" href="' . $url . '">' . $url . '</a><br><p class="last_updated_desc">last updated: '.$last_imdb_updated_string.$update_link.'</p>');
        }
        if ($array_family['imdb_rating']) {
            $content .= self::rating_to_comment($array_family['imdb_rating'], $array_family['imdb_rating_desc'], 1);
        }

        if ($movie_type!='VideoGame') {

            $last_cm_updated = $array_family['cms_date'];
            if ($last_cm_updated) {
                $last_cm_updated_string = date('Y-m-d', $last_cm_updated);
            } else {
                $last_cm_updated_string = 'never';
            }

            $update_link = '';
            if ($last_cm_updated < time()-86400)
            {
                $update_link = '<a href="#" id="last_cms_pg_update" data-value="' . $id . '" class="update_data">Update data</a>';
            }


            if ($array_family['cms_rating']) {

                $cms_extlink ='<a target="_blank" href="' . $array_family['cms_link'] . '">' . $array_family['cms_link'].'</a>';

            } else {

                    !class_exists('PgRating') ? include ABSPATH . "analysis/include/pg_rating.php" : '';
                    $cmslink = PgRating::get_cms_link($id);

                if ($cmslink) {
                    $cms_extlink = '<a target="_blank" href="' . $cmslink . '">https://www.commonsensemedia.org</a>';

                }
            }
             $content .= self::debug_table('<h3>Commonsensemedia Rating</h3>' . $cms_extlink . '<br><p class="last_updated_desc">last updated: ' . $last_cm_updated_string . $update_link . '</p>');

             $content .= self::rating_to_comment($array_family['cms_rating'], $array_family['cms_rating_desk']);



            if ($array_family['dove_rating']) {
                $content .= self::debug_table('<h3>Dove Rating</h3><a target="_blank" href="' . $array_family['dove_link'] . '">' . $array_family['dove_link'] . '</a>');
                $content .= self::rating_to_comment($array_family['dove_rating'], $array_family['dove_rating_desc']);
            }
        }



        $content .= self::debug_table('e');

        return $array_family;
    }

    public function debug_table($a = '', $b = '', $color = '') {
        $color_td = '';
        if ($color) {
            $color_td = ' style="color:' . $color . '" ';
        }

        if ($a == 's') {
            echo '<table style="table-layout: auto;">';
            return;
        }
        if ($a == 'e') {
            echo '</table>';
            return;
        }

        if (!$b) {
            echo '<tr ' . $color_td . '><td colspan="2">' . $a . '</td></tr>';
        } else {
            echo '<tr ' . $color_td . '><td style="width: 150px" >' . $a . '</td><td >';
            print_r($b);
            echo '</td><tr>';
        }
    }

    public function check_pg_limit($pg, $v, $total, $debug = '', $name = 'PG') {
        $rating_limit = $v;
        if ($debug && $rating_limit) {
            self::debug_table($name . ' limit ', $pg . ' = ' . $rating_limit);
        }

        if (strstr($rating_limit, '-')) {
            $array_limited = explode('-', $rating_limit);
            if ($total > $array_limited[1]) {
                if ($debug)
                    self::debug_table($name . ' limit calculate', $total . ' > ' . $array_limited[1] . ' ; total = ' . $array_limited[1]);
                $total = $array_limited[1];
            }
            else if ($total < $array_limited[0]) {

                if ($debug)
                    self::debug_table($name . ' limit calculate', $total . ' < ' . $array_limited[0] . ' ; total = ' . $array_limited[0]);
                $total = $array_limited[0];
            }
            else {
                if ($debug)
                    self::debug_table($name . ' limit calculate', $array_limited[0] . ' < ' . $total . ' < ' . $array_limited[1]);
            }
        }
        return $total;
    }

    public function update_rating($imdb_id = '', $rating_name = '', $rating_value = '') {
        if (!$imdb_id)
            return;

        $imdb_id = intval($imdb_id);

        if ($rating_name =='imdb')
        {
            $sql = "UPDATE `data_pg_rating` SET  `" . $rating_name . "_result` = '" . $rating_value . "' WHERE `data_pg_rating`.`movie_id` = " . $imdb_id;

        }
        else
        {
            $sql = "UPDATE `data_pg_rating` SET `" . $rating_name . "_date` = '" . time() . "', `" . $rating_name . "_result` = '" . $rating_value . "' WHERE `data_pg_rating`.`movie_id` = " . $imdb_id;

        }

               Pdo_an::db_query($sql);
    }

    public function get_data() {

        !class_exists('PgRating') ? include ABSPATH . "analysis/include/pg_rating.php" : '';




        !class_exists('OptionData') ? include ABSPATH . "analysis/include/option.php" : '';
        $value = OptionData::get_options('', 'custom_rating_data');
        if ($value) {

            $result = unserialize($value);
            return $result;
        } else {
            $rating = [];

            $rating['convert'] = array("None" => 0, "Mild" => 1.5, "Moderate" => 3, "Severe" => 5);
            $rating['Imdb'] = array("nudity" => 1, "violence" => 1, "profanity" => 1, "alcohol" => 1, "frightening" => 1);
            $rating['Commonsensemedia'] = PgRating::rating_cms_array();
            $rating['Dove'] = array("Faith" => 1, "Integrity" => 1, "Sex" => 1, "Language" => 1, "Violence" => 1, "Drugs" => 1, "Nudity" => 1, "Other" => 1);
            $rating['Positive'] = array('imdb_weight' => 1, 'cms_weight' => 1, 'dove_weight' => 1, 'audience_rating' => 1, 'staff_rating' => 3, 'total_imdb_rating' => 5, 'total_positive_rwt' => 1);
            return $rating;
        }
    }

    public function max_rating($array) {
        /// var_dump($array);
        $array_result = [];

        foreach ($array as $type => $data) {
            $lastname = '';
            $lastcount = 0;

            foreach ($data as $name => $count) {
                if ($count > $lastcount && $count > 0) {
                    $lastname = $name;
                    $lastcount = $count;
                }
            }
            if ($lastname && $lastcount) {
                $array_result[$type] = $lastname;
            }
        }
        return $array_result;
    }

    public function get_imdb_id_from_id($id) {
        $id = intval($id);

        $sql = "SELECT movie_id FROM `data_movie_imdb` where id ='" . $id . "' limit 1 ";

        $r = Pdo_an::db_fetch_row($sql);

        if ($r->movie_id) {
            $movie_id = $r->movie_id;
        }
        if ($movie_id) {
            return $movie_id;
        }
    }
    private function compare_words($array_v,$comment_content)
    {
        $lgbt_text=[];
        foreach ($array_v as $word) {

            $word = trim($word);
            $pattern = '/(?<=^|\s|[.,!?()])' . (strpos($word, '*') !== false ? (substr($word,0,strlen($word)-1)).'\w*' : preg_quote($word, '/')) . '(?=\s|[.,!?()]|$)/i';


            $count = preg_match_all($pattern, $comment_content, $matches);
            if ($count > 0) {

                if (!empty($matches[0])) {
                    $lgbt_text = array_merge($lgbt_text, $matches[0]);
                }
            }
        }

        $lgbt_text = array_filter($lgbt_text, function($value) {
            return trim($value) !== "";
        });
        $unique_keywords = array_unique($lgbt_text);
        return [$unique_keywords,count($lgbt_text)];

    }

    public function custom_rating_lgbt($keywords, $multiple, $max_rating, $array_family, $debug, $total, $row_type = 'lgbt_warning', $comment = 'LGBT Warning') {


        $total_lgbt_result=0;
        $lgbt_enable=0;
        $lgbt_text_total=[];
        $counts =0;

        $array_result =[];

        if ($keywords) {

            $keywords_string = implode(', ', $keywords);
            $keywords_string = strtolower($keywords_string);
        }


        $comment_content = $keywords_string;
        if ($array_family['imdb_rating_desc']) {
            $comment_content .= $array_family['imdb_rating_desc'];
        }
        if ($array_family['cms_rating_desk']) {
            $comment_content .= $array_family['cms_rating_desk'];
        }
        if ($array_family['dove_rating_desc']) {
            $comment_content .= $array_family['dove_rating_desc'];
        }
        if ($array_family['crowd']) {
            $comment_content .= json_encode($array_family['crowd']);
        }



        if ($multiple['multiple']) {
            foreach ($multiple['multiple'] as $m => $data) {
                $v = $data['text'];
                if (strstr($v, ',')) {
                    $array_v = explode(',', $v);
                } else if ($v) {
                    $array_v[] = $v;
                }

                $max_rating = $data['max_rating'];


                if ($comment_content) {
                    [$lgbt_text, $total_count] = self::compare_words($array_v, $comment_content);
                    if ($total_count && $lgbt_text) {
                        $counts += $total_count;

                        //$lgbt_text_string = implode(',', $lgbt_text);
                        $total_lgbt = self::check_pg_limit('', $max_rating, $total, 0, $comment);
                        $lgbt_enable = 1;
                        $lgbt_text_total = array_merge($lgbt_text_total, $lgbt_text);


                        $array_result[$total_lgbt] = [$lgbt_text, $max_rating];
                    }
                }

            }


            ksort($array_result);
            $keys = array_keys($array_result);

            $str = implode(',', $array_result[$keys[0]][0]);
            self::check_pg_limit($str, $array_result[$keys[0]][1], $keys[0], 1, $comment);

            $total_lgbt_result = $keys[0];
            $lgbt_text_string_total = implode(',', $lgbt_text_total);

            if (!$total_lgbt_result) $total_lgbt_result = $total;

            return array($total_lgbt_result, $lgbt_enable, $lgbt_text_string_total, $counts);

        }
        else
        {


        $lgbt_text = [];
        $lgbt_enable = 0;
        $total_lgbt = 0;
        $v = $multiple['text'];
        if (strstr($v, ',')) {
            $array_v = explode(',', $v);
        }
        else if ($v)
        {
            $array_v[]= $v;
        }

        ///search in keywords

            if ($keywords) {

                $keywords_string = implode(',',$keywords);
                $keywords_string = strtolower($keywords_string);





//                    if (!$total_lgbt) {
//                        $key = array_keys($intersection);
//                        if ($debug)
//                            self::debug_table($comment . ' in keywords');
//                        $total_lgbt = self::check_pg_limit($intersection[$key[0]], $max_rating, $total, $debug, $comment);
//                    }
                }



            ///search in content






            if ($comment_content) {
              [$lgbt_text,$total_count] =  self::compare_words($array_v,$comment_content);
            }


        if ($lgbt_text)
        {
            $lgbt_enable = 1;
            if (!$total_lgbt) {
                if ($debug)
                    self::debug_table($comment . ' in comments');

             foreach ($lgbt_text as $word)
             {
                 $total_lgbt = self::check_pg_limit($word,$max_rating, $total, $debug, $comment);
             }

            }


        }
        if (!$lgbt_enable) {
            $lgbt_enable = '';
        }
        $lgbt_text_string = '';

        if ($lgbt_text) {
            $lgbt_text_string = implode(',', $lgbt_text);
        }

        if (!$total_lgbt)$total_lgbt =$total;

        ///echo 'lgbt result: '.$lgbt_enable.'; '.$lgbt_text_string.' '.$comment.'<br>';
        return array($total_lgbt, $lgbt_enable, $lgbt_text_string);
        }
    }
    private function get_movie_genre($id)
    {

        $genre=[];

        $q ="SELECT `data_movie_genre`.`name` FROM `meta_movie_genre`,`data_movie_genre`  WHERE `data_movie_genre`.`id` =`meta_movie_genre`.`gid` AND  `meta_movie_genre`.`mid` = ".$id." ";
        $r = Pdo_an::db_results_array($q);
        foreach ($r as $rows)
        {

            $genre[]=   $rows['name'];
        }
        if (!$genre)
        {
            $genre = self::get_data_in_movie('genre', '',$id);
            $genre = explode(',', $genre);

        }
        return $genre;
    }


    public function CalculateRating($imdb_id = '', $id = '', $debug = '', $update = 1)
    {

        if (!$id) {
            $movie_data = self::get_movie_data($imdb_id);
            $id = $movie_data['id'];
        }

        if (!$imdb_id) {
            $imdb_id = self::get_imdb_id_from_id($id);
        }


        $movie_type = self::get_data_in_movie('type', '',$id);


        if ($debug)
            self::debug_table('s');

        //echo 'CalculateRating '.$imdb_id;

        $array_family = self::get_family_rating_in_movie('',$id);
        ////"educational":"2","message":"2","role_model":"3","
//        if ($id && !$array_family['rwt_id'])
//        {
//            $sql = "UPDATE `data_pg_rating` SET  `rwt_id` = '" . $id . "' WHERE `data_pg_rating`.`movie_id` = " . $imdb_id;
//            Pdo_an::db_query($sql);
//        }

        $rating_array = self::get_data();


        !class_exists('PgRating') ? include ABSPATH . "analysis/include/pg_rating.php" : '';
        ///get
        $array_cms_rating_type = PgRating::rating_cms_array(2);//array("educational" => 1, "message" => 1, "role_model" => 1, "sex" => -1, "violence" => -1, "language" => -1, "drugs" => -1, "consumerism" => -1);
        $array_dove_rating_type = array("Faith" => 1, "Integrity" => 1, "Sex" => -1, "Language" => -1, "Violence" => -1, "Drugs" => -1, "Nudity" => -1, "Other" => -1);
        $array_rwt_rating_type = array("message" => 1, "nudity" => -1, "violence" => -1, "language" => -1, "drugs" => -1, "other" => -1);
        $array_rating_convert = $rating_array['convert'];

        $array_rating_weight = $rating_array['Imdb'];
        $array_cms_rating_weight = $rating_array['Commonsensemedia'];
        $array_dove_ratig_weight = $rating_array['Dove'];
        $array_rwt_rating_weight = $rating_array['rwt'];

        $array_Audience_Staff = $rating_array['Audience_Staff'];
        $array_Imdb_Rwt = $rating_array['Imdb_Rwt'];

        $array_positive_rating_weight = $rating_array['Positive'];
        $imdb_rating = $array_family['imdb_rating'];

        $total_count_array = [];
        $rating_count = 0;

        $family_rating_croud = self::get_family_rating_croud_in_movie($id, $array_rwt_rating_weight, 1);
        if ($family_rating_croud['imdb_rating']) {


            if ($debug)
                self::debug_table('<b>ZR Crowdsource</b>');
            if ($debug)
                self::debug_table('ZR rating array', $family_rating_croud['imdb_rating'])['imdb_rating'];
            if ($debug)
                self::debug_table('ZR rating weight', $array_rwt_rating_weight, 'red');


            $rating_count++;
            $total_rwt_croud_rating = self::site_rating($family_rating_croud['imdb_rating'], $array_rwt_rating_type, $array_rwt_rating_weight, $debug, $name = 'ZR Crowdsource', $imdb_id);

            $array_family['crowd'] = $family_rating_croud['imdb_rating_desc'];
        }
        if ($imdb_rating) {

            $final_value = sprintf('%07d', $imdb_id);
            $url = "https://www.imdb.com/title/tt" . $final_value . '/parentalguide';

            if ($debug)
                self::debug_table('<b>IMDb</b> <a target="_blank" href="' . $url . '">' . $url . '</a>');
            if ($debug)
                self::debug_table('IMDb rating array', json_decode($imdb_rating, 1));

            $imdb_rating = json_decode($imdb_rating);


            //  var_dump($imdb_rating);
            foreach ($imdb_rating as $i => $data) {
                ///var_dump($data);
                $total_count = 0;
                $total_count_all = 0;
                foreach ($data as $i1 => $val) {
                    ///   echo $i1.' '.$val.'<br>';
                    $total_count += $val;
                    $total_count_all += $val * $array_rating_convert[$i1];
                }
                if (!$total_count)
                    $total_count = 1;
                if (!$total_count_all)
                    $total_count_all = 0;
                $total_count_array[$i] = $total_count_all / $total_count;
            }
            // echo '<br><br>';
            ///  var_dump($total_count_array);
            ///
            if ($debug)
                self::debug_table('IMDb ZR rating convert', $array_rating_convert, 'red');
            if ($debug)
                self::debug_table('Multiply IMDb rating on an array of conversion and get the average data for each type from 0 to 5', '', 'gray');
            if ($debug)
                self::debug_table('IMDb array rating total: ', $total_count_array);
            if ($debug)
                self::debug_table('IMDb ZR rating weight', $array_rating_weight, 'red');


            $array_rating_temp = [];
            $total_imdb_rating = 0;
            foreach ($total_count_array as $i => $v) {
                $v1 = $array_rating_weight[$i] * $v;
                $array_rating_temp[$i] = $v1;
                /// echo $v1.' '.$array_rating_weight[$i].' '.$v.' <br>';
                if ($v1 > $total_imdb_rating) {
                    $total_imdb_rating = $v1;
                }
            }
            if ($total_imdb_rating) {
                $total_imdb_rating = round($total_imdb_rating, 2);

                if ($total_imdb_rating >= 5) {
                    $total_imdb_rating = 4.9;
                }
            }
            if ($debug)
                self::debug_table('Recalling data taking into account the correctional coefficient of ZR', '', 'gray');
            if ($debug)
                self::debug_table('IMDb array rating total', $array_rating_temp);
            if ($debug)
                self::debug_table('A positive rating is the biggest rating of positive, negative is the greatest value from negative, if at least one of the values will be 5 then a negative rating will be 5.', '', 'gray');


            if (!$total_imdb_rating) {
                $total_imdb_rating = 5;
            }
            if ($debug)
                self::debug_table('total IMDb rating calculate', ' 5 - ' . $total_imdb_rating . ' = ' . (5 - $total_imdb_rating) . ' ');
            $total_imdb_rating = 5 - $total_imdb_rating;
            if ($debug)
                self::debug_table('Total IMDb rating', $total_imdb_rating, 'green');

            if ($array_family['imdb_result']!=$total_imdb_rating)
            {
                self::update_rating($imdb_id, 'imdb', $total_imdb_rating);
            }


            $rating_count++;
        }

        $cms_rating = $array_family['cms_rating'];
        if ($cms_rating) {

            $cmsurl = $array_family['cms_link'];
            if ($debug)
                self::debug_table('<b>Commonsensemedia</b><br><a target="_blank" href="' . $cmsurl . '">' . $cmsurl . '</a>');
            if ($debug)
                self::debug_table('Cms rating array', json_decode($cms_rating, 1));
            if ($debug)
                self::debug_table('Cms rating weight', $array_cms_rating_weight, 'red');

            $cms_rating = json_decode($cms_rating);

            if ($cms_rating) {
                $rating_count++;
                $total_cms_rating = self::site_rating($cms_rating, $array_cms_rating_type, $array_cms_rating_weight, $debug, $name = 'cms', $imdb_id);
            }
        }

        //////dove rating

        $dove_rating = $array_family['dove_rating'];

        if ($dove_rating) {
            $doveurl = $array_family['dove_link'];
            if ($debug)
                self::debug_table('<b>Dove<b><br><a target="_blank" href="' . $doveurl . '">' . $doveurl . '</a>');
            if ($debug)
                self::debug_table('Dove rating array', json_decode($dove_rating, 1));
            if ($debug)
                self::debug_table('Dove rating weight', $array_dove_ratig_weight, 'red');


            $dove_rating = json_decode($dove_rating);

            if ($dove_rating) {
                $rating_count++;
                $total_dove_rating = self::site_rating($dove_rating, $array_dove_rating_type, $array_dove_ratig_weight, $debug, $name = 'dove', $imdb_id);
            }
        }

        $audience = self::rwt_audience($id, 1);
        $staff = []; //self::rwt_audience($id, 2);


        if ($debug && ($audience["rating"] ))
            self::debug_table('<b>Audience</b>');


        if ($audience["rating"]) {
            $audience_rating = $audience["rating"];
            if ($debug)
                self::debug_table('Audience', $audience_rating);


            if ($array_family['rwt_audience']!=$audience_rating && $update)
            {
                $sql = "UPDATE `data_pg_rating` SET  `rwt_audience` = '" . $audience_rating . "' WHERE `data_pg_rating`.`movie_id` = " . $imdb_id;

                Pdo_an::db_query($sql);

            }

        }
        if ($audience_rating) {
            $total_positive_rwt = $audience_rating;
        }



        if ($debug && $total_positive_rwt)
            self::debug_table('Total positive ZR', $total_positive_rwt, 'green');


        if ($total_positive_rwt && $total_imdb_rating) {
            if ($debug)
                self::debug_table('Array IMDb ZR weight', $array_Imdb_Rwt, 'red');

            $array_Imdb_Rwt = $rating_array['Imdb_Rwt'];

            $total_imdb_rating_current = $total_imdb_rating;

            $k_imdb = $array_Imdb_Rwt['total_imdb_rating'] / ($array_Imdb_Rwt['total_imdb_rating'] + $array_Imdb_Rwt['total_positive_rwt']);
            $k_imdb = round($k_imdb, 2);

            $k_rwt = $array_Imdb_Rwt['total_positive_rwt'] / ($array_Imdb_Rwt['total_imdb_rating'] + $array_Imdb_Rwt['total_positive_rwt']);
            $k_rwt = round($k_rwt, 2);


            if ($debug && $total_positive_rwt)
                self::debug_table('k_imdb ', $array_Imdb_Rwt['total_imdb_rating'] . '/(' . $array_Imdb_Rwt['total_imdb_rating'] . '+' . $array_Imdb_Rwt['total_positive_rwt'] . ') = ' . $k_imdb);
            if ($debug && $total_positive_rwt)
                self::debug_table('k_rwt  ', $array_Imdb_Rwt['total_positive_rwt'] . '/(' . $array_Imdb_Rwt['total_imdb_rating'] . '+' . $array_Imdb_Rwt['total_positive_rwt'] . ') = ' . $k_rwt);

            $total_imdb_rating = $total_imdb_rating_current * $k_imdb + $total_positive_rwt * $k_rwt;

            $total_imdb_rating = round($total_imdb_rating, 2);

            if ($debug)
                self::debug_table('Total imdb rating', ' (' . $total_imdb_rating_current . '*' . $k_imdb . '+' . $total_positive_rwt . '*' . $k_rwt . ') = ' . $total_imdb_rating);
        }

        // echo '$total_rating2='.$total_imdb_rating.'<br>';


        if ($rating_count) {

            if ($debug)
                self::debug_table('Positive rating weight', $array_positive_rating_weight, 'red');
            // $rating['Positive'] = array('imdb_weight' => 1, 'cms_weight' => 1, 'dove_weight' => 1, 'audience_rating' => 1, 'staff_rating' => 3, 'total_imdb_rating' => 5, 'total_positive_rwt' => 1);
            //        if ($total_positive_rwt && $total_imdb_rating && $total_dove_rating && $total_cms_rating )

            if ($total_imdb_rating)
                $imdb_weight = $array_positive_rating_weight['imdb_weight'];
            if ($total_cms_rating)
                $cms_weight = $array_positive_rating_weight['cms_weight'];
            if ($total_dove_rating)
                $dove_weight = $array_positive_rating_weight['dove_weight'];
            if ($total_rwt_croud_rating)
                $rwt_weight = $array_positive_rating_weight['rwt_weight'];


            $total_weight = ($imdb_weight + $cms_weight + $dove_weight + $rwt_weight) / $rating_count;
            if (!$total_weight)$total_weight=1;
            $total =(
                    $total_rwt_croud_rating * $rwt_weight / $total_weight +
                    $total_imdb_rating * $imdb_weight / $total_weight + $total_cms_rating * $cms_weight / $total_weight + $total_dove_rating * $dove_weight / $total_weight

                    ) / $rating_count;
            $total = doubleval($total);
            if ($total)
            {
                $total =round($total, 2);
            }



            if (!$total_imdb_rating)
                $total_imdb_rating = 0;
            if (!$total_dove_rating)
                $total_dove_rating = 0;
            if (!$total_cms_rating)
                $total_cms_rating = 0;

            if (!$rwt_weight)
                $rwt_weight = 0;
            if (!$imdb_weight)
                $imdb_weight = 0;
            if (!$cms_weight)
                $cms_weight = 0;
            if (!$dove_weight)
                $dove_weight =0;




            if ($debug)
                self::debug_table('Total rating calculate', ''
                        . $total_rwt_croud_rating . '*' . $rwt_weight . '/(' . $total_weight . '*' . $rating_count . ')' .
                        '+' . $total_imdb_rating . '*' . $imdb_weight . '/(' . $total_weight . '*' . $rating_count . ')' .
                        '+' . $total_cms_rating . '*' . $cms_weight . '/(' . $total_weight . '*' . $rating_count . ')' .
                        '+' . $total_dove_rating . '*' . $dove_weight . '/(' . $total_weight . '*' . $rating_count . ')'
                );
        }


        if (!$total)$total=0;
        if ($debug)
            self::debug_table('Total rating', ' '.$total.' ', 'green');
        $pg_cert = $array_family['certification_countries'];
        $pg = $array_family['pg'];

        $pg_rated = 0;

        ///video
        ///check movie type
        if ($movie_type=='VideoGame')
        {
            $limit ='PG_games_limit';
        }
        else
        {
            $limit ='PG_limit';
        }

        if (($pg || $pg_cert) && $total>0) {
            if ($debug)
                self::debug_table('PG Limit');


            if ($debug)
            self::debug_table('PG  limit array', $rating_array[$limit], 'red');



            foreach ($rating_array[$limit] as $i => $v) {
                if (strstr($i, ',')) {
                    $array_rating_index = explode(',', $i);

                    ///var_dump($array_rating_index);
                    if (in_array($pg, $array_rating_index)) {
                        $pg_rated = 1;
                          $total = self::check_pg_limit($pg, $v, $total, $debug);


                    }
                }

            }
            if ($pg_cert && !$pg_rated) {
                $pg_cert_array = json_decode($pg_cert);

                foreach ($pg_cert_array as $country => $pg) {
                    if ($pg_rated) {
                        break;
                    }
                    foreach ($rating_array[$limit] as $i => $v) {
                        if (strstr($i, ',')) {
                            $array_rating_index = explode(',', $i);
                            if (in_array($pg[0], $array_rating_index)) {
                                $pg_rated = 1;
                                 $total = self::check_pg_limit($country . ' => ' . $pg[0], $v, $total, $debug);
                                break;
                            }
                        }
                    }

                }
            }
        }

                ////Keywords limit


        else if (!$total &&($pg || $pg_cert)) {

            $pg_rated = 0;
            $cr = 5;
            foreach ($rating_array[$limit] as $i => $v) {
                if (strstr($i, ',')) {
                    $array_rating_index = explode(',', $i);

                    ///var_dump($array_rating_index);
                    if (in_array($pg, $array_rating_index)) {
                        $pg_rated = 1;

                        break;

                    }

                }
                $cr--;
            }

            if ($pg_cert && !$pg_rated) {
                $pg_cert_array = json_decode($pg_cert);

                foreach ($pg_cert_array as $country => $pg) {
                    if ($pg_rated) {
                        break;
                    }
                    $cr = 5;
                    foreach ($rating_array[$limit] as $i => $v) {
                        if (strstr($i, ',')) {
                            $array_rating_index = explode(',', $i);
                            if (in_array($pg[0], $array_rating_index)) {
                                $pg_rated = 1;

                                break;
                            }

                        }
                        $cr--;
                    }

                }
            }


        if ($pg_rated ) {
                $total =  $rating_array['PG_default'][$cr];


                if ($debug)
                {
                    self::debug_table('PG  limit array', $rating_array[$limit], 'red');
                    self::debug_table('PG  Default', $rating_array['PG_default'], 'red');
                    self::debug_table('Total:', $i.'=>' .$cr.' = '.$total.' ');
                }

            }

        }



        $f = '';

        !class_exists('Movie_Keywords') ? include ABSPATH . "analysis/include/keywords.php" : '';
        global $keywords_class;
        if (!$keywords_class)
        {

            $keywords_class = new Movie_Keywords;
        }




        $keywords  = $keywords_class->get_keywors_array($id);

      // var_dump($keywords);

        ///$keywords = self::get_data_in_movie('keywords', $imdb_id);
        if ($keywords) {
          ///  $keywords = explode(',', $keywords);

            $words = $rating_array['words_limit'];
            foreach ($words as $i => $v) {
                if (strstr($v, ',')) {
                    $array_v = explode(',', $v);
                    $intersection = array_intersect($array_v, $keywords);
                    if ($intersection) {
                        if ($debug && !$f)
                            self::debug_table('Keywords limit');
                        $key = array_keys($intersection);
                        $f = 1;
                        //  echo $i;
                        $total = self::check_pg_limit($intersection[$key[0]], $i, $total, $debug, 'Keyword');
                    }
                }
            }
        }
        $genre = self::get_movie_genre($id);

        if ($genre) {

            $words = $rating_array['words_limit'];
            foreach ($words as $i => $v) {
                if (strstr($v, ',')) {
                    $array_v = explode(',', $v);
                    $intersection = array_intersect($array_v, $genre);
                    if ($intersection) {
                        $ki = array_keys($intersection);
                        $total = self::check_pg_limit($intersection[$ki[0]], $i, $total, $debug, 'Genre');
                    }
                }
            }
        }




        /////////lgbt warning


        $v_m = $rating_array['lgbt_warning'];


        [$total,$lgbt_enable,$lgbt_text_string ,$counts] = self::custom_rating_lgbt($keywords, $v_m, [], $array_family, $debug, $total, 'lgbt_warning','LGB Warning');

        if ($update) {
            if ($array_family['lgbt_warning']!=$lgbt_enable || $array_family['lgbt_text']!= $lgbt_text_string) {
                $sql = "UPDATE `data_pg_rating` SET  `lgbt_warning` = '" . $lgbt_enable . "', `lgbt_text` = ?  WHERE `data_pg_rating`.`movie_id` = " . $imdb_id;
                Pdo_an::db_results_array($sql, array($lgbt_text_string));
            }

        }
        /////////qtia warning


        $v_m = $rating_array['qtia_warning'];


        [$total,$lgbt_enable,$lgbt_text_string ,$counts] = self::custom_rating_lgbt($keywords, $v_m, [], $array_family, $debug, $total, 'qtia_warning','QTIA+ Warning');


        if ($update) {
            if ($array_family['qtia_warning']!=$lgbt_enable || $array_family['qtia_text']!= $lgbt_text_string) {
                $sql = "UPDATE `data_pg_rating` SET  `qtia_warning` = '" . $lgbt_enable . "', `qtia_text` = ?  WHERE `data_pg_rating`.`movie_id` = " . $imdb_id;
                Pdo_an::db_results_array($sql, array($lgbt_text_string));
            }


        }
        ///////woke
        $v_m = $rating_array['woke'];

        [$total,$lgbt_enable,$lgbt_text_string ,$counts] = self::custom_rating_lgbt($keywords, $v_m, [], $array_family, $debug, $total, 'woke', 'Woke conclusions');

        if ($update) {

            if ($array_family['woke']!=$lgbt_enable || $array_family['woke_text']!= $lgbt_text_string) {
                $sql = "UPDATE `data_pg_rating` SET  `woke` = '" . $lgbt_enable . "', `woke_text` = ?  WHERE `data_pg_rating`.`movie_id` = " . $imdb_id;
                Pdo_an::db_results_array($sql, array($lgbt_text_string));
            }

        }


        if ($update) {
            if (!$total)$total=0;


            if ($array_family['rwt_pg_result']!=$total) {
                $sql = "UPDATE `data_pg_rating` SET  `rwt_pg_result` = '" . $total . "' WHERE `data_pg_rating`.`movie_id` = " . $imdb_id;
                Pdo_an::db_query($sql);
            }

            ////update rwt pg cache
        }

        if ($update) {

            $sql = "UPDATE `data_pg_rating` SET  `last_update` = '" . time() . "' WHERE `data_pg_rating`.`movie_id` = " . $imdb_id;

            Pdo_an::db_query($sql);


            if ($id !=$array_family['rwt_id'] )
            {
                $sql = "UPDATE `data_pg_rating` SET  `rwt_id` = '" . $id . "' WHERE `data_pg_rating`.`movie_id` = " . $imdb_id;
                Pdo_an::db_query($sql);
            }




            $array_family_updated =self::get_family_rating_in_movie($imdb_id);


            if (!$array_family['last_update'] || $array_family_updated['rwt_id'] != $array_family['rwt_id']  || $array_family['imdb_result'] != $array_family_updated['imdb_result'] || $array_family['cms_rating'] != $array_family_updated['cms_rating'] || $array_family['dove_result'] != $array_family_updated['dove_result'] || $array_family['rwt_audience'] != $array_family_updated['rwt_audience'] || $array_family['rwt_pg_result'] != $array_family_updated['rwt_pg_result'] || $array_family['lgbt_warning'] != $array_family_updated['lgbt_warning'] || $array_family['woke'] != $array_family_updated['woke']
            )
            {




                $comment=' updated';
                !class_exists('TMDB') ? include ABSPATH . "analysis/include/tmdb.php" : '';
                TMDB::add_log($id,$imdb_id,'update movies','CalculateRating  PG'.$comment,1,'admin');


                !class_exists('Import') ? include ABSPATH . "analysis/export/import_db.php" : '';
                Import::create_commit('', 'update', 'data_pg_rating', array('rwt_id' => $id), 'pg_rating', 9, ['skip' => ['id']]);
            }


        }


        if ($debug) {

            $last_imdb_updated  = $array_family['last_update'];

            $last_imdb_updated_string = date('Y-m-d',$last_imdb_updated);
            $update_link='';
            if ($last_imdb_updated < time()-86400)
            {
                $update_link = '<a href="#" id="last_pg_update" data-value="'.$id.'" class="update_data">Update data</a>';
            }

            self::debug_table('Total rating', $total, 'green');
            self::debug_table('<p class="last_updated_desc">last updated: '.$last_imdb_updated_string.$update_link.'</p>');
        }
        if ($debug)
            self::debug_table('e'); ///end of table

        return $total;
    }

    public function site_rating($cms_rating, $array_cms_rating_type, $array_cms_rating_weight, $debug, $name = 'cms', $imdb_id) {


        $total_rating = 0;
        $i = 0;
        $total_negative_rating = 0;
        $total_positive_rating = 0;
        $total_rating_array = [];
        foreach ($cms_rating as $type => $ratind) {


            if (!$array_cms_rating_weight[$type]) {
                $array_cms_rating_weight[$type] = 1;
            }
            if (!$array_cms_rating_type[$type]) {
                $array_cms_rating_type[$type] = -1;
            }
            if ($array_cms_rating_type[$type] == -1) {
                $total_rating = $array_cms_rating_weight[$type] * $ratind;
                $total_rating_array[$type] = $total_rating;

                if ($total_rating > $total_negative_rating) {
                    $total_negative_rating = $total_rating;
                }
            }
            if ($array_cms_rating_type[$type] == 1) {
                $total_rating = $array_cms_rating_weight[$type] * $ratind;
                $total_rating_array[$type] = $total_rating;
                if ($total_rating > $total_positive_rating) {
                    $total_positive_rating = $total_rating;
                }
            }

            $i++;
        }

        if ($debug)
            self::debug_table($name . ' rating array converted', $total_rating_array);

        if ($total_negative_rating >= 5) {
            $total_negative_rating = 4.9;
        }
        if ($total_negative_rating != 0) {

            $total_negative_rating_result = 5 - $total_negative_rating;
        }
        if (!$total_negative_rating_result) {
            $total_negative_rating_result = 0;
        }
        if ($total_positive_rating >= 5) {
            $total_positive_rating = 5;
        }


        if ($debug)
            self::debug_table('A positive rating is the biggest rating of positive, negative is the greatest value from negative, if at least one of the values will be 5 then a negative rating will be 5.', '', 'gray');
        if ($debug && $total_positive_rating)
            self::debug_table('Total positive ' . $name . ' rating', $total_positive_rating);
        if ($debug)
            self::debug_table('Total negative ' . $name . ' rating', $total_negative_rating);
        if ($debug)
            self::debug_table('Total ' . $name . ' negative rating result', ' 5 - ' . $total_negative_rating . ' = ' . $total_negative_rating_result . '  ');

        if ($total_positive_rating && $total_negative_rating) {
            $total_cms_rating = ($total_negative_rating_result + $total_positive_rating) / 2;
            if ($debug)
                self::debug_table('Total ' . $name . ' rating calculate ', '(' . $total_negative_rating_result . '+' . $total_positive_rating . ')/2 = ' . $total_cms_rating);
        } else if ($total_positive_rating) {
            $total_cms_rating = $total_positive_rating;
        } else if ($total_negative_rating_result) {
            $total_cms_rating = $total_negative_rating_result;
        }
        if ($debug && $total_cms_rating)
            self::debug_table('Total ' . $name . ' rating ', $total_cms_rating, 'green');
        /////update cms rating

        self::update_rating($imdb_id, $name, $total_cms_rating);

        return $total_cms_rating;
    }

    public function get_family_rating_in_movie($rid='',$id='') {
        if ($id)
        {
            $id = intval($id);
            $sql = "SELECT * FROM `data_pg_rating` WHERE `rwt_id` = {$id} limit 1";

        }
        else if ($rid)
        {
            $rid = intval($rid);
            $sql = "SELECT * FROM `data_pg_rating` WHERE `movie_id` = {$rid} limit 1";

        }


        $row = Pdo_an::db_results_array($sql);
        return $row[0];
    }

    public function get_family_rating_croud_in_movie($id, $array_rwt_rating_weight, $all_data = '') {
        $id = intval($id);
        $sql = "SELECT * FROM `data_movies_pg_crowd` WHERE `rwt_id` = {$id} and status = 1 ";
        $row = Pdo_an::db_results_array($sql);

        $rating_rwt_crd = [];
        $rating_rwt_crd_comment = [];
        $count = count($row);
        if (!$count)
            $count = 1;

        foreach ($row as $family_rating_croud) {
            ///    var_dump($family_rating_croud);
            foreach ($family_rating_croud as $r => $v) {
                if ($array_rwt_rating_weight[$r]) {
                    if ($v != 0) {
                        $rating_rwt_crd[$r] += $v;
                    }
                }
                if ($all_data) {
                    if (strstr($r, '_comment')) {
                        $r1 = str_replace('_comment', '', $r);
                        if ($array_rwt_rating_weight[$r1]) {
                            $rating_rwt_crd_comment[$r1] .= $v . '<br>';
                        }
                    }
                }
            }
        }

        $rating_rwt_crd_result = [];
        foreach ($rating_rwt_crd as $i => $v) {
            $rating_rwt_crd_result[$i] = $v / $count;
        }
        if ($all_data) {

            return array('imdb_rating' => $rating_rwt_crd_result, 'imdb_rating_desc' => $rating_rwt_crd_comment);
        } else {
            return $rating_rwt_crd_result;
        }
    }

    public function get_movie_data($rid) {
        $rid = intval($rid);
        $sql = "SELECT * FROM `data_movie_imdb` WHERE `movie_id` = {$rid} limit 1";
        $row = Pdo_an::db_results_array($sql);
        return $row[0];
    }

    public function get_data_in_movie($data, $rid = '', $id = '') {
        if ($id) {
            $id = intval($id);
            $sql = "SELECT `{$data}` FROM `data_movie_imdb` WHERE `id` = {$id} limit 1";

            $row = Pdo_an::db_fetch_row($sql);
        } else {
            $rid = intval($rid);
            $sql = "SELECT {$data} FROM `data_movie_imdb` WHERE `movie_id` = {$rid} limit 1";
            $row = Pdo_an::db_fetch_row($sql);
        }

        if ($row) {

            if ($row->$data) {
                return $row->$data;
            }
        }
    }

    public function get_audience_rating_in_movie($rid, $type = 1) {

        ///get from e rating
//        $rid = intval($rid);
//        $sql = "SELECT * FROM `data_movie_erating` where `movie_id` = " . $rid;
//        $row_movie_erating= Pdo_an::db_results_array($sql);

        $rid = intval($rid);

        $sql = "SELECT * FROM `cache_rwt_rating` WHERE `movie_id` = {$rid}  limit 1";

        $row = Pdo_an::db_results_array($sql);
        if ($row) {
            return $row[0];
        }
        return [];
    }

    public function rwt_audience($movie_id, $audience_type = 1, $update = '') {
        global $debug;
        if (!$update) {
            $result_summ_rating = self::get_audience_rating_in_movie($movie_id, $audience_type);

            if ($result_summ_rating) {
                return $result_summ_rating;
            }
        }

        if ($debug) { !class_exists('TMDB') ? include ABSPATH . "analysis/include/tmdb.php" : '';

            echo 'rwt_audience<br>';
            TMDB::var_dump_table(['result_summ_rating',$result_summ_rating]);
        }


//critic_matic_posts_meta
        $hollywood_total = [];
        $audience_count=0;
        $review_data = self::get_wpcdata($movie_id, 1);
        ///echo '<br>review_data<br>'.PHP_EOL;
///var_dump($review_data);

        if ($debug)
        {
            TMDB::var_dump_table(['review_data',$review_data]);
        }


        $total_audience = [];

        if ($review_data) {
            if (is_array($review_data)) {
                foreach ($review_data as $rid => $val) {
                    // echo $rid.'<br>';
                    ///  var_dump($val);
                    $audience_count += 1;

                    if ($val['hollywood']) {
                        $hollywood_total[$rid]['data'] = $val['hollywood'];
                        $hollywood_total[$rid]['count']++;
                    }

                    foreach ($val as $i => $v) {
                        if ($i == 'vote') {
                            $total_audience[$i][$v]++;
                        } else {

                            if ($v) {
                                $total_audience[$i]['data'] += $v;
                                $total_audience[$i]['count']++;
                                if ($i != 'r') {
                                    $hollywood_total[$rid]['data'] += $v;
                                    $hollywood_total[$rid]['count']++;
                                }
                            }
                        }
                    }
                }
                // echo '<br>';  echo '<br>';

                if ($audience_type == 2) {
                    //  $total_audience = self::rwt_staff($movie_id, $total_audience);
                }

                $result_summ_rating = [];

                //   $array_convert = array('r' => 'rating', 'h' => 'hollywood', 'p' => 'patriotism', 'm' => 'misandry', 'a' => 'affirmative', 'l' => 'lgbtq', 'g' => 'god', 'v' => 'vote');
                //echo 'total_audience<br>'.PHP_EOL;
                //var_dump($total_audience);
                ///echo 'total_audience end<br>'.PHP_EOL;
                foreach ($total_audience as $i => $v) {

                    if ($i) {
                        if ($i == 'vote') {
                            arsort($v);
                            $key = array_keys($v);
                            $result_summ_rating['vote'] = $key[0];
                        } else {
                            $i0 = $i;

                            if ($v['count']) {
                                $summ = $v['data'] / $v['count'];
                                $summ = ceil(($summ) / 0.5) * 0.5;
                                $result_summ_rating[$i0] = $summ;
                            }
                        }
                    }
                }

                $hollywood_result = [];
                foreach ($hollywood_total as $pid => $data) {
                    $hollywood_result['data'] += $data['data'];
                    $hollywood_result['count'] += $data['count'];
                }
                if ($hollywood_result['count']) {
                    $hollywood_result_string = $hollywood_result['data'] / $hollywood_result['count'];
                    if ($hollywood_result_string) {
                        $hollywood_result_string = ceil(($hollywood_result_string) / 0.5) * 0.5;
                    }
                    $result_summ_rating['hollywood'] = $hollywood_result_string;
                }

                //var_dump($result_summ_rating);

                if (!$audience_count) $audience_count = 0;

                $result_summ_rating['count'] = $audience_count;

                if ($debug) {
                    TMDB::var_dump_table(['result_summ_rating', $result_summ_rating]);
                }

                self::update_rating_db($movie_id, $result_summ_rating, $audience_type);
            }
        }else
        {
            if ($debug)
            {
               echo 'review_data is null<br>';
            }
        }
        return $result_summ_rating;
    }

    public static function check_audience_meta($rid,$audience)
    {
        global $debug;

        if ($debug)
        {
            TMDB::var_dump_table(['check_audience_meta',$rid,$audience]);
        }

        if ($audience)
        {
            $audience_calc = $audience*20;

            $q = "SELECT `id`,`audience_rating` FROM `data_movie_erating` WHERE`movie_id` = ".$rid;
            $rw = Pdo_an::db_results_array($q);

            $data_current_array['audience_rating']=$audience_calc;
            $data_current_array['movie_id'] = $rid;
            $data_current_array['audience_date'] = time();

            if (!$rw && $audience_calc>0)
            {
                $data_current_array['date'] = time();
                $data_current_array['last_upd'] = time();
                if ($debug){
                    TMDB::var_dump_table(['insert data_movie_erating',$audience_calc,$rid]);
                }

                        self::sync_update($data_current_array, '', 'data_movie_erating', 'insert', 1);
            }
            else {


             $rat =  $rw[0]['audience_rating'];

                if ($audience_calc >0  && $rat!=$audience_calc)
                {
                    //update
                    if ($debug){
                        TMDB::var_dump_table(['update data_movie_erating',$audience_calc,$rid]);
                    }
                    self::sync_update($data_current_array, $rw[0]['id'], 'data_movie_erating', 'update', 1);



                }
                else
                {
                    TMDB::var_dump_table(['skip update data_movie_erating',$audience_calc]);

                }
            }










        }

    }

    public function update_rating_db($rid, $ar, $type = 1) {

        global $debug;

        if ($debug)
        {
            echo 'update_rating_db<br>';
        }


        $dop = '';
        if ($type == 2) {
            $dop = '_staff';
        }


        $rid = intval($rid);




        $r = self::get_audience_rating_in_movie($rid, $type);

        if ($r) {

            ///check before update

            if (
                    $r['vote'] == $ar['vote'] &&
                    $r['rating'] == $ar['rating'] &&
                    $r['affirmative'] == $ar['affirmative'] &&
                    $r['god'] == $ar['god'] &&
                    $r['hollywood'] == $ar['hollywood'] &&
                    $r['lgbtq'] == $ar['lgbtq'] &&
                    $r['misandry'] == $ar['misandry'] &&
                    $r['count'] == $ar['count'] &&
                    $r['patriotism'] == $ar['patriotism']
            ) {
                //skip

                if ($debug)echo 'skip<br>';

            } else {
                if ($debug)echo 'update<br>';
                $sql = "UPDATE `cache_rwt_rating" . $dop . "` SET 
                              `vote` = ?,   `rating` = ?,    `affirmative` = ?,   `god` = ?,
                              `hollywood` = ?,  `lgbtq` = ?, `misandry` = ?, 
                              `patriotism` = ?,  `count` = ? ,`last_update` =?  WHERE `movie_id` = {$rid} and `type`={$type} ";


                Pdo_an::db_results_array($sql, array($ar['vote'], $ar['rating'], $ar['affirmative'], $ar['god'], $ar['hollywood'], $ar['lgbtq'], $ar['misandry'], $ar['patriotism'], $ar['count'],time()));


                !class_exists('Import') ? include ABSPATH . "analysis/export/import_db.php" : '';
                Import::create_commit('', 'update', 'cache_rwt_rating', array('movie_id' => $rid), 'cache_rwt_rating', 20, ['skip' => ['id']]);


                ///update erating

                self::add_movie_rating($rid,'',$debug,1,1,1);
            }
        } else {
            if ($debug)echo 'insert<br>';
            if ($ar['vote'] || $ar['rating'] || $ar['affirmative'] || $ar['god'] || $ar['hollywood'] || $ar['lgbtq'] || $ar['misandry'] || $ar['patriotism']) {

                $sql = "INSERT INTO cache_rwt_rating" . $dop . " (`id`, `movie_id`, `type`, `vote`, `rating`, `affirmative`, `god`, `hollywood`, `lgbtq`, `misandry`, `patriotism`, `count`, `last_update`)   
                          VALUES (NULL, ?,        ?,     ?,    ?,             ?,           ?,     ?,          ?,          ?,          ?,          ?,          ?);";
                Pdo_an::db_results_array($sql, array($rid, $type, $ar['vote'], $ar['rating'], $ar['affirmative'], $ar['god'], $ar['hollywood'], $ar['lgbtq'], $ar['misandry'], $ar['patriotism'],$ar['count'],time()));

                !class_exists('Import') ? include ABSPATH . "analysis/export/import_db.php" : '';
                Import::create_commit('', 'update', 'cache_rwt_rating', array('movie_id' => $rid), 'cache_rwt_rating', 20, ['skip' => ['id']]);


                ///update erating

                self::add_movie_rating($rid,'',$debug,1,1,1);

            }
        }


        ////check audience meta
      //  self::check_audience_meta($rid,$ar['rating']);


    }

    public function get_wpcdata($movie_id, $audience_type=1) {

        global $debug;


        $staff_type = "and a.type=2";


        if (!$movie_id)
            return;


        $review_data = [];

        $sql = "select r.* from ".DB_PREFIX_WP_AN."critic_matic_rating as r 
    inner join ".DB_PREFIX_WP_AN."critic_matic_posts_meta as m ON m.cid = r.cid
inner join ".DB_PREFIX_WP_AN."critic_matic_posts as p ON p.id = r.cid
inner join ".DB_PREFIX_WP_AN."critic_matic_authors_meta as am ON am.cid = m.cid
inner join ".DB_PREFIX_WP_AN."critic_matic_authors as a ON a.id = am.aid


where  m.fid='{$movie_id}' AND m.state!=0  and p.status=1 " . $staff_type;

       if ($debug) {            echo 'get_wpcdata<br>'.$sql.'<br>';}

        $rows = Pdo_an::db_results_array($sql);


        if ($debug) { !class_exists('TMDB') ? include ABSPATH . "analysis/include/tmdb.php" : '';


            TMDB::var_dump_table(['critic_matic_rating',$rows]);
        }

        foreach ($rows as $r) {


            $r_id = $r['id'];

            $review_data[$r_id] = array(
                'rating' => $r['rating'],
                'hollywood' => $r['hollywood'],
                'patriotism' => $r['patriotism'],
                'misandry' => $r['misandry'],
                'affirmative' => $r['affirmative'],
                'lgbtq' => $r['lgbtq'],
                'god' => $r['god'],
                'vote' => $r['vote']
            );
        }
        return $review_data;
    }

}
