<?php

/**
 * The template for displaying analytics
 *
 * @package WordPress
 * @subpackage Twenty_Sixteen
 * @since Twenty Sixteen 1.0
 */
add_filter('body_class', function ($classes) {
    global $total;
    if ($total > 0) {
        if (($key = array_search('search-no-results', $classes)) !== false) {
            unset($classes[$key]);
            $classes[] = 'search-results';
        }
    }
    return $classes;
});

add_filter('pre_get_document_title', function () {
    global $search_text;
    return trim(strip_tags($search_text));
});

add_filter('wpseo_opengraph_title', function () {
    global $search_text;
    return trim(strip_tags($search_text));
});

get_header();
?>
<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<!--script src="https://code.highcharts.com/maps/highmaps.js"></script-->
<script src="https://code.highcharts.com/stock/highstock.js"></script>

<!--<script src="https://code.highcharts.com/highcharts.js"></script>-->
<script src="https://code.highcharts.com/highcharts-more.js"></script>
<script src="https://code.highcharts.com/modules/exporting.js"></script>
<script src="https://code.highcharts.com/modules/export-data.js"></script>
<script src="https://code.highcharts.com/modules/accessibility.js"></script>
<script src="https://code.highcharts.com/modules/series-label.js"></script>
<!--<script src="/analysis/js/bell_curve_src.js"></script>-->
 <script src="https://code.highcharts.com/modules/histogram-bellcurve.js"></script>


<script src="https://code.highcharts.com/maps/modules/map.js"></script>
<script src="https://code.highcharts.com/mapdata/custom/world.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/proj4js/2.3.15/proj4.js"></script>
<script src="https://code.highcharts.com/mapdata/countries/us/us-all.js"></script>  
<?php

//js
wp_enqueue_script('section_home', get_template_directory_uri() . '/js/section_home.js', array('jquery'), LASTVERSION);
wp_enqueue_script('search_extend', get_template_directory_uri() . '/js/search_extend.js', array('jquery'), LASTVERSION);
wp_enqueue_script('spoiler.min', get_template_directory_uri() . '/js/spoiler.min.js', array('jquery'));
wp_enqueue_script('sortable.min', get_template_directory_uri() . '/js/sortable.min.js', array('jquery'));

wp_enqueue_style('movie_single', get_template_directory_uri() . '/css/movie_single.css', array(), LASTVERSION);
wp_enqueue_style('colums_template', get_template_directory_uri() . '/css/colums_template.css', array(), LASTVERSION);

wp_enqueue_style('search_extend', get_template_directory_uri() . '/css/search_extend.css', array(), LASTVERSION);

include (ABSPATH . 'wp-content/themes/custom_twentysixteen/template-parts/analytics-inner.php');
?>

<?php get_sidebar(); ?>
<?php get_footer(); ?>