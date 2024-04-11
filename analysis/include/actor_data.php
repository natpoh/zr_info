<?php
!class_exists('MOVIE_DATA') ? include ABSPATH . "analysis/movie_data.php" : '';

class Actor_Data
{


private static function normalise_array($array)
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

public static function actor_data_template($id)
{
$array_compare_cache = array('Sadly, not' => 'N/A', '1' => 'N/A', '2' => 'N/A', 'NJW' => 'N/A', 'W' => 'White', 'B' => 'Black', 'EA' => 'Asian', 'H' => 'Latino', 'JW' => 'Jewish', 'I' => 'Indian', 'M' => 'Arab', 'MIX' => 'Mixed / Other', 'IND' => 'Indigenous');
!class_exists('RWTimages') ? include ABSPATH . "analysis/include/rwt_images.php" : '';
global $debug;
if ($debug) {
    $array_timer[] = 'before functions  ' .timer_stop_data();
}


if (strstr($id, ',')) {
    $array_id = explode(',', $id);

} else {

    $array_id[0] = $id;
}

$data = ($_POST['data']);

if (!$data) {

    $data = ('{"movie_type":[],"movie_genre":[],"inflation":null,"actor_type":["star","main"],"diversity_select":"default","display_select":"date_range_international","country_movie_select":[],"ethnycity":{"1":{"crowd":1},"2":{"ethnic":1},"3":{"jew":1},"4":{"face":1},"5":{"face2":1},"6":{"forebears":1},"7":{"familysearch":1},"8":{"surname":1}}} ');

}

$data_object = json_decode($data);
$ethnycity = $data_object->ethnycity;


$w = '';
$cat = array('star', 'main', 'extra');
$ethnycity_string = urlencode(json_encode($ethnycity));
foreach ($array_id as $id) {

    $id = intval($id);

    $sql = "SELECT * FROM `data_actors_imdb` where id =" . $id . " ";
    $r = Pdo_an::db_results_array($sql);

    $name = $r[0]['name'];

    $actor_updated = $r['lastupdate'];

/////name

    echo '<div id="' . $id . '" class="actor_main_container">';
    echo '<div class="actor_title_container">' . $name . '</div><span data-value="actor_popup" class="nte_info nte_right nte_open_down"></span>';
    /////image

    $image_link = RWTimages::get_image_link($id);

    echo '<div class="actor_image_container"><img class="actor_image"  title="What ethnicity is '.$name.'?" alt="'.$name.' ethnicity" 
        src="'.$image_link.'" /></div>';


////////get actor data
    !class_exists('INTCONVERT') ? include ABSPATH . "analysis/include/intconvert.php" : '';
    $array_ints = INTCONVERT::get_array_ints();

    !class_exists('OptionData') ? include ABSPATH . "analysis/include/option.php" : '';
    $vd_data = unserialize(unserialize(OptionData::get_options('','critic_matic_settings')));
    $verdict_method=0; if ($vd_data["an_verdict_type"]=='w'){$verdict_method=1;}

    $a_sql = "actor_id ='" . $id . "' ";

    ///$array_movie_result = get_movie_data_from_db('', $a_sql, '');
    ///$array_movie_result  = MOVIE_DATA::get_movie_data_from_db($id, $a_sql,'');

    $av = MOVIE_DATA::get_actor_meta($id);

///	tmdb_id	gender	ethnic	jew	kairos	bettaface	placebirth	surname	familysearch	forebears	crowdsource	verdict	verdict_weight	n_ethnic	n_jew	n_kairos	n_bettaface	n_surname	n_familysearch	n_forebears	n_crowdsource	n_verdict	n_verdict_weight	img	tmdb_img	last_update

    $face2 = $array_ints[$av['n_bettaface']];
    $face = $array_ints[$av['n_kairos']];
    $surname = $array_ints[$av['n_surname']];
    $etn = $array_ints[$av['n_ethnic']];
    $jew = $array_ints[$av['n_jew']];
    $crowd = $array_ints[$av['n_crowdsource']];
    $forebears = $array_ints[$av['n_forebears_rank']];
    $familysearch = $array_ints[$av['n_familysearch']];

    $verdict =$array_ints[ $av['n_verdict']];

    if ($verdict_method==1)
    {
        $verdict = $array_ints[$av['n_verdict_weight']];
        if (!$verdict)
        {
            $verdict = $array_ints[$av['n_verdict']];
        }
    }






///var_dump($ethnycity);
///
///
///
    if ($debug) {
        $array_timer[] = 'get_movie_data_from_db  ' .timer_stop_data();
    }
    foreach ($ethnycity as $order => $data) {
        foreach ($data as $type => $enable) {

            if ($type == 'forebears') {
                echo '<p class="in_hdr">Forebears Surname Analysis:</p>';
                if ($forebears) {


                    echo '<p class="verdict">Verdict: ' . $array_compare_cache[$forebears]. '</p>';
                    echo '<a class="source_link"  target="_blank" href="https://forebears.io/surnames">Source: https://forebears.io/surnames</a>';
                }
                else echo '<p class="verdict">N/A</p>';
            }
            if ($type == 'familysearch') {
                echo '<p class="in_hdr">FamilySearch Surname Analysis:</p>';
                if ($familysearch) {


                    echo '<p class="verdict">Verdict: ' . $array_compare_cache[$familysearch]. '</p>';
                    echo '<a class="source_link"  target="_blank" href="https://www.familysearch.org/en/surname">Source: https://www.familysearch.org/en/surname</a>';
                }
                else echo '<p class="verdict">N/A</p>';
            }

            if ($type == 'surname') {
                echo '<p class="in_hdr">Surname Analysis:</p>';
                if ($surname) {

                    $sql = "SELECT *  FROM data_actors_ethnicolr where aid =" . $id;

                    $q = Pdo_an::db_results_array($sql);
                    $r = $q[0];

                    $actor_data = [];



                    $data = $r['wiki'];
                    if ($data) {
                        $data = json_decode($data,1);

                        $actor_data['EA'] += (float)$data[5] * 100;



                        $actor_data['EA'] += (float)$data[9] * 100;


                        $actor_data['I'] += (float)$data[13] * 100;


                        $actor_data['B'] += (float)$data[17] * 100;


                        $actor_data['M'] += (float)$data[21] * 100;


                        $actor_data['W'] += (float)$data[25] * 100;


                        $actor_data['W'] += (float)$data[29] * 100;


                        $actor_data['JW'] += (float)$data[33] * 100;
                        $actor_data['W'] += (float)$data[37] * 100;
                        $actor_data['W'] += (float)$data[41] * 100;
                        $actor_data['H'] += (float)$data[45] * 100;
                        $actor_data['W'] += (float)$data[49] * 100;

                        $actor_data['W'] += (float)$data[53] * 100;


                        arsort($actor_data);
                        $key = array_keys($actor_data);

                        $surname = $array_compare_cache[$r['verdict']];


                        if ($surname) {
                            $surname = ucfirst($surname);
                        } else {
                            $surname = 'N/A';
                        }
                        $actor_data = self::normalise_array($actor_data);

                        echo '<div class="small_desc">';
                        foreach ($actor_data as $i => $v) {
                            echo $array_compare_cache[$i] . ': ' . $v . '%<br>';
                        }
                        echo '</div>';

                        $key = array_keys($actor_data);


                        echo '<p class="verdict">Verdict: ' .  $surname . '</p>';
                        echo '<a class="source_link"  target="_blank" href="https://pypi.org/project/ethnicolr/">Source: https://pypi.org/project/ethnicolr/</a>';
                    } else echo '<p class="verdict">N/A</p>';
                } else echo '<p class="verdict">N/A</p>';

                if ($debug) {
                    $array_timer[] = 'after surname  ' .timer_stop_data();
                }
            }
            if ($type == 'jew') {

                echo '<p class="in_hdr">JewOrNotJew:</p>';
                if ($jew) {
                    //////gett_jew_data

                    $jverdict = 'N/A';
                    $sql = "SELECT Verdict, actor_id FROM `data_actors_jew` WHERE actor_id=" . $id;
                    $q = Pdo_an::db_results_array($sql);


                    foreach ($q as $r) {


                        if ($r['Verdict']) {
                            $jverdict = $r['Verdict'];
                        }

                    }

                    echo '<p class="verdict">Verdict: ' . $jverdict . '</p>';


                    echo '<a class="source_link" target="_blank" href="http://jewornotjew.com/">Source: http://jewornotjew.com/</a>';

                } else {
                    echo '<p class="verdict">N/A</p>';
                }
                if ($debug) {
                    $array_timer[] = 'after jew  ' .timer_stop_data();
                }
            }
            if ($type == 'face') {

                echo '<p class="in_hdr">Facial Recognition by Kairos:</p>';
                if ($face) {


                    $sql = "SELECT  *  FROM data_actors_race where actor_id =" . $id . " LIMIT 1";

                    $q = Pdo_an::db_results_array($sql);
                    $row = $q[0];
                    $imgid = $row['actor_id'];

                    $array_race['EA'] = $row['Asian'];
                    $array_race['B'] = $row['Black'];
                    $array_race['H'] = $row['Hispanic'];
                    $array_race['W'] = $row['White'];


                    $array_race = self::normalise_array($array_race);
                    arsort($array_race);

                    echo '<div class="small_desc">';
                    foreach ($array_race as $i => $v) {
                        echo $array_compare_cache[$i] . ': ' . $v . '%<br>';
                    }
                    echo '</div>';


                    echo '<p class="verdict">Verdict: ' . $array_compare_cache[$face] . '</p>';

                    echo '<a class="source_link" target="_blank" href="https://kairos.com/">Source: https://kairos.com/</a>';

                } else   echo '<p class="verdict">N/A</p>';
                if ($debug) {
                    $array_timer[] = 'after face  ' .timer_stop_data();
                }
            }
            if ($type == 'face2') {

                echo '<p class="in_hdr">Facial Recognition by Betaface:</p>';
                if ($face2) {


                    $sql = "SELECT  *  FROM data_actors_face where actor_id =" . $id . " LIMIT 1";
                    $fr = Pdo_an::db_results_array($sql);
                    $brace = $fr[0]['race'];
                    $prcnt = $fr[0]['percent'];

                    if ($brace && $prcnt)
                    {
                        echo '<div class="small_desc">';

                        echo ucfirst($brace) . ': ' . $prcnt*100 . '%<br>';

                        echo '</div>';
                    }


                    echo '<p class="verdict">Verdict: ' . $array_compare_cache[$face2] . '</p>';


                    echo '<a class="source_link" target="_blank" href="https://www.betafaceapi.com/demo_old.html">Source: https://www.betafaceapi.com/</a>';

                } else     echo '<p class="verdict">N/A</p>';

                if ($debug) {
                    $array_timer[] = 'after face2  ' .timer_stop_data();
                }
            }
            if ($type == 'ethnic') {


                echo '<p class="in_hdr">Ethnicelebs:</p>';

                if ($etn) {
                    $sql = "SELECT Ethnicity as ethnicity, Tags, actor_id, Link   FROM `data_actors_ethnic` WHERE  actor_id= " . $id . " limit 1";

                    $q = Pdo_an::db_results_array($sql);
                  foreach ($q as $r) {

                        if ($r['ethnicity']) {

                            echo '<div class="small_desc">';
                            echo $r['ethnicity'] . '<br>';
                            echo '</div>';

                        }
                        if ($r['Link'])
                        {
                            $link = $r['Link'];
                        }

                    }

                    $ethnic_result = $etn;
                    if ($array_compare_cache[$ethnic_result]) {
                        echo '<p class="verdict">Verdict: ' . $array_compare_cache[$ethnic_result] . '</p>';
                    } else {
                        echo '<p class="verdict">Verdict: ' . $ethnic_result . '</p>';
                    }

                    if ($link) {

                        echo '<a class="source_link"  target="_blank" href="' . $link . '">Source: ' . $link . '</a>';
                    } else {
                        echo '<a class="source_link"  target="_blank" href="http://ethnicelebs.com/">Source: http://ethnicelebs.com/</a>';
                    }

                } else {
                    echo '<p class="verdict">N/A</p>';

                }

                if ($debug) {
                    $array_timer[] = 'after ethnic  ' .timer_stop_data();
                }
            }
            if ($type == 'crowd') {


                echo '<p class="in_hdr">Crowdsource:</p>';

                if ($crowd)
                {
                    $sql = "SELECT *   FROM `data_actors_crowd` WHERE  actor_id= " . $id . " and  	`status` =  1";

                    $rows = Pdo_an::db_results_array($sql);

                    foreach ($rows as $r)
                    {

                        if ($r['verdict']) {

                            echo '<div class="small_desc">';
                            echo $array_compare_cache[$r['verdict']] . '<br>';
                            echo '</div>';

                        }


                        if ($r['comment']) {
                            echo '<p>User comment: ' . $r['comment'] . '</p>';

                        }
                        if ($r['link']) {
                            echo '<a class="source_link"  target="_blank" href="' . $r['link'] . '">Source: ' . $r['link'] . '</a>';
                        }
                    }
                } else {
                    echo '<p class="verdict">N/A</p>';



                }

                echo   '<div class="actor_crowdsource_container" ><p>Please help improve ZR by correcting & adding data.</p>
                    <a id="op" data-value="'.$id.'" class="actor_crowdsource" href="#">Edit Actor Data</a>
                    <div class="crowd_data"></div></div>';


                if ($debug) {
                    $array_timer[] = 'after ethnic  ' .timer_stop_data();
                }
            }
        }
    }

    if (!$verdict)$verdict=1;
    if ($verdict)
    {
        echo '<p style="font-size: 20px; margin: 20px 0px; text-transform: uppercase" class="verdict">Final Verdict:  '.$array_compare_cache[$verdict].'</p>';

        echo '<p><a href="#" data-actor="'.$id.'" class="calculate_actor_data">Methodology</a></p>';

    }

    ///update data info


    $update_array = ['actror_data_update'=>['time'=>$actor_updated,'comment'=>'Actor IMDB data:']];

    foreach ($update_array as $i=>$v)
    {

        $asctor_u.= MOVIE_DATA::last_update_container($id,$i,$v['time'],$v['comment'],86400);

    }
    $update_container = '<div class="actor_update_data"><p>Last updated: </p>'.$asctor_u.'</div>';
    echo $update_container;



    echo '<a  target="_blank" class="admin_link" href="https://info.antiwoketomatoes.com/analysis/include/scrap_imdb.php?actor_logs='.$id.'">Actor info</a>';


    if ($debug) {
        $array_timer[] = 'end  ' .timer_stop_data();
        /// print_timer($array_timer);
    }

    echo '</div>';
}
}
}