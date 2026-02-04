<?php

/**
 * Manage rss feeds
 *
 * @author brahman
 */
class CriticFeeds extends AbstractDBWp {

    // Critic matic
    private $cm;
    private $db;
    private $max_cron_time = 20;
    private $feed_settings = '';
    private $feed_settings_def = '';
    public $perpage = 30;
    public $update_interval = array(
        15 => 'Fifteen min',
        30 => 'Thirty min',
        60 => 'Hourly',
        120 => 'Two hours',
        720 => 'Twice daily',
        1440 => 'Daily'
    );
    public $feed_state = array(
        1 => 'Active',
        0 => 'Inactive',
        3 => 'Auto stopped',
        2 => 'Trash',
    );
    public $tabs = array(
        'home' => 'Feeds list',
        'posts' => 'Posts',
        'log' => 'Log',
        'settings' => 'Settings and rules',
        'rules' => 'Global rules test',
        'update' => 'Update',
        'add' => 'Add a new campaign',
    );
    public $campaign_tabs = array(
        'home' => 'Veiw',
        'posts' => 'Posts',
        'log' => 'Log',
        'edit' => 'Edit',
        'rules' => 'Rules test',
        'preview' => 'Preview feed',
        'update' => 'Update',
        'trash' => 'Trash',
    );
    public $rules_fields = array(
        't' => 'Title',
        'd' => 'Content',
        'c' => 'Category',
        'u' => 'URL'
    );
    public $rules_actions = array(
        0 => 'Draft',
        1 => 'Publish',
        2 => 'Trash',
    );
    public $rules_condition = array(
        1 => 'True',
        0 => 'False',
    );
    public $def_options;
    private $log_type = array(
        0 => 'Info',
        1 => 'Warning',
        2 => 'Error',
    );
    private $log_status = array(
        0 => 'No new posts',
        1 => 'Add post',
        2 => 'Wp error',
        3 => 'Simplepie error',
        4 => 'Auto stop',
        5 => 'Invalid post'
    );
    public $post_fields = array(
        't' => 'Title',
        'u' => 'Link',
        'c' => 'Category',
        'd' => 'Content',
    );

    public function __construct($cm = '') {
        $this->cm = $cm ? $cm : new CriticMatic();
        $table_prefix = DB_PREFIX_WP;
        $this->db = array(
            // Critic Feeds
            'campaign' => $table_prefix . 'critic_feed_campaign',
            'feed_meta' => $table_prefix . 'critic_feed_meta',
            'log' => $table_prefix . 'critic_feed_log',
        );

        $this->feed_settings_def = array(
            'critic_feeds_max_feed_error' => 100,
            'update_interval' => 60,
            'post_status' => 1,
            'rss_date' => 1,
            'rules' => '',
            'use_global_rules' => 1,
            'rt' => '',
            'body_len' => 200,
        );

        // Init settings
        $this->get_feed_settings();

        $this->def_options = array(
            'date' => $this->curr_time(),
            'last_update' => 0,
            'update_interval' => $this->feed_settings['update_interval'],
            'status' => 1,
            'title' => '',
            'feed_hash' => '',
            'feed' => '',
            'site' => '',
            'options' => array(
                'rss_date' => $this->feed_settings['rss_date'],
                'post_status' => $this->feed_settings['post_status'],
                'rules' => '',
                'use_global_rules' => $this->feed_settings['use_global_rules'],
                'rt' => '',
                'body_len' => $this->feed_settings['body_len'],
                'show_status' => 0,
            ),
            'critic_feeds_max_feed_error' => $this->feed_settings['critic_feeds_max_feed_error'],
        );
        $this->get_perpage();
    }

    /*
     * Core
     */

    public function run_cron() {

        $cron_option = 'feed_matic_cron_last_run';
        $last_run = $this->get_option($cron_option, 0);
        $currtime = $this->curr_time();

        $next_run = $last_run + 4 * 60;

        //validate next run
        if ($next_run > $currtime + 10 * 60) {
            $next_run = 0;
        }

        $count = 0;

        if ($currtime > $next_run) {
            $this->update_option($cron_option, $currtime);
            $count = $this->process_all();
            $this->update_option($cron_option, 0);
        }
        return $count;
    }

    public function process_all($force = false) {
        $campaigns = $this->get_campaigns();
        $count = 0;
        foreach ($campaigns as $campaign) {
            $count += $this->check_time_campaign($campaign, $force);
            $time = (int) $this->timer_stop(0);
            if ($time > $this->max_cron_time) {
                break;
            }
        }
        return $count;
    }

    public function get_campaigns() {
        $query = "SELECT * FROM {$this->db['campaign']} WHERE status = 1 ORDER BY id DESC";
        $result = $this->db_results($query);
        return $result;
    }

    public function check_time_campaign($campaign, $force = false) {
        $options = unserialize($campaign->options);
        $update_interval = $campaign->update_interval;
        $update_last_time = $campaign->last_update;

        $next_update = $update_last_time + $update_interval * 60;
        $currtime = $this->curr_time();

        $count = 0;

        if ($currtime > $next_update || $force) {
            $count = $this->process_campaign($campaign);
        }

        return $count;
    }

    public function process_campaign($campaign) {
        $currtime = $this->curr_time();
        $cid = $campaign->id;
        $options = unserialize($campaign->options);
        $feed = $campaign->feed;

        $simplepie = $this->fetch_feed($feed);
        $get_feeds = true;
        $settings = $this->get_feed_settings();

        //Valid campaign feed
        $feed_invalid = isset($options['feed_invalid']) ? $options['feed_invalid'] : -1;
        $show_in = isset($options['show_status']) ? $options['show_status'] : 0;

        //Wp error
        if (is_wp_error($simplepie)) {
            $msg = array();
            $msg[] = $simplepie->get_error_code();
            $msg[] = $simplepie->get_error_message(); //> Сообщение ошибки...
            $msg[] = $simplepie->get_error_data();    //> 404

            $error = implode('; ', $msg);
            $this->log_wp_error($error, $cid);
            $get_feeds = false;
        }

        if ($get_feeds) {
            //Simplepie error
            $error = $simplepie->error();
            if ($error) {
                if (is_array($error)) {
                    $error = implode('; ', $error);
                }
                $this->log_simplepie_error($error, $cid);
                $get_feeds = false;
            }
        }

        //TODO count and auto-disable invalid campaigns
        // Get posts (last is first)
        $count = 0;
        $last_hash = $campaign->last_hash;
        $first_hash = '';

        if (!$get_feeds) {
            if ($feed_invalid <= 0) {
                $feed_invalid = 1;
            } else {
                $feed_invalid += 1;
            }
            //Update campaign time
            $this->update_campaign_last_hash($cid, $currtime, $last_hash);

            //Update options
            $options['feed_invalid'] = $feed_invalid;
            $this->update_campaign_options($cid, $options);

            //If many errors, then stop the campaing
            if ($feed_invalid > 0) {

                $max_errors = $this->get_option('critic_feeds_max_feed_error', $this->def_options['critic_feeds_max_feed_error']);
                if ($feed_invalid >= $max_errors) {
                    //Stop the campaign
                    //$status = 3;
                    //$this->update_campaign_status($cid, $status);
                    //$this->log_auto_stop('Error count: ' . $feed_invalid, $cid);
                }
            }

            return $count;
        }

        //Get the post date from RSS. Default ON
        $rss_date = isset($options['rss_date']) ? $options['rss_date'] : $this->def_options['options']['rss_date'];

        foreach ($simplepie->get_items() as $item) {
            $permalink = $item->get_permalink();
            $permalink_hash = $this->link_hash($permalink);
            if (!$first_hash) {
                $first_hash = $permalink_hash;
            }

            // Check last hash
            //print "$first_hash == $permalink_hash<br />";
            if ($first_hash == $last_hash) {
                if ($count == 0) {
                    break;
                }
            }

            // Proccess item            
            $title = $this->escape_title($item->get_title());
            $content = $item->get_content();

            $view_type = $this->cm->get_post_view_type($permalink);


            if ($view_type == 0) {
                // Validate body len
                $valid_len = isset($options['body_len']) ? $options['body_len'] : $this->def_options['options']['body_len'];

                if (!$this->validate_body_len(strip_tags($content), $valid_len)) {
                    $this->log_invalid_post($title, $cid);
                    continue;
                }
            }

            $cat = '';
            $cat_arr = $item->get_categories();
            if ($cat_arr && sizeof($cat_arr)) {
                $terms = array();
                foreach ($cat_arr as $cat_obj) {
                    $terms[] = $cat_obj->term;
                }
                $cat = implode(', ', $terms);
            }


            //Type is RSS Feeds
            $type = 1;


            $date = 0;
            if ($rss_date) {
                $time_rss = $item->get_date('r');
                if ($time_rss) {
                    $date = strtotime($time_rss);
                }
            }
            if ($date == 0) {
                $date = $this->curr_time();
            }

            $status = isset($options['post_status']) ? $options['post_status'] : $this->def_options['options']['post_status'];
            $new_status = $status;

            $test_post = array(
                't' => $title,
                'u' => $permalink,
                'c' => $cat,
                'd' => $content,
            );
            //Validate campaign rules
            $rules = isset($options['rules']) ? $options['rules'] : array();
            if ($rules) {
                $check = $this->check_post($rules, $test_post, true);
                if ($check) {
                    foreach ($check as $key => $action) {
                        if ($action != $new_status) {
                            //Change post status
                            $new_status = $action;
                            break;
                        }
                    }
                }
            }
            // Global rules
            $use_global_rules = isset($options['use_global_rules']) ? $options['use_global_rules'] : $this->def_options['options']['use_global_rules'];

            if ($use_global_rules && $new_status == $status) {
                $global_rules = isset($settings['rules']) ? $settings['rules'] : array();
                if ($global_rules) {
                    $check = $this->check_post($global_rules, $test_post, true);
                    if ($check) {
                        foreach ($check as $key => $action) {
                            if ($action != $new_status) {
                                //Change post status
                                $new_status = $action;
                                break;
                            }
                        }
                    }
                }
            }

            if ($status != $new_status) {
                $status = $new_status;
            }

            //print '<h2>' . $title . '</h2>';
            //print $content . '<br />';
            // Add new post
            $top_movie = 0;


            $cm_id = $this->cm->add_post($date, $type, $permalink, $title, $content, $top_movie, $status, $view_type,0,true,$show_in);

            if ($cm_id == 0) {
                //Item already exist
                continue;
            } else {
                if ($status == 1) {
                    $this->append_id($cm_id);
                }
            }

            //Add post author meta
            $author_id = $campaign->author;
            $this->cm->add_post_author($cm_id, $author_id);

            //Add feeds meta
            $this->cm->add_feed_post_meta($cid, $cm_id);

            $this->log_add_post($title, $cid);

            $this->cm->hook_update_post($cm_id);

            $count += 1;
        }

        if ($count == 0) {
            // $this->log_no_new_post('', $cid);
        }

        //Update campaign lasthash
        $this->update_campaign_last_hash($cid, $currtime, $first_hash);

        //Reset invalid feed
        if ($feed_invalid != 0) {
            //Update options
            $options['feed_invalid'] = 0;
            $this->update_campaign_options($cid, $options);
        }

        return $count;
    }

    public function validate_body_len($code = '', $valid_len = 500) {
        $body_len = strlen($code);
        if ($body_len > $valid_len) {
            return true;
        }
        return false;
    }

    /**
     * Build SimplePie object based on RSS or Atom feed from URL.
     *
     * @since 2.8.0
     *
     * @param string|string[] $url URL of feed to retrieve. If an array of URLs, the feeds are merged
     *                             using SimplePie's multifeed feature.
     *                             See also {@link http://simplepie.org/wiki/faq/typical_multifeed_gotchas}
     * @return SimplePie|WP_Error SimplePie object on success or WP_Error object on failure.
     */
    function fetch_feed($url) {

        add_filter('https_ssl_verify', function () {
            return false;
        });

        if (!class_exists('SimplePie', false)) {
            require_once ABSPATH . WPINC . '/class-simplepie.php';
        }

        require_once ABSPATH . WPINC . '/class-wp-feed-cache-transient.php';
        require_once ABSPATH . WPINC . '/class-wp-simplepie-file.php';
        require_once ABSPATH . WPINC . '/class-wp-simplepie-sanitize-kses.php';

        $feed = new SimplePie();

        // $feed->set_sanitize_class('WP_SimplePie_Sanitize_KSES');
        // We must manually overwrite $feed->sanitize because SimplePie's
        // constructor sets it before we have a chance to set the sanitization class.
        // $feed->sanitize = new WP_SimplePie_Sanitize_KSES();
        // Register the cache handler using the recommended method for SimplePie 1.3 or later.
        if (method_exists('SimplePie_Cache', 'register')) {
            SimplePie_Cache::register('wp_transient', 'WP_Feed_Cache_Transient');
            $feed->set_cache_location('wp_transient');
        } else {
            // Back-compat for SimplePie 1.2.x.
            require_once ABSPATH . WPINC . '/class-wp-feed-cache.php';
            $feed->set_cache_class('WP_Feed_Cache');
        }

        $feed->set_file_class('WP_SimplePie_File');

        $feed->set_feed_url($url);
        /** This filter is documented in wp-includes/class-wp-feed-cache-transient.php */
        $feed->set_cache_duration(apply_filters('wp_feed_cache_transient_lifetime', 1 * HOUR_IN_SECONDS, $url));

        $feed->set_timeout($timeout = 60);
        /**
         * Fires just before processing the SimplePie feed object.
         *
         * @since 3.0.0
         *
         * @param SimplePie       $feed SimplePie feed object (passed by reference).
         * @param string|string[] $url  URL of feed or array of URLs of feeds to retrieve.
         */
        do_action_ref_array('wp_feed_options', array(&$feed, $url));

        $feed->init();
        $feed->set_output_encoding($this->get_option('blog_charset'));

        if ($feed->error()) {
            return new WP_Error('simplepie-error', $feed->error());
        }

        return $feed;
    }

    public function preview($campaign) {
        $ret = array();
        $cid = $campaign->id;
        $options = unserialize($campaign->options);
        $feed = $campaign->feed;
        $simplepie = $this->fetch_feed($feed);
        $get_feeds = true;
        $settings = $this->get_feed_settings();

        //Valid campaign feed
        $feed_invalid = isset($options['feed_invalid']) ? $options['feed_invalid'] : -1;

        //Wp error
        if (is_wp_error($simplepie)) {
            $msg = array();
            $msg[] = $simplepie->get_error_code();
            $msg[] = $simplepie->get_error_message(); //> Сообщение ошибки...
            $msg[] = $simplepie->get_error_data();    //> 404

            $error = implode('; ', $msg);
            $this->log_wp_error($error, $cid);
            $get_feeds = false;
        }

        if ($get_feeds) {
            //Simplepie error
            $error = $simplepie->error();
            if ($error) {
                if (is_array($error)) {
                    $error = implode('; ', $error);
                }
                $this->log_simplepie_error($error, $cid);
                $get_feeds = false;
            }
        }

        //TODO count and auto-disable invalid campaigns
        // Get posts (last is first)
        $count = 0;

        if (!$get_feeds) {
            return $count;
        }

        //Get the post date from RSS. Default ON
        $rss_date = isset($options['rss_date']) ? $options['rss_date'] : $this->def_options['options']['rss_date'];

        foreach ($simplepie->get_items() as $item) {
            $permalink = $item->get_permalink();
            $permalink_hash = $this->link_hash($permalink);

            //Proccess item            
            $title = $this->escape_title($item->get_title());
            $content = $item->get_content();


            $view_type = $this->cm->get_post_view_type($permalink);

            $code = strip_tags($content);
            $curr_len = strlen($code);

            $post_valid_len = true;
            if ($view_type == 0) {
                // Validate body len
                $valid_len = isset($options['body_len']) ? $options['body_len'] : $this->def_options['options']['body_len'];

                if (!$this->validate_body_len($code, $valid_len)) {
                    $post_valid_len = false;
                }
            }

            $cat = '';
            $cat_arr = $item->get_categories();
            if ($cat_arr && sizeof($cat_arr)) {
                $terms = array();
                foreach ($cat_arr as $cat_obj) {
                    $terms[] = $cat_obj->term;
                }
                $cat = implode(', ', $terms);
            }
            //print_r($cat);
            //Type is RSS Feeds
            $type = 1;


            $date = 0;
            if ($rss_date) {
                $time_rss = $item->get_date('r');
                if ($time_rss) {
                    $date = strtotime($time_rss);
                }
            }
            if ($date == 0) {
                $date = $this->curr_time();
            }

            $status = isset($options['post_status']) ? $options['post_status'] : $this->def_options['options']['post_status'];
            $new_status = $status;

            $test_post = array(
                't' => $title,
                'u' => $permalink,
                'c' => $cat,
                'd' => $content,
            );

            //Validate campaign rules
            $rules = isset($options['rules']) ? $options['rules'] : array();
            if ($rules) {
                $check = $this->check_post($rules, $test_post, true);
                if ($check) {
                    foreach ($check as $key => $action) {
                        if ($action != $new_status) {
                            //Change post status
                            $new_status = $action;
                            break;
                        }
                    }
                }
            }
            // Global rules
            $use_global_rules = isset($options['use_global_rules']) ? $options['use_global_rules'] : $this->def_options['options']['use_global_rules'];

            if ($use_global_rules && $new_status == $status) {
                $global_rules = isset($settings['rules']) ? $settings['rules'] : array();
                if ($global_rules) {
                    $check = $this->check_post($global_rules, $test_post, true);
                    if ($check) {
                        foreach ($check as $key => $action) {
                            if ($action != $new_status) {
                                //Change post status
                                $new_status = $action;
                                break;
                            }
                        }
                    }
                }
            }

            $ret['items'][] = array(
                'post' => $test_post,
                'date' => $date,
                'check' => $check,
                'status' => $status,
                'new_status' => $new_status,
                'publish' => $post_valid_len,
                'len' => $curr_len,
            );



            //print '<h2>' . $title . '</h2>';
            //print $content . '<br />';


            $count += 1;
        }
        $ret['count'] = $count;

        return $ret;
    }

    /*
     * Search ids
     * After add a new post item, we add its id to search list.
     * After index the item, we remove id from search list and find movies for this post.
     */

    private function append_id($id) {
        // Append a new id to search queue
        $opt_key = 'feed_matic_search_ids';
        $ids_str = $this->get_option($opt_key, '');
        $ids = array();
        if ($ids_str) {
            $ids = unserialize($ids_str);
        }
        if (!in_array($id, $ids)) {
            $ids[] = $id;
            $ids_str = serialize($ids);
            $this->update_option($opt_key, $ids_str);
        }
    }

    public function update_campaign_status($id, $status) {
        $this->db_query(sprintf("UPDATE {$this->db['campaign']} SET status=%d WHERE id = %d", (int) $status, (int) $id));
    }

    public function update_campaign_last_hash($id, $time, $feed_hash) {
        $this->db_query(sprintf("UPDATE {$this->db['campaign']} SET                 
                last_update=%d, last_hash='%s' WHERE id = '%d'", (int) $time, $feed_hash, $id));
    }

    public function get_campaign($id, $cache = false) {
        if ($cache) {
            static $dict;
            if (is_null($dict)) {
                $dict = array();
            }

            if (isset($dict[$id])) {
                return $dict[$id];
            }
        }
        $sql = sprintf("SELECT * FROM {$this->db['campaign']} WHERE id=%d", $id);
        $result = $this->db_fetch_row($sql);

        if ($cache) {
            $dict[$id] = $result;
        }
        return $result;
    }

    public function get_campaign_by_hash($rss_hash) {
        $sql = sprintf("SELECT id FROM {$this->db['campaign']} WHERE feed_hash='%s'", $rss_hash);
        $result = $this->db_fetch_row($sql);
        return $result;
    }
    
    public function get_cid_by_pid($pid) {
        $query = sprintf("SELECT cid FROM {$this->db['feed_meta']} WHERE pid=%d", $pid);

        $result = $this->cm->db_get_var($query);
        return $result;
    }
    
    public function get_pids_by_cid($cid) {
        $query = sprintf("SELECT pid FROM {$this->db['feed_meta']} WHERE cid=%d", $cid);

        $results = $this->cm->db_results($query);
        return $results;
    }

    /*
     * Pages
     */

    public function get_feeds($status = -1, $page = 1, $aid = 0, $orderby = '', $order = 'ASC') {
        $page -= 1;
        $start = $page * $this->perpage;

        $limit = '';
        if ($this->perpage > 0) {
            $limit = " LIMIT $start, " . $this->perpage;
        }

        // Custom status
        $status_trash = 2;
        $status_query = " WHERE c.status != " . $status_trash;
        if ($status != -1) {
            $status_query = " WHERE c.status = " . (int) $status;
        }

        // Author filter        
        $aid_and = '';
        if ($aid > 0) {
            $aid_and = sprintf(" AND c.author = %d", $aid);
        }

        //Sort
        $and_orderby = '';
        if ($orderby && in_array($orderby, $this->cm->sort_pages)) {
            $and_orderby = ' ORDER BY ' . $orderby;
            if ($order) {
                $and_orderby .= ' ' . $order;
            }
        } else {
            $and_orderby = " ORDER BY id DESC";
        }

        $sql = sprintf("SELECT c.id, c.date, c.status, c.last_update, c.update_interval, c.title, c.author, c.feed, c.site, c.options "
                . "FROM {$this->db['campaign']} c "
                . $status_query . $aid_and . $and_orderby . $limit);

        $result = $this->db_results($sql);

        return $result;
    }

    public function get_feeds_count($type = -1, $aid = 0) {
        // Custom type
        $type_trash = 2;
        $type_query = " WHERE status != " . $type_trash;
        if ($type != -1) {
            $type_query = " WHERE status = " . (int) $type;
        }

        // Author filter        
        $aid_and = '';
        if ($aid > 0) {
            $aid_and = sprintf(" AND author = %d", $aid);
        }

        $query = "SELECT COUNT(id) FROM {$this->db['campaign']}" . $type_query . $aid_and;
        $result = $this->db_get_var($query);
        return $result;
    }
    
    public function get_next_update($last_update = 0, $interval = 0) {
        $nextUpdate = $last_update + $interval * 60;

        if ($this->curr_time() > $nextUpdate) {
            $textDate = __('Waiting');
        } else {
            $textDate = gmdate('Y-m-d H:i:s', $nextUpdate);
        }

        return $textDate;
    }

    public function trash_campaign($form_state) {
        $result = 0;
        $status = isset($form_state['status']) ? $form_state['status'] : 0;

        if ($form_state['id']) {
            // To trash
            $id = $form_state['id'];
            $sql = sprintf("UPDATE {$this->db['campaign']} SET status=%d WHERE id = %d", $status, $id);
            $this->db_query($sql);
            $result = $id;
        }
        return $result;
    }

    public function campaign_edit_validate($form_state) {

        if (isset($form_state['trash'])) {
            // Trash
        } else if (isset($form_state['edit_feed'])) {
            // Edit
            if ($form_state['title'] == '') {
                return __('Enter the title');
            }

            if ($form_state['feed'] == '') {
                return __('Enter the RSS feed URL');
            }
        }

        $nonce = wp_verify_nonce($_POST['critic-feeds-nonce'], 'critic-feeds-options');
        if (!$nonce) {
            return __('Error validate nonce');
        }

        return true;
    }

    public function campaign_edit_submit($form_state) {
        $result = 0;
        $id = 0;
        $def_opt = $this->def_options['options'];
        $opt_prev = $def_opt;
        if ($form_state['id']) {
            //EDIT
            $id = $form_state['id'];
            $campaign = $this->get_campaign($id);
            $opt_prev = unserialize($campaign->options);
        }

        $show_status = isset($form_state['show_status']) ? $form_state['show_status'] : $def_opt['show_status'];
        
        $options = array(
            'rss_date' => isset($form_state['rss_date']) ? $form_state['rss_date'] : 0,
            'use_global_rules' => isset($form_state['use_global_rules']) ? $form_state['use_global_rules'] : 0,
            'post_status' => isset($form_state['post_status']) ? $form_state['post_status'] : $def_opt['post_status'],
            'show_status' => $show_status,
            'body_len' => isset($form_state['body_len']) ? $form_state['body_len'] : 0,
        );
        $status = isset($form_state['status']) ? $form_state['status'] : 0;

        $options['rules'] = $this->rules_form($form_state);

        $last_update = $date = $this->curr_time();
        $update_interval = $form_state['interval'];
        $author = $form_state['author'];

        $title = $this->escape($form_state['title']);
        $feed = $form_state['feed'];
        $feed_hash = $this->link_hash($feed);
        $site = $this->escape($form_state['site']);
        $opt_str = serialize($options);
        $last_hash = '';

        if ($id) {
            //EDIT
            foreach ($options as $key => $value) {
                $opt_prev[$key] = $value;
            }
            $opt_str = serialize($opt_prev);

            $sql = sprintf("UPDATE {$this->db['campaign']} SET 
                last_update=%d,
                update_interval=%d,
                author=%d, 
                status=%d, 
                title='%s', 
                feed_hash='%s', 
                feed='%s', 
                site='%s',                 
                options='%s' 
                WHERE id = %d", $last_update, $update_interval, $author, $status, $title, $feed_hash, $feed, $site, $opt_str, $id
            );

            $this->db_query($sql);           
            $result = $id;
            
            
            // Force update
            if ($form_state['force_show_status']){
                $updated = $this->force_updte_show_status_posts($id, $form_state['show_status']);
                print "<div class=\"updated\"><p><strong>Updated show status posts: $updated</strong></p></div>";
            }
            
        } else {
            //ADD
            $this->db_query(sprintf("INSERT INTO {$this->db['campaign']} (
                date, 
                last_update, 
                update_interval,
                author,
                status, 
                title,
                feed_hash,
                feed,
                site,
                last_hash,
                options                
                ) VALUES (
                %d,%d,%d,%d,%d,'%s','%s','%s','%s','%s','%s')"
                            . "", $date, $last_update, $update_interval, $author, $status, $title, $feed_hash, $feed, $site, $last_hash, $opt_str
            ));
            // print_r(array('last error',Pdo_wp::last_error()));
            //Return id
            $id = $this->getInsertId('id', $this->db['campaign']);

            $result = $id;
        }
        return $result;
    }

    public function settings_submit($form_state) {
        $form_state['rss_date'] = isset($form_state['rss_date']) ? 1 : 0;
        $form_state['use_global_rules'] = isset($form_state['use_global_rules']) ? 1 : 0;
        $form_state['rules'] = $this->rules_form($form_state);
        $form_state['body_len'] = isset($form_state['body_len']) ? $form_state['body_len'] : 0;
        $this->update_feed_settings($form_state);
    }

    private function rules_form($form_state) {
        $rule_exists = array();

        $to_remove = isset($form_state['remove_rule']) ? $form_state['remove_rule'] : array();

        // Exists rules
        foreach ($form_state as $name => $value) {
            if (strstr($name, 'rule_id_')) {
                $key = $value;
                if (in_array($key, $to_remove)) {
                    continue;
                }
                $upd_rule = array(
                    'r' => base64_encode(stripslashes($form_state['rule_r_' . $key])),
                    'c' => $form_state['rule_c_' . $key],
                    'f' => $form_state['rule_f_' . $key],
                    'a' => $form_state['rule_a_' . $key],
                    'w' => $form_state['rule_w_' . $key]
                );
                $rule_exists[$key] = $upd_rule;
            }
        }

        // New rule
        if ($form_state['new_rule_r']) {

            $old_key = 0;
            if ($rule_exists) {
                krsort($rule_exists);
                $old_key = array_key_first($rule_exists);
            }
            $new_rule_key = $old_key + 1;
            $new_rule = array(
                'r' => base64_encode(stripslashes($form_state['new_rule_r'])),
                'c' => $form_state['new_rule_c'],
                'f' => $form_state['new_rule_f'],
                'a' => $form_state['new_rule_a'],
                'w' => $form_state['new_rule_w']
            );
            $rule_exists[$new_rule_key] = $new_rule;
        }

        ksort($rule_exists);

        return $rule_exists;
    }

    public function campaign_rules_test_submit($form_state) {
        $rules_test = array(
            't' => isset($form_state['t']) ? base64_encode($form_state['t']) : '',
            'u' => isset($form_state['u']) ? base64_encode($form_state['u']) : '',
            'c' => isset($form_state['c']) ? base64_encode($form_state['c']) : '',
            'd' => isset($form_state['d']) ? base64_encode($form_state['d']) : '',
        );

        $id = $form_state['id'];
        $campaign = $this->get_campaign($id);
        $options = unserialize($campaign->options);
        $options['rt'] = $rules_test;
        $this->update_campaign_options($id, $options);
    }

    public function settings_rules_test_submit($form_state) {
        $rules_test = array(
            't' => isset($form_state['t']) ? base64_encode($form_state['t']) : '',
            'u' => isset($form_state['u']) ? base64_encode($form_state['u']) : '',
            'c' => isset($form_state['c']) ? base64_encode($form_state['c']) : '',
            'd' => isset($form_state['d']) ? base64_encode($form_state['d']) : '',
        );

        $form = array('rt' => $rules_test);
        $this->update_feed_settings($form);
    }

    public function get_feed_test_post($options) {
        if (isset($options['rt'])) {
            $rules_test = array(
                't' => isset($options['rt']['t']) ? stripslashes(base64_decode($options['rt']['t'])) : '',
                'u' => isset($options['rt']['u']) ? stripslashes(base64_decode($options['rt']['u'])) : '',
                'c' => isset($options['rt']['c']) ? stripslashes(base64_decode($options['rt']['c'])) : '',
                'd' => isset($options['rt']['d']) ? stripslashes(base64_decode($options['rt']['d'])) : '',
            );
        }
        return $rules_test;
    }

    public function post_to_test($post) {
        $rules_test = array(
            't' => $post->title,
            'u' => $post->link,
            'c' => '',
            'd' => $post->content,
        );
        return $rules_test;
    }

    public function check_post($rules, $post, $all = false) {
        $results = array();
        if ($rules && sizeof($rules)) {
            $rules_w = $this->sort_rules_by_weight($rules);
            foreach ($rules_w as $key => $rule) {
                if ($rule['r']) {
                    $reg = base64_decode($rule['r']);
                    $fields = isset($rule['f']) ? $rule['f'] : array();
                    if ($fields) {
                        foreach ($fields as $field) {
                            if (isset($post[$field])) {
                                $content = $post[$field];
                                $match = preg_match($reg, $content);
                                $condition = isset($rule['c']) && $rule['c'] == 1 ? true : false;
                                $result = -1;
                                if ($match && $condition) {
                                    $result = $rule['a'];
                                } else if (!$match && !$condition) {
                                    $result = $rule['a'];
                                }
                                if ($result >= 0) {
                                    $results[$key] = $result;
                                    break;
                                }
                            }
                        }
                    }
                }
                if (!$all && $results) {
                    break;
                }
            }
        }

        return $results;
    }

    public function apply_feed_rules($id) {
        $post = $this->cm->get_post($id, true);

        $changed = array();
        // Feed type
        if ($post->type == 1) {
            $cid = $post->fmcid;
            $campaign = $this->get_campaign($cid, true);
            $options = unserialize($campaign->options);
            $settings = $this->get_feed_settings();
            $camp_status = isset($options['post_status']) ? $options['post_status'] : $this->def_options['options']['post_status'];
            $post_status = $post->status;
            $test_post = $this->post_to_test($post);
            $rules = isset($options['rules']) ? $options['rules'] : array();
            $check = $this->check_post($rules, $test_post, true);

            $is_change = false;
            $is_check = false;
            if ($check) {
                $is_check = true;
                foreach ($check as $key => $action) {
                    if ($action != $post_status) {
                        //Change post status
                        $this->cm->change_post_state($id, $action);
                        $changed = array('id' => $id, 'from' => $post_status, 'to' => $action, 'rule' => $key, 'cid' => $cid);
                        $is_change = true;
                        break;
                    }
                }
            }

            if (!$is_change) {
                // Global rules
                $use_global_rules = isset($options['use_global_rules']) ? $options['use_global_rules'] : $this->def_options['options']['use_global_rules'];

                if ($use_global_rules && $new_status == $status) {
                    $global_rules = isset($settings['rules']) ? $settings['rules'] : array();
                    if ($global_rules) {
                        $check = $this->check_post($global_rules, $test_post, true);
                        if ($check) {
                            $is_check = true;
                            foreach ($check as $key => $action) {
                                if ($action != $post_status) {
                                    //Change post status
                                    $this->cm->change_post_state($id, $action);
                                    $changed = array('id' => $id, 'from' => $post_status, 'to' => $action, 'rule' => $key, 'cid' => $cid);
                                    $is_change = true;
                                    break;
                                }
                            }
                        }
                    }
                }
            }
            if (!$is_change && !$is_check) {
                if ($camp_status != $post_status) {
                    $this->cm->change_post_state($id, $camp_status);
                    $changed = array('id' => $id, 'from' => $post_status, 'to' => $camp_status, 'rule' => 0, 'cid' => $cid);
                }
            }
        }
        return $changed;
    }

    public function sort_rules_by_weight($rules) {
        $sort_rules = $rules;
        if ($rules) {
            $rules_w = array();
            foreach ($rules as $key => $value) {
                $rules_w[$key] = $value['w'];
            }
            asort($rules_w);
            $sort_rules = array();
            foreach ($rules_w as $key => $value) {
                $sort_rules[$key] = $rules[$key];
            }
        }
        return $sort_rules;
    }

    public function feed_states($aid = 0) {
        $count = $this->get_feeds_count(-1, $aid);
        $feed_states = array(
            '-1' => array(
                'title' => 'All',
                'count' => $count
            )
        );
        foreach ($this->feed_state as $key => $value) {
            $feed_states[$key] = array(
                'title' => $value,
                'count' => $this->get_feeds_count($key, $aid));
        }
        return $feed_states;
    }

    public function feed_actions() {
        foreach ($this->campaign_tabs as $key => $value) {
            $feed_actions[$key] = array('title' => $value);
        }
        return $feed_actions;
    }

    public function add_campaign($author = 0, $title = '', $post_date = 0, $rss_url = '', $site_url = '', $upd_interval = 60, $state = 1, $options = array()) {

        //Rss already exist?
        $rss_hash = $this->link_hash($rss_url);
        $campaign = $this->get_campaign_by_hash($rss_hash);
        if ($campaign) {
            return false;
        }

        //serialize options
        $opt_str = serialize($options);

        $sql = sprintf("INSERT INTO {$this->db['campaign']} "
                . "(date, update_interval, author, status, title, feed_hash, feed, site, options) "
                . "VALUES (%d, %d, %d, %d, '%s', '%s', '%s', '%s', '%s')", (int) $post_date, (int) $upd_interval, (int) $author, (int) $state, $this->escape($title), $rss_hash, $this->escape($rss_url), $this->escape($site_url), $opt_str);

        $this->db_query($sql);
    }

    public function update_campaign_options($id, $options = array()) {
        $opt_str = serialize($options);
        $this->db_query(sprintf("UPDATE {$this->db['campaign']} SET                 
                options='%s' WHERE id = '%d'", $opt_str, $id));
    }

    public function log_add_post($message, $cid) {
        $this->log($message, $cid, 0, 1);
    }

    public function log_no_new_post($message, $cid) {
        $this->log($message, $cid, 0, 0);
    }

    public function log_auto_stop($message, $cid) {
        $this->log($message, $cid, 1, 4);
    }

    public function log_invalid_post($message, $cid) {
        $this->log($message, $cid, 1, 5);
    }

    public function log_wp_error($message, $cid) {
        $this->log($message, $cid, 2, 2);
    }

    public function log_simplepie_error($message, $cid) {
        $this->log($message, $cid, 2, 3);
    }

    public function get_log_type($type) {
        return isset($this->log_type[$type]) ? $this->log_type[$type] : 'None';
    }

    public function get_log_status($type) {
        return isset($this->log_status[$type]) ? $this->log_status[$type] : 'None';
    }

    /*
     * Log
     * message - string
     * cid - campaign id
     * type:
      0 => 'Info',
      1 => 'Warning',
      2 => 'Error'

      status:
      0 => 'No new posts',
      1 => 'Add post',
      2 => 'Wp error',
      3 => 'Simplepie error'
      5 => 'Invalid post'

     */

    public function log($message, $cid = 0, $type = 0, $status = 0) {
        $time = $this->curr_time();
        $this->db_query(sprintf("INSERT INTO {$this->db['log']} (date, cid, type, status, message) VALUES (%d, %d, %d, %d, '%s')", $time, $cid, $type, $status, $this->escape($message)));
    }

    public function get_log($page = 1, $cid = 0) {
        $page -= 1;
        $start = $page * $this->perpage;

        $limit = '';
        if ($this->perpage > 0) {
            $limit = " LIMIT $start, " . $this->perpage;
        }
        $where = '';
        if ($cid) {
            $where = sprintf(" WHERE cid=%d", (int) $cid);
        }

        $order = " ORDER BY id DESC";
        $sql = sprintf("SELECT id, date, cid, type, status, message FROM {$this->db['log']}" . $where . $order . $limit);

        $result = $this->db_results($sql);

        return $result;
    }

    public function get_log_count($cid = 0) {
        $where = '';
        if ($cid) {
            $where = sprintf(" WHERE cid=%d", (int) $cid);
        }

        $query = "SELECT COUNT(id) FROM {$this->db['log']}" . $where;

        $result = $this->db_get_var($query);
        return $result;
    }

    public function get_last_log($id) {
        $query = sprintf("SELECT type, status, message FROM {$this->db['log']} WHERE cid=%d ORDER BY id DESC", $id);
        $result = $this->db_fetch_row($query);
        $str = '';
        if ($result) {
            $str = $this->get_log_type($result->type) . ': ' . $this->get_log_status($result->status);
            if ($result->message) {
                $str = $str . ' | ' . $result->message;
            }
        }
        return $str;
    }

    private function get_perpage() {
        $this->perpage = isset($_GET['perpage']) ? (int) $_GET['perpage'] : $this->perpage;
        return $this->perpage;
    }

    public function show_rules($rules = array(), $edit = true, $check = array(), $global = false) {
        if ($rules || $edit) {
            $disabled = '';
            if (!$edit) {
                $disabled = ' disabled ';
            }
            $title = __('Rules');
            if ($global) {
                $title = __('Global rules');
            }
            ?>
            <h2><?php print $title ?></h2>
            <table id="rules" class="wp-list-table widefat striped table-view-list">
                <thead>
                    <tr>
                        <th><?php print __('Id') ?></th>
                        <th><?php print __('Rule') ?></th>
                        <th><?php print __('Condition') ?></th> 
                        <th><?php print __('Field') ?></th>
                        <th><?php print __('Action') ?></th>                 
                        <th><?php print __('Weight') ?></th>  
                        <?php if ($edit): ?>
                            <th><?php print __('Remove') ?></th> 
                        <?php endif ?>
                        <?php if ($check): ?>
                            <th><?php print __('Check') ?></th> 
                        <?php endif ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($rules) {
                        $rules = $this->sort_rules_by_weight($rules);
                        ?>
                        <?php foreach ($rules as $rid => $rule) {
                            ?>
                            <tr>
                                <td>
                                    <?php print $rid ?>
                                    <input type="hidden" name="rule_id_<?php print $rid ?>" value="<?php print $rid ?>">
                                </td>
                                <td>
                                    <input type="text" name="rule_r_<?php print $rid ?>" class="reg" value="<?php print htmlspecialchars(base64_decode($rule['r'])) ?>"<?php print $disabled ?>>
                                </td>
                                <td>
                                    <select name="rule_c_<?php print $rid ?>" class="condition"<?php print $disabled ?>>
                                        <?php
                                        $con = $rule['c'];
                                        foreach ($this->rules_condition as $key => $name) {
                                            $selected = ($key == $con) ? 'selected' : '';
                                            ?>
                                            <option value="<?php print $key ?>" <?php print $selected ?> ><?php print $name ?></option>                                
                                            <?php
                                        }
                                        ?>                          
                                    </select>     
                                </td>
                                <td>
                                    <div class="flex-row">
                                        <?php foreach ($this->rules_fields as $key => $value) { ?>
                                            <label class="inline-edit-field flex-column">                
                                                <?php
                                                $checked = '';
                                                $fields = isset($rule['f']) ? $rule['f'] : array();
                                                if (in_array($key, $fields)) {
                                                    $checked = 'checked="checked"';
                                                }
                                                ?>
                                                <input type="checkbox" name="rule_f_<?php print $rid ?>[]" value="<?php print $key ?>" <?php print $checked ?> <?php print $disabled ?>>
                                                <span class="checkbox-title"><?php print $value ?></span>
                                            </label>  
                                        <?php } ?>
                                    </div>
                                </td>
                                <td>
                                    <select name="rule_a_<?php print $rid ?>" class="interval"<?php print $disabled ?>>
                                        <?php
                                        $action = $rule['a'];
                                        foreach ($this->rules_actions as $key => $name) {
                                            $selected = ($key == $action) ? 'selected' : '';
                                            ?>
                                            <option value="<?php print $key ?>" <?php print $selected ?> ><?php print $name ?></option>                                
                                            <?php
                                        }
                                        ?>                          
                                    </select>     
                                </td>
                                <td>
                                    <input type="text" name="rule_w_<?php print $rid ?>" class="rule_w" value="<?php print $rule['w'] ?>"<?php print $disabled ?>>
                                </td>
                                <?php if ($edit): ?>
                                    <td>
                                        <input type="checkbox" name="remove_rule[]" value="<?php print $rid ?>">
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
                            <td colspan="7"><b><?php print __('Add a new regexp rule') ?></b></td>        
                        </tr>
                        <tr>
                            <td></td>
                            <td>
                                <input type="text" name="new_rule_r" class="reg" value="" placeholder="Enter a regexp rule">
                                <div class="desc">Example: /movie review[s]*/i</div>
                            </td>
                            <td>
                                <select name="new_rule_c" class="condition">
                                    <?php foreach ($this->rules_condition as $key => $name) { ?>
                                        <option value="<?php print $key ?>"><?php print $name ?></option>                                
                                        <?php
                                    }
                                    ?>                          
                                </select> 
                            </td>
                            <td>
                                <div class="flex-row">
                                    <?php foreach ($this->rules_fields as $key => $value) { ?>
                                        <label class="inline-edit-field flex-column"> 
                                            <input type="checkbox" name="new_rule_f[]" value="<?php print $key ?>">
                                            <span class="checkbox-title"><?php print $value ?></span>
                                        </label> 
                                    <?php } ?>
                                </div>
                            </td>
                            <td>
                                <select name="new_rule_a" class="interval">
                                    <?php foreach ($this->rules_actions as $key => $name) { ?>
                                        <option value="<?php print $key ?>"><?php print $name ?></option>                                
                                        <?php
                                    }
                                    ?>                          
                                </select>     
                            </td>
                            <td>
                                <input type="text" name="new_rule_w" class="rule_w" value="0">
                            </td>
                            <td>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>    <?php
        }
    }

    public function get_feed_settings() {
        if ($this->feed_settings) {
            return $this->feed_settings;
        }
        // Get search settings from options
        $settings = unserialize($this->get_option('critic_feed_settings'));
        if ($settings && sizeof($settings)) {
            foreach ($this->feed_settings_def as $key => $value) {
                if (!isset($settings[$key])) {
                    //replace empty settings to default
                    $settings[$key] = $value;
                }
            }
        } else {
            $settings = $this->feed_settings_def;
        }
        $this->feed_settings = $settings;
        return $settings;
    }

    public function update_feed_settings($form) {
        $ss = $this->get_feed_settings();
        foreach ($ss as $key => $value) {
            $new_value = $form[$key];
            if (isset($new_value)) {
                $ss[$key] = $new_value;
            }
        }
        $this->feed_settings = $ss;
        $this->update_option('critic_feed_settings', serialize($ss));
    }

    public function find_movies_queue($ids) {
        $ret = false;
        if ($ids) {
            //get options
            $opt_key = 'feed_matic_search_ids';
            $ids_str = $this->get_option($opt_key, '');
            $opt_ids = array();
            if ($ids_str) {
                $opt_ids = unserialize($ids_str);
            }

            foreach ($ids as $id) {
                if (!in_array($id, $opt_ids)) {
                    $opt_ids[] = $id;
                    $ret = true;
                }
            }
            if ($ret) {
                $ids_str = serialize($opt_ids);
                $this->update_option($opt_key, $ids_str);
            }
        }

        return $ret;
    }

    public function bulk_change_campaign_status($ids = array(), $b) {
        foreach ($ids as $id) {
            if ($b == 'start_feed') {
                $status = 1;
            } else if ($b == 'stop_feed') {
                $status = 0;
            } else if ($b == 'trash_feed') {
                $status = 2;
            }

            $this->update_campaign_status($id, $status);
        }
    }

    /*
     * Other
     */

    public function import_feeds() {
        // UNUSED
        $authors_count = $this->cm->get_authors_count();
        if (!$authors_count) {
            return;
        }
        $table_prefix = DB_PREFIX_WP;
        // get rss feeds
        $wp_posts = $table_prefix . 'posts';
        $sql = "SELECT * FROM {$wp_posts} WHERE post_type = 'wprss_feed'";
        $result = $this->db_results($sql);
        if (sizeof($result)) {
            foreach ($result as $item) {

                //Get meta
                $meta = get_post_meta($item->ID);

                //Author
                $author = 0;
                $author_name = trim($item->post_title);
                if ($author_name) {
                    $status = isset($meta['wprss_is_public'][0]) ? 1 : 0;
                    $author_type = $meta['wprss_feed_from'][0] == 0 ? 0 : 1;
                    $author = $this->cm->get_or_create_author_by_name($author_name, $author_type, $status);
                }

                $post_date = $date = strtotime($item->post_date);

                //Post meta
                $wprss_site_url = $meta['wprss_site_url'][0];
                $wprss_url = $meta['wprss_url'][0];
                $site_arr = explode(',', $wprss_site_url);
                $rss_arr = explode(',', $wprss_url);
                $rss_to_add = array();
                if (sizeof($rss_arr)) {
                    for ($i = 0; $i < sizeof($rss_arr); $i += 1) {
                        $rss_url = trim($rss_arr[$i]);
                        if (!$rss_url) {
                            continue;
                        }
                        $rss_to_add[$i]['rss'] = $rss_url;
                        if (isset($site_arr[$i])) {
                            $rss_to_add[$i]['site'] = trim($site_arr[$i]);
                        }
                    }
                }

                // Upadate interval
                $update_interval = array(
                    15 => 'fifteen_min',
                    30 => 'thirty_min',
                    60 => 'hourly',
                    120 => 'two_hours',
                    720 => 'twicedaily',
                    1440 => 'daily'
                );
                $upd_interval = $this->def_options['update_interval'];
                if (isset($meta['wprss_update_interval'][0])) {
                    $interval = array_search($meta['wprss_update_interval'][0], $update_interval);
                    if ($interval) {
                        $upd_interval = $interval;
                    }
                }

                $state = ($meta['wprss_state'][0] == 'active') ? 1 : 0;


                if (!sizeof($rss_to_add)) {
                    continue;
                }

                $options = array();

                foreach ($rss_to_add as $value) {
                    $rss_url = $value['rss'];
                    $site_url = isset($value['site']) ? $value['site'] : '';

                    // Campaign title
                    if ($site_url) {
                        $title = $this->clean_url($site_url);
                    } else {
                        $title = $this->clean_url($rss_url);
                    }

                    //Add a new campaign
                    $this->add_campaign($author, $title, $post_date, $rss_url, $site_url, $upd_interval, $state, $options);
                }

                //print_r($item);
                //print_r($meta);
            }
        }
    }
    
    public function force_updte_show_status_posts($cid, $show_status=0){
        $campaign = $this->get_campaign($cid);
        $urls = $this->get_pids_by_cid($cid);
         $updated = 0;
        if (!$urls){
            return $updated;
        }
        $ids = array();
        foreach ($urls as $url) {
            $ids[]=$url->pid;
        }
        
        $posts = $this->cm->get_posts_by_ids($ids);
       
        if ($posts){
            foreach ($posts as $post) {
                if($post->show_in!=$show_status){
                    $data = array(
                      'show_in'=>$show_status
                    );
                    $this->cm->update_post_fields($post->id, $data);
                    $updated+=1;
                }
            }
        }
        return $updated;
    }
    

    /*
     * Other
     */

    public function escape_title($title) {
        $title = strip_tags($title);
        $title_decode = html_entity_decode($title, ENT_QUOTES, 'UTF-8');
        if ($title_decode) {
            $title = $title_decode;
        }
        return $title;
    }

    public function clean_url($url) {
        $clean_url = '';
        if (preg_match('|//([^/\?#]+)|', $url, $match)) {
            $clean_url = $match[1];
            $clean_url = preg_replace('/^www\./i', '', $clean_url);
        }
        return $clean_url;
    }
    
}
