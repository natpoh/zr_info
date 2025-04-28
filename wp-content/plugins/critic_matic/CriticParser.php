<?php
/*
 * Critic parser
 */

class CriticParser extends AbstractDB {

    // Critic matic
    public $cm;
    // Movie parser
    public $mp;
    public $cpyoutube;
    public $cprules;
    public $def_options;
    public $parser_settings = '';
    public $parser_settings_def = '';
    public $arhive_path = ABSPATH . 'wp-content/uploads/critic_parser/arhive/';
    public $previews_number = array(1 => 1, 5 => 5, 10 => 10, 20 => 20);
    public $log_modules = array(
        'arhive' => 2,
        'parsing' => 3,
    );

    public function __construct($cm = '') {
        $this->cm = $cm ? $cm : new CriticMatic();

        //$table_prefix = DB_PREFIX_WP;
        $this->db = array(
            'posts' => DB_PREFIX_WP_AN . 'critic_matic_posts',
            'url' => DB_PREFIX_WP_AN . 'critic_parser_url',
            // Critic Parser
            'campaign' => DB_PREFIX_WP_AN . 'critic_parser_campaign',
            'log' => DB_PREFIX_WP_AN . 'critic_parser_log',
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
                    'last_update_all' => 0,
                    'last_update' => 0,
                    'status' => 1,
                ),
                'service_urls' => array(
                    'webdrivers' => 0,
                    'del_pea' => 0,
                    'del_pea_cnt' => 10,
                    'tor_h' => 20,
                    'tor_d' => 100,
                    'tor_mode' => 2,
                    'progress' => 0,
                    'weight' => 0,
                ),
                'arhive' => array(
                    'last_update' => 0,
                    'interval' => 60,
                    'num' => 10,
                    'yt_num' => 50,
                    'status' => 0,
                    'proxy' => 0,
                    'webdrivers' => 0,
                    'random' => 1,
                    'progress' => 0,
                    'del_pea' => 0,
                    'del_pea_int' => 1440,
                    'tor_h' => 20,
                    'tor_d' => 100,
                    'tor_mode' => 0,
                    'body_len' => 500,
                    'chd' => '',
                ),
                'yt_playlists' => array(),
            ),
            'max_error' => $this->parser_settings['max_error'],
        );
        $this->get_perpage();
    }

    public function get_cm() {
        return $this->cm;
    }

    public function get_mp() {
        // Get movies parser
        if (!$this->mp) {
            if (!class_exists('MoviesLinks')) {
                !defined('MOVIES_LINKS_PLUGIN_DIR') ? define('MOVIES_LINKS_PLUGIN_DIR', ABSPATH . 'wp-content/plugins/movies_links/') : '';
                require_once( MOVIES_LINKS_PLUGIN_DIR . 'db/MoviesAbstractFunctions.php' );
                require_once( MOVIES_LINKS_PLUGIN_DIR . 'db/MoviesAbstractDB.php' );
                require_once( MOVIES_LINKS_PLUGIN_DIR . 'MoviesLinks.php' );
            }

            $ml = new MoviesLinks();
            // get parser
            $this->mp = $ml->get_mp();
        }
        return $this->mp;
    }

    public function get_cpyoutube() {
        // Get movies parser
        if (!$this->cpyoutube) {
            $this->cpyoutube = new CPYoutube($this);
        }
        return $this->cpyoutube;
    }

    public function get_cprules() {
        // Get cp rules
        if (!$this->cprules) {
            $this->cprules = new CPRules();
        }
        return $this->cprules;
    }

    public function get_perpage() {
        $this->perpage = isset($_GET['perpage']) ? (int) $_GET['perpage'] : $this->perpage;
        return $this->perpage;
    }

    /*
     * Core
     */

    public function run_cron($cron_type = 1, $force = false, $debug = false, $cid = 0) {
        $cron = new CPCron($this);
        $count = $cron->proccess_all($cron_type, $force, $debug, $cid);
        return $count;
    }

    public function stop_module($campaign, $module, $options = array()) {
        $options_upd = array();
        if (!$options) {
            $options = $this->get_options($campaign);
        }
        if (isset($options[$module])) {
            $status = $options[$module]['status'];
            // Update status
            if ($status != 0) {
                $options_upd[$module]['status'] = 0;
            }
        }

        if ($options_upd) {
            $this->update_campaign_options($campaign->id, $options_upd);
            $message = 'Stop module: ' . $module;
            $mtype = $this->log_modules[$module] ? $this->log_modules[$module] : 0;

            $this->log_info($message, $campaign->id, 0, $mtype);
        }
    }

    public function pause_module($campaign, $module, $options = array()) {
        $options_upd = array();
        if (!$options) {
            $options = $this->get_options($campaign);
        }
        if (isset($options[$module])) {
            $status = $options[$module]['status'];
            // Update status
            if ($status == 1) {
                $options_upd[$module]['status'] = 3;
            }
        }

        if ($options_upd) {
            $this->update_campaign_options($campaign->id, $options_upd);
            $message = 'Paused module: ' . $module;
            $mtype = $this->log_modules[$module] ? $this->log_modules[$module] : 0;

            $this->log_info($message, $campaign->id, 0, $mtype);
        }
    }

    public function start_paused_module($campaign, $module, $options = array()) {
        $options_upd = array();
        if (!$options) {
            $options = $this->get_options($campaign);
        }
        if (isset($options[$module])) {
            $status = $options[$module]['status'];
            // Update status
            if ($status == 3) {
                $options_upd[$module]['status'] = 1;
            }
        }

        if ($options_upd) {
            $this->update_campaign_options($campaign->id, $options_upd);
            $message = 'Module unpaused: ' . $module;
            $mtype = $this->log_modules[$module] ? $this->log_modules[$module] : 0;

            $this->log_info($message, $campaign->id, 0, $mtype);
        }
    }

    public function start_module($campaign, $module, $options = array()) {
        $options_upd = array();
        if (!$options) {
            $options = $this->get_options($campaign);
        }
        if (isset($options[$module])) {
            $status = $options[$module]['status'];
            // Update status
            if ($status != 1) {
                $options_upd[$module]['status'] = 1;
            }
        }

        if ($options_upd) {
            $this->update_campaign_options($campaign->id, $options_upd);
            $message = 'Module start: ' . $module;
            $mtype = $this->log_modules[$module] ? $this->log_modules[$module] : 0;

            $this->log_info($message, $campaign->id, 0, $mtype);
        }
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
        $this->update_option('critic_parser_settings', serialize($ss));
    }

    /*
     * Campaigns
     */

    public function get_campaigns($status = 1) {
        $status_query = '';
        if ($status > 0) {
            $status_query = " AND status = " . (int) $status;
        }
        $query = "SELECT * FROM {$this->db['campaign']} WHERE id>0 {$status_query} ORDER BY id DESC";
        $result = $this->db_results($query);
        $items = array();
        if ($result) {
            foreach ($result as $item) {
                $items[$item->id] = $item;
            }
        }
        return $items;
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

    public function get_options($campaign) {
        $options = unserialize($campaign->options);

        foreach ($this->def_options['options'] as $key => $value) {
            if (!isset($options[$key])) {
                // replace empty settings to default
                $options[$key] = $value;
            }
            if ($key == 'arhive') {
                foreach ($this->def_options['options'][$key] as $ckey => $cvalue) {
                    if (!isset($options[$key][$ckey])) {
                        $options[$key][$ckey] = $cvalue;
                    }
                }
            }
        }
        return $options;
    }

    public function update_campaign($id, $data = array()) {
        $this->db_update($data, $this->db['campaign'], $id);
    }

    public function update_campaign_options($id, $options) {

        // 1. Get options
        $campaign = $this->get_campaign($id, false);
        $opt_prev = $this->get_options($campaign);
        $update = false;
        // 2. Get new options
        if ($options) {
            foreach ($options as $key => $value) {
                if (!isset($opt_prev[$key])) {
                    $opt_prev[$key] = $value;
                    $update = true;
                } else {
                    if (is_array($opt_prev[$key])) {
                        // Value with childs
                        foreach ($options[$key] as $ckey => $cvalue) {
                            if (!isset($opt_prev[$key][$ckey])) {
                                // Add child
                                $opt_prev[$key][$ckey] = $cvalue;
                                $update = true;
                            } else {
                                // Update child
                                if ($opt_prev[$key][$ckey] != $cvalue) {
                                    $opt_prev[$key][$ckey] = $cvalue;
                                    $update = true;
                                }
                            }
                        }
                    } else {
                        // String value
                        if ($opt_prev[$key] != $value) {
                            $opt_prev[$key] = $value;
                            $update = true;
                        }
                    }
                }
            }
        }
        if ($update) {
            // 3. Update options
            $opt_str = serialize($opt_prev);
            $sql = sprintf("UPDATE {$this->db['campaign']} SET options='%s' WHERE id = %d", $opt_str, (int) $id);
            $this->db_query($sql);
        }
    }

    /*
     * URLs
     */

    public function get_url($id) {
        $sql = sprintf("SELECT * FROM {$this->db['url']} WHERE id = %d", (int) $id);
        $result = $this->cm->db_fetch_row($sql);
        return $result;
    }

    public function get_url_by_post($pid) {
        $sql = sprintf("SELECT * FROM {$this->db['url']} WHERE pid = %d", (int) $pid);
        $result = $this->cm->db_fetch_row($sql);
        return $result;
    }

    public function get_url_by_hash($link_hash) {
        $sql = sprintf("SELECT id, cid FROM {$this->db['url']} WHERE link_hash = '%s'", $link_hash);
        $result = $this->cm->db_fetch_row($sql);
        return $result;
    }

    public function get_last_urls($count = 10, $status = -1, $cid = 0, $random = 0, $debug = false, $custom_url_id = 0, $arhive = -1) {

        if ($custom_url_id > 0) {
            $query = sprintf("SELECT * FROM {$this->db['url']} WHERE id=%d", $custom_url_id);
            $result = $this->cm->db_results($query);
        } else {

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


            // Arhive
            $and_arhive = '';
            if ($arhive != -1) {
                if ($arhive == 0) {
                    $and_arhive = sprintf(" AND arhive_date=0", (int) $arhive);
                } else {
                    $and_arhive = sprintf(" AND arhive_date>%d", (int) $arhive);
                }
            }

            if ($random == 1) {
                if ($debug) {
                    print "Random URLs\n";
                }
                // Get all urls
                $query = "SELECT id FROM {$this->db['url']}" . $status_query . $cid_and . $and_arhive;
                if ($debug) {
                    print $query . "\n";
                }
                $items = $this->db_results($query);
                if ($items) {
                    $ids = array();
                    foreach ($items as $item) {
                        $ids[] = $item->id;
                    }
                    shuffle($ids);
                    $i = 1;
                    $random_ids = array();
                    foreach ($ids as $id) {
                        $random_ids[] = $id;
                        if ($i >= $count) {
                            break;
                        }
                        $i += 1;
                    }
                    // Get random urls
                    $query = "SELECT * FROM {$this->db['url']} WHERE id IN(" . implode(",", $random_ids) . ")";
                    if ($debug) {
                        print $query . "\n";
                    }
                    $result = $this->db_results($query);
                }
            } else {
                $query = sprintf("SELECT * FROM {$this->db['url']}" . $status_query . $cid_and . $and_arhive . " ORDER BY id DESC LIMIT %d", $count);
                $result = $this->db_results($query);
            }
        }
        return $result;
    }

    public function get_no_arhive_urls_count($cid = 0) {
        // Company id
        $cid_and = '';
        if ($cid > 0) {
            $cid_and = sprintf(" AND cid=%d", (int) $cid);
        }
        $query = "SELECT COUNT(*) FROM {$this->db['url']} WHERE status=0 {$cid_and} AND arhive_date=0";
        $result = $this->db_get_var($query);

        return $result;
    }

    public function get_no_arhive_urls($cid = 0, $count = 100) {
        // Company id
        $cid_and = '';
        if ($cid > 0) {
            $cid_and = sprintf(" AND cid=%d", (int) $cid);
        }
        $query = sprintf("SELECT * FROM {$this->db['url']} WHERE status=0 AND arhive_date=0" . $cid_and . " ORDER BY id DESC LIMIT %d", $count);
        $result = $this->db_results($query);
        return $result;
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

        $data = array(
            'cid' => $cid,
            'pid' => $pid,
            'date' => $this->curr_time(),
            'status' => $status,
            'link_hash' => $link_hash,
            'link' => $link,
        );

        // Return id
        //$id = $this->cm->db_insert($data, $this->db['url']);
        $id = $this->cm->sync_insert_data($data, $this->db['url']);

        return $id;
    }

    public function change_url_state($id, $status = 0) {
        $sql = sprintf("SELECT status FROM {$this->db['url']} WHERE id=%d", $id);
        $old_status = $this->cm->db_get_var($sql);
        if ($old_status != $status) {
            $data = array(
                'status' => $status,
                'last_upd' => $this->curr_time(),
            );
            //$this->cm->db_update($data, $this->db['url'], $id);
            $this->cm->sync_update_data($data, $id, $this->db['url']);
            return true;
        }
        return false;
    }

    public function change_url($id, $data = array()) {
        $data['last_upd'] = $this->curr_time();
        //$this->cm->db_update($data, $this->db['url'], $id);
        $this->cm->sync_update_data($data, $id, $this->db['url']);
        return true;
    }

    public function update_url_data($data, $id) {

        $data['last_upd'] = $this->curr_time();

        // $this->cm->db_update($data, $this->db['url'], $id);
        $this->cm->sync_update_data($data, $id, $this->db['url']);
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

        $data = array();
        foreach ($item as $key => $value) {
            if ($key == 'id') {
                continue;
            }
            $data[$key] = $value;
        }
        $data['last_upd'] = $this->curr_time();

        //$this->cm->db_update($data, $this->db['url'], $item->id);
        $this->cm->sync_update_data($data, $item->id, $this->db['url']);
    }

    public function update_url_campaing($id, $cid) {
        $data = array(
            'cid' => $cid,
            'last_upd' => $this->curr_time(),
        );
        //$this->cm->db_update($data, $this->db['url'], $id);
        $this->cm->sync_update_data($data, $id, $this->db['url']);
    }

    /*
     * Other functions
     */

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
                $this->update_option($opt_key, $ids_str);
            }
        }

        return $ret;
    }

    public function append_id($id) {
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

    public function url_update_link_hash($id, $link) {
        // UNUSED
        if ($link) {
            $link_hash = $this->link_hash($link);

            $data = array(
                'link_hash' => $link_hash,
                'last_upd' => $this->curr_time(),
            );
            //$this->cm->db_update($data, $this->db['url'], $id);
            $this->cm->sync_update_data($data, $id, $this->db['url']);

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

    /**
     * Balances tags of string using a modified stack.
     *
     * @since 2.0.4
     * @since 5.3.0 Improve accuracy and add support for custom element tags.
     *
     * @author Leonard Lin <leonard@acm.org>
     * @license GPL
     * @copyright November 4, 2001
     * @version 1.1
     * @todo Make better - change loop condition to $text in 1.2
     * @internal Modified by Scott Reilly (coffee2code) 02 Aug 2004
     *      1.1  Fixed handling of append/stack pop order of end text
     *           Added Cleaning Hooks
     *      1.0  First Version
     *
     * @param string $text Text to be balanced.
     * @return string Balanced text.
     */
    public function force_balance_tags($text) {
        $tagstack = array();
        $stacksize = 0;
        $tagqueue = '';
        $newtext = '';
        // Known single-entity/self-closing tags.
        $single_tags = array('area', 'base', 'basefont', 'br', 'col', 'command', 'embed', 'frame', 'hr', 'img', 'input', 'isindex', 'link', 'meta', 'param', 'source');
        // Tags that can be immediately nested within themselves.
        $nestable_tags = array('blockquote', 'div', 'object', 'q', 'span');

        // WP bug fix for comments - in case you REALLY meant to type '< !--'.
        $text = str_replace('< !--', '<    !--', $text);
        // WP bug fix for LOVE <3 (and other situations with '<' before a number).
        $text = preg_replace('#<([0-9]{1})#', '&lt;$1', $text);

        /**
         * Matches supported tags.
         *
         * To get the pattern as a string without the comments paste into a PHP
         * REPL like `php -a`.
         *
         * @see https://html.spec.whatwg.org/#elements-2
         * @see https://w3c.github.io/webcomponents/spec/custom/#valid-custom-element-name
         *
         * @example
         * ~# php -a
         * php > $s = [paste copied contents of expression below including parentheses];
         * php > echo $s;
         */
        $tag_pattern = (
                '#<' . // Start with an opening bracket.
                '(/?)' . // Group 1 - If it's a closing tag it'll have a leading slash.
                '(' . // Group 2 - Tag name.
                // Custom element tags have more lenient rules than HTML tag names.
                '(?:[a-z](?:[a-z0-9._]*)-(?:[a-z0-9._-]+)+)' .
                '|' .
                // Traditional tag rules approximate HTML tag names.
                '(?:[\w:]+)' .
                ')' .
                '(?:' .
                // We either immediately close the tag with its '>' and have nothing here.
                '\s*' .
                '(/?)' . // Group 3 - "attributes" for empty tag.
                '|' .
                // Or we must start with space characters to separate the tag name from the attributes (or whitespace).
                '(\s+)' . // Group 4 - Pre-attribute whitespace.
                '([^>]*)' . // Group 5 - Attributes.
                ')' .
                '>#' // End with a closing bracket.
                );

        while (preg_match($tag_pattern, $text, $regex)) {
            $full_match = $regex[0];
            $has_leading_slash = !empty($regex[1]);
            $tag_name = $regex[2];
            $tag = strtolower($tag_name);
            $is_single_tag = in_array($tag, $single_tags, true);
            $pre_attribute_ws = isset($regex[4]) ? $regex[4] : '';
            $attributes = trim(isset($regex[5]) ? $regex[5] : $regex[3]);
            $has_self_closer = '/' === substr($attributes, -1);

            $newtext .= $tagqueue;

            $i = strpos($text, $full_match);
            $l = strlen($full_match);

            // Clear the shifter.
            $tagqueue = '';
            if ($has_leading_slash) { // End tag.
                // If too many closing tags.
                if ($stacksize <= 0) {
                    $tag = '';
                    // Or close to be safe $tag = '/' . $tag.
                    // If stacktop value = tag close value, then pop.
                } elseif ($tagstack[$stacksize - 1] === $tag) { // Found closing tag.
                    $tag = '</' . $tag . '>'; // Close tag.
                    array_pop($tagstack);
                    $stacksize--;
                } else { // Closing tag not at top, search for it.
                    for ($j = $stacksize - 1; $j >= 0; $j--) {
                        if ($tagstack[$j] === $tag) {
                            // Add tag to tagqueue.
                            for ($k = $stacksize - 1; $k >= $j; $k--) {
                                $tagqueue .= '</' . array_pop($tagstack) . '>';
                                $stacksize--;
                            }
                            break;
                        }
                    }
                    $tag = '';
                }
            } else { // Begin tag.
                if ($has_self_closer) { // If it presents itself as a self-closing tag...
                    // ...but it isn't a known single-entity self-closing tag, then don't let it be treated as such
                    // and immediately close it with a closing tag (the tag will encapsulate no text as a result).
                    if (!$is_single_tag) {
                        $attributes = trim(substr($attributes, 0, -1)) . "></$tag";
                    }
                } elseif ($is_single_tag) { // Else if it's a known single-entity tag but it doesn't close itself, do so.
                    $pre_attribute_ws = ' ';
                    $attributes .= '/';
                } else { // It's not a single-entity tag.
                    // If the top of the stack is the same as the tag we want to push, close previous tag.
                    if ($stacksize > 0 && !in_array($tag, $nestable_tags, true) && $tagstack[$stacksize - 1] === $tag) {
                        $tagqueue = '</' . array_pop($tagstack) . '>';
                        $stacksize--;
                    }
                    $stacksize = array_push($tagstack, $tag);
                }

                // Attributes.
                if ($has_self_closer && $is_single_tag) {
                    // We need some space - avoid <br/> and prefer <br />.
                    $pre_attribute_ws = ' ';
                }

                $tag = '<' . $tag . $pre_attribute_ws . $attributes . '>';
                // If already queuing a close tag, then put this tag on too.
                if (!empty($tagqueue)) {
                    $tagqueue .= $tag;
                    $tag = '';
                }
            }
            $newtext .= substr($text, 0, $i) . $tag;
            $text = substr($text, $i + $l);
        }

        // Clear tag queue.
        $newtext .= $tagqueue;

        // Add remaining text.
        $newtext .= $text;

        while ($x = array_pop($tagstack)) {
            $newtext .= '</' . $x . '>'; // Add remaining tags to close.
        }

        // WP fix for the bug with HTML comments.
        $newtext = str_replace('< !--', '<!--', $newtext);
        $newtext = str_replace('<    !--', '< !--', $newtext);

        return $newtext;
    }

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
        //http://165.227.101.220:8110/?p=ds1bfgFe_23_KJDS-F&url= http://185.135.80.156:8110/?p=ds1bfgFe_23_KJDS-F&url= http://37.27.53.197:8110/?p=ds1bfgFe_23_KJDS-F&url=
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

    public function clear_read($url, $content = '', $proxy = '') {
        if (!$content) {
            $content = $this->get_proxy($url, $proxy, $header);
        }

        $result = false;
        $ret = array();

        if ($content) {
            // $content = "<body>Look at this cat: <img src='./cat.jpg'> 123 <img src=x onerror=alert(1)//></body>";
            // TODO move service and pass to options
            $pass = 'sdDclSPMF_32sd-s';
            $service = 'http://37.27.53.197:8980/';

            $data = array('p' => $pass, 'u' => $url, 'c' => $content);

            // use key 'http' even if you send the request to https://...
            $options = array(
                'http' => array(
                    'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                    'method' => 'POST',
                    'content' => http_build_query($data)
                )
            );
            $context = stream_context_create($options);
            $result = file_get_contents($service, false, $context);
        }

        if ($result) {
            $result_data = json_decode($result);

            if ($result_data) {
                $ret = array('title' => $result_data->title, 'author' => $result_data->author, 'content' => $result_data->content);
            }
        }

        return $ret;
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
      6 => 'Arhive URLs',
     */

    public function log($message, $cid = 0, $uid = 0, $type = 0, $status = 0) {
        $time = $this->curr_time();
        $this->db_query(sprintf("INSERT INTO {$this->db['log']} (date, cid, uid, type, status, message) VALUES (%d, %d, %d, %d, %d, '%s')", $time, $cid, $uid, $type, $status, $this->escape($message)));
    }

    /*
      0 => 'Other',
      1 => 'Find URLs',
      3 => 'Parsing',
     */

    public function log_info($message = '', $cid = 0, $uid = 0, $status = 0) {
        $this->log($message, $cid, $uid, 0, $status);
    }

    public function log_warn($message = '', $cid = 0, $uid = 0, $status = 0) {
        $this->log($message, $cid, $uid, 1, $status);
    }

    public function log_error($message = '', $cid = 0, $uid = 0, $status = 0) {
        $this->log($message, $cid, $uid, 2, $status);
    }

    public function log_campaign_add_urls($message = '', $cid = 0) {
        $this->log($message, $cid, 0, 0, 5);
    }

    public function clear_all_logs() {
        $sql = "DELETE FROM {$this->db['log']} WHERE id>0";
        $this->db_query($sql);
    }
}

class CPAdmin extends CriticParser {

    public $perpage = 30;
    public $camp_state = array(
        1 => 'Active',
        0 => 'Inactive',
        2 => 'Trash',
    );
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
    public $parser_type = array(
        0 => 'Default',
        1 => 'YouTube',
    );
    public $campaign_modules = array(
        'cron_urls' => array(0),
        'yt_urls' => array(1),
        'arhive' => array(0, 1),
    );
    public $option_names = array(
        'arhive' => array('log' => 2, 'title' => 'Arhive'),
        'cron_urls' => array('log' => 1, 'title' => 'Find URLs'),
        'yt_urls' => array('log' => 1, 'title' => 'Find URLs Youtube'),
    );
    public $parser_state = array(
        1 => 'Active',
        0 => 'Inactive',
        2 => 'Paused',
        3 => 'Paused (Auto)',
        4 => 'Stop error (Auto)',
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
        'find' => '1. Find URLs',
        'arhive' => '2. Arhive',
        'edit' => '3. Edit Parsing',
        'preview' => '4. Preview',
        'log' => 'Log',
        'update' => 'Force Update',
        'trash' => 'Trash',
    );
    public $log_type = array(
        0 => 'Info',
        1 => 'Warning',
        2 => 'Error',
    );
    public $log_status = array(
        0 => 'Other',
        1 => 'Find URLs',
        2 => 'Arhive',
        3 => 'Parsing',
    );

    /*
     * Parser mode
     */
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
    public $remove_interval = array(
        1440 => 'Day',
        10080 => 'Week',
        20160 => 'Two weeks',
        43200 => 'Mounth',
    );
    public $parsing_type = array(
        0 => 'Id ASC',
        1 => 'Random',
    );
    public $parse_number = array(1 => 1, 2 => 2, 3 => 3, 5 => 5, 7 => 7, 10 => 10, 20 => 20, 35 => 35, 50 => 50, 75 => 75, 100 => 100, 200 => 200, 500 => 500, 1000 => 1000);
    public $url_status = array(
        0 => 'New',
        1 => 'Exist',
        2 => 'Trash',
        3 => 'Ignore',
        4 => 'Error',
        5 => 'Parsing',
        6 => 'Proccess',
        7 => 'Arhive',
    );
    public $bulk_actions = array(
        'parsenew' => 'Parse',
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
    public $yt_per_page = array(
        1 => 1,
        5 => 5,
        10 => 10,
        25 => 25,
        50 => 50
    );

    public function preview_parser($campaign, $urls = array()) {
        if (!$urls) {
            return array();
        }

        $parser = new CPParsing($this->cp);
        $preview = $parser->preview($campaign, $urls);

        return $preview;
    }

    public function bulk_url_filter($id) {

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

            $cprules = $this->get_cprules();
            $check = $cprules->check_post_rules($options['rules'], $opt_url_status, $test_post, false);
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
                    $cprules = $this->cp->get_cprules();
                    $message = 'Check filters:' . $new_status . '. ' . $cprules->show_check($check);
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

        $query = "SELECT COUNT(id) FROM {$this->db['campaign']} WHERE id>0" . $type_query . $status_query . $parser_query . $aid_and;
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

    public function get_next_update($last_update = 0, $interval = 0) {
        $nextUpdate = $last_update + $interval * 60;

        if ($this->curr_time() > $nextUpdate) {
            $textDate = __('Waiting');
        } else {
            $textDate = gmdate('Y-m-d H:i:s', $nextUpdate);
        }

        return $textDate;
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
                $this->update_campaign($id, array('status' => $status));
            } else if ($b == 'stop_campaign') {
                $status = 0;
                $this->update_campaign($id, array('status' => $status));
            } else if ($b == 'trash_campaign') {
                $status = 2;
                $this->update_campaign($id, array('status' => $status));
            } else if ($b == 'active_parser') {
                $status = 1;
                $this->update_campaign($id, array('parser_status' => $status));
            } else if ($b == 'inactive_parser') {
                $status = 0;
                $this->update_campaign($id, array('parser_status' => $status));
            } else if ($b == 'active_arhive') {
                $campaign = $this->get_campaign($id);
                $this->start_module($campaign, 'arhive');
            } else if ($b == 'inactive_arhive') {
                $campaign = $this->get_campaign($id);
                $this->stop_module($campaign, 'arhive');
            }
        }
    }

    public function campaign_edit_validate($form_state) {

        if (isset($form_state['title'])) {
            // Edit
            if ($form_state['title'] == '') {
                return __('Enter the title');
            }
        } else if (isset($form_state['find_urls']) || isset($form_state['cron_urls'])) {
            // Find urls
            if ($form_state['match'] == '') {
                return __('Enter the match regexp');
            }
            if ($form_state['first'] == '' && $form_state['page'] == '') {
                return __('Enter the any page');
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

        $options = $opt_prev;

        $form_fields = array('post_status', 'pr_num', 'parse_num', 'url_status', 'use_rules', 'use_dom', 'use_reg', 'yt_force_update', 'yt_parse_num', 'yt_pr_num', 'yt_pr_status', 'new_urls_weight');

        foreach ($form_fields as $field) {
            if (isset($form_state[$field])) {
                $options[$field] = $form_state[$field];
            }
        }

        $status = isset($form_state['status']) ? $form_state['status'] : 0;
        $cprules = $this->get_cprules();
        $options['rules'] = $cprules->rules_form($form_state);
        $options['parser_rules'] = $cprules->parser_rules_form($form_state);

        if ($form_state['import_rules_json']) {
            $rules = json_decode(trim(stripslashes($form_state['import_rules_json'])), true);
            if (sizeof($rules)) {
                $options['parser_rules'] = $rules;
            }
        }
        if ($form_state['dom']) {
            $options['dom'] = base64_encode(stripslashes($form_state['dom']));
        }

        if ($form_state['reg']) {
            $options['reg'] = base64_encode(stripslashes($form_state['reg']));
        }

        $last_update = $date = $this->curr_time();
        $update_interval = isset($form_state['interval']) ? $form_state['interval'] : $def_opt['interval'];
        $parser_status = $form_state['parser_status'] ? $form_state['parser_status'] : 0;
        $author = $form_state['author'];
        $type = $form_state['type'];

        $title = $form_state['title'];
        $site = $form_state['site'];

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
        $data = array(
            'last_update' => $last_update,
            'update_interval' => $update_interval,
            'author' => $author,
            'status' => $status,
            'type' => $type,
            'parser_status' => $parser_status,
            'title' => $title,
            'site' => $site,
            'options' => $opt_str,
        );

        if ($id) {
            // EDIT
            foreach ($options as $key => $value) {
                $opt_prev[$key] = $value;
            }
            $opt_str = serialize($opt_prev);

            $data['options'] = $opt_str;

            $this->db_update($data, $this->db['campaign'], $id);
            $result = $id;
        } else {
            // ADD
            $data['date'] = $date;
            $result = $this->db_insert($data, $this->db['campaign']);
        }
        return $result;
    }

    public function find_channel_id($site) {
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

                // Youtube playlists
                $yt_pl = array();
                if ($form_state['yt_playlists']) {
                    foreach ($form_state['yt_playlists'] as $pl) {
                        $yt_pl[] = $pl;
                    }
                }
                $options['yt_playlists'] = $yt_pl;

                $this->update_campaign_options($id, $options);
            } else if ($form_state['add_urls']) {
                $this->add_urls($id, $form_state['add_urls'], $opt_prev);
            } else if ($form_state['service_urls']) {

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
                $this->update_campaign_options($id, $options);
            }
        }
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
        $find = new CPFind($this);
        $ret = $find->parse_urls($cid, $reg, $urls, $options, $wait, $preview);

        return $ret;
    }

    // Arhive
    public function arhive_edit_submit($form_state) {
        $result = 0;

        if ($form_state['id']) {

            $id = $form_state['id'];
            $campaign = $this->get_campaign($id);
            $opt_prev = unserialize($campaign->options);

            $arhive = array(
                'interval' => isset($form_state['interval']) ? $form_state['interval'] : $opt_prev['arhive']['interval'],
                'num' => isset($form_state['num']) ? $form_state['num'] : $opt_prev['arhive']['num'],
                'yt_num' => isset($form_state['yt_num']) ? $form_state['yt_num'] : $opt_prev['arhive']['yt_num'],
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

            $this->update_campaign_options($id, $options);
            $result = $id;
        }
        return $result;
    }

    public function preview_arhive($url, $campaign, $debug = false) {
        // Get posts (last is first)      

        $ret = array();

        $arhive = new CPArhive($this->cp);
        if ($campaign->type == 1) {
            // TODO Refactor
            // Youtube        
            $ret = $arhive->preview_arhive_yt($url, $campaign);
        } else {
            // Parser
            $ret = $arhive->preview_arhive($url, $campaign);
        }
        return $ret;
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
        $result = $this->cm->db_results($query);
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

        $query = "SELECT COUNT(id) FROM {$this->db['url']} u" . $linked_inner . $status_query . $meta_type_and . $linked_and . $cid_and;

        $result = $this->cm->db_get_var($query);
        return $result;
    }

    public function get_all_urls($cid) {
        $query = sprintf("SELECT * FROM {$this->db['url']} WHERE cid=%d AND status!=2", $cid);
        $result = $this->cm->db_results($query);
        return $result;
    }

    public function get_last_url($cid = 0) {
        $query = sprintf("SELECT link FROM {$this->db['url']} WHERE cid=%d ORDER BY id DESC", $cid);
        $result = $this->cm->db_get_var($query);
        return $result;
    }

    public function add_urls($id, $add_urls, $options) {
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

    public function delete_url($id) {
        $sql = sprintf("DELETE FROM {$this->db['url']} WHERE id=%d", (int) $id);
        $this->cm->db_query($sql);
    }

    /*
     * Log
     */

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

        $query = "SELECT COUNT(id) FROM {$this->db['log']} WHERE id>0" . $and_cid . $and_status . $and_type;

        $result = $this->db_get_var($query);
        return $result;
    }

    public function get_last_log($url_id = 0, $parser_id = 0, $log_type = -1) {

        $and_uid = '';
        if ($url_id > 0) {
            $and_uid = sprintf(' AND uid=%d', $url_id);
        }

        $and_cid = '';
        if ($parser_id > 0) {
            $and_cid = sprintf(' AND cid=%d', $parser_id);
        }

        $and_status = '';
        if ($log_type != -1) {
            $and_status = sprintf(' AND status=%d', $log_type);
        }

        $query = sprintf("SELECT type, status, message FROM {$this->db['log']} WHERE id>0" . $and_uid . $and_cid . $and_status . " ORDER BY id DESC", $url_id);
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

    public function get_log_type($type) {
        return isset($this->log_type[$type]) ? $this->log_type[$type] : 'None';
    }

    public function get_log_status($type) {
        return isset($this->log_status[$type]) ? $this->log_status[$type] : 'None';
    }
}

class CPCron {
    /*
     * Criric parser cron
     */

    private $cp;
    private $max_cron_time = 20;
    private $cron_types = array(
        2 => 'cron_urls',
        3 => 'arhive',
        1 => 'parsing',
    );

    public function __construct($cp = '') {
        $this->cp = $cp ? $cp : new CriticParser();
    }

    public function proccess_all($cron_type = 1, $force = false, $debug = false, $cid = 0) {
        $campaigns = $this->cp->get_campaigns();
        if ($debug && $campaigns) {
            print "Campaigns found: " . count($campaigns) . "\n";
        }
        $count = 0;
        $type_name = isset($this->cron_types[$cron_type]) ? $this->cron_types[$cron_type] : '';
        if ($debug) {
            print "Cron type: {$type_name}\n";
        }

        $next_keys = array();
        foreach ($campaigns as $campaign) {
            $next_key = $this->check_time_campaign($campaign, $type_name, $force, $debug, $cid);
            if ($next_key != -1) {
                $next_keys[$next_key . '-' . $campaign->id] = $campaign;
            }
        }
        ksort($next_keys);
        if ($debug) {
            print_r($next_keys);
        }

        if ($next_keys) {
            foreach ($next_keys as $campaign) {
                $count += $this->process_campaign($campaign, $type_name, $force, $debug);
                $time = (int) $this->cp->timer_stop(0);
                if ($time > $this->max_cron_time) {
                    break;
                }
            }
        }
        return $count;
    }

    public function check_time_campaign($campaign, $type_name = '', $force = false, $debug = false, $cid = 0) {

        $options = $this->cp->get_options($campaign);
        $active = 0;

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
        } else if ($type_name == 'arhive') {
            // Custom options
            $type_opt = $options[$type_name];
            $active = $type_opt['status'];
            $update_interval = $type_opt['interval'];
            $update_last_time = $type_opt['last_update'];
        }

        $next_key = -1;
        if ($active == 1) {
            $next_update = $update_last_time + $update_interval * 60;
            $currtime = $this->cp->curr_time();

            if ($currtime > $next_update || $force) {
                $next_key = $next_update;
            }
        }

        return $next_key;
    }

    public function process_campaign($campaign, $type_name, $force = false, $debug = false) {
        if ($debug) {
            print "Proccess campaign: {$campaign->id} - $type_name\n";
        }
        if ($type_name == 'parsing') {
            $count = $this->process_cron_parser($campaign, $force, $debug);
        } else if ($type_name == 'cron_urls' || $type_name == 'yt_urls') {
            $count = $this->proccess_cron_urls($campaign, $force, $debug);
        } else if ($type_name == 'arhive') {
            $count = $this->proccess_cron_arhive_urls($campaign, $force, $debug);
        }

        return $count;
    }

    /*
     * Parsing cron
     */

    public function process_cron_parser($campaign = '', $force = false, $debug = false) {
        $options = $this->cp->get_options($campaign);

        // Get posts (last is first)        
        $urls_count = $options['parse_num'];

        // Status arhive
        $status = 7;

        // Get last urls
        $urls = $this->cp->get_last_urls($urls_count, $status, $campaign->id);
        if ($debug) {
            print_r(array('urls', $urls));
        }
        $count = sizeof($urls);
        if ($count) {
            $parser = new CPParsing($this->cp);

            foreach ($urls as $item) {
                // Default parsing
                $parser->parse_url($item->id, false, $debug);
            }
        } else {
            // Auto pause Parser
            $parser_status = 3;
            $this->cp->update_campaign($campaign->id, array('parser_status' => $parser_status));
            $message = 'All URLS parsed. Parser paused';
            if ($debug) {
                print $message;
            }
            $this->cp->log_info($message, $campaign->id, 0, 0);
        }

        // Update date
        $currtime = $this->cp->curr_time();
        $this->cp->update_campaign($campaign->id, array('last_update' => $currtime));

        return $count;
    }

    /*
     * Urls cron
     */

    public function proccess_cron_urls($campaign = '', $force = false, $debug = false) {
        $options = $this->cp->get_options($campaign);

        $result = $this->cron_urls($campaign, false, $debug);
        if (isset($result['add_urls'])) {
            $count = sizeof($result['add_urls']);
            $message = 'Add new URLs: ' . $count;
            $this->cp->log_info($message, $campaign->id, 0, 1);

            // Start arhive            
            $this->cp->start_paused_module($campaign, 'arhive');
        }


        return $count;
    }

    public function cron_urls($campaign, $preview = true, $debug = false) {
        $options = $this->cp->get_options($campaign);
        $cid = $campaign->id;

        $find = new CPFind($this->cp);

        $type_name = 'cron_urls';
        $options_upd = array();
        $time = $this->cp->curr_time();

        if ($campaign->type == 1) {
            $type_name = 'yt_urls';
            // Youtube campaign
            // Playlists
            $playlists = $options['yt_playlists'] ? $options['yt_playlists'] : array();

            if ($playlists) {
                $ret = array('add_urls' => array());
                foreach ($playlists as $pid) {
                    $item = $find->find_urls_playlist_yt($cid, $pid, $options, '', $preview);
                    if ($item['add_urls']) {
                        $ret['add_urls'] = array_merge($ret['add_urls'], $item['add_urls']);
                    }
                }
            } else {
                $last_update_all = isset($options[$type_name]['last_update_all']) ? $options[$type_name]['last_update_all'] : 0;
                if ($last_update_all > 0) {
                    //$ret = $find->find_urls_yt($cid, $options, '', $preview);
                    $client_id = base64_decode($options['yt_page']);
                    $reg = '/<link [^>]*href="(https:\/\/www\.youtube\.com\/watch\?v=[^"]+)"/';
                    $rss_url = 'https://www.youtube.com/feeds/videos.xml?channel_id=' . $client_id;
                    $wait = 0;
                    $ret = $find->parse_urls($cid, $reg, [$rss_url], $options, $wait, $preview);

                    if ($debug) {
                        print "[$cid] Update last page by RSS\n";
                    }
                } else {
                    $ret = $find->find_all_urls_yt($campaign, false);
                    if ($debug) {
                        print "[$cid] Update all. Last update all: $last_update_all\n";
                    }
                }
            }

            $options_upd[$type_name]['last_update_all'] = $time;
        } else {
            $cron_urls = $options[$type_name];

            $urls = array();
            if (isset($cron_urls['page']) && $cron_urls['page'] != '') {
                $urls[] = htmlspecialchars(base64_decode($cron_urls['page']));
            }

            $reg = isset($cron_urls['match']) ? base64_decode($cron_urls['match']) : '';
            $wait = 0;
            $ret = $find->parse_urls($cid, $reg, $urls, $options, $wait, $preview);
        }

        $options_upd[$type_name]['last_update'] = $time;
        $this->cp->update_campaign_options($campaign->id, $options_upd);

        return $ret;
    }

    /*
     * Arhive cron
     */

    public function proccess_cron_arhive_urls($campaign = '', $force = false, $debug = false) {
        $type_name = 'arhive';
        $options = $this->cp->get_options($campaign);
        $type_opt = $options[$type_name];

        // Already progress
        $progress = isset($type_opt['progress']) ? $type_opt['progress'] : 0;
        $currtime = $this->cp->curr_time();
        if ($progress && !$force) {
            // Ignore old last update            
            $wait = 180; // 3 min
            if ($currtime < $progress + $wait) {
                $message = 'Archiving is in progress already.';
                if ($debug) {
                    print $message;
                }
                // $this->cp->log_warn($message, $campaign->id, 0, 6);
                return 0;
            }
        }

        // Update progress
        $options_upd = array();
        $options_upd[$type_name]['progress'] = $currtime;
        $this->cp->update_campaign_options($campaign->id, $options_upd);

        // Urls count
        $count = $this->cp->get_no_arhive_urls_count($campaign->id);

        if ($debug) {
            print "Urls count: " . $count . "\n";
        }

        if ($count) {
            $cm = $this->cp->get_cm();
            $ss = $cm->get_settings();
            $parser_arhive_async = $ss['parser_arhive_async'];

            if ($parser_arhive_async == 1) {
                // Async run
                $this->get_async_cron($campaign, $type_name);
            } else {
                // Sync run
                $this->run_cron_async($campaign->id, $type_name, $debug, 0, $no_arhive_urls);
            }
        } else {
            // Campaign done          
            $this->cp->pause_module($campaign, $type_name);
        }
        return $count;
    }

    public function get_async_cron($campaign, $type_name = '') {
        if (function_exists('get_site_url')) {
            $site_url = get_site_url();
        } else {
            $site_url = $this->cp->get_option('siteurl');
        }
        $url = $site_url . '/wp-content/plugins/critic_matic/cron/async_cron.php?p=8ggD_23_2D0DSF-F&type=' . $type_name . '&cid=' . $campaign->id;

        $this->cp->send_curl_no_responce($url);
    }

    public function run_cron_async($cid = 0, $type_name = '', $debug = false, $custom_url_id = 0, $no_arhive_urls = array()) {

        if (!$cid) {
            return;
        }

        if ($type_name == 'arhive') {
            $arhive = new CPArhive($this->cp);
            $campaign = $this->cp->get_campaign($cid);
            $options = $this->cp->get_options($campaign);
            $type_opt = $options[$type_name];
            $urls_count = $type_opt['num'];
            if ($campaign->type == 1) {
                $urls_count = $type_opt['yt_num'];
            }


            // Get last urls in status NEW
            $status = 0;
            $arhive_date = 0;

            // Random urls
            $random_urls = $type_opt['random'];
            $urls = $this->cp->get_last_urls($urls_count, $status, $campaign->id, $random_urls, $debug, $custom_url_id, $arhive_date);

            if ($debug) {
                print_r($urls);
            }
            $count = count((array) $urls);

            if ($debug) {
                print_r(array('Arhive count' => $count));
            }

            if ($count) {
                $arhive->arhive_urls($campaign, $options, $urls, false, $debug);
            }

            // Remove proggess flag
            $options_upd = array();
            $options_upd[$type_name]['progress'] = 0;
            $options_upd[$type_name]['last_update'] = $this->cp->curr_time();
            $this->cp->update_campaign_options($campaign->id, $options_upd);

            // TODO Delete garbage
            // Delete error arhives
            $del_pea = $type_opt['del_pea'];
            if ($del_pea == 1) {
                // Delete arhives witch error posts
            }

            // Delete error urls
            $service_opt = $options['service_urls'];
            $del_pea = $service_opt['del_pea'];
            if ($del_pea == 1) {
                // Delete arhives witch error url                
            }

            return;
        }
    }
}

class CPFind {
    /*
     * Criric parser find urls
     */

    private $cp;

    public function __construct($cp = '') {
        $this->cp = $cp ? $cp : new CriticParser();
    }

    /*
     * Parsing
     */

    public function parse_urls($cid, $reg, $urls, $options, $wait, $preview) {

        $ret = array();
        $new_urls_weight = $options['new_urls_weight'];
        $service_urls = $options['service_urls'];

        $mp = $this->cp->get_mp();
        $mp_settings = $mp->get_settings();

        if ($reg && $urls) {

            set_error_handler(function ($err_severity, $err_msg, $err_file, $err_line, array $err_context) {
                throw new ErrorException($err_msg, 0, $err_severity, $err_file, $err_line);
            }, E_WARNING);

            foreach ($urls as $url) {
                $url = htmlspecialchars_decode($url);
                $code = $mp->get_code_by_current_driver($url, $headers, $mp_settings, $service_urls);

                $error_msg = '';

                try {
                    if (preg_match_all($reg, $code, $match)) {
                        foreach ($match[1] as $u) {
                            if (preg_match('#^/#', $u)) {
                                // Short links
                                $domain = preg_replace('#^([^\/]+\:\/\/[^\/]+)(\/|\?|\#).*#', '$1', $url . '/');
                                $u = $domain . $u;
                            }

                            if (!$preview) {
                                $add = $this->cp->add_url($cid, $u, $new_urls_weight);
                                if ($add) {
                                    $ret['add_urls'][] = $u;
                                }
                            }

                            $ret['urls'][] = $u;
                        }
                    }
                } catch (Exception $exc) {
                    $error_msg = $exc->getMessage();
                }


                if ($preview) {
                    if ($error_msg) {
                        $ret['urls'][] = 'Invalid Regexp: ' . $error_msg;
                    }

                    $ret['content'] = $code;
                    $ret['headers'] = $headers;
                    break;
                } else {
                    if ($error_msg) {
                        break;
                    }
                    sleep($wait);
                }
            }

            restore_error_handler();
        }

        return $ret;
    }

    /*
     * Youtube
     */

    public function find_all_urls_yt($campaign, $preview = false, $debug = false) {
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
        $options = $this->cp->get_options($campaign);
        $cnt = $options['yt_urls']['per_page'];
        $cid = $campaign->id;

        // Playlists
        $playlists = $options['yt_playlists'] ? $options['yt_playlists'] : array();
        $first_page = array();

        if ($playlists) {
            $result = array('found' => 0, 'add' => 0);
            foreach ($playlists as $pid) {
                $first_page = $this->find_urls_playlist_yt($cid, $pid, $options, '', $preview);
                if ($preview) {
                    break;
                }
                $result_pl = $this->yt_parse_pager($first_page, $cnt, $cid, $pid, $options, $preview);
                $result['found'] += $result_pl['found'];
                $result['add'] += $result_pl['add'];
            }
            if ($preview) {
                return $first_page;
            }
        } else {
            $first_page = $this->find_urls_yt($cid, $options, '', $preview);
            if ($preview) {
                // Get data from first page
                return $first_page;
            }
            $result = $this->yt_parse_pager($first_page, $cnt, $cid, 0, $options, $preview);
        }
        return $result;
    }

    private function yt_parse_pager($first_page = array(), $cnt = 0, $cid = 0, $pid = 0, $options = array(), $preview = false) {
        $total_add = 0;

        $next = $first_page['next'];
        $total_found = (int) $first_page['total'];

        $total_parsed = $total_found;
        if ($next) {
            while ($next) {
                if ($pid) {
                    // Find in a playlist
                    $result = $this->find_urls_playlist_yt($cid, $pid, $options, $next);
                } else {
                    // Find in a channel
                    $result = $this->find_urls_yt($cid, $options, $next);
                }
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

    public function find_urls_yt($cid, $options, $next = '', $preview = false) {
        $cpyoutube = $this->cp->get_cpyoutube();

        $cnt = $options['yt_urls']['per_page'];
        $client_id = base64_decode($options['yt_page']);

        $new_urls_weight = $options['new_urls_weight'];

        $ret = array();
        $ret['urls'] = array();

        if ($client_id) {
            $responce = $cpyoutube->youtube_get_videos($client_id, $cnt, $next);
            if ($cpyoutube->yt_in_quota && $responce) {
                $total_found = $responce->pageInfo->totalResults;
                $ret['total'] = $total_found;
                $ret['next'] = $responce->nextPageToken;

                if ($responce->items) {
                    foreach ($responce->items as $item) {
                        $id = $item->id->videoId;
                        $url = $cpyoutube->youtube_url . $id;
                        $ret['urls'][] = $url;
                        if ($preview) {
                            continue;
                        }
                        if ($this->cp->add_url($cid, $url, $new_urls_weight)) {
                            $ret['add_urls'][] = $url;
                        }
                    }
                }
            }
            $ret['responce'] = $responce;
        }
        return $ret;
    }

    public function find_urls_playlist_yt($cid, $pid, $options, $next = '', $preview = false) {
        $cpyoutube = $this->cp->get_cpyoutube();

        $cnt = $options['yt_urls']['per_page'];

        $new_urls_weight = $options['new_urls_weight'];

        $ret = array();
        $ret['urls'] = array();

        if ($pid) {
            $responce = $cpyoutube->youtube_get_playlist_videos($pid, $cnt, $next);
            if ($cpyoutube->yt_in_quota && $responce) {
                $total_found = $responce->pageInfo->totalResults;
                $ret['total'] = $total_found;
                $ret['next'] = $responce->nextPageToken;
                /*
                 * [items] => Array
                  (
                  [0] => stdClass Object
                  (
                  [etag] => lYhCT5oErk1Pm51hvuNUgmYU6fY
                  [id] => UExLVFQ4NlVJUUtxM096bEZOaE9sUnc5NEZYbWtncEIxNi41NkI0NEY2RDEwNTU3Q0M2
                  [kind] => youtube#playlistItem
                  [snippet] => stdClass Object
                  (
                  [channelId] => UC0uWCUBVPIhgsjai2RN1buQ
                  [channelTitle] => Argent
                  [description] => Discord Server Link, Also the Best Place to Contact Me
                  https://discord.gg/7KeA749XWT

                  If you enjoy my content you can super chat or pledge to my Patreon here
                  https://www.patreon.com/argenttemplar
                  [playlistId] => PLKTT86UIQKq3OzlFNhOlRw94FXmkgpB16
                  [position] => 0
                  [publishedAt] => 2019-07-08T01:19:15Z
                  [title] => Castlevania Season 2 Reactionary Review: A Cartoonishly (Pun Intended) Anti Christian Polemic
                  [videoOwnerChannelId] => UC0uWCUBVPIhgsjai2RN1buQ
                  [videoOwnerChannelTitle] => Argent

                  [resourceId] => stdClass Object
                  (
                  [channelId] =>
                  [kind] => youtube#video
                  [playlistId] =>
                  [videoId] => CHQOIp3TUc8
                  )
                  )
                  )
                 */
                if ($responce->items) {
                    foreach ($responce->items as $item) {
                        $id = $item->snippet->resourceId->videoId;
                        $url = $cpyoutube->youtube_url . $id;
                        $ret['urls'][] = $url;
                        if ($preview) {
                            continue;
                        }
                        if ($this->cp->add_url($cid, $url, $new_urls_weight)) {

                            $ret['add_urls'][] = $url;
                        }
                    }
                }
            }
            $ret['responce'] = $responce;
        }
        return $ret;
    }
}

class CPArhive {
    /*
     * Criric parser arhive urls
     */

    private $cp;

    public function __construct($cp = '') {
        $this->cp = $cp ? $cp : new CriticParser();
    }

    public function arhive_urls($campaign, $options, $urls = array(), $expired = false, $debug = false) {
        $type_name = 'arhive';
        $type_opt = $options[$type_name];
        if ($urls) {
            if ($campaign->type == 1) {
                // Youtube
                $this->arhive_urls_yt($urls, $campaign, $type_opt, $expired, $debug);
            } else {
                foreach ($urls as $item) {
                    $this->arhive_url($item, $campaign, $type_opt, $expired);
                }
            }
        }

        // Start auto-paused parser
        if ($campaign->parser_status == 3) {
            $parser_status = 1;
            $this->cp->update_campaign($campaign->id, array('parser_status' => $parser_status));
        }
    }

    public function preview_arhive($url, $campaign) {

        $options = $this->cp->get_options($campaign);
        $arhive_urls = $options['arhive'];
        $mp = $this->cp->get_mp();
        $mp_settings = $mp->get_settings();
        $code = $mp->get_code_by_current_driver($url, $headers, $mp_settings, $arhive_urls);

        $valid_body_len = $this->validate_body_len($code, $type_opt['body_len']);
        $headers_status = $this->get_header_status($headers);

        $ret['content'] = $code;
        $ret['headers'] = $headers;
        $ret['headers_status'] = $headers_status;
        $ret['valid_body'] = $valid_body_len;

        return $ret;
    }

    private function arhive_url($item, $campaign, $type_opt, $expired = false) {

        /*
          [id] => 158745
          [cid] => 221
          [pid] => 0
          [status] => 0
          [link_hash] => 150f02007a4dd0b337bf8e59bd9708997460f80a
          [link] => https://www.youtube.com/watch?v=zNMnpb2QZck
          [date] => 1689402684
          [last_upd] => 0
          [arhive_date] => 0
          [arhive_hash] =>
         */

        // Status proccess
        $status = 6;
        $this->cp->change_url_state($item->id, $status);

        // 2. Get the content  
        $options = $this->cp->get_options($campaign);

        $arhive_urls = $options['arhive'];

        $mp = $this->cp->get_mp();
        $mp_settings = $mp->get_settings();
        $code = $mp->get_code_by_current_driver($item->link, $headers, $mp_settings, $arhive_urls);

        // Validate headers
        $header_status = $this->get_header_status($headers);

        $message = '';
        $status = 0;
        if ($header_status == 403) {
            // Status - 403 error
            $status = 4;
            $message = 'Error 403 Forbidden';
        } else if ($header_status == 500) {
            // Status - 500 error
            $status = 4;
            $message = 'Error 500 Internal Server Error';
        } else if ($header_status == 404) {
            // Status - 404
            $status = 4;
            $message = 'Error 404 Not found';
        }
        // Other statuses
        $error_statuses = array(401, 402, 429);
        if (!$status && in_array($header_status, $error_statuses)) {
            // Status - 404
            $status = 4;
            $message = 'Error ' . $header_status;
        }

        if (!$status) {
            if ($code) {
                // Validate body
                $valid_body_len = $this->validate_body_len($code, $type_opt['body_len']);
                if (!$valid_body_len) {
                    $status = 4;
                    $message = 'Error validate body length: ' . strlen($code);
                }
            } else {
                // Status - error
                $status = 4;
                $message = 'Can not get code from URL';
            }
        }

        if ($status > 0) {
            // Error status
            $this->cp->change_url_state($item->id, $status);
            $this->cp->log_error($message, $campaign->id, $item->id, 2);
            return;
        }

        $full_path = $this->get_arhive_path($item->cid, $item->link_hash, true);

        if (file_exists($full_path)) {
            unlink($full_path);
        }

        // Save code to arhive folder
        $gzdata = gzencode($code, 9);

        file_put_contents($full_path, $gzdata);

        // Add arhive
        $message = 'Add arhive: ' . strlen($code);
        $this->cp->log_info($message, $campaign->id, $item->id, 2);

        $data = array(
            'status' => 7,
            'arhive_date' => $this->cp->curr_time(),
        );
        // Status - exist
        $this->cp->update_url_data($data, $item->id);
    }

    /*
     * Youtube arhive
     */

    public function preview_arhive_yt($url, $campaign) {

        $cpyoutube = $this->cp->get_cpyoutube();
        $id = $cpyoutube->find_url_video_id($url);
        $ids = array('ids' => array($id));
        $responce = $cpyoutube->yt_listVideos($ids);
        $code = json_encode($responce);
        $headers = "
in_quota: {$cpyoutube->yt_in_quota}\n
error_msg: {$cpyoutube->yt_error_msg}\n
            ";
        $valid_body_len = $this->yt_error_msg ? false : true;
        $headers_status = '';

        $ret['content'] = $code;
        $ret['headers'] = $headers;
        $ret['headers_status'] = $headers_status;
        $ret['valid_body'] = $valid_body_len;

        return $ret;
    }

    private function arhive_urls_yt($items, $campaign, $type_opt, $expired = false, $debug = false) {
        $cpyoutube = $this->cp->get_cpyoutube();

        $ids = array();
        foreach ($items as $item) {
            $url = $item->link;
            $id = $cpyoutube->find_url_video_id($url);
            $ids[] = $id;
            $urls_id[$url] = $id;
        }

        if ($debug) {
            print_r($ids);
        }

        $snippets = $cpyoutube->find_youtube_data_api($ids, false);

        // Save arhive
        foreach ($items as $item) {
            $url = $item->link;
            $id = $urls_id[$url];
            $snippet = isset($snippets[$id]) ? $snippets[$id] : array();

            if ($debug) {
                print_r(array($url, $id, $snippet));
            }

            $status = 0;
            if (!$snippet) {
                $status = 4;
                $message = 'Error get data';
            }

            if ($status > 0) {
                // Error status
                $this->cp->change_url_state($item->id, $status);
                $this->cp->log_error($message, $campaign->id, $item->id, 2);
                continue;
            }

            $code = json_encode($snippet);

            // Save arhive
            $full_path = $this->get_arhive_path($item->cid, $item->link_hash, true);

            if (file_exists($full_path)) {
                unlink($full_path);
            }

            // Save code to arhive folder
            $gzdata = gzencode($code, 9);

            file_put_contents($full_path, $gzdata);

            // Add arhive
            $message = 'Add arhive: ' . strlen($code);
            $this->cp->log_info($message, $campaign->id, $item->id, 2);

            $data = array(
                'status' => 7,
                'arhive_date' => $this->cp->curr_time(),
            );
            // Status - exist
            $this->cp->update_url_data($data, $item->id);
        }
    }

    public function get_arhive_file($cid, $link_hash) {
        $full_path = $this->get_arhive_path($cid, $link_hash);

        $gzcontent = '';
        if (file_exists($full_path)) {
            $gzcontent = file_get_contents($full_path);
        }
        $content = '';
        if ($gzcontent) {
            $content = gzdecode($gzcontent);
        }

        return $content;
    }

    public function get_arhive_path($cid, $link_hash, $create_dir = false) {

        $arhive_path = $this->cp->arhive_path;
        $first_letter = substr($link_hash, 0, 1);
        $cid_path = $arhive_path . $cid . '/';
        $first_letter_path = $cid_path . $first_letter . '/';

        if ($create_dir) {
            $this->check_and_create_dir($first_letter_path);
        }
        $full_path = $first_letter_path . $link_hash;

        return $full_path;
    }

    public function check_and_create_dir($dst_path) {
        $path = '';
        if (ABSPATH) {
            $path = ABSPATH;
        }
        $dst_path = str_replace($path, '', $dst_path);

        # Создать дирикторию
        $arr = explode("/", $dst_path);

        foreach ($arr as $a) {
            if (isset($a)) {
                $path = $path . $a . '/';
                $this->fileman($path);
            }
        }
        return null;
    }

    public function fileman($way) {
        //Проверка наличия и создание директории
        // string $way - путь к дириктории
        $ret = true;
        if (!file_exists($way)) {
            if (!mkdir("$way", 0777)) {
                $ret = false;
                throw new Exception('Can not create dir: ' . $way . ', check cmod');
            }
        }
        return $ret;
    }

    public function validate_body_len($code = '', $valid_len = 500) {
        $body_len = strlen($code);
        if ($body_len > $valid_len) {
            return true;
        }
        return false;
    }

    public function get_header_status($headers) {
        $status = 200;
        if ($headers) {
            if (preg_match_all('/HTTP\/[0-9\.]+[ ]+([0-9]{3})/', $headers, $match)) {
                $status = $match[1][(sizeof($match[1]) - 1)];
            }
        }
        return $status;
    }
}

class CPParsing {
    /*
     * Criric parser parsing data
     */

    private $cm;
    private $cp;
    private $arhive;

    public function __construct($cp = '') {
        $this->cp = $cp ? $cp : new CriticParser();
        $this->cm = $this->cp->get_cm();
    }

    public function get_arhive() {
        if (!$this->arhive) {
            $this->arhive = new CPArhive($this->cp);
        }
        return $this->arhive;
    }

    /*
     * Parsing
     */

    public function preview($campaign, $urls) {

        $ret = array();
        $options = $this->cp->get_options($campaign);
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
        $cprules = $this->cp->get_cprules();
        $arhive = $this->get_arhive();

        foreach ($urls as $item) {
            $ret[$item->id]['url'] = $item;

            $content = '';

            // 1. Validate campaign URL rules
            if ($options['use_rules']) {
                $test_post = array(
                    'u' => $item->link,
                );
                $check = $cprules->check_post_rules($options['rules'], $url_status, $test_post, true);
                $ret[$item->id]['check_url'] = $check;

                $new_status = $check['status'];
                if ($new_status === 0) {
                    // Ignore URL
                    continue;
                }
            }

            // 2. Get the arhive  
            $code = $arhive->get_arhive_file($campaign->id, $item->link_hash);

            if ($code) {

                $content = '';
                $title = '';
                $author = '';
                $ret[$item->id]['raw'] = $code;

                if ($campaign->type == 1) {
                    // Youtube
                    try {
                        $snippet = json_decode($code);
                        $title = $snippet->title;
                        $date_raw = $snippet->publishedAt;
                        $content = $snippet->description;
                        $author = '';
                    } catch (Exception $exc) {
                        if ($debug) {
                            print $exc->getTraceAsString();
                        }
                    }
                } else {
                    // Default
                    if ($options['p_encoding'] != 'utf-8') {
                        $code = mb_convert_encoding($code, 'utf-8', $options['p_encoding']);
                    }

                    // Use reg rules
                    $items = $cprules->check_reg_post($options['parser_rules'], $code, '', $item->link);

                    $content = isset($items['d']) ? $items['d'] : '';
                    $title = isset($items['t']) ? $items['t'] : '';
                    $author = isset($items['a']) ? $items['a'] : '';
                    $date_raw = isset($items['y']) ? $items['y'] : '';
                }

                if ($content) {
                    $content = $this->cp->force_balance_tags($content);
                }

                if ($content) {
                    // Core filters
                    $domain = preg_replace('#^([a-z]+\:\/\/[^\/]+)(\/|\?|\#).*#', '$1', $item->link . '/');
                    $content = $this->cp->absoluteUrlFilter($domain, $content);

                    // Validate content
                    if ($options['use_rules']) {
                        $test_post = array(
                            'a' => $author,
                            'd' => $content,
                            't' => $title,
                        );
                        $check = $cprules->check_post_rules($options['rules'], $url_status, $test_post, true);
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
                    $date = $this->cp->curr_time();
                }

                $ret[$item->id]['title'] = $title;
                $ret[$item->id]['date'] = $date;
                $ret[$item->id]['date_raw'] = $date_raw;
                $ret[$item->id]['author'] = $author;
            }

            $ret[$item->id]['content'] = $content;
        }

        return $ret;
    }

    public function get_urls_content_yt($campaign, $urls = array(), $debug = false) {
        // UNUSED
        if (!$urls) {
            return array();
        }
        $options = $this->cp->get_options($campaign);
        $url_status = $options['url_status'];
        $cpyoutube = $this->cp->get_cpyoutube();

        $ids = array();
        $urls_id = array();
        foreach ($urls as $item) {
            $link = $item->link;
            $id = str_replace($cpyoutube->youtube_url, '', $link);
            $ids[] = $id;
            $urls_id[$link] = $id;
        }
        $snippets = $cpyoutube->find_youtube_data_api($ids);

        if (!$cpyoutube->yt_in_quota) {
            return array();
        }

        $cprules = $this->cp->get_cprules();
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
                $check = $cprules->check_post_rules($options['rules'], $url_status, $test_post, true);
                $ret[$item->id]['check'] = $check;
            }

            // 2. Content regexps
            if ($options['yt_pr_status']) {
                $test_post = $cprules->check_reg_post_yt($options['parser_rules'], $test_post);
            }

            $ret[$item->id]['link'] = $link;
            $ret[$item->id]['title'] = $test_post['t'];
            $ret[$item->id]['date'] = $date;
            $ret[$item->id]['desc'] = str_replace("\n", '<br />', $test_post['d']);
        }

        return $ret;
    }

    public function parse_urls_yt($urls, $campaign, $force = false, $debug = false) {
        // UNUSED
        $options = $this->cp->get_options($campaign);
        $force_update = $options['yt_force_update'];
        $cpyoutube = $this->cp->get_cpyoutube();

        $content = $this->get_urls_content_yt($campaign, $urls);
        if ($cpyoutube->yt_in_quota && $content && sizeof($content)) {
            foreach ($content as $id => $data) {
                //Get url object
                $item = $data['url'];
                $status = $item->status;

                // New status or Force.
                if ($status == 0 || $force) {
                    // Post exist?
                    $link_hash = $this->cp->link_hash($item->link);
                    $post_exist = $this->cm->get_post_by_link_hash($link_hash);
                    $item->status = 5;

                    if ($post_exist) {
                        if ($debug) {
                            print_r(array('Post exist', $link_hash, $post_exist));
                        }
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
                            $this->cp->change_url_state($id, $status);
                            $message = 'Post exists, continue URL';
                            $this->cp->log_warn($message, $campaign->id, $item->id, 3);
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

                    if ($debug) {
                        print_r(array('Data', $data));
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
                            $date = $this->cp->curr_time();
                        }

                        if ($post_exist) {
                            // Update post                            
                            $pid = $post_exist->id;
                            $this->cm->update_post($pid, $date, $post_status, $item->link, $title, $content, $post_type);

                            $log_message = "Update post:$pid, campaign:" . $campaign->id;
                        } else {
                            // Add post 
                            // View type is Youtube
                            $view_type = 1;
                            $pid = $this->cm->add_post($date, $post_type, $item->link, $title, $content, $top_movie, $post_status, $view_type);

                            if ($pid) {
                                // Add author      
                                $aid = $campaign->author;
                                $this->cm->add_post_author($pid, $aid);

                                $log_message = "Add post: $pid, author:$aid, campaign:" . $campaign->id;

                                if ($debug) {
                                    print_r(array('Add author for new post', $aid));
                                }
                            } else {
                                $log_message = "Error add post url: " . $item->link . ", campaign:" . $campaign->id;
                                $status = 4;
                                $this->cp->change_url_state($id, $status);
                                $this->cp->log_error($log_message, $campaign->id, $item->id, 3);
                                if ($debug) {
                                    print_r(array('error', $log_message));
                                }
                                continue;
                            }
                        }

                        // Update url         

                        $item->pid = $pid;
                        $this->cp->update_url($item);

                        // Add log
                        if ($item->status != 3) {
                            $this->cp->log_info($log_message, $campaign->id, $item->id, 3);
                            if ($debug) {
                                print_r(array('info', $log_message));
                            }
                        } else {
                            $cprules = $this->cp->get_cprules();
                            $message = 'Check URL:' . $new_status . '. ' . $cprules->show_check($check);
                            $this->cp->log_warn($message, $campaign->id, $item->id, 3);
                            if ($debug) {
                                print_r(array('warn', $log_message));
                            }
                        }
                        $this->cp->append_id($pid);
                    } else {
                        $status = 4;
                        $this->cp->change_url_state($id, $status);
                        $message = 'Error URL filters';
                        if (!$title) {
                            $message .= '. No Title';
                        }
                        if (!$content) {
                            $message .= '. No Content';
                        }
                        $this->cp->log_error($message, $campaign->id, $item->id, 3);
                        if ($debug) {
                            print_r(array('error', $message));
                        }
                    }
                }
            }
        }
    }

    public function parse_url($id, $force = false, $debug = false) {
        $changed = false;
        $item = $this->cp->get_url($id);
        $cprules = $this->cp->get_cprules();
        if ($item) {

            $content = '';
            $status = $item->status;

            // New status or Force.
            if ($status == 7 || $force) {
                $campaign = $this->cp->get_campaign($item->cid, true);
                $options = $this->cp->get_options($campaign);
                $url_status = $options['url_status'];
                $post_status = $options['post_status'];

                $item->status = 5;

                // Post exist?
                $link_hash = $item->link_hash;

                // Check the post already in db
                $post_exist = $this->cm->get_post_by_link_hash($link_hash);

                if ($debug) {
                    print_r(array('post_exist', $post_exist));
                }

                // 1. Validate campaign URL rules
                if ($options['use_rules']) {

                    $test_post = array(
                        'u' => $item->link,
                    );

                    $check = $cprules->check_post_rules($options['rules'], $url_status, $test_post, false);
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
                        $this->cp->update_url($item);

                        $cprules = $this->cp->get_cprules();
                        $message = 'Check URL:' . $new_status . '. ' . $cprules->show_check($check);
                        if ($debug) {
                            print_r(array($message));
                        }
                        $this->cp->log_warn($message, $campaign->id, $item->id, 3);
                        return $changed;
                    }
                }

                // 2. Get the arhive  
                $arhive = $this->get_arhive();
                $code = $arhive->get_arhive_file($campaign->id, $link_hash);

                if ($code) {
                    $title = '';
                    $content = '';

                    if ($campaign->type == 1) {
                        // Youtube
                        try {
                            $snippet = json_decode($code);
                            $title = $snippet->title;
                            $date = strtotime($snippet->publishedAt);
                            $content = $snippet->description;
                            $author = '';
                        } catch (Exception $exc) {
                            if ($debug) {
                                print $exc->getTraceAsString();
                            }
                        }
                    } else {
                        // Default
                        if ($options['p_encoding'] != 'utf-8') {
                            $code = mb_convert_encoding($code, 'utf-8', $options['p_encoding']);
                        }

                        // Use reg rules
                        $items = $cprules->check_reg_post($options['parser_rules'], $code, '', $item->link);
                        $content = isset($items['d']) ? trim($items['d']) : '';
                        $title = isset($items['t']) ? trim($items['t']) : '';
                        $author = isset($items['a']) ? trim(strip_tags($items['a'])) : '';
                        $date = isset($items['y']) ? strtotime($items['y']) : '';
                    }

                    if ($debug) {
                        print_r(array('title', $title));
                        print_r(array('content', $content));
                    }

                    if ($content && $title) {
                        $content = $this->cp->force_balance_tags($content);
                        // Core filters
                        $domain = preg_replace('#^([a-z]+\:\/\/[^\/]+)(\/|\?|\#).*#', '$1', $item->link . '/');
                        $content = $this->cp->absoluteUrlFilter($domain, $content);

                        // Validate content
                        if ($options['use_rules']) {
                            $test_post = array(
                                'u' => $item->link,
                                'a' => $author,
                                'd' => $content,
                                't' => $title,
                            );
                            $check = $cprules->check_post_rules($options['rules'], $url_status, $test_post, false);
                            $new_status = $check['status'];

                            if ($new_status == 0) {
                                // Ignore URL                                
                                $item->status = 3;
                                $this->cp->change_url_state($id, $status);
                                $cprules = $this->cp->get_cprules();
                                $message = 'Check rules:' . $new_status . '. ' . $cprules->show_check($check);
                                $this->cp->log_warn($message, $campaign->id, $item->id, 3);

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
                            $date = $this->cp->curr_time();
                        }

                        if ($post_exist) {
                            // Update post
                            $log_message = 'Update post';
                            $pid = $post_exist->id;

                            // Update author
                            $exist_author = $this->cm->get_post_author($pid);
                            if ($author && $exist_author->name != $author) {
                                $this->cm->remove_post_author($pid);
                                $author_type = 1;
                                $author_status = 0;
                                $aid = $this->cm->get_or_create_author_by_name($author, $author_type, $author_status);
                                $this->cm->add_post_author($pid, $aid);
                            }

                            $this->cm->update_post($pid, $date, $post_status, $item->link, $title, $content, $post_type);
                        } else {

                            $view_type = $this->cm->get_post_view_type($item->link);

                            // Add post 
                            $log_message = 'Add post';
                            $pid = $this->cm->add_post($date, $post_type, $item->link, $title, $content, $top_movie, $post_status, $view_type);

                            if ($pid) {
                                // Add author
                                if ($author) {
                                    $author_type = 1;
                                    $author_status = 0;
                                    $aid = $this->cm->get_or_create_author_by_name($author, $author_type, $author_status);
                                } else {
                                    $aid = $campaign->author;
                                }

                                $this->cm->add_post_author($pid, $aid);
                            } else {
                                $changed = true;
                                $log_message = "Error add post url: " . $item->link . ", campaign:" . $campaign->id;
                                $status = 4;
                                $this->cp->change_url_state($id, $status);
                                $this->cp->log_error($message, $campaign->id, $item->id, 3);
                                if ($debug) {
                                    print_r(array('error', $message));
                                }
                                return $changed;
                            }
                        }

                        if ($debug) {
                            print_r(array('log_message', $log_message));
                        }

                        // Update url         

                        $item->pid = $pid;
                        $this->cp->update_url($item);

                        // Add log
                        if ($item->status != 3) {
                            $this->cp->log_info($log_message, $campaign->id, $item->id, 3);
                        }
                        $this->cp->append_id($pid);
                    } else {
                        $changed = true;
                        $status = 4;
                        $this->cp->change_url_state($id, $status);
                        $message = 'Error URL filters';
                        if (!$title) {
                            $message .= '. No Title';
                        }
                        if (!$content) {
                            $message .= '. No Content';
                        }
                        $this->cp->log_error($message, $campaign->id, $item->id, 3);
                        return $changed;
                    }
                } else {
                    $changed = true;
                    $status = 4;
                    $this->cp->change_url_state($id, $status);
                    $message = 'Can not get the content';
                    $this->cp->log_error($message, $campaign->id, $item->id, 3);
                    return $changed;
                }
            }
        }

        return $changed;
    }
}

class CPYoutube {
    /*
     * Critic parser Youtube API service
     */

    private $cp;
    private $gs;
    private $client;
    public $yt_in_quota = true;
    public $yt_error_msg = '';
    public $youtube_url = 'https://www.youtube.com/watch?v=';

    public function __construct($cp = '') {
        $this->cp = $cp ? $cp : new CriticParser();
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
                $cm = $this->cp->get_cm();
                $ss = $cm->get_settings();
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

    public function find_url_video_id($keyword) {
        $video_id = '';
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
        return $video_id;
    }

    public function yt_video_data($url, $cache = true) {

        $video_id = $this->find_url_video_id($url);

        $ret = array();

        if ($video_id) {
            $result = $this->find_youtube_data_api(array($video_id), $cache);
            if (isset($result[$video_id])) {
                $ret = $result[$video_id];
            }
        }

        return $ret;
    }

    public function yt_total_posts($options) {

        $total = -1;
        $cid = base64_decode($options['yt_page']);
        $cnt = 5;

        $playlists_checked = $options['yt_playlists'] ? $options['yt_playlists'] : array();

        if ($cid) {
            try {
                if (!$playlists_checked) {
                    // All posts
                    $responce = $this->youtube_get_videos($cid, $cnt);
                    if ($this->yt_in_quota && $responce) {
                        $total = $responce->pageInfo->totalResults;
                    }
                } else {
                    $total_arr = array();
                    foreach ($playlists_checked as $play_list_id) {
                        $item = $this->youtube_get_playlist_videos($play_list_id, $cnt);

                        if ($this->yt_in_quota && $item) {
                            $total_arr[] = $item->pageInfo->totalResults;
                        }
                    }
                    $total = implode(', ', $total_arr);
                }
            } catch (Exception $exc) {
                print $exc->getTraceAsString();
            }
        }
        return $total;
    }

    public function yt_playlists_select($options) {
        $ret = array();
        $playlists = $this->yt_get_playlists($options);
        if ($this->yt_in_quota && $playlists->items) {
            foreach ($playlists->items as $item) {
                $ret[$item->id] = $item->snippet->title;
            }
        }
        return $ret;
    }

    public function yt_get_playlists($options) {

        $playlists = array();
        $cid = base64_decode($options['yt_page']);
        $cnt = 50;

        if ($cid) {
            try {
                $playlists = $this->youtube_get_playlists($cid, $cnt);
            } catch (Exception $exc) {
                print $exc->getTraceAsString();
            }
        }
        return $playlists;
    }

    public function youtube_get_videos($cid = 0, $count = 50, $pageToken = '', $cache = true) {
        if (!$cid) {
            return;
        }
        $arg = array();
        $arg['cid'] = $cid;
        $arg['count'] = $count;
        if ($pageToken) {
            $arg['pageToken'] = $pageToken;
        }

        if ($cache) {
            $arg['gz'] = 1;
            $filename = "ls-$cid-$count-$pageToken";
            $str = ThemeCache::cache('yt_listSearch', false, $filename, 'def', $this, $arg);
            $responce = json_decode(gzdecode($str));
        } else {
            $responce = $this->yt_listSearch($arg);
        }
        return $responce;
    }

    public function youtube_get_playlist_videos($play_list_id = 0, $count = 50, $pageToken = '', $cache = true) {
        if (!$play_list_id) {
            return;
        }
        $arg = array();
        $arg['pid'] = $play_list_id;
        $arg['count'] = $count;
        if ($pageToken) {
            $arg['pageToken'] = $pageToken;
        }
        if ($cache) {
            $arg['gz'] = 1;
            $filename = "lps-$play_list_id-$count-$pageToken";
            $str = ThemeCache::cache('yt_playlistItems', false, $filename, 'def', $this, $arg);
            $responce = json_decode(gzdecode($str));
        } else {
            $responce = $this->yt_playlistItems($arg);
        }
        return $responce;
    }

    public function youtube_get_playlists($cid = 0, $count = 50, $cache = true) {
        if (!$cid) {
            return;
        }
        $arg = array();
        $arg['cid'] = $cid;
        $arg['count'] = $count;

        if ($cache) {
            $arg['gz'] = 1;
            $filename = "lp-$cid-$count";
            $str = ThemeCache::cache('yt_playlists', false, $filename, 'def', $this, $arg);
            $responce = json_decode(gzdecode($str));
        } else {
            $responce = $this->yt_playlists($arg);
        }

        return $responce;
    }

    public function youtube_get_channel_info($cid = 0, $cache = true) {
        if (!$cid) {
            return;
        }
        $arg = array();
        $arg['cid'] = $cid;

        if ($cache) {
            $arg['gz'] = 1;
            $filename = "yci-$cid";
            $str = ThemeCache::cache('yt_channel_info', false, $filename, 'def', $this, $arg);
            $responce = json_decode(gzdecode($str));
        } else {
            $responce = $this->yt_channel_info($arg);
        }

        return $responce;
    }

    public function find_youtube_data_api($ids, $cache = true) {
        if (!$ids) {
            return;
        }

        $arg = array();
        $arg['ids'] = $ids;

        if ($cache) {
            $arg['gz'] = 1;
            $id_name = md5(implode('-', $ids));
            $filename = "lv-$id_name";
            $str = ThemeCache::cache('yt_listVideos', false, $filename, 'def', $this, $arg);
            $response = json_decode(gzdecode($str));
        } else {
            $response = $this->yt_listVideos($arg);
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
            $message = $exc->getMessage();
            $this->cp->log_error($message, $arg['cid'], 0, 3);
            $response = array();
            $this->yt_in_quota = false;
            $this->yt_error_msg = $message;
        }
        if ($arg['gz']) {
            return gzencode(json_encode($response));
        }
        return $response;
    }

    public function yt_playlistItems($arg = array()) {
        $service = $this->init_gs();

        $queryParams = array(
            'playlistId' => $arg['pid'],
            'maxResults' => $arg['count'],
        );

        if ($arg['pageToken']) {
            $queryParams['pageToken'] = $arg['pageToken'];
        }

        try {
            $response = $service->playlistItems->listPlaylistItems('snippet', $queryParams);
        } catch (Exception $exc) {
            $message = $exc->getMessage();
            $this->cp->log_error($message, $arg['cid'], 0, 3);
            $response = array();
            $this->yt_in_quota = false;
            $this->yt_error_msg = $message;
        }
        if ($arg['gz']) {
            return gzencode(json_encode($response));
        }
        return $response;
    }

    public function yt_listVideos($arg = array()) {
        $service = $this->init_gs();

        $queryParams = [
            'id' => implode(',', $arg['ids'])
        ];

        try {
            $response = $service->videos->listVideos('snippet', $queryParams);
        } catch (Exception $exc) {
            $message = $exc->getMessage();
            $this->cp->log_error($message, $arg['cid'], 0, 3);
            $response = array();
            $this->yt_in_quota = false;
            $this->yt_error_msg = $message;
        }
        if ($arg['gz']) {
            return gzencode(json_encode($response));
        }
        return $response;
    }

    public function yt_playlists($arg = array()) {
        $service = $this->init_gs();

        $queryParams = [
            'channelId' => $arg['cid'],
            'maxResults' => $arg['count']
        ];

        try {
            $response = $service->playlists->listPlaylists('snippet', $queryParams);
        } catch (Exception $exc) {
            $message = $exc->getMessage();
            $this->cp->log_error($message, $arg['cid'], 0, 3);
            $response = array();
            $this->yt_in_quota = false;
            $this->yt_error_msg = $message;
        }
        if ($arg['gz']) {
            return gzencode(json_encode($response));
        }
        return $response;
    }

    public function yt_channel_info($arg = array()) {
        $service = $this->init_gs();

        $queryParams = [
            'id' => $arg['cid'],
        ];

        try {
            $response = $service->channels->listChannels('snippet', $queryParams);
        } catch (Exception $exc) {
            $message = $exc->getMessage();
            $this->cp->log_error($message, $arg['cid'], 0, 3);
            $response = array();
            $this->yt_in_quota = false;
            $this->yt_error_msg = $message;
        }
        if ($arg['gz']) {
            return gzencode(json_encode($response));
        }
        return $response;
    }
}

class CPRules {
    /*
     * Criric parser reg rules
     */

    private $cp;
    public $parser_rules_fields = array(
        'a' => 'Author',
        'd' => 'Content',
        't' => 'Title',
        'y' => 'Date',
        'r' => 'Rating',
    );
    public $parser_rules_type = array(
        'x' => 'XPath',
        'm' => 'Regexp match',
        'r' => 'Regexp replace',
        'n' => 'None',
    );
    public $parser_data_fields = array(
        'r' => 'Raw HTML',
        'ct' => 'Clear Title',
        'cc' => 'Clear Content',
        'ca' => 'Clear Author',
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
        'n' => 0,
            /*  'vmat'=>'',        
              'vrul'=>'',
              'vrat'=>0, */
    );
    public $rules_fields = array(
        'a' => 'Author',
        'd' => 'Content',
        't' => 'Title',
        'u' => 'URL',
        'r' => 'Rating',
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

    public function __construct($cp = '') {
        $this->cp = $cp ? $cp : new CriticParser();
    }

    public $parser_valid_rules_type = array(
        'z' => 'Above zero',
        'e' => 'Exist',
        'm' => 'Regexp match',
    );

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

    public function rules_form($form_state) {
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

    public function check_post_rules($rules, $status, $test_post, $all = false) {
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

    public function check_reg_post($rules, $content, $rule_type = '', $link = '') {
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

            $clear_content = array();
            foreach ($this->parser_rules_fields as $type => $title) {
                if ($rule_type && $type != $rule_type) {
                    continue;
                }
                $i = 0;
                foreach ($rules_w as $key => $rule) {
                    // Clear content logic
                    $data_field = isset($rule['d']) ? $rule['d'] : 'r';
                    if ($data_field == 'r') {
                        $rule_content = $content;
                    } else {
                        // Get clear content
                        if (!$clear_content) {

                            $clear_content = $this->cp->clear_read($link, $content);
                        }
                        if ($data_field == 'ca') {
                            $rule_content = isset($clear_content['author']) ? $clear_content['author'] : '';
                        } else if ($data_field == 'ct') {
                            $rule_content = isset($clear_content['title']) ? $clear_content['title'] : '';
                        } else if ($data_field == 'cc') {
                            $rule_content = isset($clear_content['content']) ? $clear_content['content'] : '';
                        }
                    }

                    if ($type == $rule['f']) {
                        if ($rule['a'] != 1) {
                            continue;
                        }
                        if ($rule['n'] == 1) {
                            $i += 1;
                        }

                        if (!isset($results[$type][$i])) {
                            $results[$type][$i] = $rule_content;
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

    public function show_parser_rules($rules = array(), $edit = true, $type = 0, $check = array()) {
        if ($rules || $edit) {
            $rules = $this->sort_reg_rules_by_weight($rules);
            $disabled = '';

            $parser_rules_fields = $this->parser_rules_fields;
            if ($type == 1) {
                unset($parser_rules_fields['a']);
                unset($parser_rules_fields['y']);
            }

            $data_fields = $this->parser_data_fields;

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
                        <th><?php print __('Data field') ?></th> 
                        <th><?php print __('Weight') ?></th> 
                        <?php /*
                          <th><?php print __('Valid Rating') ?></th>
                          <th><?php print __('Valid Rule') ?></th>
                          <th><?php print __('Valid Match') ?></th>
                         */ ?>
                        <th><?php print __('Comment') ?></th>                       

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
                                    <select name="rule_reg_d_<?php print $rid ?>" class="condition"<?php print $disabled ?>>
                                        <?php
                                        if ($data_fields) {
                                            $con = isset($rule['d']) ? $rule['d'] : 'r';
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
                                    <input type="text" name="rule_reg_w_<?php print $rid ?>" class="rule_w" value="<?php print $rule['w'] ?>"<?php print $disabled ?>>
                                </td>
                                <?php /*
                                  <td>
                                  <input type="text" name="rule_reg_vrat_<?php print $rid ?>" class="rule_w" value="<?php print $rule['vrat'] ?>"<?php print $disabled ?>>
                                  </td>

                                  <td>
                                  <select name="rule_reg_vmat_<?php print $rid ?>" class="condition"<?php print $disabled ?>>
                                  <?php
                                  $con = $rule['vmat'];
                                  foreach ($this->parser_valid_rules_type as $key => $name) {
                                  $selected = ($key == $con) ? 'selected' : '';
                                  ?>
                                  <option value="<?php print $key ?>" <?php print $selected ?> ><?php print $name ?></option>
                                  <?php
                                  }
                                  ?>
                                  </select>
                                  </td>

                                  <td>
                                  <input type="text" name="rule_reg_vrul_<?php print $rid ?>" class="rule_m" value="<?php print $rule['vrul'] ?>"<?php print $disabled ?>>
                                  </td>

                                 */ ?>
                                <td>
                                    <input type="text" name="rule_reg_c_<?php print $rid ?>" class="rule_c" value="<?php print $rule['c'] ?>"<?php print $disabled ?>>
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
                            <td colspan="13"><b><?php print __('Add a new rule') ?></b></td>        
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
                                <input type="text" name="reg_new_rule_w" class="rule_w" value="0">
                            </td>
                            <?php /*
                              <td>
                              <input type="text" name="reg_new_rule_vrat" class="rule_w" value="0">
                              </td>
                              <td>
                              <select name="reg_new_rule_vmat" class="condition">
                              <?php foreach ($this->parser_valid_rules_type as $key => $name) { ?>
                              <option value="<?php print $key ?>"><?php print $name ?></option>
                              <?php
                              }
                              ?>
                              </select>
                              </td>

                              <td>
                              <input type="text" name="reg_new_rule_vrul" class="rule_m" value="">
                              <div class="desc">
                              Example: /(pattern)/Uis
                              </div>
                              </td>

                             */ ?>
                            <td>
                                <input type="text" name="reg_new_rule_c" class="rule_c" value="" placeholder="Comment">
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

    public function parser_rules_form($form_state) {
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
                    'n' => $form_state['rule_reg_n_' . $key],
                    'd' => $form_state['rule_reg_d_' . $key]
                );
                $rule_exists[$key] = $upd_rule;
            }
        }

        // New rule
        if ($form_state['reg_new_rule_r'] || $form_state['reg_new_rule_t'] == 'n') {

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
                'n' => $form_state['reg_new_rule_n'],
                'd' => $form_state['reg_new_rule_d']
            );
            $rule_exists[$new_rule_key] = $new_rule;
        }

        ksort($rule_exists);

        return $rule_exists;
    }

    private function get_dom($rule, $match_str, $code) {
        $content = '';
        if ($rule && $code) {
            $code = $this->cp->force_balance_tags($code);
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
}
