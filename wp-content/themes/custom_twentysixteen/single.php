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
            if ($post->post_type == 'movie' || $post->post_type == 'tvseries' ) {


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
//
//            else if ($post->post_type == 'wprss_feed_item') {
//
//
//                global $review_type,$review_id;
//                $review_id =$post->ID;
//                $review_type ='p';
//                $movie_id='';
//                $post_meta = get_post_meta($review_id);
//                $movie_id_main='';
//                $movie_id_main_enable='';
//
//                if (isset($_GET))
//                {
//                    $key = array_keys( $_GET);
//                    if (preg_match('#([0-9]+)#', $key[0], $mach)) {
//                        $movie_title_main= get_the_title($mach[1]);
//                        if ($movie_title_main)
//                        {
//                            $movie_id_main=$mach[1];
//                        }
//                    }
//                }
//
//
//                foreach ($post_meta as $i=>$v)
//                {
//                //    var_dump($v[0]);
//                    if ($i=='wprss_feed_url' && strstr($v[0],'zeitgeistreviews')){
//
//
//                        $review_type='s';
//                    }
//                    if ($i=='wprss_item_permalink' && strstr($v[0],'zeitgeistreviews')){
//
//                        $review_type='s';
//                    }
//                    if ($i=='wprss_feed_type' && $v[0]=='staff'){
//
//                        $review_type='s';
//                    }
//
//
//                    if ($i=='wprss_item_movies'&& strstr($v[0],$movie_title_main)){
//
//                        $movie_id_main_enable=1;
//
//                    }
//                    if ($i=='wprss_item_movie' && $v && !$movie_id){
//                        foreach ($v as $i1=>$v1)
//                        {
//                            $movie_title=$v1;
//                            //echo $movie_title;
//
//                            if ($movie_title_main && $movie_title_main==$movie_title)
//                            {
//                                $movie_id_main_enable=1;
//                            }
//
//
//                            $movie_id = get_page_by_title( $movie_title, OBJECT , array('movie') );
//
//                            if ($movie_id)
//                            {
//                                $movie_id=$movie_id->ID;
//                                break;
//                            }
//                        }
//
//                    }
//
//                }
//
//
//              if ($movie_id_main_enable)
//              {
//                  $movie_id  =$movie_id_main;
//
//              }
//                // get_review_type($review_id);
//
//                get_template_part('template-parts/content', 'single-movie-review');
//            }
//            else if ($post->post_type == 'wpcr3_review') {
//
//
//                global $review_type,$review_id;
//                $review_id =$post->ID;
//                $review_type ='a';
//                $movie_id='';
//                $post_meta_movie = get_post_meta($review_id,'wpcr3_review_post',1);
//                $movie_id_main='';
//                $movie_id_main_enable='';
//
//                if (isset($_GET))
//                {
//                    $key = array_keys( $_GET);
//                    if (preg_match('#([0-9]+)#', $key[0], $mach)) {
//                        $movie_title_main= get_the_title($mach[1]);
//                        if ($movie_title_main)
//                        {
//                            $movie_id_main=$mach[1];
//                        }
//                    }
//                }
//
//
//                if (!$movie_id_main)
//                {
//                    $movie_id =$post_meta_movie;
//
//                }else
//                {
//                    $movie_id  = $movie_id_main;
//
//                }
//                // get_review_type($review_id);
//
//                get_template_part('template-parts/content', 'single-movie-review');
//            }
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
