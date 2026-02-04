<?php
/**
 * The template used for displaying page content
 *
 * @package WordPress
 * @subpackage Twenty_Sixteen
 * @since Twenty Sixteen 1.0
 */
?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<header class="entry-header">
		<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
	</header><!-- .entry-header -->

	<?php

    remove_filter( 'the_content', 'synved_social_wp_the_content' );
    twentysixteen_post_thumbnail(); ?>

	<div class="entry-content">
		<?php
		the_content();

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


            global $post;

            $post_name = $post->post_name;
            $post_title = $post->post_title;
            $post_id = $post->ID;

            $link = WP_SITEURL . '/' . $post_name . '/';
            $pg_idnt = $post_id . ' ' . $link;

            $comments_allowed = comments_open($post_id);


            if ($comments_allowed) {

           $comments_account = get_option('disqus_forum_url');
                echo '<div style="text-align: center"><h3 class="column_header">Comments:</h3></div>
            <div class="not_load" id="disquss_container" data_comments="' . $comments_account . '"  data_title="' . $post_title . '" data_link="' . $link . '" data_idn="' . $pg_idnt . '"></div>';
            }
            wp_enqueue_script('section_home', get_template_directory_uri() . '/js/section_home.js', array('jquery'), LASTVERSION);
            wp_enqueue_script('shortcodes', site_url() . '/wp-content/plugins/shortcodes-ultimate/assets/js/other-shortcodes.js', array('jquery'), LASTVERSION);


            if (function_exists('synved_social_wp_the_content'))
            {
                echo synved_social_wp_the_content('', $post_id);
            }



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
