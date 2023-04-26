<?php

/**
 * The template for displaying search results pages
 *
 * @package WordPress
 * @subpackage Twenty_Sixteen
 * @since Twenty Sixteen 1.0
 */
global $cfront, $cm_new_api, $review_api;

global $search_text, $video_api;
if (isset($_POST['filters'])) {

    $filters = $_POST['filters'];

    if (isset($filters['poster'])) {
        //Dinamic poster logic
        $cfront->dinamic_poster((int) $filters['poster']['w'], (int) $filters['poster']['h'], (int) $filters['poster']['id']);
        exit;
    }

    // Get the video from an db. Video logic after 25.08.2021. 
    $is_movie = isset($filters['movies']);
    $is_tv = isset($filters['tvseries']);
    $is_game = isset($filters['videogame']);
    $is_podcastseries = isset($filters['podcastseries']);


    $post_type_slug = '';
    if ($is_movie || $is_tv || $is_game || $is_podcastseries) {

        if ($is_movie) {
            $post_name = $filters['movies'];
            $post_type = 'Movie';
            $post_type_slug = 'movies';
        } else if ($is_tv) {
            $post_name = $filters['tvseries'];
            $post_type = 'TVseries';
            $post_type_slug = 'tvseries';
        } else if ($is_game) {
            $post_name = $filters['videogame'];
            $post_type = 'VideoGame';
            $post_type_slug = 'videogame';
        } else if ($is_podcastseries) {
            $post_name = $filters['podcastseries'];
            $post_type = 'PodcastSeries';
            $post_type_slug = 'podcastseries';
        }
        // Get post from an db
        $ma = $cfront->get_ma();
        $post_id = $ma->get_post_id_by_name($post_name, $post_type);

        // Try to redirect from old slug
        if (!$post_id) {
            $new_post_name = $ma->get_post_name_by_old_slug($post_name, $post_type);
            if ($new_post_name) {
                $url = $_SERVER['REQUEST_URI'];
                $redirect_url = str_replace($post_type_slug . '/' . $post_name, $post_type_slug . '/' . $new_post_name, $url);
                wp_redirect($redirect_url, 301);
                exit();
            }
        }

        if (!$post_id) {
            // Not found. Go to 404
            global $wp_query;
            $wp_query->set_404();
            status_header(404);
            get_template_part(404);
            exit();
        }


        if ($review_api == 2) {
            //Load critic audience
            $ca = $cfront->get_ca();
            $ca->add_actions();
        }

        include 'movie_an.php';
        return;
    }



    // Single critic post

    /*
      if (isset($filters['old_reviews'])) {
      // Redirect from old reviews to new
      $critic_id = $cfront->get_critic_url_by_old_slug($filters['old_reviews']);
      if ($critic_id) {
      $filters['critics'][0] = $critic_id;
      }
      }
     */
    if (isset($filters['search'])) {
        // Search page
        if ($cfront->need_redirect()) {
            $cfront->init_search_get_fiters();
            $redirect_url = $cfront->get_current_search_url();
            wp_redirect($redirect_url, 301);
            exit();
        }
        gmi('search pre');
        
        // Include content parts logic for ajax
        $inc = '';
        $show_content = true;
        $show_facets = true;

        // Init filters
        $cfront->init_search_filters();
        gmi('init_search_filters');
        
        // Get current tab
        $curr_tab = $cfront->get_search_tab();
        $results = $cfront->find_results();
        $keywords = $cfront->get_search_keywords();
        $search_tabs = $cfront->search_tabs($results);

        global $total, $search_text;
        $total = $results['count'];
        $search_title = 'Search';
        $blog_title = get_bloginfo('name');
        $search_text = 'Search. ' . $blog_title;
        if ($keywords) {
            $search_title = 'Search results:';
            $search_text = 'Search Results for: ' . $keywords . '. ' . $blog_title;
        }

        $tab_key = $cfront->get_tab_key();

        $fiters = $cfront->search_filters($tab_key);
        $sort = $cfront->search_sort($tab_key);
        $facets = $results[$tab_key]['facets'];

        // Lodad facets css and js
        $tpl = get_template_directory_uri();
        wp_enqueue_script('nouislider', $tpl . '/js/nouislider.js', array(), CRITIC_MATIC_VERSION, true);
        wp_enqueue_style('nouislider', $tpl . '/css/nouislider.css', array(), CRITIC_MATIC_VERSION);

        $search_front = $cfront;
        //Load search results template
        include 'critic-search.php';
        return;
    }

    if (isset($filters['analytics'])) {
        gmi('analytics pre');
        // Analitics page
        if (!class_exists('AnalyticsFront')) {
            require_once( CRITIC_MATIC_PLUGIN_DIR . 'AnalyticsFront.php' );
            require_once( CRITIC_MATIC_PLUGIN_DIR . 'AnalyticsSearch.php' );
        }

        $search_front = new AnalyticsFront($cfront->cm, $cfront->cs);

        // Include content parts logic for ajax
        $inc = '';
        $show_content = true;
        $show_facets = true;

        // Init filters
        $search_front->init_search_filters();

        // Get current tab
        $curr_tab = $search_front->get_search_tab();
        $tab_key = $search_front->get_tab_key();
        $results = $search_front->find_results();
        $keywords = $search_front->get_search_keywords();
        $search_tabs = $search_front->search_tabs($results);
        gmi('search results');

        global $total, $search_text;
        $total = $results['count'];
        $search_title = 'Analytics';
        $blog_title = get_bloginfo('name');
        $search_text = 'Analytics. ' . $blog_title;
        if ($keywords) {
            $search_title = 'Analytics';
            $search_text = 'Analytics for: ' . $keywords . '. ' . $blog_title;
        }

        $tab_key = $search_front->get_tab_key();

        $fiters = $search_front->search_filters($tab_key);
        $sort = $search_front->search_sort($tab_key);
        $facets = $results[$tab_key]['facets'];

        //Lodad facets css and js
        $tpl = get_template_directory_uri();
        wp_enqueue_script('nouislider', $tpl . '/js/nouislider.js', array(), CRITIC_MATIC_VERSION, true);
        wp_enqueue_style('nouislider', $tpl . '/css/nouislider.css', array(), CRITIC_MATIC_VERSION);

        //Load search results template
        include 'analytics.php';
        return;
    }

    if (isset($filters['critics'])) {
        // Single critic post logic
        $search_slug = $filters['critics'];
        $redirect = false;
        $critic_id = 0;
        if (is_numeric($search_slug)) {
            $redirect = true;
            $critic_id = $search_slug;
        } else {
            $url_arr = explode('-', $search_slug);
            if (is_numeric($url_arr[0])) {
                $critic_id = $url_arr[0];
            }
        }


        global $post;
        $post = '';
        if ($critic_id > 0) {
            $post = $cfront->cm->get_post_and_author($critic_id);
            if ($post) {
                $post_slug = $cfront->get_critic_slug($post);

                if ($search_slug != $post_slug) {
                    // Redirect
                    $redirect = true;
                }
                if ($post->status != 1) {
                    $post = '';
                }
            }
        }

        // Post not found. 404
        if ($critic_id > 0 && !$post) {
            global $wp_query;
            $wp_query->set_404();
            status_header(404);
            get_template_part(404);
            exit();
        }

        // Url nod valid. Redirect
        if ($redirect) {
            // Redirect to full url
            $url = $cfront->get_critic_url($post);
            wp_redirect($url, 301);
            exit();
        }

        if ($post) {
            // Single critic page
            include 'critics.php';
            return;
        }

        // Critics list 
        // DEPRECATED Redirect
        // Filter by movie
        $movie_id = 0;

        $movie_type = 'movies';
        if (isset($filters['critics_movies'])) {
            $ma = $cfront->get_ma();
            $slug = $filters['critics_movies'];
            $movie = $ma->get_post_by_slug($slug, 'Movie');
            if ($movie) {
                $movie_id = $movie->id;
            }
        } else if (isset($filters['critics_tvseries'])) {
            $ma = $cfront->get_ma();
            $slug = $filters['critics_tvseries'];
            $movie = $ma->get_post_by_slug($slug, 'TVseries');
            if ($movie) {
                $movie_type = 'tvseries';
                $movie_id = $movie->id;
            }
        }

        /*
          // Limit
          $per_page = 20;

          // Start page
          $start = 0;
          if ($filters['page']) {
          $page = (int) $filters['page'];
          if (!$page) {
          $page = 1;
          }
          $start = $per_page * $page - $per_page;
          }
         */
        // All posts
        /* $type = -1;
          $group_slugs = array('group_staff' => 0, 'group_pro' => 1, 'group_audience' => 2);
          if (isset($group_slugs[$search_slug])) {
          $type = $group_slugs[$search_slug];
          } */

        //Category
        // New api redirect
        $redirect_url = '/search/tab_critics';
        $tag_id = 0;
        if (isset($filters['category'])) {
            $tag = $cfront->cm->get_tag_by_slug($filters['category']);
            if ($tag) {
                $tag_id = $tag->id;
                $redirect_url .= '/tags_' . $filters['category'];
            }
        }



        $type_urls = array('group_staff' => 'author_staff', 'group_pro' => 'author_pro', 'group_audience' => 'author_audience');
        if (isset($type_urls[$search_slug])) {
            $type = $type_urls[$search_slug];
            $redirect_url .= '/' . $type;
        }


        if ($movie_id) {
            $redirect_url .= '/movie_' . $movie_id;
        }


        wp_redirect($redirect_url, 301);

        return;
    }
}

// Redirect to main search page for all other requests
$new_url = $cfront->get_main_tab();
wp_redirect($new_url, 301);
exit();
