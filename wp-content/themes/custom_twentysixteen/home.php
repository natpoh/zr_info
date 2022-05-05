<?php
/**
 * The template for displaying pages
 *
 * This is the template that displays all pages by default.
 * Please note that this is the WordPress construct of pages and that
 * other "pages" on your WordPress site will use a different template.
 *
 * @package WordPress
 * @subpackage Twenty_Sixteen
 * @since Twenty Sixteen 1.0
 */
get_header();
?>

<div id="primary" class="content-full">
    <main id="main" class="site-main" role="main">
        <?php
        wp_enqueue_script('spoiler.min', get_template_directory_uri() . '/js/spoiler.min.js', array('jquery'));
        include(get_template_directory() . '/template/video_colums_template.php');
        ?>

        <section class="inner_content no_pad lasted_comments">
            <div class="column_wrapper">
                <div class="content_wrapper wrap no_bottom_pad">
                    <div class="column">
                        <div class="column_header">
                            <h2>Latest Comments:</h2>
                        </div>
                        <div class="not_load" id="disqus_last_comments"></div>
                    </div>
                </div>
            </div>
        </section>
    </main><!-- .site-main -->
</div>


<?php
if (is_active_sidebar('sidebar-4')) {
    echo '<div class="site-main-block">';
    dynamic_sidebar('sidebar-4');
    echo '</div>';
}
?>

</div><!-- .content-area -->


<?php get_footer(); ?>
