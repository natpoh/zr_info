<?php
/**
 * The template part for displaying single posts
 *
 * @package WordPress
 * @subpackage Twenty_Sixteen
 * @since Twenty Sixteen 1.0
 */
wp_enqueue_style('movie_single', get_template_directory_uri() . '/css/movie_single.css', array(), LASTVERSION);
wp_enqueue_style('colums_template', get_template_directory_uri() . '/css/colums_template.css', array(), LASTVERSION);
//   require get_template_directory() . '/template/movie_single_template.php';
wp_enqueue_script('section_home', get_template_directory_uri() . '/js/section_home.js', array('jquery'), LASTVERSION);
?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

<?php
//   template_single_movie(get_the_ID(), get_the_title(),'',1);
?>

    <?php
    global $movie_id;
    if (!$movie_id) {
        $movie_id = get_the_ID();
    }



////get reviews
//require get_template_directory() . '/template/video_colums_template_single.php';
    ?>
    <div class="entry-content" style="padding: 0px">
    <?php
    require get_template_directory() . '/template/review_template.php';
// $result = array('page_url' => $link, 'page_identifier' => $pg_idnt, 'title' => $title, 'content' => $content);



    $content = get_content_review();

    /// $content = json_decode($content);

    echo $content['content'];


    // require get_template_directory() . '/template/include/emotiondata.php';

    global $review_id;
    global $cfront;
    $cfront->ce->get_emotions($review_id, 0, true);

    ///discuss_config(data_object);
    ///
    ///
    //////get comment
    ///  echo '<div style="text-align: center; margin-top: 30px "><h3 class="column_header">Comments:</h3></div>';

    $comments_account = get_option('disqus_forum_url');
    ?>

        <script type="text/javascript">


            var data_object = new Object();

            data_object['page_url'] = '<?php echo $content->page_url; ?>';
            data_object['page_identifier'] = '<?php echo $content->page_identifier; ?>';
            data_object['title'] = '<?php echo $content->title; ?>';
            data_object['data_comments'] = '<?php echo $comments_account; ?>';
///console.log(data_object);

            document.addEventListener("DOMContentLoaded", function (event) {
                discuss_config(data_object);
            });

        </script>

<?php
// require get_template_directory() . '/template/plugins/disquss_template.php';



/*
  $wpcr = new WPCustomerReviews3;
  $wpcr->init();
  $wpcr->include_goatee();
  $reviews_content = $wpcr->show_reviews_form($post_id,1,1);
  echo "<div class='wpcr3_respond_1 ' data-on-postid='".$post_id."' data-postid='".$post_id."' user_id='". get_current_user_id()."'>".
  $reviews_content.'</div>';

  ///the_content();




  echo '<div style="text-align: center"><h3 class="column_header">Share this page:</h3>'. synved_social_wp_the_content('', $post_id).'</div>';

 */

///  the_content();

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
                        __('Edit<span class="screen-reader-text"> "%s"</span>', 'twentysixteen'), get_the_title()
                ), '<span class="edit-link">', '</span>'
        );
        ?>
    </footer><!-- .entry-footer -->
</article><!-- #post-<?php the_ID(); ?> -->
