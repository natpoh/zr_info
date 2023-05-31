<?php
/**
 * The template for displaying all single posts and attachments
 *
 * @package WordPress
 * @subpackage Twenty_Sixteen
 * @since Twenty Sixteen 1.0
 */

get_header(); ?>

<div id="primary" class="content-area">
    <main id="main" class="site-main" role="main">
        <?php
        // Start the loop.
        while (have_posts()) :

            the_post();

            // Include the single post content template.

            global $post;

            if ($post->post_type == 'movie' || $post->post_type == 'tvseries' || $post->post_type == 'videogame' || $post->post_type =='podcastseries') {


                if (isset($_GET)) {
                    $key = array_keys( $_GET);
                    if (preg_match('#([pas])([0-9]+)#', $key[0], $mach)) {
                        global $review_type,$review_id;
                        $review_type = $mach[1];
                        $review_id = $mach[2];
                        if ($review_id) {
                            get_template_part('template-parts/content', 'single-movie-review');
                        }
                    }

                }
                if (!$review_id) {
                    get_template_part('template-parts/content', 'single-movie');
                }


            }

            else {

                get_template_part('template-parts/content', 'single');
            }
            // If comments are open or we have at least one comment, load up the comment template.
/*
 if (!$review_id) {
     if (comments_open() || get_comments_number()) {
         comments_template();
     }
 }*/

            /*
            if ( is_singular( 'attachment' ) ) {
                // Parent post navigation.
                the_post_navigation(
                    array(
                        'prev_text' => _x( '<span class="meta-nav">Published in</span><span class="post-title">%title</span>', 'Parent post link', 'twentysixteen' ),
                    )
                );
            } elseif ( is_singular( 'post' ) ) {
                // Previous/next post navigation.
                the_post_navigation(
                    array(
                        'next_text' => '<span class="meta-nav" aria-hidden="true">' . __( 'Next', 'twentysixteen' ) . '</span> ' .
                            '<span class="screen-reader-text">' . __( 'Next post:', 'twentysixteen' ) . '</span> ' .
                            '<span class="post-title">%title</span>',
                        'prev_text' => '<span class="meta-nav" aria-hidden="true">' . __( 'Previous', 'twentysixteen' ) . '</span> ' .
                            '<span class="screen-reader-text">' . __( 'Previous post:', 'twentysixteen' ) . '</span> ' .
                            '<span class="post-title">%title</span>',
                    )
                );
            }
*/

            // End of the loop.

///$term =get_the_category();
///$cat  = $term[0]->term_id;


        endwhile;
        ?>

    </main><!-- .site-main -->

    <?php get_sidebar('content-bottom'); ?>

</div><!-- .content-area -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>
