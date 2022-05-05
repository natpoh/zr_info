<?php
/**
 * The template for displaying movies and tvseries posts
 *
 * @package WordPress
 * @subpackage Twenty_Sixteen
 * @since Twenty Sixteen 1.0
 */
$post_an = $ma->get_post($post_id);

global $title;
$blog_title = get_bloginfo('name');
$title = $post_an->title . '. ' . $blog_title;

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
    global $post_an;
    return trim(strip_tags($post_an->description));
});
add_filter('fb_og_image', function () {
    global $post_an;
    global $cfront;
    $img = $cfront->get_thumb_og_images($post_an->id);
    return trim(strip_tags($img));
});
get_header();

//ob_start();
//$quicktags_settings = array();
//wp_editor('', 'id_wpcr3_ftext', array('textarea_name' => 'wpcr3_ftext', 'media_buttons' => false, 'tinymce' => true, 'quicktags' => $quicktags_settings));
//$review_field = ob_get_clean();
?>
<div id="primary" class="content-area">
    <main id="main" class="site-main" role="main">
        <?php
        // RWT post
        global $post;
        global $post_an;
        global $ma;
        //$post = get_post($post_an->rwt_id);
        get_template_part('template-parts/content', 'single-movie');
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
