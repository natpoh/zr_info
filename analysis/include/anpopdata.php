<?php

if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');


//DB config
!defined('DB_HOST_AN') ? include ABSPATH . 'analysis/db_config.php' : '';
//Abstract DB
!class_exists('Pdoa') ? include ABSPATH . "analysis/include/Pdoa.php" : '';
!class_exists('MOVIE_DATA') ? include ABSPATH . "analysis/movie_data.php" : '';


class ANPOPDATA
{

public static function get_main_data($data_object)
{
    global $array_compare;
    if (!$array_compare)
    {
        !class_exists('TMDB') ? include ABSPATH . "analysis/include/tmdb.php" : '';
        $array_compare = TMDB::get_array_compare();
    }


global $pdo;

    global $selected_color;
    global $array_ethnic_color;
    if(!$array_ethnic_color)$array_ethnic_color = MOVIE_DATA::get_ethnic_color($selected_color);

    ///////////////////////////////////////////////////setup data //////////////////////

    $array_data = $data_object->result_data;
    $start = $array_data['start'];
    $end = $array_data['end'];
    $join_dop = $array_data['join'];
    $idop = $array_data['dop'];
    $inflation_array = $data_object->inflation;
    $country = $data_object->country;

    $display_xa_axis = $data_object->display_xa_axis;
    $diversity_select = $data_object->diversity_select;
    $idop_yaer = $data_object->idop_yaer;

    $display_select= $data_object->display_select;


    ///////////////////////////////////////////////////setup data //////////////////////


//    if ($display_select == 'Buying_power2') {
//        $populatin_result = [];
//        $result_in = '';
//        $array_country_data = [];
//        $array_country = [];
//        $data_power = [];
//        $per_capita_max = 0;
//        $per_capita_min = 10000000000000000000000000;
//
//        $array_code = array('XK' => 'KV');
//
//        $sql = "SELECT *  FROM  data_population_country ";
//        ///echo $sql.PHP_EOL;
//        $q = $pdo->prepare($sql);
//        $q->execute();
//        $q->setFetchMode(PDO::FETCH_ASSOC);
//
//        while ($r = $q->fetch()) {
//
//
//            // $country = $r['country_name'];
//            $cca2 = $r['cca2'];
//
//
//            $sql2 = "SELECT *  FROM `data_buying_power` where cca2='" . $r['cca2'] . "' limit 1";
//
//
//            $q2 = $pdo->prepare($sql2);
//            $q2->execute();
//            $q2->setFetchMode(PDO::FETCH_ASSOC);
//
//            $r2 = $q2->fetch();
//
//            //var_dump($r2);
//
//            $country = $r2['name'];
//
//
//            if ($array_code[$cca2]) {
//                $cca2 = $array_code[$cca2];
//            }
//
//            $per_capita = 0;
//            $total = 0;
//            $date = 0;
//
//
//            $per_capita = $r2['per_capita'];
//
//            if ($per_capita > $per_capita_max) {
//                $per_capita_max = $per_capita;
//            }
//
//            if ($per_capita < $per_capita_min) {
//                $per_capita_min = $per_capita;
//            }
//
//
//            $total = $r2['total'];
//            $date = $r2['date'];
//
//            if (!$date) $date = 2010;
//            if (!$total) $total = 1000;
//            if (!$per_capita) $per_capita = 1000;
//
//
//            $data_power[$cca2] = array($country, $per_capita, $total, $date);
//
//        }
//
//
/////var_dump($data_power);
//
//        $result_data = '';
//        $result_in = '';
//
//        foreach ($data_power as $cca2 => $val) {
//
//            if ($cca2 && $val[2] && $val[0]) {
//
//                //  $result_in .=
//                echo "{ name: '" . $val[0] . "', code2: '" . $cca2 . "', value: '" . $val[1] . "', year:'" . $val[3] . "', total:'" . $val[2] . "'},";
//                //  echo '<br>';
//            }
//
//        }
//
//
//        $result_data .= "{  data: [" . $result_in . "],
//                       joinBy: ['iso-a2', 'code2'],
//                       name: 'Purchasing Power Parity (Per Capita)',
//                           dataLabels: {
//                          // enabled: true,
//                         ///  format: '{point.name}'
//
//                       }  ,
//
//
//              ///  color: '#ccc',
//
//                },";
//
//
//    }




    if ($display_select == 'Buying_power_by_race') {
        $array_total = [];
        $yearmin = 0;

        $yaerstart = 2010;

        $sql = "SELECT *  FROM `data_buying_power`, data_population_country where data_buying_power.cca2=data_population_country.cca2";
        ///echo $sql.PHP_EOL;
        $q = $pdo->prepare($sql);
        $q->execute();
        $q->setFetchMode(PDO::FETCH_ASSOC);

        while ($r = $q->fetch()) {

            $country = $r['name'];
            $cca2 = $r['cca2'];
            $per_capita = $r['per_capita'];
            $total = $r['total'];

            $year = $r['date'];
            if ($year > $yearmin) {
                $yearmin = $year;
            }

            $populatin_by_year = $r['populatin_by_year'];
            $populatin_by_year_result = json_decode($populatin_by_year, JSON_FORCE_OBJECT);

            $pop = $populatin_by_year_result[$yaerstart];


            $ethnic_array_result = $r['ethnic_array_result'];

            if ($ethnic_array_result && $per_capita) {

                $ethnic_array_result = json_decode($ethnic_array_result, JSON_FORCE_OBJECT);


                foreach ($ethnic_array_result as $e => $count) {

                    $population = ($total / $per_capita) * ($count / 100);

                    /*
                    echo 'race '.$e.'<br>';
                    echo 'count '.$count.'<br>';
                    echo $per_capita.'<br>';
                    echo $total.'<br>';
                    echo 'population '.$population.'<br>';
*/

                    $summ_country = $population * $per_capita;


                    /// echo 'population '.($total/$per_capita)*($count/100).'<br>';
                    $array_total['p'][$e] += $summ_country;
                    $array_total['t'][$e] += $count * $total / 100;
                    $array_total['i'][$e] += $population;


                    $array_total['pop_p'][$e] += $count * $pop / 100;
                    $array_total['pop_all'][$e] += $pop;

                }

            }

        }
        arsort($array_total['t']);


        foreach ($array_total['t'] as $race => $count) {

            $result_in_all .= "{name:'" . $race . "', y: " . round($count, 0) . ", color: '" . $array_ethnic_color[$race] . "'},";
            $result_in_pop_all .= "{name:'" . $race . "', y: " . round($array_total['pop_p'][$race], 0) . ",content:'Population " . $array_total['pop_p'][$race] . "'}, ";
        }


        $array_temp = [];


        foreach ($array_total['p'] as $race => $count) {

            $pop = $array_total['i'][$race];

            $count = $count / $pop;
            $array_temp[$race] = round($count, 0);


        }

        arsort($array_temp);

        foreach ($array_temp as $race => $count) {


            $result_in .= "{name:'" . $race . "', y: " . $count . ", color: '" . $array_ethnic_color[$race] . "'}, ";

            $result_in_pop .= "{name:'" . $race . "', y: " . round($array_total['pop_p'][$race], 0) . ",content:'Population " . $array_total['pop_p'][$race] . "'}, ";

        }
//var_dump($array_total['p']);
    }
    else if ($display_select == 'Buying_power') {
        $populatin_result = [];
        $result_in = '';
        $result_c = '';
        $result_c_all = '';
        $array_country_data = [];
        $array_country = [];
        $data_power = [];
        $per_capita_max = 0;
        $per_capita_min = 10000000000000000000000000;

        $all_data_max = 0;
        $all_data_min = 10000000000000000000000000;

        $array_code = array('XK' => 'KV');

        $sql = "SELECT *  FROM `data_buying_power`, data_population_country where data_buying_power.cca2=data_population_country.cca2";
        ///echo $sql.PHP_EOL;
        $q = $pdo->prepare($sql);
        $q->execute();
        $q->setFetchMode(PDO::FETCH_ASSOC);

        while ($r = $q->fetch()) {

            $country = $r['name'];
            $cca2 = $r['cca2'];
            $cca3 = strtolower($r['cca3']);

            if ($array_code[$cca2]) {
                $cca2 = $array_code[$cca2];
            }


            $per_capita = $r['per_capita'];

            if ($per_capita > $per_capita_max) {
                $per_capita_max = $per_capita;
            }
            if ($per_capita < $per_capita_min) {
                $per_capita_min = $per_capita;
            }


            $total = $r['total'];

            if ($total > $all_data_max) {
                $all_data_max = $total;
            }
            if ($total < $all_data_min) {
                $all_data_min = $total;
            }


            $date = $r['date'];
            $data_power[$cca2] = array($country, $per_capita, $total, $date, $cca3);


        }


        $result_data = '';
        $result_in = '';
        $result_in_all = '';
        foreach ($data_power as $cca2 => $val) {


            //$result_c .= "['".$val[0]."',  ".round($val[1],0)."],";
            $result_c .= "{name:'" . $val[4] . "', y: " . round($val[1], 0) . ", country: '" . $val[0] . "', code2: '" . $cca2 . "'},";
            $result_c_all .= "{name:'" . $val[4] . "', y: " . round($val[2], 0) . ", country: '" . $val[0] . "', code2: '" . $cca2 . "'},";

            $result_in .= "{ name: '" . $val[0] . "', code2: '" . $cca2 . "', value: '" . $val[1] . "', year:'" . $val[3] . "', total:'" . $val[2] . "'},";

            $result_in_all .= "{ name: '" . $val[0] . "', code2: '" . $cca2 . "', value: '" . $val[2] . "', year:'" . $val[3] . "', total:'" . $val[1] . "'},";


        }

        $result_data .= "{  data: [" . $result_in . "],
                       joinBy: ['iso-a2', 'code2'],
                       name: 'Purchasing Power Parity (Per Capita)',
                           dataLabels: {
                          // enabled: true,
                         ///  format: '{point.name}'
   
                       }  ,
              
                
              ///  color: '#ccc',
                
                },";


    }
    else if ($display_select == 'world_map') {
        $populatin_result = [];
        $result_in = '';
        $array_country_data = [];
        $array_country = [];
        $array_race = [];

        $array_code = array('XK' => 'KV');

        $sql = "SELECT *  FROM `data_population_country`";
        ///echo $sql.PHP_EOL;
        $q = $pdo->prepare($sql);
        $q->execute();
        $q->setFetchMode(PDO::FETCH_ASSOC);

        while ($r = $q->fetch()) {

            $country = $r['country_name'];
            $cca3 = $r['cca3'];
            $cca2 = $r['cca2'];


            if ($array_code[$cca2]) {
                $cca2 = $array_code[$cca2];
            }

            ////////////////
            $ethnic_array_result = $r['ethnic_array_result'];
            if ($ethnic_array_result) {
                $ethnic_array_result = json_decode($ethnic_array_result, JSON_FORCE_OBJECT);
                $key = array_keys($ethnic_array_result);
                $value = $key[0];


                $ethnic_country = '';
                foreach ($ethnic_array_result as $race => $count) {


                    $ethnic_country .= $race . ' : ' . $count . '<br>';

                }


            }

            $population_data = $r['population_data'];
            $population_data_result = json_decode($population_data);

            $populatin_by_year = $r['populatin_by_year'];
            $populatin_by_year_result = json_decode($populatin_by_year);

            $populatin_result = [];

            foreach ($population_data_result as $year => $data) {
                if ($year > date('Y', time())) {
                    $populatin_result[$year] = $data;
                }
            }

            foreach ($populatin_by_year_result as $year => $data) {
                if ($data > 0) {
                    $populatin_result[$year] = $data;
                }
            }
            $last_summ = 0;
            $last_year = 0;

            foreach ($populatin_result as $year => $summ) {

                if ($year >= $start && ($year <= $end)) {

                    $last_summ = $summ;
                    $last_year = $year;
                }
            }

            if ($value) {
                // $array_movie_bell[$value][$cca2] = array($country, $ethnic_country, $last_summ, $last_year);
            }

            /////////////////////

            $ethnic_array = $r['ethnic_array'];
            $array_result = json_decode($ethnic_array);
            $content = [];

            $arry_total = [];

            arsort($array_result);

            $next = 0;
            foreach ($array_result as $index => $val) {

                $index = trim($index);
                $index = strtolower($index);
                $index = ucfirst($index);


                if ($array_compare[$index]) {
                    $race = $array_compare[$index];

                    $arry_total[$race] += $val;
                    $next = 1;
                } else if ($array_compare[$country]) {

                    $array_race[$index] = $array_compare[$country];


                    $race = $array_compare[$country];
                    $arry_total[$race] += $val;
                    $next = 1;
                } else {
                    $array_country[$country][$index]++;
                    $array_country_data[$country] = $r['region'] . ' ' . $r['subregion'] . ' ' . $r['latlng'];
                }


                $content[$index] += $val;

            }
            arsort($content);
            $content_string = $ethnic_country . '<br>----- Ethnic data -----<br>';
            foreach ($content as $race => $val) {
                $content_string .= $race . ' (' . $array_compare[$race] . ') : ' . $val . ' %<br>';
            }
            arsort($arry_total);
            $key = array_keys($arry_total);
            $value = $arry_total[$key[0]];

            if ($next) {
                $array_movie_bell[$key[0]][$cca2] = array($country, $content_string, $last_summ, $last_year);
                // echo $country . ' ' . $key[0] . '-' . $value . '<br>';
            }


        }


        $result_data = '';


        foreach ($array_movie_bell as $ethnic => $data) {
            $result_in = '';

            foreach ($data as $cca2 => $val) {

                $result_in .= "{ name: '" . $val[0] . "', code2: '" . $cca2 . "', value: '" . $val[2] . "', year:'" . $val[3] . "', content:'" . $val[1] . "'},";

            }

            $result_data .= "{  data: [" . $result_in . "],
                       joinBy: ['iso-a2', 'code2'],
                       name: '" . $ethnic . "',
                           dataLabels: {
                          // enabled: true,
                         ///  format: '{point.name}'
   
                       }  ,
                       tooltip: {
                    headerFormat: '',
                    pointFormat: '<p>{point.name}</p><br><p>{point.content}</p>'
                },
                
                color: '" . $array_ethnic_color[$ethnic] . "',
                
                },";
        }


        arsort($array_race);

        /*
        foreach ($array_race as $race=>$val)
        {
            echo "'".$race."' => '".$val."',<br>";
        }



        foreach ($array_country as $country => $data) {
            foreach ($data as $name => $count) {

                echo $name . '  ( ' . $country . ' )  ' . $array_country_data[$country] . '<br>';
            }

        }
        */
    }
    else if ($display_select == 'world_population') {
        $array_country = [];
        $array_total = [];
        $array_world = [];
        $array_country_data = [];
        $array_total_country = [];

        $sql = "SELECT *  FROM `data_population_country`";
        ///echo $sql.PHP_EOL;
        $q = $pdo->prepare($sql);
        $q->execute();
        $q->setFetchMode(PDO::FETCH_ASSOC);

        while ($r = $q->fetch()) {

            $country = $r['country_name'];

            $ethnic_array = $r['ethnic_array'];
            $array_result = json_decode($ethnic_array);


            $population_data = $r['population_data'];
            $population_data_result = json_decode($population_data);

            $populatin_by_year = $r['populatin_by_year'];
            $populatin_by_year_result = json_decode($populatin_by_year);


            $populatin_result = [];

            foreach ($population_data_result as $year => $data) {
                if ($year > date('Y', time())) {
                    $populatin_result[$year] = $data;
                }
            }


            foreach ($populatin_by_year_result as $year => $data) {
                if ($data > 0) {
                    $populatin_result[$year] = $data;
                }
            }


            // ksort($populatin_result);
            ///  echo $r['country_name'].'<br>';


            $ethnic_array_result = $r['ethnic_array_result'];


            if ($ethnic_array_result) {
                $ethnic_array_result = json_decode($ethnic_array_result, JSON_FORCE_OBJECT);
            }


            foreach ($populatin_result as $year => $summ) {

                $array_total_country[$year][$country] += $summ;


                if ($year >= $start && (($year <= $end) || ($end == date('Y', time())))) {

                    // $summ = $summ * 1000;
                    //echo '<br>year'.$year.' '.$summ.'<br>';
                    // $array_total[$country][$year] += $summ;


                    foreach ($ethnic_array_result as $race => $count) {


                        if ($count > 0) {

                            $summ_result = ($count * $summ / 100);
                            $array_total[$race][$year] += $summ_result;
                            $array_world[$year] += $summ_result;

                            $array_country[$year][$race][$country] += $summ_result;

                        }

                    }

                }

            }

            //break;
        }

        $result_data = '';

        ///arsort($array_total);
        $array_none = [];
        foreach ($array_total as $name => $data) {

            ksort($data);
            /// var_dump($data);

            $result_in = '';

            foreach ($data as $year => $summ) {

                $summ = round($summ, 0);

                $world = $array_world[$year];

                $wpercent = round(($summ / $world) * 100, 2);


                $result_in .= "{ x: " . $year . ", y: " . $summ . ",world:'" . $world . "' ,wpercent: '" . $wpercent . "'},";

            }

            $result_data .= "{
                  name: '" . $name . "',
                    type: 'spline',
                   color: '" . $array_ethnic_color[$name] . "', 
                    marker: {            enabled: false        },
                  turboThreshold:0,
                  data: [" . $result_in . "]},";


            ///   echo $name.' '.$summ.'<br>';
        }

        ///  ksort($array_country);

        /*
        if (is_array($array_none)) {
            arsort($array_none);
            foreach ($array_country as $country => $data) {
                foreach ($data as $name => $count) {

                    echo $name . '  ( ' . $country . ' )  ' . $array_country_data[$country] . '<br>';
                }

            }
        }
*/


    }
    else if ($display_select == 'date_range_international' || $display_select == 'date_range_country')
    {

        ///foreach ($country as $country_value) {
        ///  echo $country_value.'<br>';

        $country_value = $country;

        if ($display_select == 'date_range_international') {
            $DomesticBox = 1;
            $International = 1;
        }

        if ($display_select == 'date_range_country') {
            $byCountry = 1;

/// data_movie_imdb.`Box Office Worldwide`, data_movie_imdb.`Box Office Country`

            $join_dop .= " INNER join data_movie_rank  on (data_movie_rank.MovieID =  data_movie_imdb.movie_id ) ";
            $idop .= " and data_movie_rank.`Box Office Country` IS NOT NULL ";

            $sql = "SELECT  *  FROM data_movie_rank where `Box Office Country` IS NOT NULL and Year >={$start} && Year<={$end}";
        } else {

            $idop = substr($idop, 4);
            $sql = "SELECT  *  FROM  data_movie_imdb " . $join_dop . "  WHERE   " . $idop . $idop_yaer;

        }

        $array_index = array('Box Office International', 'Box Office Domestic');
        if ($byCountry) {

            $array_index = [];

        }


        // [Countries] => ["Italy"]
        ///echo $sql;

        $q = $pdo->prepare($sql);
        $q->execute();
        $q->setFetchMode(PDO::FETCH_ASSOC);

        $array_years = [];
        $array_top_country = [];//////top 10 country


        while ($r = $q->fetch()) {

            if ($byCountry) {
                $r['year'] = $r['Year'];
            }

            $k = $inflation_array[$r['year']];
            if (!$k) $k = 1;


            if (!$r['box_usa']) $r['box_usa'] = 0;

            $array_years[$r['year']]['Box Office Domestic'] += $r['box_usa'] * $k;


            $int = 0;
            if ($r['box_world'] > $r['box_usa']) {
                $int = ($r['box_world'] - $r['box_usa']) * $k;
            }

            $array_years[$r['year']]['Box Office International'] += $int;

            if ($byCountry) {

                if ($r['Box Office Country']) {
                    $array_country = json_decode($r['Box Office Country']);
                    ///   var_dump($array_country);
                    foreach ($array_country as $country => $summ) {


                        if (in_array($country, $array_index)) {
                            $array_index[] = $country;
                        }

                        $array_top_country[$country] += $summ * $k;

                        if ($summ > 0) {
                            $array_years[$r['year']][$country] += $summ * $k;
                        }
                    }

                }

                arsort($array_top_country);

                $array_top_country = array_slice($array_top_country, 0, 10);


            }


        }


        $result_data = '';
        $data_years = '';
        $array_result = [];
        ksort($array_years);
        if (is_array($array_years)) {
            foreach ($array_years as $index => $val) {
                arsort($val);


                /// foreach ($array_index as $val_type ) {
                foreach ($val as $val_type => $count) {
                    ///  $count= $array_years[$index][$val_type];


                    if (!$count) $count = 0;

                    /// echo $index.' '.$val_type.' '.$count.'<br>';


                    if (($byCountry && $array_top_country[$val_type]) || !$byCountry) {
                        $array_result[$val_type] .= "{x:" . $index . ",y:" . $count . "},";

                    }
                }


            }


            if ($byCountry) {

                uksort($array_result, function ($a, $b) use ($array_top_country) {
                    return $array_top_country[$b] - $array_top_country[$a];
                });


            }

            foreach ($array_result as $val_type => $data) {


                $result_data .= "{   name: '" . $val_type . "',
                             data: [" . $data . "],
                               color: '" . $array_ethnic_color[$val_type] . "',
                             },";
            }


        }


        if ($byCountry) {

            foreach ($array_top_country as $name => $summ) {
                $array_name .= ", '" . $name . "'";
            }

        }
    }


    if ($display_select == 'Buying_power_by_race') {
        ?>

        <script type="text/javascript">
            $('#chart_div').height(500);
            var chart = Highcharts.chart('chart_div', {
                chart: {
                    type: 'column',
                    panning: true,
                },
                title: {
                    text: 'Buying Power by race Per Capita (<?php echo $yearmin ?>)'
                },
                /*
                 plotOptions: {
                     series: {
                         grouping: false,
                         borderWidth: 0,

                                 point: {
                                     events: {
                                         click: function (e) {

                                             var code2 = e.point.code2;
                                             var name = e.point.name;
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
                                                     oper: 'get_country_data',
                                                     id: name,
                                                     cur_year: '',
                                                     code2: code2,
                                                     data: JSON.stringify(data),

                                                 }),
                                                 success: function (html) {

                                                     $('.footer_table_result').html('<div class="clear_table"></div><div class="movie_content" id="' + name + '">' + html + '</div>');

                                                 }
                                             });


                                         }
                                     }
                                 }

                     }
                 },
 */
                legend: {
                    enabled: false
                },
                tooltip: {
                    shared: true,
                    headerFormat: '<span style="font-size: 15px">{point.point.name}</span><br/>',
                    pointFormat: '<span style="color:{point.color}">\u25CF</span> {series.name}: <b> {point.y} </b><br>'
                },
                xAxis: {
                    type: 'category',

                },
                yAxis: [{
                    title: {
                        text: 'Buying Power'
                    },
                    showFirstLabel: false
                }, {
                    opposite: true,
                    title: {
                        text: 'Population'
                    }
                }],

                series: [{
                    name: 'Buying Power Per Capita',
                    id: 'main',
                    dataSorting: {
                        // enabled: true,
                        //  matchByName: true
                    },
                    dataLabels: [{
                        enabled: false,
                        inside: true,
                        style: {
                            fontSize: '16px'
                        }
                    }],
                    data: [<?php echo $result_in ?>]
                }, {
                    name: 'Population',
                    color: '#0026ff',
                    type: 'spline',
                    zIndex: 2,
                    data: [<?php echo $result_in_pop ?>],
                    marker: {
                        radius: 6,
                        color: '#0026ff',
                        states: {
                            hover: {
                                enabled: true,

                            }
                        }
                    },
                    lineWidth: 4,
                    yAxis: 1,
                    tooltip: {
                        ///    valuePrefix: '$ '
                    }
                }],
                exporting: {
                    allowHTML: true
                }
            });
            $('.change_buying_power ').click(function () {
                var stack = $(this).attr('id');

                if (stack == 'all_data') {
                    $(this).attr('id', 'per_capita').html('Stacking by per capita');

                    chart.title.update({
                        text: 'Buying Power by race Total (<?php echo $yearmin ?>)'
                    });


                    chart.series[0].update({
                        data: [<?php echo $result_in_all ?>],
                        name: 'Purchasing Power Total'
                    }, false);
                    chart.series[1].update({
                        data: [<?php echo $result_in_pop_all ?>]
                    }, false);
                } else {

                    $(this).attr('id', 'all_data').html('Stacking by All data');


                    chart.title.update({
                        text: 'Buying Power by race Per Capita (<?php echo $yearmin ?>)'
                    });


                    chart.series[0].update({
                        data: [<?php echo $result_in ?>],
                        name: 'Purchasing Power Per Capita'
                    }, false);
                    chart.series[1].update({
                        data: [<?php echo $result_in_pop ?>]
                    }, false);
                }

                chart.redraw();
            });
        </script>

        <button class="change_buying_power button_big" id="all_data">Stacking by All data</button>

    <?php }
    else if ($display_select == 'Buying_power') {

        ?>

        <script type="text/javascript">

            $('#chart_div').height(700);

            var chart = Highcharts.mapChart('chart_div', {

                chart: {
                    map: 'custom/world'
                },

                title: {
                    text: 'Purchasing Power Parity (Per Capita)'
                },

                legend: {
                    title: {
                        text: 'Buying Power',
                    }
                },
                plotOptions: {
                    map: {
                        ///    allAreas: false,
                    },

                    series: {
                        point: {
                            events: {
                                click: function (e) {


                                    /// console.log(e);


                                    var code2 = e.point.code2;
                                    var name = e.point.name;
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
                                            oper: 'get_country_data',
                                            id: name,
                                            cur_year: '',
                                            code2: code2,
                                            data: JSON.stringify(data),

                                        }),
                                        success: function (html) {

                                            $('.footer_table_result').html('<div class="clear_table"></div><div class="movie_content" id="' + name + '">' + html + '</div>');

                                        }
                                    });


                                }
                            }
                        }
                    },
                },
                mapNavigation: {
                    enabled: true,
                    buttonOptions: {
                        verticalAlign: 'bottom'
                    }
                },
                tooltip: {
                    headerFormat: '',
                    //useHTML: true,
                    pointFormat: '<p>{point.name}</p><br><p>Buying Power (Per Capita): <b>$ {point.value}</b></p><br><p>Buying Power (Total): $ {point.total}</p><br><p>Year: {point.year}</p>'
                },

                colorAxis: {
                    min:<?php echo $per_capita_min ?>,
                    max:<?php echo $per_capita_max ?>,
                    //  type: 'logarithmic',
                    minColor: '#dbe3ff',
                    maxColor: '#000016',
                    stops: [
                        [0, '#EFEFFF'],
                        [0.13, '#004c78'],
                        [0.62, '#000322'],
                        [1, '#000000']
                    ]
                },

                series: [
                    <?php echo $result_data; ?>

                ]

            });


            var chart2 = Highcharts.chart('chart_div_2', {
                chart: {
                    type: 'column',
                    panning: true,
                },
                title: false,

                plotOptions: {
                    series: {
                        grouping: false,
                        borderWidth: 0,


                        point: {
                            events: {
                                click: function (e) {


                                    /// console.log(e);


                                    var code2 = e.point.code2;
                                    var name = e.point.name;
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
                                            oper: 'get_country_data',
                                            id: name,
                                            cur_year: '',
                                            code2: code2,
                                            data: JSON.stringify(data),

                                        }),
                                        success: function (html) {

                                            $('.footer_table_result').html('<div class="clear_table"></div><div class="movie_content" id="' + name + '">' + html + '</div>');

                                        }
                                    });


                                }
                            }
                        }

                    }
                },
                legend: {
                    enabled: false
                },
                tooltip: {
                    shared: true,
                    headerFormat: '<span style="font-size: 15px">{point.point.country}</span><br/>',
                    pointFormat: '<span style="color:{point.color}">\u25CF</span> {series.name}: <b>{point.y} </b><br/>'
                },
                xAxis: {
                    type: 'category',
                    max: 10,
                    scrollbar: {
                        enabled: true
                    },
                    labels: {
                        useHTML: true,
                        animate: true,
                        formatter: function () {
                            ////   console.log(this);
                            var output = this.value;
                            return '<span><img src="/analysis/country_data/' + output + '.svg" style="width: 40px; height: 40px;"/><br></span>';
                        }
                    }
                },
                yAxis: [{
                    title: {
                        text: 'Buying Power'
                    },
                    showFirstLabel: false
                }],
                colorAxis: {
                    min:<?php echo $per_capita_min ?>,
                    max:<?php echo $per_capita_max ?>,
                    //  type: 'logarithmic',
                    minColor: '#dbe3ff',
                    maxColor: '#000016',
                    stops: [
                        [0, '#EFEFFF'],
                        [0.13, '#004c78'],
                        [0.62, '#000322'],
                        [1, '#000000']
                    ]
                },
                series: [{
                    name: 'Buying Power Per Capita',
                    id: 'main',
                    dataSorting: {
                        enabled: true,
                        matchByName: true
                    },
                    dataLabels: [{
                        enabled: false,
                        inside: true,
                        style: {
                            fontSize: '16px'
                        }
                    }],
                    data: [<?php echo $result_c ?>]
                }],
                exporting: {
                    allowHTML: true
                }
            });


            $('.change_buying_power ').click(function () {
                var stack = $(this).attr('id');

                if (stack == 'all_data') {
                    $(this).attr('id', 'per_capita').html('Stacking by per capita');

                    //  console.log(chart);


                    chart.title.update({
                        text: 'Purchasing Power Total'
                    });

                    chart.tooltip.update({
                        pointFormat: '<p>{point.name}</p><br><p>Buying Power (Total): <b>$ {point.value}</b></p><br><p>Buying Power (Per Capita): $ {point.total}</p><br><p>Year: {point.year}</p>'
                    });

                    chart.colorAxis[0].update({
                        min:<?php echo $all_data_min ?>,
                        max:<?php echo $all_data_max ?>,
                        //  type: 'logarithmic',
                        minColor: '#dbe3ff',
                        maxColor: '#000016',
                        stops: [
                            [0, '#EFEFFF'],
                            [0.13, '#004c78'],
                            [0.62, '#000858'],
                            [1, '#000000']
                        ]
                    }, false);


                    chart.series[0].update({
                        data: [<?php echo $result_in_all ?>]
                    }, false);

                    ////////$result_c

                    chart2.colorAxis[0].update({
                        min:<?php echo $all_data_min ?>,
                        max:<?php echo $all_data_max ?>,
                        //  type: 'logarithmic',
                        minColor: '#dbe3ff',
                        maxColor: '#000016',
                        stops: [
                            [0, '#EFEFFF'],
                            [0.13, '#004c78'],
                            [0.62, '#000858'],
                            [1, '#000000']
                        ]
                    }, false);


                    chart2.series[0].update({
                        data: [<?php echo $result_c_all ?>],
                        name: 'Purchasing Power Total'
                    }, false);
                } else {

                    $(this).attr('id', 'all_data').html('Stacking by All data');


                    chart.title.update({
                        text: 'Purchasing Power Parity (Per Capita)'
                    });
                    chart.tooltip.update({
                        pointFormat: '<p>{point.name}</p><br><p>Buying Power (Per Capita): <b>$ {point.value}</b></p><br><p>Buying Power (Total): $ {point.total}</p><br><p>Year: {point.year}</p>'
                    });

                    chart.colorAxis[0].update({
                        min:<?php echo $per_capita_min ?>,
                        max:<?php echo $per_capita_max ?>,
                        // type: 'logarithmic',
                        minColor: '#dbe3ff',
                        maxColor: '#000016',
                        stops: [
                            [0, '#EFEFFF'],
                            [0.21, '#004c78'],
                            [0.62, '#000322'],
                            [1, '#000000']
                        ]
                    }, false);

                    chart.series[0].update({
                        data: [<?php echo $result_in ?>]
                    }, false);

//////////////////


                    chart2.colorAxis[0].update({
                        min:<?php echo $per_capita_min ?>,
                        max:<?php echo $per_capita_max ?>,
                        // type: 'logarithmic',
                        minColor: '#dbe3ff',
                        maxColor: '#000016',
                        stops: [
                            [0, '#EFEFFF'],
                            [0.21, '#004c78'],
                            [0.62, '#000322'],
                            [1, '#000000']
                        ]
                    }, false);

                    chart2.series[0].update({
                        data: [<?php echo $result_c ?>],
                        name: 'Purchasing Power Per Capita'
                    }, false);


                }

                chart.redraw();
                chart2.redraw();
            });


        </script>


        <div id="chart_div_2" style="width: 100%; height: 400px"></div>
        <button class="change_buying_power button_big" id="all_data">Stacking by All data</button>


    <?php }
    else if ($display_select == 'world_map') {
        ?>


        <script type="text/javascript">

            $('#chart_div').height(700);
            // Create the chart

            /*
                        // New map-pie series type that also allows lat/lon as center option.
            // Also adds a sizeFormatter option to the series, to allow dynamic sizing
            // of the pies.
            Highcharts.seriesType('mappie', 'pie', {
              center: null, // Can't be array by default anymore
              clip: true, // For map navigation
              states: {
                hover: {
                  halo: {
                    size: 5
                  }
                }
              },
              linkedMap: null, //id of linked map
              dataLabels: {
                enabled: false
              }
            }, {
              render: function () {
                var series = this,
                  chart = series.chart,


                  linkedSeries = chart.get(series.options.id);
                Highcharts.seriesTypes.pie.prototype.render.apply(series, arguments);
                if (series.group && linkedSeries) {
                  series.group.add(linkedSeries.group);
                }

              },

              getCenter: function () {
                var options = this.options,
                  chart = this.chart,
                  slicingRoom = 2 * (options.slicedOffset || 0);
               /// console.log('options',options);
                if (!options.center) {
                  options.center = [null, null]; // Do the default here instead
                }
                // Handle lat/lon support
                if (options.center.plotX !== undefined) {

                  options.center = [
                    chart.xAxis[0].toPixels(options.center.plotX, true) ,
                    chart.yAxis[0].toPixels(options.center.plotY, true)
                  ];
                }
                // Handle dynamic size
                if (options.sizeFormatter) {
                  options.size = options.sizeFormatter.call(this);
                }
                // Call parent function
                var result = Highcharts.seriesTypes.pie.prototype.getCenter.call(this);
                // Must correct for slicing room to get exact pixel pos
                result[0] -= slicingRoom;
                result[1] -= slicingRoom;
                return result;
              },


              translate: function (p) {
                this.options.center = this.userOptions.center;
                this.center = this.getCenter();
                return Highcharts.seriesTypes.pie.prototype.translate.call(this, p);
              }


            });
            */

            var chart = Highcharts.mapChart('chart_div', {
                chart: {
                    map: 'custom/world',
                    ///styledMode: true
                },

                title: {
                    text: 'Ethnic world map'
                },
                plotOptions: {
                    map: {
                        allAreas: false,
                    },
                    tooltip: {
                        headerFormat: '<p>{series.name}</p>',
                        pointFormat: '{point.content}'
                    },

                    series: {
                        point: {
                            events: {
                                click: function (e) {


                                    /// console.log(e);


                                    var code2 = e.point.code2;
                                    var name = e.point.name;
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
                                            oper: 'get_country_data',
                                            id: name,
                                            cur_year: '',
                                            code2: code2,
                                            data: JSON.stringify(data),

                                        }),
                                        success: function (html) {

                                            $('.footer_table_result').html('<div class="clear_table"></div><div class="movie_content" id="' + name + '">' + html + '</div>');

                                        }
                                    });


                                }
                            }
                        }
                    },


                },

                mapNavigation: {
                    enabled: true,
                    buttonOptions: {
                        verticalAlign: 'bottom'
                    }
                },

                series: [
                    <?php echo $result_data; ?>

                ]
            });


            /*
            var data = [
                // state, demVotes, repVotes, libVotes, grnVotes, sumVotes, winner, offset config for pies
                ['RU', 729547, 1318255, 44467, 9391, 2101660, -1],
                ['US', 1367716, 1322951, 112972, 36985, 2840624, 1, { lon: -1, drawConnector: false }],
                ['CN', 1967444, 1509688, 72143, 37131, 3586406, 3, { lat: -1, lon: 1.2 }],

              ],

              maxVotes = 0,
              demColor = 'rgba(74,131,240,0.80)',
              repColor = 'rgba(220,71,71,0.80)',
              libColor = 'rgba(240,190,50,0.80)',
              grnColor = 'rgba(90,200,90,0.80)';

                        /*
                        // Compute max votes to find relative sizes of bubbles
                        Highcharts.each(data, function (row) {
                          maxVote= Math.max(maxVotes, row[5]);
                        });
                        /*
                        // Build the chart
                        var chart = Highcharts.mapChart('chart_div', {
                          title: {
                            text: 'Population'
                          },

                          chart: {
                              ///styledMode: true,
                            animation: false // Disable animation, especially for zooming
                          },

                          colorAxis: {
                            dataClasses: [{
                              from: -1,
                              to: 0,
                              color: 'rgba(244,91,91,0.5)',
                              name: 'Republican'
                            }, {
                              from: 0,
                              to: 1,
                              color: 'rgba(124,181,236,0.5)',
                              name: 'Democrat'
                            }, {
                              from: 2,
                              to: 3,
                              name: 'Libertarian',
                              color: libColor
                            }, {
                              from: 3,
                              to: 4,
                              name: 'Green',
                              color: grnColor
                            }]
                          },

                          mapNavigation: {
                            enabled: true
                          },

                          // Limit zoom range

                          yAxis: {
                           // minRange: 2300
                          },

                          tooltip: {
                            useHTML: true
                          },

                          // Default options for the pies
                          plotOptions: {
                              map: {
                                  allAreas: false,
                              },
                            mappie: {
                              borderColor: 'rgba(255,255,255,0.4)',
                              borderWidth: 1,
                              tooltip: {
                                headerFormat: ''
                              }
                            }
                          },

                          series: [{
                            mapData: Highcharts.maps['custom/world'],
                            data: data,
                            name: 'States',
                            borderColor: '#FFF',
                            showInLegend: false,
                           // joinBy: ['name', 'id'],
                            joinBy: ['iso-a2', 'id'],
                            keys: ['id', 'demVotes', 'repVotes', 'libVotes', 'grnVotes',
                              'sumVotes', 'value', 'pieOffset'],
                            tooltip: {
                              headerFormat: '',
                              pointFormatter: function () {
                                var hoverVotes = this.hoverVotes; // Used by pie only
                                return '<b>' + this.id + ' votes</b><br/>' +
                                  Highcharts.map([
                                    ['Democrats', this.demVotes, demColor],
                                    ['Republicans', this.repVotes, repColor],
                                    ['Libertarians', this.libVotes, libColor],
                                    ['Green', this.grnVotes, grnColor]
                                  ].sort(function (a, b) {
                                    return b[1] - a[1]; // Sort tooltip by most votes
                                  }), function (line) {
                                    return '<span style="color:' + line[2] +
                                      // Colorized bullet
                                      '">\u25CF</span> ' +
                                      // Party and votes
                                      (line[0] === hoverVotes ? '<b>' : '') +
                                      line[0] + ': ' +
                                      Highcharts.numberFormat(line[1], 0) +
                                      (line[0] === hoverVotes ? '</b>' : '') +
                                      '<br/>';
                                  }).join('') +
                                  '<hr/>Total: ' + Highcharts.numberFormat(this.sumVotes, 0);
                              }
                            }
                          }

                          , {
                              name: 'Separators',
                              id: 'us-all',
                              type: 'mapline',
                              data: Highcharts.geojson(Highcharts.maps['countries/us/us-all'], 'mapline'),
                              color: '#707070',
                              showInLegend: false,
                              enableMouseTracking: false
                          }, {
                              name: 'Connectors',
                              type: 'mapline',
                              color: 'rgba(130, 130, 130, 0.5)',
                              zIndex: 5,
                              showInLegend: false,
                              enableMouseTracking: false
                          }

                          ]
                        });

                        */
            /*
            // When clicking legend items, also toggle connectors and pies
            Highcharts.each(chart.legend.allItems, function (item) {
              var old = item.setVisible;
              item.setVisible = function () {
                var legendItem = this;
                old.call(legendItem);
                Highcharts.each(chart.series[0].points, function (point) {
                  if (chart.colorAxis[0].dataClasses[point.dataClass].name === legendItem.name) {
                    // Find this state's pie and set visibility
                    Highcharts.find(chart.series, function (item) {
                      return item.name === point.id;
                    }
                    ).setVisible(legendItem.visible, false);
                    // Do the same for the connector point if it exists
                    var connector = Highcharts.find(chart.series[2].points, function (item) {
                      return item.name === point.id;
                    });
                    if (connector) {
                      connector.setVisible(legendItem.visible, false);
                    }
                  }
                });
                chart.redraw();
              };
            });


            // Add the pies after chart load, optionally with offset and connectors
            Highcharts.each(chart.series[0].points, function (state) {
              if (!state.id) {
                return; // Skip points with no data, if any
              }

            console.log(state);

              var pieOffset = state.pieOffset || {},
                centerLat = parseFloat(state._midX),
                centerLon = parseFloat(state._midY);

              //console.log(centerLat,centerLon);

              // Add the pie for this state
              chart.addSeries({
                type: 'mappie',
                name: state.id,
                linkedMap: 'custom/world',
                zIndex: 10, // Keep pies above connector lines
                sizeFormatter: function () {
                  var yAxis = this.chart.yAxis[0],
                    zoomFactor = (yAxis.dataMax - yAxis.dataMin) /
                      (yAxis.max - yAxis.min);
                  return Math.max(
                    this.chart.chartWidth / 45,// * zoomFactor, // Min size
                    this.chart.chartWidth / 11 // * zoomFactor * state.sumVotes / maxVotes
                  );
                },


                tooltip: {
                  // Use the state tooltip for the pies as well
                  pointFormatter: function () {
                    return state.series.tooltipOptions.pointFormatter.call({
                      id: state.id,
                      hoverVotes: this.name,
                      demVotes: state.demVotes,
                      repVotes: state.repVotes,
                      libVotes: state.libVotes,
                      grnVotes: state.grnVotes,
                      sumVotes: state.sumVotes
                    });
                  }
                },

                data: [{
                  name: 'Democrats',
                  y:10,// state.demVotes,
                 // color: demColor
                }, {
                  name: 'Republicans',
                  y: 5,//state.repVotes,
                //  color: repColor
                }, {
                  name: 'Libertarians',
                  y:80,/// state.libVotes,
               //   color: libColor
                }, {
                  name: 'Green',
                  y:5,/// state.grnVotes,
                 // color: grnColor
                }],
                center: {
                    plotX: centerLat ,
                    plotY: centerLon ,
                }
              }, false);

            //  console.log(chart);

              // Draw connector to state center if the pie has been offset
              if (pieOffset.drawConnector !== false) {
                var centerPoint = chart.fromLatLonToPoint({
                    lat: centerLat,
                    lon: centerLon
                  }),
                  offsetPoint = chart.fromLatLonToPoint({
                    lat: centerLat + (pieOffset.lat || 0),
                    lon: centerLon + (pieOffset.lon || 0)
                  });
                chart.series[2].addPoint({
                  name: state.id,
                  path: 'M' + offsetPoint.x + ' ' + offsetPoint.y +
                    'L' + centerPoint.x + ' ' + centerPoint.y
                }, false);
              }


            }
            );
            // Only redraw once all pies and connectors have been added

            chart.redraw();

                        console.log(chart);



            */


        </script>

    <?php }
    else if ($display_select == 'world_population') {
        ?>

        <script type="text/javascript">

            Highcharts.chart('chart_div', {
                chart: {

                    zoomType: 'xy',
                    ///styledMode: true
                },
                title: {
                    text: 'World population'
                },

                xAxis: {
                    title: {
                        text: 'Year',

                    },
                },
                yAxis: {
                    title: {
                        text: 'Total',

                    },
                },
                legend: {
                    maxHeight: 70,
                },
                tooltip: {

                    pointFormat: '<strong>{series.name}</strong><br><p>Population: <b>{point.y:.0f}</b></p><br><p>Percent: <b>{point.wpercent} %</b></p>',

                    //shared: true
                },


                plotOptions: {


                    series: {
                        cursor: 'pointer',
                        point: {
                            events: {
                                click: function (e) {


                                    var m_id = e.point.x;
                                    var race = e.point.series.name;
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
                                            oper: 'get_race_data',
                                            id: m_id,
                                            race: race,
                                            data: data
                                        }),
                                        success: function (html) {
                                            $('.footer_table_result').html('<div class="clear_table"></div><div class="movie_content" id="race_' + m_id + '">' + html + '</div>');


                                        }
                                    });


                                }
                            }
                        }
                    },

                },


                series: [<?php echo $result_data; ?>]
            });

        </script>

        <?php


    }
    else if ($DomesticBox || $International || $byCountry ) {
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
                        <?php if ($diversity_select == 'diversity' && $display_select == 'ethnicity') {
                            echo " text: 'Simpson�s Diversity Index'";
                        } else echo " text: 'Box Office by year'";

                        ?>

                    },
                    legend: {
                        maxHeight: 70,
                    },
                    xAxis: {
                        startOnTick: true,
                        endOnTick: true,
                    },
                    yAxis: {
                        // min: 0,
                        <?php if ($diversity_select == 'diversity' && $display_select == 'ethnicity') {
                            echo "min: 0,max: 1,";
                        } ?>
                        title: {

                            <?php
                            if ($diversity_select == 'diversity' && $display_select == 'ethnicity') {
                                echo " text: 'Diversity Index'";
                            } else if ($display_select == 'ethnicity' && $display_xa_axis != 'Movie release date') {
                                echo "text: '" . $display_xa_axis . "'";
                            } else {
                                echo "text: 'Total Box Office'";
                            } ?>

                        }
                    },
                    tooltip: {


                        <?php if ($diversity_select == 'diversity') { ?>

                        pointFormat: '<span >Diversity Index</span>: <b> {point.y}</b>',

                        <?php } else { ?>
                        pointFormat: '<span >{series.name}</span>: <b>({point.percentage:.0f}%)</b><br/><?php if ($display_select == 'ethnicity') {
                            echo $display_xa_axis;
                        }?> $ {point.y}',
                        <?php  } ?>
                        //shared: true
                    },
                    plotOptions: {
                        column: {
                            stacking: 'normal',

                            ///  stacking: 'percent'
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
                        $(this).attr('id', 'normal').html('Stacking by Box Office');

                    } else {
                        $(this).attr('id', 'percent').html('Stacking by percent');
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
        <button class="change_stack button_big" id="percent">Stacking by percent</button>
        <!--<button class="reverse_aix button_big" id="percent">Reverse the x and y axes</button>-->
        <?php
    }

}






}