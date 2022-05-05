<?php
/**
 * The template for displaying archive pages
 *
 * Used to display archive-type pages if nothing more specific matches a query.
 * For example, puts together date-based pages if no date.php file exists.
 *
 * If you'd like to further customize these archive views, you may create a
 * new template file for each one. For example, tag.php (Tag archives),
 * category.php (Category archives), author.php (Author archives), etc.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package WordPress
 * @subpackage Twenty_Sixteen
 * @since Twenty Sixteen 1.0
 */

get_header(); ?>

	<div id="primary" class="content-full">
		<main id="main" class="site-main" role="main">

		<?php
        global $wp_query;

        if ( have_posts() ) { ?>

			<header class="page-header">
				<?php
				//	the_archive_title( '<h1 class="page-title">', '</h1>' );




                    $title = get_the_archive_title();
                    $title = strtoupper($title);
                    $title =str_replace('CATEGORY: ','',$title);
                    echo '<h1 class="page-title">'.$title.'</h1>';







					the_archive_description( '<div class="taxonomy-description">', '</div>' );
				?>
			</header><!-- .page-header -->

			<?php
			// Start the Loop.
			/*
            while ( have_posts() ) :

			the_post();
				get_template_part( 'template-parts/content', get_post_format() );
        				// End the loop.
			endwhile;
			*/

          //  posts_per_page

echo  '<div class="grid_content">';
            contentview();
echo  '</div>';

            if (function_exists('wp_pagenavi')) {
                wp_pagenavi();
            }

            ajax_load_content();



			// Previous/next page navigation.

          /*
            the_posts_pagination(
				array(
					'prev_text'          => __( 'Previous page', 'twentysixteen' ),
					'next_text'          => __( 'Next page', 'twentysixteen' ),
					'before_page_number' => '<span class="meta-nav screen-reader-text">' . __( 'Page', 'twentysixteen' ) . ' </span>',
				)
			);

            */

			// If no content, include the "No posts found" template.
}
 else {
            get_template_part('template-parts/content', 'none');
        }

		?>

		</main><!-- .site-main -->
	</div><!-- .content-area -->

<?php get_footer(); ?>
