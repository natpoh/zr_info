<?php
/**
 * Customs RSS feed with related posts.
 *
 * Place this file in your theme's directory.
 *
 * @package sometheme
 * @subpackage theme
 */


/**
 * Get related posts.
 */
function my_rss_related()
{

    global $post;

    // Setup post data
    $pid = $post->ID;
    $tags = wp_get_post_tags($pid);
    $tag_ids = array();

    // Loop through post tags
    foreach ($tags as $individual_tag) {
        $tag_ids[] = $individual_tag->term_id;
    }

    // Execute WP_Query
    $related_by_tag = new WP_Query(array(
        'tag__in' => $tag_ids,
        'post__not_in' => array($pid),
        'posts_per_page' => 3,
    ));

    // Loop through posts and build HTML
    if ($related_by_tag->have_posts()) :

        echo 'Related:<br />';

        while ($related_by_tag->have_posts()) : $related_by_tag->the_post();
            echo '<a href="' . get_permalink() . '">' . get_the_title() . '</a><br />';
        endwhile;

    else :
        echo '';
    endif;

    wp_reset_postdata();
}


/**
 * Feed defaults.
 */
header('Content-Type: ' . feed_content_type('rss-http') . '; charset=' . get_option('blog_charset'), true);
$frequency = 1;        // Default '1'. The frequency of RSS updates within the update period.
$duration = 'hourly'; // Default 'hourly'. Accepts 'hourly', 'daily', 'weekly', 'monthly', 'yearly'.
$postlink = '<br /><a href="' . get_permalink() . '">See the rest of the story at zeitgeistreviews.com</a><br /><br />';
$postimages = wp_get_attachment_image_src(get_post_thumbnail_id(get_the_ID()), 'large');

// Check for images
if ($postimages) {

    // Get featured image
    $postimage = $postimages[0];

} else {

    // Fallback to a default
    $postimage = get_stylesheet_directory_uri() . '/images/default.jpg';
}


/**
 * Start RSS feed.
 */
echo '<?xml version="1.0" encoding="' . get_option('blog_charset') . '"?' . '>'; ?>

<rss version="2.0"
     xmlns:content="http://purl.org/rss/1.0/modules/content/"
     xmlns:wfw="http://wellformedweb.org/CommentAPI/"
     xmlns:dc="http://purl.org/dc/elements/1.1/"
     xmlns:atom="http://www.w3.org/2005/Atom"
     xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
     xmlns:slash="http://purl.org/rss/1.0/modules/slash/"
    <?php do_action('rss2_ns'); ?>
>

    <!-- RSS feed defaults -->
    <channel>
        <title><?php bloginfo_rss('name');
            wp_title_rss(); ?></title>
        <link><?php bloginfo_rss('url') ?></link>
        <description><?php bloginfo_rss('description') ?></description>
        <lastBuildDate><?php echo mysql2date('D, d M Y H:i:s +0000', get_lastpostmodified('GMT'), false); ?></lastBuildDate>
        <language><?php bloginfo_rss('language'); ?></language>
        <sy:updatePeriod><?php echo apply_filters('rss_update_period', $duration); ?></sy:updatePeriod>
        <sy:updateFrequency><?php echo apply_filters('rss_update_frequency', $frequency); ?></sy:updateFrequency>
        <atom:link href="<?php self_link(); ?>" rel="self" type="application/rss+xml"/>

        <!-- Feed Logo (optional) -->
        <image>
            <url>https://zeitgeistreviews.com/wp-content/uploads/2019/11/head1.jpg</url>
            <title>
                <?php bloginfo_rss('description') ?>
            </title>
            <link><?php bloginfo_rss('url') ?></link>
        </image>

        <?php


        do_action('rss2_head');

        if (isset($_POST['filters'])) {
            $filters = $_POST['filters'];
            $filters = json_decode($filters);
            if ($filters->critics) {

                chdir($_SERVER['DOCUMENT_ROOT']);

                include('wp-content/plugins/cirtics-review/search/ajaxdata.php');

                pdoconnect();
            }
        }


     ////echo $_SERVER['REQUEST_URI'];

        global $wp_query;

        if (strstr($_SERVER['REQUEST_URI'],'category/box-office/feed'))
        {
            global $wp_query;

           $wp_query = new WP_Query(array('cat' => 116067, 'posts_per_page' => 10));


         $office_feed = 1;

        }


        ?>
        <!-- Start loop -->
        <?php



        if (have_posts()) {

        if ($filters->critics) {


        foreach ($wp_query->posts as $r ) {
///var_dump($r);
            $array_result[$r->ID]['post_title'] = $r->post_title;
            $array_result[$r->ID]['post_name'] = $r->post_name;
            $array_result[$r->ID]['pid_data'][$r->post_id]=1;

        }




        foreach ($array_result as $id => $val) {
            ///  echo $id.'<br>';
            /// var_dump($val);
            //  echo '<br>';

            $meta = getpost_meta($id);

            // var_dump($r);

            $title = $val['post_title'];
            $link = $val['post_name'];

            $url = site_url().'/movies/' . $link;

            $tsumbid = $meta['_thumbnail_id'];

            if ($tsumbid) {
                $imgsrc = getpost_tsumb($tsumbid);
            }

            $date = $meta['_wpmoly_movie_release_date'];

            if ($date) {
                $date = strtotime($date);
                $date = date('Y', $date);
                if (strstr($title, $date)) {
                    $date = '';
                } else {
                    $date = ' (' . $date . ')';
                }
            }



                $pid_array = $val['ID']['pid_data'];

                $content = get_template_critics($filters, $title, $imgsrc, $url, $date, $pid_array, 1);
              echo $content;

            }
            }
            /*
            if ($filters->critics) {

                $thumb = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), 'dyad-thumbnails');
                // var_dump($thumb);

                $date = get_post_meta(get_the_ID(), '_wpmoly_movie_release_date');

                if (is_array($date)) {
                    $date = $date[0];
                }

                if ($date) {
                    $date = strtotime($date);
                    $date = date('Y', $date);
                    if (strstr($title, $date)) {
                        $date = '';
                    } else {
                        $date = ' (' . $date . ')';
                    }
                }
                echo get_template_critics($filters, get_the_title(), $thumb[0], esc_url(get_permalink()), $date, '', 1);

            }
            */
            else {


                while ( have_posts() )
                {
                    the_post();

                if ($office_feed == 1) {

                    if (function_exists('get_post_image_office_feed')) {

                       global $post;

                       $post_id = $post->ID;

                        $postimage = get_post_image_office_feed(get_the_title_rss(),$post_id);

                        $content_img = '';

                        if ($postimage) {
                            $content_img = '<img src="' . $postimage . '"/><br>';
                        }


                    }
                }


                ?>

                <item>
                    <title><?php the_title_rss(); ?></title>
                    <link><?php the_permalink_rss(); ?></link>
                    <guid isPermaLink="false"><?php the_guid(); ?></guid>
                    <author><?php the_author(); ?></author>

                    <pubDate><?php echo mysql2date('D, d M Y H:i:s +0000', get_post_time('Y-m-d H:i:s', true), false); ?></pubDate>
                    <content:encoded>
                        <![CDATA[<?php echo $content_img . the_excerpt_rss(); ?>]]>
                    </content:encoded>
                </item>

                <?php

            }
            }


        }

         ?>
    </channel>
</rss>
