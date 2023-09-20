<?php
if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');

//DB config
!defined('DB_HOST_AN') ? include ABSPATH . 'analysis/db_config.php' : '';
//Abstract DB
!class_exists('Pdoa') ? include ABSPATH . "analysis/include/Pdoa.php" : '';

class MOVIE_DATA
{

    public static  function last_update_container($id,$class,$time,$comment='Last updated:',$update_period=86400)
    {
        if ($time==0)
        {
            $last_imdb_updated_string= 'newer';
        }
        else
        {
            $last_imdb_updated_string = date('Y-m-d',$time);
        }

        $update_link='';

        if ($time < time()-$update_period)
        {
            $update_link = '<a href="#" id="'.$class.'" data-value="'.$id.'" class="update_data">update</a>';
        }
        return '<p class="last_updated_desc">'.$comment.' '.$last_imdb_updated_string. $update_link.'</p>';

    }

    private static $debug=0;
    private static $verdct_method='';

    public static function get_actor_meta($id)
    {
        $q = "SELECT * FROM `data_actors_meta`  where actor_id = ".intval($id);
        $r = Pdo_an::db_results_array($q);
        return $r[0];
    }


    public static function get_movies_data($movie_id,$actor_type=array("star","main"),$priority_string='')
    {


        if (!defined('CRITIC_MATIC_PLUGIN_DIR')) {
            define('CRITIC_MATIC_PLUGIN_DIR', ABSPATH . 'wp-content/plugins/critic_matic/');
            require_once( CRITIC_MATIC_PLUGIN_DIR . 'critic_matic_ajax_inc.php' );
        }

        $movies = array($movie_id);

        $debug = false;
        if (self::$debug) {
            $debug = true;
        }

        $cm = new CriticMatic();
        $af = $cm->get_af();
        $ss = $cm->get_settings(false);
        if (isset($ss['an_weightid']) && $ss['an_weightid'] > 0) {
            $mode_key = $ss['an_weightid'];
        }
        if (!$mode_key) $mode_key = 0;

        if ($debug) {echo 'mode_key='.$mode_key.'<br>';}

        !class_exists('OptionData') ? include ABSPATH . "analysis/include/option.php" : '';
        $vd_data = unserialize(unserialize(OptionData::get_options('','critic_matic_settings')));
        $verdict_method=0; if ($vd_data["an_verdict_type"]=='w'){$verdict_method=1;}



      $array_actor_conver = array("star"=>1,"main"=>2,"extra"=>3,"directors"=>4);

      foreach ($actor_type as $v)
      {
          $showcast[]=  $array_actor_conver[$v];
      }
        //$showcast = array(1, 2);
        /*  $showcast:
          1 = 'Stars'
          2 = 'Supporting'
          3 = 'Other'
          4 = 'Production'
         */

// Custom priority
        $priority = '';

        $ver_weight = false;
        if ($verdict_method ==1) {
            // Weights logic
            $ver_weight = true;
            $weights_arr = $af->get_filter_mode($mode_key);
            if ($weights_arr['custom']) {
                $priority = $weights_arr['priority'];
            }
        } else {
            // Priority logic
            $priority_arr = $af->get_filter_priority($priority_string);
            if ($priority_arr['custom']) {
                $priority = $priority_arr['priority'];
            }
        }

        $race_data = $af->get_movies_race_data($movies, $showcast, $ver_weight, $priority, $debug);
       return $race_data;
    }

    public static function get_actor_name($id)
    {
        $sql = "SELECT name FROM `data_actors_imdb` where id =" . $id;
        $r = Pdo_an::db_fetch_row($sql);
        $name = $r->name;
//        if (!$name)
//        {
//            $sql = "SELECT primaryName  FROM `data_actors_all` where actor_id =".$id;
//            $r = Pdo_an::db_fetch_row($sql);
//            $name = $r->primaryName;
//        }
        return $name;
    }
    public static function get_verdict_method()
    {

        if (self::$verdct_method)
        {

            return self::$verdct_method['verdict'];
        }
        else
        {
            !class_exists('OptionData') ? include ABSPATH . "analysis/include/option.php" : '';
            $vd_data = unserialize(unserialize(OptionData::get_options('','critic_matic_settings')));
            $verdict_method=0; if ($vd_data["an_verdict_type"]=='w'){$verdict_method=1;}
            self::$verdct_method = array('verdict'=>$verdict_method);
            return $verdict_method;
        }

    }

    public static function get_actor_race($id)
    {
        !class_exists('INTCONVERT') ? include ABSPATH . "analysis/include/intconvert.php" : '';
        $array_ints = INTCONVERT::get_array_ints();


        $array_convert = array('2' => 'Male', '1' => 'Female', '0' => 'NA');
        $array_compare_cache = array('Sadly, not' => 'N/A', '1' => 'N/A', '2' => 'N/A', 'NJW' => 'N/A', 'W' => 'White', 'B' => 'Black', 'EA' => 'Asian', 'H' => 'Latino', 'JW' => 'Jewish', 'I' => 'Indian', 'M' => 'Arab', 'MIX' => 'Mixed / Other', 'IND' => 'Indigenous');
        $array_ints = INTCONVERT::get_array_ints();

        $meta = self::get_actor_meta($id);
        $gender = $array_convert[$meta['gender']];
        $verdict_method=   self::get_verdict_method();
        if ($verdict_method==1)
        {
            $verdict = $meta['n_verdict_weight'];
            if (!$verdict)
            {
                $verdict = $meta['n_verdict'];
            }
        }

        if (!$verdict)$verdict=0;
        $race = $array_compare_cache[$array_ints[$verdict]];
        return $race.' '.$gender;
    }

    public static function single_actor_template($id,$type='',$ethnycity_string='')
    {
        !class_exists('RWTimages') ? include ABSPATH . "analysis/include/rwt_images.php" : '';
        $name = self::get_actor_name($id);
        if ($id) {
            $e = '';
            if ($ethnycity_string) {
                $e = '&e=' . $ethnycity_string;
            }
            if ($type == 'main') {
                $type = 'supporting';
            }

            $filmolink= '/search/actor_'.$id;


            $dop_string = '<span class="a_data_n_d">' . str_replace('_', ' ', ucfirst($type)) . '</span>';

            $image_link = RWTimages::get_image_link($id,'270x338','','','',1);
            $actor_name_encoded =urlencode($name);
            ///gender and race
            $arace = self::get_actor_race($id);


            $actor_cntr = '<div class="card style_1 img_tooltip">
                     <a  class="actor_info" data-id="' . $id . '"  href="#">
                     <div class="a_data_n">' . $name . $dop_string . ' </div>
                     <img loading="lazy" title="What ethnicity is ' . $name . '?" alt="' . $name . ' ethnicity" class="a_data_i" 
                     src="' . $image_link . '" />
                     </a>
                     
                      <div class="actor_crowdsource_container adtor_r_data">'.$arace.'<a title="Edit Actor data" id="op" data-value="' . $id . '" class="actor_crowdsource button_edit" href="#"></a></div>
 
                     
                     <span class="actor_b_link"><a href="'.$filmolink.'">Filmography</a>
<a target="_blank" href="https://en.wikipedia.org/w/index.php?search='.$actor_name_encoded.'">Wikipedia</a></span>
                                       </div>';



        }
        return $actor_cntr;

    }


    public static function get_actors_template($movie_id, $actor_type, $ethnycity_string = '')
    {




        $actors_array = self::get_actors_from_movie($movie_id, '', $actor_type);

        $actor_type_min = array('star' => 's', 'main' => 'm', 'extra' => 'e', 'director' => 'director', 'writer' => 'writer', 'cast_director' => 'cast_director', 'producer' => 'producer');

        if (is_array($actors_array)) {

            $content_array = [];

            foreach ($actor_type as $type) {

                foreach ($actors_array[$actor_type_min[$type]] as $id => $enable) {

                    $actor_cntr =self::single_actor_template($id,$type,$ethnycity_string);

                    $addtime = time();
                    $content_array[$addtime . '_' . $id] = array('pid' => $id, 'content_data' => $actor_cntr);
                }


            }


        }

        return $content_array;
    }


    public static function get_actors_from_movie($id = '', $imdb_id = '', $actor_type = [])
    {
        $w = '';
        $actor_result = [];
        $actor_types = array('1' => 's', '2' => 'm', '3' => 'e');

        $actor_types_big = array('star' => '1', 'main' => '2', 'extra' => '3');

        $director_types = array(1 => 'director', 2 => 'writer', 3 => 'cast_director', 4 => 'producer');


        if (!$id && $imdb_id) {
            $imdb_id = intval($imdb_id);

            $sql = "SELECT id FROM `data_movie_imdb` where `movie_id` ='" . $imdb_id . "'  limit 1 ";

            $r = Pdo_an::db_fetch_row($sql);
            $id = $r->id;
        }
        if ($id) {


            if ($actor_type) {
                foreach ($actor_type as $val) {
                    if ($val && ($val != 'director' && $val != 'writer' && $val != 'cast_director' && $val != 'producer')) {
                        $w .= "OR `type` = '" . $actor_types_big[$val] . "' ";
                    }

                }
                if ($w) {
                    $w = substr($w, 2);
                    $w = " AND (" . $w . ") ";
                }


                if (in_array('director', $actor_type) || in_array('writer', $actor_type) || in_array('cast_director', $actor_type) || in_array('producer', $actor_type)) {
                    $sql = "SELECT * FROM meta_movie_director WHERE mid={$id} ";


                    $r = Pdo_an::db_results_array($sql);
                    foreach ($r as $row) {
                        $actor_result[$director_types[$row['type']]][$row['aid']] = 1;
                    }
                }
            }


            if ($w) {
                $sql = "SELECT * FROM meta_movie_actor WHERE mid={$id}  {$w} ";
                //echo $sql;
                $r = Pdo_an::db_results_array($sql);
                foreach ($r as $row) {
                    $actor_result[$actor_types[$row['type']]][$row['aid']] = 1;
                }
            }

        }
        return $actor_result;
    }

    public static function get_array_convert_type()
    {

        $array_convert_type = array('crowd' => 'crowd', 'ethnic' => 'ethnic', 'jew' => 'jew', 'face' => 'kairos', 'face2' => 'bettaface', 'surname' => 'surname','familysearch'=>'familysearch','forebears'=>'forebears');

        return $array_convert_type;
    }
    public static function get_movie_year($mid)
    {
        $q="SELECT `year` FROM `data_movie_imdb` WHERE `id` = ".$mid;
        $r =Pdo_an::db_fetch_row($q);
        return $r->year;

    }

    public static function get_movie_data_from_db($id = '', $a_sql = '', $only_etnic = 0, $actor_type = [], $actors_array = [], $diversity_select = "default", $ethnycity = [], $all_data = '')
    {

        if (!$ethnycity)
        {
            $ethnycity = json_decode('{"1":{"crowd":1},"2":{"ethnic":1},"3":{"jew":1},"4":{"face":1},"5":{"face2":1},"6":{"forebears":1},"7":{"familysearch":1},"8":{"surname":1}}',true);

        }
        $year = self::get_movie_year($id);

        !class_exists('INTCONVERT') ? include ABSPATH . "analysis/include/intconvert.php" : '';
        $array_ints = INTCONVERT::get_array_ints();

        !class_exists('OptionData') ? include ABSPATH . "analysis/include/option.php" : '';
        $vd_data = unserialize(unserialize(OptionData::get_options('','critic_matic_settings')));
        $verdict_method=0; if ($vd_data["an_verdict_type"]=='w'){$verdict_method=1;}


        $i = $i0 = $i_j = 0;
        $need_request = '';

        $ethnic = [];
        if (!$a_sql) {

            if (!count($actors_array)) {
                $actors_array = self::get_actors_from_movie($id, '', $actor_type);
            }


            $actors_array = self::check_actors_to_stars($actors_array, $actor_type);
//{ [1]=> string(8) "director" [2]=> string(6) "writer" [3]=> string(13) "cast_director" [4]=> string(8) "producer" }
            //var_dump($actor_type);

            $actor_type_min = array('star' => 's', 'main' => 'm', 'extra' => 'e', 'director' => 'director', 'writer' => 'writer', 'cast_director' => 'cast_director', 'producer' => 'producer');

            foreach ($actor_type as $val) {
                $prefix = $actor_type_min[$val];
                if ($actors_array[$prefix]) {
                    foreach ($actors_array[$prefix] as $id => $val) {
                        $all_actors[$id] = 1;
                        $total_actors_data[$id] = 1;
                    }
                }
            }
        } else {
            $all_actors[$id] = 1;
            $total_actors_data[$id] = 1;
        }

        $array_convert = array('2' => 'Male', '1' => 'Female', '0' => 'NA');

        $verdict_data=[];
        foreach ($all_actors as $id => $enable) {
            $sql = "SELECT * FROM `data_actors_meta` where actor_id =" . $id . " ";
            $rows = Pdo_an::db_results_array($sql);

            foreach ($rows as $r) {

                //if ($r['gender'])
                //{
                //  echo $r['actor_id'].' - '.$r['gender'].'<br>';

                $gender_result = $array_convert[$r['gender']];
                if (!$gender_result) $gender_result = 'NA';

                $ethnic['gender'][$r['actor_id']] = $gender_result;
                //}


                if ($r['n_jew']) $ethnic['jew'][$r['actor_id']] = $array_ints[$r['n_jew']];
                if ($r['n_bettaface']) $ethnic['bettaface'][$r['actor_id']] = $array_ints[$r['n_bettaface']];
                if ($r['n_surname']) $ethnic['surname'][$r['actor_id']] = $array_ints[$r['n_surname']];
                if ($r['n_ethnic']) $ethnic['ethnic'][$r['actor_id']] = $array_ints[$r['n_ethnic']];
                if ($r['n_kairos']) $ethnic['kairos'][$r['actor_id']] =$array_ints[ $r['n_kairos']];
                if ($r['n_crowdsource']) $ethnic['crowd'][$r['actor_id']] = $array_ints[$r['n_crowdsource']];
                if ($r['n_forebears']) $ethnic['forebears'][$r['actor_id']] = $array_ints[$r['n_forebears']];
                if ($r['n_familysearch']) $ethnic['familysearch'][$r['actor_id']] = $array_ints[$r['n_familysearch']];

                if ($verdict_method)
                {
                    if ($r['n_verdict_weight'])
                    {
                        $verdict_data[$r['actor_id']] =$array_ints[$r['n_verdict_weight']];
                    }
                    else
                    {
                        $verdict_data[$r['actor_id']] =$array_ints[$r['n_verdict']];
                    }

                }
                else
                {
                    $verdict_data[$r['actor_id']] =$array_ints[$r['n_verdict']];
                }


            }
        }


        $array_convert_type = self::get_array_convert_type();


        $ethnic_sort = [];
        $result = [];

        if ($diversity_select == 'm_f' && !$a_sql) {
            $gender_data = $ethnic['gender'];

            $gd_result=[];
           foreach ($gender_data as $aid=>$gd)
           {
               $gd_result[$gd]++;
           }
           return $gd_result;
        }
        else {
            if ($diversity_select == 'wmj_nwm' || $diversity_select == 'wm_j_nwmj') {
                $ethnic_sort['gender'] = [];
            }


            if ($ethnycity) {
                foreach ($ethnycity as $order => $data) {
                    foreach ($data as $typeb => $enable) {
                        if ($enable) {
                            $ethnic_sort[$array_convert_type[$typeb]] = [];
                        }
                    }
                }
                foreach ($ethnic_sort as $key => $value) {
                    $result[$key] = $ethnic[$key];
                }
            }

        }



        if (!$a_sql) {
            $ethnic = $result;
            if ($diversity_select != 'wj_nw') $diversity_select = '';
        }


        $arrayneed_compare = [];
        $request_type = [];
        $array_request = [];
        $total_data_result = [];


        $ethnic_array = self::get_echnic_custom($total_actors_data, $ethnic, $diversity_select, $arrayneed_compare, $request_type, $array_request, 1 ,$verdict_data);
        if ($diversity_select == 'wj_nw') {//////White (+ Jews ) v.s. non-White
            $array_result=[];
            $array_result['default_data'] = $ethnic_array['result'] ;
            foreach ($ethnic_array['result'] as $data=>$v)
            {

                if ($data == 'White' || $data == 'Jewish') {
                    $data = 'White (+ Jews )';

                } else {
                    $data = 'non-White';
                }
                $array_result['diversity'][$data]+=$v;


            }
            $array_result['diversity'] = self::normalise_array($array_result['diversity']);
            $array_result['default_data'] = self::normalise_array($array_result['default_data']);
        return $array_result;

        }
       // var_dump($ethnic_array);

        $data = $ethnic_array['result'];

//var_dump($ethnic_array);
        $diversity = $ethnic_array['diversity'];
        $request_type = $ethnic_array['type'];
        $arrayneed_compare = $ethnic_array['arrayneed_compare'];
        $array_request = $ethnic_array['array_request'];
        $all_data = $ethnic_array['all_data'];

        $array_request_actors = $ethnic_array['array_request_actors'];

        if (count($data)) {
            $array_movie_result['movie_id'] = $id;
            $array_movie_result['data'] = $data;
        }

        ///var_dump($all_data['request']);

        //echo '<br><br>';
        ////////////////////////////////////////////////////////////////////////////


        arsort($arrayneed_compare);
        arsort($request_type);
        arsort($data);
        $data = self::normalise_array($data);
        //var_dump($array_request_actors);
        $total_data = $total_data_result;

        if (!$a_sql) {

            $current = self::get_summary_request($all_data['actors_data'], $arrayneed_compare, $request_type, $array_request, 0, 1, 1,$year);
            $total = self::get_summary_request($all_data['actors_data'], $all_data['result'], $all_data['type'], $all_data['request'], $ethnycity, 1,$year);
        }

        return array('data' => $data, 'all_data' => $all_data, 'current' => $current, 'total' => $total, 'diversity' => $diversity, 'default_data' => $ethnic_array['array_default'],'verdict_data'=>$verdict_data);


    }

    public static function normalise_array($array)
    {
        $totalsumm = 0;


        foreach ($array as $index => $val) {
            $totalsumm += $val;
        }
        if ($totalsumm) {
            foreach ($array as $index => $val) {

                $array_result[$index] = round($val * 100 / $totalsumm, 2);


            }

            return $array_result;


        }
        return $array;
    }

    public static function check_actors_to_stars($actors_array, $actor_type = [])
    {

        if (((in_array('s', $actor_type) && !$actors_array['s']) || (in_array('m', $actor_type) && !$actors_array['m'])) && $actors_array['e']) {
            $a = 0;

            if (in_array('s', $actor_type) && !$actors_array['s']) {
                $s = 1;
            }
            if (in_array('m', $actor_type) && !$actors_array['m']) {
                $m = 1;
            }

            foreach ($actors_array['e'] as $id => $val) {

                if ($a < 3 && $s) {
                    $actors_array['s'][$id] = $val;
                    if (isset($actors_array['e'] [$id])) {
                        unset($actors_array['e'] [$id]);
                    }
                }

                if ((($a > 3) || (!$s)) && $a <= 15 && $m) {
                    $actors_array['m'][$id] = $val;
                    if (isset($actors_array['e'] [$id])) {
                        unset($actors_array['e'] [$id]);
                    }
                }

                if ($a > 15) {
                    break;
                }

                $a++;
            }
        }
        return $actors_array;
    }

    public static function get_echnic_custom($array_actors, $ethnic, $diversity_select, $arrayneed_compare, $request_type, $array_request, $all_data = '',$verdict_data =[])
    {

        $array_compare_cache = array('Sadly, not' => 'N/A', '1' => 'N/A', '2' => 'N/A', 'NJW' => 'N/A', 'W' => 'White', 'B' => 'Black', 'EA' => 'Asian', 'H' => 'Latino', 'JW' => 'Jewish', 'I' => 'Indian', 'M' => 'Arab', 'MIX' => 'Mixed / Other', 'IND' => 'Indigenous');

        !class_exists('OptionData') ? include ABSPATH . "analysis/include/option.php" : '';
        $vd_data = unserialize(unserialize(OptionData::get_options('','critic_matic_settings')));
        $verdict_method=0; if ($vd_data["an_verdict_type"]=='w'){$verdict_method=1;}

        global $array_compare;
        $array_default = [];
        $array_type = [];
        $array_result = [];
        $all_data_request = [];
        foreach ($array_actors as $id => $enable) {

            //echo 'prepare '.$id.'<br>';
            ////////sor ethnic
            $a = 0;
            foreach ($ethnic as $type => $array) {
                if ($array[$id]) {
                    //var_dump($array);
                    $data = $array[$id];
                    ///echo '$data='.$data.'<br>';
                    if (!is_numeric($data) && $array_compare_cache[$data] != 'N/A') {

                        if ($array_compare_cache[$data]) {
                            $data = $array_compare_cache[$data];
                        } else if ($array_compare[$data]) {
                            $data = $array_compare[$data];
                        }

                            $ethnic_data = $data;

                        if ($verdict_method)
                        {
                            $data =  $array_compare_cache[$verdict_data[$id]];
                        }
                        //echo '$data =' .$data.' <bR>';
/*
                        if ($diversity_select == 'wj_nw') {//////White (+ Jews ) v.s. non-White

                            $array_default[$data]++;
                            if ($data == 'White' || $data == 'Jewish') {
                                $data = 'White (+ Jews )';

                            } else {
                                $data = 'non-White';
                            }
                            $array_result[$data]++;
                            $array_type[$type]++;
                            break;
                        }
                        else if ($diversity_select == 'w_j_nwj')
                        {///////White (- Jews ) v.s. non-White (+ Jews)

                            if ($data == 'White') {
                                $data = 'White (- Jews )';
                                $array_result[$data]++;
                            } else if ($data == 'Jewish') {
                                $data = 'White (- Jews )';
                                $array_result[$data]--;
                            } else {
                                $data = 'non-White (+ Jews)';
                                $array_result[$data]++;
                            }

                            $array_type[$type]++;
                            break;
                        }
                        else if ($diversity_select == 'wmj_nwm') {
                            if ($type != 'gender') {
                                //////White Male (+ Jews ) v.s. non-Whites ( + Female Whites )
                                $gender = $ethnic['gender'][$id];

                                //echo $type.' '.$data.' ';
                                //echo 'gender: '. $gender . '  <br>';

                                if (($data == 'White' || $data == 'Jewish') && $gender == "Male") {

                                    $data = 'White Male (+ Jews )';

                                } else {

                                    $data = 'non-Whites ( + Female Whites )';
                                }

                                $array_result[$data]++;
                                $array_type[$type]++;

                                break;
                            }
                        }
                        else if ($diversity_select == 'wm_j_nwmj') {
                            if ($type != 'gender') {
                                //////White Male (- Jews ) v.s. non-White Males (+ Jews + Female Whites)
                                $gender = $ethnic['gender'][$id];

                                //echo $type.' '.$data.' ';
                                //echo 'gender: '. $gender . '  <br>';

                                if (($data == 'White') && $gender == "Male") {

                                    $data = 'White Male (- Jews )';
                                    $array_result[$data]++;
                                    $array_type[$type]++;
                                } else {

                                    $data = 'non-Whites ( + Jews + Female Whites )';
                                    $array_result[$data]++;
                                    $array_type[$type]++;
                                }

                                break;
                            }
                        }
                        else */
                            {

                            if ($ethnic_data==$data) {
                                if ($a == 0) {

                                    $array_result[$data]++;
                                    $request_type[$type]++;
                                    $array_request[$type][$data]++;
                                    $arrayneed_compare[$data]++;

                                    $all_data_request['actors_data'][$data][$type][] = $id;

                                    if (!$all_data) {

                                        break;

                                    }
                                }
                                //echo 'result  '.$type.' - '.$data.'<br>';

                                $a++;
                            }

//                            else {
//
//                                $all_data_request['result'][$data]++;
//                                $all_data_request['type'][$type]++;
//                                $all_data_request['request'][$type][$data]++;
//
//
//                                $array_enable = $all_data_request['actors_data'][$data][$type];
//                                if (!$array_enable) $array_enable = [];
//                                if (!in_array($id, $array_enable)) {
//
//                                    $all_data_request['actors_data'][$data][$type][] = $id;
//
//
//                                }
//
//
//                            }

                        }
                    }
                }

            }
        }


        if ($diversity_select == 'diversity') {

            $total_d = 0;

            foreach ($array_result as $index => $summ) {

                $total = $summ;
                if (!$total) $total = 0;

                $total_d += $total * ($total - 1);
                $total_summ += $total;

            }

//echo '$total_summ = '.$total_summ.' $total_d= '.$total_d.'<br>';

            $total_summ_result = 1 - ($total_d / ($total_summ * ($total_summ - 1)));
            $total_summ_result = round($total_summ_result, 2);


//echo '$total_summ_result ='.$total_summ_result.'<br>';

        }
        if (!$total_summ_result) {
            $total_summ_result = 0;
        }

        //var_dump($array_result);
//echo '<br>';

        if ($array_default) {
            $array_default = self::normalise_array($array_default);
        }
        return array('diversity' => $total_summ_result, 'array_default' => $array_default, 'all_data' => $all_data_request, 'array_request' => $array_request, 'total' => count($array_actors), 'type' => $request_type, 'result' => $array_result, 'arrayneed_compare' => $arrayneed_compare);


    }

    public static function get_population_array($type)
    {
        $array_desc = [];
        $sql = "SELECT * FROM `data_population` where `type`='" . $type . "'";
        $rows = Pdo_an::db_results_array($sql);


        if ($type == 'ethnic_desc') {
            foreach ($rows as $r) {
                $array_desc[$r['name']] = array('desc' => $r['Notes'], 'link' => $r['Source']);
            }

            return $array_desc;

        } else {
            return $rows;
        }

    }

    public static function get_summary_request($array_request_actors, $arrayneed_compare, $request_type, $array_request, $enable_ethnycity = '', $enable_actors_link = '', $enable_demograpic = '',$year='')
    {


        $array_desc = self::get_population_array('ethnic_desc');

        $summ = 0;
        $content = '<div id="container_main_movie_graph" class="section_chart section_ethnic"></div>
<table class="tablesorter-blackice"><tr><th class="t_header">Race</th>';

        if (!$arrayneed_compare){return ;}

        foreach ($arrayneed_compare as $race => $count) {
            $content .= '<th>' . ucfirst($race) . '</th>';
        }
        $content .= '<th class="total_column">Total</th><th class="t_small">Visuals</th><th class="t_small">Info</th><tr>';

        $r = 0;
        foreach ($request_type as $name => $val) {
            $content .= '<tr class="row_demograpic">';
            $i = 0;

            foreach ($arrayneed_compare as $race => $count) {
                if ($i == 0) {
                    $content .= '<td>' . ucfirst($name) . '</td>';
                }

                if ($enable_actors_link) {


                    if ($array_request[$name][$race]) {


                        $actors_data = $array_request_actors[$race][$name];
                        $actors_data_string = implode(',', $actors_data);
                        $content .= '<td><a class="actors_link" href="#" data-id="' . $actors_data_string . '">' . $array_request[$name][$race] . '</a></td>';
                    } else {
                        $content .= '<td></td>';
                    }


                } else {
                    $content .= '<td>' . $array_request[$name][$race] . '</td>';
                }


                $i++;
            }

            $content .= '<td>' . $val . '</td>';
            $summ += $val;




            $source = '';

            if ($array_desc[$name]['link']) {
                $source = '<p style="margin-top: 10px"><a target="_blank"  href="' . $array_desc[$name]['link'] . '">Source: '.$array_desc[$name]['link'] .'</a></p>';
            }
            $graph = '';
            $comment=$array_desc[$name]['desc'];
            if ($comment )
            {
                $comment = str_replace("\'","'",$comment);

                $comment_graph='<div class="note"><div class="t_desc"></div><div class="note_show" style="display: none;"><div class="note_show_content"><div>'.$comment.$source.'</div></div></div></div>';
            }
            else
            {
                $comment_graph='';
            }


            $content .= $graph . '<td></td><td class="o_viz">'.$comment_graph.'</td></tr>';

            $r++;

        }

        $data_graph = self::normalise_array($arrayneed_compare);
        $ethnic_graph_data = self::create_pie('Cast Percentages',$data_graph);
        ///footer

        if (!$enable_ethnycity) {
            $content .= '<tr><th class="align_left">Total</th>';
            foreach ($arrayneed_compare as $race => $count) {

                $actors_all_data = '';
                if ($enable_actors_link) {
                    foreach ($array_request_actors[$race] as $ai => $af) {
                        $actors_all_data .= ',' . implode(',',$af);
                    }
                    if ($actors_all_data) {
                        $actors_all_data = substr($actors_all_data, 1);
                    }

                    $content .= '<th><a class="actors_link" href="#" data-id="' . $actors_all_data . '">' . $count . '</a></th>';
                } else {
                    $content .= '<th>' . $count . '</th>';
                }


            }
            $content .= '<th>' . $summ . '</th><th><span class="t_edit"></span></th><th rowspan="2"><a id="op" class="open_demographic open_ul" href="#"></a></th></tr>';


            if ($enable_demograpic) {
                $footer = 'Cast Percentages';

            } else {
                $footer = 'Total Percent';
            }


            $content .= '<tr><th class="align_left">' . $footer . '</th>';


            foreach ($arrayneed_compare as $race => $count) {
                $count_percent = '';
                if ($summ) {
                    $count_percent = round(($count / $summ) * 100, 2) . '<span class="prcnt">%</span>';
                }

                $content .= '<th>' . $count_percent . '</th>';
            }



            $content .= '<th>100<span class="prcnt">%</span></th><th><span class="t_graph ethnic_graph main_ethnic_graph"></span><div style="display: none" class="ethnic_graph_data">'.$ethnic_graph_data.'</div></th></tr>';

            if ($enable_demograpic) {

                $actor_content = self::set_table_ethnic($arrayneed_compare,$year);

                $content .= $actor_content ;


            }

        }

        $content .= '</table>';


//echo $content;
        return $content;
    }

    public static function get_populaton_countries($index,$year, $type, $array_population, $population_prefix, $countries,$prefix,$only_population='')
    {



        foreach ($countries as $innercountry) {

            $at=[];
            $array_countries = array('USA' => 'United States', 'UK' => 'United Kingdom', 'Russia (CIS)' => 'Russia');

            if ($array_countries[$innercountry]) {
                $innercountry = $array_countries[$innercountry];
            }

            if ($type == 'buying_power') {

                $sql = "SELECT *   FROM `data_buying_power`, data_population_country where data_buying_power.cca2=data_population_country.cca2 and  data_population_country.`country_name` = '" . $innercountry . "' limit 1";

            }
            else {
                $sql = "SELECT populatin_by_year, ethnic_array_result  FROM data_population_country  WHERE `country_name` = '" . $innercountry . "' limit 1";
            }
            $r = Pdo_an::db_fetch_row($sql, [], 'array');

            $rows = Pdo_an::db_results_array($sql);

            $year_range_max = 1000;
            $current_year = '';
            foreach ($rows as $r) {


                if ($type == 'buying_power') {

                    $current_population = $r['total'];

                    // echo $current_population.' ';
                } else {

                    $population = $r['populatin_by_year'];
                    if ($population) {
                        $population_array = json_decode($population);

                        foreach ($population_array as $cy => $count) {
                            $year_range = abs($year - $cy);

                            if ($year_range < $year_range_max && $cy <= date('Y', time()) && $count > 0) {
                                $year_range_max = $year_range;
                                $current_year = $cy;
                            }
                        }
                    }
                    $current_population = $population_array->{$current_year};
                }
                if ($only_population)
                {
                    return $current_population;
                }

                if ($current_year)
                {
                    $pname = $innercountry . $population_prefix . ' (' . $current_year . ')';
                }
                else
                {
                    $pname = $innercountry . $population_prefix ;
                }


                $ethnic = $r['ethnic_array_result'];
                if ($ethnic) {
                    $ethnic_array = json_decode($ethnic);
                    foreach ($ethnic_array as $i => $d) {
                        $at[$i] += $d * $current_population;
                    }

                }

            }


            $array_population[$innercountry.$index] = array(
                'name'=>$pname,
                'data' => $at,
                'percent'=>self::normalise_array($at),
                'comment'=>'',
                'prefix'=>$prefix
            );

        }


        return $array_population;
    }


    public static function get_populaton_domestic($year, $type, $array_population, $population_prefix)
    {


        if ($type == 'buying_power') {

            $sql = "SELECT *   FROM `data_buying_power`, data_population_country where data_buying_power.cca2=data_population_country.cca2 and (  `country_name` = 'United States' or `country_name` = 'Canada') limit 2";

        } else {
            $sql = "SELECT ethnic_array_result, populatin_by_year  FROM data_population_country  WHERE `country_name` = 'United States' or `country_name` = 'Canada' limit 2";
        }
        $rows = Pdo_an::db_results_array($sql);

        $year_range_max = 1000;
        $current_year = '';
        foreach ($rows as $r) {


            if ($type == 'buying_power') {

                $current_population = $r['total'];

                // echo $current_population.' ';
            } else {

                $population = $r['populatin_by_year'];
                if ($population) {
                    $population_array = json_decode($population);

                    foreach ($population_array as $cy => $count) {
                        $year_range = abs($year - $cy);

                        if ($year_range < $year_range_max && $cy <= date('Y', time()) && $count > 0) {
                            $year_range_max = $year_range;
                            $current_year = $cy;
                        }
                    }
                }
                $current_population = $population_array->{$current_year};
            }

            if ($current_year)
            {
                $pname = 'Domestic ' . $population_prefix . ' (' . $current_year . ')';
            }
            else
            {
                $pname = 'Domestic ' . $population_prefix ;
            }


            $ethnic = $r['ethnic_array_result'];
            if ($ethnic) {
                $ethnic_array = json_decode($ethnic);
                foreach ($ethnic_array as $i => $d) {
                    $array_population[$pname][$i] += $d * $current_population;
                }

            }

        }





        if (!$type == 'buying_power') {
            $array_population[$pname] = self::normalise_array($array_population[$pname]);
        } else {
            foreach ($array_population[$pname] as $i => $d) {
                $array_population[$pname][$i] = self::k_m_b_generator($d / 100);
            }

        }


        return $array_population;

    }


    public static function get_populaton_custom($index,$year, $type, $array_population,$prefix='',$total_population=1)
    {


        $custom_ethnic_array = array('White', 'Arab', 'Asian', 'Black', 'Indian', 'Indigenous', 'Latino', 'Mixed / Other', 'Jewish');


        if ($type == 'buying_power') {

          $custom_population = self::get_population_array('buying_power');
        }
        else if ($type == 'income') {

            $custom_population = self::get_population_array('income');
        }
        else
        {
            $custom_population = self::get_population_array('population');
        }

        $population_array_custom = [];


        foreach ($custom_population as $r) {
            $population_array_custom[$r['Year']] = $r;
        }


        $year_range_max = 1000;
        $current_year = '';

        foreach ($population_array_custom as $cy => $data) {
            $year_range = abs($year - $cy);

            if ($year_range < $year_range_max && $cy <= date('Y', time())) {
                $year_range_max = $year_range;
                $current_year = $cy;
            }
        }


        $custom_population_data = $population_array_custom[$current_year];


        $custon_name = $custom_population_data['name'];


        $at = [];


        foreach ($custom_ethnic_array as $eth) {

            $number = $custom_population_data[$eth];
            if ($type == 'buying_power') {
                $number = self::to_number($number);
              //  $number= self::k_m_b_generator($number);
            }
            if (!$number)  $number=0;

            if ($index=='us_income')
            {


               $population =  $array_population['us']['data'][$eth];

                $at[$eth] = round($number*$population*$total_population/100,0);


            }
            else
            {
                $at[$eth] =  round($number,2);
            }





        }
         if ($type == 'buying_power') {
            $comment_array = self::get_population_array('buying_power_notes');
        }
         else     if ($index=='us')
         {
             $comment_array = self::get_population_array('population_notes');
         }
        else if ($type)
        {
            $comment_array = $custom_population;
        }



        if ($index=='us')
        {
            $percerted = $at;
        }
        else
        {
            $percerted=self::normalise_array($at);
        }


        $comment = $comment_array[0]['Notes'];
        $array_population[$index] = array(
            'name'=>$custon_name . ' (' . $current_year . ')',
            'data' => $at,
            'percent'=>$percerted,
            'comment'=>$comment,
            'prefix'=>$prefix
        );

        return $array_population;
    }

    public static function get_populaton_world($index,$year, $type, $array_population_result, $population_prefix,$prefix='')
    {
        $custom_ethnic_array = array('White', 'Arab', 'Asian', 'Black', 'Indian', 'Indigenous', 'Latino', 'Mixed / Other', 'Jewish');
        $year_range_max = 1000;
        $current_year = '';
        $array_population=[];

        if ($type == 'buying_power') {

            $sql = "SELECT *   FROM `data_buying_power`, data_population_country where data_buying_power.cca2=data_population_country.cca2 and   data_population_country.`ethnic_array_result` !=''  and data_population_country.populatin_by_year !=''";

        } else {
            $sql = "SELECT ethnic_array_result,populatin_by_year  FROM data_population_country  WHERE `ethnic_array_result` !=''  and populatin_by_year !='' ";
        }

        $rows = Pdo_an::db_results_array($sql);

        foreach ($rows as $r) {

            if ($type == 'buying_power') {
                $current_population = $r['total'];
            } else {
                $population = $r['populatin_by_year'];
                if ($population) {
                    $population_array = json_decode($population);

                    foreach ($population_array as $cy => $count) {
                        $year_range = abs($year - $cy);

                        if ($year_range < $year_range_max && $cy <= date('Y', time()) && $count > 0) {
                            $year_range_max = $year_range;
                            $current_year = $cy;
                        }
                    }
                }
                $current_population = $population_array->{$current_year};
            }

            if ($current_year)
            {
                $pname_w = 'World ' . $population_prefix. ' ('.$current_year.') ';
            }
            else
            {

            }
            if ($type == 'buying_power') {

                $pname_w = 'World Buying Power' . $population_prefix;
            }
            $pname =  $index;

            ///var_dump($current_population);

            $ethnic = $r['ethnic_array_result'];
            if ($ethnic) {
                $ethnic_array = json_decode($ethnic);
                foreach ($ethnic_array as $i => $d) {
                    if( in_array($i,$custom_ethnic_array))  $array_population[$pname][$i] += round($d * $current_population,0);

                }

            }


        }
        if ($index == 'w_bp') {
            $comment_array = self::get_population_array('world_buying_power_notes');
        }
        else  if ($index == 'w')
        {
            $comment_array = self::get_population_array('world_notes');
        }




        $comment = $comment_array[0]['Notes'];

        $array_population_result[$index] = array(
            'name'=>$pname_w,
            'data' =>$array_population[$pname],
            'percent'=>self::normalise_array($array_population[$pname]),
            'comment'=>$comment,
            'prefix'=>$prefix
        );

        return $array_population_result;
    }

    public static function get_actor_type_min()
    {
        return  array('star' => 's', 'main' => 'm', 'extra' => 'e','director'=>'director','writer'=>'writer','cast_director'=>'cast_director','producer'=>'producer');

    }

    public static function get_ethnic_color($selected_color='default')
    {
        if ($selected_color == 'default') {

            $array_ethnic_color = array(

                'White' => '#2b908f',
                'Asian' => '#90ee7e',
                'Black' => '#f45b5b',
                'Indian' => '#7798BF',
                'Indigenous' => '#aaeeee',
                'Jewish' => '#ff0066',
                'Latino' => '#eeaaee',
                'Mixed / Other' => '#55BF3B',
                'Arab' => '#DF5353',

                'Male' => '#2b908f',
                'Female' => '#90ee7e',

                'White (+ Jews )' => '#2b908f',
                'non-White' => '#90ee7e',

                'White (- Jews )' => '#2b908f',
                'non-White (+ Jews)' => '#90ee7e',

                'White Male (+ Jews )' => '#2b908f',
                'non-Whites ( + Female Whites )' => '#90ee7e',

                'White Male (- Jews )' => '#2b908f',
                'non-Whites ( + Jews + Female Whites )' => '#90ee7e',

                'Box Office International' => '#2b908f',
                'Box Office Domestic' => '#90ee7e',

                'Box Office per movie' => '#0006ee',
                'Box Office total' => '#00ee1a'
            );


        }
        else {
            $array_ethnic_color = [];

            $sql = "SELECT * FROM `options` where id =6 limit 1";

            $r = Pdo_an::db_fetch_row($sql);
            $val = $r->val;
            $val = str_replace('\\', '', $val);
            $array_compare_0 = explode("',", $val);
            foreach ($array_compare_0 as $val) {
                $val = trim($val);
                // echo $val.' ';
                $result = explode('=>', $val);
                ///var_dump($result);
                $index = trim(str_replace("'", "", $result[0]));
                $value = trim(str_replace("'", "", $result[1]));
                $index = trim($index);
                $array_ethnic_color[$index] = $value;
            }
        }

        return $array_ethnic_color;
    }

    public static function create_pie($p_name,$data_graph,$prefix='')
    {
        $array_ethnic_color =  self::get_ethnic_color();

        $data_series=[];
        foreach ($data_graph as $race => $count) {
            if (!$count) $count = 0;
            $data_series[] =[
                'name'=> $race ,
                'y' =>  floatval($count) ,
                'color'=> $array_ethnic_color[$race] ,
                'sliced'=> true,
                'selected'=> true
            ];
        }
        $ethnic_graph_data = json_encode(array('name'=>$p_name,'series'=>$data_series,'prefix'=>$prefix));

        return $ethnic_graph_data;
    }
    public static function create_column_single($p_name,$data_graph,$prefix='')
    {

        $array_ethnic_color =  self::get_ethnic_color();

        $data_series=[];

        foreach ($data_graph as $race => $count) {
            if (!$count) $count = 0;


            $data_series[] =[
                'name'=> $race ,
                'y' =>  floatval($count) ,
                'color'=> $array_ethnic_color[$race] ,
            ];

        }

        $ethnic_graph_data = json_encode(array('name'=>$p_name,'series'=>$data_series,'prefix'=>$prefix));

        return $ethnic_graph_data;
    }

    public static function create_column($p_name,$data_graph,$data_ethnic)
    {

        $array_ethnic_color =  self::get_ethnic_color();

        $data_series=[];
        $data_series_ethnic=[];


        foreach ($data_ethnic as $race => $count) {
            if (!$count) $count = 0;
          //   $data_series[] =[$race,floatval($count)];


            $data_series_ethnic[] =[
                'name'=> $race ,
                'y' =>  floatval($count) ,
                'color'=> $array_ethnic_color[$race] ,
            ];

            $count =$data_graph[$race];
            if (!$count) $count = 0;

            $data_series[] =[$race,floatval($count)];

        }


        $ethnic_graph_data = json_encode(array('name'=>$p_name,'series'=>$data_series,'cast'=>$data_series_ethnic));

        return $ethnic_graph_data;
    }

    public static function set_table_ethnic($data, $year = '', $type = '')
    {

        if (!$year) {
            $year = date('Y', time());
        }
        $population_prefix = 'population ';
        if ($type == 'buying_power') {
            $population_prefix = ' ';

        }
//        global $end;
//        if ($end)
//        {
//            $year = $end;
//        }

        //$year = 1980;

        $array_year = [];


        global $country;

      /// $country=['France','USA'];

        // var_dump($country);
        $countries = $country;
        $result_compare = '';

        $data = self::normalise_array($data);
        $array_population = [];

        if (is_string($countries)) {
            $countries[0] = $countries;
        }
/////////get contries




////get domestic population

     // $array_population = self::get_populaton_domestic($year,$type,$array_population,$population_prefix);


///get custom data

       $array_population = self::get_populaton_custom('us',$year,'',$array_population,'');

       $array_population = self::get_populaton_custom('us_bp',$year,'buying_power',$array_population,'$');


        $array_population_us = self::get_populaton_countries('cntr',$year,'',$array_population,' Population ',['USA'],'',1);
        $array_population = self::get_populaton_custom('us_income',$year,'income',$array_population,'$',$array_population_us);

        ////get world population

       $array_population = self::get_populaton_world('w',$year,'',$array_population,'population ');
       $array_population = self::get_populaton_world('w_bp',$year,'buying_power',$array_population,'','$');



        if ($countries) {
            $array_population = self::get_populaton_countries('cntr',$year,'',$array_population,' Population ',$countries,'');
        }

        $array_pname = array('Jewish (Law of Return)' => 'Jewish');

        $array_population_result = [];


        //arsort($data);
        if (is_array($data)) {


            $i = 0;
            $actor_content = '';
            $actor_heder = '';
            $actor_result = '';
            foreach ($data as $name => $summ) {

                $actor_heder .= '<th></th>';
                // $actor_result .= '<td>' . $summ . '%</td>';

                /// echo $name.'-'.$summ.'<br>';

                ///var_dump($array_population);


                foreach ($array_population as $index => $object) {

                    $p_name = $object['name'];
                    $p_data = $object['percent'];


                    foreach ($p_data as $p_data_name => $p_data_val) {

                        if ($p_data_name == $name || $array_pname[$p_data_name] == $name) {

                            $presult = 0;

                            $p_data_val = str_replace('%', '', $p_data_val);

                            if ($p_data_val) {
                                $presult = $summ - $p_data_val;
                            }


                            $array_population_result[$index]['value'][$name][0] = round($p_data_val, 2);
                            $array_population_result[$index]['value'][$name][1] = round($presult, 2);

                        }
                    }

                    if ((!$array_population[$index] && $summ) && !$array_population_result[$index]['value'][$name][1]) {
                        $array_population_result[$index]['value'][$name][1] = round($summ, 2);
                    }

                }


                $i++;

            }
            $result_compare = '';


            foreach ($array_population_result as $index => $p_data_val_main) {


                $comment = $array_population[$index]['comment'];


                $p_name = $array_population[$index]['name'];
                $data_graph = $array_population[$index]['data'];
                $prefix = $array_population[$index]['prefix'];
                $data_graph_percent = $array_population[$index]['percent'];


                if ($array_year[$p_name]) {

                    $p_name = $p_name . ' (' . $array_year[$p_name] . ' )';
                }

                $res_dt = '';
                $res_dtpercent = '';
                foreach ($data as $name => $summ) {
                    //foreach ($p_data_val['value'] as $p_data_name => $p_data_val) {


                    $p_data_val = $p_data_val_main['value'][$name];

                    if (!$p_data_val[0]) {

                        $p_data_val[0] = 0;
                    }


                    if ($p_data_val[0]) {
                        $res_dt .= '<td>' . $p_data_val[0] . '<span class="prcnt">%</span></td>';

                    } else {
                        $res_dt .= '<td></td>';
                    }


                    $cur_prcnt = round($p_data_val[1], 2);
                    if ($cur_prcnt < 0) {
                        $cur_prcnt = '<span class="red">' . $cur_prcnt . '</span>';
                    } else if ($cur_prcnt > 0) {
                        $cur_prcnt = '<span class="green">+' . $cur_prcnt . '</span>';
                    } else if ($cur_prcnt == 0) {
                        $cur_prcnt = '';
                    } else {
                        $cur_prcnt = '<span class="green">' . $cur_prcnt . '</span>';
                    }
                    $res_dtpercent .= '<td>' . $cur_prcnt . '</td>';


                }




                if($index=='us')
                {
                    $ethnic_graph_data = self::create_column_single($p_name, $data_graph, $prefix);
                }
                else
                {
                    $ethnic_graph_data = self::create_pie($p_name, $data_graph, $prefix);
                }

                   $ethnic_graph_column = self::create_column($p_name, $data_graph_percent, $data);


                if ($comment) {
                    $comment_graph = self::to_comments($comment);
                } else {
                    $comment_graph = '';
                }

                if($index=='us')
                {
                    $source_graph = '<td ><span class="t_column ethnic_graph ethnic_graph_column_single"></span><div style="display: none" class="ethnic_graph_data">' . $ethnic_graph_data . '</div></td><td class="o_viz">' . $comment_graph . '</td></tr>';

                }
                else
                {
                    $source_graph = '<td ><span class="t_graph ethnic_graph"></span><div style="display: none" class="ethnic_graph_data">' . $ethnic_graph_data . '</div></td><td class="o_viz">' . $comment_graph . '</td></tr>';

                }

                $source_column = '<td ><span class="t_column ethnic_graph ethnic_graph_column"></span><div style="display: none" class="ethnic_graph_data">' . $ethnic_graph_column . '</div></td><td></td></tr>';


                $result_compare .= '<tr class="actor_data"><td class="align_left">' . $p_name . ' <span class="gray">Percentage</span></td>' . $res_dt . '<td></td>' . $source_graph . '</tr>';
                $result_compare .= '<tr class="actor_data"><td class="align_left">' . $p_name . ' <span class="gray">Representation</span></td>' . $res_dtpercent . '<td></td>' . $source_column . '</tr>';

            }
            ///notes
            //total_notes
            $notes = self::get_population_array('total_notes');

            $comments = '';


            if ($notes[0]) {
                foreach ($data as $race => $count) {

                    $reslt = '';
                    if ($notes[0][$race]) {
                        $reslt = $notes[0][$race];
                        $comments .= '<td>' . self::to_comments($reslt) . '</td>';
                    } else {
                        $comments .= '<td></td>';
                    }

                }
                if ($comments) {
                    $comments = '<tr><td>Notes</td>' . $comments . '<td colspan="3"></td></tr>';

                    $result_compare .= $comments;
                }


            }
        }
    return $result_compare;
}
public  static function to_comments($comment)
{
    $comment_graph='<div class="note nte"><div class="t_desc btn"></div><div class="nte_show"><div class="nte_in"><div class="nte_cnt "><div class="note_show_content">'.$comment.'</div></div></div></div></div></div>';

    return $comment_graph;
}

public static function to_number($data)
{
    $data = trim($data);
    $data = str_replace(',','',$data);
    $data = str_replace(' ','',$data);

    $data = strtolower($data);



    if (strstr($data,'k'))
    {
        $data  =str_replace('k','',$data);
        $data = $data*1000;
    }

    else if (strstr($data,'m'))
    {
        $data  =str_replace('m','',$data);
        $data = $data*1000000;
    }
    else if (strstr($data,'b'))
    {
        $data  =str_replace('b','',$data);
        $data = $data*1000000000;
    }
    else if (strstr($data,'t'))
    {
        $data  =str_replace('t','',$data);
        $data = $data*1000000000000;
    }
    return $data;

}


public static function   k_m_b_generator($num) {
      if ($num > 999 && $num < 99999) {
          return round(($num / 1000),0)." K";
      } else if ($num > 99999 && $num < 999999) {
    return round(($num / 1000),0)." K";
} else if ($num > 999999 && $num < 999999999) {
    return round(($num / 1000000),0)." M";
} else if ($num > 999999999 && $num < 999999999999) {
    return round(($num / 1000000000),0)." B";
}
else if ($num > 999999999999) {
    return round(($num / 1000000000000),2)." T";
}
else {
    return $num;
}
}
}