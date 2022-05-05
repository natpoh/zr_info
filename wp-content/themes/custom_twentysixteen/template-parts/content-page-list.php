<?php
/**
 * Template part for displaying pages.
 *
 * @package Dyad
 */

?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
    <header class="entry-header">
        <?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
    </header><!-- .entry-header -->

    <?php twentysixteen_post_thumbnail(); ?>

    <div class="entry-content">
        <?php
		the_content();

            ////a-z list

            function get_a_z_list()
            {
                set_time_limit(0);

                $azlist = new \A_Z_Listing\Query(array('post_type' => 'movie'), 'posts');

                echo $azlist->the_listing();
            }

            if ( defined( 'LOCAL_CACHE' ) && LOCAL_CACHE == true && $showdate != 'on') {

                echo wp_theme_cache('get_a_z_list');
            }
            else
            {
                echo wp_theme_cache('get_a_z_list');
                ///get_a_z_list();
            }


            wp_enqueue_style( 'a-z-listing' );

        wp_link_pages(
            array(
                'before'      => '<div class="page-links"><span class="page-links-title">' . __( 'Pages:', 'twentysixteen' ) . '</span>',
                'after'       => '</div>',
                'link_before' => '<span>',
                'link_after'  => '</span>',
                'pagelink'    => '<span class="screen-reader-text">' . __( 'Page', 'twentysixteen' ) . ' </span>%',
                'separator'   => '<span class="screen-reader-text">, </span>',
            )
        );
        ?>
    </div><!-- .entry-content -->

    <?php
    edit_post_link(
        sprintf(
        /* translators: %s: Name of current post */
            __( 'Edit<span class="screen-reader-text"> "%s"</span>', 'twentysixteen' ),
            get_the_title()
        ),
        '<footer class="entry-footer"><span class="edit-link">',
        '</span></footer><!-- .entry-footer -->'
    );
    ?>

</article><!-- #post-<?php the_ID(); ?> -->
