<?php
/*
 * Critic parser for site-urls list
 * 
 * TODO
 * bulk actions
 * log for all urls
 * add author
 * cron jobs
 * show rules
 * 
 */

class CriticParser extends AbstractDBWp {

    // Critic matic
    private $cm;
    // Google client
    private $gs;
    private $client;
    private $max_cron_time = 20;
    public $perpage = 30;
    public $update_interval = array(
        1 => 'One min',
        5 => 'Five min',
        15 => 'Fifteen min',
        30 => 'Thirty min',
        60 => 'Hourly',
        120 => 'Two hours',
        720 => 'Twice daily',
        1440 => 'Daily',
        10080 => 'Weekly'
    );
    public $parser_interval = array(
        15 => 'Fifteen min',
        30 => 'Thirty min',
        60 => 'Hourly',
        120 => 'Two hours',
        720 => 'Twice daily',
        1440 => 'Daily',
        10080 => 'Weekly'
    );
    public $camp_state = array(
        1 => 'Active',
        0 => 'Inactive',
        2 => 'Trash',
    );
    public $parser_type = array(
        0 => 'Default',
        1 => 'YouTube',
    );
    public $parser_state = array(
        1 => 'Active',
        0 => 'Inactive',
        2 => 'Paused (Auto)',
        3 => 'Stop (Error)',
    );
    public $tabs = array(
        'home' => 'Campaigns list',
        'urls' => 'URLs',
        'log' => 'Log',
        'update' => 'Update',
        'add' => 'Add a new campaign',
    );
    public $campaign_tabs = array(
        'home' => 'Veiw',
        'urls' => 'URLs',
        'find' => 'Find URLs',
        'log' => 'Log',
        'edit' => 'Edit',
        'preview' => 'Preview',
        'update' => 'Update',
        'trash' => 'Trash',
    );
    public $rules_fields = array(
        'a' => 'Author',
        'd' => 'Content',
        't' => 'Title',
        'u' => 'URL'
    );
    public $rules_actions = array(
        0 => 'Ignore URL and Trash post',
        1 => 'Publish post',
        2 => 'Draft post',
        3 => 'Trash post',
    );
    public $rules_condition = array(
        1 => 'True',
        0 => 'False',
    );
    public $parser_rules_type = array(
        'x' => 'XPath',
        'm' => 'Regexp match',
        'r' => 'Regexp replace',
    );
    public $parser_rules_fields = array(
        'a' => 'Author',
        'd' => 'Content',
        't' => 'Title',
        'y' => 'Date',
    );
    public $def_options;
    private $parser_settings = '';
    private $parser_settings_def = '';
    private $log_type = array(
        0 => 'Info',
        1 => 'Warning',
        2 => 'Error',
    );
    private $log_status = array(
        0 => 'Other',
        1 => 'Find URLs',
        3 => 'Parsing',
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
        'parsenew' => 'Parse new',
        'parseforce' => 'Parse force',
        'urlfilter' => 'Use filters',
        'findmovies' => 'Find movies',
        'statusnew' => 'Status new',
        'trash' => 'Trash',
        'delete' => 'Delete',
    );
    public $post_meta_status = array(
        1 => 'With post',
        0 => 'No post',
    );
    private $def_reg_rule = array(
        'f' => '',
        't' => '',
        'r' => '',
        'm' => '',
        'c' => '',
        'y' => '',
        'w' => 0,
        'a' => 0,
        'n' => 0
    );
    private $cron_types = array(
        1 => 'parsing',
        2 => 'cron_urls',
    );
    public $yt_per_page = array(
        5 => 5,
        10 => 10,
        25 => 25,
        50 => 50
    );
    public $previews_number = array(1 => 1, 5 => 5, 10 => 10, 20 => 20);
    private $youtube_url = 'https://www.youtube.com/watch?v=';

    public function __construct($cm = '') {
        $this->cm = $cm;
        $table_prefix = DB_PREFIX_WP;
        $this->db = array(
            'posts' => $table_prefix . 'critic_matic_posts',
            // Critic Parser
            'campaign' => $table_prefix . 'critic_parser_campaign',
            'url' => $table_prefix . 'critic_parser_url',
            'log' => $table_prefix . 'critic_parser_log',
        );

        $this->parser_settings_def = array(
            'update_interval' => 60,
            'post_status' => 1,
            'max_error' => 10,
        );

        // Init settings
        $this->get_parser_settings();

        $this->def_options = array(
            'date' => $this->curr_time(),
            'last_update' => 0,
            'update_interval' => $this->parser_settings['update_interval'],
            'status' => 1,
            'title' => '',
            'site' => '',
            'options' => array(
                'dom' => '',
                'post_status' => $this->parser_settings['post_status'],
                'pr_num' => 5,
                'parse_num' => 5,
                'p_encoding' => 'utf-8',
                'rules' => '',
                'parser_rules' => '',
                'reg' => '',
                'url_status' => 1,
                'use_rules' => 0,
                'use_dom' => 0,
                'use_reg' => 0,
                'yt_force_update' => 1,
                'yt_page' => '',
                'yt_parse_num' => 50,
                'yt_pr_num' => 50,
                'yt_pr_status' => 0,
                'new_urls_weight' => 0,
                'find_urls' => array(
                    'first' => '',
                    'page' => '',
                    'from' => 2,
                    'to' => 3,
                    'match' => '',
                    'wait' => 1
                ),
                'cron_urls' => array(
                    'page' => '',
                    'match' => '',
                    'interval' => 1440,
                    'last_update' => 0,
                    'status' => 0,
                ),
                'yt_urls' => array(
                    'per_page' => 50,
                    'cron_page' => 50,
                    'interval' => 1440,
                    'last_update' => 0,
                    'status' => 1,
                )
            ),
            'max_error' => $this->parser_settings['max_error'],
        );
        $this->get_perpage();
    }

    private function init_client() {
        if (!$this->client) {
            /**
             * https://developers.google.com/youtube/v3/docs/videos/list?apix=true&apix_params=%7B%22part%22%3A%5B%22snippet%22%5D%2C%22id%22%3A%5B%22TOAqMhKcP_Q%2CcBEmK39XFYQ%22%5D%7D
             * https://github.com/googleapis/google-api-php-client/releases
             * 
             * Sample PHP code for youtube.videos.list
             * See instructions for running these code samples locally:
             * https://developers.google.com/explorer-help/guides/code_samples#php
             */
            if (!class_exists('Google_Client')) {
                $gs_name = CRITIC_MATIC_PLUGIN_DIR . 'lib/google-api-php-client--PHP7.4/vendor/autoload.php';
                if (file_exists($gs_name)) {
                    require_once CRITIC_MATIC_PLUGIN_DIR . 'lib/google-api-php-client--PHP7.4/vendor/autoload.php';
                } else {
                    print 'Goolge client lib not exist';
                    exit;
                }
            }

            try {
                $ss = $this->cm->get_settings();
                $client = new Google_Client();
                $client->setApplicationName($ss['parser_gapp']);
                $client->setDeveloperKey($ss['parser_gdk']);
                $this->client = $client;
            } catch (Exception $e) {
                print_r($e);
                exit;
            }
        }

        return $this->client;
    }

    private function init_gs() {
        if (!$this->gs) {
            $client = $this->init_client();
            try {
                // Define service object for making API requests.
                $this->gs = new Google_Service_YouTube($client);
            } catch (Exception $e) {
                print_r($e);
                exit;
            }
        }
        return $this->gs;
    }

    /*
     * Core
     */

    public function run_cron($cron_type = 1, $force = false) {
        $count = $this->process_all($cron_type, $force);
        return $count;
    }

    public function process_all($cron_type = 1, $force = false) {
        $campaigns = $this->get_campaigns();
        $count = 0;
        foreach ($campaigns as $campaign) {
            $count += $this->check_time_campaign($campaign, $cron_type, $force);
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

    public function check_time_campaign($campaign, $cron_type = 1, $force = false) {

        $count = 0;
        $options = $this->get_options($campaign);
        $active = 0;
        $type_name = isset($this->cron_types[$cron_type]) ? $this->cron_types[$cron_type] : '';

        if ($type_name == 'parsing') {
            // Parser interval
            $update_interval = $campaign->update_interval;
            $update_last_time = $campaign->last_update;
            $active = $campaign->parser_status;
        } else if ($type_name == 'cron_urls') {
            if ($campaign->type == 1) {
                $type_name = 'yt_urls';
            }
            // Custom options
            $type_opt = $options[$type_name];
            $active = $type_opt['status'];
            $update_interval = $type_opt['interval'];
            $update_last_time = $type_opt['last_update'];
        } else {
            return $count;
        }

        if ($active == 1) {

            $next_update = $update_last_time + $update_interval * 60;
            $currtime = $this->curr_time();

            if ($currtime > $next_update || $force) {
                $count = $this->process_campaign($campaign, $type_name);
                // Update timer
                if ($type_name == 'parsing') {
                    $this->update_campaign_last_update($campaign->id, $currtime);
                } else {
                    $options[$type_name]['last_update'] = $currtime;
                    $this->update_campaign_options($campaign->id, $options);
                }
            }
        }
        return $count;
    }

    public function process_campaign($campaign, $type_name) {

        if ($type_name == 'parsing') {
            $count = $this->process_parser($campaign);
        } else if ($type_name == 'cron_urls' || $type_name == 'yt_urls') {
            $count = $this->proccess_cron_urls($campaign);
        }
        return $count;
    }

    public function process_parser($campaign = '') {
        $options = $this->get_options($campaign);

        // Get posts (last is first)        
        $urls_count = $options['parse_num'];

        // Get last urls
        $status = 0;
        $urls = $this->get_last_urls($urls_count, $status, $campaign->id);

        $count = sizeof($urls);
        if ($count) {
            if ($campaign->type == 1) {
                //YouTube campaign
                $this->parse_urls_yt($urls, $campaign);
            } else {
                foreach ($urls as $item) {
                    $this->parse_url($item->id, false);
                }
            }
        } else {
            // Auto pause Parser
            $parser_status = 2;
            $this->update_campaign_parser_status($campaign->id, $parser_status);
            $message = 'All URLS parsed. Parser paused';
            $this->log_info($message, $campaign->id, 0, 0);
        }

        return $count;
    }

    public function proccess_cron_urls($campaign = '') {
        $options = $this->get_options($campaign);

        $result = $this->cron_urls($campaign, false);
        if (isset($result['add_urls'])) {
            $count = sizeof($result['add_urls']);
            $message = 'Add new URLs: ' . $count;
            $this->log_info($message, $campaign->id, 0, 1);

            // Start auto-paused parser
            if ($campaign->parser_status == 2) {
                $parser_status = 1;
                $this->update_campaign_parser_status($campaign->id, $parser_status);
            }
        }

        return $count;
    }

    public function preview($campaign, $urls = array()) {
        if (!$urls) {
            return array();
        }
        $ret = array();
        $options = $this->get_options($campaign);
        /*
         * [0] => stdClass Object ( 
         * [id] => 5 
         * [cid] => 1 
         * [pid] => 36829 
         * [status] => 1 
         * [link_hash] => 9161575e0d1a6f1afdfea00b2e5a75d92c289e6c 
         * [link] => https://www.nationalreview.com/2021/10/movie-review-dune-and-doom/
         */
        $url_status = $options['url_status'];
        foreach ($urls as $item) {
            $ret[$item->id]['url'] = $item;
            $headers = '';
            $content = '';

            // 1. Validate campaign URL rules
            if ($options['use_rules']) {
                $test_post = array(
                    'u' => $item->link,
                );
                $check = $this->check_post_rules($options['rules'], $url_status, $test_post, true);
                $ret[$item->id]['check_url'] = $check;

                $new_status = $check['status'];
                if ($new_status === 0) {
                    // Ignore URL
                    continue;
                }
            }

            // 2. Get the content       
            $code = $this->get_proxy($item->link, '', $headers);

            if ($code) {
                $ret[$item->id]['raw'] = $code;

                if ($options['p_encoding'] != 'utf-8') {
                    $code = mb_convert_encoding($code, 'utf-8', $options['p_encoding']);
                }

                // Use reg rules
                $items = $this->check_reg_post($options['parser_rules'], $code);

                $content = isset($items['d']) ? $items['d'] : '';
                $title = isset($items['t']) ? $items['t'] : '';
                $author = isset($items['a']) ? $items['a'] : '';
                $date_raw = isset($items['y']) ? $items['y'] : '';

                if ($content) {
                    $content = force_balance_tags($content);
                }

                if ($content) {
                    // Core filters
                    $domain = preg_replace('#^([a-z]+\:\/\/[^\/]+)(\/|\?|\#).*#', '$1', $item->link . '/');
                    $content = $this->absoluteUrlFilter($domain, $content);

                    // Validate content
                    if ($options['use_rules']) {
                        $test_post = array(
                            'a' => $author,
                            'd' => $content,
                            't' => $title,
                        );
                        $check = $this->check_post_rules($options['rules'], $url_status, $test_post, true);
                        $ret[$item->id]['check_content'] = $check;

                        $new_status = $check['status'];
                        if ($new_status === 0) {
                            // Ignore URL                            
                        }
                    }
                }
                if ($date_raw) {
                    $date = strtotime($date_raw);
                } else {
                    $date = $this->curr_time();
                }

                $ret[$item->id]['title'] = $title;
                $ret[$item->id]['date'] = $date;
                $ret[$item->id]['date_raw'] = $date_raw;
                $ret[$item->id]['author'] = $author;
            }

            $ret[$item->id]['headers'] = $headers;
            $ret[$item->id]['content'] = $content;
        }
        return $ret;
    }

    public function get_urls_content_yt($campaign, $urls = array()) {
        if (!$urls) {
            return array();
        }
        $options = $this->get_options($campaign);
        $url_status = $options['url_status'];


        $ids = array();
        $urls_id = array();
        foreach ($urls as $item) {
            $link = $item->link;
            $id = str_replace($this->youtube_url, '', $link);
            $ids[] = $id;
            $urls_id[$link] = $id;
        }
        $snippets = $this->find_youtube_data_api($ids, false);

        foreach ($urls as $item) {
            $ret[$item->id]['url'] = $item;
            $link = $item->link;
            $id = $urls_id[$link];
            $snippet = $snippets[$id];

            /* Snippet
              [description] => how is this the world that we live in ----- Credits xdarjeelingxtea Rushia Ch.
              [publishedAt] => 2022-02-11T21:28:40Z
              [title] => Hololive's Rushia getting canceled over complete nonsense
             */
            $title = $snippet->title;
            $date = strtotime($snippet->publishedAt);
            $desc = $snippet->description;


            // 1. Validate campaign URL rules
            $test_post = array(
                'u' => $link,
                'd' => $desc,
                't' => $title,
            );
            if ($options['use_rules']) {
                $check = $this->check_post_rules($options['rules'], $url_status, $test_post, true);
                $ret[$item->id]['check'] = $check;
            }

            // 2. Content regexps
            if ($options['yt_pr_status']) {
                $test_post = $this->check_reg_post_yt($options['parser_rules'], $test_post);
            }

            $ret[$item->id]['link'] = $link;
            $ret[$item->id]['title'] = $test_post['t'];
            $ret[$item->id]['date'] = $date;
            $ret[$item->id]['desc'] = str_replace("\n", '<br />', $test_post['d']);
        }

        return $ret;
    }

    public function parse_urls_yt($urls, $campaign, $force = false) {
        $options = $this->get_options($campaign);
        $force_update = $options['yt_force_update'];

        $content = $this->get_urls_content_yt($campaign, $urls);
        if ($content && sizeof($content)) {
            foreach ($content as $id => $data) {
                //Get url object
                $item = $data['url'];
                $status = $item->status;

                // New status or Force.
                if ($status == 0 || $force) {
                    // Post exist?
                    $link_hash = $this->link_hash($item->link);
                    $post_exist = $this->cm->get_post_by_link_hash($link_hash);
                    $item->status = 5;

                    if ($post_exist) {
                        //Check post type
                        /*
                          public $post_type = array(
                          0 => 'Import',
                          1 => 'Feed',
                          2 => 'Manual',
                          3 => 'Parser',
                          4 => 'Transcript'
                          );
                         */
                        if ($post_exist->type == 1 && $force_update) {
                            // Update posts
                        } else {
                            // Update only Feed posts
                            /*
                             * URL status
                              0 => 'New',
                              1 => 'Exist',
                              2 => 'Trash',
                              3 => 'Ignore',
                              4 => 'Error',
                              5 => 'Parsing',
                             */
                            $status = 1;
                            $this->change_url_state($id, $status);
                            $message = 'Post exists, continue URL';
                            $this->log_warn($message, $campaign->id, $item->id, 3);
                            continue;
                        }
                    }

                    $url_status = $options['url_status'];
                    $post_status = $options['post_status'];

                    if ($options['use_rules']) {
                        $check = $data['check'];
                        $new_status = $check['status'];
                        if ($new_status == 0) {
                            // Ignore URL                                
                            $item->status = 3;
                            $post_status = 2;
                        } else if ($new_status == 1 || $new_status == 2 || $new_status == 3) {
                            $post_status = 0;
                            if ($new_status == 1) {
                                // Publish
                                $post_status = 1;
                            } else if ($new_status == 3) {
                                // Trash
                                $post_status = 2;
                            }
                        }
                    }

                    //Check content
                    $content = $data['desc'];
                    $title = $data['title'];
                    $date = $data['date'];
                    if ($title) {
                        // Change url status
                        $post_type = 3;
                        $top_movie = 0;
                        if (!$date) {
                            $date = $this->curr_time();
                        }

                        if ($post_exist) {
                            // Update post
                            $log_message = 'Update post';
                            $pid = $post_exist->id;
                            $this->cm->update_post($pid, $date, $post_status, $item->link, $title, $content, $post_type);
                        } else {
                            // Add post 
                            $log_message = 'Add post';
                            $pid = $this->cm->add_post($date, $post_type, $item->link, $title, $content, $top_movie, $post_status);

                            // Add author      
                            $aid = $campaign->author;
                            $this->cm->add_post_author($pid, $aid);
                        }

                        // Update url         

                        $item->pid = $pid;
                        $this->update_url($item);

                        // Add log
                        if ($item->status != 3) {
                            $this->log_info($log_message, $campaign->id, $item->id, 3);
                        } else {
                            $message = 'Check URL:' . $new_status . '. ' . $this->show_check($check);
                            $this->log_warn($message, $campaign->id, $item->id, 3);
                        }
                        $this->append_id($pid);
                    } else {
                        $status = 4;
                        $this->change_url_state($id, $status);
                        $message = 'Error URL filters';
                        $this->log_error($message, $campaign->id, $item->id, 3);
                    }
                }
            }
        }
    }

    public function parse_url($id, $force = false) {
        $changed = false;
        $item = $this->get_url($id);
        if ($item) {
            $headers = '';
            $content = '';
            $status = $item->status;


            // New status or Force.
            if ($status == 0 || $force) {
                $campaign = $this->get_campaign($item->cid, true);
                $options = $this->get_options($campaign);
                $url_status = $options['url_status'];
                $post_status = $options['post_status'];

                $item->status = 5;

                // Post exist?
                $link_hash = $this->link_hash($item->link);

                // Check the post already in db
                $post_exist = $this->cm->get_post_by_link_hash($link_hash);

                // 1. Validate campaign URL rules
                if ($options['use_rules']) {

                    $test_post = array(
                        'u' => $item->link,
                    );

                    $check = $this->check_post_rules($options['rules'], $url_status, $test_post, false);
                    $new_status = $check['status'];

                    if ($new_status == 0) {

                        // Ignore URL
                        $changed = true;

                        if ($post_exist) {
                            $item->pid = $post_exist->id;
                            // Trash post
                            $this->cm->trash_post_by_id($post_exist->id);
                        }

                        $item->status = 3;
                        $this->update_url($item);

                        $message = 'Check URL:' . $new_status . '. ' . $this->show_check($check);
                        $this->log_warn($message, $campaign->id, $item->id, 3);
                        return $changed;
                    }
                }

                // 2. Get the content       
                $code = $this->get_proxy($item->link, '', $headers);

                if ($code) {
                    if ($options['p_encoding'] != 'utf-8') {
                        $code = mb_convert_encoding($code, 'utf-8', $options['p_encoding']);
                    }

                    // Use reg rules
                    $items = $this->check_reg_post($options['parser_rules'], $code);
                    $content = isset($items['d']) ? trim($items['d']) : '';
                    $title = isset($items['t']) ? trim($items['t']) : '';
                    $author = isset($items['a']) ? trim(strip_tags($items['a'])) : '';
                    $date = isset($items['y']) ? strtotime($items['y']) : '';

                    if ($content && $title) {
                        $content = force_balance_tags($content);
                        // Core filters
                        $domain = preg_replace('#^([a-z]+\:\/\/[^\/]+)(\/|\?|\#).*#', '$1', $item->link . '/');
                        $content = $this->absoluteUrlFilter($domain, $content);

                        // Validate content
                        if ($options['use_rules']) {
                            $test_post = array(
                                'u' => $item->link,
                                'a' => $author,
                                'd' => $content,
                                't' => $title,
                            );
                            $check = $this->check_post_rules($options['rules'], $url_status, $test_post, false);
                            $new_status = $check['status'];

                            if ($new_status == 0) {
                                // Ignore URL                                
                                $item->status = 3;
                                $this->change_url_state($id, $status);
                                $message = 'Check rules:' . $new_status . '. ' . $this->show_check($check);
                                $this->log_warn($message, $campaign->id, $item->id, 3);

                                $post_status = 2;
                            } else if ($new_status == 1 || $new_status == 2 || $new_status == 3) {
                                $post_status = 0;
                                if ($new_status == 1) {
                                    // Publish
                                    $post_status = 1;
                                } else if ($new_status == 3) {
                                    // Trash
                                    $post_status = 2;
                                }
                            }
                        }

                        // Change url status
                        $changed = true;

                        $post_type = 3;
                        $top_movie = 0;
                        if (!$date) {
                            $date = $this->curr_time();
                        }

                        if ($post_exist) {
                            // Update post
                            $log_message = 'Update post';
                            $pid = $post_exist->id;

                            $this->cm->update_post($pid, $date, $post_status, $item->link, $title, $content, $post_type);
                        } else {

                            // Add post 
                            $log_message = 'Add post';
                            $pid = $this->cm->add_post($date, $post_type, $item->link, $title, $content, $top_movie, $post_status);

                            // Add author
                            if ($author) {
                                $author_type = 1;
                                $author_status = 0;
                                $aid = $this->cm->get_or_create_author_by_name($author, $author_type, $author_status);
                            } else {
                                $aid = $campaign->author;
                            }

                            $this->cm->add_post_author($pid, $aid);
                        }

                        // Update url         

                        $item->pid = $pid;
                        $this->update_url($item);

                        // Add log
                        if ($item->status != 3) {
                            $this->log_info($log_message, $campaign->id, $item->id, 3);
                        }
                        $this->append_id($pid);
                    } else {
                        $changed = true;
                        $status = 4;
                        $this->change_url_state($id, $status);
                        $message = 'Error URL filters';
                        $this->log_error($message, $campaign->id, $item->id, 3);
                        return $changed;
                    }
                } else {
                    $changed = true;
                    $status = 4;
                    $this->change_url_state($id, $status);
                    $message = 'Can not get the content';
                    $this->log_error($message, $campaign->id, $item->id, 3);
                    return $changed;
                }
            }
        }

        return $changed;
    }

    public function url_filter($id) {

        $changed = false;
        $item = $this->get_url($id);
        $campaign = $this->get_campaign($item->cid, true);
        $options = $this->get_options($campaign);

        $opt_url_status = $options['url_status'];

        // 1. Validate campaign URL rules
        if ($options['use_rules']) {

            $test_post = array(
                'u' => $item->link,
            );

            $post_exist = '';
            if ($item->pid) {
                $post_exist = $this->cm->get_post($item->pid);
                $author = $this->cm->get_author($post_exist->aid, true);
                $test_post = array(
                    'u' => $item->link,
                    'a' => $author->name,
                    'd' => $post_exist->content,
                    't' => $post_exist->title,
                );
            }

            $check = $this->check_post_rules($options['rules'], $opt_url_status, $test_post, false);
            $new_status = $check['status'];

            // Current url status
            /*
              0 => 'New',
              1 => 'Exist',
              2 => 'Trash',
              3 => 'Ignore',
              4 => 'Error',
              5 => 'Parsing',
             */
            $url_status = $item->status;


            /*
              0 => 'Ignore URL and Trash post',
              1 => 'Publish post',
              2 => 'Draft post',
              3 => 'Trash post',
             */
            if ($new_status == 0) {
                // Ignore URL and Trash post                
                if ($url_status != 3) {
                    // Ignore URL
                    $changed = true;
                    $item->status = 3;
                    $this->update_url($item);

                    $message = 'Check filters:' . $new_status . '. ' . $this->show_check($check);
                    $this->log_warn($message, $campaign->id, $item->id, 3);
                }

                // Trash post
                if ($post_exist) {
                    $this->cm->trash_post_by_id($item->pid);
                    $changed = true;
                }
            } else if ($new_status == 1 || $new_status == 2 || $new_status == 3) {
                if ($item->pid) {
                    $status = 0;
                    if ($new_status == 1) {
                        // Publish
                        $status = 1;
                    } else if ($new_status == 3) {
                        // Trash
                        $status = 2;
                    }
                    $changed = $this->cm->change_post_state($item->pid, $status);
                }
            }
        }
        return $changed;
    }

    public function update_url($item) {
        /*
          `id` int(11) unsigned NOT NULL auto_increment,
          `cid` int(11) NOT NULL DEFAULT '0',
          `pid` int(11) NOT NULL DEFAULT '0',
          `status` int(11) NOT NULL DEFAULT '0',
          `link_hash` varchar(255) NOT NULL default '',
          `link` text default NULL,
         */
        $sql = sprintf("UPDATE {$this->db['url']} SET                 
                cid=%d,
                pid=%d,
                status=%d,
                link='%s', 
                link_hash='%s'              
                WHERE id = %d", (int) $item->cid, (int) $item->pid, (int) $item->status, $this->escape($item->link), $item->link_hash, (int) $item->id
        );
        $this->db_query($sql);
    }

    public function update_url_campaing($id, $cid) {
        $sql = sprintf("UPDATE {$this->db['url']} SET cid=%d WHERE id = %d", (int) $cid, (int) $id);
        $this->db_query($sql);
    }

    private function get_dom($rule, $match_str, $code) {
        $content = '';
        if ($rule && $code) {
            $code = force_balance_tags($code);
            $dom = new DOMDocument;
            libxml_use_internal_errors(true);
            $dom->loadHTML($code);
            $xpath = new DOMXPath($dom);
            $result = $xpath->query($rule);
            if (!is_null($result)) {
                foreach ($result as $element) {
                    $content = $this->getNodeInnerHTML($element);
                    break;
                }
            }
        }
        unset($dom);
        unset($xpath);
        if ($match_str) {
            $content = str_replace('$1', $content, $match_str);
        }
        return $content;
    }

    private function getNodeInnerHTML(DOMNode $oNode) {
        $oDom = new DOMDocument();
        foreach ($oNode->childNodes as $oChild) {
            $oDom->appendChild($oDom->importNode($oChild, true));
        }
        return $oDom->saveHTML();
    }

    private function get_reg($rule, $match_str, $content) {
        // Filters reg
        if ($rule) {
            $content = preg_replace($rule, $match_str, $content);
        }
        return $content;
    }

    private function get_reg_match($rule, $match_str, $content) {
        // Filters match
        $ret = '';
        if ($rule && $content) {
            if (preg_match($rule, $content, $match)) {
                // Math reg
                if (preg_match_all('/\$([0-9]+)/', $match_str, $match_all)) {
                    for ($i = 0; $i < sizeof($match_all[0]); $i++) {
                        $num = (int) $match_all[1][$i];
                        if (!$ret) {
                            $ret = $match_str;
                        }
                        if (isset($match[$num])) {
                            $ret = str_replace($match_all[0][$i], trim($match[$num]), $ret);
                        }
                    }
                }
            }
        }
        return $ret;
    }

    public function get_parser_settings() {
        if ($this->parser_settings) {
            return $this->parser_settings;
        }
        // Get search settings from options
        $settings = unserialize($this->get_option('critic_parser_settings'));
        if ($settings && sizeof($settings)) {
            foreach ($this->parser_settings_def as $key => $value) {
                if (!isset($settings[$key])) {
                    // replace empty settings to default
                    $settings[$key] = $value;
                }
            }
        } else {
            $settings = $this->parser_settings_def;
        }
        $this->parser_settings = $settings;
        return $settings;
    }

    public function update_parser_settings($form) {
        $ss = $this->get_parser_settings();
        foreach ($ss as $key => $value) {
            $new_value = $form[$key];
            if (isset($new_value)) {
                $ss[$key] = $new_value;
            }
        }
        $this->parser_settings = $ss;
        update_option('critic_parser_settings', serialize($ss));
    }

    public function get_parsers($type, $status = -1, $page = 1, $aid = 0, $parser_status = -1, $orderby = '', $order = 'ASC') {
        $page -= 1;
        $start = $page * $this->perpage;

        $limit = '';
        if ($this->perpage > 0) {
            $limit = " LIMIT $start, " . $this->perpage;
        }

        // Custom status
        $status_trash = 2;
        $status_query = " AND c.status != " . $status_trash;
        if ($status != -1) {
            $status_query = " AND c.status = " . (int) $status;
        }

        // Custom parser status

        $parser_query = "";
        if ($parser_status != -1) {
            $parser_query = " AND c.parser_status = " . (int) $parser_status;
        }

        $type_query = "";
        if ($type != -1) {
            $type_query = " AND type = " . (int) $type;
        }

        // Author filter        
        $aid_and = '';
        if ($aid > 0) {
            $aid_and = sprintf(" AND c.author = %d", $aid);
        }

        // Sort
        $and_orderby = '';
        if ($orderby && in_array($orderby, $this->cm->sort_pages)) {
            $and_orderby = ' ORDER BY ' . $orderby;
            if ($order) {
                $and_orderby .= ' ' . $order;
            }
        } else {
            $and_orderby = " ORDER BY id DESC";
        }

        $sql = sprintf("SELECT c.id, c.date, c.type, c.status, c.parser_status, c.last_update, c.update_interval, c.title, c.author, c.site, c.options "
                . "FROM {$this->db['campaign']} c WHERE c.id>0"
                . $type_query . $status_query . $parser_query . $aid_and . $and_orderby . $limit);

        $result = $this->db_results($sql);

        return $result;
    }

    public function get_parser_count($aid = 0, $type = -1, $status = -1, $parser_status = -1) {
        // Custom type
        $status_trash = 2;
        $status_query = " AND status != " . $status_trash;
        if ($status != -1) {
            $status_query = " AND status = " . (int) $status;
        }

        // Author filter        
        $aid_and = '';
        if ($aid > 0) {
            $aid_and = sprintf(" AND author = %d", $aid);
        }

        $parser_query = "";
        if ($parser_status != -1) {
            $parser_query = " AND parser_status = " . (int) $parser_status;
        }

        $type_query = "";
        if ($type != -1) {
            $type_query = " AND type = " . (int) $type;
        }

        $query = "SELECT COUNT(*) FROM {$this->db['campaign']} WHERE id>0" . $type_query . $status_query . $parser_query . $aid_and;
        $result = $this->db_get_var($query);
        return $result;
    }

    public function parser_types($aid = 0) {
        $count = $this->get_parser_count($aid);
        $parser_states = array(
            '-1' => array(
                'title' => 'All',
                'count' => $count
            )
        );
        foreach ($this->parser_type as $key => $value) {
            $parser_states[$key] = array(
                'title' => $value,
                'count' => $this->get_parser_count($aid, $key));
        }
        return $parser_states;
    }

    public function parser_states($aid = 0, $type = -1) {
        $count = $this->get_parser_count($aid, $type);
        $parser_states = array(
            '-1' => array(
                'title' => 'All',
                'count' => $count
            )
        );
        foreach ($this->camp_state as $key => $value) {
            $parser_states[$key] = array(
                'title' => $value,
                'count' => $this->get_parser_count($aid, $type, $key));
        }
        return $parser_states;
    }

    public function parser_parser_states($aid = 0, $type = -1, $status = 0) {
        $count = $this->get_parser_count($aid, $type, $status);
        $parser_states = array(
            '-1' => array(
                'title' => 'All',
                'count' => $count
            )
        );
        foreach ($this->parser_state as $key => $value) {
            $parser_states[$key] = array(
                'title' => $value,
                'count' => $this->get_parser_count($aid, $type, $status, $key));
        }
        return $parser_states;
    }

    public function parser_actions() {
        foreach ($this->campaign_tabs as $key => $value) {
            $parser_actions[$key] = array('title' => $value);
        }
        return $parser_actions;
    }

    public function get_perpage() {
        $this->perpage = isset($_GET['perpage']) ? (int) $_GET['perpage'] : $this->perpage;
        return $this->perpage;
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

    public function update_campaign_status($id, $status) {
        $this->db_query(sprintf("UPDATE {$this->db['campaign']} SET status=%d WHERE id = %d", (int) $status, (int) $id));
    }

    public function update_campaign_parser_status($id, $status) {
        $this->db_query(sprintf("UPDATE {$this->db['campaign']} SET parser_status=%d WHERE id = %d", (int) $status, (int) $id));
    }

    public function update_campaign_last_update($id, $last_update) {
        $this->db_query(sprintf("UPDATE {$this->db['campaign']} SET last_update=%d WHERE id = %d", (int) $last_update, (int) $id));
    }

    public function update_campaign_last_hash($id, $time, $feed_hash) {
        $this->db_query(sprintf("UPDATE {$this->db['campaign']} SET                 
                last_update=%d, last_hash='%s' WHERE id = '%d'", (int) $time, $feed_hash, $id));
    }

    public function update_campaign_options($id, $options) {
        $opt_str = serialize($options);
        $this->db_query(sprintf("UPDATE {$this->db['campaign']} SET options='%s' WHERE id = %d", $opt_str, (int) $id));
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

    public function get_campaign_weight($id, $cache = true) {
        if ($cache) {
            static $dict;
            if (is_null($dict)) {
                $dict = array();
            }

            if (isset($dict[$id])) {
                return $dict[$id];
            }
        }

        $campaign = $this->get_campaign($id, $cache);
        $options = $this->get_options($campaign);
        $weight = $options['new_urls_weight'];


        if ($cache) {
            $dict[$id] = $weight;
        }
        return $weight;
    }

    public function campaign_edit_validate($form_state) {

        if (isset($form_state['trash']) || isset($form_state['add_urls']) || isset($form_state['yt_urls'])) {
            // Trash
        } else if (isset($form_state['find_urls']) || isset($form_state['cron_urls'])) {
            // Find urls
            if ($form_state['match'] == '') {
                return __('Enter the match regexp');
            }
            if ($form_state['first'] == '' && $form_state['page'] == '') {
                return __('Enter the any page');
            }
        } else {
            // Edit
            if ($form_state['title'] == '') {
                return __('Enter the title');
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

        $options = array(
            'post_status' => isset($form_state['post_status']) ? $form_state['post_status'] : $def_opt['post_status'],
            'pr_num' => isset($form_state['pr_num']) ? $form_state['pr_num'] : $def_opt['pr_num'],
            'parse_num' => isset($form_state['parse_num']) ? $form_state['parse_num'] : $def_opt['parse_num'],
            'url_status' => isset($form_state['url_status']) ? $form_state['url_status'] : $def_opt['url_status'],
            'use_rules' => isset($form_state['use_rules']) ? $form_state['use_rules'] : $def_opt['use_rules'],
            'use_dom' => isset($form_state['use_dom']) ? $form_state['use_dom'] : $def_opt['use_dom'],
            'use_reg' => isset($form_state['use_reg']) ? $form_state['use_reg'] : $def_opt['use_reg'],
            'yt_force_update' => isset($form_state['yt_force_update']) ? $form_state['yt_force_update'] : $def_opt['yt_force_update'],
            'yt_parse_num' => isset($form_state['yt_parse_num']) ? $form_state['yt_parse_num'] : $def_opt['yt_parse_num'],
            'yt_pr_num' => isset($form_state['yt_pr_num']) ? $form_state['yt_pr_num'] : $def_opt['yt_pr_num'],
            'yt_pr_status' => isset($form_state['yt_pr_status']) ? $form_state['yt_pr_status'] : $def_opt['yt_pr_status'],
            'new_urls_weight' => isset($form_state['new_urls_weight']) ? $form_state['new_urls_weight'] : $def_opt['new_urls_weight'],
        );
        $status = isset($form_state['status']) ? $form_state['status'] : 0;

        $options['rules'] = $this->rules_form($form_state);
        $options['parser_rules'] = $this->parser_rules_form($form_state);

        if ($form_state['dom']) {
            $options['dom'] = base64_encode(stripslashes($form_state['dom']));
        }

        if ($form_state['reg']) {
            $options['reg'] = base64_encode(stripslashes($form_state['reg']));
        }

        $last_update = $date = $this->curr_time();
        $update_interval = isset($form_state['interval']) ? $form_state['interval'] : $def_opt['interval'];
        $parser_status = $form_state['parser_status'];
        $author = $form_state['author'];
        $type = $form_state['type'];

        $title = $this->escape($form_state['title']);
        $site = $this->escape($form_state['site']);

        // Yt settings
        if ($type == 1) {
            // Validate YT channel
            if ($form_state['yt_page'] == '') {
                $form_state['yt_page'] = $this->find_channel_id($form_state['site']);
            }
            if ($form_state['yt_page']) {
                $options['yt_page'] = base64_encode(stripslashes($form_state['yt_page']));
            }
        }

        $opt_str = serialize($options);

        if ($id) {
            // EDIT
            foreach ($options as $key => $value) {
                $opt_prev[$key] = $value;
            }
            $opt_str = serialize($opt_prev);

            $sql = sprintf("UPDATE {$this->db['campaign']} SET 
                last_update=%d,
                update_interval=%d,
                author=%d, 
                status=%d, 
                type=%d,
                parser_status=%d,
                title='%s', 
                site='%s',                 
                options='%s' 
                WHERE id = %d", $last_update, $update_interval, $author, $status, $type, $parser_status, $title, $site, $opt_str, $id
            );

            $this->db_query($sql);
            $result = $id;
        } else {
            // ADD
            $this->db_query(sprintf("INSERT INTO {$this->db['campaign']} (
                date, 
                last_update, 
                update_interval,
                author,
                status, 
                type,
                parser_status,
                title,
                site,                
                options                
                ) VALUES (
                %d,%d,%d,%d,%d,%d,%d,'%s','%s','%s')"
                            . "", $date, $last_update, $update_interval, $author, $status, $type, $parser_status, $title, $site, $opt_str
            ));

            // Return id
            $id = $this->getInsertId('id', $this->db['campaign']);

            $result = $id;
        }
        return $result;
    }

    private function find_channel_id($site) {
        if (preg_match('/\/channel\/([\w\d_-]+)/', $site, $match)) {
            return $match[1];
        }
        return '';
    }

    public function campaign_find_urls_submit($form_state) {
        if ($form_state['id']) {
            // EDIT
            $id = $form_state['id'];
            $campaign = $this->get_campaign($id);
            $opt_prev = $this->get_options($campaign);

            // Find urls
            if ($form_state['find_urls']) {
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
                $this->update_campaign_options($id, $options);
            } else if ($form_state['cron_urls']) {

                $def_urls = $this->def_options['options']['cron_urls'];
                $urls_prev = $opt_prev['cron_urls'];
                $urls = array();
                $not_set_array = array('status');
                foreach ($def_urls as $key => $value) {
                    if (isset($form_state[$key]) || in_array($key, $not_set_array)) {

                        if ($key == 'page' || $key == 'match') {
                            $urls[$key] = base64_encode(stripslashes($form_state[$key]));
                        } else {
                            $urls[$key] = $form_state[$key];
                        }
                    } else {
                        $urls[$key] = isset($urls_prev[$key]) ? $urls_prev[$key] : $value;
                    }
                }
                $options = $opt_prev;
                $options['cron_urls'] = $urls;
                $this->update_campaign_options($id, $options);
            } else if ($form_state['yt_urls']) {

                $def_yt_urls = $this->def_options['options']['yt_urls'];
                $urls_prev = $opt_prev['yt_urls'];
                $urls = array();
                $not_set_array = array('status');
                foreach ($def_yt_urls as $key => $value) {
                    if (isset($form_state[$key]) || in_array($key, $not_set_array)) {
                        $urls[$key] = $form_state[$key];
                    } else {
                        $urls[$key] = isset($urls_prev[$key]) ? $urls_prev[$key] : $value;
                    }
                }
                $options = $opt_prev;
                $options['yt_urls'] = $urls;

                // Yt page
                $key == 'yt_page';
                if (!$form_state[$key]) {
                    $form_state[$key] = $this->find_channel_id($campaign->site);
                }
                $options[$key] = base64_encode(stripslashes($form_state[$key]));

                $this->update_campaign_options($id, $options);
            } else if ($form_state['add_urls']) {
                $this->add_urls($id, $form_state['add_urls'], $opt_prev);
            }
        }
    }

    public function find_urls($campaign, $preview = true) {
        $options = $this->get_options($campaign);
        $find_urls = $options['find_urls'];

        $urls = array();
        if (isset($find_urls['first']) && $find_urls['first'] != '') {
            $urls[] = htmlspecialchars(base64_decode($find_urls['first']));
        }

        $from = isset($find_urls['from']) ? (int) $find_urls['from'] : 2;
        $to = isset($find_urls['to']) ? (int) $find_urls['to'] : 3;

        if (isset($find_urls['page'])) {
            $page = htmlspecialchars(base64_decode($find_urls['page']));

            $page_first = $page;
            $page_sec = '';
            if (strstr($page, '$1')) {
                $page_arr = explode('$1', $page);
                $page_first = $page_arr[0];
                $page_sec = isset($page_arr[1]) ? $page_arr[1] : '';
            }

            for ($i = $from; $i <= $to; $i++) {
                $urls[] = $page_first . $i . $page_sec;
            }
        }
        $reg = isset($find_urls['match']) ? base64_decode($find_urls['match']) : '';
        $wait = isset($find_urls['wait']) ? (int) $find_urls['wait'] : 1;

        $cid = $campaign->id;
        $ret = $this->parse_urls($cid, $reg, $urls, $options, $wait, $preview);

        return $ret;
    }

    public function cron_urls($campaign, $preview = true) {
        $options = $this->get_options($campaign);
        $cid = $campaign->id;

        if ($campaign->type == 1) {
            $ret = $this->find_urls_yt($cid, $options, '', $preview);
        } else {
            $cron_urls = $options['cron_urls'];

            $urls = array();
            if (isset($cron_urls['page']) && $cron_urls['page'] != '') {
                $urls[] = htmlspecialchars(base64_decode($cron_urls['page']));
            }

            $reg = isset($cron_urls['match']) ? base64_decode($cron_urls['match']) : '';
            $wait = 0;
            $ret = $this->parse_urls($cid, $reg, $urls, $options, $wait, $preview);
        }
        return $ret;
    }

    public function find_all_urls_yt($campaign, $preview = false) {
        /*
          'yt_force_update' => 1,
          'yt_page' => '',
          'yt_parse_num' => 50,
          'yt_pr_num' => 50,

          'yt_urls' => array(
          'per_page' => 50,
          'cron_page' => 50,
          'last_update' => 0,
          'status' => 0,
         */
        $options = $this->get_options($campaign);
        $cnt = $options['yt_urls']['per_page'];
        $cid = $campaign->id;

        // Get data from first page
        $first_page = $this->find_urls_yt($cid, $options, '', $preview);
        if ($preview) {
            return $first_page;
        }

        $total_add = 0;


        $next = $first_page['next'];
        $total_found = (int) $first_page['total'];

        $total_parsed = $total_found;
        if ($next && $total_found) {
            for ($i = 0; $i < $total_found; $i += $cnt) {
                $result = $this->find_urls_yt($cid, $options, $next);

                if ($result['urls']) {
                    $total_parsed += sizeof($result['urls']);
                }
                if ($result['add_urls']) {
                    $total_add += sizeof($result['add_urls']);
                }

                $next = $result['next'];
                if (!$next) {
                    break;
                }
            }
        }
        return array('found' => $total_parsed, 'add' => $total_add);
    }

    private function find_urls_yt($cid, $options, $next = '', $preview = false) {
        $cnt = $options['yt_urls']['per_page'];
        $client_id = base64_decode($options['yt_page']);

        $new_urls_weight = $options['new_urls_weight'];

        $ret = array();
        $ret['urls'] = array();

        if ($client_id) {
            $responce = $this->youtube_get_videos($client_id, $cnt, $next);
            if ($responce) {
                $total_found = $responce->pageInfo->totalResults;
                $ret['total'] = $total_found;
                $ret['next'] = $responce->nextPageToken;

                if ($responce->items) {
                    foreach ($responce->items as $item) {
                        $id = $item->id->videoId;
                        $url = $this->youtube_url . $id;
                        $ret['urls'][] = $url;
                        if ($preview) {
                            continue;
                        }
                        if ($this->add_url($cid, $url, $new_urls_weight)) {
                            $ret['add_urls'][] = $url;
                        }
                    }
                }
            }
            $ret['responce'] = $responce;
        }
        return $ret;
    }

    private function parse_urls($cid, $reg, $urls, $options, $wait, $preview) {

        $ret = array();
        $new_urls_weight = $options['new_urls_weight'];

        if ($reg && $urls) {
            foreach ($urls as $url) {
                $url = htmlspecialchars_decode($url);
                $code = $this->get_proxy($url, '', $headers);
                if (preg_match_all($reg, $code, $match)) {
                    foreach ($match[1] as $u) {
                        if (preg_match('#^/#', $u)) {
                            // Short links
                            $domain = preg_replace('#^([^\/]+\:\/\/[^\/]+)(\/|\?|\#).*#', '$1', $url . '/');
                            $u = $domain . $u;
                        }

                        if (!$preview) {
                            $add = $this->add_url($cid, $u, $new_urls_weight);
                            if ($add) {
                                $ret['add_urls'][] = $u;
                            }
                        }

                        $ret['urls'][] = $u;
                    }
                }

                if ($preview) {
                    $ret['content'] = $code;
                    $ret['headers'] = $headers;
                    break;
                } else {
                    sleep($wait);
                }
            }
        }

        return $ret;
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

    /*
     * URLs
     */

    private function add_urls($id, $add_urls, $options) {
        if (strstr($add_urls, "\n")) {
            $list = explode("\n", $add_urls);
        } else {
            $list = array($add_urls);
        }

        $new_urls_weight = $options['new_urls_weight'];

        $count = 0;
        foreach ($list as $url) {
            $url = trim($url);
            if ($url) {
                $add = $this->add_url($id, $url, $new_urls_weight);
                if ($add) {
                    $count += 1;
                }
            }
        }
        if ($count) {
            $message = 'Add new URLs: ' . $count;
            $this->log_info($message, $id, 0, 1);
        }
    }

    public function add_url($cid, $link, $weight = 0) {
        $link_hash = $this->link_hash($link);
        $url_exist = $this->get_url_by_hash($link_hash);
        if ($url_exist) {
            // URL already exist in another campaign            
            if ($weight > 0 && $cid != $url_exist->cid) {
                // Check old campaign weight
                $old_weight = $this->get_campaign_weight($url_exist->cid);
                if ($weight > $old_weight) {
                    $this->update_url_campaing($url_exist->id, $cid);
                    $message = 'Update URL campaign from ' . $url_exist->cid . ' to ' . $cid;
                    $this->log_info($message, $cid, $url_exist->id, 1);
                }
            }
            return 0;
        }
        /*
          `cid` int(11) NOT NULL DEFAULT '0',
          `pid` int(11) NOT NULL DEFAULT '0',
          `status` int(11) NOT NULL DEFAULT '0',
          `link_hash` varchar(255) NOT NULL default '',
          `link` text default NULL,
         */

        $pid = 0;
        // Status 'NEW'
        $status = 0;

        // Post exist?
        $post = $this->cm->get_post_by_link_hash($link_hash);
        if ($post) {
            $pid = $post->id;
            $status = 1;
        }

        $sql = sprintf("INSERT INTO {$this->db['url']} (cid,pid,status,link_hash,link) "
                . "VALUES ('%d','%d','%d','%s','%s')", (int) $cid, (int) $pid, (int) $status, $link_hash, $this->escape($link));

        $this->db_query($sql);

        // Return id
        $id = $this->getInsertId('id', $this->db['url']);


        return $id;
    }

    public function get_url($id) {
        $sql = sprintf("SELECT * FROM {$this->db['url']} WHERE id = %d", (int) $id);
        $result = $this->db_fetch_row($sql);
        return $result;
    }

    public function get_url_by_hash($link_hash) {
        $sql = sprintf("SELECT id, cid FROM {$this->db['url']} WHERE link_hash = '%s'", $link_hash);
        $result = $this->db_fetch_row($sql);
        return $result;
    }

    public function get_url_status($status) {
        return isset($this->url_status[$status]) ? $this->url_status[$status] : 'None';
    }

    public function get_url_status_count($cid = 0, $aid = 0) {
        $status = -1;
        $count = $this->get_urls_count($status, $cid);
        $states = array(
            '-1' => array(
                'title' => 'All',
                'count' => $count
            )
        );
        foreach ($this->url_status as $key => $value) {
            $states[$key] = array(
                'title' => $value,
                'count' => $this->get_urls_count($key, $cid));
        }
        return $states;
    }

    public function get_post_meta_types($cid = 0, $status = -1) {

        $count = $this->get_urls_count($status, $cid);
        $states = array(
            '-1' => array(
                'title' => 'All',
                'count' => $count
            )
        );
        foreach ($this->post_meta_status as $key => $value) {
            $states[$key] = array(
                'title' => $value,
                'count' => $this->get_urls_count($status, $cid, $key));
        }
        return $states;
    }

    public function get_urls($status = -1, $page = 1, $cid = 0, $meta_type = -1, $orderby = '', $order = 'ASC') {
        $status_trash = 2;
        $status_query = " WHERE status != " . $status_trash;
        if ($status != -1) {
            $status_query = " WHERE status = " . (int) $status;
        }

        $page -= 1;
        $start = $page * $this->perpage;

        $limit = '';
        if ($this->perpage > 0) {
            $limit = " LIMIT $start, " . $this->perpage;
        }

        // Company id
        $cid_and = '';
        if ($cid > 0) {
            $cid_and = sprintf(" AND cid=%d", (int) $cid);
        }


        // Sort
        $and_orderby = '';
        if ($orderby && in_array($orderby, $this->cm->sort_pages)) {
            $and_orderby = ' ORDER BY ' . $orderby;
            if ($order) {
                $and_orderby .= ' ' . $order;
            }
        } else {
            $and_orderby = " ORDER BY id DESC";
        }

        // Post type filter
        $meta_type_and = '';
        if ($meta_type != -1) {
            if ($meta_type == 1) {
                $meta_type_and = " AND pid != 0";
            } else {
                $meta_type_and = " AND pid = 0";
            }
        }

        $query = "SELECT * FROM {$this->db['url']} " . $status_query . $cid_and . $meta_type_and . $and_orderby . $limit;
        $result = $this->db_results($query);
        return $result;
    }

    public function get_all_urls($cid) {
        $query = sprintf("SELECT * FROM {$this->db['url']} WHERE cid=%d AND status!=2", $cid);
        $result = $this->db_results($query);
        return $result;
    }

    public function get_last_urls($count = 10, $status = -1, $cid = 0) {
        $status_trash = 2;
        $status_query = " WHERE status != " . $status_trash;
        if ($status != -1) {
            $status_query = " WHERE status = " . (int) $status;
        }

        // Company id
        $cid_and = '';
        if ($cid > 0) {
            $cid_and = sprintf(" AND cid=%d", (int) $cid);
        }

        $query = sprintf("SELECT * FROM {$this->db['url']}" . $status_query . $cid_and . " ORDER BY id DESC LIMIT %d", $count);
        $result = $this->db_results($query);
        return $result;
    }

    public function get_urls_count($status = -1, $cid = 0, $meta_type = -1, $linked = false) {

        // Custom status
        $status_trash = 2;
        $status_query = " WHERE u.status != " . $status_trash;
        if ($status != -1) {
            $status_query = " WHERE u.status = " . (int) $status;
        }

        // Company id
        $cid_and = '';
        if ($cid > 0) {
            $cid_and = sprintf(" AND u.cid=%d", (int) $cid);
        }

        // Post type filter
        $meta_type_and = '';
        if ($meta_type != -1) {
            if ($meta_type == 1) {
                $meta_type_and = " AND u.pid!=0";
            } else {
                $meta_type_and = " AND u.pid=0";
            }
        }
        // Linked
        $linked_and = '';
        $linked_inner = '';
        if ($linked) {
            $linked_inner = " INNER JOIN {$this->db['posts']} p ON p.id=u.pid";
            $linked_and = ' AND p.top_movie>0';
        }

        $query = "SELECT COUNT(*) FROM {$this->db['url']} u" . $linked_inner . $status_query . $meta_type_and . $linked_and . $cid_and;

        $result = $this->db_get_var($query);
        return $result;
    }

    public function get_options($campaign) {
        $options = unserialize($campaign->options);
        foreach ($this->def_options['options'] as $key => $value) {
            if (!isset($options[$key])) {
                // replace empty settings to default
                $options[$key] = $value;
            }
        }
        return $options;
    }

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

    /*
     * Rules
     */

    public function show_rules($rules = array(), $edit = true, $check = array(), $ctype = 0) {
        if ($rules || $edit) {
            $disabled = '';
            if (!$edit) {
                $disabled = ' disabled ';
                $title = __('Rules filter');
                ?>
                <h2><?php print $title ?></h2>            
            <?php } ?>
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
                                        <?php
                                        foreach ($this->rules_fields as $key => $value) {
                                            if ($ctype == 1 && $key == 'a') {
                                                continue;
                                            }
                                            ?>
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
                                    <?php
                                    foreach ($this->rules_fields as $key => $value) {
                                        if ($ctype == 1 && $key == 'a') {
                                            continue;
                                        }
                                        ?>
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

    private function check_post_rules($rules, $status, $test_post, $all = false) {
        $check = '';
        if ($rules && $test_post) {
            $check = $this->check_post($rules, $test_post, $all);
            if ($check) {
                foreach ($check as $key => $action) {
                    if ($action != $status) {
                        // Change post status
                        $status = $action;
                        break;
                    }
                }
            }
        }
        return array('data' => $check, 'status' => $status);
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

    /*
     * Rules parser
     */

    public function sort_reg_rules_by_weight($rules) {
        $sort_rules = $rules;
        if ($rules) {
            $rules_w = array();
            foreach ($rules as $key => $value) {
                $rules_w[$key] = $value['w'];
            }
            asort($rules_w);
            $sort_rules = array();
            foreach ($this->parser_rules_fields as $id => $item) {
                foreach ($rules_w as $key => $value) {
                    if ($rules[$key]['f'] == $id) {
                        $sort_rules[$key] = $this->get_valid_parser_rule($rules[$key]);
                    }
                }
            }
        }
        return $sort_rules;
    }

    public function get_valid_parser_rule($rule) {
        foreach ($this->def_reg_rule as $key => $value) {
            if (!isset($rule[$key])) {
                $rule[$key] = $value;
            }
        }

        return $rule;
    }

    public function check_reg_post($rules, $content, $rule_type = '') {
        $results = array();
        $rule_type_exist = 0;
        if ($rules && sizeof($rules)) {
            $rules_w = $this->sort_reg_rules_by_weight($rules);

            /*
             * (
              [f] => a
              [t] => x
              [r] => Ly9kaXZbQGNsYXNzPSdhcnRpY2xlLWhlYWRlcl9fbWV0YS1hdXRob3ItY29udGFpbmVyJ10vYQ==
              [m] =>
              [c] =>
              [w] => 0
              [a] => 1
              )
             */

            foreach ($this->parser_rules_fields as $type => $title) {
                if ($rule_type && $type != $rule_type) {
                    continue;
                }
                $i = 0;
                foreach ($rules_w as $key => $rule) {
                    if ($type == $rule['f']) {
                        if ($rule['a'] != 1) {
                            continue;
                        }
                        if ($rule['n'] == 1) {
                            $i += 1;
                        }

                        if (!isset($results[$type][$i])) {
                            $results[$type][$i] = $content;
                        }
                        $results[$type][$i] = $this->use_reg_rule($rule, $results[$type][$i]);

                        if ($rule_type && $rule_type == $type) {
                            $rule_type_exist = 1;
                        }
                    }
                }
            }
        }

        //implode results
        $ret = array();
        foreach ($results as $type => $items) {
            $ret[$type] = implode('', $items);
        }

        if ($rule_type && $rule_type_exist == 0) {
            $ret[$rule_type] = $content;
        }

        return $ret;
    }

    public function check_reg_post_yt($rules, $item) {
        /*
          $item = array(
          'u' => $link,
          'd' => $desc,
          't' => $title,
          );
         */
        foreach ($item as $key => $content) {
            if ($key == 'u') {
                continue;
            }
            $check_key = $this->check_reg_post($rules, $content, $key);
            $item[$key] = $check_key[$key];
        }
        return $item;
    }

    private function use_reg_rule($rule, $content) {
        $reg = base64_decode($rule['r']);

        if ($rule['t'] == 'x') {
            $content = $this->get_dom($reg, $rule['m'], $content);
        } else if ($rule['t'] == 'm') {
            $content = $this->get_reg_match($reg, $rule['m'], $content);
        } else if ($rule['t'] == 'r') {
            $content = $this->get_reg($reg, $rule['m'], $content);
        }

        return $content;
    }

    private function parser_rules_form($form_state) {
        $rule_exists = array();

        $to_remove = isset($form_state['remove_reg_rule']) ? $form_state['remove_reg_rule'] : array();

        // Exists rules
        foreach ($form_state as $name => $value) {
            if (strstr($name, 'rule_reg_id_')) {
                $key = $value;
                if (in_array($key, $to_remove)) {
                    continue;
                }
                $upd_rule = array(
                    'f' => $form_state['rule_reg_f_' . $key],
                    't' => $form_state['rule_reg_t_' . $key],
                    'r' => base64_encode(stripslashes($form_state['rule_reg_r_' . $key])),
                    'm' => $form_state['rule_reg_m_' . $key],
                    'c' => $form_state['rule_reg_c_' . $key],
                    'w' => $form_state['rule_reg_w_' . $key],
                    'a' => $form_state['rule_reg_a_' . $key],
                    'n' => $form_state['rule_reg_n_' . $key]
                );
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
            $new_rule = array(
                'f' => $form_state['reg_new_rule_f'],
                't' => $form_state['reg_new_rule_t'],
                'r' => base64_encode(stripslashes($form_state['reg_new_rule_r'])),
                'm' => $form_state['reg_new_rule_m'],
                'c' => $form_state['reg_new_rule_c'],
                'w' => $form_state['reg_new_rule_w'],
                'a' => $form_state['reg_new_rule_a'],
                'n' => $form_state['reg_new_rule_n']
            );
            $rule_exists[$new_rule_key] = $new_rule;
        }

        ksort($rule_exists);

        return $rule_exists;
    }

    public function show_parser_rules($rules = array(), $edit = true, $type = 0, $check = array()) {
        if ($rules || $edit) {
            $rules = $this->sort_reg_rules_by_weight($rules);
            $disabled = '';

            $parser_rules_fields = $this->parser_rules_fields;
            if ($type == 1) {
                unset($parser_rules_fields['a']);
                unset($parser_rules_fields['y']);
            }

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
                        <th><?php print __('Type') ?></th>
                        <th><?php print __('Rule') ?></th>
                        <th><?php print __('Match') ?></th>
                        <th><?php print __('New') ?></th>
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
                                        foreach ($parser_rules_fields as $key => $name) {
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
                                        foreach ($this->parser_rules_type as $key => $name) {
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
                            <td colspan="10"><b><?php print __('Add a new rule') ?></b></td>        
                        </tr>
                        <tr>
                            <td></td>
                            <td>
                                <select name="reg_new_rule_f" class="condition">
                                    <?php foreach ($parser_rules_fields as $key => $name) { ?>
                                        <option value="<?php print $key ?>"><?php print $name ?></option>                                
                                        <?php
                                    }
                                    ?>                          
                                </select> 
                            </td>
                            <td>
                                <select name="reg_new_rule_t" class="condition">
                                    <?php foreach ($this->parser_rules_type as $key => $name) { ?>
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

    public function show_check($check) {
        $ret = '';
        if ($check['data']) {
            foreach ($check['data'] as $key => $value) {
                $ret = 'Result: <b>' . $this->rules_actions[$value] . '</b>. Rule id: ' . $key;
                break;
            }
        }
        return $ret;
    }

    public function find_movies_queue($ids) {
        $ret = false;
        if ($ids) {
            // get options
            $opt_key = 'feed_matic_search_ids';
            $ids_str = $this->get_option($opt_key, '');
            $opt_ids = array();
            if ($ids_str) {
                $opt_ids = unserialize($ids_str);
            }

            foreach ($ids as $id) {
                $url = $this->get_url($id);
                if ($url->pid) {
                    if (!in_array($url->pid, $opt_ids)) {
                        $opt_ids[] = $url->pid;
                        $ret = true;
                    }
                }
            }
            if ($ret) {
                $ids_str = serialize($opt_ids);
                update_option($opt_key, $ids_str);
            }
        }

        return $ret;
    }

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
            update_option($opt_key, $ids_str);
        }
    }

    public function url_update_link_hash($id, $link) {
        if ($link) {
            $link_hash = $this->link_hash($link);
            $sql = sprintf("UPDATE {$this->db['url']} SET link_hash='%s' WHERE id=%d", $link_hash, (int) $id);
            $this->db_query($sql);
            return $link_hash;
        }
        return '';
    }

    public function update_dublicate_post($item) {
        // UNUSED
        $link_post = $this->cm->get_post_by_link_hash_type($item->link_hash, array(), array(3));
        $link_post_parser = $this->cm->get_post_by_link_hash_type($item->link_hash, array(3), array());
        if ($link_post) {
            $item_type = $this->cm->get_post_type($link_post->type);
            print 'Other:' . $item_type;
        }
        if ($link_post_parser) {
            $item_type = $this->cm->get_post_type($link_post_parser->type);
            if ($link_post) {
                // remove dublicate post
                if ($link_post_parser->status != 2) {
                    print ' Trash dublicate - ';
                    $this->cm->trash_post_by_id($link_post_parser->id);
                }
                // change url status
                if ($item->status == 5) {
                    print ' Update URL ';
                    $new_item = $item;
                    // exist
                    $new_item->status = 1;
                    $new_item->pid = $link_post->id;
                    $this->update_url($new_item);
                }
            }
            print 'Parser:' . $item_type;
        }
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
      0 => 'Ignore post',
      1 => 'Add post',
      2 => 'Error',
      3 => 'Auto stop',
      4 => 'Done',
      5 => 'Add URLs',
     */

    public function log($message, $cid = 0, $uid = 0, $type = 0, $status = 0) {
        $time = $this->curr_time();
        $this->db_query(sprintf("INSERT INTO {$this->db['log']} (date, cid, uid, type, status, message) VALUES (%d, %d, %d, %d, %d, '%s')", $time, $cid, $uid, $type, $status, $this->escape($message)));
    }

    public function get_log($page = 1, $cid = 0, $uid = 0, $status = -1, $type = -1, $perpage = 30) {
        $page -= 1;
        $start = $page * $perpage;

        $limit = '';
        if ($perpage > 0) {
            $limit = " LIMIT $start, " . $perpage;
        }
        $and_cid = '';
        if ($cid) {
            $and_cid = sprintf(" AND cid=%d", (int) $cid);
        }

        $and_uid = '';
        if ($uid) {
            $and_uid = sprintf(" AND uid=%d", (int) $uid);
        }

        $and_status = '';
        if ($status != -1) {
            $and_status = sprintf(" AND status=%d", (int) $status);
        }

        $and_type = '';
        if ($type != -1) {
            $and_type = sprintf(" AND type=%d", (int) $type);
        }

        $order = " ORDER BY id DESC";
        $sql = sprintf("SELECT id, date, cid, uid, type, status, message FROM {$this->db['log']} WHERE id>0" . $and_cid . $and_uid . $and_status . $and_type . $order . $limit);

        $result = $this->db_results($sql);

        return $result;
    }

    public function get_log_count($cid = 0, $status = -1, $type = -1) {

        $and_cid = '';
        if ($cid) {
            $and_cid = sprintf(" AND cid=%d", (int) $cid);
        }

        $and_status = '';
        if ($status != -1) {
            $and_status = sprintf(" AND status=%d", (int) $status);
        }

        $and_type = '';
        if ($type != -1) {
            $and_type = sprintf(" AND type=%d", (int) $type);
        }

        $query = "SELECT COUNT(*) FROM {$this->db['log']} WHERE id>0" . $and_cid . $and_status . $and_type;

        $result = $this->db_get_var($query);
        return $result;
    }

    public function get_last_log($url_id = 0, $parser_id = 0) {

        $and_uid = '';
        if ($url_id > 0) {
            $and_uid = sprintf(' AND uid=%d', $url_id);
        }

        $and_cid = '';
        if ($parser_id > 0) {
            $and_cid = sprintf(' AND cid=%d', $parser_id);
        }

        $query = sprintf("SELECT type, status, message FROM {$this->db['log']} WHERE id>0" . $and_uid . $and_cid . " ORDER BY id DESC", $url_id);
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

    /*
      0 => 'Other',
      1 => 'Find URLs',
      3 => 'Parsing',
     */

    public function log_info($message, $cid, $uid, $status) {
        $this->log($message, $cid, $uid, 0, $status);
    }

    public function log_warn($message, $cid, $uid, $status) {
        $this->log($message, $cid, $uid, 1, $status);
    }

    public function log_error($message, $cid, $uid, $status) {
        $this->log($message, $cid, $uid, 2, $status);
    }

    public function get_log_type($type) {
        return isset($this->log_type[$type]) ? $this->log_type[$type] : 'None';
    }

    public function get_log_status($type) {
        return isset($this->log_status[$type]) ? $this->log_status[$type] : 'None';
    }

    public function log_campaign_add_urls($message, $cid) {
        $this->log($message, $cid, 0, 0, 5);
    }

    public function get_post_log_status($cid = 0) {

        $count = $this->get_log_count($cid);
        $states = array(
            '-1' => array(
                'title' => 'All',
                'count' => $count
            )
        );
        foreach ($this->log_status as $key => $value) {
            $states[$key] = array(
                'title' => $value,
                'count' => $this->get_log_count($cid, $key));
        }
        return $states;
    }

    public function get_post_log_types($cid = 0, $status = -1) {

        $count = $this->get_log_count($cid, $status);
        $states = array(
            '-1' => array(
                'title' => 'All',
                'count' => $count
            )
        );
        foreach ($this->log_type as $key => $value) {
            $states[$key] = array(
                'title' => $value,
                'count' => $this->get_log_count($cid, $status, $key));
        }
        return $states;
    }

    public function clear_all_logs() {
        $sql = "DELETE FROM {$this->db['log']} WHERE id>0";
        $this->db_query($sql);
    }

    /*
     * Youtube API
     */

    public function yt_total_posts($options) {

        $total = -1;
        $cid = base64_decode($options['yt_page']);
        $cnt = 5;

        if ($cid) {
            try {
                $responce = $this->youtube_get_videos($cid, $cnt);

                if ($responce) {
                    $total = $responce->pageInfo->totalResults;
                }
            } catch (Exception $exc) {

                print $exc->getTraceAsString();
            }
        }
        return $total;
    }

    public function youtube_get_videos($cid = 0, $count = 50, $pageToken = '') {
        if (!$cid) {
            return;
        }
        $arg = array();
        $arg['cid'] = $cid;
        $arg['count'] = $count;
        if ($pageToken) {
            $arg['pageToken'] = $pageToken;
        }

        $filename = "ls-$cid-$count-$pageToken";
        $str = ThemeCache::cache('yt_listSearch', false, $filename, 'def', $this, $arg);
        $responce = json_decode(gzdecode($str));

        return $responce;
    }

    public function find_youtube_data_api($ids, $debug = false) {
        if (!$ids) {
            return;
        }

        $arg = array();
        $arg['ids'] = $ids;

        $id_name = md5(implode('-', $ids));
        $filename = "lv-$id_name";
        $str = ThemeCache::cache('yt_listVideos', false, $filename, 'def', $this, $arg);
        $response = json_decode(gzdecode($str));

        if ($debug) {
            print_r($response);
        }

        $ret = array();
        if ($response && isset($response->items)) {
            foreach ($response->items as $item) {
                $ret[$item->id] = $item->snippet;
            }
        }

        return $ret;
    }

    public function yt_listSearch($arg = array()) {
        $service = $this->init_gs();

        $queryParams = array(
            'channelId' => $arg['cid'],
            'maxResults' => $arg['count'],
            'order' => 'date',
            'type' => 'video'
        );

        if ($arg['pageToken']) {
            $queryParams['pageToken'] = $arg['pageToken'];
        }

        try {
            $response = $service->search->listSearch('snippet', $queryParams);
        } catch (Exception $exc) {
            $response = array();
        }
        return gzencode(json_encode($response));
    }

    public function yt_listVideos($arg = array()) {
        $service = $this->init_gs();

        $queryParams = [
            'id' => implode(',', $arg['ids'])
        ];

        try {
            $response = $service->videos->listVideos('snippet', $queryParams);
        } catch (Exception $exc) {
            $response = array();
        }
        return gzencode(json_encode($response));
    }

    /*
     * Other functions
     */

    public function get_proxy($url, $proxy = '', &$header = '') {

        $ch = curl_init();
        $ss = $this->cm->get_settings();
        $curl_user_agent = $ss['parser_user_agent'];

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_ENCODING, 'deflate');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
        curl_setopt($ch, CURLOPT_TIMEOUT, 25);
        curl_setopt($ch, CURLOPT_USERAGENT, $curl_user_agent);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        $cookie_path = $ss['parser_cookie_path'];

        if ($cookie_path) {
            curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_path);
            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_path);
        }

        if ($proxy)
            curl_setopt($ch, CURLOPT_PROXY, "$proxy");

        $response = curl_exec($ch);

        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $header_size);
        $body = substr($response, $header_size);

        curl_close($ch);

        return $body;
    }

    public function get_webdriver($url, &$header = '', $settings = '', $use_driver = -1) {

        $webdrivers_text = base64_decode($settings['web_drivers']);
        //http://165.227.101.220:8110/?p=ds1bfgFe_23_KJDS-F&url= http://185.135.80.156:8110/?p=ds1bfgFe_23_KJDS-F&url= http://148.251.54.53:8110/?p=ds1bfgFe_23_KJDS-F&url=
        $web_arr = array();
        if ($webdrivers_text) {
            if (strstr($webdrivers_text, "\n")) {
                $web_arr = explode("\n", $webdrivers_text);
            } else {
                $web_arr = array($webdrivers_text);
            }
        }

        if (!$web_arr) {
            return 'No webdrivers found';
        }

        if ($use_driver != -1) {
            if (!isset($web_arr[$use_driver])) {
                return 'Webdriver not found, ' . $use_driver;
            }
        }

        $current_driver = trim($web_arr[array_rand($web_arr, 1)]);
        $url = $current_driver . $url;

        $ch = curl_init();
        $ss = $settings ? $settings : array();
        $curl_user_agent = isset($ss['parser_user_agent']) ? $ss['parser_user_agent'] : '';
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_ENCODING, 'deflate');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        if ($curl_user_agent) {
            curl_setopt($ch, CURLOPT_USERAGENT, $curl_user_agent);
        }
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        curl_setopt($ch, CURLINFO_HEADER_OUT, true); // enable tracking


        $response = curl_exec($ch);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headerSent = curl_getinfo($ch, CURLINFO_HEADER_OUT); // request headers
        $header_responce = substr($response, 0, $header_size);

        $header = "RESPONCE:\n" . $header_responce . "\nREQUEST:\n" . $headerSent;
        $body = substr($response, $header_size);

        curl_close($ch);

        return $body;
    }

    public function send_curl_no_responce($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_ENCODING, 'deflate');
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $response = curl_exec($ch);
        curl_close($ch);
    }

    public function absoluteUrlFilter($domain, $content) {
        // $content = '<a href="/example.com"></a><a href=/example.com ></a><img src="/testimg"><img src=/testimg2.jpg >';    
        $reg = "#(?:src|href)[ ]*=[ ]*(?:\"|'|)(/[^/]+[^\"' >]+)(?:\"|'|)(?: |>)#";
        if (preg_match_all($reg, $content, $match)) {
            if (count($match[1]) > 0) {
                for ($i = 0; $i < count($match[1]); $i++) {
                    $newlink = str_replace($match[1][$i], $domain . $match[1][$i], $match[0][$i]);
                    $content = str_replace($match[0][$i], $newlink, $content);
                }
            }
        }
        return $content;
    }

    private function get_dom_commands($text) {
        $ret = array();
        $text = preg_replace('/##.*/', '', $text);
        if ($text) {
            if (strstr($text, "\n")) {
                $list = explode("\n", $text);
            } else {
                $list = array($text);
            }

            foreach ($list as $item) {
                $command = trim($item);
                if ($command) {
                    $ret[] = $command;
                }
            }
        }
        return $ret;
    }

    private function get_regexps($text) {
        $text = preg_replace('/##.*/', '', $text);
        $ret = array();
        if ($text) {
            if (strstr($text, "\n")) {
                $list = explode("\n", $text);
            } else {
                $list = array($text);
            }
            foreach ($list as $item) {
                if ($item) {
                    if (strstr($item, ";")) {
                        $command = explode(";", $item);
                    } else {
                        $command = array($item, '');
                    }
                    $ret[] = array(trim($command[0]), trim($command[1]));
                }
            }
        }
        return $ret;
    }

}
