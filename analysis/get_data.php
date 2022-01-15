<?php
ini_set('memory_limit', '4096M');
set_time_limit(300);
error_reporting(E_ERROR);

if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');

global $debug;
$debug = 0;
if (isset($_POST['data'])) {
    $data = ($_POST['data']);
    $data_object = json_decode($data);
    $selected_color = $data_object->color;

}

if (!function_exists('timer_start_data')) {
    function timer_start_data()
    { // if called liketimer_stop_data(1), will echo $timetotal
        global $timestart;
        $timestart = microtime(1);

    }
}
if (!function_exists('timer_stop_data')) {
    function timer_stop_data ($display = 0, $precision = 3)
    { // if called liketimer_stop_data(1), will echo $timetotal
        global $timestart, $timeend;
        $mtime = microtime(1);
        $timetotal = $mtime - $timestart;
        $r = number_format($timetotal, $precision);

        return $r;
    }
}
function print_timer($array_timer)
{
    foreach ($array_timer as $index => $val) {
        echo '<div class="table_print"><span>' . $index . '</span><span>' . $val . '</span></div>';
    }
}

timer_start_data();

if ($debug) {
    $array_timer[] = 'start  ' .timer_stop_data();
}
global $included;

//DB config
!defined('DB_HOST_AN') ? include ABSPATH . 'analysis/db_config.php' : '';
//Abstract DB
!class_exists('Pdoa') ? include ABSPATH . "analysis/include/Pdoa.php" : '';

!class_exists('MOVIE_DATA') ? include ABSPATH . "analysis/movie_data.php" : '';


    global $pdo;
    pdoconnect_db();
    $pdo->query('use imdbvisualization');


global $array_ethnic_color;
if ($selected_color == 'default') {
    /*
        ?>

        <style type="text/css">
            .highcharts-color-0 {
                fill: #2b908f!important;
                stroke: #2b908f!important;
            }

            .highcharts-color-1 {
                fill: #90ee7e!important;
                stroke: #90ee7e!important;
            }

            .highcharts-color-2 {
                fill: #f45b5b!important;
                stroke: #f45b5b!important;
            }

            .highcharts-color-3 {
                fill: #7798BF!important;
                stroke: #7798BF!important;
            }

            .highcharts-color-4 {
                fill: #aaeeee!important;
                stroke: #aaeeee!important;
            }

            .highcharts-color-5 {
                fill: #ff0066;
                stroke: #ff0066;
            }

            .highcharts-color-6 {
                fill: #eeaaee!important;
                stroke: #eeaaee!important;
            }

            .highcharts-color-7 {
                fill: #55BF3B!important;
                stroke: #55BF3B!important;
            }

            .highcharts-color-8 {
                fill: #DF5353!important;
                stroke: #DF5353!important;
            }

            .highcharts-color-9 {
                fill: #7798BF!important;
                stroke: #7798BF!important;
            }

            .highcharts-color-10 {
                fill: #aaeeee!important;
                stroke: #aaeeee!important;
            }

            .highcharts-color-11 {
                fill: #0006ee!important;
                stroke: #0006ee!important;
            }


            .highcharts-color-12 {
                fill: #ee002d!important;
                stroke: #ee002d!important;
            }
            .highcharts-color-13 {
                fill: #eece00!important;
                stroke: #eece00!important;
            }
            .highcharts-color-14 {
                fill: #00ee1a!important;
                stroke: #00ee1a!important;
            }
            .highcharts-color-15 {
                fill: #0076ee!important;
                stroke: #0076ee!important;
            }
        </style>


        <?php
    */
    global $array_ethnic_color;

    $array_ethnic_color = array(

        'White' => '#2b908f',
        'Asian' => '#90ee7e',
        'Black' => '#f45b5b',
        'Dark Asian' => '#7798BF',
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

    $q = $pdo->prepare($sql);
    $q->execute();
    $r = $q->fetch();
    $val = $r['val'];
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


$array_timer = [];
if (!function_exists('fileman')) {
    function fileman($way)
    {
        if (!file_exists($way))
            if (!mkdir("$way", 0777)) {
                // p_r($way);
                //  throw new Exception('Can not create dir: ' . $way . ', check cmod');
            }
        return null;
    }
}

function single_movie()
{

    $id = intval($_POST['id']);
    $global_actor_id = '';


    if (!$data_object) {
        $data_object = get_post_data_request($_POST['data']);
    }


    ///////////////////////////////////////////////////setup data //////////////////////


    $array_data = $data_object->result_data;
    $display_select = $data_object->display_select;
    $ethnycity = $data_object->ethnycity;
    $start = $array_data['start'];
    $end = $array_data['end'];
    $join_dop = $array_data['join'];
    $idop = $array_data['dop'];
    $inflation_array = $data_object->inflation;
    $actor_type = $data_object->actor_type;
    $display_xa_axis = $data_object->display_xa_axis;
    $diversity_select = $data_object->diversity_select;
    $post_country_2 = $data_object->post_country_2;
    $idop_yaer = $data_object->idop_yaer;
    global $country;
    $country = $data_object->country_movie_select;
    ///////////////////////////////////////////////////setup data //////////////////////


    global $debug;


    if ($id) {

        global $pdo;
        ////////production budget
        $sql = "SELECT Production_Budget  FROM `data_movie_budget`  where MovieID=" . $id;
        $q = $pdo->prepare($sql);
        $q->execute();

        $r = $q->fetch();

        $movies_budget = $r['Production_Budget'];


        $sql = "SELECT * FROM data_movie_imdb  where movie_id=" . $id;

        $q = $pdo->prepare($sql);
        $q->execute();
        $q->setFetchMode(PDO::FETCH_ASSOC);

        $r = $q->fetch();
        $title = $r['title'];
        $rwt_id = $r['id'];
    }

    ///get movie template

        include($_SERVER['DOCUMENT_ROOT'] . '/wp-content/themes/custom_twentysixteen/template/movie_single_template.php');
        include($_SERVER['DOCUMENT_ROOT'] . '/wp-content/themes/custom_twentysixteen/template/section_home_template.php');

        $movie_template = template_single_movie($rwt_id, $title, $name = '', 1);

        echo $movie_template;



    /////////add rating

    if (isset($_POST['refresh_rating']))
    {

        if ($_POST['refresh_rating']==1)
        {

//PgRatingCalculate
            !class_exists('PgRatingCalculate') ? include ABSPATH . "analysis/include/pg_rating_calculate.php" : '';
            PgRatingCalculate::CalculateRating($id,$rwt_id,1);
            return;
        }

    }



    $actors_array=  MOVIE_DATA::get_actors_from_movie($rwt_id,'',$actor_type);

    $array_movie_result = MOVIE_DATA::get_movie_data_from_db($rwt_id, '', 0, $actor_type , $actors_array, $diversity_select, $ethnycity, 1 );


    $array_movie_result = get_movie_data_from_db($id, '', '', $actor_type, $actors_array, $diversity_select, $ethnycity, 1);


    $data = $array_movie_result['data'];

    //////////create result_data
     echo $array_movie_result['current'];

     $all_data = $array_movie_result['all_data'];





    //$actor_content = set_table_ethnic($data, $country);

    // echo $actor_content;


    $chart = '';

    global $array_ethnic_color;
    $array_convert_type = array('ethnic' => 'ethnic', 'jew' => 'jew', 'face' => 'kairos', 'face2' => 'bettaface', 'surname' => 'surname');

    $chartbody = '';
    foreach ($ethnycity as $order => $data) {
        foreach ($data as $type => $enable) {
            $link = '';
            if ($enable) {


                if ($enable && $type == 'surname') {
                    $link = '<a class="source_link" target="_blank" href="https://pypi.org/project/ethnicolr/">Source: https://pypi.org/project/ethnicolr/</a>';
                    $surtext = '&sur=1';
                }
                if ($enable && $type == 'jew') {
                    $link = '<a class="source_link" target="_blank" href="http://jewornotjew.com/">Source: http://jewornotjew.com/</a>';
                    $jewtext = '&jew=1';
                }
                if ($enable && $type == 'face') {
                    $link = '<a class="source_link" target="_blank" href="https://kairos.com/">Source: https://kairos.com/</a>';
                    $facetext = '&face=1';
                }
                if ($enable && $type == 'face2') {
                    $link = '<a class="source_link" target="_blank" href="https://www.betafaceapi.com/demo_old.html">Source: https://www.betafaceapi.com/</a>';
                    $face2text = '&face2=1';
                }
                if ($enable && $type == 'ethnic') {
                    $link = '<a class="source_link" target="_blank" href="http://ethnicelebs.com/">Source: http://ethnicelebs.com/</a>';
                    $ethnictext = '&eth=1';
                }

                $typeetnic = $array_convert_type[$type];


                if ($all_data['request'][$typeetnic]) {
                    $data = $all_data['request'][$typeetnic];
                    $data = normalise_array($data);
                    /// var_dump($data);
                    // echo '<br><br>';
                    $data_series = '';

                    foreach ($data as $race => $count) {
                        if (!$count) $count = 0;

                        $data_series .= "{
            name: '" . $race . "',
            y: " . $count . ",
            color: '" . $array_ethnic_color[$race] . "',
            sliced: true,
            selected: true
        },";
                    }
                    $chart_div .= "<div class='card style_1'><div class='chart_container' id='container_" . $typeetnic . "'></div>" . $link . "</div>";


                    $chart .= "
Highcharts.chart('container_" . $typeetnic . "', {
    chart: {
        plotBackgroundColor: null,
        plotBorderWidth: null,
        plotShadow: false,
        type: 'pie'
    },
    title: {
        text: '" . ucfirst($typeetnic) . "'
    },
    tooltip: {
        pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
    },
    accessibility: {
        point: {
            valueSuffix: '%'
        }
    },
    plotOptions: {   
        
        pie: {
             size: 120,
           allowPointSelect: true,
             cursor: 'pointer',
                dataLabels: {
                enabled: true,
                distance: 20,
                format: '<b>{point.name}</b>: {point.percentage:.1f} %',
                
                style: {
                    fontWeight: 'bold',
                    color: 'white',
                    fontSize:'12px'
                }
            },
               
                format: '<b>{point.name}</b>: {point.percentage:.1f} %'
            }
  
    },
    series: [{
        name: 'Race',
        colorByPoint: true,
        data: [" . $data_series . "]
    }]
});
";


                }
            }
        }
    }

    if (!$only_actors) {
        $chart = "<script type='text/javascript'>" . $chart . "</script>";

        $section_data = $section;

        $section_data = str_replace('{post_id}', 'none', $section_data);
        $section_data = str_replace('{class}', 'section_gray section_actors section_ethnic', $section_data);
        $section_data = str_replace('{id}', 'ethn', $section_data);
        $section_data = str_replace('{title}', 'Ethnic data', $section_data);
        $section_data = str_replace('{content}', $chart_div, $section_data);
        $content .= $section_data . $chart;

        $content .= $array_movie_result['total'];
    }

    $ethnycity_string = urlencode(json_encode($ethnycity));

    $actors_array =MOVIE_DATA::check_actors_to_stars($actors_array,$actor_type);


    $actor_type_min = array('star' => 's', 'main' => 'm', 'extra' => 'e','director'=>'director','writer'=>'writer','cast_director'=>'cast_director','producer'=>'producer');
    if (!$global_actor_id) {
        if (is_array($actors_array)) {
            if ($only_actors) {
                $content_array = [];

                foreach ($actor_type as $type) {

                    foreach ($actors_array[$actor_type_min[$type]] as $id => $enable) {

                        $name =MOVIE_DATA::get_actor_name($id);

                        if ($id) {
                            $dop_string='';


                                $dop_string = '<span class="a_data_n_d">'.str_replace('_',' ',ucfirst($type)).'</span>';


                            $actor_cntr = '<div class="card style_1 img_tooltip">
                     <a  class="actor_info" data-id="' . $id . '"  href="#">
                     <div class="a_data_n">' . $name .$dop_string. ' </div>
                     <img loading="lazy" class="a_data_i" src="https://' . $_SERVER['HTTP_HOST'] . '/analysis/create_image.php?id=' . $id . '&e=' . $ethnycity_string . '" />
                     </a><span class="actor_edit actor_crowdsource_container"><a title="Edit Actor data" id="op" data-value="' . $id . '" class="actor_crowdsource button_edit" href="#"></a></span>
                    </div>';

                            $addtime = time();
                            $content_array['result'][$addtime . '_' . $id] = array('pid' => $id, 'content_data' => $actor_cntr);

                        }
                    }

                    //$content_array['html'][$type] = $array_movie_result['current'];


                }


            }
            else {


                foreach ($actor_type as $type) {
                    $actor_cntr = '';
                    foreach ($actors_array[$actor_type_min[$type]] as $id => $e) {

                        if ($id) {

                            $name =MOVIE_DATA::get_actor_name($id);

                            $dop_string = '<span class="a_data_n_d">'.str_replace('_',' ',ucfirst($type)).'</span>';

                            $actor_cntr .= '<div class="card style_1 img_tooltip">
                     <a  class="actor_info" data-id="' . $id . '"  href="#">
                     <div class="a_data_n">' . $name .$dop_string. ' </div>
                     <img class="a_data_i" src="https://' . $_SERVER['HTTP_HOST'] . '/analysis/create_image.php?id=' . $id . '&e=' . $ethnycity_string . '" />
                     </a>
                    </div>';
                        }
                    }


                    $section_data = $section;

                    $section_data = str_replace('{post_id}', 'none', $section_data);
                    $section_data = str_replace('{class}', 'section_actors', $section_data);
                    $section_data = str_replace('{id}', $type, $section_data);
                    $section_data = str_replace('{title}', ucfirst($type), $section_data);
                    $section_data = str_replace('{content}', $actor_cntr, $section_data);
                    $content .= $section_data;

                }
            }
        }

    }

    echo $content;

    return;
}


if (!function_exists('check_and_create_dir')) {
    function check_and_create_dir($path)
    {
        if ($path) {
            $arr = explode("/", $path);

            $path = $_SERVER['DOCUMENT_ROOT'] . '/';

            foreach ($arr as $a) {
                if ($a) {
                    $path = $path . $a . '/';
                    fileman($path);
                }
            }
            return null;
        }
    }
}
if (!function_exists('save_file_cache')) {
    function save_file_cache($cachename = null, $string = '', $path = 'wp-content/uploads/cache_analysis_request')
    {

        chdir($_SERVER['DOCUMENT_ROOT']);
        check_and_create_dir($path);

        $file_name = $_SERVER['DOCUMENT_ROOT'] . '/' . $path . '/' . $cachename . '.html';
///echo $file_name;
        $fp = fopen($file_name, "w");
        fwrite($fp, $string);
        fclose($fp);
        chmod($file_name, 0777);


    }
}
if (!function_exists('load_file_cache')) {
    function load_file_cache($start, $end, $idop, $path = 'wp-content/uploads/cache_analysis_request')
    {
        $file_name = '';

        chdir($_SERVER['DOCUMENT_ROOT']);

        check_and_create_dir($path);

        $filepath = $_SERVER['DOCUMENT_ROOT'] . '/' . $path . '/';


        $regv = '#([0-9]{4})_([0-9]{4})#';
        $cur_year = date('Y', time());

        foreach (glob($filepath . '*_' . $idop . '.html') as $file) {


            if (preg_match($regv, $file, $mach)) {
                $s = $mach[1];
                $e = $mach[2];
                if ($start >= $s && $e >= $end) {

                    if ($end == $cur_year) {
                        $cache = 86400;
                    } else {
                        $cache = 86400 * 7;
                    }

                    if (time() - filemtime($file) > $cache) {
                        unlink($file);

                    } else {
                        $file_name = $file;
                        break;
                    }

                }
            }
        }


        if ($file_name) {

            if (file_exists($file_name)) {

                $fbody = file_get_contents($file_name);

                if ($fbody) {
                    return $fbody;
                }

            }
        }
        return 0;
    }
}


if ($debug) {
    $array_timer[] = 'config  ' .timer_stop_data();
}

global $pdo;
global $array_exclude;
$sql = "SELECT * FROM `options` where id =4 limit 1";

$q = $pdo->prepare($sql);
$q->execute();
$r = $q->fetch();
$val = $r['val'];
$array_exclude = [];
$val = str_replace('\\', '', $val);
$array_exclude = explode(',', $val);
foreach ($array_exclude as $index => $val) {
    $array_exclude[$index] = trim(str_replace("'", "", $val));
}
///var_dump($array_exclude);

global $array_compare;
$array_compare = [];
$sql = "SELECT * FROM `options` where id =3 limit 1";

$q = $pdo->prepare($sql);
$q->execute();
$r = $q->fetch();
$val = $r['val'];
$val = str_replace('\\', '', $val);
$array_compare_0 = explode("',", $val);
foreach ($array_compare_0 as $val) {
    $val = trim($val);
    // echo $val.' ';
    $result = explode('=>', $val);
    ///var_dump($result);
    $index = trim(str_replace("'", "", $result[0]));
    $value = trim(str_replace("'", "", $result[1]));

    $regv = '#([A-Za-z\,\(\)\- ]{1,})#';

    if (preg_match($regv, $index, $mach)) {
        $index = $mach[1];
    }


    $index = trim($index);

    $array_compare[$index] = $value;
}


$actor_type_min = array('star' => 's', 'main' => 'm', 'extra' => 'e');


//var_dump($array_compare);


if ($debug) {
    $array_timer[] = 'before functions  ' .timer_stop_data();
}



function get_country($Details)
{
    $reg = '#Country\:([^\;]+)#';
    if (preg_match($reg, $Details, $mach)) {


        return trim($mach[1]);
    }
}

function get_normal_date($Details, $Year)
{
    $reg = '#([0-9]{1,2} [a-zA-z]+ [0-9]{4})#';

    if (preg_match($reg, $Details, $mach)) {
        return strtotime($mach[1]);
    }

    return strtotime('01.01.' . $Year);

}

function wph_cut_by_words($maxlen, $text)
{
    $len = (mb_strlen($text) > $maxlen) ? mb_strripos(mb_substr($text, 0, $maxlen), ' ') : $maxlen;
    $cutStr = mb_substr($text, 0, $len);
    $temp = (mb_strlen($text) > $maxlen) ? $cutStr : $cutStr;
    return $temp;
}

function checkcast($Leading, $actor_array_name)
{
    $result = '';
    $array_result = [];
    $e = 0;
    foreach ($actor_array_name as $id => $name) {
        if (strstr($Leading, $name)) {
            $array_result[] = $id;
            $e = 1;
        }
    }
    if ($e) {

        $result = $array_result;
    }
    return $result;
}
function check_ethnic_array($v1, $v2)
{
    if (strstr(strtolower(trim($v1)), strtolower(trim($v2)))) {
        return 0;
    }
    return 1;
}


function get_movie_data_from_db($imdb_id, $a_sql = '', $only_etnic = 0, $actor_type = [], $actors_array = [], $diversity_select = "default", $ethnycity = [], $all_data = '')
{
    $sql = "SELECT id FROM `data_movie_imdb` where `movie_id` ='" . $imdb_id . "' limit 1 ";
    $r = Pdo_an::db_fetch_row($sql);
    $id =  $r->id;

   return MOVIE_DATA::get_movie_data_from_db($id, $a_sql, $only_etnic, $actor_type , $actors_array, $diversity_select, $ethnycity, $all_data );
}


function add_actor_to_movie_cache($id, $cat_string, $actors)
{
    global $pdo;

    $sql2 = "SELECT id FROM `data_movie_actor_cache` WHERE  movie_id = '" . $id . "'  limit 1";
    $q = $pdo->prepare($sql2);
    $q->execute();
    $q->setFetchMode(PDO::FETCH_ASSOC);
    $r2 = $q->fetch();
    if (!$r2['id']) {
        $a_result_string = json_encode($actors, JSON_FORCE_OBJECT);

        $sql = "INSERT INTO `data_movie_actor_cache` VALUES (NULL, '" . $id . "', '" . $cat_string . "',:data,'" . time() . "')";
        $q = $pdo->prepare($sql);
        $q->bindParam(':data', $a_result_string);
        $q->execute();
    }


}

function set_data_from_movie($movie_id, $actor_type)
{
    global $pdo;


    ////////////get_all_actors

    foreach ($actor_type as $actor_type_enable) {
        if ($actor_type_enable) {
            if ($actor_type_enable != 'all') {

                $dop_actors = "and `data_actors`.Category  = '" . $actor_type_enable . "' ";


            }

            $sql = "SELECT actor_id FROM `data_actors`,`data_movie` where `data_actors`.MovieID  = `data_movie`.MovieID
 and `data_movie`.MovieID = '" . $movie_id . "' " . $dop_actors . " limit 10";

            ///    echo $sql . '<br>';

            $q = $pdo->prepare($sql);
            $q->execute();
            $q->setFetchMode(PDO::FETCH_ASSOC);

            if ($q->fetchColumn()) {

                while ($r = $q->fetch()) {
                    /// echo 'actor_id=' . $r['actor_id'] . '<br>';

                    /////get ethnic data from actors


                }
            }


        }
    }


}

/*
function get_data_etn($movie_id, $actor_type)
{
    global $pdo;

    $array_movie_result = get_movie_data_from_db($movie_id, '', 1);

    return $array_movie_result;
}
*/

function add_to_array($input, $output, $i)
{
    $other = 100;

    foreach ($input as $key => $val) {

        $rv = ($val / $i) * 100;
        $rv = round($rv, 2);
        $output  [$key] += $rv;
        $other -= $rv;

    }
    if ($other != 100) {
        $output  ['Other'] = $other;
    }
    return $output;

}

function normalise_array($array)
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


function data_etnic_to_table($data, $diversity_select)
{

    ////get us population
//////Полные тексты	id 	name 	Population Total 	White 	Non-White 	Arab 	Asian 	Black 	Dark Asian 	Indigenous 	Latino 	Mixed / Other 	Jewish (Core) 	Jewish (Law of Return)

    // arsort($data);
    if (is_array($data)) {


        $i = 0;
        $actor_content = '';
        $actor_heder = '';
        $actor_result = '';
        foreach ($data as $name => $summ) {

            if ($i >= 5) {
                break;
            }
            $actor_heder .= '<td>' . $name . '</td>';
            $actor_result .= '<td>' . $summ . '%</td>';


            $i++;

        }


        $actor_content = '<table  class="tablesorter-blackice no_overflow">
<tr>' . $actor_heder . '</tr>
<tr class="actor_data">' . $actor_result . '</tr>
</table>';

        return $actor_content;
    } else {
        return 'no data';
    }


}

function get_post_data_request($data, $single = '')
{
    global $pdo;

    $start = 1970;
    $end = 2018;

    $type = 'box';
    $data_object = json_decode($data);

    global $animation;
    global $diversity_select;
    $inflation_array = [];
    global $inflation_array;


    $start = $data_object->start;
    $end = $data_object->end;
    $IMDb = $data_object->IMDb;
    $animation = $data_object->animation;
    $inflation = $data_object->inflation;
    $country = $data_object->country;

    $data_type = $data_object->data_type;
    $ethnycity = $data_object->ethnycity;

    $actor_type = $data_object->actor_type;
    $limit = $data_object->movies_limit;
    $diversity_select = $data_object->diversity_select;
    $display_select = $data_object->display_select;

    $country_movie_select = $data_object->country_movie_select;

    $budget_min = $data_object->budget_min;
    $budget_max = $data_object->budget_max;

    $movie_type = $data_object->movie_type;
    $movie_genre = $data_object->movie_genre;
    $display_xa_axis = $data_object->display_xa_axis;







    if (!$actor_type) {
        $actor_type = array('star', 'main', 'extra');
    }

    if ($inflation) {
        $sql = "SELECT * FROM `data_inflation` ";
        $q = $pdo->prepare($sql);
        $q->execute();
        $q->setFetchMode(PDO::FETCH_ASSOC);

        $array_years = [];

        while ($r = $q->fetch()) {
            $inflation_array[$r['Year']] = $r['value'];
        }
    }
    $type_post = ($_POST['type']);

    $join_dop = '';
    $idop = '';

    if ($movie_type) {
        $idopcnt_type = '';
        foreach ($movie_type as $type) {
            $idopcnt_type .= " OR data_movie_imdb.`type` = '" . $type . "' ";

        }
        $idopcnt_type = substr($idopcnt_type, 3);

        $idop .= " and (" . $idopcnt_type . ")";
    }

    if ($movie_genre) {
        $idopcnt_type = '';
        foreach ($movie_genre as $type) {
            $idopcnt_type .= " OR data_movie_imdb.`genre` LIKE '%" . $type . "%' ";

        }
        $idopcnt_type = substr($idopcnt_type, 3);

        $idop .= " and (" . $idopcnt_type . ")";
    }


    if ($display_select == 'ethnicity' && $display_xa_axis!='Rating') {

    $display_xa_axis = 'Box Office Worldwide';

}

    else if ($display_select == 'performance_country' || $display_xa_axis == 'Box Office Domestic') {

        $idop .= " and (data_movie_imdb.`box_usa` > 0 ) ";


    }


    if ($display_xa_axis == 'Box Office International' || $display_xa_axis == 'Box Office Worldwide') {

        $idop .= " and (data_movie_imdb.`box_world` > 0 ) ";

    } else if ($display_xa_axis == 'DVD Sales Domestic') {

        $join_dop .= " INNER join data_movie_rank  on (data_movie_rank.MovieID =  data_movie_imdb.movie_id ) ";
        $idop .= " and (data_movie_rank.`" . $display_xa_axis . "`>0) ";

    }
    else if ( $display_xa_axis=='Rating') {

        $idop .= " and (data_movie_imdb.`rating` > 0 ) ";

    }
    if ($display_xa_axis == 'Box Office Profit actual' || $budget_min || $budget_max) {


        if ($budget_min || $budget_max) {

            $join_dop .= " INNER join data_movie_budget  on (data_movie_budget.MovieID =  data_movie_imdb.movie_id ) ";

            if ($budget_min) {
                $idop .= " and data_movie_budget.`Production_Budget`>=" . intval($budget_min) . " ";
            }
            if ($budget_max) {
                $idop .= " and data_movie_budget.`Production_Budget`<=" . intval($budget_max) . " ";
            }


        } else if ($display_xa_axis == 'Box Office Profit actual') {

            $join_dop .= " INNER join data_movie_budget  on (data_movie_budget.MovieID =  data_movie_imdb.movie_id ) ";
            $idop .= " and data_movie_budget.`Production_Budget`> 0 ";

        }

    }


    if ($animation == 2) {
        $idop .= " and data_movie_imdb.`genre` NOT LIKE '%Animation%' ";
    }


    $idopcnt = '';
    if ($country_movie_select) {
        foreach ($country_movie_select as $cntry_m) {
            $idopcnt .= " OR data_movie_imdb.`country` LIKE '" . $cntry_m . "%' ";
        }

        $idopcnt = substr($idopcnt, 3);

        $idopcnt = " and (" . $idopcnt . ")";

        $idop .= $idopcnt;
    }


    if ($_POST['oper'] === 'get_movie_cast_data_total') {


        if ($type_post == 'years') {
            $yaer = intval($_POST['id']);
            $idop .= " and data_movie_imdb.`year` = " . $yaer . " ";
        } else if ($type_post == 'country') {

            $post_country = ($_POST['id']);

            $array_compare_country = array('UK' => 'United Kingdom');

            if ($array_compare_country[$post_country]) {

                $data_object->post_country_2 = $array_compare_country[$post_country];
            } else {
                $data_object->post_country_2 = $post_country;
            }

            $idop .= " and (data_movie_imdb.`country` LIKE '" . $post_country . "%')";


        }
    }


    if ($start) {
        $idop_yaer .= " and data_movie_imdb.`year` >= " . $start;
    }

    if ($end) {
        $idop_yaer .= " and data_movie_imdb.`year` <= " . $end;
    }


    if ($movie_type) {
        $idopcnt_type = '';
        foreach ($movie_type as $type) {
            $idopcnt_type .= " OR data_movie_imdb.`type` = '" . $type . "' ";

        }
        $idopcnt_type = substr($idopcnt_type, 3);

        $idop .= " and (" . $idopcnt_type . ")";
    }


    $data_object->result_data = array('start' => $start, 'end' => $end, 'join' => $join_dop, 'dop' => $idop);
    $data_object->inflation = $inflation_array;
    $data_object->idop_yaer = $idop_yaer;
    return $data_object;
}

if ($included) return;

if ($_POST['oper'] == 'get_country_data') {

    $id = ($_POST['id']);
    $data = ($_POST['data']);
    $data_object = json_decode($data);
    $cur_year = $_POST['cur_year'];
    $code2 = $_POST['code2'];


    $start = $data_object->start;
    $end = $data_object->end;

    $name = $id;

    global $pdo;

    if ($code2) {


        $sql = "SELECT *  FROM data_population_country where cca2 = ? limit 1";
        $q = $pdo->prepare($sql);
        $q->execute(array(0 => $code2));
        $q->setFetchMode(PDO::FETCH_ASSOC);


    } else {
        $sql = "SELECT *  FROM data_population_country where country_name = ? limit 1";
        $q = $pdo->prepare($sql);
        $q->execute(array(0 => $id));
        $q->setFetchMode(PDO::FETCH_ASSOC);
    }
    $r = $q->fetch();


    $cca2 = $r['cca2'];
    $jew_data = $r['jew_data'];

    $cca3 = $r['cca3'];

    $population_data = $r['population_data'];
    $population_data_result = json_decode($population_data);

    $populatin_by_year = $r['populatin_by_year'];
    $populatin_by_year_result = json_decode($populatin_by_year);


    $populatin_result = [];

    foreach ($population_data_result as $year => $data) {

        if ($end == date('Y', time()) && $year > $end) {

            $populatin_result[$year] = $data;

        }
    }


    foreach ($populatin_by_year_result as $year => $data) {
        if ($data > 0) {

            if ($year >= $start && ($year <= $end)) {
                $populatin_result[$year] = $data;
            }

        }


    }


    if (!$cur_year) {
        if ($populatin_result[$end]) {
            $cur_year = $end;
        } else {

            $cur_year = end(array_keys($populatin_result));

        }
        /// echo $cur_year;
    }


    echo '<h1 style="margin-top: 20px"><span><img style="width: 50px" src="/analysis/country_data/' . strtolower($cca3) . '.svg"/></span> ' . $r['country_name'] . '</h1>';


    $data_array = $r['ethnic_array_result'];


    echo '<p style="font-size: 15px;">Ethnic: ' . $r['ethnicdata'] . '</p>';
    echo '<a class="source_link" target="_blank" href="https://www.cia.gov/library/publications/the-world-factbook/fields/400.html#' . $cca2 . '">Source: https://www.cia.gov</a><br><br>';

    ////////show ethnic
    $ethnic_array = $r['ethnic_array'];
    $actor_heder = '';
    $actor_result = '';
    $actor_result_year = '';
    $actor_race_type = '';

    if ($ethnic_array) {
        $array_result = json_decode($ethnic_array);
        $content = '';

        $arry_total = [];

        arsort($array_result);

        $next = 0;
        foreach ($array_result as $index => $val) {

            $index = trim($index);
            $index = strtolower($index);
            $index = ucfirst($index);


            if ($array_compare[$index]) {
                $race = $array_compare[$index];
            } else if ($array_compare[$r['country_name']]) {
                $race = $array_compare[$r['country_name']];

            }


            $actor_heder .= '<th>' . $index . '</th>';
            $actor_race_type .= '<td>' . $race . '</td>';
            $actor_result .= '<td>' . $val . '%</td>';
            $actor_result_year .= '<td>' . number_format($val * $populatin_result[$cur_year] / 100) . '</td>';


        }


        $actor_content_race = '<table  class="tablesorter-blackice no_overflow">
<tr><th>Ethnic compare</th>' . $actor_heder . '</tr>
<tr class="actor_data"><td>Result</td>' . $actor_race_type . '</tr>
<tr class="actor_data"><td>Percent</td>' . $actor_result . '</tr>
<tr class="actor_data"><td>Total population by year (' . $cur_year . ')</td>' . $actor_result_year . '</tr>
</table><br>';


        echo $actor_content_race;

    }


    if ($jew_data) {
        $data = json_decode($jew_data);
        $actor_heder = '';
        $actor_result = '';
        $actor_result_year = '';

        foreach ($data as $name => $jew_count) {

            if ($jew_count > 0) {
                if ($populatin_result) {

                    $population = $populatin_result[2018];

                    if ($population && $jew_count) {
                        $jew_percent = ($jew_count / $population) * 100;
                        $jew_percent = round($jew_percent, 4);
                    }

                }


            }


            $actor_heder .= '<th>' . $name . '</th>';
            $actor_result .= '<td>' . $jew_percent . '%</td>';
            $actor_result_year .= '<td>' . number_format($jew_percent * $populatin_result[$cur_year] / 100) . '</td>';
        }


        $actor_content_jew = '<table  class="tablesorter-blackice no_overflow">
<tr><th>Jew population</th>' . $actor_heder . '</tr>
<tr class="actor_data"><td>Percent</td>' . $actor_result . '</tr>
<tr class="actor_data"><td>Total population by year (' . $cur_year . ')</td>' . $actor_result_year . '</tr>
</table><br>';


        echo $actor_content_jew;
        echo '<a class="source_link" target="_blank" href="https://en.wikipedia.org/wiki/Jewish_population_by_country">Source: https://en.wikipedia.org/wiki/Jewish_population_by_country</a><br><br>';

    }


    if ($data_array) {

        $actor_heder = '';
        $actor_result = '';
        $actor_result_year = '';

        $data = json_decode($data_array);

        foreach ($data as $name => $summ) {

            $actor_heder .= '<th>' . $name . '</th>';
            $actor_result .= '<td>' . $summ . '%</td>';
            $actor_result_year .= '<td>' . number_format($summ * $populatin_result[$cur_year] / 100) . '</td>';
        }


        $actor_content = '<table  class="tablesorter-blackice no_overflow">
<tr><th>Result ethnic data</th>' . $actor_heder . '</tr>
<tr class="actor_data"><td>Percent</td>' . $actor_result . '</tr>
<tr class="actor_data"><td>Total population by year (' . $cur_year . ')</td>' . $actor_result_year . '</tr>
</table>';
        echo $actor_content . '<br><br>';


    }


    //  var_dump($populatin_result);

    ksort($populatin_result);


    foreach ($populatin_result as $year => $summ) {

        $summ = round($summ, 0);
        $result_in .= "{ x: " . $year . ", y: " . $summ . " },";

    }

    $result_data .= "{
                  name: '" . $r['country_name'] . " population',
                    type: 'spline',
                   ///color: '" . $array_ethnic_color[$name] . "', 
                    marker: {            enabled: false        },
                  turboThreshold:0,
                  data: [" . $result_in . "]},";

////////graph

    ?>
    <div id="country_div_<?php echo $r['id'] ?>" style="width: 100%; height: 400px"></div>
    <br>
    <?php
    echo '<a class="source_link" target="_blank" href="https://worldpopulationreview.com/">Source: https://worldpopulationreview.com</a><br>
        <a class="source_link" target="_blank" href="https://datatopics.worldbank.org/world-development-indicators/themes/people.html">Source: https://datatopics.worldbank.org</a><br><br>';

    ?>


    <script type="text/javascript">

        Highcharts.chart('country_div_<?php echo $r['id'] ?>', {
            chart: {
                zoomType: 'xy',
            },
            title: {
                text: '<?php echo $r['country_name'] ?> population'
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
                enabled: false
            },

            plotOptions: {
                series: {
                    cursor: 'pointer',
                },

            },


            series: [<?php echo $result_data; ?>]
        });

    </script>

    <?php


}
if ($_POST['oper'] === 'get_movie_cast_data_main') {


    $id = intval($_POST['id']);
    $global_actor_id = '';


    $data_object = get_post_data_request($_POST['data']);

    ///////////////////////////////////////////////////setup data //////////////////////

    // var_dump($data_object);
    $array_data = $data_object->result_data;
    $display_select = $data_object->display_select;
    $ethnycity = $data_object->ethnycity;
    $start = $array_data['start'];
    $end = $array_data['end'];
    $join_dop = $array_data['join'];
    $idop = $array_data['dop'];
    $inflation_array = $data_object->inflation;
    $actor_type = $data_object->actor_type;
    $display_xa_axis = $data_object->display_xa_axis;
    $diversity_select = $data_object->diversity_select;
    $post_country_2 = $data_object->post_country_2;
    $idop_yaer = $data_object->idop_yaer;
    $country = $data_object->country_movie_select;
    ///////////////////////////////////////////////////setup data //////////////////////


    $actor_type_string = implode(', ', $actor_type);
    $actor_content_type = '<h2 style="margin-top: 20px">Cast (' . $actor_type_string . ')</h2>';

    global $debug;

    if ($id) {

        global $pdo;

        $sql = "SELECT * FROM data_movie_imdb  where movie_id=" . $id;
        $q = $pdo->prepare($sql);
        $q->execute();
        $q->setFetchMode(PDO::FETCH_ASSOC);

        $r = $q->fetch();
        $year = $r['year'];
        $movies_country = $r['country'];
        $title = $r['title'];
    }

//    if ($r['actors']) {
//        $actors_array = json_decode($r['actors'], JSON_FORCE_OBJECT);
//    }
    $actors_array=  MOVIE_DATA::get_actors_from_movie($r['id'],'',$actor_type);

    $array_movie_result = get_movie_data_from_db($id, '', '', $actor_type, $actors_array, $diversity_select, $ethnycity, 1);
    $data = $array_movie_result['data'];


    $actor_content = data_etnic_to_table($data, $diversity_select);

    echo $actor_content;


    /// var_dump($data);

} else if ($_POST['oper'] === 'get_actordata') {

    global $debug;
    if ($debug) {
        $array_timer[] = 'before functions  ' .timer_stop_data();
    }
    $id = $_POST['id'];

    if (strstr($id, ',')) {
        $array_id = explode(',', $id);

    } else {

        $array_id[0] = $id;
    }


    $data = ($_POST['data']);

    if (!$data) {

        $data = ('{"movie_type":[],"movie_genre":[],"inflation":null,"actor_type":["star","main"],"diversity_select":"default","display_select":"date_range_international","country_movie_select":[],"ethnycity":{"1":{"crowd":1},"2":{"ethnic":1},"3":{"jew":1},"4":{"face":1},"5":{"face2":1},"6":{"surname":1}}} ');

    }

    $data_object = json_decode($data);
    $ethnycity = $data_object->ethnycity;


    $w = '';
    $cat = array('star', 'main', 'extra');
    $ethnycity_string = urlencode(json_encode($ethnycity));
    foreach ($array_id as $id) {

        $id = intval($id);

        $sql = "SELECT * FROM `data_actors_imdb` where id =" . $id . " ";
        $q = $pdo->prepare($sql);
        $q->execute();
        $r = $q->fetch();
        $name = $r['name'];


/////name

        echo '<div id="' . $id . '" class="actor_main_container">';
        echo '<div class="actor_title_container">' . $name . '</div>';
        /////image

        echo '<div class="actor_image_container"><img class="actor_image" src="https://' . $_SERVER['HTTP_HOST'] . '/analysis/create_image.php?nocache=1&id=' . $id . '&e=' . $ethnycity_string . '" /></div>';


////////get actor data


        $a_sql = "actor_id ='" . $id . "' ";

        ///$array_movie_result = get_movie_data_from_db('', $a_sql, '');
        $array_movie_result  = MOVIE_DATA::get_movie_data_from_db($id, $a_sql,'');


        $face2 = $array_movie_result['all_data']['request']['bettaface'];
        $face = $array_movie_result['all_data']['request']['kairos'];
        $surname = $array_movie_result['all_data']['request']['surname'];
        $etn = $array_movie_result['all_data']['request']['ethnic'];
        $jew = $array_movie_result['all_data']['request']['jew'];
        $crowd = $array_movie_result['all_data']['request']['crowd'];

///var_dump($ethnycity);
///
///
///
        if ($debug) {
            $array_timer[] = 'get_movie_data_from_db  ' .timer_stop_data();
        }
        foreach ($ethnycity as $order => $data) {
            foreach ($data as $type => $enable) {
                if ($type == 'surname') {
                    echo '<p class="in_hdr">Surname Analysis:</p>';
                    if ($surname) {

                        $sql = "SELECT *  FROM data_actors_surname where actor_id =" . $id;

                        $q = $pdo->prepare($sql);
                        $q->execute();
                        $q->setFetchMode(PDO::FETCH_ASSOC);

                        $actor_data = [];

                        $r = $q->fetch();

                        $data = $r['wiki_data'];
                        if ($data) {
                            $data = json_decode($data);

                            $actor_data['EA'] += (float)$data[4] * 100;// "Asian,GreaterEastAsian,EastAsian",
                            $actor_data['EA'] += (float)$data[5] * 100;//   "Asian,GreaterEastAsian,Japanese",
                            $actor_data['I'] = (float)$data[6] * 100;// "Asian,IndianSubContinent",
                            $actor_data['B'] = (float)$data[7] * 100;// "GreaterAfrican,Africans",

                            $actor_data['M'] = (float)$data[8] * 100;// "GreaterAfrican,Muslim",
                            $actor_data['W'] += (float)$data[9] * 100;// "GreaterEuropean,British",
                            $actor_data['W'] += (float)$data[10] * 100;// "GreaterEuropean,EastEuropean",

                            $actor_data['JW'] = (float)$data[11] * 100;// "GreaterEuropean,Jewish",
                            $actor_data['W'] += (float)$data[12] * 100;// "GreaterEuropean,WestEuropean,French",
                            $actor_data['W'] += (float)$data[13] * 100;// "GreaterEuropean,WestEuropean,Germanic",
                            $actor_data['W'] += (float)$data[14] * 100;// "GreaterEuropean,WestEuropean,Hispanic",
                            $actor_data['W'] += (float)$data[15] * 100;// "GreaterEuropean,WestEuropean,Italian",
                            $actor_data['W'] += (float)$data[16] * 100;// "GreaterEuropean,WestEuropean,Nordic"]]


                            arsort($actor_data);
                            $key = array_keys($actor_data);

                            $surname = $array_compare[$key[0]];


                            if ($surname) {
                                $surname = strtoupper($surname);
                            } else {
                                $surname = 'N/A';
                            }
                            $actor_data = normalise_array($actor_data);

                            echo '<div class="small_desc">';
                            foreach ($actor_data as $i => $v) {
                                echo $array_compare[$i] . ': ' . $v . '%<br>';
                            }
                            echo '</div>';

                            $key = array_keys($actor_data);


                            echo '<p class="verdict">Verdict: ' . $array_compare[$key[0]] . '</p>';
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
                        $q = $pdo->prepare($sql);
                        $q->execute();
                        $q->setFetchMode(PDO::FETCH_ASSOC);

                        while ($r = $q->fetch()) {


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

                        $q = $pdo->prepare($sql);
                        $q->execute();
                        $q->setFetchMode(PDO::FETCH_ASSOC);

                        $row = $q->fetch();
                        $imgid = $row['actor_id'];

                        $array_race['EA'] = $row['Asian'];
                        $array_race['B'] = $row['Black'];
                        $array_race['H'] = $row['Hispanic'];
                        $array_race['W'] = $row['White'];


                        $array_race = normalise_array($array_race);
                        arsort($array_race);

                        echo '<div class="small_desc">';
                        foreach ($array_race as $i => $v) {
                            echo $array_compare[$i] . ': ' . $v . '%<br>';
                        }
                        echo '</div>';

                        $key = array_keys($face);
                        echo '<p class="verdict">Verdict: ' . $array_compare[$key[0]] . '</p>';

                        echo '<a class="source_link" target="_blank" href="https://kairos.com/">Source: https://kairos.com/</a>';

                    } else   echo '<p class="verdict">N/A</p>';
                    if ($debug) {
                        $array_timer[] = 'after face  ' .timer_stop_data();
                    }
                }
                if ($type == 'face2') {

                    echo '<p class="in_hdr">Facial Recognition by Betaface:</p>';
                    if ($face2) {
                        $face2 = normalise_array($face2);
                        arsort($face2);

                        $key = array_keys($face2);
                        echo '<p class="verdict">Verdict: ' . $array_compare[$key[0]] . '</p>';


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

                        $q = $pdo->prepare($sql);
                        $q->execute();
                        $q->setFetchMode(PDO::FETCH_ASSOC);

                        while ($r = $q->fetch()) {

                            if ($r['ethnicity']) {

                                echo '<div class="small_desc">';
                                echo $r['ethnicity'] . '<br>';
                                echo '</div>';

                            }
                        }
                        $key = array_keys($etn);
                        $ethnic_result = ucfirst($key[0]);
                        if ($array_compare[$ethnic_result]) {
                            echo '<p class="verdict">Verdict: ' . $array_compare[$ethnic_result] . '</p>';
                        } else {
                            echo '<p class="verdict">Verdict: ' . $ethnic_result . '</p>';
                        }

                        if ($r['Link']) {

                            echo '<a class="source_link"  target="_blank" href="' . $r['Link'] . '">Source: ' . $r['Link'] . '</a>';
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
                                echo $array_compare[$r['verdict']] . '<br>';
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

                    echo   '<div class="actor_crowdsource_container" ><p>Please help improve RWT by correcting & adding data.</p>
                    <a id="op" data-value="'.$id.'" class="actor_crowdsource" href="#">Edit Actor Data</a>
                    <div class="crowd_data"></div></div>';


                    if ($debug) {
                        $array_timer[] = 'after ethnic  ' .timer_stop_data();
                    }
                }
            }
        }


        if ($debug) {
            $array_timer[] = 'end  ' .timer_stop_data();
            /// print_timer($array_timer);
        }

        echo '</div>';
    }


} else if ($_POST['oper'] === 'movie_data') {

    single_movie();

}

if ($_POST['oper'] === 'get_inner' || $_POST['oper'] === 'get_chart_by_country' || $_POST['oper'] === 'get_race_data') {


    $DomesticBox = 1;
    $International = 1;
    $byCountry = 1;
    $cast = 1;


    $data_object = get_post_data_request($_POST['data']);

    ///////////////////////////////////////////////////setup data //////////////////////

    // var_dump($data_object);
    $array_data = $data_object->result_data;
    $display_select = $data_object->display_select;
    $ethnycity = $data_object->ethnycity;
    $start = $array_data['start'];
    $end = $array_data['end'];
    $join_dop = $array_data['join'];
    $idop = $array_data['dop'];
    $inflation_array = $data_object->inflation;
    $actor_type = $data_object->actor_type;
    $display_xa_axis = $data_object->display_xa_axis;
    $diversity_select = $data_object->diversity_select;
    $post_country_2 = $data_object->post_country_2;
    $idop_yaer = $data_object->idop_yaer;
    ///////////////////////////////////////////////////setup data //////////////////////


    if ($_POST['oper'] === 'get_chart_by_country') {
        $post_country = $_POST['id'];
        echo '<h2>' . $post_country . '</h2>';


        $idop .= " and data_movie_imdb.`country`  LIKE '" . $post_country . "%' ";

    } else {
        $date = $_POST['colum_data'];
        $year = intval($date);

    }


//var_dump($data_object);


    if ($_POST['oper'] === 'get_race_data') {

        $year = intval($_POST['id']);
        $race = $_POST['race'];

        $array_country = [];
        $array_total = [];
        $array_country_data = [];
        $ethnic_array_result = [];
        $ethnic_array = [];

        $sql = "SELECT *  FROM `data_population_country`";

///echo $sql;
        $array_total_summ = [];

        $q = $pdo->prepare($sql);
        $q->execute();
        $q->setFetchMode(PDO::FETCH_ASSOC);

        $array = [];
        $total_row = 0;
        while ($r = $q->fetch()) {


            $country = $r['country_name'];

            ///     echo $country.' <br>';

            $population_data = $r['population_data'];
            $population_data_result = json_decode($population_data, JSON_FORCE_OBJECT);

            $populatin_by_year = $r['populatin_by_year'];
            $populatin_by_year_result = json_decode($populatin_by_year, JSON_FORCE_OBJECT);

            $ethnic_array_result = $r['ethnic_array_result'];

            if ($ethnic_array_result) {
                $ethnic_array_result = json_decode($ethnic_array_result, JSON_FORCE_OBJECT);
            }

            $summ = 0;

            $data = $population_data_result[$year];

            if ($year > date('Y', time())) {
                $summ = $data;
            }

            $data = $populatin_by_year_result[$year];

            if ($data > 0) {
                $summ = $data;

            }

            $array_total_summ['world'] += $summ;

            /// echo $country.' '.$summ.' <br>';


            $count = $ethnic_array_result[$race];

            if ($count > 0) {

                $summ_result = $count * $summ / 100;
                $array_total[$country] += $summ;

                foreach ($ethnic_array_result as $i => $v) {

                    $ethnic_array[$i] += $count * $v / 100;

                }

                $array_country_data[$country] = array('ethnic' => $ethnic_array_result);


            }


        }


        arsort($array_total);


        $id = 0;

        arsort($ethnic_array);
        ///  var_dump($ethnic_array);


        foreach ($array_total as $country => $summ) {
            $id++;

            ///echo $country.' '.$summ.'<br>';

            $cnt = '';

            if (is_array($ethnic_array_result)) {

                foreach ($ethnic_array as $e => $enable) {

                    $percent = $array_country_data[$country]['ethnic'][$e];
                    if (!$percent) $percent = 0;

                    if ($e == $race) {
                        $cnt .= '<td>' . $percent . '</td><td>' . number_format(round($percent * $summ) / 100) . '</td>';
                        $array_total_summ[$e] += round($percent * $summ) / 100;
                    }
                }
            }

            ///  print_r($array_country_data[$country]['ethnic']);

            if ($summ) {

                $content .= '<tr id="' . $country . '" class="click_open"><td>' . $id . '</td><td>' . $country . '</td><td>' . number_format(round($summ)) . '</td>' . $cnt . '<td style="position: relative; overflow: hidden"><a id="op" class="open_country_data open_ul" href="#"></a></td></tr>';
            }
        }

        $colspan = 4;

        foreach ($ethnic_array as $e => $enable) {

            if ($e == $race) {
                $hdr .= '<th>' . $e . ' %</th><th>' . $e . ' total</th>';
                $colspan += 2;
            }
        }

        $footer_inner = '';
        foreach ($array_total_summ as $e => $summ) {

            $summ = round($summ);

            $world = $array_total_summ['world'];

            $percent = round(($summ / $world) * 100, 2);

            ////  echo $summ.' '. $world.' '.($summ/$world).'<br>';

            $footer_inner .= '<td>' . $percent . '</td><td>' . number_format($summ) . '</td>';
        }

        echo '<h1>' . $race . ' (year: <span class="cur_year">' . $year . '</span>)</h1>';

        echo '<table  class="tablesorter tablesorter-blackice no_overflow"><thead><tr><th>№</th><th>Country</th><th>Population</th>' . $hdr . '<th style="width: 30px">More</th>';

        $footer = '<tr><td></td>' . $footer_inner . '<td></td></tr>';
    } else {


        if ($year) {
            $idop_yaer = "data_movie_imdb.`year` = " . $year . "   ";
            echo '<h1>' . $year . '</h1>';
        } else if ($idop_yaer) {
            if (strpos('and', $idop_yaer) == 0) {
                $idop_yaer = substr($idop_yaer, 4);
            }
        }


        echo '<div class="table_ethnic_total"></div>';


        if (!$date && $start && $end) {
            $date = $start . ' - ' . $end;
        }

        $sql = "SELECT * FROM data_movie_imdb " . $join_dop . " where " . $idop_yaer . "   " . $idop;

        global $debug;
        if ($debug) echo $sql;


        $q = $pdo->prepare($sql);
        $q->execute();
        $q->setFetchMode(PDO::FETCH_ASSOC);

        $array = [];
        $total_row = 0;
        while ($r = $q->fetch()) {


            if (!$year) {
                $year = $r['year'];


            }


            $totalbox = $r['box_world'];

            if ($totalbox == 0) {
                $totalbox = $r['box_usa'];
            }
            if ($totalbox != 0) {
                $k = $inflation_array[$year];
                if (!$k) $k = 1;

                $totalbox = $totalbox * $k;

                $all_totalbox += $totalbox;

                if ($DomesticBox) {
                    $array[$totalbox][$r['id']]['d'] = $r['box_usa'] * $k;

                    $all_DomesticBox += $r['box_usa'] * $k;
                }
                if ($International) {
                    $box_int = $r['box_world'] - $r['box_usa'];
                    if ($box_int < 0) $box_int = 0;

                    $array[$totalbox][$r['id']]['i'] = $box_int * $k;

                    $all_International += $box_int * $k;
                }


                if ($byCountry) {

                    if ($r['Box Office Country']) {
                        $array_country = json_decode($r['Box Office Country']);
                        ///   var_dump($array_country);
                        foreach ($array_country as $country => $summ) {


                            if ($summ > 0) {
                                $array[$totalbox][$r['id']]['c'][$country] = $summ * $k;
                                $array[$totalbox][$r['id']]['c_all'] += $summ * $k;
                            }


                        }

                    }

                }


                $array[$totalbox][$r['id']]['t'] = $r['title'];
                $array[$totalbox][$r['id']]['im'] = $r['movie_id'];
                $array[$totalbox][$r['id']]['yr'] = $r['year'];

            }

            $total_row++;
        }

        krsort($array);

        if (is_array($array)) {
            $i = 0;
            foreach ($array as $w => $val) {
                if ($w != 0) {
                    $i++;
                    foreach ($val as $id => $data) {
                        $share = ($data['d'] / $w) * 100;
                        $share = round($share, 2);
                        //print_r($data['gender']);
                        $content .= '<tr class="click_open" id="' . $data['im'] . '"><td>' . $i . '</td><td>' . $data['t'] . '<br><span class="gray">' . $data['yr'] . '</span></td><td>$ ' . number_format($w) . '</td>';

                        if ($DomesticBox) {
                            $content .= '<td>$ ' . number_format($data['d']) . '</td>';
                        }
                        if ($International) {
                            $content .= '<td>$ ' . number_format($data['i']) . '</td>';
                        }

                        if ($DomesticBox && $International) {
                            $content .= '<td>' . $share . '</td>';
                        }

                        if ($byCountry) {


                            if (is_array($data['c'])) {
                                /////sort country by summ

                                arsort($data['c']);


                                $ix = 0;
                                $counry_content = '';
                                $country_heder = '<td>Total</td>';
                                $country_result = '<td>' . number_format($data['c_all']) . '</td>';
                                foreach ($data['c'] as $country_name => $summ) {

                                    if ($ix >= 5 && !$post_country) {
                                        break;
                                    }

                                    if (($post_country && $country_name == $post_country_2) || !$post_country) {
                                        $country_heder .= '<td>' . $country_name . '</td>';
                                        $country_result .= '<td>' . number_format($summ) . '</td>';
                                    }

                                    $ix++;
                                }
                                $counry_content = '<table  class="tablesorter-blackice no_overflow">
<tr>' . $country_heder . '<td style="width: 30px">More</td></tr>
<tr class="country_data">' . $country_result . '<td style="position: relative; overflow: hidden" ><a id="op" class="open_country open_ul"  href="#"></a><div style="display: none" class="data">' . json_encode($data['c']) . '</div></td></tr>
</table>';


                                $content .= '<td >' . $counry_content . '</td>';
                            } else {
                                $content .= '<td></td>';
                            }


                        }
                        if ($cast) {
                            if ($data['cast']) {


                                $content .= '<td class="movie_cast_data">' . $data['cast'] . '</td>';

                            } else {

                                $content .= '<td  class="movie_cast_data" ></td>';
                            }

                        }

                        $content .= '<td style="position: relative; width: 30px;"><a id="op" class="open_ethnic open_ul" href="#"></a></td></tr>';
                    }
                }
            }
        }


        ////////////result data


        $all_share = $all_DomesticBox / $all_totalbox * 100;
        $all_share = round($all_share, 2);


        echo '<table class="tablesorter-blackice"><tr><th>Year</th><th>Worldwide Box Office</th><th>Domestic Box Office</th><th>International Box Office</th><th>Domestic Share %</th></tr>
<tr><td>' . $date . '</td><td>$ ' . number_format($all_totalbox) . '</td><td>$ ' . number_format($all_DomesticBox) . '</td><td>$ ' . number_format($all_International) . '</td><td>' . $all_share . '</td></tr></table>';


        echo '<!-- pager -->
    <div class="pager">
        <span class="pagedisplay"></span>
    </div>
    <table id="get_inner_table" cellspacing="0" class="tablesorter">
    <thead><tr><th>#</th><th>Movie</th><th>Worldwide Box Office</th>';
        $colspan = 4;
        if ($DomesticBox) {
            $colspan += 1;
            echo '<th>Domestic Box Office</th>';
        }
        if ($International) {
            $colspan += 1;
            echo '<th>International Box Office</th>';
        }

        if ($DomesticBox && $International) {
            $colspan += 1;
            echo '<th>Domestic Share %</th>';
        }

        if ($byCountry) {
            $colspan += 1;
            echo '<th>Box Office by Country</th>';
        }


        if ($cast) {
            $colspan += 1;
            echo '<th>Cast</th>';
        }
    }

    echo '<th>Open</th></tr></thead>
    <tbody>' . $content . '</tbody>
    <tfoot>' . $footer . '
        <tr>
          <td colspan="' . $colspan . '">
            <div class="pager"> <span class="left">
                # per page:
                <a href="#">10</a> |
                <a href="#" class="current">25</a> |
                <a href="#">50</a> |
                <a href="#">100</a>
            </span>
            <span class="right">
                <span class="prev">
                    <img src="/analysis/tablesorter/addons/pager/icons/prev.png" /> Prev&nbsp;
                </span>
                <span class="pagecount"></span>
                &nbsp;<span class="next">Next
                    <img src="/analysis/tablesorter/addons/pager/icons/next.png" />
                </span>
            </span>
            </div>
          </td>
        </tr>
      </tfoot>
    </table>
    <script id="js">$(function(){
        var $table = $(\'table.tablesorter\'),
            $pager = $(\'.pager\');
    
        $.tablesorter.customPagerControls({
            table          : $table,                   // point at correct table (string or jQuery object)
            pager          : $pager,                   // pager wrapper (string or jQuery object)
            pageSize       : \'.left a\',                // container for page sizes
            currentPage    : \'.right a\',               // container for page selectors
            ends           : 2,                        // number of pages to show of either end
            aroundCurrent  : 1,                        // number of pages surrounding the current page
            link           : \'<a href="#">{page}</a>\', // page element; use {page} to include the page number
            currentClass   : \'current\',                // current page class name
            adjacentSpacer : \'<span> | </span>\',       // spacer for page numbers next to each other
            distanceSpacer : \'<span> &#133; <span>\',   // spacer for page numbers away from each other (ellipsis = &amp;#133;)
            addKeyboard    : true,                     // use left,right,up,down,pageUp,pageDown,home, or end to change current page
            pageKeyStep    : 10                        // page step to use for pageUp and pageDown
        });
    
        // initialize tablesorter & pager
        $table
            .tablesorter({
                theme: \'blackice\',
                widgets: [\'zebra\', \'columns\'/*, \'filter\'*/],
           
            }).bind(\'pagerChange\', function(e, c) {
    ajax_cast_data_main=0;     ';


    if ($_POST['oper'] != 'get_race_data') {

        echo 'get_ajax_cast();';

    }
    echo '    })
    
    .tablesorterPager({
                // target the pager markup - see the HTML block below
                container: $pager,
                size: 25,
                page: 0,
                savePages : false,
                pageReset: 0,
                output: \'showing: {startRow} to {endRow} ({filteredRows})\'
            });
    });
    </script>';
    return;
}
if ($_POST['oper'] === 'actor_info') {
    $actor_id = (int)$_POST['actor_id'];

    $sql = "SELECT *, (SELECT Verdict FROM `data_actors_jew` WHERE `actor_id` = data_actors_race.actor_id LIMIT 1) as verdict FROM data_actors_race where actor_id = '" . $actor_id . "' LIMIT 1";
    $q = $pdo->prepare($sql);
    $q->execute();
    $q->setFetchMode(PDO::FETCH_ASSOC);

    echo '';
    return;
}


if ($_POST['oper'] === 'box' || $_POST['oper'] === 'get_movie_cast_data_total') {

    $data_object = get_post_data_request($_POST['data']);

///////////////////////////////////////////////////setup data //////////////////////

    // var_dump($data_object);
    $array_data = $data_object->result_data;
    $display_select = $data_object->display_select;
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


    if ($display_select == 'Buying_power2') {
        $populatin_result = [];
        $result_in = '';
        $array_country_data = [];
        $array_country = [];
        $data_power = [];
        $per_capita_max = 0;
        $per_capita_min = 10000000000000000000000000;

        $array_code = array('XK' => 'KV');

        $sql = "SELECT *  FROM  data_population_country ";
        ///echo $sql.PHP_EOL;
        $q = $pdo->prepare($sql);
        $q->execute();
        $q->setFetchMode(PDO::FETCH_ASSOC);

        while ($r = $q->fetch()) {


            // $country = $r['country_name'];
            $cca2 = $r['cca2'];


            $sql2 = "SELECT *  FROM `data_buying_power` where cca2='" . $r['cca2'] . "' limit 1";


            $q2 = $pdo->prepare($sql2);
            $q2->execute();
            $q2->setFetchMode(PDO::FETCH_ASSOC);

            $r2 = $q2->fetch();

            //var_dump($r2);

            $country = $r2['name'];


            if ($array_code[$cca2]) {
                $cca2 = $array_code[$cca2];
            }

            $per_capita = 0;
            $total = 0;
            $date = 0;


            $per_capita = $r2['per_capita'];

            if ($per_capita > $per_capita_max) {
                $per_capita_max = $per_capita;
            }

            if ($per_capita < $per_capita_min) {
                $per_capita_min = $per_capita;
            }


            $total = $r2['total'];
            $date = $r2['date'];

            if (!$date) $date = 2010;
            if (!$total) $total = 1000;
            if (!$per_capita) $per_capita = 1000;


            $data_power[$cca2] = array($country, $per_capita, $total, $date);

        }


///var_dump($data_power);

        $result_data = '';
        $result_in = '';

        foreach ($data_power as $cca2 => $val) {

            if ($cca2 && $val[2] && $val[0]) {

                //  $result_in .=
                echo "{ name: '" . $val[0] . "', code2: '" . $cca2 . "', value: '" . $val[1] . "', year:'" . $val[3] . "', total:'" . $val[2] . "'},";
                //  echo '<br>';
            }

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
    else if ($display_select == 'bubble' || $display_select == 'bellcurve' || $display_select == 'plurality_bellcurve' || $display_select == 'scatter'
        || $display_select == 'regression' || $display_select == 'performance_country'
        || $display_select == 'ethnicity' || $_POST['oper'] === 'get_movie_cast_data_total') {

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

        if ($debug) {


            //  echo '<div class="hdata">Total Movies: ' . $all_movies . '</div>';

        }


        ////////add filters


        include ('include/get_data/FilterCrew.php');


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


            // $display_xa_axis == 'Box Office International' || $display_xa_axis == 'Box Office Domestic' || $display_xa_axis == 'DVD Sales Domestic'


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
            } else {
                $country_result = $country_array[0];
            }

            $country = $country_result;
            $array_movie_result['movies_country'] = $country_result;
//            $actors_array = $r['actors'];
//
//
//            /////////////////////////////////////////////////actors/////////////////////////////////////////
//
//
//            //var_dump($actors_array);
//
//
//            $actors_array = json_decode($actors_array, JSON_FORCE_OBJECT);

            $actors_array=  MOVIE_DATA::get_actors_from_movie($r['id'],'',$actor_type);
            $actors_array =MOVIE_DATA::check_actors_to_stars($actors_array,$actor_type);

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


        $array_convert_type = array('ethnic' => 'ethnic', 'jew' => 'jew', 'face' => 'kairos', 'face2' => 'bettaface', 'surname' => 'surname');


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

        /// var_dump($total_data);

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

            /// var_dump($arrayneed_compare);

            //  print_timer($arrayneed_compare);
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
            //   var_dump($array_movie_result);
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
            } else if ($display_select == 'bubble') {


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


            } else if ($display_select == 'ethnicity') {


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


            } else if ($display_select == 'performance_country') {


                $array_compare_country = array('United Kingdom' => 'UK');


                $box_domestic = $r['Box Office Domestic'];
                $box_internal = $r['Box Office International'];
                $box_country = $r['Box Office Country'];

                /*
                                if ($box_country) {

                                    $array_country = json_decode($box_country);

                                    foreach ($array_country as $country => $summc) {

                                        if ($array_compare_country[$country]) {
                                            $country = $array_compare_country[$country];
                                        }


                                        if ($summc > 0) {

                                            $array_result[$country] += $summc * $k;
                                            $array_top_country[$country]['box'] += $summc * $k;

                                            $array_top_country[$country]['count'] += 1;


                                            if (is_array($data)) {

                                                foreach ($data as $name => $summ) {

                                                    $array_top_country[$country]['race'][$name] += $summ;

                                                }
                                            }

                                        }
                                    }
                                }
                */

                //////////domestic
                $dcntr = $r['movies_country'];
                //  echo 'dcntr = '.$dcntr.'<br>';


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
        //  var_dump($array_top_country);

//var_dump($array_movie_bell);


        if ($debug) {
            $array_timer[] = 'foreach  ' .timer_stop_data();
        }


        if ($_POST['oper'] === 'get_movie_cast_data_total') {

            //arsort($array_movie_bell);

            //$array_movie_bell = normalise_array($array_movie_bell);
            //$actor_content = set_table_ethnic($array_movie_bell, $_POST['id']);
            //echo $actor_content . '<br>';

            return;


        } else if ($display_select == 'ethnicity') {


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

        } else if ($display_select == 'performance_country') {
            $result_in = '';

            arsort($array_result);
            // var_dump($array_result);

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
        } else if ($display_select == 'regression') {


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
        } else if ($display_select == 'scatter') {


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
        } else if ($display_select == 'bellcurve' || $display_select == 'plurality_bellcurve') {

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

        } else if ($display_select == 'bubble') {
            ///echo 'total movies: ' . $total_movies . '<br>';

            foreach ($result as $index => $item) {

                $result_data .= "{
                  name: '" . $index . "',
        color: '" . $array_ethnic_color[$index] . "', 
                  turboThreshold:0,
                  data: [" . $item . "]},";

            }
        }

        if ($debug) {
            $array_timer[] = 'before end  ' .timer_stop_data();

            echo '<br>';
            print_timer($array_timer);

        }


        ///  echo $result_data;
    } else if ($display_select == 'date_range_international' || $display_select == 'date_range_country') {

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


    } else if ($display_select == 'Buying_power_by_race') {
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

    <?php } else if ($display_select == 'Buying_power') {
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


    <?php } else if ($display_select == 'world_map') {
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

    <?php } else if ($display_select == 'world_population') {
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


    } else if ($display_select == 'bellcurve' || $display_select == 'plurality_bellcurve') {
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
                                    var labels = ['4σ', '3σ', '2σ', 'σ', 'μ', 'σ', '2σ', '3σ', '4σ'];
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


    } else if ($display_select == 'bubble' || $display_select == 'scatter' || $display_select == 'regression') {
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


    } else if ($DomesticBox || $International || $byCountry || $result) {
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
                            echo " text: 'Simpson’s Diversity Index'";
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
    } else if ($display_select == 'ethnicity') {
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

if (isset($_POST['check_tmdb_id'])) {
    include($_SERVER['DOCUMENT_ROOT'] . '/wp-content/themes/custom_twentysixteen/template/include/custom_curl.php');

    $id = intval($_POST['movie_id']);

    global $pdo;
    $sql = "SELECT * FROM data_movie_imdb  where movie_id=" . $id;

    $q = $pdo->prepare($sql);
    $q->execute();
    $q->setFetchMode(PDO::FETCH_ASSOC);


    $r = $q->fetch();
    $tmdb_id = $r['tmdb_id'];

    if (!$tmdb_id) {
        $final_value = sprintf('%07d', $_POST['movie_id']);
        $url = "https://api.themoviedb.org/3/find/tt" . $final_value . "?api_key=1dd8ba78a36b846c34c76f04480b5ff0&language=en-US&external_source=imdb_id";
        // echo $url.PHP_EOL;
        $result = getCurlCookie($url, 1);
        if ($result) {
            $result = json_decode($result);
            ///var_dump($result);
            $data = $result->movie_results[0];
            if (!$data) $data = $result->tv_results[0];
            if (!$data) $data = $result->tv_episode_results[0];
            if (!$data) $data = $result->tv_season_results[0];
            if ($data) $tmdb_id = $data->id;


            $sql = "UPDATE `data_movie_imdb` SET `tmdb_id` = '" . $tmdb_id . "' WHERE `data_movie_imdb`.`movie_id`=" . $id;
            $q = $pdo->prepare($sql);
            $q->execute();
        }

    }

    if ($tmdb_id) {
        echo $tmdb_id;
    }

}