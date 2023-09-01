<?php
/**
 * The template for displaying critic posts
 *
 * @package WordPress
 * @subpackage Twenty_Sixteen
 * @since Twenty Sixteen 1.0
 */
global $top_title;
$blog_title = get_bloginfo('name');
$top_title = $post->title . '. ' . $blog_title;

add_filter('pre_get_document_title', function () {
    global $top_title;
    return trim(strip_tags($top_title));
});

add_filter('wpseo_opengraph_title', function () {
    global $top_title;
    return trim(strip_tags($top_title));
});

add_filter('fb_og_title', function () {
    global $top_title;
    return trim(strip_tags($top_title));
});
add_filter('fb_og_desc', function () {
    global $post;
    return trim(substr(strip_tags($post->content), 0, 200));
});
add_filter('fb_og_image', function () {
    global $post_an;
    global $cfront;
    $url = site_url() . $_SERVER['REQUEST_URI'];
    $img = $cfront->screenshot($url);

    return $img;
});

get_header();
?>

<div id="primary" class="content-area">
    <main id="main" class="site-main" role="main">
        <?php
        wp_enqueue_style('movie_single', get_template_directory_uri() . '/css/movie_single.css', array(), LASTVERSION);
        wp_enqueue_style('colums_template', get_template_directory_uri() . '/css/colums_template.css', array(), LASTVERSION);
        wp_enqueue_script('spoiler.min', get_template_directory_uri() . '/js/spoiler.min.js', array('jquery'));
        wp_enqueue_script('section_home', get_template_directory_uri() . '/js/section_home.js', array('jquery'), LASTVERSION);
        ?>
        <article id="post-<?php $post->id; ?>" class="post-<?php $post->id; ?> critics">
            <div class="entry-content" style="padding: 0px">
                <?php
                /*
                 * Get movie
                 */
                $top_movie = $post->top_movie;
                //$top_movie = $cfront->cm->get_top_movie($post->id);
                // Get external movie meta
                $get_meta = isset($_GET['meta']) ? (int) $_GET['meta'] : 0;
                if ($get_meta) {
                    // Validate meta
                    $valid_meta = $cfront->cm->get_movies_data($post->id, $get_meta);
                    if ($valid_meta) {
                        $top_movie = $get_meta;
                    }
                }

                $critic_content = $cfront->cache_single_critic_content($post->id, $top_movie, $post->date_add);
                // Load short coder js and css
                if (strstr($critic_content, 'short_codes_enabled')) {
                    if (function_exists('do_shortcode')) {
                        $short_content = do_shortcode($post->content);
                        $short_content = strip_shortcodes($short_content);
                    }
                }
                ?>
                <div class="full_review">
                    <?php print $critic_content ?>                    
                </div>

                <?php print $cfront->ce->get_emotions($post->id, 0, true); ?>

                <div id="comments">                                    
                    <?php
                    /* $pandoraComments = new PandoraComments();
                      print $pandoraComments->getComments($post->id);
                      $pandoraComments->commentForm(array(), $post->id); */

                    if (function_exists('current_user_can')) {
                        if (current_user_can("administrator")) {
                            print 'Review <a target="_blank" href="https://info.antiwoketomatoes.com/wp-admin/admin.php?page=critic_matic&pid=' . $post->id . '">adimin info</a>.<br />';
                        }
                    }
                    ?>       
                </div>  

                <div id="disqus_thread"></div>

                <script type="text/javascript">
                    var data_object = new Object();

                    //data_object['page_url'] ='<?php //print 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];   ?>//';
                    //data_object['page_identifier'] = '<?php //print $post->id.' https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];   ?>//';

<?php
///try pet pgind from db
$sql = "SELECT `idn` FROM `cache_disqus_treheads` WHERE `type`='critics' and `post_id` ='" . $post->id . "' limit 1";
$r1 = Pdo_an::db_fetch_row($sql);
if ($r1) {
    $pg_idnt = $r1->idn;
}
if (!$pg_idnt) {
    $pg_idnt = $post->id . ' ' . $cfront->get_critic_url($post);
}
?>

                    data_object['page_url'] = '<?php print $cfront->get_critic_url($post); ?>';
                    data_object['page_identifier'] = '<?php print $pg_idnt ?>';
                    data_object['title'] = '<?php print addslashes($post->title) ?>';
                    document.addEventListener("DOMContentLoaded", function (event) {
                        discuss_config(data_object);
                    });

                </script>

                <?php ?>
            </div><!-- .entry-content -->

            <footer class="entry-footer">
                <?php print $cfront->admin_edit_link($post->id); ?>
            </footer><!-- .entry-footer -->
        </article><!-- #critic-post-<?php print $post->id; ?> -->
    </main><!-- .site-main -->
    <?php get_sidebar('content-bottom'); ?>
</div><!-- .content-area -->
<?php
if (isset($_GET['to_image'])) {
    ?>
    <style type="text/css">
        .header_nav{
            display: none;
        }
        .site-content{
            margin: 0;

        }
        .original_link{
            display: none!important;
        }
        .site-main .full_review, html {
            position: fixed;
            z-index: 1000000;
            overflow: hidden;
            width: 100%;
            height: 100%;
            left: 0px;
            top: 0px;
            margin: 0!important;
        }
        .search .full_review_content_block {
            overflow-y: hidden;
            max-height: 350px;
        }
        .full_review_content_block strong:nth-of-type(1) {
            font-size: 18px;
            line-height: 18px;
        }

        .search .full_review_movie img {
            display: inline-block;
        }
        body, .full_review_content_block {

            font-size: 15px;
            line-height: 18px;
        }
        html #wpadminbar {
            display: none;
        }
        body, .full_review_content_block {
            height: auto;
            overflow: hidden;
        }
        .full_review_movie a {
            display: block;
            max-width: 100%;
        }
        .full_review_movie a > div {
            display: inline-block;
            max-width: 520px;
            padding: 5px;
        }
        .amsg_aut {
            display: block!important;
            margin-top: -20px;
        }
        .amsg_aut>div{
            display: block;
            float: left;
        }
    </style>

    <?php
}
?>


<?php get_sidebar(); ?>
<?php get_footer(); ?>
