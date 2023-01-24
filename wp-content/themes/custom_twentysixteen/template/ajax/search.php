<?php

/*
 * Ajax search for critic matic api
 */

if (!defined('ABSPATH')) {
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');
}

if (!defined('CRITIC_MATIC_PLUGIN_DIR')) {
    define('CRITIC_MATIC_PLUGIN_DIR', ABSPATH . 'wp-content/plugins/critic_matic/');
    require_once( CRITIC_MATIC_PLUGIN_DIR . 'critic_matic_ajax_inc.php' );
}

if (isset($_GET['search_type']) && $_GET['search_type'] == 'ajax') {
    unset($_GET['search_type']);

    $inc = $_GET['inc'];
    unset($_GET['inc']);

    $autocomplite = false;
    $autocomplite_type = '';
    $show_content = true;
    $show_facets = true;

    if ($inc) {
        // Include logic
        if ($inc == 'content') {
            $show_facets = false;
        } else if ($inc == 'facets') {
            $show_content = false;
        } else if ($inc == 'autocomplite') {
            $autocomplite = true;
            $autocomplite_type = $_GET['facet_type'];
        } else if ($inc == 'none') {
            
        } else {
            $inc = '';
        }
    }

    $actors_facets = array('actor', 'actorstar', 'actormain');
    $dirs_facets = array('dirall', 'dir', 'dirwrite', 'dircast', 'dirprod');

    $analytics = ($_GET['analytics'] && $_GET['analytics']) ? true : false;

    if (in_array($autocomplite_type, $actors_facets) || in_array($autocomplite_type, $dirs_facets)) {
        $analytics = false;
    }

    if ($analytics) {
        if (!class_exists('AnalyticsFront')) {
            require_once( CRITIC_MATIC_PLUGIN_DIR . 'AnalyticsFront.php' );
            require_once( CRITIC_MATIC_PLUGIN_DIR . 'AnalyticsSearch.php' );
        }
        $search_front = new AnalyticsFront();
    } else {
        $search_front = new CriticFront();
    }


    // Init filters
    $search_front->init_search_get_fiters();

    // Create url
    $search_url = $search_front->get_current_search_url();

    // Tab
    $curr_tab = $search_front->get_search_tab();
    $tab_key = $search_front->get_tab_key();

    // Filters
    $fiters = $search_front->search_filters($tab_key);

    if ($autocomplite) {
        $keyword = $_GET['facet_keyword'];
        $count = (int) $_GET['facet_count'];
        $facet_ac_type = $_GET['facet_ac_type'];

        if ($facet_ac_type == 'ac') {
            if (in_array($autocomplite_type, $actors_facets)) {
                $search_front->actor_autocomplite($keyword, $count, 'actor');
            } else if (in_array($autocomplite_type, $dirs_facets)) {
                $search_front->actor_autocomplite($keyword, $count, 'dir');
            } else if ($autocomplite_type == 'movie') {
                $search_front->movie_autocomplite($keyword, $count);
            }
        } else if ($facet_ac_type == 'qf') {
            $search_front->movie_quickfilter($keyword, $count, $autocomplite_type);
        }
        exit;
    }

    // Title
    global $search_text;
    $keywords = $search_front->get_search_keywords();
    $search_title = 'Search';
    if ($analytics) {
        $search_title = 'Analytics';
    }
    $blog_title = 'Zeitgeist Reviews';

    $search_text = $search_title . '. ' . $blog_title;

    if ($keywords) {
        if ($analytics) {
            $search_title = 'Analytics';
            $search_text = 'Analytics for: ' . $keywords . '. ' . $blog_title;
        } else {
            $search_title = 'Search results:';
            $search_text = 'Search Results for: ' . $keywords . '. ' . $blog_title;
        }
    }

    // Only new URL
    if ($inc == 'none') {
        print '<div>';
        $search_front->theme_search_url($search_url, $search_text, $inc);
        print '</div>';
        exit;
    }

    //Find results
    $results = $search_front->find_results(array(), $show_facets);


    global $total;
    $total = $results['count'];

    if ($show_content) {
        // Tabs
        $search_tabs = $search_front->search_tabs($results);

        // Sort
        $sort = $search_front->search_sort($tab_key);
    }
    // Facets
    $facets = $results[$tab_key]['facets'];

    if ($analytics) {
        include (ABSPATH . 'wp-content/themes/custom_twentysixteen/template-parts/analytics-inner.php');
    } else {
        require (ABSPATH . 'wp-content/themes/custom_twentysixteen/template/movie_single_template.php');
        include (ABSPATH . 'wp-content/themes/custom_twentysixteen/template-parts/critic-search-inner.php');
    }
} else if (isset($_GET['search_extend']) && $_GET['search_extend'] == 'ajax') {
    // Search extend custom queryes
    if (!class_exists('AnalyticsFront')) {
        require_once( CRITIC_MATIC_PLUGIN_DIR . 'AnalyticsFront.php' );
        require_once( CRITIC_MATIC_PLUGIN_DIR . 'AnalyticsSearch.php' );
    }
    $search_front = new AnalyticsFront();
    $type = $_GET['type'];
    if ($type == 'race') {
        $race = $_GET['race'];
        $year = $_GET['year'];
        $search_front->get_race_data($year, $race);
    } else if ($type == 'country') {
        $country_key = $_GET['code'];
        $cur_year = $_GET['year'] ? $_GET['year'] : 0;
        $from = $_GET['from'] ? $_GET['from'] : 0;
        $to = $_GET['to'] ? $_GET['to'] : 9999;
        $search_front->get_country_data($country_key, $cur_year, $from, $to);
    } else if ($type == 'movie') {
        $movie = $_GET['code'];
        $search_front->get_movie($movie);
    } else if ($type == 'verdict') {
        $rules = $_GET['data'];
        $rules_id = $search_front->get_id_by_rules($rules);
        print $rules_id;
    }
}
exit;
