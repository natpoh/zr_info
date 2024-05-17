<?php

/**
 * The template for displaying critic search
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

if (!function_exists('custom_search_title')) {

    function custom_search_title() {
        global $title, $search_text, $sfilter, $swatchilst;
        $ret = $title;
        if ($search_text) {
            $ret = trim(strip_tags($search_text));
        }
        if ($sfilter) {
            $ret = $sfilter->title;
        } else if ($swatchilst) {
            $ret = $swatchilst->title;
        }
        return $ret;
    }

}

add_filter('pre_get_document_title', function () {
    return custom_search_title();
});
add_filter('wpseo_opengraph_title', function () {
    return custom_search_title();
});
add_filter('fb_og_title', function () {
    return custom_search_title();
});

add_filter('fb_og_desc', function () {
    global $sfilter, $swatchilst;
    $ret = '';
    if ($sfilter) {
        $ret = strip_tags($sfilter->content);
    } else if ($swatchilst) {
        $ret = $swatchilst->content;
    }
    return $ret;
});

if ($sfilter) {
    if ($sfilter->img) {
        add_filter('fb_og_image', function () {
            global $cfront, $sfilter;
            $uf = $cfront->cm->get_uf();
            $img_path = $uf->get_img_path($sfilter->img);
            return $img_path;
        });
    }
}
/*
  add_filter('fb_og_image', function () {
  global $post_an;
  global $cfront;
  $img = $cfront->get_thumb_og_images($post_an->id);
  return trim(strip_tags($img));
  });
 */

get_header();

//css
wp_enqueue_style('movie_single', get_template_directory_uri() . '/css/movie_single.css', array(), LASTVERSION);
wp_enqueue_style('colums_template', get_template_directory_uri() . '/css/colums_template.css', array(), LASTVERSION);
//js
wp_enqueue_script('section_home', get_template_directory_uri() . '/js/section_home.js', array('jquery'), LASTVERSION);
wp_enqueue_script('spoiler.min', get_template_directory_uri() . '/js/spoiler.min.js', array('jquery'));

require 'template/movie_single_template.php';

include (ABSPATH . 'wp-content/themes/custom_twentysixteen/template-parts/critic-search-inner.php');
?>

<?php get_sidebar(); ?>
<?php get_footer(); ?>
<?php

if (isset($_GET['gmi_debug'])) {
    global $gmi;
    p_r($gmi);
}
?>
