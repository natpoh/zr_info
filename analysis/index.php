<?php error_reporting(E_ERROR);
include 'db_config.php';
global $pdo;
pdoconnect_db();


global $WP_include;

if (!$WP_include) {
    include ABSPATH . 'wp-load.php';
}

if (function_exists('current_user_can')) {
    $curent_user = current_user_can("administrator");
}

if (!$curent_user) {
    return;
}


///debug;
$version = time();

$start_time = 1950;
$end_time = date('Y', time());
$curent_start_limit = 1960;
$curent_end_limit = date('Y', time());


$budget_max = 0;
$budget_min = 1000000000000;


/////Production
$sql = "SELECT Production_Budget  FROM `data_movie_budget` ";
$q = $pdo->prepare($sql);
$q->execute();
while ($r = $q->fetch()) {
    if ($r['Production_Budget'] < $budget_min) {
        $budget_min = $r['Production_Budget'];

    }
    if ($r['Production_Budget'] > $budget_max) {
        $budget_max = $r['Production_Budget'];

    }

}


$array_country = [];
$sql = "SELECT `country`, `type`, `genre` FROM `data_movie_imdb` ";
$q = $pdo->prepare($sql);
$q->execute();
while ($r = $q->fetch()) {


    $type_string = $r['type'];

    $array_move_type[$type_string] = 1;


    $genre = $r['genre'];
    if (strstr($genre, ',')) {
        $genre_array = explode(',', $genre);
        foreach ($genre_array as $val) {
            $genre_array_total[$val] = 1;
        }
    } else {
        $genre_array_total[$genre] = 1;
    }


    $acntr_string = $r['country'];


    if (strstr($acntr_string, ',')) {
        $acntr = explode(',', $acntr_string);
        $dcntr = trim($acntr[0]);
    } else {
        $dcntr = trim($acntr_string);
    }


    $array_country[$dcntr] += 1;

}
arsort($array_move_type);
arsort($genre_array_total);
arsort($array_country);
$option_t = '';
foreach ($array_move_type as $type => $enable) {

    $option_t .= '<option value="' . $type . '">' . $type . '</option>';

}
$option_g = '';
foreach ($genre_array_total as $genre => $enable) {

    $option_g .= '<option value="' . $genre . '">' . $genre . '</option>';

}
$option_crew='';

$array_compare_select = array(  'Male' => 'Male', 'Female' => 'Female',  'W' => 'White', 'B' => 'Black', 'EA' => 'Asian', 'H' => 'Latino', 'JW' => 'Jewish', 'I' => 'Indian', 'M' => 'Arab', 'MIX' => 'Mixed / Other', 'IND' => 'Indigenous');
foreach ($array_compare_select as $id => $name) {

    $option_crew .= '<option value="' . $id . '">' . $name . '</option>';

}



$option_c = '';
foreach ($array_country as $country => $enable) {

    $option_c .= '<option value="' . $country . '">' . $country . '</option>';

}


?>
<!DOCTYPE html>
<html lang="en-US" prefix="og: http://ogp.me/ns# fb: http://ogp.me/ns/fb#" class="no-js">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Analysis</title>
    <!--Load the AJAX API-->
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>

    <script src="https://code.highcharts.com/stock/highstock.js"></script>

    <!--<script src="https://code.highcharts.com/highcharts.js"></script>-->

    <script src="https://code.highcharts.com/highcharts-more.js"></script>
    <script src="https://code.highcharts.com/modules/exporting.js"></script>
    <script src="https://code.highcharts.com/modules/export-data.js"></script>
    <script src="https://code.highcharts.com/modules/accessibility.js"></script>
    <script src="https://code.highcharts.com/modules/series-label.js"></script>

    <script src="/analysis/js/bell_curve_src.js"></script>
    <!-- <script src="https://code.highcharts.com/modules/histogram-bellcurve.js"></script>-->

    <script src="https://code.highcharts.com/maps/modules/map.js"></script>
    <script src="https://code.highcharts.com/mapdata/custom/world.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/proj4js/2.3.15/proj4.js"></script>
    <script src="https://code.highcharts.com/mapdata/countries/us/us-all.js"></script>


    <script type="text/javascript" src="https://cdn.jsdelivr.net/jquery/latest/jquery.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>


    <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <!-- Tablesorter: required -->

    <link rel="stylesheet" href="<?php echo WP_SITEURL.'/wp-content/themes/custom_twentysixteen/css/movie_single.css?'.LASTVERSION ?>">
    <link rel="stylesheet" href="<?php echo WP_SITEURL.'/wp-content/themes/custom_twentysixteen/css/colums_template.css?'.LASTVERSION ?>">
    <link rel="stylesheet" href="/analysis/tablesorter/css/theme.blackice.min.css">
    <script src="<?php echo WP_SITEURL; ?>/analysis/tablesorter/js/jquery.tablesorter.js"></script>
    <script src="<?php echo WP_SITEURL; ?>/analysis/tablesorter/js/jquery.tablesorter.widgets.js"></script>

    <!-- Tablesorter: optional -->
    <link rel="stylesheet" href="<?php echo WP_SITEURL; ?>/analysis/tablesorter/addons/pager/jquery.tablesorter.pager.css">
    <style>
        .left {
            float: left;
        }

        .right {
            float: right;
            -webkit-user-select: none;
            -moz-user-select: none;
            -khtml-user-select: none;
            -ms-user-select: none;
        }

        .pager .prev, .pager .next, .pagecount {
            cursor: pointer;
        }

        .pager a {
            text-decoration: none;
            color: black;
        }

        .pager a.current {
            color: #0080ff;
        }
    </style>
    <script src="/analysis/tablesorter/addons/pager/jquery.tablesorter.pager.js"></script>
    <script src="/analysis/tablesorter/beta-testing/pager-custom-controls.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/js/select2.min.js"></script>
    <script src="<?php echo WP_SITEURL.'/wp-content/themes/custom_twentysixteen/js/section_home.js?'.LASTVERSION ?>"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/css/select2.min.css" rel="stylesheet"/>

    <link href="/analysis/graph.css?<?php echo $version ?>" rel="stylesheet"/>
    <!-- <link href="/analysis/dark-unica.css" rel="stylesheet" />-->
</head>

<body>
<?php
error_reporting(E_ERROR);


include('../wp-content/uploads/fastcache/get_header_data.html');

///  echo '<div class="header_menu" style="display: flex;margin: 10px;"><a   style="padding: 10px;" href="data.php">Return to table</a><a  class="selected" style="padding: 10px;" href="graph.php">Graph</a> </div><a style="position: absolute; right: 0px; top: 0px" href="admin.php">Admin</a>';


for ($r = $end_time; $r >= $start_time; $r--) {
    $seleced_0 = '';

    if ($r == $curent_end_limit) {
        $seleced_0 = ' selected ';
    }


    $option .= '<option ' . $seleced_0 . '  value="' . $r . '">' . $r . '</option>';

    $seleced = '';

    if ($r == $curent_start_limit) {
        $seleced = ' selected ';
    }
    $option1 .= '<option ' . $seleced . ' value="' . $r . '">' . $r . '</option>';
}

$array_type = array(
    'date_range_international'=>array('name'=>'Box Office international v.s. domestic','filters'=>'all'),
    'date_range_country'=>array('name'=>'Box Office breakdown by country','filters'=>'all'),
    'ethnicity'=>array('name'=>'Ethnicity Data Set','ethnic_setup'=>1,'filters'=>'all'),
    'world_population'=>array('name'=>'World population','filters'=>'year'),
    'world_map'=>array('name'=>'Ethnic world map'),
    'Buying_power'=>array('name'=>'Buying power'),
    'Buying_power_by_race'=>array('name'=>'Buying power by race')
    );
if (!isset($_GET['type']))
{
    $_GET['type']='date_range_international';
}

foreach ($array_type as $i=>$v)
{
    $selected='';

    if (isset($_GET['type']))
    {

        if ($_GET['type']==$i)
        {
            $selected = ' is_selected ';
        }


    }


    $type_option.='<a  href="?type='.$i.'" id="'.$i.'" class="v_type'.$selected.'" >'.$v['name'].'</a>';




}




?>
<div class="content">

    <div class="control_panel" >
        <div class="get_data_refresh">
            <button class="data_refresh">Refresh data</button>
        </div>

        <div class="control_panel_block">
            <div class="slide_control">
                <span class="hdr_drp_dwn_menu">
                                <div class="bar"></div>
                                <div class="bar"></div>
                                <div class="bar"></div>
                                 <div class="bar"></div>
                                <div class="clear"></div>
                            </span>
            </div>
            <p>Visualization type</p>

            <?php echo $type_option; ?>
        </div>


        <?php

        if ($array_type[$_GET['type']]['ethnic_setup']==1)
        {

        ?>
        <div class="control_panel_block ethnicity_block">

            <h4>Ethnicity</h4>
            <p>Ethnic visualization</p>

            <select autocomplete="off" class="date_range display_select">
                <option value="ethnicity">Default</option>
                <option value="scatter">Scatter Chart</option>
                <option value="bubble">Plurality Scatterplot</option>
                <option value="regression">Regression line</option>
                <option value="bellcurve">Bell curve</option>
                <option value="plurality_bellcurve">Plurality Bell curve</option>
                <option value="performance_country">Average (performance metric) per country</option>

            </select>
            <p>Diversity</p>

            <select autocomplete="off" class="date_range diversity_select">
                <option value="default">Default not present</option>
                <option value="diversity">Simpson's Diversity Index</option>
                <option value="m_f">Male v.s. Female</option>
                <option value="wj_nw">White (+ Jews ) v.s. non-White</option>
                <option value="w_j_nwj">White (- Jews ) v.s. non-White (+ Jews)</option>
                <option value="wmj_nwm">White Male (+ Jews ) v.s. non-White Males (+ Female Whites)</option>
                <option value="wm_j_nwmj">White Male (- Jews ) v.s. non-White Males (+ Jews + Female Whites)</option>
            </select>


            <p>X-Axis</p>

            <select autocomplete="off" class="date_range display_xa_axis">
                <option value="Box Office Worldwide">Box Office revenue worldwide</option>
                <option value="Box Office International">Box Office revenue internationally</option>
                <option value="Box Office Domestic">Box Office revenue domestic</option>
                <option value="Box Office Profit actual">Box Office revenue profit</option>
                <option value="DVD Sales Domestic">DVD Sales Domestic</option>
                <option value="Movie release date">Movie release date</option>
                <option value="Rating">Rating imdb</option>
            </select>
<p>
            <details class="dark actor_details">
                <summary>Setup</summary>
                <div>
                    <?php include 'include/template_control.php'; echo $data_Set;?>
                </div>
 </details></p>


        </div>
<?php



        }


        if ($array_type[$_GET['type']]['filters'])
        {

?>
        <div class="control_panel_block filter_block">
                <h4>Filters</h4>

            <div class="block_year">
            <p>Year</p>
                <div style="display:flex;">
                <select style="width: 135px" autocomplete="off" type="text"
                        class="date_range date_range_start"><?php echo $option1; ?></select>

                <select style="width: 135px" autocomplete="off" type="text"
                        class="date_range date_range_end"><?php echo $option; ?></select>
                </div>
                <p>Year</p>
                <div class="main_slider_range" id="main_slider" style="margin: 20px 40px;">
                    <div id="custom-handle" class="ui-slider-handle"></div>
                    <div id="custom-handle2" class="ui-slider-handle"></div>
                </div>


            </div>


            <?php
            if ($array_type[$_GET['type']]['filters']=='all')
            {
            ?>

            <p>Production budget</p>
            <input type="hidden" value="<?php echo $budget_min; ?>" default-value="<?php echo $budget_min; ?>"
                   id="budget_min" autocomplete="off" class="date_range budget_min"/><input type="hidden" autocomplete="off"
                                                                                            value="<?php echo $budget_max; ?>"
                                                                                            default-value="<?php echo $budget_max; ?>"
                                                                                            id="budget_max"
                                                                                            class="date_range budget_max"/>

            <div class="budget_slider_range" id="budget_slider" style="margin: 20px 40px;">
                <div id="budget_custom-handle" class="ui-slider-handle"></div>
                <div id="budget_custom-handle2" class="ui-slider-handle"></div>
            </div>

                <p>Crew Filter</p>
                <span class="row"><label>Director</label><select multiple="multiple" autocomplete="off" class="date_range director_select"><?php echo  $option_crew; ?></select></span>
                <span class="row"><label>Cast Director</label><select multiple="multiple" autocomplete="off" class="date_range cast_director_select"><?php echo  $option_crew; ?></select></span>
                <span class="row"><label>Writer</label><select multiple="multiple" autocomplete="off" class="date_range writer_select"><?php echo  $option_crew; ?></select></span>
                <span class="row"><label>Lead Actor</label><select multiple="multiple" autocomplete="off" class="date_range leed_actor_select"><?php echo  $option_crew; ?></select></span>

            <p>Production country</p>

            <select autocomplete="off" multiple="multiple" class="date_range country_movie_select">
                <?php echo $option_c; ?>
            </select>

            <p>Cast</p>
            <select autocomplete="off" multiple="multiple" class="date_range  actos_range actos_range_category">
                <option value="star" selected>Star</option>
                <option value="main" selected>Main</option>
                <option value="extra" >Extra</option>
            </select>
            <p>Movie type</p>
            <select autocomplete="off" class="date_range date_range_type" multiple="multiple"><?php echo $option_t; ?></select>
                <p>Movie genre</p>
                <select autocomplete="off" class="date_range date_range_genre" multiple="multiple"><?php echo $option_g; ?></select>


                <p>Inflation</p>
                <select autocomplete="off" class="date_range date_range_inflation">
                    <option selected value="0">No inflation</option>
                    <option value="1">Use inflation</option>
                </select>
                <p>Animation</p>
                <select autocomplete="off" class="date_range date_range_animation">
                    <option value="0">All data</option>
                    <option selected value="2">Exclude Animation</option>
                </select>

                <!--
                        <p>Top movies box office limit</p>
                        <select  autocomplete="off" class="date_range  movies_limit">

                            <option value="3">3</option>
                            <option value="5">5</option>
                            <option selected  value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                            <option value="10000">All</option>
                        </select>



                <div class="g_block" style="margin-top: 100px">
                    <?php
//
//                    $sql = "SELECT * FROM `options` where id =1 limit 1";
//
//                    $q = $pdo->prepare($sql);
//                    $q->execute();
//                    $r = $q->fetch();
//
//                    $val = $r['val'];
//                    $val = stripcslashes($val);
//                    echo $val;
                    ?>

                </div>     -->
            <?php } ?>
        </div>
<?php }

        ?>

    </div>

    <div class="graph">
        <p style="position: relative"><button id="default"  class="change_color button_big">Skin color</button></p>
        <div id="chart_div"></div>
        <div class="chart_script"></div>

        <div class="footer_table_result"></div>
    </div>

</div>
<!-- Own scripts -->
<script src="/analysis/scripts.js?<?php echo $version ?>"></script>


</body>
</html>