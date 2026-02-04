<?php
/*
   Template Name: customsearch

 */

get_header(); ?>

<main id="primary" class="content-area" role="main">

    <div id="posts" class="posts">

        <?php if (have_posts()) : ?>

        <header class="page-header">
            <?php
            ///the_archive_title( '<h1 class="page-title">', '</h1>' );
            the_archive_description('<div class="taxonomy-description">', '</div>');

            if (function_exists('criticscustomsearch')) {
                echo '<br>' . criticscustomsearch() . '<br>';
            }
            ?>
        </header><!-- .page-header -->
        <div class="searc_content">

            <?php /* Start the Loop */ ?>
            <?php
            if (isset($_POST['filters'])) {
                $filters = $_POST['filters'];
                $filters = json_decode($filters);


            }


                chdir($_SERVER['DOCUMENT_ROOT']);

                $_POST['type'] = 'grid';

                include('wp-content/plugins/cirtics-review/search/ajaxdata.php');

                pdoconnect();

            global $wp_query;
        ///    var_dump($wp_query->posts);



            if (have_posts()) :


              ///  var_dump($wp_query->posts);

                foreach ($wp_query->posts as $r ) {
///var_dump($r);
                        $array_result[$r->ID]['post_title'] = $r->post_title;
                        $array_result[$r->ID]['post_name'] = $r->post_name;
                        $array_result[$r->ID]['pid_data'][$r->post_id]=1;

                }




  foreach ($array_result as $id => $val)
           {
          ///  echo $id.'<br>';
         /// var_dump($val);
          //  echo '<br>';

            $meta = getpost_meta($id);

            // var_dump($r);

            $title = $val['post_title'];
            $link = $val['post_name'];

            $url = '/movies/' . $link;

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

            if ($filters->critics) {

             $pid_array = $val['ID']['pid_data'];

             $content = get_template_critics($filters, $title, $imgsrc, $url, $date, $pid_array);
                echo $content;

            }
            else
            {
                $tsumb = 'has-post-thumbnail';
                $content = '<article class="movie type-movie status-publish ' . $tsumb . ' hentry">
                                <div class="entry-media" style="background-image: url(' . $imgsrc . ')"></div>
    	                        <div class="entry-inner">
    	                        	<div class="entry-inner-content">
    	                        		<header class="entry-header">
    	                        			<h2 class="entry-title"><a href="' . $url . '" rel="bookmark">' . $title . '</a></h2>
    	                        		</header><!-- .entry-header -->
                            
    	                        		<div class="entry-content">
    	                        			' . $meta['_wpmoly_movie_overview'] . '
    	                        		</div><!-- .entry-content -->
    	                        	</div><!-- .entry-inner-content -->
    	                        </div><!-- .entry-inner -->
                            
                                       <a class="cover-link" href="' . $url . '"></a>
                            
                            </article>';

             echo  $content;
            }



           }



             endif;

         else : ?>

                <?php get_template_part('template-parts/content', 'none'); ?>


            <?php endif;


            ?>




        </div>
    </div><!-- .posts -->
    <?php
    global $s_posts_per_page;
    global $s_page;
    global $total_search_count;
    if (function_exists('custom_wprss_pagination')) {
        echo custom_wprss_pagination($total_search_count, $s_posts_per_page, 4, $s_page);
    }
    //// echo 'totaol count '.$total_search_count.' page '.$s_page.' total '.$s_posts_per_page;

    ?>
</main><!-- #main -->


<?php get_footer(); ?>
