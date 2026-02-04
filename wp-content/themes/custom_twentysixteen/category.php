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

			<header class="page-header category_header">
				<?php
				//	the_archive_title( '<h1 class="page-title">', '</h1>' );



                    $title = get_the_archive_title();
                    $title = strtoupper($title);
                    $title =str_replace('CATEGORY: ','',$title);
                    echo '<h1 class="page-title page-title_category">'.$title.'</h1>';


                        echo '<h2 class="landing-subheader">'. category_description().'</h2>';




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
?>



	<?php
	$current_cat = get_query_var('cat');
	global $ancestor;
	$childcats = get_categories('child_of='.$current_cat.'&hide_empty=0&orderby=id');



      /*

            $total_cat=0;

	if (count($childcats)>1)
    {



        foreach ($childcats as $childcat) {
            $cat_id = $childcat->cat_ID;
            $postslist = get_posts('posts_per_page=-1&category=' .$cat_id);
        if (count($postslist))
        {
            $total_cat++;
        }
        }


    }
var_dump($total_cat);
*/
    $total_cat=count($childcats);


	if ( $total_cat>2 )
    {
        $add_class = ' three_columns';
    }



	if ($childcats) {


        echo '<div id="moreCategories"  class="blue-stripe"><h2>'.$title.' Categories</h2></div>
<div class="moreCategories_content'.$add_class.'">';

        foreach ($childcats as $childcat) {
            if (cat_is_ancestor_of($ancestor, $childcat->cat_ID) == false) {

                $mycat = get_the_category();
                $mycat = $mycat[0];

                $cat_id = $childcat->cat_ID;


                $size_data = array( 1280, 720 );
                $meta_key='_thumbnail_id';
                $attach_term_meta_key = 'img_term';
                $image_id = get_term_meta($cat_id,'_thumbnail_id');
                $image_url = wp_get_attachment_image_url($image_id[0], $size_data);


                if (!$image_url)
                {

                    $img='';
                    $postslist = get_posts('posts_per_page=-1&category=' .$cat_id);
                    foreach ($postslist as $post) {


                        if (has_post_thumbnail($post->ID)) {

                            $img = get_the_post_thumbnail($post->ID);

                        }
                        if (!$img) {
                            $content =   $post->post_content;
                            $img = get_image_content($content);

                        }

                        if (!$img && $cat_id!=2) {

                            $img = get_video($content);

                        }

                        if ($img)
                        {
                            $image_url=$img;

                            break;
                        }

                    }

                }

                if ($image_url) {

                    echo '<div class="cell">
<a class="catBox" href="' . get_category_link($cat_id) . '">
<div class="image-block">
<div class="intrinsic-content">
<img src="' . $image_url . '"  > <h3>' . $childcat->cat_name . '</h3>
</div>
</div>
</a>
</div>';
                }

///<div class="desc">' . category_description($cat_id) . '</div>

                $ancestor = $childcat->cat_ID;
            }
        }
echo '</div></div>';


    }
	else
    {




        echo  '<div class="grid_content">';
        contentview();
        echo  '</div>';

        if (function_exists('wp_pagenavi')) {
            wp_pagenavi();
        }


        ajax_load_content();





    }


          //  posts_per_page




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
