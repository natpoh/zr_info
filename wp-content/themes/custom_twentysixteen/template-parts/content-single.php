<?php
/**
 * The template part for displaying single posts
 *
 * @package WordPress
 * @subpackage Twenty_Sixteen
 * @since Twenty Sixteen 1.0
 */

global $post;

?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
    <header class="entry-header">
        <?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
    </header><!-- .entry-header -->

    <?php twentysixteen_post_thumbnail(); ?>

    <div class="entry-content">
        <?php
        the_content();


        global $post;

        $post_name = $post->post_name;
        $post_title = $post->post_title;
        $post_id = $post->ID;




//        $link = 'https://' . $_SERVER['HTTP_HOST'] . '/' . $post_name . '/';
//        $pg_idnt = $post_id . ' ' . $link;
//        $comments_account = get_option('disqus_forum_url');
//
//        echo '<div style="text-align: center"><h3 class="column_header">Comments:</h3></div>
//            <div class="not_load" id="disquss_container" data_comments="' . $comments_account . '"  data_title="' . $post_title . '" data_link="' . $link . '" data_idn="' . $pg_idnt . '"></div>';

        wp_enqueue_script('section_home', get_template_directory_uri() . '/js/section_home.js', array('jquery'), LASTVERSION);
        wp_enqueue_script('shortcodes', site_url() . '/wp-content/plugins/shortcodes-ultimate/assets/js/other-shortcodes.js', array('jquery'), LASTVERSION);


        //    echo '<div style="text-align: center"><h3 class="column_header">Share this page:</h3>'. synved_social_wp_the_content('', get_the_ID()).'</div>';

		/*
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


			if ( '' !== get_the_author_meta( 'description' ) ) {
				get_template_part( 'template-parts/biography' );
			}
*/

			?>
	</div><!-- .entry-content -->

	<footer class="entry-footer">
        <?php
        //twentysixteen_entry_meta();

        ?>
		<?php
			edit_post_link(
				sprintf(
					/* translators: %s: Name of current post */
					__( 'Edit<span class="screen-reader-text"> "%s"</span>', 'twentysixteen' ),
					get_the_title()
				),
				'<span class="edit-link">',
				'</span>'
			);
			?>
	</footer><!-- .entry-footer -->
</article><!-- #post-<?php the_ID(); ?> -->
