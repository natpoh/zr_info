<?php
/*
 * Admin interface for Critic Matic
 */

class CriticMaticAdmin {

    //Critic matic
    private $cm;
    //Critic search
    private $cs;
    //Critic feeds
    private $cf;
    //Critic parser admin
    private $cp;
    //Movies an
    private $ma;
    private $ca;
    public $cfront;
    private $ts;
    private $access_level = 4;
    public $new_audience_count = 0;
    //Slug    
    private $parrent_slug = 'critic_matic';
    private $admin_page = '/wp-admin/admin.php?page=';
    private $authors_url = '';
    private $audience_url = '';
    private $countries_url = '';
    private $clear_url = '';
    private $feeds_url = '';
    private $genres_url = '';
    private $movies_url = '';
    private $providers_url = '';
    private $parser_url = '';
    private $settings_url = '';
    private $sitemap_url = '';
    private $tags_url = '';
    private $transcriptions_url = '';
    private $settings_tabs = array(
        'search' => 'Search',
        'parser' => 'Parser',
        'audience' => 'Audience',
        'posts' => 'Posts view',
        'score' => 'Score',
        'actors' => 'Actors',
        'analytics' => 'Analytics',
        'cache' => 'Cache',
        'sync' => 'Sync'
    );
    public $bulk_actions = array(
        'publish' => 'Publish',
        'draft' => 'Draft',
        'trash' => 'Trash',
        'findmovies' => 'Find movies',
        'changeauthor' => 'Change the author',
        'rules' => 'Apply feed rules'
    );
    public $bulk_actions_audience = array(
        'publish' => 'Publish',
        'draft' => 'Draft',
        'trash' => 'Trash',
        'wl' => 'IP to White list',
        'gl' => 'IP to Gray list',
        'bl' => 'IP to Black list',
        'nl' => 'Remove IP from list',
        'findmovies' => 'Find movies',
    );
    public $bulk_actions_authors = array(
        'author_publish' => 'Publish',
        'author_draft' => 'Draft',
        'author_trash' => 'Trash',
        'author_find_avatar' => 'Find avatar',
        'author_url_to_avatar' => 'Upload avatars from URLs',
    );
    public $bulk_actions_audience_ip = array(
        'wl' => 'IP to White list',
        'gl' => 'IP to Gray list',
        'bl' => 'IP to Black list',
        'nl' => 'Remove IP from list',
    );
    public $bulk_actions_search = array(
        'add_critics' => 'Add valid critics meta',
        'add_critics_force' => 'Force approved critics meta',
    );
    public $bulk_actions_meta = array(
        'meta_approve' => 'Approve meta',
        'meta_unapprove' => 'Unapprove meta',
        'meta_remove' => 'Remove meta',
    );
    public $bulk_actions_genre = array(
        'genre_remove' => 'Remove genre',
    );
    public $bulk_actions_ml = array(
        'ml_remove_post' => 'Remove post',
    );
    public $bulk_actions_feeds = array(
        'start_feed' => 'Start campaigns',
        'stop_feed' => 'Stop campaigns',
        'trash_feed' => 'Trash campaigns'
    );
    public $bulk_actions_parser = array(
        'start_campaign' => 'Start campaigns',
        'stop_campaign' => 'Stop campaigns',
        'trash_campaign' => 'Trash campaigns',
        'active_arhive' => 'Active arhive',
        'inactive_arhive' => 'Inactive arhive',
        'active_parser' => 'Active parser',
        'inactive_parser' => 'Inactive parser',
            /* 'active_find' => 'Active find urls',
              'inactive_find' => 'Inactive find urls' */
    );
    public $per_pages = array(30, 100, 500, 1000);

    public function __construct($cm = '', $cs = '') {
        $this->cm = $cm ? $cm : new CriticMatic();
        $this->cs = $cs ? $cs : new CriticSearch($this->cm);
        $this->cf = $this->cm->get_cf();
        $this->cp = $this->get_cp_admin();

        $this->authors_url = $this->parrent_slug . '_authors';
        $this->audience_url = $this->parrent_slug . '_audience';
        $this->countries_url = $this->parrent_slug . '_countries';
        $this->clear_url = $this->parrent_slug . '_clear';
        $this->feeds_url = $this->parrent_slug . '_feeds';
        $this->genres_url = $this->parrent_slug . '_genres';
        $this->movies_url = $this->parrent_slug . '_movies';
        $this->parser_url = $this->parrent_slug . '_parser';
        $this->providers_url = $this->parrent_slug . '_providers';
        $this->settings_url = $this->parrent_slug . '_settings';
        $this->sitemap_url = $this->parrent_slug . '_sitemap';
        $this->tags_url = $this->parrent_slug . '_tags';
        $this->transcriptions_url = $this->parrent_slug . '_transcriptions';

        add_action('admin_menu', array($this, 'add_option_page'));
        add_action('admin_print_styles', array($this, 'print_admin_styles'));

        wp_enqueue_script('croppie', CRITIC_MATIC_PLUGIN_URL . 'js/croppie.js', false, CRITIC_MATIC_VERSION);
        wp_enqueue_script('critic_matic_admin', CRITIC_MATIC_PLUGIN_URL . 'js/admin.js', false, CRITIC_MATIC_VERSION);
        wp_enqueue_script('critic_matic_tags', CRITIC_MATIC_PLUGIN_URL . 'js/tags.js', false, CRITIC_MATIC_VERSION);

        add_action("wp_ajax_cm_autocomplite", array($this, "cm_autocomplite"));
        add_action("wp_ajax_cm_author_autocomplite", array($this, "cm_author_autocomplite"));
        add_action("wp_ajax_cm_find_yt_channel", array($this, "cm_find_yt_channel"));
        add_action("wp_ajax_cm_add_tag", array($this, "cm_add_tag"));

        if (function_exists('user_can')) {
            $this->user_can = $this->user_can();
            if ($this->user_can) {
                $q_audience = array(
                    'status' => 0,
                    'author_type' => 2
                );
                $this->new_audience_count = $this->cm->get_post_count($q_audience);
            }
            add_action('admin_bar_menu', array($this, 'admin_bar_render'), 99);
        }
    }

    // User permissions
    public function user_can() {
        global $user_ID;
        if (user_can($user_ID, 'editor') || user_can($user_ID, 'administrator')) {
            return true;
        }
        return false;
    }

    function admin_bar_render($wp_admin_bar) {
        if ($this->new_audience_count > 0 && $this->user_can) {

            $wp_admin_bar->add_menu(array(
                'parent' => '',
                'id' => 'flag-report',
                'title' => '<span style="color: #ff5e28; font-weight: bold;">Reviews: ' . $this->new_audience_count . '</span>',
                'href' => '/wp-admin/admin.php?page=critic_matic_audience&status=0'
            ));
        }
    }

    public function add_option_page() {

        $count_text = '';
        if ($this->cm->new_audience_count > 0) {
            $count_text = ' <span class="awaiting-mod count-' . $this->cm->new_audience_count . '"><span class="pending-count">' . $this->cm->new_audience_count . '</span></span>';
        }

        add_menu_page(__('Critic Matic'), __('Critic Matic') . $count_text, $this->access_level, $this->parrent_slug, array($this, 'overview'));
        add_submenu_page($this->parrent_slug, __('Critic Matic overview'), __('Overview'), $this->access_level, $this->parrent_slug, array($this, 'overview'));
        add_submenu_page($this->parrent_slug, __('Movies'), __('Movies'), $this->access_level, $this->movies_url, array($this, 'movies'));
        add_submenu_page($this->parrent_slug, __('Authors'), __('Authors'), $this->access_level, $this->authors_url, array($this, 'authors'));
        add_submenu_page($this->parrent_slug, __('Tags'), __('Tags'), $this->access_level, $this->tags_url, array($this, 'tags'));
        add_submenu_page($this->parrent_slug, __('Genres'), __('Genres'), $this->access_level, $this->genres_url, array($this, 'genres'));
        add_submenu_page($this->parrent_slug, __('Countries'), __('Countries'), $this->access_level, $this->countries_url, array($this, 'countries'));
        add_submenu_page($this->parrent_slug, __('Providers'), __('Providers'), $this->access_level, $this->providers_url, array($this, 'providers'));
        add_submenu_page($this->parrent_slug, __('Audience'), __('Audience') . $count_text, $this->access_level, $this->audience_url, array($this, 'audience'));
        add_submenu_page($this->parrent_slug, __('Clear comments'), __('Clear comments') . $count_text, $this->access_level, $this->clear_url, array($this, 'clear_comments'));
        add_submenu_page($this->parrent_slug, __('Transcriptions'), __('Transcriptions'), $this->access_level, $this->transcriptions_url, array($this, 'transcriptions'));
    
            add_submenu_page($this->parrent_slug, __('Feeds'), __('Feeds'), $this->access_level, $this->feeds_url, array($this, 'feeds'));
            add_submenu_page($this->parrent_slug, __('Parser'), __('Parser'), $this->access_level, $this->parser_url, array($this, 'parser'));
        
        add_submenu_page($this->parrent_slug, __('Settings'), __('Settings'), $this->access_level, $this->settings_url, array($this, 'settings'));
        add_submenu_page($this->parrent_slug, __('Sitemap'), __('Sitemap'), $this->access_level, $this->sitemap_url, array($this, 'sitemap'));
    }

    public function print_admin_styles() {
        wp_enqueue_style('critic_matic_admin', CRITIC_MATIC_PLUGIN_URL . 'css/style.css', false, CRITIC_MATIC_VERSION);
        wp_enqueue_style('critic_matic_croppie', CRITIC_MATIC_PLUGIN_URL . 'css/croppie.css', false, CRITIC_MATIC_VERSION);
        wp_enqueue_style('critic_matic_tags', CRITIC_MATIC_PLUGIN_URL . 'css/tags.css', false, CRITIC_MATIC_VERSION);
    }

    public function get_ma() {
        // Get criti
        if (!$this->ma) {
            //init cma
            if (!class_exists('MoviesAn')) {
                require_once( CRITIC_MATIC_PLUGIN_DIR . 'MoviesAn.php' );
            }
            $this->ma = new MoviesAn($this->cm);
        }
        return $this->ma;
    }

    public function get_ca() {
        // Get critic audience
        if (!$this->ca) {
            //init cma
            if (!class_exists('CriticAudience')) {
                require_once( CRITIC_MATIC_PLUGIN_DIR . 'CriticAudience.php' );
            }
            $this->ca = new CriticAudience($this->cm);
        }
        return $this->ca;
    }

    public function get_cp_admin() {
        // Get CriticParser Admin
        if (!$this->cp) {
            if (!class_exists('CriticParser')) {
                require_once( CRITIC_MATIC_PLUGIN_DIR . 'CriticParser.php' );
            }
            $this->cp = new CPAdmin($this->cm);
        }
        return $this->cp;
    }

    public function get_cfront() {
        if (!$this->cfront) {
            //init cma
            if (!class_exists('CriticFront')) {
                require_once( CRITIC_MATIC_PLUGIN_DIR . 'CriticFront.php' );
            }
            $this->cfront = new CriticFront($this->cm, $this->cs);
        }
        return $this->cfront;
    }

    public function get_ts() {
        if (!$this->ts) {
            //init 
            if (!class_exists('CriticMaticTrans')) {
                require_once( CRITIC_MATIC_PLUGIN_DIR . 'CriticMaticTrans.php' );
            }
            $this->ts = new CriticMaticTrans($this->cm);
        }
        return $this->ts;
    }

    /*
     * Get sort orderby
     */

    private function get_orderby($allow_order = array()) {
        $orderby = sanitize_text_field(stripslashes($_GET['orderby']));
        if (!in_array($orderby, $allow_order)) {
            $orderby = '';
        }
        return $orderby;
    }

    /*
     * Get sort order
     */

    private function get_order() {
        $order = isset($_GET['order']) && $_GET['order'] == 'desc' ? 'desc' : 'asc';
        return $order;
    }

    /*
     * Get current tab
     */

    private function get_tab($def = '') {
        $tab = !empty($_GET['tab']) ? sanitize_text_field(stripslashes($_GET['tab'])) : $def;
        return $tab;
    }

    /*
     * Get current page
     */

    private function get_page() {
        $page = isset($_GET['p']) ? (int) $_GET['p'] : 1;
        return $page;
    }

    private function get_perpage() {
        $pp = isset($_GET['perpage']) ? (int) $_GET['perpage'] : $this->cm->perpage;
        return $pp;
    }

    public function cm_autocomplite() {
        $keyword = isset($_GET['keyword']) ? strip_tags(stripslashes($_GET['keyword'])) : '';
        $ret = array('type' => 'no', 'data' => array());
        if ($keyword) {
            $limit = 6;
            $results = $this->cs->front_search_any_movies_by_title_an($this->cm->escape($keyword), $limit);

            if (sizeof($results)) {
                $ret['type'] = 'ok';
                foreach ($results as $item) {
                    $title = $item->title . ', ' . $item->year;
                    $ret['data'][] = array('id' => $item->id, 'title' => $title);
                }
            }
        }
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            print json_encode($ret);
        } else {
            header("Location: " . $_SERVER["HTTP_REFERER"]);
        }
        die();
    }

    public function cm_author_autocomplite() {
        $keyword = isset($_GET['keyword']) ? strip_tags(stripslashes($_GET['keyword'])) : '';
        $ret = array('type' => 'no', 'data' => array());
        if ($keyword) {
            $limit = 6;
            $results = $this->cm->find_authors($this->cm->escape($keyword), $limit);

            if (sizeof($results)) {
                $ret['type'] = 'ok';
                foreach ($results as $item) {
                    $type = $this->cm->get_author_type($item->type);
                    $title = $item->name . ' (' . $type . ')';
                    $ret['data'][] = array('id' => $item->id, 'title' => $title);
                }
            }
        }
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            print json_encode($ret);
        } else {
            header("Location: " . $_SERVER["HTTP_REFERER"]);
        }
        die();
    }

    public function cm_find_yt_channel() {
        $keyword = isset($_GET['yt_query']) ? strip_tags(stripslashes($_GET['yt_query'])) : '';

        $channel = '';
        $video_id = '';
        $title = '';
        $total = -1;
        $err = '';
        $valid = 0;

        if (preg_match('/\/channel\/([\w\d_-]+)/', $keyword, $match)) {
            $channel = $match[1];
        }

        if (!$channel) {
            //Get youtube urls
            if ((strstr($keyword, 'youtube') || strstr($keyword, 'youtu.be'))) {
                if (preg_match('#//www\.youtube\.com/embed/([a-zA-Z0-9\-_]+)#', $keyword, $match) ||
                        preg_match('#//(?:www\.|)youtube\.com/(?:v/|watch\?v=|watch\?.*v=|embed/)([a-zA-Z0-9\-_]+)#', $keyword, $match) ||
                        preg_match('#//youtu\.be/([a-zA-Z0-9\-_]+)#', $keyword, $match)) {
                    if (count($match) > 1) {
                        $video_id = $match[1];
                    }
                }
            }
        }

        if (!$channel && $video_id) {
            $cpyoutube = $this->cp->get_cpyoutube();
            $result = $cpyoutube->find_youtube_data_api(array($video_id));
            if (isset($result[$video_id])) {
                $channel = $result[$video_id]->channelId;
                $title = $result[$video_id]->channelTitle;
            }
        }

        if ($channel) {
            try {
                $cpyoutube = $this->cp->get_cpyoutube();
                $responce = $cpyoutube->youtube_get_videos($channel, 5);

                if ($responce) {
                    $total = $responce->pageInfo->totalResults;
                    if ($total) {
                        $title = $responce->items[0]->snippet->channelTitle;
                    }
                }
            } catch (Exception $exc) {

                //$err = $exc->getTraceAsString();
                $err = 'Channel error';
            }
        }

        if ($total > 0) {
            $valid = 1;
        } else {
            if (!$err) {
                $err = 'Channel invalid';
            }
        }

        $ret = array('err' => $err, 'total' => $total, 'channel' => $channel, 'valid' => $valid, 'title' => $title);

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            print json_encode($ret);
        } else {
            header("Location: " . $_SERVER["HTTP_REFERER"]);
        }
        die();
    }

    public function cm_add_tag() {
        $tags = isset($_GET['tags']) ? $_GET['tags'] : [];
        $camp_id = isset($_GET['camp_id']) ?(int) $_GET['camp_id'] : 0;
        $post_type = isset($_GET['post_type']) ?(int) $_GET['post_type'] : 0;
        
        // 1. Get or create tags 
        $tag_ids = array();
        if ($tags){
            foreach ($tags as $name) {
                $slug = $this->cm->create_slug($name,'');
                $tag_id = $this->cm->get_or_create_camp_tag_id($name, $slug);
                $tag_ids[]=$tag_id;
            }
        }
        // 2. Get old meta
        $old_meta_ids = $this->cm->get_camp_tag_meta($camp_id, $post_type);
        
        // 3. Add new meta        
        if ($tag_ids) {
            foreach ($tag_ids as $tag_id) {
                if (!in_array($tag_id, $old_meta_ids)){
                    // Add meta
                    $this->cm->add_camp_tag_meta($camp_id, $post_type, $tag_id);
                }
            }
        }
        
        // 4. Remove old meta
        if ($old_meta_ids){
            foreach ($old_meta_ids as $tag_id) {
                if (!in_array($tag_id, $tag_ids)){
                    // Remove unused meta
                    $this->cm->remove_camp_tag_meta($camp_id, $post_type, $tag_id);
                }
            }
        }
        
        $err='';
        
        $ret = array('err' => $err);
        
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            print json_encode($ret);
        } else {
            header("Location: " . $_SERVER["HTTP_REFERER"]);
        }
        die();
    }

    /*
     * Last critic posts
     */

    public function overview() {
        $curr_tab = $this->get_tab();
        $page = $this->get_page();
        $per_page = $this->get_perpage();
        $pid = isset($_GET['pid']) ? (int) $_GET['pid'] : '';

        //Bulk actions
        $this->bulk_submit();

        //Search
        $s = isset($_GET['s']) ? strip_tags(stripslashes($_GET['s'])) : '';

        //Sort
        $sort_pages = $this->cm->sort_pages;
        $orderby = $this->get_orderby($sort_pages);
        $order = $this->get_order();

        $url = $this->admin_page . $this->parrent_slug;
        $page_url = $url;

        if ($s) {
            // Search logic

            $start = ($page - 1) * $per_page;

            // Sort
            $sort = array();
            if ($orderby) {
                $sort = array('sort' => $orderby, 'type' => $order);
            }

            // Filter by author type
            $home_author_type = '';
            $filters = array();
            $facets = array();
            /*
              $author_type = isset($_GET['author_type']) ? (int) $_GET['author_type'] : $home_author_type;

              if ($author_type != $home_author_type) {
              $page_url = $page_url . '&author_type=' . $author_type;
              $filters['author_type'] = $author_type;
              }

              //$facets = array('author_type');
             */
            $results = $this->cs->front_search_critics($s, $per_page, $start, $sort, $filters, $facets);

            /*
             * [author_type] => Array
              (
              [0] => stdClass Object
              (
              [author_type] => 1
              [count(*)] => 291
              )
              )
             */

            $posts = $results['result'];
            $total = $results['total'];
            /*
              $facets_result = $results['facets'];
              $states = array();
              if (isset($facets_result['author_type'])) {
              $states = array(
              '-1' => array(
              'title' => 'All',
              'count' => $total
              )
              );
              $author_count=array();
              foreach ($facets_result['author_type'] as $value) {
              $val_arr = (array) $value;
              $author_count[$val_arr['author_type']] = $val_arr['count(*)'];
              }
              foreach ($this->cm->author_type as $key => $value) {
              $states[$key] = array(
              'title' => $value,
              'count' => isset($author_count[$key]) ? $author_count[$key] : 0
              );
              }
              }
              $filters_author_type = $this->get_filters($states, $page_url, $author_type, $front_slug = '', $name = 'author_type');
             */
            $page_url = $page_url . '&s=' . urlencode($s);

            if ($total) {
                $pager = $this->themePager($page, $page_url, $total, $per_page, $orderby, $order);
            }

            include(CRITIC_MATIC_PLUGIN_DIR . 'includes/list_posts_search.php');
            return;
        }

        if ($pid) {
            // Post page
            $append = '&pid=' . $pid;
            $tabs_arr = $this->cm->post_tabs;
            $tabs = $this->get_tabs($url, $tabs_arr, $curr_tab, $append);

            if (!$curr_tab) {
                $curr_tab = 'home';
            }

            if ($curr_tab == 'home') {
                // Post view page  
                $post = $this->cm->get_post($pid, true, true);
                wp_enqueue_style('audience_rating', CRITIC_MATIC_PLUGIN_URL . 'css/rating.css', false, CRITIC_MATIC_VERSION);
                include(CRITIC_MATIC_PLUGIN_DIR . 'includes/view_post.php');
            } else if ($curr_tab == 'edit') {
                // Edit the post
                $authors = $this->cm->get_all_authors(1);
                if (isset($_POST['title'])) {
                    $valid = $this->cm->post_edit_validate($_POST);
                    if ($valid === true) {
                        $result_id = $this->cm->post_edit_submit($_POST);
                        $result = __('Post updated');
                        print "<div class=\"updated\"><p><strong>$result</strong></p></div>";
                    } else {
                        print "<div class=\"error\"><p><strong>$valid</strong></p></div>";
                    }
                }
                $post = $this->cm->get_post($pid);
                include(CRITIC_MATIC_PLUGIN_DIR . 'includes/edit_post.php');
            } else if ($curr_tab == 'trash') {
                // Trash
                if (isset($_POST['id'])) {
                    $valid = $this->cm->post_edit_validate($_POST);
                    if ($valid === true) {
                        $result_id = $this->cm->trash_post($_POST);
                        $status = isset($_POST['status']) ? $_POST['status'] : 0;
                        if ($status == 2) {
                            $result = __('Post') . ' [' . $result_id . '] ' . __('moved to trash');
                        } else {
                            $result = __('Post') . ' [' . $result_id . '] ' . __('untrashed');
                        }

                        print "<div class=\"updated\"><p><strong>$result</strong></p></div>";
                    } else {
                        print "<div class=\"error\"><p><strong>$valid</strong></p></div>";
                    }
                }
                $post = $this->cm->get_post($pid);
                include(CRITIC_MATIC_PLUGIN_DIR . 'includes/trash_post.php');
            }

            return;
        }
        /*
         * Other pages
         */

        $tabs_arr = $this->cm->main_tabs;
        $tabs = $this->get_tabs($url, $tabs_arr, $curr_tab, $append);

        // Filter by status
        $home_status = -1;
        $status = isset($_GET['status']) ? (int) $_GET['status'] : $home_status;

        if (!$curr_tab) {
            $curr_tab = 'home';
        }

        if ($curr_tab == 'home') {

            $filters = array(
                'status' => $this->cm->post_status
            );

            $filters_tabs = $this->get_filters_tabs($filters, $page_url);
            $query_adb = $filters_tabs['query_adb'];
            $query = $query_adb->get_query();
            $page_url = $filters_tabs['p'];
            $count = $filters_tabs['c'];

            $per_page = $this->cm->perpage;
            $pager = $this->themePager($page, $page_url, $count, $per_page, $orderby, $order);
            $posts = $this->cm->get_posts($query, $page, $per_page, $orderby, $order, false, false);

            include(CRITIC_MATIC_PLUGIN_DIR . 'includes/list_posts_overview.php');
        } else if ($curr_tab == 'details') {

            $page_url .= '&tab=' . $curr_tab;

            $filters = array(
                'post_update' => $this->cm->post_update,
                'post_date' => $this->cm->post_update,
                'type' => $this->cm->post_type,
                'view_type' => $this->cm->post_view_type,
                'author_type' => $this->cm->author_type,
                'meta_type' => $this->cm->post_meta_status,
                'status' => $this->cm->post_status
            );

            $filters_tabs = $this->get_filters_tabs($filters, $page_url);
            $query_adb = $filters_tabs['query_adb'];
            $query = $query_adb->get_query();
            $page_url = $filters_tabs['p'];
            $count = $filters_tabs['c'];

            $per_page = $this->cm->perpage;
            $pager = $this->themePager($page, $page_url, $count, $per_page, $orderby, $order);
            $posts = $this->cm->get_posts($query, $page, $per_page, $orderby, $order, false, false);

            $author_type = isset($_GET['author_type']) ? (int) $_GET['author_type'] : -1;

            include(CRITIC_MATIC_PLUGIN_DIR . 'includes/list_posts_details.php');
        } else if ($curr_tab == 'meta') {
            // Filter by status

            $page_url .= '&tab=' . $curr_tab;

            $filter_arr = $this->cm->get_meta_states();
            $filters = $this->get_filters($filter_arr, $page_url, $status);
            if ($status != $home_status) {
                $page_url = $page_url . '&status=' . $status;
            }

            $count = isset($filter_arr[$status]['count']) ? $filter_arr[$status]['count'] : 0;

            // Filter by rating
            $home_rating = -1;
            $meta_rating = isset($_GET['rating']) ? (int) $_GET['rating'] : $home_rating;
            $filter_meta_rating_arr = $this->cm->get_meta_rating($status);
            $rating_filters = $this->get_filters($filter_meta_rating_arr, $page_url, $meta_rating, $front_slug = '', $name = 'rating');
            if ($meta_rating != $home_rating) {
                $page_url = $page_url . '&rating=' . $meta_rating;
                $count = isset($filter_meta_rating_arr[$meta_rating]['count']) ? $filter_meta_rating_arr[$meta_rating]['count'] : 0;
            }


            // Filter by meta type
            $home_meta_type = -1;
            $type = isset($_GET['type']) ? (int) $_GET['type'] : $home_meta_type;
            $filter_type_arr = $this->cm->get_meta_type($status);
            $filters_type = $this->get_filters($filter_type_arr, $page_url, $type, $front_slug = '', $name = 'type');
            if ($type != $home_meta_type) {
                $page_url = $page_url . '&type=' . $type;
            }

            $per_page = $this->cm->perpage;
            $pager = $this->themePager($page, $page_url, $count, $per_page, $orderby, $order);
            $meta = $this->cm->get_meta($status, $page, $type, $meta_rating, $orderby, $order);
            include(CRITIC_MATIC_PLUGIN_DIR . 'includes/list_meta.php');
        } else if ($curr_tab == 'log') {
            $this->meta_log($tabs, $url);
        } else if ($curr_tab == 'add') {
            if (isset($_POST['title'])) {
                $valid = $this->cm->post_edit_validate($_POST);
                if ($valid === true) {
                    $result_id = $this->cm->post_add_submit($_POST);
                    $result = __('Post added. Go to the post: ') . '<a href="' . $url . '&pid=' . $result_id . '">' . $result_id . '</a>';
                    print "<div class=\"updated\"><p><strong>$result</strong></p></div>";
                } else {
                    print "<div class=\"error\"><p><strong>$valid</strong></p></div>";
                }
            }
            include(CRITIC_MATIC_PLUGIN_DIR . 'includes/add_post.php');
        }
    }

    /*
     * Audience posts
     */

    public function audience() {
        $curr_tab = $this->get_tab();
        $page = $this->get_page();
        $per_page = $this->get_perpage();
        $pid = isset($_GET['pid']) ? (int) $_GET['pid'] : '';

        //Bulk actions
        $this->bulk_submit();

        //Sort
        $sort_pages = $this->cm->sort_pages;
        $orderby = $this->get_orderby($sort_pages);
        $order = $this->get_order();

        $url = $this->admin_page . $this->audience_url;
        $page_url = $url;

        $cfront = $this->get_cfront();

        if ($pid) {
            // Post page
            $append = '&pid=' . $pid;
            $tabs_arr = $this->cm->post_tabs;
            $tabs = $this->get_tabs($url, $tabs_arr, $curr_tab, $append);

            if (!$curr_tab) {
                $curr_tab = 'home';
            }

            if ($curr_tab == 'home') {
                // Post view page  
                $post = $this->cm->get_post($pid);
                wp_enqueue_style('audience_rating', CRITIC_MATIC_PLUGIN_URL . 'css/rating.css', false, CRITIC_MATIC_VERSION);
                include(CRITIC_MATIC_PLUGIN_DIR . 'includes/view_post.php');
            } else if ($curr_tab == 'edit') {
                // Edit the post
                $authors = $this->cm->get_all_authors(1);
                if (isset($_POST['title'])) {
                    $valid = $this->cm->post_edit_validate($_POST);
                    if ($valid === true) {
                        $result_id = $this->cm->post_edit_submit($_POST);
                        $result = __('Post updated');
                        print "<div class=\"updated\"><p><strong>$result</strong></p></div>";
                    } else {
                        print "<div class=\"error\"><p><strong>$valid</strong></p></div>";
                    }
                }
                $post = $this->cm->get_post($pid);
                include(CRITIC_MATIC_PLUGIN_DIR . 'includes/edit_post.php');
            } else if ($curr_tab == 'trash') {
                // Trash
                if (isset($_POST['id'])) {
                    $valid = $this->cm->post_edit_validate($_POST);
                    if ($valid === true) {
                        $result_id = $this->cm->trash_post($_POST);
                        $status = isset($_POST['status']) ? $_POST['status'] : 0;
                        if ($status == 2) {
                            $result = __('Post') . ' [' . $result_id . '] ' . __('moved to trash');
                        } else {
                            $result = __('Post') . ' [' . $result_id . '] ' . __('untrashed');
                        }

                        print "<div class=\"updated\"><p><strong>$result</strong></p></div>";
                    } else {
                        print "<div class=\"error\"><p><strong>$valid</strong></p></div>";
                    }
                }
                $post = $this->cm->get_post($pid);
                include(CRITIC_MATIC_PLUGIN_DIR . 'includes/trash_post.php');
            }

            return;
        }
        /*
         * Other pages
         */

        $tabs_arr = $this->cm->audience_tabs;
        $tabs = $this->get_tabs($url, $tabs_arr, $curr_tab, $append);

        // Filter by status
        $home_status = -1;
        $status = isset($_GET['status']) ? (int) $_GET['status'] : $home_status;

        if (!$curr_tab) {
            $curr_tab = 'home';
        }

        if ($curr_tab == 'home') {
            // Audience authors

            $author_type = 2;
            $query_adb = new QueryADB();
            $query_adb->add_query('author_type', $author_type);

            $filters = array(
                'status' => $this->cm->post_status
            );

            $filters_tabs = $this->get_filters_tabs($filters, $page_url, $query_adb);
            $query_adb = $filters_tabs['query_adb'];
            $query = $query_adb->get_query();
            $page_url = $filters_tabs['p'];
            $count = $filters_tabs['c'];

            $per_page = $this->cm->perpage;
            $pager = $this->themePager($page, $page_url, $count, $per_page, $orderby, $order);
            $posts = $this->cm->get_posts($query, $page, $per_page, $orderby, $order, false, false);

            include(CRITIC_MATIC_PLUGIN_DIR . 'includes/list_posts_audience.php');
            wp_enqueue_style('audience_rating', CRITIC_MATIC_PLUGIN_URL . 'css/rating.css', false, CRITIC_MATIC_VERSION);
        } else if ($curr_tab == 'queue') {

            $ca = $this->get_ca();

            $home_status = 0;
            if ($status == -1) {
                $status = 0;
            }
            $page_url = $page_url . '&tab=' . $curr_tab;
            $filter_arr = $ca->get_queue_states();
            $filters = $this->get_filters($filter_arr, $page_url, $status);
            if ($status != $home_status) {
                $page_url = $page_url . '&status=' . $status;
            }

            $count = isset($filter_arr[$status]['count']) ? $filter_arr[$status]['count'] : 0;
            $pager = $this->themePager($page, $page_url, $count, $per_page, $orderby, $order);
            $posts = $ca->get_queue($status, $page, $per_page, $orderby, $order);

            include(CRITIC_MATIC_PLUGIN_DIR . 'includes/list_queue_audience.php');
            wp_enqueue_style('audience_rating', CRITIC_MATIC_PLUGIN_URL . 'css/rating.css', false, CRITIC_MATIC_VERSION);
        } else if ($curr_tab == 'iplist') {
            // Get IP list
            $page_url .= '&tab=' . $curr_tab;
            $filter_arr = $this->cm->get_ip_states();
            $filters = $this->get_filters($filter_arr, $page_url, $status);
            if ($status != $home_status) {
                $page_url = $page_url . '&status=' . $status;
            }

            $count = isset($filter_arr[$status]['count']) ? $filter_arr[$status]['count'] : 0;

            $pager = $this->themePager($page, $page_url, $count, $per_page, $orderby, $order);
            $posts = $this->cm->get_ips($status, $page, $orderby, $order);

            include(CRITIC_MATIC_PLUGIN_DIR . 'includes/list_ip_audience.php');
        }
    }

    /*
     * The authors page
     */

    public function authors() {

        $curr_tab = $this->get_tab();
        $page = $this->get_page();
        $per_page = $this->get_perpage();
        $aid = isset($_GET['aid']) ? (int) $_GET['aid'] : '';

        $this->bulk_submit();

        //Sort
        $sort_pages = $this->cm->sort_pages;
        $orderby = $this->get_orderby($sort_pages);
        $order = $this->get_order();

        $url = $this->admin_page . $this->authors_url;
        $page_url = $url;

        if ($aid) {
            // Author page
            $append = '&aid=' . $aid;
            $tabs_arr = $this->cm->get_sync_tabs($this->cm->author_tabs);
            $tabs = $this->get_tabs($url, $tabs_arr, $curr_tab, $append);

            if (!$curr_tab) {
                $curr_tab = 'home';
            }

            if ($curr_tab == 'home') {
                // Author view page  
                $author = $this->cm->get_author($aid);
                include(CRITIC_MATIC_PLUGIN_DIR . 'includes/view_author.php');
            } else if ($curr_tab == 'posts') {
                // Author posts
                $this->feeds_posts($tabs, $url, 0, $aid);
            } else if ($curr_tab == 'feeds') {
                // Author feeds
                $page_url .= '&tab=feeds';
                $this->feeds_campaigns($tabs, $page_url, 0, $aid);
            } else if ($curr_tab == 'parsers') {
                // Author parsers
                $page_url .= '&tab=parsers';
                $this->parser_campaingns($tabs, $page_url, $aid);
            } else if ($curr_tab == 'edit') {
                if (isset($_POST['name'])) {
                    $valid = $this->cm->author_edit_validate($_POST);
                    if ($valid === true) {
                        $result_id = $this->cm->author_edit_submit($_POST);
                        $result = __('Author updated');
                        print "<div class=\"updated\"><p><strong>$result</strong></p></div>";
                    } else {
                        print "<div class=\"error\"><p><strong>$valid</strong></p></div>";
                    }
                }
                $author = $this->cm->get_author($aid);
                $author_type = $this->cm->author_type;
                $tags = $this->cm->get_tags();
                include(CRITIC_MATIC_PLUGIN_DIR . 'includes/edit_author.php');
            } else if ($curr_tab == 'trash') {
                //Trash
                if (isset($_POST['id'])) {
                    $valid = $this->cm->author_edit_validate($_POST);
                    if ($valid === true) {
                        $result_id = $this->cm->trash_author($_POST);

                        $status = isset($_POST['status']) ? $_POST['status'] : 0;
                        if ($status == 2) {
                            $result = __('Author') . ' [' . $result_id . '] ' . __('moved to trash');
                        } else {
                            $result = __('Author') . ' [' . $result_id . '] ' . __('untrashed');
                        }

                        print "<div class=\"updated\"><p><strong>$result</strong></p></div>";
                    } else {
                        print "<div class=\"error\"><p><strong>$valid</strong></p></div>";
                    }
                }
                $author = $this->cm->get_author($aid);

                include(CRITIC_MATIC_PLUGIN_DIR . 'includes/trash_author.php');
            }

            return;
        }

        //
        // Other pages
        //
        //Tabs
        $tabs_arr = $this->cm->authors_tabs;
        $tabs = $this->get_tabs($url, $tabs_arr, $curr_tab);

        if (!$curr_tab) {
            // Authors   

            $author_type = isset($_GET['type']) ? (int) $_GET['type'] : 1;

            $query_adb = new QueryADB();

            $filters = array(
                'type' => array(
                    'type_list' => $this->cm->author_type,
                    'home_type' => 1,
                ),
                'avatar' => $this->cm->pro_author_avatar,
                'status' => $this->cm->author_status,
            );

            $filters_tabs = $this->get_filters_tabs($filters, $page_url, $query_adb, 'author', false);
            $query_adb = $filters_tabs['query_adb'];
            $query = $query_adb->get_query();
            $page_url = $filters_tabs['p'];
            $count = $filters_tabs['c'];

            $per_page = $this->cm->perpage;
            $pager = $this->themePager($page, $page_url, $count, $per_page, $orderby, $order);
            $authors = $this->cm->get_authors_query($query, $page, $per_page, $orderby, $order);

            if ($author_type == 1) {
                include(CRITIC_MATIC_PLUGIN_DIR . 'includes/list_authors.php');
            } else {
                include(CRITIC_MATIC_PLUGIN_DIR . 'includes/list_authors_audience.php');
            }
        } else if ($curr_tab == 'add') {
            // Add
            if (isset($_POST['name'])) {
                $valid = $this->cm->author_edit_validate($_POST);
                if ($valid === true) {
                    $result_id = $this->cm->author_edit_submit($_POST);
                    $result = __('Author added. Go to the author: ') . '<a href="' . $url . '&aid=' . $result_id . '">' . $result_id . '</a>';
                    print "<div class=\"updated\"><p><strong>$result</strong></p></div>";
                } else {
                    print "<div class=\"error\"><p><strong>$valid</strong></p></div>";
                }
            }
            $author_type = $this->cm->author_type;
            $tags = $this->cm->get_tags();
            include(CRITIC_MATIC_PLUGIN_DIR . 'includes/add_author.php');
        }
    }

    /*
     * The tags page
     */

    public function tags() {

        $curr_tab = $this->get_tab();
        $page = $this->get_page();
        $per_page = $this->get_perpage();

        $tid = isset($_GET['tid']) ? (int) $_GET['tid'] : '';

        //Sort
        $sort_pages = $this->cm->sort_pages;
        $orderby = $this->get_orderby($sort_pages);
        $order = $this->get_order();

        $url = $this->admin_page . $this->tags_url;
        $page_url = $url;

        if ($tid) {
            // Tag page
            $append = '&tid=' . $tid;
            $tabs_arr = $this->cm->tag_tabs;
            $tabs = $this->get_tabs($url, $tabs_arr, $curr_tab, $append);

            if (!$curr_tab) {
                $curr_tab = 'home';
            }

            if ($curr_tab == 'home') {
                // Tag view page  
                $tag = $this->cm->get_tag($tid);

                $authors = $this->cm->get_authors(-1, $page, $tag->id);

                include(CRITIC_MATIC_PLUGIN_DIR . 'includes/view_tag.php');
            } else if ($curr_tab == 'edit') {

                if (isset($_POST['name'])) {
                    $valid = $this->cm->tag_edit_validate($_POST);
                    if ($valid === true) {
                        $result_id = $this->cm->tag_edit_submit($_POST);
                        $result = __('Tag updated');
                        print "<div class=\"updated\"><p><strong>$result</strong></p></div>";
                    } else {
                        print "<div class=\"error\"><p><strong>$valid</strong></p></div>";
                    }
                }

                $tag = $this->cm->get_tag($tid);
                include(CRITIC_MATIC_PLUGIN_DIR . 'includes/edit_tag.php');
            } else if ($curr_tab == 'trash') {
                //Trash
                if (isset($_POST['id'])) {
                    $valid = $this->cm->tag_edit_validate($_POST);
                    if ($valid === true) {
                        $result_id = $this->cm->trash_tag($_POST);

                        $status = isset($_POST['status']) ? $_POST['status'] : 0;
                        if ($status == 2) {
                            $result = __('Tag') . ' [' . $result_id . '] ' . __('moved to trash');
                        } else {
                            $result = __('Tag') . ' [' . $result_id . '] ' . __('untrashed');
                        }

                        print "<div class=\"updated\"><p><strong>$result</strong></p></div>";
                    } else {
                        print "<div class=\"error\"><p><strong>$valid</strong></p></div>";
                    }
                }
                $tag = $this->cm->get_tag($tid);

                include(CRITIC_MATIC_PLUGIN_DIR . 'includes/trash_tag.php');
            }

            return;
        }

        //
        // Other pages
        //
        //Tabs
        $tabs_arr = $this->cm->tags_tabs;
        $tabs = $this->get_tabs($url, $tabs_arr, $curr_tab);

        // Filter by status
        $home_status = -1;
        $status = isset($_GET['status']) ? (int) $_GET['status'] : $home_status;
        $filter_arr = $this->cm->get_tag_states();
        $filters = $this->get_filters($filter_arr, $page_url, $status);
        if ($status != $home_status) {
            $page_url = $page_url . '&status=' . $status;
        }

        $count = isset($filter_arr[$status]['count']) ? $filter_arr[$status]['count'] : 0;

        if (!$curr_tab) {
            // Authors          
            $pager = $this->themePager($page, $page_url, $count, $per_page, $orderby, $order);
            $tags = $this->cm->get_tags($status, $page, $orderby, $order);
            $tag_status = $this->cm->tag_status;
            include(CRITIC_MATIC_PLUGIN_DIR . 'includes/list_tags.php');
        } else if ($curr_tab == 'add') {
            // Add
            if (isset($_POST['name'])) {
                $valid = $this->cm->tag_edit_validate($_POST);
                if ($valid === true) {
                    $result_id = $this->cm->tag_edit_submit($_POST);
                    $result = __('Tag added. Go to the tag: ') . '<a href="' . $url . '&tid=' . $result_id . '">' . $result_id . '</a>';
                    print "<div class=\"updated\"><p><strong>$result</strong></p></div>";
                } else {
                    print "<div class=\"error\"><p><strong>$valid</strong></p></div>";
                }
            }
            include(CRITIC_MATIC_PLUGIN_DIR . 'includes/add_tag.php');
        }
    }

    /*
     * The genres page
     */

    public function genres() {

        $curr_tab = $this->get_tab();
        $page = $this->get_page();
        $per_page = $this->get_perpage();

        $gid = isset($_GET['gid']) ? (int) $_GET['gid'] : '';

        $ma = $this->get_ma();

        //Sort
        $sort_pages = $ma->sort_pages;
        $orderby = $this->get_orderby($sort_pages);
        $order = $this->get_order();

        $url = $this->admin_page . $this->genres_url;
        $page_url = $url;

        if ($gid) {
            // Genres page
            $append = '&gid=' . $gid;
            $tabs_arr = $ma->genre_tabs;
            $tabs = $this->get_tabs($url, $tabs_arr, $curr_tab, $append);

            if (!$curr_tab) {
                $curr_tab = 'home';
            }

            if ($curr_tab == 'home') {
                // Gernre view page  

                $genre = $ma->get_genre($gid);
                include(CRITIC_MATIC_PLUGIN_DIR . 'includes/view_genre.php');
            } else if ($curr_tab == 'edit') {

                if (isset($_POST['name'])) {
                    $valid = $ma->genre_edit_validate($_POST);
                    if ($valid === true) {
                        $result_id = $ma->genre_edit_submit($_POST);
                        $result = __('Genre updated');
                        print "<div class=\"updated\"><p><strong>$result</strong></p></div>";
                    } else {
                        print "<div class=\"error\"><p><strong>$valid</strong></p></div>";
                    }
                }

                $genre = $ma->get_genre($gid);
                include(CRITIC_MATIC_PLUGIN_DIR . 'includes/edit_genre.php');
            } else if ($curr_tab == 'trash') {
                //Trash
                if (isset($_POST['id'])) {
                    $valid = $ma->genre_edit_validate($_POST);
                    if ($valid === true) {
                        $result_id = $ma->trash_genre($_POST);

                        $status = isset($_POST['status']) ? $_POST['status'] : 0;
                        if ($status == 2) {
                            $result = __('Genre') . ' [' . $result_id . '] ' . __('moved to trash');
                        } else {
                            $result = __('Genre') . ' [' . $result_id . '] ' . __('untrashed');
                        }

                        print "<div class=\"updated\"><p><strong>$result</strong></p></div>";
                    } else {
                        print "<div class=\"error\"><p><strong>$valid</strong></p></div>";
                    }
                }
                $genre = $ma->get_genre($gid);

                include(CRITIC_MATIC_PLUGIN_DIR . 'includes/trash_genre.php');
            }

            return;
        }

        //
        // Other pages
        //
        //Tabs
        $tabs_arr = $ma->genres_tabs;
        $tabs = $this->get_tabs($url, $tabs_arr, $curr_tab);

        // Filter by status
        $home_status = -1;
        $status = isset($_GET['status']) ? (int) $_GET['status'] : $home_status;
        $filter_arr = $ma->get_genres_states();
        $filters = $this->get_filters($filter_arr, $page_url, $status);
        if ($status != $home_status) {
            $page_url = $page_url . '&status=' . $status;
        }

        $count = isset($filter_arr[$status]['count']) ? $filter_arr[$status]['count'] : 0;

        if (!$curr_tab) {
            // Get          

            $pager = $this->themePager($page, $page_url, $count, $per_page, $orderby, $order);

            $genres = $ma->get_genres($status, $page, $orderby, $order);
            $genre_status = $ma->genre_status;
            include(CRITIC_MATIC_PLUGIN_DIR . 'includes/list_genres.php');
        } else if ($curr_tab == 'add') {
            // Add
            if (isset($_POST['name'])) {
                $valid = $ma->genre_edit_validate($_POST);
                if ($valid === true) {
                    $result_id = $ma->genre_edit_submit($_POST);
                    $result = __('Genre added. Go to the genre: ') . '<a href="' . $url . '&gid=' . $result_id . '">' . $result_id . '</a>';
                    print "<div class=\"updated\"><p><strong>$result</strong></p></div>";
                } else {
                    print "<div class=\"error\"><p><strong>$valid</strong></p></div>";
                }
            }
            include(CRITIC_MATIC_PLUGIN_DIR . 'includes/add_genre.php');
        }
    }

    /*
     * The countries page
     */

    public function countries() {

        $curr_tab = $this->get_tab();
        $page = $this->get_page();
        $per_page = $this->get_perpage();

        $cid = isset($_GET['cid']) ? (int) $_GET['cid'] : '';

        $ma = $this->get_ma();

        //Sort
        $sort_pages = $ma->sort_pages;
        $orderby = $this->get_orderby($sort_pages);
        $order = $this->get_order();

        $url = $this->admin_page . $this->countries_url;
        $page_url = $url;

        if ($cid) {
            // Genres page
            $append = '&cid=' . $cid;
            $tabs_arr = $ma->country_tabs;
            $tabs = $this->get_tabs($url, $tabs_arr, $curr_tab, $append);

            if (!$curr_tab) {
                $curr_tab = 'home';
            }

            if ($curr_tab == 'home') {
                // Gernre view page  

                $country = $ma->get_country($cid);
                include(CRITIC_MATIC_PLUGIN_DIR . 'includes/view_country.php');
            } else if ($curr_tab == 'edit') {

                if (isset($_POST['name'])) {
                    $valid = $ma->country_edit_validate($_POST);
                    if ($valid === true) {
                        $result_id = $ma->country_edit_submit($_POST);
                        $result = __('Genre updated');
                        print "<div class=\"updated\"><p><strong>$result</strong></p></div>";
                    } else {
                        print "<div class=\"error\"><p><strong>$valid</strong></p></div>";
                    }
                }

                $country = $ma->get_country($cid);
                include(CRITIC_MATIC_PLUGIN_DIR . 'includes/edit_country.php');
            } else if ($curr_tab == 'trash') {
                //Trash
                if (isset($_POST['id'])) {
                    $valid = $ma->country_edit_validate($_POST);
                    if ($valid === true) {
                        $result_id = $ma->trash_country($_POST);

                        $status = isset($_POST['status']) ? $_POST['status'] : 0;
                        if ($status == 2) {
                            $result = __('Genre') . ' [' . $result_id . '] ' . __('moved to trash');
                        } else {
                            $result = __('Genre') . ' [' . $result_id . '] ' . __('untrashed');
                        }

                        print "<div class=\"updated\"><p><strong>$result</strong></p></div>";
                    } else {
                        print "<div class=\"error\"><p><strong>$valid</strong></p></div>";
                    }
                }
                $country = $ma->get_country($cid);

                include(CRITIC_MATIC_PLUGIN_DIR . 'includes/trash_country.php');
            }

            return;
        }

        //
        // Other pages
        //
        //Tabs
        $tabs_arr = $ma->countries_tabs;
        $tabs = $this->get_tabs($url, $tabs_arr, $curr_tab);

        // Filter by status
        $home_status = -1;
        $status = isset($_GET['status']) ? (int) $_GET['status'] : $home_status;
        $filter_arr = $ma->get_countries_states();
        $filters = $this->get_filters($filter_arr, $page_url, $status);
        if ($status != $home_status) {
            $page_url = $page_url . '&status=' . $status;
        }

        $count = isset($filter_arr[$status]['count']) ? $filter_arr[$status]['count'] : 0;

        if (!$curr_tab) {
            // Get          

            $pager = $this->themePager($page, $page_url, $count, $per_page, $orderby, $order);

            $countries = $ma->get_countries($status, $page, $orderby, $order);
            $country_status = $ma->country_status;
            include(CRITIC_MATIC_PLUGIN_DIR . 'includes/list_countries.php');
        } else if ($curr_tab == 'add') {
            // Add
            if (isset($_POST['name'])) {
                $valid = $ma->country_edit_validate($_POST);
                if ($valid === true) {
                    $result_id = $ma->country_edit_submit($_POST);
                    $result = __('Genre added. Go to the country: ') . '<a href="' . $url . '&cid=' . $result_id . '">' . $result_id . '</a>';
                    print "<div class=\"updated\"><p><strong>$result</strong></p></div>";
                } else {
                    print "<div class=\"error\"><p><strong>$valid</strong></p></div>";
                }
            }
            include(CRITIC_MATIC_PLUGIN_DIR . 'includes/add_country.php');
        }
    }

    /*
     * The providers page
     */

    public function providers() {

        $curr_tab = $this->get_tab();
        $page = $this->get_page();
        $per_page = $this->get_perpage();

        $pid = isset($_GET['pid']) ? (int) $_GET['pid'] : '';

        $ma = $this->get_ma();

        //Sort
        $sort_pages = $ma->sort_pages;
        $orderby = $this->get_orderby($sort_pages);
        $order = $this->get_order();

        $url = $this->admin_page . $this->providers_url;
        $page_url = $url;

        if ($pid) {
            // Provider page
            $append = '&pid=' . $pid;
            $tabs_arr = $ma->provider_tabs;
            $tabs = $this->get_tabs($url, $tabs_arr, $curr_tab, $append);

            if (!$curr_tab) {
                $curr_tab = 'home';
            }

            if ($curr_tab == 'home') {
                // Provider view page  

                $provider = $ma->get_provider($pid);
                include(CRITIC_MATIC_PLUGIN_DIR . 'includes/view_provider.php');
            } else if ($curr_tab == 'edit') {

                if (isset($_POST['name'])) {
                    $valid = $ma->provider_edit_validate($_POST);
                    if ($valid === true) {
                        $result_id = $ma->provider_edit_submit($_POST);
                        $result = __('Genre updated');
                        print "<div class=\"updated\"><p><strong>$result</strong></p></div>";
                    } else {
                        print "<div class=\"error\"><p><strong>$valid</strong></p></div>";
                    }
                }

                $provider = $ma->get_provider($pid);
                include(CRITIC_MATIC_PLUGIN_DIR . 'includes/edit_provider.php');
            } else if ($curr_tab == 'trash') {
                //Trash
                if (isset($_POST['id'])) {
                    $valid = $ma->provider_edit_validate($_POST);
                    if ($valid === true) {
                        $result_id = $ma->trash_provider($_POST);

                        $status = isset($_POST['status']) ? $_POST['status'] : 0;
                        if ($status == 2) {
                            $result = __('Genre') . ' [' . $result_id . '] ' . __('moved to trash');
                        } else {
                            $result = __('Genre') . ' [' . $result_id . '] ' . __('untrashed');
                        }

                        print "<div class=\"updated\"><p><strong>$result</strong></p></div>";
                    } else {
                        print "<div class=\"error\"><p><strong>$valid</strong></p></div>";
                    }
                }
                $provider = $ma->get_provider($pid);

                include(CRITIC_MATIC_PLUGIN_DIR . 'includes/trash_provider.php');
            }

            return;
        }

        //
        // Other pages
        //
        //Tabs
        $tabs_arr = $ma->providers_tabs;
        $tabs = $this->get_tabs($url, $tabs_arr, $curr_tab);

        // Filter by status
        $home_status = -1;
        $status = isset($_GET['status']) ? (int) $_GET['status'] : $home_status;
        $filter_arr = $ma->get_providers_states();
        $filters = $this->get_filters($filter_arr, $page_url, $status);
        if ($status != $home_status) {
            $page_url = $page_url . '&status=' . $status;
        }

        // Filter be free
        $home_free = -1;
        $free = isset($_GET['free']) ? (int) $_GET['free'] : $home_free;
        $filter_free_arr = $ma->get_providers_free_status($status);
        $filters_free = $this->get_filters($filter_free_arr, $page_url, $free, $front_slug = '', $name = 'free');
        if ($free != $home_free) {
            $page_url = $page_url . '&free=' . $free;
            $count = isset($filter_free_arr[$free]['count']) ? $filter_free_arr[$free]['count'] : 0;
        }

        $count = isset($filter_arr[$status]['count']) ? $filter_arr[$status]['count'] : 0;

        if (!$curr_tab) {
            // Get          
            $pager = $this->themePager($page, $page_url, $count, $per_page, $orderby, $order);
            $providers = $ma->get_providers($status, $page, $orderby, $order, $free);
            $provider_status = $ma->provider_status;
            include(CRITIC_MATIC_PLUGIN_DIR . 'includes/list_providers.php');
        } else if ($curr_tab == 'add') {
            // Add
            if (isset($_POST['name'])) {
                $valid = $ma->provider_edit_validate($_POST);
                if ($valid === true) {
                    $result_id = $ma->provider_edit_submit($_POST);
                    $result = __('Genre added. Go to the provider: ') . '<a href="' . $url . '&pid=' . $result_id . '">' . $result_id . '</a>';
                    print "<div class=\"updated\"><p><strong>$result</strong></p></div>";
                } else {
                    print "<div class=\"error\"><p><strong>$valid</strong></p></div>";
                }
            }
            include(CRITIC_MATIC_PLUGIN_DIR . 'includes/add_provider.php');
        }
    }

    /*
     * Clear commetns page
     */

    public function clear_comments() {
        $curr_tab = $this->get_tab();
        $page = $this->get_page();
        $per_page = $this->get_perpage();

        $url = $this->admin_page . $this->clear_url;
        $page_url = $url;

        $cc = $this->cm->get_cc();

        $tabs_arr = $cc->tabs;
        $tabs = $this->get_tabs($url, $tabs_arr, $curr_tab);

        // Sort
        $sort_pages = $this->cm->sort_pages;
        $orderby = $this->get_orderby($sort_pages);
        $order = $this->get_order();

        /*
          'home' => 'Overview',
          'settings' => 'Settings',
          'test' => 'Test',
          'revisions' => 'Revisions',
         */

        if (!$curr_tab) {

            $page_url = $page_url . '&tab=' . $curr_tab;
            $count = $cc->get_log_count();
            $pager = $this->themePager($page, $page_url, $count, $per_page, $orderby, $order);
            $posts = $cc->get_log($page, $per_page, $orderby, $order);

            include(CRITIC_MATIC_PLUGIN_DIR . 'includes/list_clear_comments.php');
        } else if ($curr_tab == 'settings') {
            include(CRITIC_MATIC_PLUGIN_DIR . 'includes/settings_clear_comments.php');
        } else if ($curr_tab == 'test') {
            include(CRITIC_MATIC_PLUGIN_DIR . 'includes/test_clear_comments.php');
        } else if ($curr_tab == 'revisions') {

            $page_url = $page_url . '&tab=' . $curr_tab;
            $count = $cc->get_revisions_count();
            $pager = $this->themePager($page, $page_url, $count, $per_page, $orderby, $order);
            $posts = $cc->get_revisions($page, $per_page, $orderby, $order);
            wp_enqueue_style('audience_rating', CRITIC_MATIC_PLUGIN_URL . 'css/rating.css', false, CRITIC_MATIC_VERSION);
            include(CRITIC_MATIC_PLUGIN_DIR . 'includes/list_clear_comments_revisions.php');
        }
    }

    /*
     * The countries page
     */

    public function transcriptions() {

        $curr_tab = $this->get_tab();
        $page = $this->get_page();
        $per_page = $this->get_perpage();

        $ts = $this->get_ts();

        //Sort
        $sort_pages = $ts->sort_pages;
        $orderby = $this->get_orderby($sort_pages);
        $order = $this->get_order();

        $url = $this->admin_page . $this->transcriptions_url;
        $page_url = $url;

        $query_adb = new QueryADB();
        $query_adb->add_query('view_type', 1);

        $filters = array(
            'type' => $this->cm->post_type,
            'meta_type' => $this->cm->post_meta_status,
            'status' => $this->cm->post_status,
            'ts' => $ts->ts,
        );

        $filters_tabs = $this->get_filters_tabs($filters, $page_url, $query_adb);
        $query_adb = $filters_tabs['query_adb'];
        $query = $query_adb->get_query();
        $page_url = $filters_tabs['p'];
        $count = $filters_tabs['c'];

        $per_page = $this->cm->perpage;
        $pager = $this->themePager($page, $page_url, $count, $per_page, $orderby, $order);
        $posts = $this->cm->get_posts($query, $page, $per_page, $orderby, $order, false, false);

        include(CRITIC_MATIC_PLUGIN_DIR . 'includes/list_transcriptions.php');
    }

    /*
     * The movies page
     */

    public function movies() {
        $page = $this->get_page();
        $per_page = $this->get_perpage();
        $curr_tab = $this->get_tab();
        $url = $this->admin_page . $this->movies_url;
        $mid = isset($_GET['mid']) ? (int) $_GET['mid'] : '';
        $home_status = -1;
        $status = isset($_GET['status']) ? (int) $_GET['status'] : $home_status;
        $ma = $this->get_ma();
        $page_url = $url;

        //Sort
        $sort_pages = $ma->sort_pages;
        $orderby = $this->get_orderby($sort_pages);
        $order = $this->get_order();

        //Search
        $s = isset($_GET['s']) ? strip_tags(stripslashes($_GET['s'])) : '';
        if ($s) {
            // Search logic

            $start = ($page - 1) * $per_page;

            // Sort
            $sort = array();
            if ($orderby) {
                $sort = array('sort' => $orderby, 'type' => $order);
            }

            $results = $this->cs->front_search_movies_an($s, ' ', true, '', $start, $per_page, true);

            $movies = $results['result'];
            $count = $results['total'];

            $page_url = $page_url . '&s=' . urlencode($s);

            if ($count) {
                $pager = $this->themePager($page, $page_url, $count, $per_page, $orderby, $order);
            }

            include(CRITIC_MATIC_PLUGIN_DIR . 'includes/list_movies_search.php');
            return;
        }
        /*
         * Movie page
         */
        if ($mid) {

            //Bulk actions
            $this->bulk_submit();

            //Tabs
            $append = '&mid=' . $mid;
            $tabs_arr = $this->cm->movie_tabs;
            $tabs = $this->get_tabs($url, $tabs_arr, $curr_tab, $append);

            $movie = $ma->get_post($mid);

            if (!$curr_tab) {
                $curr_tab = 'home';
            }
            if ($curr_tab == 'home') {

                if (isset($_POST['mid'])) {
                    $nonce = wp_verify_nonce($_POST['critic-nonce'], 'critic-options');
                    if ($nonce) {

                        if (isset($_POST['genre'])) {
                            // Add a genre
                            $genre = $_POST['genre'];

                            if ($genre) {
                                $ma = $this->cm->get_ma();
                                $ma->add_movie_genre($mid, $genre);
                            }
                        }if ($_POST['edit_erating']) {
                            $ma = $this->cm->get_ma();
                            // Edit rating
                            $erating = (array) $ma->get_movie_erating($mid);

                            if ($erating) {
                                $rating_data = array();
                                foreach ($erating as $key => $value) {
                                    if ($key == 'id') {
                                        continue;
                                    }
                                    if (isset($_POST[$key])) {
                                        if ($value != $_POST[$key]) {
                                            $rating_data[$key] = $_POST[$key];
                                        }
                                    }
                                }
                                if ($rating_data) {

                                    // Upadte erating
                                    $ma->update_erating($erating['id'], $rating_data);
                                    // Calculate erating
                                    if (class_exists('MoviesLinks')) {
                                        $ml = new MoviesLinks();
                                        $ml_ma = $ml->get_ma();
                                        $ml_ma->update_erating($mid);
                                    }
                                }
                            }
                        }

                        print "<div class=\"updated\"><p><strong>Updated</strong></p></div>";
                    } else {
                        print "<div class=\"error\"><p><strong>Error nonce</strong></p></div>";
                    }
                }
                $critics_search = $this->cs->search_critics($movie, true);
                include(CRITIC_MATIC_PLUGIN_DIR . 'includes/view_movie.php');
            } else if ($curr_tab == 'actors') {
                include(CRITIC_MATIC_PLUGIN_DIR . 'includes/view_actors.php');
            } else if ($curr_tab == 'index') {
                include(CRITIC_MATIC_PLUGIN_DIR . 'includes/view_index.php');
            }
            return;
        }

        /*
         * List movies
         */



        // Filter by movie type
        $home_type = 'all';
        $type = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : $home_type;
        $filter_arr = $this->get_movie_types();
        $filters = $this->get_filters($filter_arr, $page_url, $type, '', 'type');
        $count = isset($filter_arr['all']['count']) ? $filter_arr['all']['count'] : 0;

        if ($type != $home_type) {
            $page_url = $page_url . '&type=' . $type;
            $count = isset($filter_arr[$type]['count']) ? $filter_arr[$type]['count'] : 0;
        }

        $per_page = $ma->perpage;
        $pager = $this->themePager($page, $page_url, $count, $per_page, $orderby, $order);
        $movies = $ma->get_posts($page, $type, $orderby, $order, true);
        include(CRITIC_MATIC_PLUGIN_DIR . 'includes/list_movies.php');
    }

    /*
     * The feeds page
     */

    public function feeds() {

        $curr_tab = $this->get_tab();
        $cid = isset($_GET['cid']) ? (int) $_GET['cid'] : '';
        $page = $this->get_page();
        $per_page = $this->get_perpage();

        //Sort
        $sort_pages = $this->cm->sort_pages;
        $orderby = $this->get_orderby($sort_pages);
        $order = $this->get_order();

        $url = $this->admin_page . $this->feeds_url;
        /*
         * Campaign page
         */
        if ($cid) {
            //Tabs
            $append = '&cid=' . $cid;
            $tabs_arr = $this->cf->campaign_tabs;
            $tabs = $this->get_tabs($url, $tabs_arr, $curr_tab, $append);
            $settings = $this->cf->get_feed_settings();

            if (!$curr_tab) {
                $curr_tab = 'home';
            }
            if ($curr_tab == 'home') {
                if ($_GET['export']) {
                    $urls = $this->cm->get_all_feed_urls($cid);
                    print '<h2>Export campaign URLs</h2>';
                    if ($urls) {
                        print '<p> Found links: ' . sizeof($urls) . '</p>';
                        $items = array();
                        foreach ($urls as $url) {
                            $items[] = $url->link;
                        }
                        print '<textarea style="width:90%; height:500px">' . implode("\n", $items) . '</textarea>';
                    }
                    exit;
                }
                // Campaign view page  
                $update_interval = $this->cf->update_interval;
                $feed_state = $this->cf->feed_state;
                $campaign = $this->cf->get_campaign($cid);
                if ($campaign) {
                    $author = $this->cm->get_author($campaign->author, true);
                    include(CRITIC_MATIC_PLUGIN_DIR . 'includes/view_feed.php');
                }
            } else if ($curr_tab == 'posts') {
                // Campaign post page         
                $this->cs->get_search_ids();
                $this->feeds_posts($tabs, $url, $cid);
            } else if ($curr_tab == 'update') {
                // Update
                $campaign = $this->cf->get_campaign($cid);
                $count = $this->cf->process_campaign($campaign);
               

                include(CRITIC_MATIC_PLUGIN_DIR . 'includes/feed_update.php');
            } else if ($curr_tab == 'log') {
                //Log
                $this->feeds_log($tabs, $url, $cid);
            } else if ($curr_tab == 'edit') {
                //Edit
                if (isset($_POST['id'])) {
                    $valid = $this->cf->campaign_edit_validate($_POST);
                    if ($valid === true) {
                        $result_id = $this->cf->campaign_edit_submit($_POST);
                        $result = __('Campaign') . ' [' . $result_id . '] ' . __('updated');
                        print "<div class=\"updated\"><p><strong>$result</strong></p></div>";
                    } else {
                        print "<div class=\"error\"><p><strong>$valid</strong></p></div>";
                    }
                }
                $authors = $this->cm->get_all_authors(1);
                $def_options = $this->cf->def_options;
                $campaign = $this->cf->get_campaign($cid);
                $update_interval = $this->cf->update_interval;
                include(CRITIC_MATIC_PLUGIN_DIR . 'includes/edit_feed.php');
            } else if ($curr_tab == 'trash') {
                // Trash
                if (isset($_POST['id'])) {
                    $valid = $this->cf->campaign_edit_validate($_POST);
                    if ($valid === true) {
                        $result_id = $this->cf->trash_campaign($_POST);

                        $active = isset($_POST['status']) ? $_POST['status'] : 0;
                        if ($active == 2) {
                            $result = __('Campaign') . ' [' . $result_id . '] ' . __('moved to trash');
                        } else {
                            $result = __('Campaign') . ' [' . $result_id . '] ' . __('untrashed');
                        }

                        print "<div class=\"updated\"><p><strong>$result</strong></p></div>";
                    } else {
                        print "<div class=\"error\"><p><strong>$valid</strong></p></div>";
                    }
                }
                $campaign = $this->cf->get_campaign($cid);

                include(CRITIC_MATIC_PLUGIN_DIR . 'includes/trash_feed.php');
            } else if ($curr_tab == 'rules') {
                // Campaign test rules
                if (isset($_POST['id'])) {
                    $valid = $this->cf->campaign_edit_validate($_POST);
                    if ($valid === true) {
                        $result_id = $this->cf->campaign_rules_test_submit($_POST);
                    } else {
                        print "<div class=\"error\"><p><strong>$valid</strong></p></div>";
                    }
                }


                $def_options = $this->cf->def_options;
                $campaign = $this->cf->get_campaign($cid);
                $options = unserialize($campaign->options);
                $test_post = $this->cf->get_feed_test_post($options);

                $use_global_rules = isset($options['use_global_rules']) ? $options['use_global_rules'] : $def_options['options']['use_global_rules'];

                if ($use_global_rules) {
                    $global_rules = isset($settings['rules']) ? $settings['rules'] : array();
                    $global_check = $this->cf->check_post($global_rules, $test_post, true);
                }
                $rules = isset($options['rules']) ? $options['rules'] : array();
                $check = $this->cf->check_post($rules, $test_post, true);

                include(CRITIC_MATIC_PLUGIN_DIR . 'includes/feed_rules.php');
            } else if ($curr_tab == 'preview') {
                // Campaign post page        
                $campaign = $this->cf->get_campaign($cid);
                $options = unserialize($campaign->options);
                $preview = $this->cf->preview($campaign);
                include(CRITIC_MATIC_PLUGIN_DIR . 'includes/feed_preview.php');
            }
            return;
        }

        //
        // Other pages
        //
        //Tabs
        $tabs_arr = $this->cf->tabs;
        $tabs = $this->get_tabs($url, $tabs_arr, $curr_tab);

        if (!$curr_tab) {
            $this->feeds_campaigns($tabs, $url, 0, $cid);
        } else if ($curr_tab == 'posts') {
            // Posts
            $this->feeds_posts($tabs, $url, 0, $cid);
        } else if ($curr_tab == 'update') {
            // Update
            $force = false;
            $count = $this->cf->process_all($force);
            include(CRITIC_MATIC_PLUGIN_DIR . 'includes/feed_update.php');
        } else if ($curr_tab == 'log') {
            // Log
            $this->feeds_log($tabs, $url);
        } else if ($curr_tab == 'settings') {
            // Settings
            if (isset($_POST['critic-feeds-nonce'])) {
                $valid = $this->nonce_validate($_POST);
                if ($valid === true) {
                    $this->cf->settings_submit($_POST);
                    $result = __('Updated');
                    print "<div class=\"updated\"><p><strong>$result</strong></p></div>";
                } else {
                    print "<div class=\"error\"><p><strong>$valid</strong></p></div>";
                }
            }
            include(CRITIC_MATIC_PLUGIN_DIR . 'includes/settings_feed.php');
        } else if ($curr_tab == 'rules') {
            // Rules test

            if (isset($_POST['critic-feeds-nonce'])) {
                $valid = $this->nonce_validate($_POST);
                if ($valid === true) {
                    $result_id = $this->cf->settings_rules_test_submit($_POST);
                } else {
                    print "<div class=\"error\"><p><strong>$valid</strong></p></div>";
                }
            }

            $settings = $this->cf->get_feed_settings();
            $test_post = $this->cf->get_feed_test_post($settings);
            $rules = isset($settings['rules']) ? $settings['rules'] : array();
            $check = $this->cf->check_post($rules, $test_post, true);
            include(CRITIC_MATIC_PLUGIN_DIR . 'includes/feed_rules_global.php');
        } else if ($curr_tab == 'add') {
            // Add
            if (isset($_POST['feed'])) {
                $valid = $this->cf->campaign_edit_validate($_POST);
                if ($valid === true) {
                    $result_id = $this->cf->campaign_edit_submit($_POST);
                    $result = __('Campaign added. Go to the campaign: ') . '<a href="' . $url . '&cid=' . $result_id . '">' . $result_id . '</a>';
                    print "<div class=\"updated\"><p><strong>$result</strong></p></div>";
                } else {
                    print "<div class=\"error\"><p><strong>$valid</strong></p></div>";
                }
            }
            $authors = $this->cm->get_all_authors(1);
            $def_options = $this->cf->def_options;
            $update_interval = $this->cf->update_interval;
            include(CRITIC_MATIC_PLUGIN_DIR . 'includes/add_feed.php');
        }
    }

    /*
     * The parser page
     */

    public function parser() {


        $cm = $this->cm;

        $curr_tab = $this->get_tab();
        $cid = isset($_GET['cid']) ? (int) $_GET['cid'] : '';
        $uid = isset($_GET['uid']) ? (int) $_GET['uid'] : '';
        // $page = $this->get_page();
        // $per_page = $this->get_perpage();
        //Bulk actions
        $this->bulk_submit();

        //Sort
        $sort_pages = $cm->sort_pages;
        $orderby = $this->get_orderby($sort_pages);
        $order = $this->get_order();

        $url = $this->admin_page . $this->parser_url;

        /*
         * Campaign page
         */
        if ($cid) {
            //Tabs
            $append = '&cid=' . $cid;
            $campaign = $this->cp->get_campaign($cid);
            $tabs_arr = $this->cp->campaign_tabs;
            $tabs = $this->get_tabs($url, $tabs_arr, $curr_tab, $append);
            $settings = $this->cp->get_parser_settings();

            if (!$curr_tab) {
                $curr_tab = 'home';
            }
            if ($curr_tab == 'home') {
                // Campaign view page  
                $update_interval = $this->cp->update_interval;
                $parser_state = $this->cp->camp_state;

                if ($campaign) {
                    $author = $cm->get_author($campaign->author, true);
                    if ($_GET['export']) {
                        $urls = $this->cp->get_all_urls($cid);
                        print '<h2>Export campaign URLs</h2>';
                        if ($urls) {
                            $items = array();
                            foreach ($urls as $url) {
                                $items[] = $url->link;
                            }
                            print '<textarea style="width:90%; height:500px">' . implode("\n", $items) . '</textarea>';
                        }
                        exit;
                    } else if ($_GET['find_urls']) {
                        print '<h2>Find campaign URLs</h2>';
                        $preivew_data = $this->cp->find_urls($campaign, false);
                        if ($preivew_data['urls']) {
                            print '<textarea style="width: 90%; height: 500px;">' . implode("\n", $preivew_data['urls']) . '</textarea>';
                        }
                        exit;
                    } else if ($_GET['find_urls_yt']) {
                        print '<h2>Find YouTuebe URLs</h2>';
                        $find = new CPFind($this->cp);
                        $preivew_data = $find->find_all_urls_yt($campaign, false);

                        if ($preivew_data) {
                            print '<p>Total found:' . $preivew_data['found'] . '</p>';
                            print '<p>Total add:' . $preivew_data['add'] . '</p>';
                        }
                        exit;
                    } else if ($_GET['export_parser_rules']) {
                        $options = $this->cp->get_options($campaign);
                        $parser_rules = $options['parser_rules'];
                        $json = json_encode($parser_rules);
                        print '<h2>Export campaign parser row rules</h2>';
                        print '<textarea style="width:90%; height:500px">' . $json . '</textarea>';

                        exit;
                    }
                    include(CRITIC_MATIC_PLUGIN_DIR . 'includes/view_parser.php');
                }
            } else if ($curr_tab == 'urls') {
                // Campaign post page                  
                $this->parser_urls($tabs, $url, $cid);
            } else if ($curr_tab == 'find') {
                // Find urls                                                 

                if (isset($_POST['id'])) {
                    $valid = $this->cp->campaign_edit_validate($_POST);
                    if ($valid === true) {
                        $this->cp->campaign_find_urls_submit($_POST);
                        $result = __('Campaign') . ' [' . $_POST['id'] . '] ' . __('updated');
                        print "<div class=\"updated\"><p><strong>$result</strong></p></div>";
                    } else {
                        print "<div class=\"error\"><p><strong>$valid</strong></p></div>";
                    }
                }
                $campaign = $this->cp->get_campaign($cid);
                $options = $this->cp->get_options($campaign);

                $yt_preivew = array();
                if (isset($_POST['yt_preview'])) {
                    $find = new CPFind($this->cp);
                    $yt_preivew = $find->find_all_urls_yt($campaign, true);
                }

                $preivew_data = array();
                if (isset($_POST['preview'])) {
                    $preivew_data = $this->cp->find_urls($campaign, true);
                }

                if (isset($_POST['cron_preview'])) {
                    $cron = new CPCron($this->cp);
                    $cron_preivew_data = $cron->cron_urls($campaign, true);
                }
                include(CRITIC_MATIC_PLUGIN_DIR . 'includes/parser_find_urls.php');
            } else if ($curr_tab == 'arhive') {
                // Create arhive
                if (isset($_POST['id'])) {
                    $valid = $this->cp->campaign_edit_validate($_POST);
                    if ($valid === true) {
                        $result_id = $this->cp->arhive_edit_submit($_POST);
                        $result = __('Campaign') . ' [' . $result_id . '] ' . __('updated');
                        print "<div class=\"updated\"><p><strong>$result</strong></p></div>";
                    } else {
                        print "<div class=\"error\"><p><strong>$valid</strong></p></div>";
                    }
                }
                $preivew_data = array();
                if (isset($_POST['arhive_preview'])) {
                    $campaign = $this->cp->get_campaign($cid);
                    $valid = $this->cp->campaign_edit_validate($_POST);
                    if ($valid) {
                        $posturl = $_POST['url'];
                        if ($posturl) {
                            $preivew_data = $this->cp->preview_arhive($posturl, $campaign);
                        }
                    }
                }

                $campaign = $this->cp->get_campaign($cid);
                include(CRITIC_MATIC_PLUGIN_DIR . 'includes/edit_arhive.php');
            } else if ($curr_tab == 'update') {
                // Update
                $campaign = $this->cp->get_campaign($cid);
                $options = $this->cp->get_options($campaign);
                global $db_debug;
                $db_debug=1;
                ob_start();

                //Update URLs
                $type_name = 'cron_urls';
                if ($campaign->type == 1) {
                    $type_name = 'yt_urls';
                }
                $type_opt = $options[$type_name];
                $active_find = $type_opt['status'];

                $cron = new CPCron($this->cp);
                $count_urls = -1;
                if ($active_find == 1) {
                    $count_urls = $cron->process_campaign($campaign, 'cron_urls', true, true);
                }

                // Arhive
                $active_arhive = $options['arhive']['status'];
                $count_arhive = -1;
                if ($active_arhive == 1) {
                    $count_arhive = $cron->process_campaign($campaign, 'arhive', true, true);
                }

                // Parser
                $active = $campaign->parser_status;
                $count = -1;
                if ($active == 1) {
                    $count = $cron->process_campaign($campaign, 'parsing', true, true);
                }

                $debug_content = ob_get_contents();
                ob_end_clean();

                include(CRITIC_MATIC_PLUGIN_DIR . 'includes/parser_update.php');
            } else if ($curr_tab == 'log') {
                //Log
                $this->parser_log($tabs, $url, $cid);
            } else if ($curr_tab == 'edit') {
                //Edit
                if (isset($_POST['id'])) {
                    $valid = $this->cp->campaign_edit_validate($_POST);
                    if ($valid === true) {
                        $result_id = $this->cp->campaign_edit_submit($_POST);
                        $result = __('Campaign') . ' [' . $result_id . '] ' . __('updated');
                        print "<div class=\"updated\"><p><strong>$result</strong></p></div>";
                    } else {
                        print "<div class=\"error\"><p><strong>$valid</strong></p></div>";
                    }
                }
                $authors = $cm->get_all_authors(1);
                $def_options = $this->cp->def_options;
                $campaign = $this->cp->get_campaign($cid);
                $options = $this->cp->get_options($campaign);
                $update_interval = $this->cp->update_interval;
                include(CRITIC_MATIC_PLUGIN_DIR . 'includes/edit_parser.php');
            } else if ($curr_tab == 'trash') {
                // Trash
                if (isset($_POST['id'])) {
                    $valid = $this->cp->campaign_edit_validate($_POST);
                    if ($valid === true) {
                        $result_id = $this->cp->trash_campaign($_POST);

                        $active = isset($_POST['status']) ? $_POST['status'] : 0;
                        if ($active == 2) {
                            $result = __('Campaign') . ' [' . $result_id . '] ' . __('moved to trash');
                        } else {
                            $result = __('Campaign') . ' [' . $result_id . '] ' . __('untrashed');
                        }

                        print "<div class=\"updated\"><p><strong>$result</strong></p></div>";
                    } else {
                        print "<div class=\"error\"><p><strong>$valid</strong></p></div>";
                    }
                }
                $campaign = $this->cp->get_campaign($cid);
                include(CRITIC_MATIC_PLUGIN_DIR . 'includes/trash_parser.php');
            } else if ($curr_tab == 'preview') {
                // Campaign preveiw post

                $campaign = $this->cp->get_campaign($cid);
                $options = $this->cp->get_options($campaign);

                $urls = array();

                if (isset($_GET['uid'])) {
                    $urls[] = $this->cp->get_url((int) $_GET['uid']);
                } else {
                    // $urls_count, $status, $campaign->id, $random_urls, $debug, $custom_url_id, $arhive_date)
                    $urls = $this->cp->get_last_urls($options['pr_num'], -1, $cid, 0, false, 0, 1);
                }

                $preview = '';
                if ($urls) {
                    $preview = $this->cp->preview_parser($campaign, $urls);
                }


                include(CRITIC_MATIC_PLUGIN_DIR . 'includes/parser_preview.php');
            }
            return;
        } else if ($uid) {
            $url_data = $this->cp->get_url($uid);
            include(CRITIC_MATIC_PLUGIN_DIR . 'includes/view_url.php');
            return;
        }

        //
        // Other pages
        //
        //Tabs
        $tabs_arr = $this->cp->tabs;
        $tabs = $this->get_tabs($url, $tabs_arr, $curr_tab);

        $clear_logs = isset($_GET['clear_logs']) ? 1 : 0;
        if ($clear_logs) {
            $this->cp->clear_all_logs();
        }

        if (!$curr_tab) {
            $this->parser_campaingns($tabs, $url);
        } else if ($curr_tab == 'urls') {
            // Posts
            $this->parser_urls($tabs, $url, 0);
        } else if ($curr_tab == 'update') {
            // Update
            $cron = new CPCron($this->cp);
            $count = $cron->proccess_all();
            include(CRITIC_MATIC_PLUGIN_DIR . 'includes/parser_update.php');
        } else if ($curr_tab == 'log') {
            // Log
            $this->parser_log($tabs, $url);
        } else if ($curr_tab == 'settings') {
            // Settings
            if (isset($_POST['critic-feeds-nonce'])) {
                $valid = $this->nonce_validate($_POST);
                if ($valid === true) {
                    $this->cp->settings_submit($_POST);
                    $result = __('Updated');
                    print "<div class=\"updated\"><p><strong>$result</strong></p></div>";
                } else {
                    print "<div class=\"error\"><p><strong>$valid</strong></p></div>";
                }
            }
            include(CRITIC_MATIC_PLUGIN_DIR . 'includes/settings_parser.php');
        } else if ($curr_tab == 'rules') {
            // Rules test
            if (isset($_POST['critic-feeds-nonce'])) {
                $valid = $this->nonce_validate($_POST);
                if ($valid === true) {
                    $result_id = $this->cp->settings_rules_test_submit($_POST);
                } else {
                    print "<div class=\"error\"><p><strong>$valid</strong></p></div>";
                }
            }

            $settings = $this->cp->get_parser_settings();
            $test_post = $this->cp->get_parser_test_post($settings);
            $rules = isset($settings['rules']) ? $settings['rules'] : array();
            $cprules = $this->cp->get_cprules();
            $check = $cprules->check_post($rules, $test_post, true);
            include(CRITIC_MATIC_PLUGIN_DIR . 'includes/parser_rules_global.php');
        } else if ($curr_tab == 'add') {
            // Add
            if (isset($_POST['site'])) {
                $valid = $this->cp->campaign_edit_validate($_POST);
                if ($valid === true) {
                    $result_id = $this->cp->campaign_edit_submit($_POST);
                    $result = __('Campaign added. Go to the campaign: ') . '<a href="' . $url . '&cid=' . $result_id . '">' . $result_id . '</a>';
                    print "<div class=\"updated\"><p><strong>$result</strong></p></div>";
                } else {
                    print "<div class=\"error\"><p><strong>$valid</strong></p></div>";
                }
            }
            $authors = $cm->get_all_authors(1);
            $def_options = $this->cp->def_options;
            $update_interval = $this->cp->update_interval;
            include(CRITIC_MATIC_PLUGIN_DIR . 'includes/add_parser.php');
        }
    }

    /*
     * The settings page
     */

    public function settings() {
        $curr_tab = $this->get_tab();
        $page = $this->get_page();
        $per_page = $this->get_perpage();
        $url = $this->admin_page . $this->settings_url;

        //Sort
        $sort_pages = $this->cm->sort_pages;
        $orderby = $this->get_orderby($sort_pages);
        $order = $this->get_order();

        if (!$curr_tab) {
            $curr_tab = 'search';
        }

        $tabs_arr = $this->settings_tabs;
        $tabs = $this->get_tabs($url, $tabs_arr, $curr_tab);

        if ($curr_tab == 'search') {

            if (isset($_POST['critic-feeds-nonce'])) {
                $valid = $this->nonce_validate($_POST);
                if ($valid === true) {
                    $this->cs->update_search_settings($_POST);
                    $result = __('Updated');
                    print "<div class=\"updated\"><p><strong>$result</strong></p></div>";
                } else {
                    print "<div class=\"error\"><p><strong>$valid</strong></p></div>";
                }
            }
            $ss = $this->cs->get_search_settings();

            include(CRITIC_MATIC_PLUGIN_DIR . 'includes/settings_search.php');
        } else if ($curr_tab == 'parser') {

            if (isset($_POST['critic-feeds-nonce'])) {
                $valid = $this->nonce_validate($_POST);
                if ($valid === true) {
                    $this->cm->update_settings($_POST);
                    $result = __('Updated');
                    print "<div class=\"updated\"><p><strong>$result</strong></p></div>";
                } else {
                    print "<div class=\"error\"><p><strong>$valid</strong></p></div>";
                }
            }
            $ss = $this->cm->get_settings(false);

            include(CRITIC_MATIC_PLUGIN_DIR . 'includes/settings_parser.php');
        } else if ($curr_tab == 'audience') {

            if (isset($_POST['critic-feeds-nonce'])) {
                $valid = $this->nonce_validate($_POST);
                if ($valid === true) {
                    $this->cm->update_settings($_POST);
                    $result = __('Updated');
                    print "<div class=\"updated\"><p><strong>$result</strong></p></div>";
                } else {
                    print "<div class=\"error\"><p><strong>$valid</strong></p></div>";
                }
            }
            $ss = $this->cm->get_settings(false);

            include(CRITIC_MATIC_PLUGIN_DIR . 'includes/settings_audience.php');
        } else if ($curr_tab == 'score') {

            if (isset($_POST['critic-feeds-nonce'])) {
                $valid = $this->nonce_validate($_POST);
                if ($valid === true) {
                    $this->cm->update_settings($_POST);
                    $result = __('Updated');
                    print "<div class=\"updated\"><p><strong>$result</strong></p></div>";
                } else {
                    print "<div class=\"error\"><p><strong>$valid</strong></p></div>";
                }
            }
            $ss = $this->cm->get_settings(false);

            include(CRITIC_MATIC_PLUGIN_DIR . 'includes/settings_score.php');
        } else if ($curr_tab == 'actors') {

            if (isset($_POST['critic-feeds-nonce'])) {
                $valid = $this->nonce_validate($_POST);
                if ($valid === true) {
                    $this->cm->update_settings($_POST);
                    $result = __('Updated');
                    print "<div class=\"updated\"><p><strong>$result</strong></p></div>";
                } else {
                    print "<div class=\"error\"><p><strong>$valid</strong></p></div>";
                }
            }
            if (!class_exists('MoviesActorWeight')) {
                require_once( CRITIC_MATIC_PLUGIN_DIR . 'MoviesActorWeight.php' );
            }
            $maw = new MoviesActorWeight($this->cm);

            $ss = $this->cm->get_settings(false);

            include(CRITIC_MATIC_PLUGIN_DIR . 'includes/settings_actors.php');
        } else if ($curr_tab == 'analytics') {
            if (isset($_POST['critic-feeds-nonce'])) {
                $valid = $this->nonce_validate($_POST);
                if ($valid === true) {
                    $this->cm->update_settings($_POST);
                    $result = __('Updated');
                    print "<div class=\"updated\"><p><strong>$result</strong></p></div>";
                } else {
                    print "<div class=\"error\"><p><strong>$valid</strong></p></div>";
                }
            }

            $ss = $this->cm->get_settings(false);
            include(CRITIC_MATIC_PLUGIN_DIR . 'includes/settings_analytics.php');
        } else if ($curr_tab == 'posts') {

            if (isset($_POST['critic-feeds-nonce'])) {
                $valid = $this->nonce_validate($_POST);
                if ($valid === true) {
                    $this->cm->update_settings($_POST);
                    $result = __('Updated');
                    print "<div class=\"updated\"><p><strong>$result</strong></p></div>";
                } else {
                    print "<div class=\"error\"><p><strong>$valid</strong></p></div>";
                }
            }
            $ss = $this->cm->get_settings(false);

            include(CRITIC_MATIC_PLUGIN_DIR . 'includes/settings_posts.php');
        } else if ($curr_tab == 'cache') {

            if (isset($_POST['critic-feeds-nonce'])) {
                $valid = $this->nonce_validate($_POST);
                if ($valid === true) {
                    $this->cm->update_settings($_POST);
                    $result = __('Updated');
                    print "<div class=\"updated\"><p><strong>$result</strong></p></div>";
                } else {
                    print "<div class=\"error\"><p><strong>$valid</strong></p></div>";
                }
            }
            $ss = $this->cm->get_settings(false);

            include(CRITIC_MATIC_PLUGIN_DIR . 'includes/settings_cache.php');
        } else if ($curr_tab == 'sync') {

            if (isset($_POST['critic-feeds-nonce'])) {
                $valid = $this->nonce_validate($_POST);
                if ($valid === true) {
                    $this->cm->update_settings($_POST);
                    $result = __('Updated');
                    print "<div class=\"updated\"><p><strong>$result</strong></p></div>";
                } else {
                    print "<div class=\"error\"><p><strong>$valid</strong></p></div>";
                }
            }
            $ss = $this->cm->get_settings(false);

            include(CRITIC_MATIC_PLUGIN_DIR . 'includes/settings_sync.php');
        }
    }

    /*
     * Feeds campaigns list
     */

    public function feeds_campaigns($tabs = '', $url = '', $cid = 0, $aid = 0) {
        $page = $this->get_page();
        $per_page = $this->get_perpage();
        $page_url = $url;

        // Bulk actions
        $this->bulk_submit();

        //Sort
        $sort_pages = $this->cm->sort_pages;
        $orderby = $this->get_orderby($sort_pages);
        $order = $this->get_order();

        // Author id
        $author = '';
        if ($aid) {
            $author = $this->cm->get_author($aid);
            $page_url .= '&aid=' . $aid;
        }

        // Filter by status
        $home_status = -1;
        $status = isset($_GET['status']) ? (int) $_GET['status'] : $home_status;
        $filter_arr = $this->cf->feed_states($aid);
        $filters = $this->get_filters($filter_arr, $page_url, $status);
        if ($status != $home_status) {
            $page_url = $page_url . '&status=' . $status;
        }

        //Pager
        $count = isset($filter_arr[$status]['count']) ? $filter_arr[$status]['count'] : 0;

        $pager = $this->themePager($page, $page_url, $count, $per_page, $orderby, $order);

        $feeds = $this->cf->get_feeds($status, $page, $aid, $orderby, $order);
        if ($aid) {
            include(CRITIC_MATIC_PLUGIN_DIR . 'includes/list_feeds_author.php');
        } else {
            include(CRITIC_MATIC_PLUGIN_DIR . 'includes/list_feeds_critic.php');
        }
    }

    /*
     * Feed posts page
     */

    public function feeds_posts($tabs = '', $url = '', $cid = 0, $aid = 0) {
        $page = $this->get_page();
        $per_page = $this->get_perpage();

        $page_url = $url;
        $page_url .= '&tab=posts';

        //Bulk actions
        $this->bulk_submit();

        //Sort
        $sort_pages = $this->cm->sort_pages;
        $orderby = $this->get_orderby($sort_pages);
        $order = $this->get_order();

        $query_adb = new QueryADB();

        // Campaign id
        $campaign = '';
        if ($cid) {
            $campaign = $this->cf->get_campaign($cid);
            $query_adb->add_query('cid', $cid);
            $page_url .= '&cid=' . $cid;
        }

        // Author id
        $author = '';
        if ($aid) {
            $author = $this->cm->get_author($aid);
            $query_adb->add_query('aid', $aid);
            $page_url .= '&aid=' . $aid;
        }

        $filters = array(
            'post_update' => $this->cm->post_update,
            'post_date' => $this->cm->post_update,
            'type' => $this->cm->post_type,
            'view_type' => $this->cm->post_view_type,
            'author_type' => $this->cm->author_type,
            'meta_type' => $this->cm->post_meta_status,
            'status' => $this->cm->post_status
        );

        $filters_tabs = $this->get_filters_tabs($filters, $page_url, $query_adb);
        $query_adb = $filters_tabs['query_adb'];
        $query = $query_adb->get_query();
        $page_url = $filters_tabs['p'];
        $count = $filters_tabs['c'];

        $per_page = $this->cm->perpage;
        $pager = $this->themePager($page, $page_url, $count, $per_page, $orderby, $order);
        $posts = $this->cm->get_posts($query, $page, $per_page, $orderby, $order, false, false);

        if ($aid) {
            include(CRITIC_MATIC_PLUGIN_DIR . 'includes/list_posts_author.php');
        } else {
            include(CRITIC_MATIC_PLUGIN_DIR . 'includes/list_posts_feed.php');
        }
    }

    /*
     * Parser
     */

    public function parser_campaingns($tabs = '', $url = '', $aid = 0) {

        $page = $this->get_page();
        $per_page = $this->get_perpage();

        //Sort
        $sort_pages = $this->cm->sort_pages;
        $orderby = $this->get_orderby($sort_pages);
        $order = $this->get_order();

        $page_url = $url;

        // Author id
        $author = '';
        if ($aid) {
            $author = $this->cm->get_author($aid);
            $page_url .= '&aid=' . $aid;
        }

        // Filter by campaign type
        $home_type = -1;
        $type = isset($_GET['type']) ? (int) $_GET['type'] : $home_type;
        $filter_status_arr = $this->cp->parser_types($aid);
        $type_filters = $this->get_filters($filter_status_arr, $page_url, $type, '', 'type');
        if ($type != $home_type) {
            $page_url = $page_url . '&type=' . $type;
        }

        // Filter by status
        $home_status = -1;
        $status = isset($_GET['status']) ? (int) $_GET['status'] : $home_status;
        $filter_arr = $this->cp->parser_states($aid, $type);
        $filters = $this->get_filters($filter_arr, $page_url, $status);
        if ($status != $home_status) {
            $page_url = $page_url . '&status=' . $status;
        }

        // Filter by parser status
        $home_parser_status = -1;
        $parser_status = isset($_GET['parser_status']) ? (int) $_GET['parser_status'] : $home_parser_status;
        $filter_status_arr = $this->cp->parser_parser_states($aid, $type, $status);
        //$filter_arr = array(), $url = '', $curr_tab = -1, $front_slug = '', $name = 'status', $class = '', $show_name = true
        $parser_status_filters = $this->get_filters($filter_status_arr, $page_url, $parser_status, '', 'parser_status');
        if ($parser_status != $home_parser_status) {
            $page_url = $page_url . '&parser_status=' . $parser_status;
        }

        //Pager
        $count = isset($filter_arr[$status]['count']) ? $filter_arr[$status]['count'] : 0;
        $pager = $this->themePager($page, $page_url, $count, $per_page, $orderby, $order);
        $campaigns = $this->cp->get_parsers($type, $status, $page, $aid, $parser_status, $orderby, $order);

        include(CRITIC_MATIC_PLUGIN_DIR . 'includes/list_parsers_critic.php');
    }

    public function parser_urls($tabs = '', $url = '', $cid = 0, $aid = 0) {
        $page = $this->get_page();
        $per_page = $this->get_perpage();

        $page_url = $url;
        $page_url .= '&tab=urls';

        //Bulk actions
        $this->bulk_parser_submit();

        //Sort
        $sort_pages = $this->cm->sort_pages;
        $orderby = $this->get_orderby($sort_pages);
        $order = $this->get_order();

        // Campaign id
        $campaign = '';
        if ($cid) {
            $campaign = $this->cp->get_campaign($cid);
            $page_url .= '&cid=' . $cid;
        }

        // Filter by status
        $home_status = -1;
        $status = isset($_GET['status']) ? (int) $_GET['status'] : $home_status;
        $filter_arr = $this->cp->get_url_status_count($cid);
        $filters = $this->get_filters($filter_arr, $page_url, $status, '', 'status');
        if ($status != $home_status) {
            $page_url = $page_url . '&status=' . $status;
        }
        $count = isset($filter_arr[$status]['count']) ? $filter_arr[$status]['count'] : 0;

        // Filter by post meta
        $home_meta_type = -1;
        $meta_type = isset($_GET['meta_type']) ? (int) $_GET['meta_type'] : $home_meta_type;
        $filter_meta_type_arr = $this->cp->get_post_meta_types($cid, $status);
        $filters_meta_type = $this->get_filters($filter_meta_type_arr, $page_url, $meta_type, '', 'meta_type');
        if ($meta_type != $home_meta_type) {
            $page_url = $page_url . '&meta_type=' . $meta_type;
            $count = isset($filter_meta_type_arr[$meta_type]['count']) ? $filter_meta_type_arr[$meta_type]['count'] : 0;
        }

        $pager = $this->themePager($page, $page_url, $count, $per_page, $orderby, $order);
        $posts = $this->cp->get_urls($status, $page, $cid, $meta_type, $orderby, $order);

        include(CRITIC_MATIC_PLUGIN_DIR . 'includes/list_urls.php');
    }

    /*
     * Log page
     */

    public function feeds_log($tabs = '', $url = '', $cid = 0) {
        $page = $this->get_page();
        $per_page = $this->get_perpage();

        $page_url = $url;
        $page_url .= '&tab=log';

        //Sort
        $sort_pages = $this->cm->sort_pages;
        $orderby = $this->get_orderby($sort_pages);
        $order = $this->get_order();

        // Campaign id
        $campaign = '';
        if ($cid) {
            $campaign = $this->cf->get_campaign($cid);
            $page_url .= '&cid=' . $cid;
        }

        $count = $this->cf->get_log_count($cid);

        $log = $this->cf->get_log($page, $cid);
        $status = -1;
        $pager = $this->themePager($page, $page_url, $count, $per_page, $orderby, $order);

        include(CRITIC_MATIC_PLUGIN_DIR . 'includes/feed_log.php');
    }

    public function sitemap() {
        //Sitemap page
        include(CRITIC_MATIC_PLUGIN_DIR . 'includes/sitemap.php');
    }

    /*
     * Meta log page
     */

    public function meta_log($tabs = '', $url = '') {
        $page = $this->get_page();
        $per_page = $this->get_perpage();

        //Sort
        $sort_pages = $this->cm->sort_pages;
        $orderby = $this->get_orderby($sort_pages);
        $order = $this->get_order();

        $page_url = $url;
        $page_url .= '&tab=log';

        // Filter by status
        $home_log_status = -1;
        $status = isset($_GET['status']) ? (int) $_GET['status'] : $home_log_status;
        $filter_log_status_arr = $this->cs->get_count_log_status();
        $filters_log_status = $this->get_filters($filter_log_status_arr, $page_url, $status, '', 'status');

        $page_url = $page_url . '&log_status=' . $status;
        $count = isset($filter_log_status_arr[$status]['count']) ? $filter_log_status_arr[$status]['count'] : 0;

        // Log type
        $home_type = -1;
        $type = isset($_GET['type']) ? (int) $_GET['type'] : $home_type;
        $filter_type_arr = $this->cs->get_count_log_type($status);
        $filters_type = $this->get_filters($filter_type_arr, $page_url, $type, '', 'type');
        if ($type != $home_type) {
            $page_url = $page_url . '&type=' . $type;
            $count = isset($filter_type_arr[$type]['count']) ? $filter_type_arr[$type]['count'] : 0;
        }

        $log = $this->cs->get_log($page, 0, 0, $per_page, $status, $type);

        $pager = $this->themePager($page, $page_url, $count, $per_page, $orderby, $order);
        include(CRITIC_MATIC_PLUGIN_DIR . 'includes/list_log_meta.php');
    }

    public function parser_log($tabs = '', $url = '', $cid = 0) {
        $page = $this->get_page();
        $per_page = $this->get_perpage();

        //Sort
        $sort_pages = $this->cm->sort_pages;
        $orderby = $this->get_orderby($sort_pages);
        $order = $this->get_order();

        $page_url = $url;
        $page_url .= '&tab=log';

        if ($cid) {
            $page_url .= '&cid=' . $cid;
        }

        // Filter by status
        $home_log_status = -1;
        $status = isset($_GET['status']) ? (int) $_GET['status'] : $home_log_status;
        $filter_log_status_arr = $this->cp->get_post_log_status($cid);
        $filters_log_status = $this->get_filters($filter_log_status_arr, $page_url, $status, '', 'status');

        $page_url = $page_url . '&log_status=' . $status;
        $count = isset($filter_log_status_arr[$status]['count']) ? $filter_log_status_arr[$status]['count'] : 0;

        // Log type
        $home_type = -1;
        $type = isset($_GET['type']) ? (int) $_GET['type'] : $home_type;
        $filter_type_arr = $this->cp->get_post_log_types($cid, $status);
        $filters_type = $this->get_filters($filter_type_arr, $page_url, $type, '', 'type');
        if ($type != $home_type) {
            $page_url = $page_url . '&type=' . $type;
            $count = isset($filter_type_arr[$type]['count']) ? $filter_type_arr[$type]['count'] : 0;
        }

        $log = $this->cp->get_log($page, $cid, 0, $status, $type, $per_page);
        $pager = $this->themePager($page, $page_url, $count, $per_page, $orderby, $order);
        include(CRITIC_MATIC_PLUGIN_DIR . 'includes/list_log_parser.php');
    }

    public function themePager($page = 1, $url = '/', $count = 1, $per_page = 100, $orderby = '', $order = '', $pg = 'p', $active_class = 'disabled') {
        $ret = '';
        $pager = $this->getPager($page, $url, $count, $per_page, $orderby, $order);
        if ($pager) {
            $ret = '<div class="tablenav cmnav"><div class="tablenav-pages" style="float:none;"><div class="pagination-links">' . $pager . '</div></div></div>';
        }
        return $ret;
    }

    public function getPager($page = 1, $url = '/', $count = 1, $per_page = 100, $orderby = '', $order = '', $pg = 'p', $active_class = 'disabled') {
        $paged = $page;
        $max_page = 1;
        if ($per_page > 0) {
            $max_page = ceil($count / $per_page);
        }
        $pages_to_show = 10;
        $pages_to_show_minus_1 = $pages_to_show - 1;
        $half_page_start = floor($pages_to_show_minus_1 / 2);
        $half_page_end = ceil($pages_to_show_minus_1 / 2);
        $start_page = $paged - $half_page_start;
        if ($start_page <= 0) {
            $start_page = 1;
        }
        $end_page = $paged + $half_page_end;
        if (($end_page - $start_page) != $pages_to_show_minus_1) {
            $end_page = $start_page + $pages_to_show_minus_1;
        }
        if ($end_page > $max_page) {
            $start_page = $max_page - $pages_to_show_minus_1;
            $end_page = $max_page;
        }
        if ($start_page <= 0) {
            $start_page = 1;
        }

        $ret = '';

        $first_page_text = '«';
        $last_page_text = '»';

        //Sort
        $orderby_link = '';
        $order_link = '';
        if ($orderby) {
            $orderby_link = '&orderby=' . $orderby;
            $order_link = '&order=' . $order;
        }

        $per_page_link = '&perpage=' . $per_page;

        if ($max_page > 1) {

            if ($start_page >= 2 && $pages_to_show < $max_page) {
                $ret .= '<a class="tab button" href="' . $url . '&' . $pg . '=1' . $orderby_link . $order_link . $per_page_link . '" title="' . $first_page_text . '"><span>' . $first_page_text . '</span></a>';
            }

            for ($i = $start_page; $i <= $end_page; $i++) {
                $active = '';
                if ($i == $paged) {
                    $active = $active_class;
                }
                $page_text = $i;
                $ret .= '<a class="tab button ' . $active . '" href="' . $url . '&' . $pg . '=' . $i . $orderby_link . $order_link . $per_page_link . '" title="' . $page_text . '"><span>' . $page_text . '</span></a>';
            }
            //next_posts_link($pagenavi_options['next_text'], $max_page);
            if ($end_page < $max_page) {

                $ret .= '<a class="tab button" href="' . $url . '&' . $pg . '=' . $max_page . $orderby_link . $order_link . $per_page_link . '" title="' . $last_page_text . '"><span>' . $last_page_text . '</span></a>';
            }
        }

        //Per page
        if ($count > $this->per_pages[0]) {
            $ret .= ' Per page: ';
            foreach ($this->per_pages as $pp) {

                $pp_active = '';
                if ($pp == $per_page) {
                    $pp_active = $active_class;
                }

                $ret .= '<a class="tab button ' . $pp_active . '" href="' . $url . '&perpage=' . $pp . $orderby_link . $order_link . '"><span>' . $pp . '</span></a>';

                if ($count < $pp) {
                    break;
                }
            }
        }
        return $ret;
    }

    /*
     * Page tabs
     */

    public function get_tabs($url = '', $tabs = array(), $curr_tab = '', $append = '') {
        $ret = '';
        if (sizeof($tabs)) {
            $ret = '<h3 class="nav-tab-wrapper cm-nav">';
            foreach ($tabs as $slug => $title) {
                $tab_active = false;
                if ($slug == 'home') {
                    $slug = '';
                    if ($curr_tab == '') {
                        $tab_active = true;
                    }
                } else {
                    if ($slug == $curr_tab) {
                        $tab_active = true;
                    }
                    $slug = '&tab=' . $slug;
                }
                $tab_class = 'nav-tab';
                if ($tab_active) {
                    $tab_class .= ' nav-tab-active';
                }

                $ret .= '<a href="' . $url . $slug . $append . '" class="' . $tab_class . '">' . $title . '</a>';
            }
            $ret .= '</h3>';
        }
        return $ret;
    }

    public function sorted_head($slug = '', $title = '', $orderby = '', $order = 'asc', $page_url = '') {
        $ret = '';
        if ($slug) {
            $sortable = 'sortable';
            $orderby_link = '';
            $order_link = '';
            if ($slug == $orderby) {
                $sortable = 'sorted';
                $orderby_link = '&orderby=' . $slug;
                $next_order = $order == 'desc' ? 'asc' : 'desc';
                $order_link = '&order=' . $next_order;
            } else {
                $orderby_link = '&orderby=' . $slug;
                $order_link = '&order=' . $order;
            }
            ?>
            <th scope="col" id="<?php print $slug ?>" class="manage-column column-<?php print $slug ?> <?php print $sortable ?> <?php print $order ?>">
                <a href="<?php print $page_url . $orderby_link . $order_link ?>">
                    <span><?php print $title ?></span><span class="sorting-indicator"></span>
                </a>
            </th>
        <?php } else { ?>
            <th><?php print $title ?></th>
            <?php
        }
    }

    /*
     * Page filters
     */

    public function get_filters($filter_arr = array(), $url = '', $curr_tab = -1, $front_slug = '', $name = 'status', $class = '', $show_name = true) {
        $ret = array();
        if (sizeof($filter_arr)) {
            foreach ($filter_arr as $slug => $value) {
                $title = $value['title'];
                $count = isset($value['count']) ? $value['count'] : false;
                $tab_active = false;

                if ($slug === $front_slug) {
                    $slug = '';
                    if ($curr_tab == '') {
                        $tab_active = true;
                    }
                } else {
                    if ($slug == $curr_tab) {
                        $tab_active = true;
                    }
                    $slug = '&' . $name . '=' . $slug;
                }
                $tab_class = '';
                if ($tab_active) {
                    $tab_class .= 'current';
                }

                $str = '<li><a href="' . $url . $slug . '" class="' . $tab_class . '">';
                $str .= $title;

                if ($count !== false) {
                    $str .= ' <span class="count">(' . $count . ')</span>';
                }
                $str .= '</a></li>';

                $ret[] = $str;
            }
        }
        if ($class) {
            $class = ' ' . $class;
        }
        $first = '';
        if ($show_name) {
            $first = '<li>' . ucfirst(str_replace('_', ' ', $name)) . ': </li>';
        }
        return '<ul class="cm-filters subsubsub' . $class . '">' . $first . implode(' | ', $ret) . '</ul>';
    }

    public function get_filters_tabs($filters = array(), $p = '', $query_adb = '', $c_type = 'post', $all = true) {
        if (!$query_adb) {
            $query_adb = new QueryADB();
        }
        $count = 0;
        $filters_tabs = array();

        if ($filters) {
            foreach ($filters as $key => $value) {
                $home_type = isset($value['home_type']) ? $value['home_type'] : -1;

                $type_list = $value;
                $type_list = isset($value['type_list']) ? $value['type_list'] : $value;

                $type = isset($_GET[$key]) ? (int) $_GET[$key] : $home_type;

                # Custom query types
                if ($c_type == 'author') {
                    $filter_type_arr = $this->cm->get_author_type_count($query_adb->get_query(), $type_list, $key, $all);
                } else {
                    # Post default
                    $filter_type_arr = $this->cm->get_post_type_count($query_adb->get_query(), $type_list, $key);
                }
                $filters_type = $this->get_filters($filter_type_arr, $p, $type, '', $key);
                if ($type != $home_type) {
                    $p = $p . '&' . $key . '=' . $type;
                }
                $query_adb->add_query($key, $type);
                $filters_tabs['filters'][$key] = $filters_type;
            }

            $count = isset($filter_type_arr[$type]['count']) ? $filter_type_arr[$type]['count'] : 0;
        }

        $filters_tabs['query_adb'] = $query_adb;
        $filters_tabs['p'] = $p;
        $filters_tabs['c'] = $count;

        return $filters_tabs;
    }

    /*
     * Get movies list from a critic post
     */

    public function get_movies($cid) {
        $data = $this->cm->get_movies_data($cid);
        $movies = array();
        if (sizeof($data)) {
            /*
             *  Object ( [fid] => 31979 [type] => 1 [state] => 1 )
             */
            foreach ($data as $movie) {
                //$post = get_post($movie->fid);
                $movies[$movie->fid]['title'] = $movie->fid;
                $movies[$movie->fid]['link'] = $this->theme_movie_link($movie->fid, $this->get_movie_name_by_id($movie->fid));
                $movies[$movie->fid]['state'] = $this->cm->get_movie_state_name($movie->state);
                $movies[$movie->fid]['type'] = $this->cm->get_post_category_name($movie->type);
                $movies[$movie->fid]['rating'] = $movie->rating;
            }
        }
        return $movies;
    }

    /*
     * Get movies list from a critic post
     */

    public function get_critics($pid) {
        $data = $this->cm->get_critics_meta_and_posts_by_movie($pid);
        $critics = array();
        if (sizeof($data)) {
            /*
              [cid] => 26761
              [type] => 1
              [state] => 1
              [title] => My Last Film, 2015
              [link] =>
             */
            foreach ($data as $critic) {
                $critics[$critic->cid]['title'] = $critic->title;
                $critics[$critic->cid]['name'] = $critic->name;
                $critics[$critic->cid]['link'] = $critic->link;
                $critics[$critic->cid]['state'] = $this->cm->get_movie_state_name($critic->state);
                $critics[$critic->cid]['type'] = $this->cm->get_post_category_name($critic->type);
            }
        }
        return $critics;
    }

    public function get_movie_types() {
        $ma = $this->get_ma();
        $count = $ma->get_post_count();
        $states = array(
            'all' => array(
                'title' => 'All',
                'count' => $count
            )
        );
        foreach ($ma->movie_type as $key => $value) {
            $states[$key] = array(
                'title' => $value,
                'count' => $ma->get_post_count($key)
            );
        }
        return $states;
    }

    public function theme_critic_search($critics_search) {
        // Deprecated unused
        $timer = 0;
        $invalid_str = '';
        $valid_str = '';
        foreach ($critics_search as $cid => $critic) {

            //Get post data
            $title = '';
            $name = '';
            $link = '';

            $post = $this->cm->get_post_and_author($cid);
            if ($post) {
                // Post data                        
                $title = $post->title;
                $name = $post->name;
                $link = $post->link;
            }

            $type = $this->cm->get_post_category_name($critic['type']);

            $valid = true;
            $str = '';
            $str .= '<p>';

            $critic_link = $this->theme_post_link($cid, $title);
            if ($critic['valid']) {
                $str .= '<b class="green">' . $critic_link . '</b><br />'; // . $critic['t'] . '<br />';
            } else {
                $valid = false;
                $str .= '<b class="red">' . $critic_link . '</b><br />'; // . $critic['t'] . '<br />';
            }

            //print '<i>'.$critic['c'] . '</i><br />';
            $str .= $name . ': <a href="' . $link . '">' . substr($link, 0, 50) . '</a><br />';
            $str .= 'Post type: ' . $type . '. <br />';
            $str .= 'Total score: ' . $critic['total'] . '<br />';
            foreach ($critic['score'] as $key => $value) {
                $str .= " - $key => $value<br />";
            }
            $local_timer = $critic['timer'];
            $timer += $local_timer;
            $str .= 'Timer: ' . $local_timer . '<br />';
            $str .= '</p>';
            if ($valid) {
                $valid_str .= $str;
            } else {
                $invalid_str .= $str;
            }
        }
        print $valid_str;
        print $invalid_str;
        if ($timer > $local_timer) {
            print '<p>Time total: ' . $timer . '</p>';
        }
    }

    public function bulk_submit() {
        if (isset($_POST['bulkaction'])) {

            //[bulkaction] => draft [bulk-35660] => on [bulk-35659] => on
            $b = $_POST['bulkaction'];
            $ids = array();

            foreach ($_POST as $key => $value) {
                if (strstr($key, 'bulk-')) {
                    $ids[] = (int) str_replace('bulk-', '', $key);
                }
            }

            if ($b && sizeof($ids)) {
                if ($b == 'rules') {
                    // Apply feed rules
                    $changelog = array();
                    foreach ($ids as $id) {
                        $changed = $this->cf->apply_feed_rules($id);
                        if ($changed) {
                            $changelog[] = $changed;
                        }
                        //array('from' => $post->status, 'to' => $action, 'rule' => $key, 'cid' => $cid);
                    }
                    if ($changelog) {
                        print "<div class=\"updated\"><p><strong>Posts updated</strong></p>";
                        foreach ($changelog as $item) {
                            print "id: " . $item['id'] . "; " . $this->cm->post_status[$item['from']] . " => " . $this->cm->post_status[$item['to']] . "; rule: " . $item['rule'] . "; campaign: " . $item['cid'] . ".<br />";
                        }
                        print "</div>";
                    } else {
                        print "<div class=\"updated\"><p><strong>No changes</strong></p></div>";
                    }
                } else if ($b == 'findmovies') {
                    // Find movies
                    $changed = $this->cf->find_movies_queue($ids);
                    if ($changed) {
                        print "<div class=\"updated\"><p><strong>Posts updated</strong></p></div>";
                    } else {
                        print "<div class=\"updated\"><p><strong>No changes</strong></p></div>";
                    }
                } else if ($b == 'wl' || $b == 'gl' || $b == 'bl' || $b == 'nl') {
                    // Move IP to list

                    if (isset($_POST['isips'])) {
                        // Ip list
                        $changed = $this->cm->bulk_change_ip_list_type_by_ips($ids, $b);
                    } else {
                        // Post list
                        $changed = $this->cm->bulk_change_ip_list_type($ids, $b);
                    }
                    if ($changed) {
                        print "<div class=\"updated\"><p><strong>Posts updated</strong></p></div>";
                    } else {
                        print "<div class=\"updated\"><p><strong>No changes</strong></p></div>";
                    }
                } else if ($b == 'add_critics' || $b == 'add_critics_force') {
                    // Add movies critics
                    $mid = isset($_GET['mid']) ? (int) $_GET['mid'] : '';
                    $force = false;
                    if ($b == 'add_critics_force') {
                        $force = true;
                    }
                    $changed = $this->cs->bulk_add_critics_meta($mid, $ids, $force);
                } else if ($b == 'meta_approve' || $b == 'meta_unapprove' || $b == 'meta_remove') {
                    // Critics meta actions
                    $mid = isset($_GET['mid']) ? (int) $_GET['mid'] : '';

                    if ($b == 'meta_remove') {
                        $changed = $this->cm->bulk_meta_remove($ids, $mid);
                    } else {
                        $meta_state = ($b == 'meta_approve') ? 1 : 0;
                        $changed = $this->cm->bulk_meta_update($ids, $meta_state, $mid);
                    }

                    //author_id
                } else if ($b == 'changeauthor') {
                    $author_id = isset($_POST['author_id']) ? (int) $_POST['author_id'] : '';

                    if ($author_id) {
                        $changed = $this->cm->bulk_change_author($ids, $author_id);
                    }
                } else if ($b == 'start_feed' || $b == 'stop_feed' || $b == 'trash_feed') {
                    $changed = $this->cf->bulk_change_campaign_status($ids, $b);
                } else if (in_array($b, array_keys($this->bulk_actions_parser))) {
                    $changed = $this->cp->bulk_change_campaign_status($ids, $b);
                } else if ($b == 'genre_remove') {
                    $mid = isset($_GET['mid']) ? (int) $_GET['mid'] : 0;
                    if ($mid) {
                        $ma = $this->cm->get_ma();
                        $ma->bulk_remove_movie_genres($mid, $ids);
                    }
                } else if ($b == 'ml_remove_post') {
                    $ml = new MoviesLinks();
                    $mp = $ml->get_mp();
                    foreach ($ids as $id) {
                        $mp->delete_post_by_url_id($id);
                    }
                } else if (in_array($b, array_keys($this->bulk_actions_authors))) {
                    /* 'author_publish' => 'Publish',
                      'author_draft' => 'Draft',
                      'author_trash' => 'Trash',
                      'author_find_avatar' => 'Find avatar', */
                    if ($b == 'author_find_avatar') {
                        // Find avatar for author campaigns
                        $cav = $this->cm->get_cav();

                        print '<textarea style="width:100%; height:300px">';
                        print $cav->find_pro_avatars($ids, true);
                        print '</textarea>';
                    } else if ($b == 'author_url_to_avatar') {
                        // Find avatar for author campaigns
                        $cav = $this->cm->get_cav();

                        print '<textarea style="width:100%; height:300px">';
                        print $cav->bulk_transit_pro_avatars($ids, true);
                        print '</textarea>';
                    } else {
                        $status = -1;
                        if ($b == 'author_publish') {
                            $status = 1;
                        } else if ($b == 'author_draft') {
                            $status = 0;
                        } else if ($b == 'author_trash') {
                            $status = 2;
                        }
                        if ($status != -1) {
                            foreach ($ids as $id) {
                                $this->cm->update_author_status($id, $status);
                            }
                            $updated = true;
                        }
                    }
                } else {
                    // Change status
                    $updated = false;
                    $status = 1;
                    if ($b == 'draft') {
                        $status = 0;
                    } else if ($b == 'trash') {
                        $status = 2;
                    }

                    foreach ($ids as $id) {
                        if ($this->cm->change_post_state($id, $status)) {
                            $updated = true;
                        }
                    }
                    if ($updated) {
                        print "<div class=\"updated\"><p><strong>Posts updated</strong></p></div>";
                    }
                }
            }
        }
    }

    public function bulk_parser_submit() {
        if (isset($_POST['bulkaction'])) {

            //[bulkaction] => draft [bulk-35660] => on [bulk-35659] => on
            $b = $_POST['bulkaction'];
            $ids = array();

            foreach ($_POST as $key => $value) {
                if (strstr($key, 'bulk-')) {
                    $ids[] = (int) str_replace('bulk-', '', $key);
                }
            }

            if ($b && sizeof($ids)) {
                if ($b == 'parsenew' || $b == 'parseforce') {
                    // Apply feed rules
                    $changelog = array();
                    $parser = new CPParsing($this->cp);
                    foreach ($ids as $id) {
                        $force = false;
                        if ($b == 'parseforce') {
                            $force = true;
                        }

                        $changed = $parser->parse_url($id, $force);

                        if ($changed) {
                            $changelog[] = $changed;
                        }
                        //array('from' => $post->status, 'to' => $action, 'rule' => $key, 'cid' => $cid);
                    }
                    if ($changelog) {
                        print "<div class=\"updated\"><p><strong>URLs updated</strong></p></div>";
                    } else {
                        print "<div class=\"updated\"><p><strong>No changes</strong></p></div>";
                    }
                } else if ($b == 'urlfilter') {
                    // URL filter
                    $changelog = array();
                    foreach ($ids as $id) {
                        $changed = $this->cp->bulk_url_filter($id);
                        if ($changed) {
                            $changelog[] = $changed;
                        }
                    }
                    if ($changelog) {
                        print "<div class=\"updated\"><p><strong>URLs updated</strong></p></div>";
                    } else {
                        print "<div class=\"updated\"><p><strong>No changes</strong></p></div>";
                    }
                } else if ($b == 'findmovies') {
                    // Find movies queue
                    $changed = $this->cp->find_movies_queue($ids);
                    if ($changed) {
                        print "<div class=\"updated\"><p><strong>URLs updated</strong></p></div>";
                    } else {
                        print "<div class=\"updated\"><p><strong>No changes</strong></p></div>";
                    }
                } else if ($b == 'statusnew') {
                    // Change status
                    $updated = false;

                    $data = array(
                        'status' => 0,
                        'arhive_date' => 0,
                    );
                    foreach ($ids as $id) {
                        if ($this->cp->change_url($id, $data)) {
                            $updated = true;
                        }
                    }
                    if ($updated) {
                        print "<div class=\"updated\"><p><strong>URLs updated</strong></p></div>";
                    }
                } else if ($b == 'trash') {
                    // Change status
                    $updated = false;
                    $status = 2;
                    foreach ($ids as $id) {
                        if ($this->cp->change_url_state($id, $status)) {
                            $updated = true;
                        }
                    }
                    if ($updated) {
                        print "<div class=\"updated\"><p><strong>URLs updated</strong></p></div>";
                    }
                } else if ($b == 'delete') {
                    // Delete url                   
                    foreach ($ids as $id) {
                        if ($this->cp->delete_url($id)) {
                            
                        }
                    }

                    print "<div class=\"updated\"><p><strong>URLs removed</strong></p></div>";
                }
            }
        }
    }

    public function nonce_validate($form_state) {

        $nonce = wp_verify_nonce($form_state['critic-feeds-nonce'], 'critic-feeds-options');
        if (!$nonce) {
            return __('Error validate nonce');
        }

        return true;
    }

    public function theme_author_tags($tags) {
        $tag_arr = array();
        if (sizeof($tags)) {
            foreach ($tags as $tag) {
                $tag_url = $this->admin_page . $this->tags_url . '&tid=' . $tag->id;
                $tag_arr[] = '<a href="' . $tag_url . '">' . $tag->name . '</a>';
            }
        }
        return $tag_arr;
    }

    public function theme_author_link($id, $name) {
        $author_url = $this->admin_page . $this->authors_url . '&aid=' . $id;
        $link = '<a href="' . $author_url . '">' . $name . '</a>';
        return $link;
    }

    public function theme_feed_link($id, $name) {
        $link = $id;
        if ($id > 0) {
            $url = $this->admin_page . $this->feeds_url . '&cid=' . $id;
            $link = '<a href="' . $url . '">' . $name . '</a>';
        }
        return $link;
    }

    public function theme_movie_link($id, $name) {
        $link = $id;
        if ($id > 0) {
            $url = $this->admin_page . $this->movies_url . '&mid=' . $id;
            $link = '<a href="' . $url . '">' . $name . '</a>';
        }
        return $link;
    }

    public function theme_parser_url_link($id, $name) {
        $link = $id;
        if ($id > 0) {
            $url = $this->admin_page . $this->parser_url . '&uid=' . $id;
            $link = '<a href="' . $url . '">' . $name . '</a>';
        }
        return $link;
    }

    public function theme_parser_link($id, $name) {
        $link = $id;
        if ($id > 0) {
            $url = $this->admin_page . $this->parser_url . '&cid=' . $id;
            $link = '<a href="' . $url . '">' . $name . '</a>';
        }
        return $link;
    }

    public function theme_post_link($id, $name) {
        $link = $id;
        if ($id > 0) {
            $url = $this->admin_page . $this->parrent_slug . '&pid=' . $id;
            $link = '<a href="' . $url . '">' . $name . '</a>';
        }
        return $link;
    }

    public function get_movie_name_by_id($id, $cache = true) {
        //Get from cache
        if ($cache) {
            static $dict;
            if (is_null($dict)) {
                $dict = array();
            }

            if (isset($dict[$id])) {
                return $dict[$id];
            }
        }

        $ma = $this->get_ma();
        $result = $ma->get_movie_name_by_id($id);

        if ($cache) {
            $dict[$id] = $result;
        }
        return $result;
    }
}
