<?php

class MoviesParserAdmin extends ItemAdmin {

    public $mla;
    public $ml;
    private $mp;
    public $sort_pages = array('id', 'date', 'adate', 'pdate', 'title', 'last_update', 'update_interval', 'name', 'pid', 'status', 'type', 'weight');
    public $update_interval = array(
        1 => 'One min',
        5 => 'Five min',
        15 => 'Fifteen min',
        30 => 'Thirty min',
        60 => 'Hourly',
        120 => 'Two hours',
        720 => 'Twice daily',
        1440 => 'Daily'
    );
    public $parser_interval = array(
        5 => 'Five min',
        15 => 'Fifteen min',
        30 => 'Thirty min',
        60 => 'Hourly',
        120 => 'Two hours',
        720 => 'Twice daily',
        1440 => 'Daily',
        10080 => 'Weekly'
    );
    public $remove_interval = array(
        1440 => 'Day',
        10080 => 'Week',
        20160 => 'Two weeks',
        43200 => 'Mounth',
    );
    public $parsing_type = array(
        0 => 'Movie weight',
        1 => 'Random',
    );
    public $parse_number = array(1 => 1, 2 => 2, 3 => 3, 5 => 5, 7 => 7, 10 => 10, 20 => 20, 35 => 35, 50 => 50, 75 => 75, 100 => 100, 200 => 200, 500 => 500, 1000 => 1000);
    public $gen_urls_number = array(10 => 10, 100 => 100, 500 => 500, 1000 => 1000);    
    public $camp_state = array(
        1 => array('title' => 'Active'),
        4 => array('title' => 'Done'),
        0 => array('title' => 'Inactive'),
        3 => array('title' => 'Auto stop'),
        2 => array('title' => 'Trash'),
    );
    public $parser_tabs = array(
        'home' => 'Campaigns list',
        'urls' => 'URLs',
        'log' => 'Log',
        'add' => 'Add a new campaign',
    );
    public $parser_campaign_tabs = array(
        'home' => 'Veiw',
        'urls' => 'URLs list',
        'find' => '1. Find URLs',
        'arhive' => '2. Arhiving',
        'parse' => '3. Parsing',
        'links' => '4. Linking',
        'log' => 'Log',
        'edit' => 'Edit',
        'trash' => 'Trash',
    );
    public $parser_rules_names = array(
        't' => 'Title',
        'r' => 'Release',
        'y' => 'Year',
        'u' => 'URL',
    );
    public $parser_types = array(
        0 => 'Movies',
        1 => 'Actors',
        2 => 'Links',
    );
    public $paser_actor_fields = array(
        'a' => 'First and Last Names',
        'f' => 'First Name',
        'l' => 'Last Name',
    );

    /* Rules */
    public $rules_condition = array(
        1 => 'True',
        0 => 'False',
    );

    /* Arhive rules */
    public $arhive_rules_fields = array(
        'd' => 'Content',
        'u' => 'URL'
    );
    public $arhive_rules_actions = array(
        0 => 'Ignore URL',
        1 => 'Parse URL',
    );

    /* Log */
    private $log_type = array(
        0 => 'Info',
        1 => 'Warning',
        2 => 'Error',
    );
    private $log_status = array(
        0 => 'Other',
        1 => 'Find URLs',
        2 => 'Arhive',
        3 => 'Parsing',
        4 => 'Links',
    );
    public $option_names = array(
        'arhive' => array('log' => 2, 'title' => 'Arhive'),
        'find_urls' => array('log' => 1, 'title' => 'Find URLs'),
        'cron_urls' => array('log' => 1, 'title' => 'Find URLs'),
        'gen_urls' => array('log' => 1, 'title' => 'Generate URLs'),
        'parsing' => array('log' => 3, 'title' => 'Parsing'),
        'links' => array('log' => 4, 'title' => 'Links'),
    );
    public $post_status = array(
        1 => 'Publish',
        0 => 'Draft',
        2 => 'Trash'
    );
    public $url_status = array(
        0 => 'New',
        1 => 'Exist',
        2 => 'Trash',
        3 => 'Ignore',
        4 => 'Error',
        5 => 'Parsing',
    );
    public $bulk_actions = array(
        'post_status_new' => 'Post status New',
        'validate_arhive' => 'Validate Arhive len',
        'delete_url' => 'Delete URL',
        'delete_post' => 'Delete Post',
        'delete_arhive' => 'Delete Arhive and Post',
    );
    public $bulk_actions_parser = array(
        'start_campaign' => 'Start campaigns',
        'stop_campaign' => 'Stop campaigns',
        'trash_campaign' => 'Trash campaigns',
            /* 'active_parser' => 'Active parser',
              'inactive_parser' => 'Inactive parser',
              'active_find' => 'Active find urls',
              'inactive_find' => 'Inactive find urls' */
    );
    public $post_arhive_status = array(
        1 => 'With arhive',
        0 => 'No arhive',
    );
    public $post_parse_status_tab = array(
        1 => 'Parsed Done',
        2 => 'Parsed Error',
        0 => 'No pasred',
    );
    public $post_parse_status = array(
        1 => 'Done',
        0 => 'Error',
    );
    public $post_link_status = array(
        0 => 'New',
        1 => 'Done',
        2 => 'Error',
    );
    public $parse_mode = array(
        0 => 'Curl',
        1 => 'Webdrivers',
        2 => 'Tor Webdrivers',
        3 => 'Tor Curl',
    );
    public $tor_mode = array(
        0 => 'Tor and Proxy',
        1 => 'Tor',
        2 => 'Proxy',
    );
    public $tor_agent = array(
        0 => 'List agents',
        1 => 'Generate',
    );
    
    /* Generate urls */
    public $rwt_movie_type = array(
        'a' => 'All',
        'm' => 'Movies',
        't' => 'Tv Series'
    );
    public $rwt_actor_type = array(
        'a' => 'All',
        's' => 'Stars',
        'm' => 'Main',
        'e' => 'Extra',
        'd' => 'Directors',
    );
    public $rwt_actor_link = array(
        'a' => 'All normalized actors',
    );

    public function __construct($mla = '') {
        $this->mla = $mla;
        $this->ml = $mla->ml;
        $this->mp = $this->ml->get_mp();
        $this->get_perpage();
    }

    public function init() {
        $curr_tab = $this->get_tab();
        $cid = isset($_GET['cid']) ? (int) $_GET['cid'] : '';
        $uid = isset($_GET['uid']) ? (int) $_GET['uid'] : '';
        $page = $this->get_page();
        $per_page = $this->get_perpage();

        //Sort
        $orderby = $this->get_orderby($this->sort_pages);
        $order = $this->get_order();

        $url = $this->mla->admin_page . $this->mla->parser_url;

        /*
         * Url page
         */
        if ($uid) {
            $url_data = $this->mp->get_url($uid);
            include(MOVIES_LINKS_PLUGIN_DIR . 'includes/view_url.php');
            return;
        }

        /*
         * Campaign page
         */
        if ($cid) {
            $campaign = $this->mp->get_campaign($cid);
            $mlr_name = $this->ml->get_campaign_mlr_name($campaign);

            //Tabs
            $append = '&cid=' . $cid;
            $tabs_arr = $this->get_campaign_tabs($campaign, $mlr_name);

            $tabs = $this->get_tabs($url, $tabs_arr, $curr_tab, $append);

            if (!$curr_tab) {
                $curr_tab = 'home';
            }
            /*
              'home' => 'Veiw',
              'urls' => 'URLs list',
              'find' => '1. Find URLs',
              'arhive' => '2. Create arhive',
              'parse' => '3. Parsing data',
              'links' => '4. Link to movies',
              'log' => 'Log',
              'edit' => 'Edit',
              'trash' => 'Trash',
             */
            if ($curr_tab == 'home') {
                // Campaign view page
                $update_interval = $this->update_interval;
                if ($campaign) {
                    $debug = false;
                    if ($_GET['debug']) {
                        $debug = true;
                    }

                    if ($_GET['export']) {
                        $urls = $this->mp->get_all_urls($cid);
                        print '<h2>Export campaign URLs</h2>';
                        if ($urls) {
                            $items = array();
                            foreach ($urls as $url) {
                                $items[] = $url->link;
                            }
                            print '<textarea style="width:90%; height:500px">' . implode("\n", $items) . '</textarea>';
                        }
                        exit;
                    } else if ($_GET['export_rules']) {
                        $options = $this->mp->get_options($campaign);
                        $parser_rules = $options['parsing']['rules'];
                        $json = json_encode($parser_rules);
                        print '<h2>Export campaign parser rules</h2>';
                        print '<textarea style="width:90%; height:500px">' . $json . '</textarea>';

                        exit;
                    } else if ($_GET['export_row_rules']) {
                        $options = $this->mp->get_options($campaign);
                        $parser_rules = $options['parsing']['row_rules'];
                        $json = json_encode($parser_rules);
                        print '<h2>Export campaign parser row rules</h2>';
                        print '<textarea style="width:90%; height:500px">' . $json . '</textarea>';

                        exit;
                    } else if ($_GET['export_links_rules']) {
                        $options = $this->mp->get_options($campaign);
                        $parser_rules = $options['links']['rules'];
                        $json = json_encode($parser_rules);
                        print '<h2>Export campaign links rules</h2>';
                        print '<textarea style="width:90%; height:500px">' . $json . '</textarea>';

                        exit;
                    } else if ($_GET['find_urls']) {
                        print '<h2>Find campaign URLs</h2>';
                        $settings = $this->ml->get_settings();
                        $preivew_data = $this->mp->find_urls($campaign, $this->mp->get_options($campaign), $settings, false);
                        if ($preivew_data['urls']) {
                            print '<textarea style="width: 90%; height: 500px;">' . implode("\n", $preivew_data['urls']) . '</textarea>';
                        }
                        exit;
                    } else if ($_GET['gen_urls']) {
                        print '<h2>Generate campaign URLs</h2>';
                        $settings = $this->ml->get_settings();
                        $options = $this->mp->get_options($campaign);

                        $count = $this->mp->proccess_gen_urls($campaign, $options, $debug);
                        if ($count) {
                            print '<p>Total add new: ' . $count . '</p>';
                        }
                        exit;
                    }
                    include(MOVIES_LINKS_PLUGIN_DIR . 'includes/view_parser.php');
                }
            } else if ($curr_tab == 'urls') {
                // Campaign post page
                $this->parser_urls($tabs, $url, $cid);
            } else if ($curr_tab == 'find') {
                // 1. Find urls
                if (isset($_POST['id'])) {
                    $valid = $this->campaign_edit_validate($_POST);
                    if ($valid === true) {
                        $this->campaign_find_urls_submit($_POST);
                        $result = __('Campaign') . ' [' . $_POST['id'] . '] ' . __('updated');
                        print "<div class=\"updated\"><p><strong>$result</strong></p></div>";
                    } else {
                        print "<div class=\"error\"><p><strong>$valid</strong></p></div>";
                    }
                }
                $campaign = $this->mp->get_campaign($cid);

                $preivew_data = array();
                $preview_gen_data = array();

                $options = $this->mp->get_options($campaign);
                if (isset($_POST['preview'])) {
                    $settings = $this->ml->get_settings();
                    if ($_POST['find_urls']) {
                        //Find URLs
                        $preivew_data = $this->mp->find_urls($campaign, $options, $settings, true);
                    } else if ($_POST['generate_urls']) {
                        // Generage URLs
                        $preview_gen_data = $this->mp->generate_urls($campaign, $options, $settings, 0, true);
                    }
                }
                if (isset($_POST['cron_preview'])) {
                    $cron_preivew_data = $this->mp->cron_urls($campaign, $options, true);
                }

                include(MOVIES_LINKS_PLUGIN_DIR . 'includes/find_urls.php');
            } else if ($curr_tab == 'arhive') {
                // 2. Create arhive
                if (isset($_POST['id'])) {
                    $valid = $this->campaign_edit_validate($_POST);
                    if ($valid === true) {
                        $result_id = $this->arhive_edit_submit($_POST);
                        $result = __('Campaign') . ' [' . $result_id . '] ' . __('updated');
                        print "<div class=\"updated\"><p><strong>$result</strong></p></div>";
                    } else {
                        print "<div class=\"error\"><p><strong>$valid</strong></p></div>";
                    }
                }
                $preivew_data = array();
                if (isset($_POST['arhive_preview'])) {
                    $campaign = $this->mp->get_campaign($cid);
                    $options = $this->mp->get_options($campaign);
                    $valid = $this->campaign_edit_validate($_POST);
                    if ($valid) {
                        $settings = $this->ml->get_settings();
                        $posturl = $_POST['url'];
                        if ($posturl) {
                            $preivew_data = $this->mp->preview_arhive($posturl, $settings, $options);
                        }
                    }
                }

                $campaign = $this->mp->get_campaign($cid);
                include(MOVIES_LINKS_PLUGIN_DIR . 'includes/edit_arhive.php');
            } else if ($curr_tab == 'parse') {
                // 3. Parsing data
                if (isset($_POST['id'])) {
                    $valid = $this->campaign_edit_validate($_POST);
                    if ($valid === true) {
                        if ($_POST['links_parsing_data']) {
                            $result_id = $this->links_data_edit_submit($_POST);
                        } else {
                            $result_id = $this->parsing_data_edit_submit($_POST);
                        }
                        $result = __('Campaign') . ' [' . $result_id . '] ' . __('updated');
                        print "<div class=\"updated\"><p><strong>$result</strong></p></div>";
                    } else {
                        print "<div class=\"error\"><p><strong>$valid</strong></p></div>";
                    }
                }

                $preivew_data = array();
                $campaign = $this->mp->get_campaign($cid);

                if (isset($_POST['preview'])) {
                    $preivew_data = $this->preview_parsing($campaign);
                } else if (isset($_POST['preview_row'])) {
                    $preivew_data = $this->preview_parsing($campaign, 'row_rules');
                } else if (isset($_POST['preview_links'])) {
                    $preivew_data = $this->preview_parser_links($campaign);
                }
                include(MOVIES_LINKS_PLUGIN_DIR . 'includes/edit_parsing_data.php');
            } else if ($curr_tab == 'links') {
                // 4. Link to movies
                if (isset($_POST['id'])) {
                    $valid = $this->campaign_edit_validate($_POST);
                    if ($valid === true) {
                        $result_id = $this->links_data_edit_submit($_POST);
                        $result = __('Campaign') . ' [' . $result_id . '] ' . __('updated');
                        print "<div class=\"updated\"><p><strong>$result</strong></p></div>";
                    } else {
                        print "<div class=\"error\"><p><strong>$valid</strong></p></div>";
                    }
                }

                $preivew_data = array();
                $campaign = $this->mp->get_campaign($cid);

                if (isset($_POST['preview'])) {
                    $preivew_data = $this->preview_links($campaign);
                }

                include(MOVIES_LINKS_PLUGIN_DIR . 'includes/edit_links.php');
            } else if ($curr_tab == 'log') {
                // Log
                $this->parser_log($tabs, $url, $cid);
            } else if ($curr_tab == 'trash') {
                // Trash
                if (isset($_POST['id'])) {
                    $valid = $this->campaign_edit_validate($_POST);
                    if ($valid === true) {

                        if ($_POST['trash'] == 1) {

                            $result_id = $this->mp->trash_campaign($_POST);

                            $active = isset($_POST['status']) ? $_POST['status'] : 0;
                            if ($active == 2) {
                                $result = __('Campaign') . ' [' . $result_id . '] ' . __('moved to trash');
                            } else {
                                $result = __('Campaign') . ' [' . $result_id . '] ' . __('untrashed');
                            }
                        } else if ($_POST['remove_all_posts'] == 1) {
                            $this->mp->remove_all_campaign_posts($_POST);
                            $result = 'Removed';
                        }

                        print "<div class=\"updated\"><p><strong>$result</strong></p></div>";
                    } else {
                        print "<div class=\"error\"><p><strong>$valid</strong></p></div>";
                    }
                }
                $campaign = $this->mp->get_campaign($cid);
                include(MOVIES_LINKS_PLUGIN_DIR . 'includes/trash_parser.php');
            } else if ($curr_tab == 'edit') {
                if (isset($_POST['id'])) {
                    $valid = $this->campaign_edit_validate($_POST);
                    if ($valid === true) {
                        $result_id = $this->campaign_edit_submit($_POST);
                        $result = __('Campaign') . ' [' . $result_id . '] ' . __('updated');
                        print "<div class=\"updated\"><p><strong>$result</strong></p></div>";
                    } else {
                        print "<div class=\"error\"><p><strong>$valid</strong></p></div>";
                    }
                }
                $campaign = $this->mp->get_campaign($cid);
                include(MOVIES_LINKS_PLUGIN_DIR . 'includes/edit_parser.php');
            } else if ($curr_tab == 'mlr') {
                // Movies links custom results

                $mlr = $this->ml->get_campaing_mlr($campaign);
                if (!$mlr) {
                    return '';
                }

                //Sort
                $orderby = $this->get_orderby($mlr->sort_pages);
                $order = $this->get_order();

                $page_url = $url;
                $page_url .= '&cid=' . $cid;
                $page_url .= '&tab=mlr';


                //Bulk actions
                // $this->bulk_parser_submit();

                /*
                  // Filter by status
                  $home_status = -1;
                  $status = isset($_GET['status']) ? (int) $_GET['status'] : $home_status;
                  $filter_arr = $this->get_url_status_count($cid);
                  $filters = $this->get_filters($filter_arr, $page_url, $status, '', 'status');
                  if ($status != $home_status) {
                  $page_url = $page_url . '&status=' . $status;
                  }
                  $count = isset($filter_arr[$status]['count']) ? $filter_arr[$status]['count'] : 0;

                 */
                $count = $mlr->get_posts_count();
                $status = -1;
                $pager = $this->themePager($status, $page, $page_url, $count, $per_page, $orderby, $order);

                $posts = $mlr->get_posts($page, $orderby, $order, $per_page);

                include(MOVIES_LINKS_PLUGIN_DIR . 'includes/mlr/' . $mlr_name . '_results.php');
            }
            return;
        }

        //
        // Other pages
        //
        //Tabs
        $tabs_arr = $this->parser_tabs;
        $tabs = $this->get_tabs($url, $tabs_arr, $curr_tab);

        if (!$curr_tab) {
            $page_url = $url;

            // Bulk actions
            $this->bulk_submit();

            // Author id            
            // Filter by status
            $home_status = -1;
            $status = isset($_GET['status']) ? (int) $_GET['status'] : $home_status;
            $filter_arr = $this->parser_states();
            $filters = $this->get_filters($filter_arr, $page_url, $status);
            if ($status != $home_status) {
                $page_url = $page_url . '&status=' . $status;
            }

            // Filter by type
            $home_type = -1;
            $type = isset($_GET['type']) ? (int) $_GET['type'] : $home_type;
            $type_arr = $this->parser_types($status);
            $type_filters = $this->get_filters($type_arr, $page_url, $type, '', 'type');
            if ($type != $home_type) {
                $page_url = $page_url . '&type=' . $type;
            }

            //Pager
            $count = isset($filter_arr[$status]['count']) ? $filter_arr[$status]['count'] : 0;

            $pager = $this->themePager($status, $page, $page_url, $count, $per_page, $orderby, $order);

            $campaigns = $this->mp->get_campaigns($status, $type, $page, $orderby, $order, $per_page);


            include(MOVIES_LINKS_PLUGIN_DIR . 'includes/list_parsers.php');
        } else if ($curr_tab == 'urls') {
            // Posts
            $this->parser_urls($tabs, $url, 0);
        } else if ($curr_tab == 'log') {
            // Log
            $this->parser_log($tabs, $url);
        } else if ($curr_tab == 'settings') {
            // Settings
            if (isset($_POST['critic-feeds-nonce'])) {
                $valid = $this->nonce_validate($_POST);
                if ($valid === true) {
                    $this->mp->settings_submit($_POST);
                    $result = __('Updated');
                    print "<div class=\"updated\"><p><strong>$result</strong></p></div>";
                } else {
                    print "<div class=\"error\"><p><strong>$valid</strong></p></div>";
                }
            }
            include(MOVIES_LINKS_PLUGIN_DIR . 'includes/settings_parser.php');
        } else if ($curr_tab == 'add') {
            // Add
            if (isset($_POST['site'])) {
                $valid = $this->campaign_edit_validate($_POST);
                if ($valid === true) {
                    $result_id = $this->campaign_edit_submit($_POST);
                    $result = __('Campaign added. Go to the campaign: ') . '<a href="' . $url . '&cid=' . $result_id . '">' . $result_id . '</a>';
                    print "<div class=\"updated\"><p><strong>$result</strong></p></div>";
                } else {
                    print "<div class=\"error\"><p><strong>$valid</strong></p></div>";
                }
            }

            include(MOVIES_LINKS_PLUGIN_DIR . 'includes/add_parser.php');
        }
    }

    public function overview() {
        $curr_tab = $this->get_tab();
        $page = $this->get_page();
        $per_page = $this->get_perpage();

        // Bulk actions
        $this->bulk_submit();

        // Sort
        $orderby = $this->get_orderby($this->sort_pages);
        $order = $this->get_order();

        $url = $this->mla->admin_page . $this->mla->parrent_slug;
        $parser_url = $this->mla->admin_page . $this->mla->parser_url;


        $page_url = $url;

        // Author id            
        // Filter by status
        $home_status = -1;
        $status = isset($_GET['status']) ? (int) $_GET['status'] : $home_status;
        $filter_arr = $this->parser_states();
        $filters = $this->get_filters($filter_arr, $page_url, $status);
        if ($status != $home_status) {
            $page_url = $page_url . '&status=' . $status;
        }

        // Filter by type
        $home_type = -1;
        $type = isset($_GET['type']) ? (int) $_GET['type'] : $home_type;
        $type_arr = $this->parser_types($status);
        $type_filters = $this->get_filters($type_arr, $page_url, $type, '', 'type');
        if ($type != $home_type) {
            $page_url = $page_url . '&type=' . $type;
        }

        //Pager
        $count = isset($filter_arr[$status]['count']) ? $filter_arr[$status]['count'] : 0;

        $pager = $this->themePager($status, $page, $page_url, $count, $per_page, $orderby, $order);

        $campaigns = $this->mp->get_campaigns($status, $type, $page, $orderby, $order, $per_page);

        //Tabs
        $tabs_arr = $this->parser_tabs;

        $tabs = $this->get_tabs($parser_url, $tabs_arr, '-');

        include(MOVIES_LINKS_PLUGIN_DIR . 'includes/overview.php');
    }

    /*
     * Parser
     */

    public function parser_urls($tabs = '', $url = '', $cid = 0) {
        $page = $this->get_page();
        $per_page = $this->get_perpage();

        $page_url = $url;
        $page_url .= '&tab=urls';

        //Bulk actions
        $this->bulk_parser_submit();

        //Sort        

        $orderby = $this->get_orderby($this->sort_pages);
        $order = $this->get_order();

        // Campaign id
        $campaign = '';
        if ($cid) {
            $campaign = $this->mp->get_campaign($cid);
            $page_url .= '&cid=' . $cid;
        }

        // Filter by status
        $home_status = -1;
        $status = isset($_GET['status']) ? (int) $_GET['status'] : $home_status;
        $filter_arr = $this->get_url_status_count($cid);
        $filters = $this->get_filters($filter_arr, $page_url, $status, '', 'status');
        if ($status != $home_status) {
            $page_url = $page_url . '&status=' . $status;
        }
        $count = isset($filter_arr[$status]['count']) ? $filter_arr[$status]['count'] : 0;

        // Filter by arhive
        $home_arhive_type = -1;
        $arhive_type = isset($_GET['arhive_type']) ? (int) $_GET['arhive_type'] : $home_arhive_type;
        $filter_arhive_type_arr = $this->get_post_arhive_types($cid, $status);
        $filters_arhive_type = $this->get_filters($filter_arhive_type_arr, $page_url, $arhive_type, '', 'arhive_type');
        if ($arhive_type != $home_arhive_type) {
            $page_url = $page_url . '&arhive_type=' . $arhive_type;
            $count = isset($filter_arhive_type_arr[$arhive_type]['count']) ? $filter_arhive_type_arr[$arhive_type]['count'] : 0;
        }

        // Filter by parser
        $home_parser_type = -1;
        $parser_type = isset($_GET['parser_type']) ? (int) $_GET['parser_type'] : $home_parser_type;
        $filter_parser_type_arr = $this->get_post_parser_types($cid, $status, $arhive_type);
        $filters_parser_type = $this->get_filters($filter_parser_type_arr, $page_url, $parser_type, '', 'parser_type');
        if ($parser_type != $home_parser_type) {
            $page_url = $page_url . '&parser_type=' . $parser_type;
            $count = isset($filter_parser_type_arr[$parser_type]['count']) ? $filter_parser_type_arr[$parser_type]['count'] : 0;
        }

        // Filter by links
        $home_links_type = -1;
        $links_type = isset($_GET['links_type']) ? (int) $_GET['links_type'] : $home_links_type;
        $filter_links_type_arr = $this->get_post_links_types($cid, $status, $arhive_type, $parser_type);
        $filters_links_type = $this->get_filters($filter_links_type_arr, $page_url, $links_type, '', 'links_type');
        if ($links_type != $home_links_type) {
            $page_url = $page_url . '&links_type=' . $links_type;
            $count = isset($filter_links_type_arr[$links_type]['count']) ? $filter_links_type_arr[$links_type]['count'] : 0;
        }

        $pager = $this->themePager($status, $page, $page_url, $count, $per_page, $orderby, $order);
        $posts = $this->mp->get_urls($status, $page, $cid, $arhive_type, $parser_type, $links_type, $orderby, $order, $per_page);

        include(MOVIES_LINKS_PLUGIN_DIR . 'includes/list_urls.php');
    }

    public function parser_log($tabs = '', $url = '', $cid = 0) {
        $page = $this->get_page();
        $per_page = $this->get_perpage();

        //Sort
        $orderby = $this->get_orderby($this->sort_pages);
        $order = $this->get_order();

        $page_url = $url;
        $page_url .= '&tab=log';
        $page_url .= '&cid=' . $cid;

        // Filter by status
        $home_log_status = -1;
        $status = isset($_GET['status']) ? (int) $_GET['status'] : $home_log_status;
        $filter_log_status_arr = $this->get_post_log_status($cid);
        $filters_log_status = $this->get_filters($filter_log_status_arr, $page_url, $status, '', 'status');

        $page_url = $page_url . '&log_status=' . $status;
        $count = isset($filter_log_status_arr[$status]['count']) ? $filter_log_status_arr[$status]['count'] : 0;


        // Log type
        $home_type = -1;
        $type = isset($_GET['type']) ? (int) $_GET['type'] : $home_type;
        $filter_type_arr = $this->get_post_log_types($cid, $status);
        $filters_type = $this->get_filters($filter_type_arr, $page_url, $type, '', 'type');
        if ($type != $home_type) {
            $page_url = $page_url . '&type=' . $type;
            $count = isset($filter_type_arr[$type]['count']) ? $filter_type_arr[$type]['count'] : 0;
        }

        $log = $this->mp->get_log($page, $cid, 0, $status, $type, $per_page);
        $pager = $this->themePager(1, $page, $page_url, $count, $per_page, $orderby, $order);
        include(MOVIES_LINKS_PLUGIN_DIR . 'includes/list_log_parser.php');
    }

    public function campaign_edit_validate($form_state) {

        if (isset($form_state['trash'])) {
            // Trash
        } else if (isset($form_state['wait'])) {
            // Find urls
            if ($form_state['match'] == '') {
                return __('Enter the match regexp');
            }
            if ($form_state['first'] == '' && $form_state['page'] == '') {
                return __('Enter the any page');
            }
        } else if (isset($form_state['add_campaign']) || isset($form_state['edit_campaign'])) {
            // Add
            if ($form_state['title'] == '') {
                return __('Enter the title');
            }
        }

        $nonce = wp_verify_nonce($_POST['ml-nonce'], 'ml-nonce');
        if (!$nonce) {
            return __('Error validate nonce');
        }

        return true;
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
                if (in_array($b, array_keys($this->bulk_actions_parser))) {
                    $this->bulk_change_campaign_status($ids, $b);
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

                if ($b == 'post_status_new') {
                    // Change post status
                    foreach ($ids as $id) {
                        $this->mp->update_post_status($id, 0);
                    }
                    print "<div class=\"updated\"><p><strong>Updated</strong></p></div>";
                } else if ($b == 'validate_arhive') {
                    // Validate arhive
                    foreach ($ids as $id) {
                        $this->mp->validate_arhive_len($id);
                    }
                    print "<div class=\"updated\"><p><strong>Updated</strong></p></div>";
                } else if ($b == 'delete_post') {
                    // Delete url                   
                    foreach ($ids as $id) {
                        $this->mp->delete_post_by_url_id($id);
                    }

                    print "<div class=\"updated\"><p><strong>URLs removed</strong></p></div>";
                } else if ($b == 'delete_arhive') {
                    // Delete arhive                   
                    foreach ($ids as $id) {
                        $this->mp->delete_arhive_by_url_id($id);
                    }

                    print "<div class=\"updated\"><p><strong>Arhives removed</strong></p></div>";
                } else if ($b == 'delete_url') {
                    // Delete arhive                   
                    foreach ($ids as $id) {
                        $this->mp->delete_arhive_by_url_id($id);
                        $this->mp->delete_url($id);
                    }

                    print "<div class=\"updated\"><p><strong>Arhives removed</strong></p></div>";
                }
            }
        }
    }

    public function arhive_edit_submit($form_state) {
        $result = 0;

        if ($form_state['id']) {

            $id = $form_state['id'];
            $campaign = $this->mp->get_campaign($id);
            $opt_prev = unserialize($campaign->options);

            $arhive = array(
                'interval' => isset($form_state['interval']) ? $form_state['interval'] : $opt_prev['arhive']['interval'],
                'num' => isset($form_state['num']) ? $form_state['num'] : $opt_prev['arhive']['num'],
                'status' => isset($form_state['status']) ? $form_state['status'] : 0,
                'proxy' => isset($form_state['proxy']) ? $form_state['proxy'] : 0,
                'webdrivers' => isset($form_state['webdrivers']) ? $form_state['webdrivers'] : 0,
                'random' => isset($form_state['random']) ? $form_state['random'] : 0,
                'del_pea' => isset($form_state['del_pea']) ? $form_state['del_pea'] : 0,
                'del_pea_int' => isset($form_state['del_pea_int']) ? $form_state['del_pea_int'] : $opt_prev['arhive']['del_pea_int'],
                'tor_h' => isset($form_state['tor_h']) ? $form_state['tor_h'] : $opt_prev['arhive']['tor_h'],
                'tor_d' => isset($form_state['tor_d']) ? $form_state['tor_d'] : $opt_prev['arhive']['tor_d'],
                'tor_mode' => isset($form_state['tor_mode']) ? $form_state['tor_mode'] : $opt_prev['arhive']['tor_mode'],
                'body_len' => isset($form_state['body_len']) ? $form_state['body_len'] : $opt_prev['arhive']['body_len'],
                'chd' => isset($form_state['chd']) ? base64_encode(stripslashes($form_state['chd'])) : '',
            );

            $options = $opt_prev;
            $options['arhive'] = $arhive;

            $this->mp->update_campaign_options($id, $options);
            $result = $id;
        }
        return $result;
    }

    public function parsing_data_edit_submit($form_state) {
        $result = 0;

        if ($form_state['id']) {
            $id = $form_state['id'];
            $campaign = $this->mp->get_campaign($id);
            $opt_prev = $this->mp->get_options($campaign);

            $parsing_prev = $opt_prev['parsing'];
            $add_result = array();
            foreach ($parsing_prev as $key => $value) {
                if (isset($form_state[$key])) {
                    $add_result[$key] = $form_state[$key];
                }
            }

            if ($form_state['edit_parsing_options']) {
                $add_result['status'] = isset($form_state['status']) ? $form_state['status'] : 0;
            } else if ($form_state['edit_parsing_data']) {
                // Rules logic
                $add_result['rules'] = $this->parser_rules_form($form_state);
                if ($form_state['import_rules_json']) {
                    $rules = json_decode(trim(stripslashes($form_state['import_rules_json'])), true);
                    if (sizeof($rules)) {
                        $add_result['rules'] = $rules;
                    }
                }
            } else if ($form_state['edit_parsing_row']) {
                // Rules logic
                $add_result['row_rules'] = $this->parser_rules_form($form_state);
                if ($form_state['import_rules_json']) {
                    $rules = json_decode(trim(stripslashes($form_state['import_rules_json'])), true);
                    if (sizeof($rules)) {
                        $add_result['row_rules'] = $rules;
                    }
                }
                $add_result['row_status'] = isset($form_state['row_status']) ? $form_state['row_status'] : 0;
            }

            $opt_upd = array();
            $opt_upd['parsing'] = $add_result;

            $this->mp->update_campaign_options($id, $opt_upd);
            $result = $id;
        }
        return $result;
    }

    public function links_data_edit_submit($form_state) {
        $result = 0;

        if ($form_state['id']) {

            $id = $form_state['id'];
            $campaign = $this->mp->get_campaign($id);
            $opt_prev = $this->mp->get_options($campaign);

            $options = $opt_prev;

            // Update option logic 27.05.2022
            $parsing = $options['links'];
            $form_fields = array_keys($opt_prev['links']);
            foreach ($form_fields as $field) {
                if (isset($form_state[$field])) {
                    $parsing[$field] = $form_state[$field];
                }
            }
            $parsing['status'] = isset($form_state['status']) ? $form_state['status'] : 0;

            // Link to movies
            $parsing['rules'] = $this->links_rules_form($form_state);

            if ($form_state['import_rules_json']) {
                $rules = json_decode(trim(stripslashes($form_state['import_rules_json'])), true);
                if (sizeof($rules)) {
                    $parsing['rules'] = $rules;
                }
            }


            $opt_upd = array();
            $opt_upd['links'] = $parsing;

            $this->mp->update_campaign_options($id, $opt_upd);

            $result = $id;
        }
        return $result;
    }

    public function campaign_edit_submit($form_state) {

        $result = 0;
        $id = 0;

        $status = isset($form_state['status']) ? $form_state['status'] : 0;
        $type = isset($form_state['type']) ? $form_state['type'] : 0;
        $title = $this->mp->escape($form_state['title']);
        $site = $this->mp->escape($form_state['site']);

        $id = $form_state['id'] ? $form_state['id'] : 0;

        if ($id) {
            //EDIT
            $this->mp->update_campaign($status, $title, $site, $type, $id);
            $result = $id;
        } else {
            //ADD
            $result = $this->mp->add_campaing($status, $title, $site, $type);
        }
        return $result;
    }

    public function bulk_change_campaign_status($ids = array(), $b) {
        /*
          'start_campaign' => 'Start campaigns',
          'stop_campaign' => 'Stop campaigns',
          'trash_campaign' => 'Trash campaigns',
          'active_parser' => 'Active parser',
          'inactive_parser' => 'Inactive parser',
          'active_find' => 'Active find urls',
          'inactive_find' => 'Inactive find urls'
         */
        foreach ($ids as $id) {
            if ($b == 'start_campaign') {
                $status = 1;
                $this->mp->update_campaign_status($id, $status);
            } else if ($b == 'stop_campaign') {
                $status = 0;
                $this->mp->update_campaign_status($id, $status);
            } else if ($b == 'trash_campaign') {
                $status = 2;
                $this->mp->update_campaign_status($id, $status);
            }
        }
        return true;
    }

    public function get_campaign_tabs($campaign, $mlr_name = '') {
        $tabs_arr = $this->parser_campaign_tabs;
        if ($mlr_name) {
            $tabs_arr['mlr'] = 'Results';
        }
        return $tabs_arr;
    }

    public function parser_actions($campaign) {
        $mlr_name = $this->ml->get_campaign_mlr_name($campaign);
        $tabs = $this->get_campaign_tabs($campaign, $mlr_name);
        foreach ($tabs as $key => $value) {
            $parser_actions[$key] = array('title' => $value);
        }
        return $parser_actions;
    }

    public function get_next_update($last_update = 0, $interval = 0) {
        $nextUpdate = $last_update + $interval * 60;

        if ($this->mp->curr_time() > $nextUpdate) {
            $textDate = __('Waiting');
        } else {
            $textDate = gmdate('Y-m-d H:i:s', $nextUpdate);
        }

        return $textDate;
    }

    public function parser_states($aid = 0) {
        $count = $this->mp->get_parser_count(-1, -1, $aid);
        $parser_states = array(
            '-1' => array(
                'title' => 'All',
                'count' => $count
            )
        );
        foreach ($this->camp_state as $key => $value) {
            $parser_states[$key] = array(
                'title' => $value['title'],
                'count' => $this->mp->get_parser_count($key, -1, $aid));
        }
        return $parser_states;
    }

    public function parser_types($state = -1, $aid = 0) {
        $count = $this->mp->get_parser_count($state, -1, $aid);
        $parser_states = array(
            '-1' => array(
                'title' => 'All',
                'count' => $count
            )
        );
        foreach ($this->parser_types as $key => $value) {
            $parser_states[$key] = array(
                'title' => $value,
                'count' => $this->mp->get_parser_count($state, $key, $aid));
        }
        return $parser_states;
    }

    /*
     * Rules parser
     */

    public function preview_parsing($campaign, $rules_name = 'rules') {
        $options = $this->mp->get_options($campaign);
        $o = $options['parsing'];
        $count = $o['pr_num'];
        $cid = $campaign->id;
        $last_posts = $this->mp->get_last_arhives_no_posts($count, $cid, false);

        if ($last_posts) {
            $preivew_data = $this->mp->parse_arhives($last_posts, $campaign, $rules_name);
        } else {
            return -1;
        }

        return $preivew_data;
    }

    private function parser_rules_form($form_state) {
        $rule_exists = array();

        $to_remove = isset($form_state['remove_reg_rule']) ? $form_state['remove_reg_rule'] : array();
        $rule_keys = array('f', 'k', 't', 'r', 'm', 'c', 'w', 'a', 'n', 's');
        // Exists rules
        foreach ($form_state as $name => $value) {
            if (strstr($name, 'rule_reg_id_')) {
                $key = $value;
                if (in_array($key, $to_remove)) {
                    continue;
                }
                $upd_rule = array();
                foreach ($rule_keys as $k) {
                    $form_name = 'rule_reg_' . $k . '_' . $key;
                    $form_value = isset($form_state[$form_name]) ? $form_state[$form_name] : $this->mp->get_def_parser_rule($k);
                    if ($k == 'r') {
                        //Regexp encode
                        $form_value = base64_encode(stripslashes($form_value));
                    }
                    $upd_rule[$k] = $form_value;
                }

                $rule_exists[$key] = $upd_rule;
            }
        }

        // New rule
        if ($form_state['reg_new_rule_r']) {

            $old_key = 0;
            if ($rule_exists) {
                krsort($rule_exists);
                $old_key = array_key_first($rule_exists);
            }
            $new_rule_key = $old_key + 1;
            foreach ($rule_keys as $k) {
                $form_name = 'reg_new_rule_' . $k;
                $form_value = isset($form_state[$form_name]) ? $form_state[$form_name] : $this->mp->get_def_parser_rule($k);
                if ($k == 'r') {
                    //Regexp encode
                    $form_value = base64_encode(stripslashes($form_value));
                }
                $new_rule[$k] = $form_value;
            }

            $rule_exists[$new_rule_key] = $new_rule;
        }

        ksort($rule_exists);

        return $rule_exists;
    }

    public function show_parser_rules($rules = array(), $edit = true, $camp_type = 0, $check = array(), $rules_fields = array(), $parser_rules_type = array()) {
        if ($rules || $edit) {
            if (!is_array($rules)) {
                $rules = array();
            }


            if (!$rules_fields) {
                $rules_fields = $this->mp->parser_rules_fields;
                if ($camp_type == 1) {
                    $rules_fields = $this->mp->parser_rules_actor_fields;
                } else if ($camp_type == 2) {
                    $rules_fields = $this->mp->parser_urls_rules_fields;
                }
            }

            if (!$parser_rules_type) {
                $parser_rules_type = $this->mp->parser_rules_type;
            }

            $rules = $this->mp->sort_reg_rules_by_weight($rules, $rules_fields);

            $disabled = '';
            if (!$edit) {
                $disabled = ' disabled ';
                $title = __('Rules parser');
                ?>
                <h2><?php print $title ?></h2>            
            <?php } ?>
            <table id="rules" class="wp-list-table widefat striped table-view-list">
                <thead>
                    <tr>
                        <th><?php print __('Id') ?></th>
                        <th><?php print __('Field') ?></th>
                        <th><?php print __('Name') ?></th>
                        <th><?php print __('Type') ?></th> 
                        <th><?php print __('Rule') ?></th>
                        <th><?php print __('Match') ?></th>
                        <th><?php print __('New') ?></th>
                        <th><?php print __('Strip tags') ?></th>
                        <th><?php print __('Comment') ?></th>                        
                        <th><?php print __('Weight') ?></th> 
                        <th><?php print __('Active') ?></th>
                        <?php if ($edit): ?>
                            <th><?php print __('Remove') ?></th> 
                        <?php endif ?>
                        <?php if ($check): ?>
                            <th><?php print __('Check') ?></th> 
                        <?php endif ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($rules) { ?>
                        <?php foreach ($rules as $rid => $rule) {
                            ?>
                            <tr>
                                <td>
                                    <?php print $rid ?>
                                    <input type="hidden" name="rule_reg_id_<?php print $rid ?>" value="<?php print $rid ?>">
                                </td>
                                <td>
                                    <select name="rule_reg_f_<?php print $rid ?>" class="condition"<?php print $disabled ?>>
                                        <?php
                                        $con = $rule['f'];
                                        foreach ($rules_fields as $key => $name) {
                                            $selected = ($key == $con) ? 'selected' : '';
                                            ?>
                                            <option value="<?php print $key ?>" <?php print $selected ?> ><?php print $name ?></option>                                
                                            <?php
                                        }
                                        ?>                          
                                    </select>     
                                </td>
                                <td>
                                    <input type="text" name="rule_reg_k_<?php print $rid ?>" class="rule_k" value="<?php print $rule['k'] ?>"<?php print $disabled ?>>
                                </td>
                                <td>
                                    <select name="rule_reg_t_<?php print $rid ?>" class="condition"<?php print $disabled ?>>
                                        <?php
                                        $con = $rule['t'];
                                        foreach ($parser_rules_type as $key => $name) {
                                            $selected = ($key == $con) ? 'selected' : '';
                                            ?>
                                            <option value="<?php print $key ?>" <?php print $selected ?> ><?php print $name ?></option>                                
                                            <?php
                                        }
                                        ?>                          
                                    </select>     
                                </td>
                                <td>
                                    <input type="text" name="rule_reg_r_<?php print $rid ?>" class="reg" value="<?php print htmlspecialchars(base64_decode($rule['r'])) ?>"<?php print $disabled ?>>
                                </td>
                                <td>
                                    <input type="text" name="rule_reg_m_<?php print $rid ?>" class="rule_m" value="<?php print $rule['m'] ?>"<?php print $disabled ?>>
                                </td>
                                <td>
                                    <?php
                                    $checked = '';
                                    $active = isset($rule['n']) ? $rule['n'] : '';
                                    if ($active) {
                                        $checked = 'checked="checked"';
                                    }
                                    ?>
                                    <input type="checkbox" name="rule_reg_n_<?php print $rid ?>" value="1" <?php print $checked ?> <?php print $disabled ?>>                                    
                                </td>
                                <td>
                                    <?php
                                    $checked = '';
                                    $active = isset($rule['s']) ? $rule['s'] : '';
                                    if ($active) {
                                        $checked = 'checked="checked"';
                                    }
                                    ?>
                                    <input type="checkbox" name="rule_reg_s_<?php print $rid ?>" value="1" <?php print $checked ?> <?php print $disabled ?>>                                    
                                </td>
                                <td>
                                    <input type="text" name="rule_reg_c_<?php print $rid ?>" class="rule_c" value="<?php print $rule['c'] ?>"<?php print $disabled ?>>
                                </td>
                                <td>
                                    <input type="text" name="rule_reg_w_<?php print $rid ?>" class="rule_w" value="<?php print $rule['w'] ?>"<?php print $disabled ?>>
                                </td>
                                <td>
                                    <?php
                                    $checked = '';
                                    $active = isset($rule['a']) ? $rule['a'] : '';
                                    if ($active) {
                                        $checked = 'checked="checked"';
                                    }
                                    ?>
                                    <input type="checkbox" name="rule_reg_a_<?php print $rid ?>" value="1" <?php print $checked ?> <?php print $disabled ?>>                                    
                                </td>

                                <?php if ($edit): ?>
                                    <td>
                                        <input type="checkbox" name="remove_reg_rule[]" value="<?php print $rid ?>">
                                    </td>
                                <?php endif ?>
                                <?php if ($check): ?>
                                    <td>
                                        <?php
                                        if (isset($check[$rid])) {
                                            print 'Match';
                                        }
                                        ?>
                                    </td>
                                <?php endif ?>
                            </tr> 
                        <?php } ?>
                        <?php
                    }
                    if ($edit) {
                        ?>
                        <tr>                            
                            <td colspan="12"><b><?php print __('Add a new rule') ?></b></td>        
                        </tr>
                        <tr>
                            <td></td>
                            <td>
                                <select name="reg_new_rule_f" class="condition">
                                    <?php foreach ($rules_fields as $key => $name) { ?>
                                        <option value="<?php print $key ?>"><?php print $name ?></option>                                
                                        <?php
                                    }
                                    ?>                          
                                </select> 
                            </td>
                            <td>
                                <input type="text" name="reg_new_rule_k" class="rule_k" value="" placeholder="Name">
                                <div class="desc">
                                    For custom fields.
                                </div>
                            </td>
                            <td>
                                <select name="reg_new_rule_t" class="condition">
                                    <?php foreach ($parser_rules_type as $key => $name) { ?>
                                        <option value="<?php print $key ?>"><?php print $name ?></option>                                
                                        <?php
                                    }
                                    ?>                          
                                </select> 
                            </td>
                            <td>
                                <input type="text" name="reg_new_rule_r" class="reg" value="" placeholder="Enter a rule">
                                <div class="desc">
                                    Example XPath: //div[@class='content']<br />
                                    Example Regexp (match/replace): /(pattern)/Uis
                                </div>
                            </td>
                            <td>
                                <input type="text" name="reg_new_rule_m" class="rule_m" value="" placeholder="Match field number">
                                <div class="desc">
                                    Example: $1 $2<br />Default: empty
                                </div>
                            </td>
                            <td>
                                <input type="checkbox" name="reg_new_rule_n" value="1">
                                <div class="desc">
                                    Append <br />a new field
                                </div>
                            </td>
                            <td>
                                <input type="checkbox" name="reg_new_rule_s" value="1">
                            </td>
                            <td>
                                <input type="text" name="reg_new_rule_c" class="rule_c" value="" placeholder="Comment">
                            </td>
                            <td>
                                <input type="text" name="reg_new_rule_w" class="rule_w" value="0">
                            </td>
                            <td>
                                <input type="checkbox" name="reg_new_rule_a" value="1" checked="checked">
                            </td>
                            <td></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>    <?php
        }
    }

    public function preview_parser_links($campaign) {
        $options = $this->mp->get_options($campaign);
        $o = $options['links'];

        $parsing_data = $this->preview_parsing($campaign);

        $preivew_data = array();

        if ($parsing_data) {
            $preivew_data = $this->mp->find_url_posts_links($parsing_data, $o);
        } else {
            return -1;
        }

        return $preivew_data;
    }

    public function preview_links_urls($preivew_data) {

        if ($preivew_data == -1) {
            print '<p>No posts found</p>';
        } else if ($preivew_data) {
            $ma = $this->ml->get_ma();
            $col_span = 6;
            ?>
            <h3>Find links result:</h3>
            <table class="wp-list-table widefat striped table-view-list">
                <thead>
                    <tr>
                        <th></th>   

                        <?php
                        foreach ($preivew_data as $uid => $items) {
                            foreach ($items as $item) {
                                $fields = $item['fields'];
                                foreach ($fields as $key => $value) {
                                    $col_span += 1;
                                    ?>
                                    <th><?php print $key ?></th>             
                                    <?php
                                }
                                break;
                            }
                            break;
                        }
                        ?>  
                        <th><?php print __('Match') ?></th>
                        <th><?php print __('Rating') ?></th>
                        <th><?php print __('Valid') ?></th>
                        <th><?php print __('Top') ?></th>
                        <th><?php print __('Add URL') ?></th>
                    </tr>

                </thead>
                <tbody>
                    <?php
                    foreach ($preivew_data as $uid => $items) {
                        $url = $this->mp->get_url($uid);

                        $m = $ma->get_movie_by_id($url->pid);
                        $movie_title = $m->title . ' [' . $m->year . ']';
                        ?>

                        <tr>
                            <td colspan="<?php print $col_span ?>">
                                <h3><?php print $this->mla->theme_parser_url_link($url->id, $movie_title); ?></h3>
                            </td>
                        </tr>
                        <?php
                        foreach ($items as $item) {
                            $post = $item['post'];
                            $fields = $item['fields'];
                            $results = $item['results'];
                            $post_title = $post->title;
                            foreach ($results as $mid => $data) {
                                ?>
                                <tr>
                                    <td><?php print __('Input') ?></td>             
                                    <?php foreach ($fields as $key => $value) { ?>
                                        <td><?php
                                            $field = $data[$key]['data'];
                                            if (is_array($field)) {
                                                $field = implode(', ', $field);
                                            }
                                            print $field;
                                            ?>
                                        </td>             
                                    <?php } ?>       
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <?php
                                break;
                            }
                            break;
                        }


                        foreach ($items as $item) {

                            $post = $item['post'];
                            $fields = $item['fields'];
                            $results = $item['results'];
                            $post_title = $post->title;

                            if (!$results) {
                                print '<p>Results not found</p>';
                                continue;
                            }
                            ?>

                            <?php foreach ($results as $mid => $data) { ?>
                                <tr>
                                    <td><?php print __('Found') ?></td>             
                                    <?php foreach ($fields as $key => $value) { ?>
                                        <td><?php print $value ?></td>             
                                    <?php } ?>      
                                    <td><?php print $data['total']['match'] ?></td>
                                    <td><?php print $data['total']['rating'] ?></td>
                                    <td><?php print $data['total']['valid'] ?></td>
                                    <td><?php print $data['total']['top'] ?></td>
                                    <td>
                                        <?php if ($data['total']['valid']) { ?>
                                            <a target="_blank" href="<?php print $post->url ?>"><?php print $post->url ?></a>
                                        <?php } ?>
                                    </td>
                                </tr>
                            <?php } ?>

                            <?php
                        }
                    }
                    ?>
                </tbody>        
            </table>
            <br />
        <?php } else { ?>
            <h3>No results</h3>
            <p>Check regexp rules.</p>
            <?php
        }
    }

    /*
     * Rules links
     */

    public function preview_links($campaign) {
        $options = $this->mp->get_options($campaign);
        $o = $options['links'];
        $count = $o['pr_num'];
        $cid = $campaign->id;
        $last_posts = $this->mp->get_last_posts($count, $cid, -1, 1);
        $preivew_data = array();

        if ($last_posts) {
            $o = $options['links'];
            $preivew_data = $this->mp->find_posts_links($last_posts, $o, $campaign->type);
        } else {
            return -1;
        }

        return $preivew_data;
    }

    public function preview_links_search($preivew_data) {

        if ($preivew_data == -1) {
            print '<p>No posts found</p>';
        } else if ($preivew_data) {
            ?>
            <h3>Find links result:</h3>
            <?php
            foreach ($preivew_data as $id => $item) {

                $post = $item['post'];
                $fields = $item['fields'];
                $results = $item['results'];
                $post_title = $post->title . ' [' . $post->id . ']';
                ?>
                <h3><?php print $this->mla->theme_parser_url_link($post->uid, $post_title); ?></h3>
                <?php
                if (!$results) {
                    print '<p>Results not found</p>';
                    continue;
                }
                ?>

                <table class="wp-list-table widefat striped table-view-list">
                    <thead>
                        <tr>
                            <th></th>             
                            <?php foreach ($fields as $key => $value) { ?>
                                <th><?php print $key ?></th>             
                            <?php } ?>  
                            <th><?php print __('Match') ?></th>
                            <th><?php print __('Rating') ?></th>
                            <th><?php print __('Valid') ?></th>
                            <th><?php print __('Top') ?></th>
                        </tr>

                    </thead>
                    <tbody>
                        <tr>
                            <td><?php print __('Input') ?></td>             
                            <?php foreach ($fields as $key => $value) { ?>
                                <td><?php print $value ?></td>             
                            <?php } ?> 
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr> 
                        <?php foreach ($results as $mid => $data) { ?>
                            <tr>
                                <td><?php print $mid ?></td>             
                                <?php foreach ($fields as $key => $value) { ?>
                                    <td><?php
                                        $field = $data[$key]['data'];
                                        if (is_array($field)) {
                                            $field = implode(', ', $field);
                                        }
                                        print $field;
                                        ?>
                                    </td>             
                                <?php } ?>       
                                <td><?php print $data['total']['match'] ?></td>
                                <td><?php print $data['total']['rating'] ?></td>
                                <td><?php print $data['total']['valid'] ?></td>
                                <td><?php print $data['total']['top'] ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>        
                </table>
                <br />
            <?php } ?>
        <?php } else { ?>
            <h3>No results</h3>
            <p>Check regexp rules.</p>
            <?php
        }
    }

    public function show_links_rules($rules = array(), $data_fields = array(), $camp_type = 0, $edit = true, $check = array()) {
        if ($rules || $edit) {
            $rules = $this->mp->sort_link_rules_by_weight($rules, $camp_type);
            $links_rules_fields = $this->mp->links_rules_fields;
            if ($camp_type == 1) {
                $links_rules_fields = $this->mp->links_rules_actor_fields;
            }
            $disabled = '';
            if (!$edit) {
                $disabled = ' disabled ';
                $title = __('Link rules');
                ?>
                <h2><?php print $title ?></h2>            
            <?php } ?>
            <table id="rules" class="wp-list-table widefat striped table-view-list">
                <thead>
                    <tr>
                        <th><?php print __('Id') ?></th>
                        <th><?php print __('Movie field') ?></th>
                        <th><?php print __('Type') ?></th> 
                        <th><?php print __('Rule*') ?></th>
                        <th><?php print __('Match*') ?></th>
                        <th><?php print __('Equals') ?></th>
                        <th><?php print __('Data field') ?></th>                        
                        <th><?php print __('Multi*') ?></th>
                        <th><?php print __('Rating*') ?></th>
                        <th><?php print __('Comment') ?></th>                        
                        <th><?php print __('Weight') ?></th> 
                        <th><?php print __('Active') ?></th>
                        <?php if ($edit): ?>
                            <th><?php print __('Remove') ?></th> 
                        <?php endif ?>
                        <?php if ($check): ?>
                            <th><?php print __('Check') ?></th> 
                        <?php endif ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($rules) { ?>
                        <?php foreach ($rules as $rid => $rule) {
                            ?>
                            <tr>
                                <td>
                                    <?php print $rid ?>
                                    <input type="hidden" name="rule_reg_id_<?php print $rid ?>" value="<?php print $rid ?>">
                                </td>
                                <td>
                                    <select name="rule_reg_f_<?php print $rid ?>" class="condition"<?php print $disabled ?>>
                                        <?php
                                        $con = $rule['f'];
                                        foreach ($links_rules_fields as $key => $name) {
                                            $selected = ($key == $con) ? 'selected' : '';
                                            ?>
                                            <option value="<?php print $key ?>" <?php print $selected ?> ><?php print $name ?></option>                                
                                            <?php
                                        }
                                        ?>                          
                                    </select>     
                                </td>
                                <td>
                                    <select name="rule_reg_t_<?php print $rid ?>" class="condition"<?php print $disabled ?>>
                                        <?php
                                        $con = $rule['t'];
                                        foreach ($this->mp->link_rules_type as $key => $name) {
                                            $selected = ($key == $con) ? 'selected' : '';
                                            ?>
                                            <option value="<?php print $key ?>" <?php print $selected ?> ><?php print $name ?></option>                                
                                            <?php
                                        }
                                        ?>                          
                                    </select>     
                                </td>
                                <td>
                                    <input type="text" name="rule_reg_r_<?php print $rid ?>" class="reg" value="<?php print htmlspecialchars(base64_decode($rule['r'])) ?>"<?php print $disabled ?>>
                                </td>
                                <td>
                                    <input type="text" name="rule_reg_m_<?php print $rid ?>" class="rule_m" value="<?php print $rule['m'] ?>"<?php print $disabled ?>>
                                </td>
                                <td>
                                    <select name="rule_reg_e_<?php print $rid ?>" class="condition"<?php print $disabled ?>>
                                        <?php
                                        $con = $rule['e'];
                                        foreach ($this->mp->links_match_type as $key => $name) {
                                            $selected = ($key == $con) ? 'selected' : '';
                                            ?>
                                            <option value="<?php print $key ?>" <?php print $selected ?> ><?php print $name ?></option>                                
                                            <?php
                                        }
                                        ?>                          
                                    </select>     
                                </td>
                                <td>
                                    <select name="rule_reg_d_<?php print $rid ?>" class="condition"<?php print $disabled ?>>
                                        <?php
                                        if ($data_fields) {
                                            $con = $rule['d'];
                                            foreach ($data_fields as $key => $name) {
                                                $selected = ($key == $con) ? 'selected' : '';
                                                ?>
                                                <option value="<?php print $key ?>" <?php print $selected ?> ><?php print $name ?></option>                                
                                                <?php
                                            }
                                        }
                                        ?>                          
                                    </select>  
                                </td>
                                <td>
                                    <input type="text" name="rule_reg_mu_<?php print $rid ?>" class="rule_m" value="<?php print $rule['mu'] ?>"<?php print $disabled ?>>
                                </td>
                                <td>
                                    <input type="text" name="rule_reg_ra_<?php print $rid ?>" class="rule_m" value="<?php print $rule['ra'] ?>"<?php print $disabled ?>>
                                </td>
                                <td>
                                    <input type="text" name="rule_reg_c_<?php print $rid ?>" class="rule_m" value="<?php print $rule['c'] ?>"<?php print $disabled ?>>
                                </td>
                                <td>
                                    <input type="text" name="rule_reg_w_<?php print $rid ?>" class="rule_w" value="<?php print $rule['w'] ?>"<?php print $disabled ?>>
                                </td>
                                <td>
                                    <?php
                                    $checked = '';
                                    $active = isset($rule['a']) ? $rule['a'] : '';
                                    if ($active) {
                                        $checked = 'checked="checked"';
                                    }
                                    ?>
                                    <input type="checkbox" name="rule_reg_a_<?php print $rid ?>" value="1" <?php print $checked ?> <?php print $disabled ?>>                                    
                                </td>

                                <?php if ($edit): ?>
                                    <td>
                                        <input type="checkbox" name="remove_reg_rule[]" value="<?php print $rid ?>">
                                    </td>
                                <?php endif ?>
                                <?php if ($check): ?>
                                    <td>
                                        <?php
                                        if (isset($check[$rid])) {
                                            print 'Match';
                                        }
                                        ?>
                                    </td>
                                <?php endif ?>
                            </tr> 
                        <?php } ?>
                        <?php
                    }
                    if ($edit) {
                        ?>
                        <tr>                            
                            <td colspan="12"><b><?php print __('Add a new rule') ?></b></td>        
                        </tr>
                        <tr>
                            <td></td>
                            <td>
                                <select name="reg_new_rule_f" class="condition">
                                    <?php foreach ($links_rules_fields as $key => $name) { ?>
                                        <option value="<?php print $key ?>"><?php print $name ?></option>                                
                                        <?php
                                    }
                                    ?>                          
                                </select> 
                            </td>
                            <td>
                                <select name="reg_new_rule_t" class="condition">
                                    <?php foreach ($this->mp->link_rules_type as $key => $name) { ?>
                                        <option value="<?php print $key ?>"><?php print $name ?></option>                                
                                        <?php
                                    }
                                    ?>                          
                                </select> 
                            </td>
                            <td>
                                <input type="text" name="reg_new_rule_r" class="reg" value="" placeholder="Enter a rule">
                            </td>
                            <td>
                                <input type="text" name="reg_new_rule_m" class="rule_m" value="" placeholder="Match field number">
                                <div class="desc">

                                </div>
                            </td>
                            <td>
                                <select name="reg_new_rule_e" class="condition">
                                    <?php foreach ($this->mp->links_match_type as $key => $name) { ?>
                                        <option value="<?php print $key ?>"><?php print $name ?></option>                                
                                        <?php
                                    }
                                    ?>                          
                                </select> 
                            </td>
                            <td>
                                <select name="reg_new_rule_d" class="condition">
                                    <?php
                                    if ($data_fields) {
                                        foreach ($data_fields as $key => $name) {
                                            ?>
                                            <option value="<?php print $key ?>"><?php print $name ?></option>                                
                                            <?php
                                        }
                                    }
                                    ?>                          
                                </select> 
                            </td>
                            <td>
                                <input type="text" name="reg_new_rule_mu" class="rule_m" value="" placeholder="Delimiler">                                
                            </td>
                            <td>
                                <input type="text" name="reg_new_rule_ra" class="rule_m" value="" placeholder="Rating if match">                                
                            </td>
                            <td>
                                <input type="text" name="reg_new_rule_c" class="rule_m" value="" placeholder="Comment">
                            </td>
                            <td>
                                <input type="text" name="reg_new_rule_w" class="rule_w" value="0">
                            </td>
                            <td>
                                <input type="checkbox" name="reg_new_rule_a" value="1" checked="checked">
                            </td>
                            <td></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table> 
            <p class="desc">
                *Rule example (match/replace): "/(pattern)/Uis". For explode use ",".<br />               
                *Match.  Example: "$1 $2" for regexp. Default: empty.<br />
                *Multi. If multifield add delimiter. Example: "," or ";". Default empty: single field.<br />
                *Rating. How many points the rule will get if it matches.
            </p>
            <?php
        }
    }

    private function links_rules_form($form_state) {
        $rule_exists = array();
        $to_remove = isset($form_state['remove_reg_rule']) ? $form_state['remove_reg_rule'] : array();

        $rule_keys = array('f', 't', 'r', 'm', 'e', 'd', 'mu', 'ra', 'c', 'w', 'a');

        // Exists rules
        foreach ($form_state as $name => $value) {
            if (strstr($name, 'rule_reg_id_')) {
                $key = $value;
                if (in_array($key, $to_remove)) {
                    continue;
                }
                $upd_rule = array();
                foreach ($rule_keys as $k) {
                    $form_name = 'rule_reg_' . $k . '_' . $key;
                    $form_value = isset($form_state[$form_name]) ? $form_state[$form_name] : $this->mp->get_def_link_rule($k);
                    if ($k == 'r') {
                        //Regexp encode
                        $form_value = base64_encode(stripslashes($form_value));
                    }
                    $upd_rule[$k] = $form_value;
                }
                $rule_exists[$key] = $upd_rule;
            }
        }

        // New rule
        if ($form_state['reg_new_rule_ra']) {

            $old_key = 0;
            if ($rule_exists) {
                krsort($rule_exists);
                $old_key = array_key_first($rule_exists);
            }
            $new_rule_key = $old_key + 1;

            $new_rule = array();
            foreach ($rule_keys as $k) {
                $form_name = 'reg_new_rule_' . $k;
                $form_value = isset($form_state[$form_name]) ? $form_state[$form_name] : $this->mp->get_def_link_rule($k);
                if ($k == 'r') {
                    //Regexp encode
                    $form_value = base64_encode(stripslashes($form_value));
                }
                $new_rule[$k] = $form_value;
            }


            $rule_exists[$new_rule_key] = $new_rule;
        }

        ksort($rule_exists);

        return $rule_exists;
    }

    /*
     * Create URLs
     */

    public function show_create_urls_rules($rules = array(), $data_fields = array(), $camp_type = 0, $edit = true, $check = array()) {
        if ($rules || $edit) {
            //$rules = $this->mp->sort_link_rules_by_weight($rules, $camp_type);

            $campaigns = $this->mp->get_campaigns();

            $disabled = '';
            if (!$edit) {
                $disabled = ' disabled ';
                $title = __('Create URLs rules');
                ?>
                <h2><?php print $title ?></h2>            
            <?php } ?>
            <table id="rules" class="wp-list-table widefat striped table-view-list">
                <thead>
                    <tr>
                        <th><?php print __('Id') ?></th>
                        <th><?php print __('Data field') ?></th>
                        <th><?php print __('Multi*') ?></th>
                        <th><?php print __('Type') ?></th> 
                        <th><?php print __('Rule*') ?></th>
                        <th><?php print __('Match*') ?></th>
                        <th><?php print __('Campaign*') ?></th>
                        <th><?php print __('Comment') ?></th>                        
                        <th><?php print __('Weight') ?></th> 
                        <th><?php print __('Active') ?></th>
                        <?php if ($edit): ?>
                            <th><?php print __('Remove') ?></th> 
                        <?php endif ?>
                        <?php if ($check): ?>
                            <th><?php print __('Check') ?></th> 
                        <?php endif ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($rules) { ?>
                        <?php foreach ($rules as $rid => $rule) {
                            ?>
                            <tr>
                                <td>
                                    <?php print $rid ?>
                                    <input type="hidden" name="rule_reg_id_<?php print $rid ?>" value="<?php print $rid ?>">
                                </td>
                                <td>
                                    <select name="rule_reg_d_<?php print $rid ?>" class="condition"<?php print $disabled ?>>
                                        <?php
                                        if ($data_fields) {
                                            $con = $rule['d'];
                                            foreach ($data_fields as $key => $name) {
                                                $selected = ($key == $con) ? 'selected' : '';
                                                ?>
                                                <option value="<?php print $key ?>" <?php print $selected ?> ><?php print $name ?></option>                                
                                                <?php
                                            }
                                        }
                                        ?>                          
                                    </select>  
                                </td>
                                <td>
                                    <input type="text" name="rule_reg_mu_<?php print $rid ?>" class="rule_m" value="<?php print $rule['mu'] ?>"<?php print $disabled ?>>
                                </td>
                                <td>
                                    <select name="rule_reg_t_<?php print $rid ?>" class="condition"<?php print $disabled ?>>
                                        <?php
                                        $con = $rule['t'];
                                        foreach ($this->mp->link_rules_type as $key => $name) {
                                            $selected = ($key == $con) ? 'selected' : '';
                                            ?>
                                            <option value="<?php print $key ?>" <?php print $selected ?> ><?php print $name ?></option>                                
                                            <?php
                                        }
                                        ?>                          
                                    </select>     
                                </td>
                                <td>
                                    <input type="text" name="rule_reg_r_<?php print $rid ?>" class="reg" value="<?php print htmlspecialchars(base64_decode($rule['r'])) ?>"<?php print $disabled ?>>
                                </td>
                                <td>
                                    <input type="text" name="rule_reg_m_<?php print $rid ?>" class="rule_m" value="<?php print $rule['m'] ?>"<?php print $disabled ?>>
                                </td>


                                <td>
                                    <select name="rule_reg_ra_<?php print $rid ?>" class="condition"<?php print $disabled ?>>
                                        <option value="0" >Select</option>
                                        <?php
                                        if ($campaigns) {
                                            $con = $rule['ra'];
                                            foreach ($campaigns as $item) {
                                                $key = $item->id;
                                                $name = "[$key] " . $item->title;
                                                $selected = ($key == $con) ? 'selected' : '';
                                                ?>
                                                <option value="<?php print $key ?>" <?php print $selected ?> ><?php print $name ?></option>                                
                                                <?php
                                            }
                                        }
                                        ?>                          
                                    </select> 
                                </td>
                                <td>
                                    <input type="text" name="rule_reg_c_<?php print $rid ?>" class="rule_m" value="<?php print $rule['c'] ?>"<?php print $disabled ?>>
                                </td>
                                <td>
                                    <input type="text" name="rule_reg_w_<?php print $rid ?>" class="rule_w" value="<?php print $rule['w'] ?>"<?php print $disabled ?>>
                                </td>
                                <td>
                                    <?php
                                    $checked = '';
                                    $active = isset($rule['a']) ? $rule['a'] : '';
                                    if ($active) {
                                        $checked = 'checked="checked"';
                                    }
                                    ?>
                                    <input type="checkbox" name="rule_reg_a_<?php print $rid ?>" value="1" <?php print $checked ?> <?php print $disabled ?>>                                    
                                </td>

                                <?php if ($edit): ?>
                                    <td>
                                        <input type="checkbox" name="remove_reg_rule[]" value="<?php print $rid ?>">
                                    </td>
                                <?php endif ?>
                                <?php if ($check): ?>
                                    <td>
                                        <?php
                                        if (isset($check[$rid])) {
                                            print 'Match';
                                        }
                                        ?>
                                    </td>
                                <?php endif ?>
                            </tr> 
                        <?php } ?>
                        <?php
                    }
                    if ($edit) {
                        ?>
                        <tr>                            
                            <td colspan="12"><b><?php print __('Add a new rule') ?></b></td>        
                        </tr>
                        <tr>
                            <td></td>

                            <td>
                                <select name="reg_new_rule_d" class="condition">
                                    <?php
                                    if ($data_fields) {
                                        foreach ($data_fields as $key => $name) {
                                            ?>
                                            <option value="<?php print $key ?>"><?php print $name ?></option>                                
                                            <?php
                                        }
                                    }
                                    ?>                          
                                </select> 
                            </td>
                            <td>
                                <input type="text" name="reg_new_rule_mu" class="rule_m" value="" placeholder="Delimiler">                                
                            </td>


                            <td>
                                <select name="reg_new_rule_t" class="condition">
                                    <?php foreach ($this->mp->link_rules_type as $key => $name) { ?>
                                        <option value="<?php print $key ?>"><?php print $name ?></option>                                
                                        <?php
                                    }
                                    ?>                          
                                </select> 
                            </td>
                            <td>
                                <input type="text" name="reg_new_rule_r" class="reg" value="" placeholder="Enter a rule">
                            </td>
                            <td>
                                <input type="text" name="reg_new_rule_m" class="rule_m" value="" placeholder="Match field number">
                            </td>

                            <td>

                                <select name="reg_new_rule_ra" class="condition"<?php print $disabled ?>>
                                    <option value="0">Select</option>
                                    <?php
                                    if ($campaigns) {
                                        foreach ($campaigns as $item) {
                                            $key = $item->id;
                                            $name = "[$key] " . $item->title;
                                            ?>
                                            <option value="<?php print $key ?>" ><?php print $name ?></option>                                
                                            <?php
                                        }
                                    }
                                    ?>                          
                                </select>                                 
                            </td>
                            <td>
                                <input type="text" name="reg_new_rule_c" class="rule_m" value="" placeholder="Comment">
                            </td>
                            <td>
                                <input type="text" name="reg_new_rule_w" class="rule_w" value="0">
                            </td>
                            <td>
                                <input type="checkbox" name="reg_new_rule_a" value="1" checked="checked">
                            </td>
                            <td></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table> 
            <p class="desc">
                *Rule example (match/replace): "/(pattern)/Uis". For explode use ",".<br />               
                *Match.  Example: "$1 $2" for regexp. Default: empty.<br />
                *Multi. If multifield add delimiter. Example: "," or ";". Default empty: single field.<br />
                *Campaign. Where new URLs generated.
            </p>
            <?php
        }
    }

    public function preview_create_found_urls($preivew_data) {
        if ($preivew_data == -1) {
            print '<p>No posts found</p>';
        } else if ($preivew_data) {
            ?>
            <h3>Find URLs result:</h3>
            <?php
            foreach ($preivew_data as $id => $item) {

                $post = $item['post'];
                $results = $item['active_rules'];
                $post_title = $post->title . ' [' . $post->id . ']';
                ?>
                <h3><?php print $this->mla->theme_parser_url_link($post->uid, $post_title); ?></h3>
                <?php
                if (!$results) {
                    print '<p>Results not found</p>';
                    continue;
                }
                ?>
                <table class="wp-list-table widefat striped table-view-list">
                    <thead>
                        <tr>     
                            <th><?php print __('Data field') ?></th>
                            <th><?php print __('Rule') ?></th>
                            <th><?php print __('Results') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $fkey => $data) { ?>
                            <?php foreach ($data as $i => $item) { ?>
                                <tr>
                                    <td><?php print $item['title'] ?></td>                                            
                                    <td><?php print $i ?></td>
                                    <td><?php print implode('<br />', $item['content']) ?></td>                                
                                </tr>
                            <?php } ?>
                        <?php } ?>
                    </tbody>        
                </table>
                <br />
            <?php } ?>
        <?php } else { ?>
            <h3>No results</h3>
            <p>Check regexp rules.</p>
            <?php
        }
    }

    /*
     * URLs
     */

    private function add_urls($id, $add_urls) {
        if (strstr($add_urls, "\n")) {
            $list = explode("\n", $add_urls);
        } else {
            $list = array($add_urls);
        }

        $total_add = 0;

        foreach ($list as $url) {
            $url = trim($url);
            if ($url) {
                if ($this->mp->add_url($id, $url)) {
                    $total_add += 1;
                }
            }
        }
        if ($total_add > 0) {
            $message = 'Urls added from list: ' . $total_add;
            $this->mp->log_info($message, $id, 0, 1);
        }
    }

    public function get_url_status($status) {
        return isset($this->url_status[$status]) ? $this->url_status[$status] : 'None';
    }

    public function get_url_status_count($cid = 0, $aid = 0) {
        $status = -1;
        $count = $this->mp->get_urls_count($status, $cid);
        $states = array(
            '-1' => array(
                'title' => 'All',
                'count' => $count
            )
        );
        foreach ($this->url_status as $key => $value) {
            $states[$key] = array(
                'title' => $value,
                'count' => $this->mp->get_urls_count($key, $cid));
        }
        return $states;
    }

    public function get_post_arhive_types($cid = 0, $status = -1) {

        $count = $this->mp->get_urls_count($status, $cid);
        $states = array(
            '-1' => array(
                'title' => 'All',
                'count' => $count
            )
        );
        foreach ($this->post_arhive_status as $key => $value) {
            $states[$key] = array(
                'title' => $value,
                'count' => $this->mp->get_urls_count($status, $cid, $key));
        }
        return $states;
    }

    public function get_post_parser_types($cid = 0, $status = -1, $arhive_type = -1) {

        $count = $this->mp->get_urls_count($status, $cid, $arhive_type);
        $states = array(
            '-1' => array(
                'title' => 'All',
                'count' => $count
            )
        );
        foreach ($this->post_parse_status_tab as $key => $value) {
            $states[$key] = array(
                'title' => $value,
                'count' => $this->mp->get_urls_count($status, $cid, $arhive_type, $key));
        }
        return $states;
    }

    public function get_post_links_types($cid = 0, $status = -1, $arhive_type = -1, $parser_type = -1) {

        $count = $this->mp->get_urls_count($status, $cid, $arhive_type, $parser_type);
        $states = array(
            '-1' => array(
                'title' => 'All',
                'count' => $count
            )
        );
        foreach ($this->post_link_status as $key => $value) {
            $states[$key] = array(
                'title' => $value,
                'count' => $this->mp->get_urls_count($status, $cid, $arhive_type, $parser_type, $key));
        }
        return $states;
    }

    public function get_post_log_status($cid = 0) {

        $count = $this->mp->get_log_count($cid);
        $states = array(
            '-1' => array(
                'title' => 'All',
                'count' => $count
            )
        );
        foreach ($this->log_status as $key => $value) {
            $states[$key] = array(
                'title' => $value,
                'count' => $this->mp->get_log_count($cid, $key));
        }
        return $states;
    }

    public function get_post_log_types($cid = 0, $status = -1) {

        $count = $this->mp->get_log_count($cid, $status);
        $states = array(
            '-1' => array(
                'title' => 'All',
                'count' => $count
            )
        );
        foreach ($this->log_type as $key => $value) {
            $states[$key] = array(
                'title' => $value,
                'count' => $this->mp->get_log_count($cid, $status, $key));
        }
        return $states;
    }

    public function campaign_find_urls_submit($form_state) {

        if ($form_state['id']) {
            $id = $form_state['id'];
            if ($form_state['find_urls']) {
                // Find URLs               
                $campaign = $this->mp->get_campaign($id);
                $opt_prev = $this->mp->get_options($campaign);
                $find_urls_prev = $opt_prev['find_urls'];

                $find_urls = array();
                foreach ($find_urls_prev as $key => $value) {
                    if (isset($form_state[$key])) {
                        if ($key == 'first' || $key == 'page' || $key == 'match') {
                            $find_urls[$key] = base64_encode(stripslashes($form_state[$key]));
                        } else {
                            $find_urls[$key] = $form_state[$key];
                        }
                    } else {
                        $find_urls[$key] = $value;
                    }
                }

                $options = $opt_prev;
                $options['find_urls'] = $find_urls;

                $this->mp->update_campaign_options($id, $options);
            } else if ($form_state['cron_urls']) {
                $campaign = $this->mp->get_campaign($id);
                $opt_prev = $this->mp->get_options($campaign);
                $urls_prev = $opt_prev['cron_urls'];
                $urls = array();
                foreach ($urls_prev as $key => $value) {
                    if (isset($form_state[$key])) {
                        if ($key == 'page' || $key == 'match') {
                            $urls[$key] = base64_encode(stripslashes($form_state[$key]));
                        } else {
                            $urls[$key] = $form_state[$key];
                        }
                    } else {
                        $urls[$key] = $value;
                    }
                }

                $checkbox_fields = array('status');
                foreach ($checkbox_fields as $field) {
                    $urls[$field] = isset($form_state[$field]) ? $form_state[$field] : 0;
                }

                $options = $opt_prev;
                $options['cron_urls'] = $urls;
                $this->mp->update_campaign_options($id, $options);
            } else if ($form_state['add_urls']) {
                // Add URLs
                $this->add_urls($id, $form_state['add_urls']);
            } else if ($form_state['generate_urls']) {
                // Generage URLs
                $campaign = $this->mp->get_campaign($id);
                $opt_prev = $this->mp->get_options($campaign);
                $urls_prev = $opt_prev['gen_urls'];

                $find_urls = array();
                foreach ($urls_prev as $key => $value) {
                    if (isset($form_state[$key])) {
                        if ($key == 'page' || $key == 'regexp') {
                            $find_urls[$key] = base64_encode(stripslashes($form_state[$key]));
                        } else {
                            $find_urls[$key] = $form_state[$key];
                        }
                    } else {
                        $find_urls[$key] = $value;
                    }
                }
                $checkbox_fields = array('status');
                foreach ($checkbox_fields as $field) {
                    $find_urls[$field] = isset($form_state[$field]) ? $form_state[$field] : 0;
                }

                $reset = isset($form_state['reset']) ? true : false;
                if ($reset) {
                    $find_urls['last_id'] = 0;
                }

                $options = $opt_prev;
                $options['gen_urls'] = $find_urls;

                $this->mp->update_campaign_options($id, $options);
            } else if ($form_state['service_urls']) {
                $campaign = $this->mp->get_campaign($id);
                $opt_prev = $this->mp->get_options($campaign);
                $urls_prev = $opt_prev['service_urls'];
                $urls = array();
                foreach ($urls_prev as $key => $value) {
                    if (isset($form_state[$key])) {
                        $urls[$key] = $form_state[$key];
                    } else {
                        $urls[$key] = $value;
                    }
                }
                $checkbox_fields = array('del_pea');
                foreach ($checkbox_fields as $field) {
                    $urls[$field] = isset($form_state[$field]) ? $form_state[$field] : 0;
                }

                $options = $opt_prev;
                $options['service_urls'] = $urls;
                $this->mp->update_campaign_options($id, $options);
            }
        }
    }

    /*
     * Generate URLs
     */

    public function get_name_templates() {
        $ma = $this->ml->get_ma();
        $posts = $ma->get_posts();
        $post = array_shift($posts);
        $post = $this->mp->get_post_custom_fields($post);
        $ret = array();
        if ($post) {
            foreach ($post as $key => $value) {
                $ret[$key] = $value;
            }
        }
        return $ret;
    }

    public function get_actors_templates() {
        $ma = $this->ml->get_ma();
        $actors = $ma->get_actors();
        $actor = array_shift($actors);
        $ret = array();
        if ($actor) {
            foreach ($actor as $key => $value) {
                $ret[$key] = $value;
            }
        }
        return $ret;
    }

    /*
     * Log
     */

    public function get_log_type($type) {
        return isset($this->log_type[$type]) ? $this->log_type[$type] : 'None';
    }

    public function get_log_status($type) {
        return isset($this->log_status[$type]) ? $this->log_status[$type] : 'None';
    }

    public function get_last_log($url_id = 0, $parser_id = 0, $status = -1) {

        $result = $this->mp->last_log_result($url_id, $parser_id, $status);
        $str = '';
        if ($result) {
            $str = $this->get_log_type($result->type) . ': ' . $this->get_log_status($result->status);
            if ($result->message) {
                $str = $str . ' | ' . $result->message;
            }
        }
        return $str;
    }

    /*
     * Other
     */

    public function change_url_state($id, $status = 0) {
        $sql = sprintf("SELECT status FROM {$this->db['url']} WHERE id=%d", $id);
        $old_status = $this->db_get_var($sql);
        if ($old_status != $status) {
            $sql = sprintf("UPDATE {$this->db['url']} SET status=%d WHERE id=%d", $status, $id);
            $this->db_query($sql);
            return true;
        }
        return false;
    }

    public function delete_url($id) {
        $sql = sprintf("DELETE FROM {$this->db['url']} WHERE id=%d", (int) $id);
        $this->db_query($sql);
    }

}
