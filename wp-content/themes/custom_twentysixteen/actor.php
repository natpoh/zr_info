<?php
/**
 * The template for displaying movies and tvseries posts
 *
 * @package WordPress
 * @subpackage Twenty_Sixteen
 * @since Twenty Sixteen 1.0
 */

$post_an =[];/// $ma->get_post($post_id);
global $post_name;

include ABSPATH.'analysis/include/actor_data.php';
global $actor_meta;
$actor_meta = Actor_Data::get_actor_meta($post_name);

global $title;
$blog_title = get_bloginfo('name');
$title = $actor_meta['name']. '. ' . $blog_title;

add_filter('pre_get_document_title', function () {
    global $title;
    return trim(strip_tags($title));
});

add_filter('wpseo_opengraph_title', function () {
    global $title;
    return trim(strip_tags($title));
});

add_filter('fb_og_title', function () {
    global $title;
    return trim(strip_tags($title));
});
add_filter('fb_og_desc', function () {

    return '';
});
add_filter('fb_og_image', function () {
    global $actor_meta;
    $img = $actor_meta['image_big'];
    return $img;
});

get_header();

wp_enqueue_style('movie_single', get_template_directory_uri() . '/css/movie_single.css', array(), LASTVERSION);
wp_enqueue_style('colums_template', get_template_directory_uri() . '/css/colums_template.css', array(), LASTVERSION);
//js
wp_enqueue_script('section_home', get_template_directory_uri() . '/js/section_home.js', array('jquery'), LASTVERSION);

?>
<div id="primary" class="content-area">
    <main id="main" class="site-main" role="main">
        <?php

        get_template_part('template-parts/content', 'actor');

        ?>
        <pre>
            <?php
            // DB an post
            //print_r($post_an);
            ?>
        </pre>
    </main><!-- .site-main -->
    <?php get_sidebar('content-bottom'); ?>
</div><!-- .content-area -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>
