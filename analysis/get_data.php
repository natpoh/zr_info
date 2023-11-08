<?php
ini_set('memory_limit', '4096M');
set_time_limit(300);
error_reporting(E_ERROR);

if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');
global $selected_color;
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

!class_exists('BOXDATA') ? include ABSPATH . "analysis/include/box_data.php" : '';
!class_exists('TMDB') ? include ABSPATH . "analysis/include/tmdb.php" : '';

    global $pdo;
    pdoconnect_db();
    $pdo->query('use imdbvisualization');

global $selected_color;
global $array_ethnic_color;
$array_ethnic_color = MOVIE_DATA::get_ethnic_color($selected_color);


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

function single_movie($curent_user='')
{
    if (isset($_POST['rwt_id']))
    {

        $mid =intval($_POST['rwt_id']);
    }

    if (isset($_POST['id']))
    {
        !class_exists('TMDB') ? include ABSPATH . "analysis/include/tmdb.php" : '';
        $imdb_id =intval($_POST['id']);
        $mid=TMDB::get_id_from_imdbid($imdb_id);

    }

    $post_id = $mid;
    if (!function_exists('template_single_movie')) {
        require ABSPATH . 'wp-content/themes/custom_twentysixteen/template/movie_single_template.php';
    }
    template_single_movie($mid, '', '', 1);


    echo '<style type="text/css">.nte_show {    display: none;}</style>';



    echo '<div class="movie_load_grid"><a class="button" target="_blank" href="/analysis/include/scrap_imdb.php?get_imdb_movie_id&imdb_id='.$imdb_id.'">Update data</a></div>';



    /////////add rating

    if (isset($_POST['refresh_rating']))
    {

        if ($_POST['refresh_rating']==1)
        {

//PgRatingCalculate
            !class_exists('PgRatingCalculate') ? include ABSPATH . "analysis/include/pg_rating_calculate.php" : '';
            PgRatingCalculate::CalculateRating($imdb_id,$mid,1);///refresh_rating
            return;
        }

    }
    else if (isset($_POST['refresh_rwt_rating']))
    {

        if ($_POST['refresh_rwt_rating']==1)
        {

            !class_exists('PgRatingCalculate') ? include ABSPATH . "analysis/include/pg_rating_calculate.php" : '';
            PgRatingCalculate::add_movie_rating($mid,'',1);
            return;
        }

    }
    else if (isset($_POST['woke']))
    {

        if ($_POST['woke']==1)
        {
            !class_exists('WOKE') ? include ABSPATH . "analysis/include/woke.php" : '';
            $woke = new WOKE;

            $woke->zr_woke_calc($mid,1);


            return;
        }

    }

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
global $array_compare;
//$array_compare = [];
//$sql = "SELECT * FROM `options` where id =3 limit 1";
//
//$q = $pdo->prepare($sql);
//$q->execute();
//$r = $q->fetch();
//$val = $r['val'];
//$val = str_replace('\\', '', $val);
//$array_compare_0 = explode("',", $val);
//foreach ($array_compare_0 as $val) {
//    $val = trim($val);
//    // echo $val.' ';
//    $result = explode('=>', $val);
//    ///var_dump($result);
//    $index = trim(str_replace("'", "", $result[0]));
//    $value = trim(str_replace("'", "", $result[1]));
//
//    $regv = '#([A-Za-z\,\(\)\- ]{1,})#';
//
//    if (preg_match($regv, $index, $mach)) {
//        $index = $mach[1];
//    }
//
//
//    $index = trim($index);
//
//    $array_compare[$index] = $value;
//}

$array_compare = TMDB::get_array_compare();
$actor_type_min = MOVIE_DATA::get_actor_type_min();

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
//////Полные тексты	id 	name 	Population Total 	White 	Non-White 	Arab 	Asian 	Black 	Indian 	Indigenous 	Latino 	Mixed / Other 	Jewish (Core) 	Jewish (Law of Return)

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
    $idop_yaer='';
    global $keys;

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


    if (!$keys[0])
    {
        $ethnycity=  array("1"=>["crowd"=>1],"2"=>["ethnic"=>1],"3"=>["jew"=>1],"4"=>["face"=>1],"5"=>["face2"=>1],"6"=>["surname"=>1]);

        $data_object->ethnycity = $ethnycity;
    }



    $actor_type = $data_object->actor_type;
    $limit = $data_object->movies_limit;
    $diversity_select = $data_object->diversity_select;
    $display_select = $data_object->display_select;
    $ethnic_display_select = $data_object->ethnic_display_select;



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
    if (!$cur_year) {
        $cur_year=2019;
    }

    $start = $data_object->start;
    $end = $data_object->end;

    if (!$end)$end=2019;
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

            $array1 = array_keys($populatin_result);
            $cur_year = end($array1);

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
global $result_data;

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
    $ethnic_display_select = $data_object->ethnic_display_select;
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

}
else if ($_POST['oper'] === 'get_actordata') {

    $array_compare_cache = array('Sadly, not' => 'N/A', '1' => 'N/A', '2' => 'N/A', 'NJW' => 'N/A', 'W' => 'White', 'B' => 'Black', 'EA' => 'Asian', 'H' => 'Latino', 'JW' => 'Jewish', 'I' => 'Indian', 'M' => 'Arab', 'MIX' => 'Mixed / Other', 'IND' => 'Indigenous');




    !class_exists('RWTimages') ? include ABSPATH . "analysis/include/rwt_images.php" : '';
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
        $q = $pdo->prepare($sql);
        $q->execute();
        $r = $q->fetch();
        $name = $r['name'];

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

                        $q = $pdo->prepare($sql);
                        $q->execute();
                        $q->setFetchMode(PDO::FETCH_ASSOC);

                        $actor_data = [];

                        $r = $q->fetch();

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

                            $surname = $array_compare[$r['verdict']];


                            if ($surname) {
                                $surname = ucfirst($surname);
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

                        $q = $pdo->prepare($sql);
                        $q->execute();
                        $q->setFetchMode(PDO::FETCH_ASSOC);

                        while ($r = $q->fetch()) {

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
else if ($_POST['oper'] === 'movie_data') {


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
    $main_display_select= $data_object->display_select;


    if ($main_display_select == 'ethnicity')
    {
    BOXDATA::box_calculate($data_object);
    }
    else
    {
        /////include other graph
        !class_exists('ANPOPDATA') ? include ABSPATH . "analysis/include/anpopdata.php" : '';

        ANPOPDATA::get_main_data($data_object);

    }



}

