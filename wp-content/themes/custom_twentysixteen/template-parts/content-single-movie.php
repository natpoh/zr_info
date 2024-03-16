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
global $cfront, $review_api;
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

//$movie_list  = single_movie_list($post_id);
$movie_list = get_cache_single_list($post_id);
if ($movie_list) {
    echo '<!--json_data-->' . PHP_EOL;
    echo $movie_list;
}
?>

<article id="post-<?php echo $post_id; ?>" class="post-<?php echo $post_id; ?> type-<?php echo $post_type; ?> status-publish hentry">

    <?php
    $wl = $cfront->cm->get_wl();
    $user_blocks = array(
        'watchlists' => $wl->get_watch_blocks(array($post_id))
    );

    template_single_movie($post_id, $post_title, '', 1, '', $user_blocks);
    

//////movie rating
///  get_movie_rating(get_the_ID());
/////movie actors

    include get_template_directory() . '/template/actors_template_single.php';

    show_actors_template_single_cache();

///update data for admin
    if (current_user_can('administrator')) {

        $last_imdb_updated_string = date('Y-m-d', $post_an->add_time);
        $update_link = '';

        if ($post_an->add_time < time() - 86400) {
            $update_link = ' <a target="_blanc" class="admin_link" href="' . WP_SITEURL . '/analysis/include/scrap_imdb.php?get_imdb_movie_id=' . $post_id . '&debug=1"  >update data</a> <a  target="_blanc" class="admin_link"  href="https://info.antiwoketomatoes.com/analysis/include/scrap_imdb.php?get_imdb_movie_id=' . $post_id . '&debug=1"  >update server data</a>';
        }
        echo '<p class="last_updated_desc">Last updated: ' . $last_imdb_updated_string . $update_link . '  <a  target="_blanc" class="admin_link"  href="https://info.antiwoketomatoes.com/service/sheme.php?edit_sheme=5&mid=' . $post_id . '"  >View details</a>  <a href="#" id="clear_page_cache" data-value="' . $post_id . '" class="update_data">Clear cache</a></p>';
    }
    ?>

    <?php
////get reviews
    ?>
    <div class="entry-content">
    <?php
    if ($review_api == 2) {

        $ca = $cfront->get_ca();
        $ca->audience_form_code($post_id);
    }
    ///the_content();

    require get_template_directory() . '/template/video_colums_template_single.php';
    // require get_template_directory() . '/template/include/emotiondata.php';
    ?>
        <div class="column_header">
            <h2>Internet Zeitgest:</h2>
        </div>
        <div id="google_search"  data-value="<?php print $post_id ?>" class="page_custom_block not_load"></div>


<?php
$post_type = strtolower($post_type);

if ($post_type == 'movie') {
    $post_type = 'movies';
}

$link = WP_SITEURL . '/' . $post_type . '/' . $post_name . '/';
$pg_idnt = $post_id . ' ' . $link;
$comments_account = get_option('disqus_forum_url');

echo '<div class="column_header" id="movie_commnets" style="text-align: center; margin-top: 35px"><h2>Comments:</h2></div>
        <div class="not_load" id="disquss_container" data_comments="' . $comments_account . '"  data_title="' . $post_title . '" data_link="' . $link . '" data_idn="' . $pg_idnt . '"></div>';
?>
        <div id="disqus_recommendations"></div>


    </div><!-- .entry-content -->

    <footer class="entry-footer">
    </footer><!-- .entry-footer -->
</article>
