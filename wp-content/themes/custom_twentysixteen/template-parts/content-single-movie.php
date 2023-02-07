<?php
/**
 * The template part for displaying single posts
 *
 * @package WordPress
 * @subpackage Twenty_Sixteen
 * @since Twenty Sixteen 1.0
 */
wp_enqueue_style('movie_single', get_template_directory_uri() . '/css/movie_single.css', array(), LASTVERSION);
//wp_enqueue_script( 'highcharts','https://code.highcharts.com/highcharts.js' , array(), '933', true );
//wp_enqueue_script( 'highchartsstock','https://code.highcharts.com/stock/highstock.js' , array(), '933', true );

wp_enqueue_script('ctf', site_url() . '/wp-content/plugins/custom-twitter-feeds-pro/js/ctf-scripts-1-10.min.js', array(), '1');
wp_enqueue_style('ctf_css', site_url() . '/wp-content/plugins/custom-twitter-feeds-pro/css/ctf-styles.min.css', array(), 1);

require get_template_directory() . '/template/movie_single_template.php';
global $post_id;
global $rwt_id;
global $post_an;
global $ma;
$post_id = $post_an->id;
$post_title = $post_an->title;
$post_type = $post_an->type;
$post_name = $post_an->post_name;
$rwt_id = $post_an->rwt_id;


global $site_url;
if (!$site_url)
    $site_url = WP_SITEURL . '/';
?>
<script type="text/javascript">
    var ctfOptions = {"ajax_url": "<?php echo $site_url; ?>wp-admin\/admin-ajax.php", "font_method": "svg", "placeholder": "<?php echo $site_url; ?>wp-content\/plugins\/custom-twitter-feeds-pro\/img\/placeholder.png", "resized_url": "<?php echo $site_url; ?>wp-content\/uploads\/sb-twitter-feed-images\/"};

</script>

<?php
!class_exists('STRUCTURELIST') ? include ABSPATH . "analysis/include/structurelist.php" : '';

//$movie_list  =STRUCTURELIST::single_movie_to_json($post_id);
$movie_list = get_cache_single_list($post_id);
if ($movie_list) {
    echo '<!--json_data-->' . PHP_EOL;
    echo $movie_list;
}
?>

<article id="post-<?php echo $post_id; ?>" class="post-<?php echo $post_id; ?> type-<?php echo $post_type; ?> status-publish hentry">

<?php
template_single_movie($post_id, $post_title, '', 1);

//////movie rating
///  get_movie_rating(get_the_ID());
/////movie actors

include get_template_directory() . '/template/actors_template_single.php';
?>

    <?php
////get reviews
    ?>
    <div class="entry-content">
    <?php
    global $cfront, $review_api;
    if ($review_api == 2) {

        $ca = $cfront->get_ca();
        $ca->audience_form_code($post_id);
    }
    ///the_content();

    require get_template_directory() . '/template/video_colums_template_single.php';
    require get_template_directory() . '/template/include/emotiondata.php';
    

    ?>


        <div  id="twitter_scroll" data-value="<?php echo $post_id ?>" class="not_load"></div>

        <div class="section_content">
            <div class="column_header">
                <h2>Internet Zeitgest:</h2>
                <p class="content_warning"><span class="content_red_warning">CONTENT WARNING:</span> Foul language, offensive images, & possible spoilers.</p>
                <div class="column_header_main">

                    <div class="column_inner_content 4chan_review">
                        <h3 class="column_header">4Chan:</h3>
                        <div class="s_container smoched">
                            <div id="chan_scroll" data-value="<?php echo $post_id ?>" class="not_load"></div>
                            <div class="s_container_smoth">
                                <div style="text-align: center"> </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <div id="ns_related_scroll" data-value="<?php print $post_id ?>" class="not_load"></div>

<?php
$post_type = strtolower($post_type);

if ($post_type == 'movie') {
    $post_type = 'movies';
}

$link = WP_SITEURL . '/' . $post_type . '/' . $post_name . '/';
$pg_idnt = $post_id . ' ' . $link;
$comments_account = get_option('disqus_forum_url');

echo '<div class="column_header" style="text-align: center; margin-top: 35px"><h2>Comments:</h2></div>
        <div class="not_load" id="disquss_container" data_comments="' . $comments_account . '"  data_title="' . $post_title . '" data_link="' . $link . '" data_idn="' . $pg_idnt . '"></div>';
?>
        <div id="disqus_recommendations"></div>


    </div><!-- .entry-content -->
<!--    <section class="inner_content">-->
    <!--        <div  id="similar_movies" data-value="--><?php //echo  $post_id ?><!--" class="not_load"></div>-->
    <!--    </section>-->
    <footer class="entry-footer">
    </footer><!-- .entry-footer -->
</article>
