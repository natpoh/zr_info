<?php
/**
 * TODO DEPRECATED UNUSED
 * 
 * The template for displaying search results pages
 *
 * @package WordPress
 * @subpackage Twenty_Sixteen
 * @since Twenty Sixteen 1.0
 */
global $cfront, $top_title;

if ($search_slug == 'all') {
    $page_title = 'All reviews';
} else if ($search_slug == 'group_staff') {
    $page_title = 'Staff reviews';
} else if ($search_slug == 'group_pro') {
    $page_title = 'Pro reviews';
} else if ($search_slug == 'group_audience') {
    $page_title = 'Audience reviews';
} else if (strstr($search_slug, 'category_')) {
    $page_title = ucfirst(substr($search_slug, 9));
}

$blog_title = get_bloginfo('name');
$top_title = $page_title . '. ' . $blog_title;

add_filter('pre_get_document_title', function () {
    global $top_title;
    return trim(strip_tags($top_title));
});

add_filter('wpseo_opengraph_title', function () {
    global $top_title;
    return trim(strip_tags($top_title));
});

get_header();
?>

<section id="primary" class="content-full">
    <main id="main" class="site-main" role="main">

        <?php ?>

        <header class="page-header">
            <h1 class="page-title"><?php echo $page_title; ?></h1>

        </header><!-- .page-header -->

        <?php
        // Start the loop.

        require 'template/movie_single_template.php';
        wp_enqueue_script('section_home', get_template_directory_uri() . '/js/section_home.js', array('jquery'), LASTVERSION);
        wp_enqueue_script('spoiler.min', get_template_directory_uri() . '/js/spoiler.min.js', array('jquery'));

        wp_enqueue_style('movie_single', get_template_directory_uri() . '/css/movie_single.css', array(), LASTVERSION);
        wp_enqueue_style('colums_template', get_template_directory_uri() . '/css/colums_template.css', array(), LASTVERSION);

        include ('template/include/template_critics_search.php');
        require('template/include/emotiondata.php');
        global $reactions;
        $reactions = new User_Reactions_Custom();

        wp_enqueue_style('movie_single', get_template_directory_uri() . '/css/movie_single.css', array(), LASTVERSION);
        ///  var_dump($post);
        ?>
        <div class="flex_content_block">
            <?php
            if (sizeof($posts)) {
                foreach ($posts as $post_arr) {

                    //print_r($post_arr);
                    $cast = $post_arr['cast'];
                    if ($cast) {
                        $cast = $cfront->cm->crop_text($cast, 50);
                    }

                    $title = $post_arr['title'];

                    $release = $post_arr['release'];
                    if ($release) {
                        $release = strtotime($release);
                        $release = date('Y', $release);
                        if (strstr($title, $release)) {
                            $release = '';
                        } else {
                            $release = ' (' . $release . ')';
                        }
                    }
                    ?><div class="card search_review">
                        <div class="full_review_movie">
                            <a href="<?php print $post_arr['link'] ?>" class="movie_link" >
                                <img src="<?php print $post_arr['poster_link_90'] ?>">
                                <div class="movie_link_desc">
                                    <span class="itm_hdr"><?php print $title . $release ?></span>
                                    <span><?php print $cast ?></span>
                                </div>
                            </a>
                        </div>
                        <?php print $post_arr['content_pro'] ?>
                    </div><?php
                }
            }
            ?>
        </div>
        <?php

        if (function_exists('custom_wprss_pagination')) {
            echo custom_wprss_pagination($count, $per_page, 4, $page);
        }

        ajax_load_content();
        ?>

    </main><!-- .site-main -->
</section><!-- .content-area -->


<?php get_footer(); ?>
