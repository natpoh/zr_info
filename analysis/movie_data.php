<?php
if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');

//DB config
!defined('DB_HOST_AN') ? include ABSPATH . 'analysis/db_config.php' : '';
//Abstract DB
!class_exists('Pdoa') ? include ABSPATH . "analysis/include/Pdoa.php" : '';

class MOVIE_DATA{


    public static function get_actor_name($id)
    {
        $sql = "SELECT name FROM `data_actors_imdb` where id =".$id;
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


    public static function get_actors_template($movie_id,$actor_type,$ethnycity_string='')
    {

        $actors_array = self::get_actors_from_movie($movie_id, '', $actor_type);

        $actor_type_min = array('star' => 's', 'main' => 'm', 'extra' => 'e', 'director' => 'director', 'writer' => 'writer', 'cast_director' => 'cast_director', 'producer' => 'producer');

        if (is_array($actors_array)) {

            $content_array = [];

            foreach ($actor_type as $type) {

                foreach ($actors_array[$actor_type_min[$type]] as $id => $enable) {

                    $name = self::get_actor_name($id);
                    if ($id) {
                        $e = '';
                        if ($ethnycity_string) {
                            $e = '&e=' . $ethnycity_string;
                        }
                        if ($type=='main')
                        {
                            $type = 'supporting';
                        }

                        $dop_string = '<span class="a_data_n_d">' . str_replace('_', ' ', ucfirst($type)) . '</span>';


                        $actor_cntr = '<div class="card style_1 img_tooltip">
                     <a  class="actor_info" data-id="' . $id . '"  href="#">
                     <div class="a_data_n">' . $name . $dop_string . ' </div>
                     <img loading="lazy" class="a_data_i" src="https://' . $_SERVER['HTTP_HOST'] . '/analysis/create_image.php?id=' . $id . $e . '" />
                     </a><span class="actor_edit actor_crowdsource_container"><a title="Edit Actor data" id="op" data-value="' . $id . '" class="actor_crowdsource button_edit" href="#"></a></span>
                    </div>';

                        $addtime = time();
                        $content_array[$addtime . '_' . $id] = array('pid' => $id, 'content_data' => $actor_cntr);

                    }
                }


            }


        }

        return $content_array;
    }


    public static function get_actors_from_movie($id='',$imdb_id='',$actor_type=[])
    {
        $w='';
        $actor_result=[];
        $actor_types = array('1' => 's', '2' => 'm', '3' => 'e');

        $actor_types_big = array('star' => '1', 'main' => '2', 'extra' => '3');

        $director_types = array(1=>'director', 2 =>'writer',3=> 'cast_director' ,4=>'producer');


        if (!$id && $imdb_id)
        {
            $imdb_id = intval($imdb_id);

            $sql = "SELECT id FROM `data_movie_imdb` where `movie_id` ='" . $imdb_id . "'  limit 1 ";

            $r = Pdo_an::db_fetch_row($sql);
            $id =  $r->id;
        }
        if ($id)
        {

            if ($actor_type)
            {
                foreach ($actor_type as $val)
                {
                    if ($val && ($val!='director' && $val!='writer' && $val!='cast_director' && $val!='producer'))
                    {
                        $w.= "OR `type` = '".$actor_types_big[$val]."' ";
                    }

                }
                if ($w)
                {
                    $w = substr($w,2);
                    $w  =" AND (".$w.") ";
                }



                if (in_array('director',$actor_type) || in_array('writer',$actor_type) || in_array('cast_director',$actor_type) || in_array('producer',$actor_type))
                {
                    $sql = "SELECT * FROM meta_movie_director WHERE mid={$id} ";


                    $r = Pdo_an::db_results_array($sql);
                    foreach ($r as $row)
                    {
                        $actor_result[$director_types[$row['type']]][$row['aid']]=1;
                    }
                }
            }


            if ($w)
            {
            $sql = "SELECT * FROM meta_movie_actor WHERE mid={$id}  {$w} ";
             //echo $sql;
            $r = Pdo_an::db_results_array($sql);
            foreach ($r as $row)
            {
             $actor_result[$actor_types[$row['type']]][$row['aid']]=1;
            }
            }

        }
return $actor_result;
    }


    public static function get_movie_data_from_db($id='', $a_sql = '', $only_etnic = 0, $actor_type = [], $actors_array = [], $diversity_select = "default", $ethnycity = [], $all_data = '')
    {

        $i = $i0 = $i_j = 0;
        $need_request = '';

        $ethnic = [];
        if (!$a_sql) {

            if (!count($actors_array)) {
                $actors_array = self::get_actors_from_movie($id,'',$actor_type);
            }


            $actors_array =self::check_actors_to_stars($actors_array,$actor_type);
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
        }

        else {
            $all_actors[$id] = 1;
            $total_actors_data[$id] = 1;
        }

        $array_convert = array('2' => 'Male', '1' => 'Female', '0' => 'NA');
        foreach ($all_actors as $id => $enable) {
            $sql = "SELECT * FROM `data_actors_meta` where actor_id =" . $id . " ";
            $rows = Pdo_an::db_results_array($sql);

            foreach ($rows  as $r) {

                //if ($r['gender'])
                //{
                 //  echo $r['actor_id'].' - '.$r['gender'].'<br>';

                    $gender_result = $array_convert[$r['gender']];
                    if (!$gender_result)$gender_result='NA';

                    $ethnic['gender'][$r['actor_id']] = $gender_result;
                //}


                if ($r['jew']) $ethnic['jew'][$r['actor_id']] = $r['jew'];
                if ($r['bettaface']) $ethnic['bettaface'][$r['actor_id']] = $r['bettaface'];
                if ($r['surname']) $ethnic['surname'][$r['actor_id']] = $r['surname'];
                if ($r['ethnic']) $ethnic['ethnic'][$r['actor_id']] = $r['ethnic'];
                if ($r['kairos']) $ethnic['kairos'][$r['actor_id']] = $r['kairos'];
                if ($r['crowdsource']) $ethnic['crowd'][$r['actor_id']] = $r['crowdsource'];
            }
        }



        $array_convert_type = array('crowd' => 'crowd', 'ethnic' => 'ethnic', 'jew' => 'jew', 'face' => 'kairos', 'face2' => 'bettaface', 'surname' => 'surname');


        $ethnic_sort = [];
        $result = [];

        if ($diversity_select == 'm_f' && !$a_sql) {
            $result['gender'] = $ethnic['gender'];
        }
        else {
            if ($diversity_select == 'wmj_nwm' || $diversity_select == 'wm_j_nwmj') {
                $ethnic_sort['gender'] = [];
            }


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

        if (!$a_sql) {
            $ethnic = $result;
            if ($diversity_select != 'wj_nw') $diversity_select = '';
        }

        $arrayneed_compare = [];
        $request_type = [];
        $array_request = [];
        $total_data_result = [];


        $ethnic_array = self::get_echnic_custom($total_actors_data, $ethnic, $diversity_select, $arrayneed_compare, $request_type, $array_request, 1);
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

            $current = self::get_summary_request($all_data['actors_data'], $arrayneed_compare, $request_type, $array_request, 0, 1, 1);
            $total = self::get_summary_request($all_data['actors_data'], $all_data['result'], $all_data['type'], $all_data['request'], $ethnycity, 1);
        }

        return array('data' => $data, 'all_data' => $all_data, 'current' => $current, 'total' => $total, 'diversity' => $diversity,'default_data'=>$ethnic_array['array_default']);


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

    public static function   check_actors_to_stars($actors_array,$actor_type=[])
    {

        if (((in_array('s',$actor_type) && !$actors_array['s']) || (in_array('m',$actor_type) && !$actors_array['m'])) && $actors_array['e']) {
            $a = 0;

            if (in_array('s',$actor_type) && !$actors_array['s'])
            {
                $s=1;
            }
            if (in_array('m',$actor_type) && !$actors_array['m'])
            {
                $m=1;
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

public static function get_echnic_custom($array_actors, $ethnic, $diversity_select, $arrayneed_compare, $request_type, $array_request, $all_data = '')
{

    $array_compare_cache = array('Sadly, not'  => 'N/A','1' => 'N/A', '2' => 'N/A', 'NJW' => 'N/A','W' => 'White', 'B' => 'Black', 'EA' => 'Asian', 'H' => 'Latino', 'JW' => 'Jewish', 'I' => 'Indian', 'M' => 'Arab', 'MIX' => 'Mixed / Other', 'IND' => 'Indigenous');


    global $array_compare;
    $array_default=[];
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
                if (!is_numeric($data) &&   $array_compare_cache[$data]!='N/A' ) {

                    if ($array_compare_cache[$data] )
                    {
                        $data = $array_compare_cache[$data];
                    } else if ($array_compare[$data]) {
                        $data = $array_compare[$data];
                    }
                    //echo '$data =' .$data.' <bR>';

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
                    else if ($diversity_select == 'w_j_nwj') {///////White (- Jews ) v.s. non-White (+ Jews)

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
                    else {


                        if ($a == 0) {
                            $array_result[$data]++;
                            $request_type[$type]++;
                            $array_request[$type][$data]++;
                            $arrayneed_compare[$data]++;
                            $all_data_request['actors_data'][$data][$type][] = $id;
                            //echo 'result  '.$type.' - '.$data.'<br>';
                        }
                        $a++;
                        if (!$all_data) {

                            break;

                        }
                        else {

                            $all_data_request['result'][$data]++;
                            $all_data_request['type'][$type]++;
                            $all_data_request['request'][$type][$data]++;


                            $array_enable =$all_data_request['actors_data'][$data][$type];
                            if (!$array_enable)$array_enable=[];
                            if (!in_array($id, $array_enable)) {

                                $all_data_request['actors_data'][$data][$type][] = $id;
                            }


                        }

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

    if ($array_default)
    {
        $array_default = self::normalise_array($array_default);
    }
    return array('diversity' => $total_summ_result, 'array_default'=>$array_default, 'all_data' => $all_data_request, 'array_request' => $array_request, 'total' => count($array_actors), 'type' => $request_type, 'result' => $array_result, 'arrayneed_compare' => $arrayneed_compare);


}


public static function get_summary_request($array_request_actors, $arrayneed_compare, $request_type, $array_request, $enable_ethnycity = '', $enable_actors_link = '', $enable_demograpic = '')
{


    $summ = 0;
    $content = '<table class="tablesorter-blackice"><tr><th>Race</th>';

    foreach ($arrayneed_compare as $race => $count) {
        $content .= '<th>' . ucfirst($race) . '</th>';
    }
    $content .= '<th>Total:</th><tr>';


    foreach ($request_type as $name => $val) {
        $content .= '<tr>';
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

        $content .= '</tr>';


    }

    ///footer

    if (!$enable_ethnycity) {
        $content .= '<tr><th>Total:</th>';
        foreach ($arrayneed_compare as $race => $count) {

            $actors_all_data = '';
            if ($enable_actors_link) {
                foreach ($array_request_actors[$race] as $ai => $af) {
                    $actors_all_data .= ',' . implode($af, ',');
                }
                if ($actors_all_data) {
                    $actors_all_data = substr($actors_all_data, 1);
                }

                $content .= '<th><a class="actors_link" href="#" data-id="' . $actors_all_data . '">' . $count . '</a></th>';
            } else {
                $content .= '<th>' . $count . '</th>';
            }


        }
        $content .= '<th>' . $summ . '</th></tr>';


        if ($enable_demograpic) {
            $footer = 'Demographic:';

        } else {
            $footer = 'Total Percent:';
        }


        $content .= '<tr><th>' . $footer . '</th>';


        foreach ($arrayneed_compare as $race => $count) {
            $count_percent = '';
            if ($summ) {
                $count_percent = round(($count / $summ) * 100, 2) . ' %';
            }

            $content .= '<th>' . $count_percent . '</th>';
        }
        $content .= '<th>100%</th></tr>';

        if ($enable_demograpic) {

            $actor_content = self::set_table_ethnic($arrayneed_compare);
            $buying_power = self::set_table_ethnic($arrayneed_compare, '', 'buying_power');

            $content .= $actor_content . $buying_power;


        }

    }

    $content .= '</table>';
//echo $content;
    return $content;
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

    global $pdo;
    global $country;
    // var_dump($country);
    $countries = $country;
    $result_compare = '';

    $data = self::normalise_array($data);
    $array_population = [];

    if (is_string($countries)) {
        $countries[0] = $countries;
    }

    if ($countries) {
        foreach ($countries as $innercountry) {
            ///   echo $country;

            $array_countries = array('USA' => 'United States', 'UK' => 'United Kingdom', 'Russia (CIS)' => 'Russia');

            if ($array_countries[$innercountry]) {
                $innercountry = $array_countries[$innercountry];
            }
            if ($type == 'buying_power') {

                $sql = "SELECT *   FROM `data_buying_power`, data_population_country where data_buying_power.cca2=data_population_country.cca2 and  data_population_country.`country_name` = '" . $innercountry . "' limit 1";

            }
            else {
                $sql = "SELECT ethnic_array_result  FROM data_population_country  WHERE `country_name` = '" . $innercountry . "' limit 1";
            }
            $r = Pdo_an::db_fetch_row($sql,[],'array');

            if ($type == 'buying_power') {
                $current_population = $r['total'];
            }
            else
            {
                $current_population=1;
            }
            $ethnic = $r['ethnic_array_result'];

            if ($ethnic) {

                $ethnic_array = json_decode($ethnic);

                foreach ($ethnic_array as $i => $d) {
                    $array_population[$innercountry . ' '.$population_prefix][$i] = $d*$current_population/100;
                }
                if ($type == 'buying_power') {
                    foreach ($array_population[$innercountry . ' '.$population_prefix] as $i => $d) {
                        $array_population[$innercountry . ' '.$population_prefix][$i] = self::k_m_b_generator($d);
                    }
                    //    $array_population[$innercountry . ' '.$population_prefix] =self::normalise_array($array_population[$innercountry . ' '.$population_prefix]);

                    // var_dump($array_population);
                }
            }
        }



    }

////get domestic population
    if ($type == 'buying_power') {

        $sql = "SELECT *   FROM `data_buying_power`, data_population_country where data_buying_power.cca2=data_population_country.cca2 and (  `country_name` = 'United States' or `country_name` = 'Canada') limit 2";

    } else {
        $sql = "SELECT ethnic_array_result, populatin_by_year  FROM data_population_country  WHERE `country_name` = 'United States' or `country_name` = 'Canada' limit 2";
    }
    $rows = Pdo_an::db_results_array($sql);

    $year_range_max = 1000;
    $current_year = '';
    foreach ($rows as $r)
    {


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


        $ethnic = $r['ethnic_array_result'];
        if ($ethnic) {
            $ethnic_array = json_decode($ethnic);
            foreach ($ethnic_array as $i => $d) {
                $array_population['Domestic '.$population_prefix][$i] += $d * $current_population;
            }

        }

    }
    if (!$type == 'buying_power')
    {
        $array_population['Domestic '.$population_prefix] = self::normalise_array($array_population['Domestic '.$population_prefix]);
    }
    else
    {
        foreach ($array_population['Domestic '.$population_prefix]  as $i => $d) {
            $array_population['Domestic '.$population_prefix] [$i] = self::k_m_b_generator($d/100);
        }

    }

//        $sql = "SELECT * FROM data_population WHERE `type` = 'percent' ";
//
//        $q = $pdo->prepare($sql);
//        $q->execute();
//        $q->setFetchMode(PDO::FETCH_ASSOC);
//
//        while ($r = $q->fetch()) {
//
//            foreach ($r as $i => $d) {
//                $array_population[$r['name']][$i] = $d;
//            }
//        }
    ////  var_dump($array_population);

    ////get world population
    if ($type == 'buying_power') {

        $sql = "SELECT *   FROM `data_buying_power`, data_population_country where data_buying_power.cca2=data_population_country.cca2 and   data_population_country.`ethnic_array_result` !=''  and data_population_country.populatin_by_year !=''";

    }
    else
    {
        $sql = "SELECT ethnic_array_result,populatin_by_year  FROM data_population_country  WHERE `ethnic_array_result` !=''  and populatin_by_year !='' ";
    }

    $rows = Pdo_an::db_results_array($sql);

    foreach ($rows as $r)
    {

        if ($type == 'buying_power') {
            $current_population = $r['total'];
        }
        else {
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

        $ethnic = $r['ethnic_array_result'];
        if ($ethnic) {
            $ethnic_array = json_decode($ethnic);
            foreach ($ethnic_array as $i => $d) {
                $array_population['World ' . $population_prefix][$i] += $d * $current_population;
            }

        }


    }
    if (!$type == 'buying_power')
    {
        $array_population['World ' . $population_prefix] = self::normalise_array($array_population['World ' . $population_prefix]);
    }
    else
    {
        $array_population['World ' . $population_prefix.' Percent'] = self::normalise_array($array_population['World ' . $population_prefix]);


        if ($array_population['World ' . $population_prefix.' percent'])
        {
        foreach ($array_population['World ' . $population_prefix.' percent']   as $i => $d) {
            $array_population['World ' . $population_prefix.' percent'][$i] = $d.' %';
        }
        }
        if ($array_population['World ' . $population_prefix] ) {
            foreach ($array_population['World ' . $population_prefix] as $i => $d) {
                $array_population['World ' . $population_prefix][$i] = self::k_m_b_generator($d / 100);
            }
        }

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

            foreach ($array_population as $p_name => $p_data) {

                foreach ($p_data as $p_data_name => $p_data_val) {

                    if ($p_data_name == $name || $array_pname[$p_data_name] == $name) {



                        if ($type == 'buying_power') {

                            $array_population_result[$p_name]['value'][$name][0] = $p_data_val;
                        }
                        else
                        {
                            $p_data_val = str_replace('%', '', $p_data_val);
                            $presult = $summ - $p_data_val;
                            $array_population_result[$p_name]['value'][$name][0] = round($p_data_val, 2);
                            $array_population_result[$p_name]['value'][$name][1] = round($presult, 2);
                        }



                    }
                }

                if ((!$array_population[$name] && $summ) && !$array_population_result[$p_name]['value'][$name][1]) {
                    $array_population_result[$p_name]['value'][$name][1] = round($summ, 2);
                }

            }


            $i++;

        }
        $result_compare = '';

///var_dump($array_population_result);

        foreach ($array_population_result as $p_name => $p_data_val_main) {


            $res_dt = '';
            $res_dtpercent = '';
            foreach ($data as $name => $summ) {
                //foreach ($p_data_val['value'] as $p_data_name => $p_data_val) {


                $p_data_val = $p_data_val_main['value'][$name];

                if (!$p_data_val[0]) {

                    $p_data_val[0] = 0;
                }

                if ($type == 'buying_power') {
                    $res_dt .= '<td>' . $p_data_val[0] . '</td>';
                }
                else {
                    $res_dt .= '<td>' . $p_data_val[0] . ' %</td>';
                    $cur_prcnt = round($p_data_val[1], 2);
                    if ($cur_prcnt < 0) {
                        $cur_prcnt = '<span class="red">' . $cur_prcnt . '</span>';
                    } else if ($cur_prcnt > 0) {
                        $cur_prcnt = '<span class="green">+' . $cur_prcnt . '</span>';
                    } else {
                        $cur_prcnt = '<span class="green">' . $cur_prcnt . '</span>';
                    }
                    $res_dtpercent .= '<td>' . $cur_prcnt . '</td>';
                }


            }

            if ($type == 'buying_power') {


                $result_compare .= '<tr class="actor_data"><td>Buying Power: ' . $p_name . '</td>' . $res_dt . '<td></td></tr>';

            }
            else {




                $result_compare .= '<tr class="actor_data"><td>Percent: ' . $p_name . '</td>' . $res_dt . '<td></td></tr>';
                $result_compare .= '<tr class="actor_data"><td>' . $p_name . ' Representation</td>' . $res_dtpercent . '<td></td></tr>';
            }
        }


    }

    return $result_compare;
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