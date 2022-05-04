<?php

if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');


//DB config
!defined('DB_HOST_AN') ? include ABSPATH . 'analysis/db_config.php' : '';
//Abstract DB
!class_exists('Pdoa') ? include ABSPATH . "analysis/include/Pdoa.php" : '';
!class_exists('MOVIE_DATA') ? include ABSPATH . "analysis/movie_data.php" : '';



global $debug;



class BOXDATA{



public static function box_calculate($data_object)
{

    global $debug;
    global $pdo;

    global $selected_color;
    global $array_ethnic_color;
    if(!$array_ethnic_color)$array_ethnic_color = MOVIE_DATA::get_ethnic_color($selected_color);




///////////////////////////////////////////////////setup data //////////////////////

    // var_dump($data_object);
$array_data = $data_object->result_data;


$ethnycity = $data_object->ethnycity;
$start = $array_data['start'];
$end = $array_data['end'];
$join_dop = $array_data['join'];
$idop = $array_data['dop'];
$inflation_array = $data_object->inflation;
$actor_type = $data_object->actor_type;
$display_xa_axis = $data_object->display_xa_axis;
$diversity_select = $data_object->diversity_select;
$idop_yaer = $data_object->idop_yaer;
$array_year_count=[];
    ///////////////////////////////////////////////////setup data //////////////////////


    $main_display_select= $data_object->display_select;
    $display_select= $data_object->ethnic_display_select;


if ($main_display_select == 'ethnicity')
{

if ($debug) {
$array_timer[] = 'ethnicity request  ' .timer_stop_data();
}


$total_movies = 0;
$ethnic = [];
$idop = substr($idop, 4);

$sql = "SELECT * FROM `data_movie_imdb` " . $join_dop . " WHERE  " . $idop . $idop_yaer;
// [Countries] => ["Italy"]

global $debug;

if ($debug) {
    echo $sql . '<br>';
}


$all_actors = [];

$ethnic = [];
$enable_cahe = 0;
$start = $array_data['start'];
$end = $array_data['end'];

$cahe_file = $start . '_' . $end . '_' . md5($idop . implode(' ', $actor_type));

///cache!!

$str = load_file_cache($start, $end, md5($idop . implode(' ', $actor_type)));

if ($str) {
    if ($debug) {
        $array_timer[] = 'cache loaded before ' .timer_stop_data();
    }


    $enable_cahe = 1;
    $ethnic = json_decode($str, true);
    if ($debug) {
        $array_timer[] = 'cache loaded  ' .timer_stop_data();
    }
}


$q = $pdo->prepare($sql);
$q->execute();
$data =  $q->fetchAll(PDO::FETCH_ASSOC);

if ($debug) {
    $array_timer[] = 'after main request  ' .timer_stop_data();
}

$array_years = [];
$array_top_country = [];//////top 10 country

$array_result = [];
$result = [];
$array_movie_bell = [];
$array_movie_bell_total = [];
$total_data = [];
$actor_movies = 0;


////////add filters


include (ABSPATH.'analysis/include/get_data/FilterCrew.php');


$ethnic_sort = FilterCrew::ethnic_sort($ethnycity);


foreach ($data as $i=> $r)
{
    $enable_move =  FilterCrew::check_filters($r,$data_object,$ethnic_sort);

    if (!$enable_move['result'] || $enable_move['result']==0)
    {
//                echo $r['title'].'<br>';
//                var_dump($enable_move);
//                echo '<br>';
        if(isset($data[$i]))
        {
            unset($data[$i]);
        }
    }


}


$all_movies = count($data);
print_timer(array('Total Movies:' => $all_movies));

if ($debug) {
    $array_timer[] = 'before while  ' .timer_stop_data();
}
foreach ($data as $r) {
    $total_actors_data = [];

    $k = $inflation_array[$r['year']];
    if (!$k) $k = 1;
    $array_movie_result = [];

    $release = strtotime($r['release']);

    $array_movie_result['movies_date'] = $release;

    if ($r['box_world'] > $r['box_usa']) {
        $array_movie_result['Box Office International'] = $r['box_world'] - $r['box_usa'];

    } else {
        $array_movie_result['Box Office International'] = 0;
    }
    $array_movie_result['Box Office Domestic'] = $r['box_usa'];
    $array_movie_result['DVD Sales Domestic'] = $r['DVD Sales Domestic'];
    $array_movie_result['title'] = str_replace("'", "\'", $r['title']);
    $array_movie_result['year'] = $r['year'];
    $array_movie_result['movie_budget'] = $r['Production_Budget'];
    $array_movie_result['box'] = $r['box_world'];
    $array_movie_result['Rating'] = $r['rating'];
    $country = $r['country'];
    $country_array = [];
    if (strpos($country, ',')) {
        $country_array = explode(',', $country);
    } else $country_array[0] = $country;
    $country_result = '';
    if ($data_object->country_movie_select) {
        foreach ($data_object->country_movie_select as $selected_country) {
            if (in_array($selected_country, $country_array)) {

                $country_result = $selected_country;
                break;
            }
        }
    }
    else {
        $country_result = $country_array[0];
    }

    $country = $country_result;
    $array_movie_result['movies_country'] = $country_result;


//            /////////////////////////////////////////////////actors/////////////////////////////////////////


    $actors_array=  MOVIE_DATA::get_actors_from_movie($r['id'],'',$actor_type);
    //$actors_array =MOVIE_DATA::check_actors_to_stars($actors_array,$actor_type);
    $actor_type_min = MOVIE_DATA::get_actor_type_min();


    foreach ($actor_type as $val) {
        $prefix = $actor_type_min[$val];
        if ($actors_array[$prefix]) {
            foreach ($actors_array[$prefix] as $id => $val) {
                $total_actors_data[$id] = 1;
                if ($enable_cahe == 0) {
                    $all_actors[$id] = 1;
                }
                $actor_movies++;

            }
        }
    }

    $array_movie_result['actors'] = $total_actors_data;
    $total_data[$r['movie_id']] = $array_movie_result;

}
if ($debug) {

    /// echo 'actor_to_ethnic ' . count($all_actors) . '<br>';

    $array_timer[] = 'after while  ' .timer_stop_data();
}
$request = '';


if ($enable_cahe == 0) {
    $array_convert = array('2' => 'Male', '1' => 'Female', '0' => 'NA');

    foreach ($all_actors as $id => $enable) {
        $sql = "SELECT * FROM `data_actors_meta` where actor_id =" . $id . " ";
        $q = $pdo->prepare($sql);
        $q->execute();
        while ($r = $q->fetch()) {
            if ($r['gender']) $ethnic['gender'][$r['actor_id']] = $array_convert[$r['gender']];
            if ($r['jew']) $ethnic['jew'][$r['actor_id']] = $r['jew'];
            if ($r['bettaface']) $ethnic['bettaface'][$r['actor_id']] = $r['bettaface'];
            if ($r['surname']) $ethnic['surname'][$r['actor_id']] = $r['surname'];
            if ($r['ethnic']) $ethnic['ethnic'][$r['actor_id']] = $r['ethnic'];
            if ($r['kairos']) $ethnic['kairos'][$r['actor_id']] = $r['kairos'];
        }
    }

    $ethnic_string = json_encode($ethnic);
    save_file_cache($cahe_file, $ethnic_string);

}


print_timer(array('Total Total Actors:' => $actor_movies));


if ($debug) {
    $array_timer[] = 'after ethnic request  ' .timer_stop_data();
}

/////////////////////////////////////////////////actors/////////////////////////////////////////
//var_dump($ethnic);


$array_convert_type = MOVIE_DATA::get_array_convert_type();


$ethnic_sort = [];
$result = [];

if ($diversity_select == 'm_f') {


    $result['gender'] = $ethnic['gender'];


} else {
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

$ethnic = $result;


if ($debug) {
    $array_timer[] = 'after ethnic sort  ' .timer_stop_data();
}
$arrayneed_compare = [];

$request_type = [];
$array_request = [];
$total_data_result = [];


// var_dump($total_data);


//print_timer(array('$total_data:' => count($total_data)));
$r =0;
foreach ($total_data as $movie_id => $array_movie_result) {


    $ethnic_array = MOVIE_DATA::get_echnic_custom($array_movie_result['actors'], $ethnic, $diversity_select, $arrayneed_compare, $request_type, $array_request);


    if ($diversity_select == 'diversity' && $display_select != 'ethnicity') {
        $data = [];
        $diversity = $ethnic_array['diversity'];
        ///echo ' diversity= '.$diversity.'<br>';
        ///
        if (is_nan($diversity)) {
            $diversity = 0;

        }
        $data['Diversity Index'] = $diversity;
        $array_movie_result['data'] = $data;

    } else {

        $data = $ethnic_array['result'];
    }


    $request_type = $ethnic_array['type'];
    $arrayneed_compare = $ethnic_array['arrayneed_compare'];
    $array_request = $ethnic_array['array_request'];

    if (count($data)) {

        //  echo $movie_id.'<br>';
        // var_dump($data);
        $array_movie_result['movie_id'] = $movie_id;
        $array_movie_result['data'] = $data;
        $total_data_result[$movie_id] = $array_movie_result;
    }
    else
    {
        $r++;
    }

    //var_dump($ethnic_array);

    //echo '<br><br>';
    ////////////////////////////////////////////////////////////////////////////
}
//echo '$r='.$r.'<br>';

arsort($arrayneed_compare);
arsort($request_type);


if ($debug) {
    $array_timer[] = 'after first forach  ' .timer_stop_data();


}

$total_data = $total_data_result;

echo MOVIE_DATA::get_summary_request($total_data, $arrayneed_compare, $request_type, $array_request, 0, 0, 0);

//$actor_content = set_table_ethnic($array_movie_result['data'], $country);
//echo $actor_content;

$all_movies_total= count($total_data);

if ($debug)
{
    print_timer(array('Total Movies result:' => $all_movies_total));
}



foreach ($total_data as $movie_id => $array_movie_result) {

       $total_movies++;
    /////////////////////////////////////////////////////////////////////////
    $array_years = [];
    $yaer = $array_movie_result['year'];

    $array_years[$yaer][$movie_id] = $array_movie_result;

    $r = $array_movie_result;


    //// var_dump($ethnic_array);


    $conent = '';

    $data = $array_movie_result['data'];

    if ($diversity_select != 'diversity') {
        $data = normalise_array($data);
    }


    //var_dump($r);

    if ($_POST['oper'] === 'get_movie_cast_data_total') {

        if (is_array($data)) {

            //  $data= normalise_array($data);


            foreach ($data as $name => $summ) {

                $array_movie_bell[$name] += $summ;
            }
        }

    }
    else if ($display_select == 'bellcurve') {
        ///var_dump($data);

        $array_names = [];
        if (is_array($data)) {
            foreach ($data as $name => $summ) {


                if (($diversity_select == 'm_f' && ($name != 'NA')) || $diversity_select != 'm_f') {


                    if ($display_xa_axis == 'Box Office International' || $display_xa_axis == 'box_usa' || $display_xa_axis == 'DVD Sales Domestic') {

                        $box = $r[$display_xa_axis];
                    } else if ($display_xa_axis == 'Box Office Profit actual') {

                        $box = ($array_movie_result['box'] - ($r['movie_budget'] * 2.5)) / 2;

                    }

                    else {

                        $box = $array_movie_result['box'];
                    }

                    if ($display_xa_axis == 'Movie release date') {
                        $array_movie_bell[$name][$r['movie_id']]['x'] = $array_movie_result['movies_date'];
                    }
                    else if ($display_xa_axis == 'Rating') {
                        $rating = $array_movie_result['Rating'];
                        if (!$rating)$rating=0;
                        $array_movie_bell[$name][$r['movie_id']]['x'] = $rating;
                    }
                    else {
                        $array_movie_bell[$name][$r['movie_id']]['x'] = $box * $k;
                    }

                    $array_movie_bell[$name][$r['movie_id']]['y'] = $summ;
                    $array_movie_bell[$name][$r['movie_id']]['title'] = $array_movie_result['title'];
                    $array_movie_bell[$name][$r['movie_id']]['date'] = $array_movie_result['movies_date'];
                    if (!in_array($name, $array_names)) {
                        $array_names[] = $name;
                    }

                    $array_movie_bell_total[$name][0] += $box * $k;
                    $array_movie_bell_total[$name][1]++;
                }
            }
        }
    }
    else if ($display_select == 'scatter' || $display_select == 'regression') {

        if (is_array($data)) {
            foreach ($data as $name => $summ) {

                if ($display_xa_axis == 'Box Office International' || $display_xa_axis == 'box_usa' || $display_xa_axis == 'DVD Sales Domestic') {
                    $box = $r[$display_xa_axis];
                } else if ($display_xa_axis == 'Box Office Profit actual') {
                    $box = ($array_movie_result['box'] - ($r['movie_budget'] * 2.5)) / 2;

                    ///   echo $array_movie_result['box'].' - '.$r['movie_budget'].' = '.$box.'<br>';

                }
                else {
                    $box = $array_movie_result['box'];
                }


                if ($display_xa_axis == 'Movie release date') {
                    $array_movie_bell[$name][$r['movie_id']]['y'] = ($array_movie_result['movies_date']) . '000';
                }

                else if ($display_xa_axis == 'Rating') {
                    $rating = $array_movie_result['Rating'];
                    if (!$rating)$rating=0;
                    $array_movie_bell[$name][$r['movie_id']]['y'] = $rating;
                }
                else {

                    $array_movie_bell[$name][$r['movie_id']]['y'] = $box * $k;
                }

                $array_movie_bell[$name][$r['movie_id']]['x'] = $summ;
                $array_movie_bell[$name][$r['movie_id']]['title'] = $array_movie_result['title'];
                $array_movie_bell[$name][$r['movie_id']]['date'] = date('d.m.Y', $array_movie_result['movies_date']);
            }
        }
    }
    else if ($display_select == 'plurality_bellcurve') {

        if ($display_xa_axis == 'Box Office International' || $display_xa_axis == 'Box Office Domestic' || $display_xa_axis == 'DVD Sales Domestic') {

            $box = $r[$display_xa_axis];
        } else if ($display_xa_axis == 'Box Office Profit actual') {

            $box = ($array_movie_result['box'] - ($r['movie_budget'] * 2.5)) / 2;


        } else {

            $box = $array_movie_result['box'];
        }


        if ($display_xa_axis == 'Movie release date') {
            $box = ($array_movie_result['movies_date']) . '000';
        }
        else if ($display_xa_axis == 'Rating') {
            $rating = $array_movie_result['Rating'];
            if (!$rating)$rating=0;
            $box = $rating;
            $k=1;
        }

        else {

            $box = $box * $k;
        }


        if (is_array($data)) {

            $array_result_pl = [];
            $ki = 0;
            $index = '';
            $maxsumm = 0;
            foreach ($data as $name => $summ) {
                if ($ki == 0) {
                    $index = $name;
                    $maxsumm = $summ;
                    $array_result_pl[$ki][0] = $name;
                    $array_result_pl[$ki][1] = $summ;
                } else if ($maxsumm == $summ && $maxsumm) {
                    $array_result_pl[$ki][0] = $name;
                    $array_result_pl[$ki][1] = $summ;
                }
                /// $conent .= $name . ': ' . $summ . ' %<br>';
                $ki++;

                $array_movie_bell_total[$name][0] += $box * $k;
            }

            foreach ($array_result_pl as $index => $val) {
                /// $result[$val[0]] .= "{ x: " . $box   . ", y: " .$val[1] . ",  date: '" . date('d.m.Y', $array_movie_result['movies_date']) . "',title:'" . $array_movie_result['title'] . "',content:'" . $conent . "',movie_id:'" . $r['movie_id'] . "' },";

                $array_movie_bell[$val[0]][$r['movie_id']]['y'] = $box;
                $array_movie_bell[$val[0]][$r['movie_id']]['title'] = $array_movie_result['title'];
                $array_movie_bell[$val[0]][$r['movie_id']]['x'] = $val[1];
                $array_movie_bell[$val[0]][$r['movie_id']]['date'] = $array_movie_result['movies_date'];
                if (!in_array($val[0], $array_names)) {
                    $array_names[] = $val[0];
                }
            }
        }
    } 
    else if ($display_select == 'bubble') {


        if ($display_xa_axis == 'Box Office International' || $display_xa_axis == 'Box Office Domestic' || $display_xa_axis == 'DVD Sales Domestic') {

            $box = $r[$display_xa_axis];
        } else if ($display_xa_axis == 'Box Office Profit actual') {

            $box = ($array_movie_result['box'] - ($r['movie_budget'] * 2.5)) / 2;


        } else {

            $box = $array_movie_result['box'];
        }


        if ($display_xa_axis == 'Movie release date') {
            $box = ($array_movie_result['movies_date']) . '000';
        } else {

            $box = $box * $k;
        }


        if (is_array($data)) {

            $array_result_pl = [];
            $ki = 0;
            $index = '';
            $maxsumm = 0;
            foreach ($data as $name => $summ) {
                if ($ki == 0) {
                    $index = $name;
                    $maxsumm = $summ;
                    $array_result_pl[$ki][0] = $name;
                    $array_result_pl[$ki][1] = $summ;
                } else if ($maxsumm == $summ && $maxsumm) {
                    $array_result_pl[$ki][0] = $name;
                    $array_result_pl[$ki][1] = $summ;
                }
                $conent .= $name . ': ' . $summ . ' %<br>';
                $ki++;
            }

            foreach ($array_result_pl as $index => $val) {
                $result[$val[0]] .= "{ x: " . $box . ", y: " . $val[1] . ",  date: '" . date('d.m.Y', $array_movie_result['movies_date']) . "',title:'" . $array_movie_result['title'] . "',content:'" . $conent . "',movie_id:'" . $r['movie_id'] . "' },";

            }


        }


    } 
    else if ($display_select == 'ethnicity') {


        $box = 0;

        if ($display_xa_axis == 'Box Office International' || $display_xa_axis == 'Box Office Domestic' || $display_xa_axis == 'DVD Sales Domestic') {

            $box = $r[$display_xa_axis];


        } else if ($display_xa_axis == 'Box Office Profit actual') {

            $box = ($r['box'] - ($r['movie_budget'] * 2.5)) / 2;


        } else {

            $box = $r['box'];
        }


        $box = $box * $k;
        if ($display_xa_axis == 'Rating') {
            $count = count($data);

            $total_data_count =count ($total_data);

            if (!$count)$count=1;
            $rating = $array_movie_result['Rating'];
            if (!$rating)$rating=0;

            $box= $rating/$count;
        }

        if (is_array($data)) {

            ///  echo 'ok <br>';

            //  $data= normalise_array($data);
            //var_dump($data);
            $array_year_count[$r['year']]++;
            foreach ($data as $name => $summ) {
                $array_result[$r['year']][$name] += $summ;

                $array_movie_bell[$r['year']] += $box;



            }
        }


    }

    else if ($display_select == 'performance_country') {


        $array_compare_country = array('United Kingdom' => 'UK');


        $box_domestic = $r['Box Office Domestic'];
        $box_internal = $r['Box Office International'];
        $box_country = $r['Box Office Country'];


        //////////domestic
        $dcntr = $r['movies_country'];
         //echo 'dcntr = '.$dcntr.'<br>';


        if ($box_domestic) {
            $array_result[$dcntr] += $box_domestic * $k;
            $array_top_country[$dcntr]['box'] += $box_domestic * $k;
            $array_top_country[$dcntr]['count'] += 1;
            if (is_array($data)) {
                foreach ($data as $name => $summ) {
                    $array_top_country[$dcntr]['race'][$name] += $summ;

                }
            }

        }


    }


}
//var_dump($array_top_country);

if ($debug) {
    $array_timer[] = 'foreach  ' .timer_stop_data();
}


if ($_POST['oper'] === 'get_movie_cast_data_total') {
    return;
}
   // var_dump($array_result);
self::prepare_graph($display_select,$diversity_select,$array_movie_bell,$display_xa_axis,$array_year_count,$array_ethnic_color,$array_movie_bell_total,$result,$array_result,$array_top_country);



if ($debug) {
    $array_timer[] = 'before end  ' .timer_stop_data();

    echo '<br>';
    print_timer($array_timer);

}



}

}

public static function prepare_graph($display_select,$diversity_select,$array_movie_bell,$display_xa_axis,$array_year_count,$array_ethnic_color,$array_movie_bell_total,$result,$array_result,$array_top_country)
{
    $result_data='';


if ($display_select == 'ethnicity') {


    $array_temp = [];

    ksort($array_result);



    foreach ($array_result as $year => $data) {


        if ($diversity_select == 'diversity') {
            $total_summ = 0;
            $total_d = 0;

            foreach ($data as $index => $summ) {

                $total = $summ;
                if (!$total) $total = 0;

                $total_d += $total * ($total - 1);
                $total_summ += $total;

            }
            //var_dump($data);

            // echo '$total_summ = '.$total_summ.' $total_d= '.$total_d.'<br>';

            $total_summ_result = 1 - ($total_d / ($total_summ * ($total_summ - 1)));
            $total_summ_result = round($total_summ_result, 2);

            //   echo '$total_summ_result ='.$total_summ_result.'<br>';

            $array_temp['Diversity Index'][$year] = $total_summ_result;
        } else {
            ///  var_dump($data);
            arsort($data);
            $data = normalise_array($data);
            foreach ($data as $name => $val) {

                $array_temp[$name][$year] = $val;

            }
        }

    }
    
    $persition=0;

    foreach ($array_temp as $name => $data) {
        //$data0 = normalise_array($data);
        //echo 'name= '.$name.'<br>';
        // print_timer($data);
        $result_in = '';

        foreach ($data as $Year => $summ) {
            if ($summ == 0) {
                $summ = '0.01';
            }

            if ($diversity_select == 'diversity') {
                $box = $array_movie_bell[$Year];
                $result_in .= "{ x: " . $Year . ", y: " . $summ . ",totalbox:" . $box . ",percent:" . $summ . "},";

            } else {

                $box = $array_movie_bell[$Year];
                if ($display_xa_axis == 'Rating') {
                    $movies_count =$array_year_count[$Year];

                    if (!$movies_count)$movies_count=1;
                    $box =   $array_movie_bell[$Year]/$movies_count;
                    $persition = 2;
                }
                $rs = round($summ, 2);
                $smm = 0;
                if ($rs) {
                    $smm = round(($box * $rs) / 100, $persition);
                }

                $result_in .= "{ x: " . $Year . ", y: " . $smm . ",totalbox:" . $box . ",percent:" . $rs . "},";
            }
        }

        //  $result_in.= $x . ',';
        $result_data .= "{
                  name: '" . $name . "',
                    color: '" . $array_ethnic_color[$name] . "', 
                 data: [" . $result_in . "]
                 },";

    }

}
else if ($display_select == 'performance_country') {
    $result_in = '';

    arsort($array_result);



    uksort($array_top_country, function ($a, $b) use ($array_result) {
        return $array_result[$b] - $array_result[$a];
    });
    //   var_dump($array_top_country);

    $i = 0;
    $race_array = [];


    foreach ($array_top_country as $country_name => $data0) {

        $race = $data0['race'];


        $race = normalise_array($race);
        arsort($race);
        foreach ($race as $name => $count) {

            /// echo $country_name.' '.$name.' '.$count.'<br>';


            $race_array[$name][$country_name] += $count;
        }

    }
    echo '<br>$race_array<br>';
//var_dump($race_array);
    $array_all_country = [];


    foreach ($array_top_country as $country_name => $data0) {


        $box_office = $data0['box'];
        $movie_count = $data0['count'];
        if (!$movie_count) $movie_count = 0;

        ///$result_in .= "{ x: '" . $country_name . "', y: " . $box_office . "},";
        $array_all_country[] = $country_name;

        $categories .= "'" . $country_name . "',";

        $result_in .= $box_office . ",";

        $result_movie .= round($box_office / $movie_count, 0) . ",";

        $i++;

    }

    $result_data .= "{
             name: 'Box Office total',
             color: '" . $array_ethnic_color['Box Office total'] . "',
        type: 'spline',
         zIndex: 2,  
        data: [" . $result_in . "],
                marker: {
              radius: 8
        },
         lineWidth: 4,
         yAxis: 1, 
        tooltip: {
             valuePrefix: '$ '
        }} 
        ,{
        name: 'Box Office per movie',
        color: '" . $array_ethnic_color['Box Office per movie'] . "',

        type: 'spline',
       zIndex: 1,
        lineWidth: 4,
        data: [" . $result_movie . "],
        marker: {
              radius: 8
        },

        tooltip: {
            valuePrefix: '$ '
        }

    },";

    $i = 0;


    // var_dump($array_all_race);

    foreach ($race_array as $race => $data_race) {

        ///   var_dump($data_race);
        $result_race = '';
        foreach ($array_all_country as $country_name) {

            /// echo $country_name.'<br> ';


            $count = $data_race[$country_name];
            if (!$count) {
                $count = 0;
            }
            $result_race .= $count . ",";
        }


        $result_data .= "{
                     name: '" . $race . "',
        type: 'column',
        yAxis: 2,
         zIndex: -1,
          color: '" . $array_ethnic_color[$race] . "',
         data: [" . $result_race . "],
        tooltip: {
                     valueSuffix: ' %',
                    
        }

    }, ";

        $i++;
    }

//echo $result_data;
}
else if ($display_select == 'regression') {


    $n = 0;
    foreach ($array_movie_bell as $name => $data0) {
        $regression = '';
        $result_in = '';
        foreach ($data0 as $movie_id => $data) {

            $regression .= '[' . $data['y'] . ',' . $data['x'] . '],';

            $result_in .= "{ x: " . $data['y'] . ", y: " . $data['x'] . ", title:'" . $data['title'] . "',movie_id:'" . $movie_id . "', date:'" . $data['date'] . "' },";
            //  $result_in.= $x . ',';
        }

        $result_data .= "{
                  name: '" . $name . "',
                    type:'scatter',
                     marker: {
                            radius: 3,
                        
                        },
                       color: '" . $array_ethnic_color[$name] . "', 
                  turboThreshold:0,
                  data: [" . $result_in . "],
     
                  }, 
                
                  {
                  name: '" . $name . " Regression',
                  type:'spline',
                            color: '" . $array_ethnic_color[$name] . "',
                    turboThreshold:0,
                  data: getregression([" . $regression . "],'" . $name . " Regression')},               
                  ";


        $n++;
///echo $name.'<br>';
    }
}
else if ($display_select == 'scatter') {


    foreach ($array_movie_bell as $name => $data0) {

        $result_in = '';
        foreach ($data0 as $movie_id => $data) {

            $result_in .= "{ x: " . $data['y'] . ", y: " . $data['x'] . ", title:'" . $data['title'] . "',movie_id:'" . $movie_id . "', date:'" . $data['date'] . "' },";
            //  $result_in.= $x . ',';
        }

        $result_data .= "{
                  name: '" . $name . "',
                  color: '" . $array_ethnic_color[$name] . "', 
                  turboThreshold:0,
                         marker: {
                            radius: 3,
                        
                        },
                  data: [" . $result_in . "]},";


///echo $name.'<br>';
    }
}
else if ($display_select == 'bellcurve' || $display_select == 'plurality_bellcurve') {

    $result_data_bell = '';
    $result_data = '';
    $i = 1;

    /// var_dump($array_movie_bell);

    foreach ($array_movie_bell as $name => $data0) {
////$array_movie_bell[$r['movie_id']][$name]['x']

        $result_in = '';


        foreach ($data0 as $movie_id => $data) {


            $result_in .= "{ x: " . $data['y'] . ", y: " . $data['x'] . ",z:" . ($array_movie_bell_total[$name][0]) . ",  date: '" . date('d.m.Y', $data['date']) . "',title:'" . $data['title'] . "',movie_id:'" . $movie_id . "' },";

            ////$result_in_bell .= $data['y'] . ',';

        }

        if (count($data0) > 1) {

            $result_data_bell .= "{
                        name: '" . $name . " ',
                        type: 'bellcurve',
                        xAxis: 0,
                        yAxis: 1,
                           marker: {
                        enabled: false
                        },
                        baseSeries: '" . $name . "',
                       zIndex: -1,
                        pointsInInterval: pointsInInterval,
                      color: '" . $array_ethnic_color[$name] . "', 
        intervals: 4,
       /// marker: {            enabled: true        }
                       
                    },";

            $result_data .= "{
                    id: '" . $name . "',
                  name: '" . $name . "',
                     type: 'scatter',
                      color: '" . $array_ethnic_color[$name] . "', 
                  turboThreshold:0,
    
                        visible :false,
                  data: [" . $result_in . "],
                  
                  },";

/// $result_remove .= "chart.series['".$name."'].remove(true);";
            //  $i++;

        }
        $i += 1;
    }


    /// echo $result_data;

}
else if ($display_select == 'bubble') {
    ///echo 'total movies: ' . $total_movies . '<br>';

    foreach ($result as $index => $item) {

        $result_data .= "{
                  name: '" . $index . "',
        color: '" . $array_ethnic_color[$index] . "', 
                  turboThreshold:0,
                  data: [" . $item . "]},";

    }
}


    ////////graph data



    self::graph_data($display_select,$result_data,$display_xa_axis,$result_data_bell,$categories);

}

public static function graph_data($display_select='',$result_data='',$display_xa_axis='',$result_data_bell='',$categories='')
{
    if ($display_select == 'performance_country') {
        ?>


        <link rel="stylesheet" href="https://netdna.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.css">

        <script type="text/javascript">
            $(document).ready(function () {


                var chart = Highcharts.chart('chart_div', {
                    chart: {
                        //zoomType: 'x',
                        ///styledMode: true,
                        panning: true,

                    },

                    title: {
                        text: 'Box Office'
                    },
                    legend: {
                        maxHeight: 70,
                    },
                    xAxis: [{
                        categories: [<?php echo $categories; ?>],
                        crosshair: true,
                        min: 0,
                        max: 12,

                        scrollbar: {
                            enabled: true
                        },
                        tickLength: 0
                    }],
                    yAxis: [{ // Primary yAxis
                        gridLineWidth: 0,
                        title: {
                            text: 'Box Office per movie',

                        },
                        opposite: true

                    }, { // Secondary yAxis
                        gridLineWidth: 0,
                        title: {
                            text: 'Box Office total',
                        },

                    }, { // Tertiary yAxis
                        gridLineWidth: 0,
                        title: {
                            text: '%',

                        },
                        visible: false,
                        opposite: true,
                        min: 0, max: 100,
                    }],
                    tooltip: {
                        shared: true,

                        plotOptions: {
                            column: {
                                stacking: 'normal'
                                ///  stacking: 'percent'
                            },


                        }

                    },

                    plotOptions: {
                        column: {
                            stacking: 'normal'
                            ///  stacking: 'percent'
                        },
                        series: {
                            // pointPadding: 0, // Defaults to 0.1
                            groupPadding: 0.02,// Defaults to 0.2
                            point: {
                                events: {
                                    click: function (e) {

                                        var category = e.point.category;
                                        var data = get_data();

                                        var whait_html = '<div class="cssload-circle">\n' +
                                            '\t\t<div class="cssload-up">\n' +
                                            '\t\t\t\t<div class="cssload-innera"></div>\n' +
                                            '\t\t</div>\n' +
                                            '\t\t<div class="cssload-down">\n' +
                                            '\t\t\t\t<div class="cssload-innerb"></div>\n' +
                                            '\t\t</div>\n' +
                                            '</div>';

                                        $('div.footer_table_result').html(whait_html);

                                        $.ajax({
                                            type: "POST",
                                            url: "get_data.php",

                                            data: ({
                                                oper: 'get_chart_by_country',
                                                id: category,

                                                data: JSON.stringify(data),
                                                cat: $('.actos_range_category').val()
                                            }),
                                            success: function (html) {


                                                $('.footer_table_result').html('<div class="clear_table"></div><div class="movie_content" id="' + category + '">' + html + '</div>');
                                                get_ajax_cast_total(category, 'country');

                                            }
                                        });


                                    }
                                }
                            }
                        },

                    },


                    series: [<?php echo $result_data; ?>]
                });


                document.querySelectorAll('#button-row button').forEach(function (button) {
                    button.addEventListener('click', function () {
                        chart.series[0].update({
                            type: button.className.split('-')[0]
                        });
                        chart.series[1].update({
                            type: button.className.split('-')[0]
                        });
                    });
                });


            });


        </script>
        <div id="button-row">
            <button class="spline-chart"><i class="fa fa-line-chart"></i></button>
            <button class="areaspline-chart"><i class="fa fa-area-chart"></i></button>

        </div>

        <?php


    }
    else if ($display_select == 'bellcurve' || $display_select == 'plurality_bellcurve')
    {
        $array_names = [];

        ?>


        <script type="text/javascript">

            var pointsInInterval = 5;

            var chart = Highcharts.chart('chart_div', {

                title: {
                    <?php if ($display_select == 'bellcurve') {
                        echo "text: 'Bell curve'";
                    } else if ($display_select == 'plurality_bellcurve') {
                        echo "text: 'Plurality Bell curve'";
                    } ?>


                },

                chart: {
                    zoomType: 'xy',
                    ///styledMode: true,
                    /*
                    events: {
                        load: function () {
                            var current = this;

                            for (i = 0; i < <?php echo count($array_names); ?>; i++) {
                                Highcharts.each(current.series[i].data, function (point, i) {
                                    ////  console.log(point, i);
                                    var labels = ['4?', '3?', '2?', '?', '?', '?', '2?', '3?', '4?'];
                                    if (i % pointsInInterval === 0) {
                                        point.update({
                                            color: 'black',
                                            dataLabels: {
                                                enabled: true,
                                                format: labels[Math.floor(i / pointsInInterval)],
                                                overflow: 'none',
                                                crop: false,
                                                y: -2,
                                                style: {
                                                    fontSize: '13px'
                                                }
                                            }
                                        });
                                    }
                                });

                            }

                        }
                    }
                    */
                },
                xAxis: [{
                    title: {

                        <?php if ($display_select == 'bellcurve') {
                            echo "text: 'Percent'";
                        } else if ($display_select == 'plurality_bellcurve') {
                            echo "text: '" . $display_xa_axis . "'";
                        } ?>


                    },

                    <?php if ($display_xa_axis == 'Movie release date') { ?>
                    type: 'datetime',

                    <?php } ?>
                }]
                ,

                yAxis: [{
                    title: {
                        <?php if ($display_select == 'bellcurve') {
                            echo "text: '" . $display_xa_axis . "'";
                        } ?>
                    }
                },
                    {
                        title: {text: 'Bell curve'},
                        visible: false,
                    }
                ],
                plotOptions: {
                    series: {
                        visible: false,

                        cursor: 'pointer',
                        point: {
                            events: {
                                click: function (e) {
                                    var m_id = e.point.movie_id;
                                    if (m_id) {
                                        var data = get_data();

                                        var whait_html = '<div class="cssload-circle">\n' +
                                            '\t\t<div class="cssload-up">\n' +
                                            '\t\t\t\t<div class="cssload-innera"></div>\n' +
                                            '\t\t</div>\n' +
                                            '\t\t<div class="cssload-down">\n' +
                                            '\t\t\t\t<div class="cssload-innerb"></div>\n' +
                                            '\t\t</div>\n' +
                                            '</div>';

                                        $('div.footer_table_result').html(whait_html);

                                        $.ajax({
                                            type: "POST",
                                            url: "get_data.php",

                                            data: ({
                                                oper: 'movie_data',
                                                id: m_id,

                                                data: JSON.stringify(data),
                                                cat: $('.actos_range_category').val()
                                            }),
                                            success: function (html) {
                                                $('.footer_table_result').html('<div class="clear_table"></div><div class="movie_content" id="' + m_id + '">' + html + '</div>');

                                                check_trailers(m_id);
                                            }
                                        });


                                    }
                                }
                            }
                        }

                    },
                    bellcurve: {
                        visible: true,
                        tooltip: {
                            useHTML: true,
                            headerFormat: '<p>{series.name}</p><br>',

                            <?php if ($display_select == 'bellcurve') {
                                echo "pointFormat:   '<p>{point.x:.0f}%</p>',";
                            } else if ($display_select == 'plurality_bellcurve') {
                                if ($display_xa_axis == 'Movie release date') {
                                    echo "pointFormat:'<p>%  {point.y:.0f}</p>',";
                                } else {
                                    echo "pointFormat:'<p>" . $display_xa_axis . "  $  {point.x:.0f}</p>',";
                                }

                            } ?>


                        }

                    },
                    scatter: {
                        marker: {
                            radius: 5,
                            states: {
                                hover: {
                                    enabled: true,

                                }
                            }
                        },
                        states: {
                            hover: {
                                marker: {
                                    enabled: false
                                }
                            }
                        },


                        tooltip: {
                            useHTML: true,
                            headerFormat: '<p>{series.name}</p><br>',
                            <?php if ($display_select == 'bellcurve') { ?>
                            pointFormat: '<p style="font-size: 14px;color: #0A9BD6;font-weight: bold">{point.title}</p><br><p style="color: #8F8F8F;" ><?php echo $display_xa_axis ?> $ {point.y}</p><br><p>Percent: {point.x} %</p><br><p style="font-size: 10px">Release date: {point.date}</p>',

                            <?php } else  if ($display_select == 'plurality_bellcurve') { ?>

                            pointFormat: '<p style="font-size: 14px;color: #0A9BD6;font-weight: bold">{point.title}</p><br><p style="color: #8F8F8F;" ><?php echo $display_xa_axis ?> $ {point.x}</p><br><p>Percent: {point.y} %</p><br><p style="font-size: 10px">Release date: {point.date}</p>',

                            <?php } ?>
                        }

                    }
                },
                series: [

                    <?php echo $result_data_bell . $result_data; ?>

                ]
            });

            //   console.log(chart.series);

            /*
            var array_remove = new Array();

            $.each(chart.series, function (a, b) {
                ////  console.log(a,b.userOptions.type);
                if (b.userOptions.type == 'line') {
                    array_remove.push(a);
                    ///

                }
            });
            array_remove = array_remove.reverse();
            $.each(array_remove, function (a, b) {
                //  console.log(b);
                chart.series[b].remove(true);
            });
            */


        </script>


        <?php


    }
    else if ($display_select == 'bubble' || $display_select == 'scatter' || $display_select == 'regression')
    {
        if ($display_select == 'regression') { ?>

            <script src="js/regression.min.js"></script>


        <?php } ?>
        <script type="text/javascript">

            var regrassion_array = new Object();

            <?php   if ( $display_select == 'regression'){ ?>

            function getregression(data, name) {


                var resultr = regression.linear(data, {precision: 100});
                ////  console.log(resultr);
                var points = resultr.points;

                regrassion_array[name] = (resultr.string);
                return points;

            }
            <?php } ?>




            Highcharts.chart('chart_div', {
                chart: {

                    <?php if ($display_select != 'regression') {
                        echo "type: 'scatter',";
                    }?>

                    zoomType: 'xy',
                    ///styledMode: true
                },
                title: {
                    text: '<?php if ($display_select == 'bubble') {
                        echo 'Plurality Scatterplot';
                    } else if ($display_select == 'scatter') {
                        echo 'Scatter Chart by percent';
                    } ?>'
                },

                xAxis: {
                    title: {
                        <?php {
                            echo "text: '" . $display_xa_axis . "'";
                        } ?>
                    },

                    <?php if ($display_xa_axis == 'Movie release date') { ?>
                    type: 'datetime',
                    <?php } ?>
                    startOnTick: true,
                    endOnTick: true,
                    showLastLabel: true,

                    dateTimeLabelFormats: {

                        //  year: '%Y'
                    },
                },
                yAxis: {
                    title: {<?php    echo "text: 'Percent'";   ?>

                    }
                },
                legend: {
                    maxHeight: 70,
                },

                <?php if ($display_select == 'regression') { ?>

                tooltip: {
                    formatter: function (tooltip) {
                        var type = this.series.userOptions.type;
                        if (type == 'spline') {
                            ///   console.log(type);
                            return '<p>' + this.series.name + '</p><br><i style="font-size: 15px;color: #0A9BD6;">' + regrassion_array[this.series.name] + '</i><br><p style="color: #8F8F8F;" ><?php echo $display_xa_axis; ?>: $ ' + (this.x).toFixed(0) + '</p><br><p>Percent: ' + (this.y).toFixed(2) + '%</p>';
                        } else return tooltip.defaultFormatter.call(this, tooltip);

                    }
                },
                <?php } ?>

                plotOptions: {
                    series: {
                        cursor: 'pointer',
                        point: {
                            events: {
                                click: function (e) {
                                    var m_id = e.point.movie_id;
                                    var data = get_data();

                                    var whait_html = '<div class="cssload-circle">\n' +
                                        '\t\t<div class="cssload-up">\n' +
                                        '\t\t\t\t<div class="cssload-innera"></div>\n' +
                                        '\t\t</div>\n' +
                                        '\t\t<div class="cssload-down">\n' +
                                        '\t\t\t\t<div class="cssload-innerb"></div>\n' +
                                        '\t\t</div>\n' +
                                        '</div>';

                                    $('div.footer_table_result').html(whait_html);

                                    $.ajax({
                                        type: "POST",
                                        url: "get_data.php",

                                        data: ({
                                            oper: 'movie_data',
                                            id: m_id,

                                            data: JSON.stringify(data),
                                            cat: $('.actos_range_category').val()
                                        }),
                                        success: function (html) {
                                            $('.footer_table_result').html('<div class="clear_table"></div><div class="movie_content" id="' + m_id + '">' + html + '</div>');

                                            check_trailers(m_id);
                                        }
                                    });


                                }
                            }
                        }
                    },
                    spline: {},
                    scatter: {
                        marker: {
                            radius: 3,
                            states: {
                                hover: {
                                    enabled: true,
                                    lineColor: 'rgb(100,100,100)'
                                }
                            }
                        },
                        states: {
                            hover: {
                                marker: {
                                    enabled: false
                                }
                            }
                        },


                        tooltip: {
                            useHTML: true,
                            headerFormat: '<p>{series.name}</p><br>',

                            <?php if ($display_select == 'bubble' ) {?>
                            pointFormat: '<p style="font-size: 14px;color: #0A9BD6;font-weight: bold">{point.title}</p><br>Percent: {point.y}%<br><p style="color: #8F8F8F;" ><?php echo $display_xa_axis; ?>: $ {point.x}</p><br>{point.content}<br><p style="font-size: 10px">Release date: {point.date}</p>',
                            <?php } else { ?>
                            pointFormat: '<p style="font-size: 14px;color: #0A9BD6;font-weight: bold">{point.title}</p><br><p style="color: #8F8F8F;" ><?php echo $display_xa_axis; ?>: $ {point.x}</p><br>Percent: {point.y}%<br><p style="font-size: 10px">Release date: {point.date}</p>',
                            <?php } ?>
                        }

                    }
                },
                series: [<?php echo $result_data; ?>]
            });

        </script>


        <?php


    }
    else if ($display_select == 'ethnicity')
    {
        ?>

        <script type="text/javascript">
            $(document).ready(function () {


                var chart = Highcharts.chart('chart_div', {
                    chart: {
                        type: 'column',
                        zoomType: 'x',
                        ///styledMode: true
                    },
                    title: {
                        text: 'Ethnicity by year'
                    },
                    legend: {
                        maxHeight: 70,
                    },
                    xAxis: {
                        startOnTick: true,
                        endOnTick: true,
                    },
                    yAxis: {

                        title: {
                            text: 'Casting Representation'
                        },
                        // min: 0,max: 100,
                    },
                    tooltip: {

                        pointFormat: '<span >{series.name}</span>: <b>{point.percent} % </b><br><p>Box Office per Race: $ {point.y:.0f}</p><br><p>Box Office Total: $ {point.totalbox:.0f}</p>',

                        //shared: true
                    },
                    plotOptions: {
                        column: {
                            //stacking: 'normal',

                            stacking: 'percent'
                        },
                        series: {
                            // pointPadding: 0, // Defaults to 0.1
                            groupPadding: 0.02,// Defaults to 0.2
                            cursor: 'pointer',
                            point: {
                                events: {
                                    click: function (e) {
                                        ///console.log(e);

                                        var m_id = e.point.x;
                                        get_inner(m_id);
                                    }
                                }
                            }
                        },


                    },
                    series: [<?php echo $result_data; ?>]
                });


                $('.change_stack ').click(function () {
                    var stack = $(this).attr('id');

                    if (stack == 'percent') {
                        $(this).attr('id', 'normal').html('Stacking by Cast Member Per Race');

                    } else {
                        $(this).attr('id', 'percent').html('Stacking by Cast Percentage');
                    }


                    var s = chart.series;
                    var sLen = s.length;

                    for (var i = 0; i < sLen; i++) {
                        s[i].update({
                            stacking: stack
                        }, false);
                    }
                    chart.redraw();


                });

            });


        </script>
        <button class="change_stack button_big" id="normal">Stacking by Cast Member Per Race</button>
        <?php
    }


}







}