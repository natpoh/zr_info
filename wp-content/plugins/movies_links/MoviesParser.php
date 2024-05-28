<?php

class MoviesParser extends MoviesAbstractDB {

    private $ml = '';
    private $db = '';
    private $def_options = '';
    public $sort_pages = array('id', 'date', 'adate', 'exp_status', 'upd_rating', 'pdate', 'pid', 'title', 'last_update', 'last_upd', 'update_interval', 'name', 'pid', 'status', 'type', 'weight');

    public function __construct($ml = '') {
        $this->ml = $ml ? $ml : new MoviesLinks();

        $this->db = array(
            'arhive' => 'movies_links_arhive',
            'campaign' => 'movies_links_campaign',
            'log' => 'movies_links_log',
            'posts' => 'movies_links_posts',
            'url' => 'movies_links_url',
            'actors_meta' => 'actors_meta',
            'fchan_posts' => 'data_fchan_posts',
            'fchan_log' => 'data_fchan_log',
            'critics' => 'movies_links_critics',
            'meta_actor_weight' => 'meta_actor_weight',
        );

        // Init settings
        $this->def_options = array(
            'date' => $this->curr_time(),
            'status' => 1,
            'title' => '',
            'site' => '',
            'options' => array(
                'arhive' => array(
                    'last_update' => 0,
                    'interval' => 60,
                    'num' => 10,
                    'status' => 0,
                    'proxy' => 0,
                    'webdrivers' => 0,
                    'random' => 1,
                    'progress' => 0,
                    'del_pea' => 0,
                    'del_pea_int' => 10080,
                    'tor_h' => 20,
                    'tor_d' => 100,
                    'tor_mode' => 0,
                    'body_len' => 500,
                    'chd' => '',
                ),
                'find_urls' => array(
                    'first' => '',
                    'page' => '',
                    'new_url' => '',
                    'from' => 2,
                    'to' => 3,
                    'match' => '',
                    'step' => 1,
                    'wait' => 1,
                ),
                'cron_urls' => array(
                    'page' => '',
                    'match' => '',
                    'interval' => 1440,
                    'last_update' => 0,
                    'status' => 0,
                ),
                'gen_urls' => array(
                    'type' => 'm',
                    'page' => '',
                    'regexp' => '',
                    'interval' => 1440,
                    'last_update' => 0,
                    'last_id' => 0,
                    'status' => 0,
                    'num' => 100,
                    'progress' => 0,
                ),
                'service_urls' => array(
                    'webdrivers' => 0,
                    'del_pea' => 0,
                    'del_pea_cnt' => 10,
                    'tor_h' => 20,
                    'tor_d' => 100,
                    'tor_mode' => 0,
                    'progress' => 0,
                    'weight' => 0,
                ),
                'parsing' => array(
                    'last_update' => 0,
                    'interval' => 60,
                    'num' => 10,
                    'pr_num' => 5,
                    'status' => 0,
                    'rules' => '',
                    'row_rules' => '',
                    'row_status' => 0,
                    'multi_parsing' => 0,
                    'multi_rule' => '',
                    'multi_rule_type' => 0,
                    'version' => 0,
                ),
                'links' => array(
                    'last_update' => 0,
                    'interval' => 60,
                    'num' => 10,
                    'pr_num' => 5,
                    'status' => 0,
                    'type' => 'm',
                    'match' => 2,
                    'rating' => 20,
                    'rules' => '',
                    'custom_last_run_id' => 0,
                    'camp' => 0,
                    'weight' => 10,
                    'del_pea' => 1,
                    'del_pea_int' => 10080,
                    'parse_movie' => 0,
                    'link_poster' => 0,
                    'poster_field' => '',
                    'poster_rules' => '',
                ),
                'critics' => array(
                    'last_update' => 0,
                    'interval' => 60,
                    'num' => 10,
                    'pr_num' => 5,
                    'status' => 0,
                    'rules' => '',
                    'version' => 0,
                    'author' => 0,
                ),
                'update' => array(
                    'status' => 0,
                    'update_rules' => '',
                    'last_update' => 0,
                    'interval' => 60,
                    'num' => 100,
                ),
            ),
        );
    }

    public $poster_titles = array(
        'hist_correl' => 'Color Correlation',
        'hist_intersect' => 'Color Inersection',
        'hist_bhatt' => 'Color Battacharya',
        'orb' => 'ORB',
        'sift' => 'SIFT',
    );
    public $def_poster_rules = array(
        'hist_correl' => array(
            'match' => 90,
            'rating' => 1,
            'active' => 1,
        ),
        'hist_intersect' => array(
            'match' => 60,
            'rating' => 3,
            'active' => 1,
        ),
        'hist_bhatt' => array(
            'match' => 60,
            'rating' => 3,
            'active' => 1,
        ),
        'orb' => array(
            'match' => 60,
            'rating' => 3,
            'active' => 1,
        ),
        'sift' => array(
            'match' => 25,
            'rating' => 20,
            'active' => 1,
        ),
    );
    public $campaign_modules = array('cron_urls', 'gen_urls', 'arhive', 'parsing', 'links', 'update','critics');
    public $log_modules = array(
        'cron_urls' => 1,
        'gen_urls' => 1,
        'arhive' => 2,
        'parsing' => 3,
        'links' => 4,
        'update' => 5,
        'critics' => 6,
    );
    private $def_reg_rule = array(
        'f' => '',
        'n' => '',
        't' => '',
        'r' => '',
        'm' => '',
        'c' => '',
        'w' => 0,
        'a' => 0,
        'n' => 0,
        's' => 0
    );
    public $parser_rules_type = array(
        'x' => 'XPath',
        'p' => 'XPath all',
        'm' => 'Regexp match',
        'a' => 'Regexp match all',
        'r' => 'Regexp replace',
    );
    public $parser_row_rules_type = array(
        'x' => 'XPath',
        'p' => 'XPath all',
        'y' => 'XPath all (multi)',
        'm' => 'Regexp match',
        'a' => 'Regexp match all',
        'b' => 'Regexp match all (multi)',
        'r' => 'Regexp replace',
    );
    public $parser_rules_fields = array(
        'r' => 'Release',
        's' => 'Score',
        't' => 'Title',
        'y' => 'Year',
        'c' => 'Custom',
    );
    public $parser_rules_actor_fields = array(
        't' => 'Title',
        'c' => 'Custom',
    );
    public $parser_urls_rules_actor_fields = array(
        't' => 'Title',
        'u' => 'URL',
        'c' => 'Custom',
    );
    public $parser_row_rules_fields = array(
        't' => 'Item',
    );
    public $parser_urls_rules_fields = array(
        'r' => 'Release',
        't' => 'Title',
        'y' => 'Year',
        'u' => 'URL',
        'c' => 'Custom',
    );

    /* Links */
    public $links_rules_fields = array(
        't' => 'Title (Requed)',
        'y' => 'Year',
        'r' => 'Release',
        'a' => 'Actors',
        'd' => 'Director',
        'rt' => 'Runtime',
        'im' => 'IMDB',
        'tm' => 'TMDB',
        'e' => 'Exist',
        'm' => 'URL Movie ID',
        'em' => 'Exist Movie',
        'et' => 'Exist TV',
        'eg' => 'Exist Game',
        'g' => 'Genre',
    );
    public $links_rules_actor_fields = array(
        'f' => 'Firstname',
        'l' => 'Lastname',
        'n' => 'Full name',
        'b' => 'Burn name',
        'y' => 'Burn year',
        'e' => 'Exist'
    );
    public $links_match_type = array(
        'm' => 'Match',
        'e' => 'Equals',
    );
    private $def_link_rule = array(
        'f' => '',
        't' => '',
        'r' => '',
        'm' => '',
        'mu' => '',
        'e' => '',
        'd' => '',
        'ra' => 1,
        'c' => '',
        'w' => 0,
    );
    public $link_rules_type = array(
        'n' => 'None',
        'm' => 'Regexp match',
        'a' => 'Regexp match all',
        'r' => 'Regexp replace',
    );
    public $movie_type = array(
        'a' => 'Movie,TVSeries',
        'm' => 'Movie',
        't' => 'TVSeries',
        'g' => 'VideoGame',
    );
    public $rating_update = array(
        7 => [50, 100],
        30 => [40, 50],
        60 => [30, 40],
        90 => [20, 30],
        360 => [10, 20],
        1080 => [0, 10],
    );

    public function get_tp() {
        return $this->ml->get_tp();
    }

    /*
     * Campaign
     */

    public function get_campaigns_query($q_req = array(), $page = 1, $perpage = 30, $orderby = '', $order = 'ASC', $count = false) {
        // New api        
        $q_def = array(
            'status' => -1,
            'type' => -1,
            'parsing_mode' => -1,
        );

        $q = array();
        foreach ($q_def as $key => $value) {
            $q[$key] = isset($q_req[$key]) ? $q_req[$key] : $value;
        }

        // Custom status
        $status_trash = 2;
        $status_query = " WHERE c.status != " . $status_trash;
        if ($q['status'] != -1) {
            $status_query = " WHERE c.status = " . (int) $q['status'];
        }

        // Type
        $type_query = '';
        if ($q['type'] != -1) {
            $type_query = " AND c.type = " . (int) $q['type'];
        }

        $parsing_mode_query = '';
        if ($q['parsing_mode'] != -1) {
            $parsing_mode_query = " AND c.parsing_mode = " . (int) $q['parsing_mode'];
        }


        //Sort
        $and_orderby = '';
        $limit = '';
        if (!$count) {
            if ($orderby && in_array($orderby, $this->sort_pages)) {
                $and_orderby = ' ORDER BY ' . $orderby;
                if ($order) {
                    $and_orderby .= ' ' . $order;
                }
            } else {
                $and_orderby = " ORDER BY c.id DESC";
            }

            $page -= 1;
            $start = $page * $perpage;

            $limit = '';
            if ($perpage > 0) {
                $limit = " LIMIT $start, " . $perpage;
            }

            $select = "c.*";
        } else {
            $select = "COUNT(c.id)";
        }

        $query = "SELECT " . $select
                . " FROM {$this->db['campaign']} c"
                . $status_query . $type_query . $parsing_mode_query . $and_orderby . $limit;

        if (!$count) {
            $result = $this->db_results($query);
        } else {
            $result = $this->db_get_var($query);
        }

        return $result;
    }

    public function get_campaigns($status = -1, $type = -1, $page = 1, $orderby = '', $order = 'ASC', $perpage = 30) {
        // DEPRECATED
        $page -= 1;
        $start = $page * $perpage;

        $limit = '';
        if ($perpage > 0) {
            $limit = " LIMIT $start, " . $perpage;
        }

        // Custom status
        $status_trash = 2;
        $status_query = " WHERE c.status != " . $status_trash;
        if ($status != -1) {
            $status_query = " WHERE c.status = " . (int) $status;
        }

        $type_query = '';
        if ($type != -1) {
            $type_query = " AND type = " . (int) $type;
        }

        //Sort
        $and_orderby = '';
        if ($orderby) {
            $and_orderby = ' ORDER BY ' . $orderby;
            if ($order) {
                $and_orderby .= ' ' . $order;
            }
        } else {
            $and_orderby = " ORDER BY id DESC";
        }



        $sql = sprintf("SELECT c.id, c.date, c.status, c.type, c.parsing_mode, c.title, c.site, c.options "
                . "FROM {$this->db['campaign']} c "
                . $status_query . $type_query . $and_orderby . $limit);

        $result = $this->db_results($sql);

        return $result;
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

    public function update_campaign($status = 0, $title = '', $site = '', $type = 0, $parsing_mode = 0, $id) {
        $data = array(
            'status' => (int) $status,
            'type' => (int) $type,
            'parsing_mode' => (int) $parsing_mode,
            'title' => $title,
            'site' => $site,
        );
        $this->db_update($data, $this->db['campaign'], $id);
    }

    public function add_campaing($status, $title, $site, $type = 0, $parsing_mode = 0) {
        $date = $this->curr_time();
        $data = array(
            'date' => (int) $date,
            'status' => (int) $status,
            'type' => (int) $type,
            'parsing_mode' => (int) $parsing_mode,
            'title' => $title,
            'site' => $site,
        );
        $id = $this->db_insert($data, $this->db['campaign']);

        return $id;
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

    public function remove_all_campaign_error_posts($form_state) {
        if ($form_state['id']) {
            // To trash
            $id = $form_state['id'];
            $sql = sprintf("DELETE p FROM {$this->db['posts']} p INNER JOIN {$this->db['url']} u ON p.uid = u.id WHERE u.cid=%d AND p.status_links=2", (int) $id);
            $this->db_query($sql);
        }
    }

    public function remove_all_campaign_posts($form_state) {
        if ($form_state['id']) {
            // To trash
            $id = $form_state['id'];
            $sql = sprintf("DELETE p FROM {$this->db['posts']} p INNER JOIN {$this->db['url']} u ON p.uid = u.id WHERE u.cid=%d", (int) $id);
            $this->db_query($sql);
        }
    }

    public function get_options($campaign) {
        $options = unserialize($campaign->options);
        foreach ($this->def_options['options'] as $key => $value) {
            if (!isset($options[$key])) {
                //replace empty settings to default
                $options[$key] = $value;
            } else {
                if (is_array($value)) {
                    foreach ($value as $skey => $svalue) {
                        if (!isset($options[$key][$skey])) {
                            //replace empty settings to default
                            $options[$key][$skey] = $svalue;
                        }
                    }
                }
            }
        }
        return $options;
    }

    public function get_parser_query_count($q_req = array()) {
        return $this->get_campaigns_query($q_req, 1, 1, '', '', true);
    }

    public function get_parser_type_count($q_req = array(), $types = array(), $custom_type = '') {
        $status = -1;
        $count = $this->get_parser_query_count($q_req);
        $states = array(
            '-1' => array(
                'title' => 'All',
                'count' => $count
            )
        );
        $q_req_custom = $q_req;

        foreach ($types as $key => $value) {
            $q_req_custom[$custom_type] = $key;
            $states[$key] = array(
                'title' => $value,
                'count' => $this->get_parser_query_count($q_req_custom));
        }
        return $states;
    }

    public function get_parser_count($status = -1, $type = -1, $aid = 0) {
        // Custom type
        $status_trash = 2;
        $status_query = " WHERE status != " . $status_trash;
        if ($status != -1) {
            $status_query = " WHERE status = " . (int) $status;
        }

        $type_query = '';
        if ($type != -1) {
            $type_query = " AND type = " . (int) $type;
        }

        // Author filter        
        $aid_and = '';
        if ($aid > 0) {
            $aid_and = sprintf(" AND author = %d", $aid);
        }

        $query = "SELECT COUNT(id) FROM {$this->db['campaign']}" . $status_query . $type_query . $aid_and;
        $result = $this->db_get_var($query);
        return $result;
    }

    public function update_campaign_status($id, $status) {
        $this->db_query(sprintf("UPDATE {$this->db['campaign']} SET status=%d WHERE id = %d", (int) $status, (int) $id));
    }

    public function update_campaign_last_hash($id, $time, $feed_hash) {
        $this->db_query(sprintf("UPDATE {$this->db['campaign']} SET                 
                last_update=%d, last_hash='%s' WHERE id = '%d'", (int) $time, $feed_hash, $id));
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
                        // Value vitch childs
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
     * Urls
     */

    public function add_url($cid, $link, $pid = 0, $weight = 0, $parent_url = 0) {
        $link_hash = $this->link_hash($link);
        $url_exist = $this->get_url_by_hash($link_hash);

        if ($url_exist) {
            $epid = $url_exist->pid;
            if (!$epid && $pid) {
                // Update post pid
                $this->update_url_pid($url_exist->id, $pid);
            }
            // URL already exist in another campaign     
            // p_r(array($weight, $cid, $url_exist->cid));
            if ($weight > 0 && $cid != $url_exist->cid) {
                // Check old campaign weight
                $old_weight = $this->get_campaign_weight($url_exist->cid);
                if ($weight > $old_weight) {
                    $this->update_url_campaing($url_exist->id, $cid);
                    $message = 'Update URL campaign from ' . $url_exist->cid . ' to ' . $cid;
                    $this->log_info($message, $cid, $url_exist->id, 1);

                    // Check and move arhive
                    $arhive = $this->get_arhive_by_url_id($url_exist->id);
                    if ($arhive) {
                        // Move arhive
                        try {
                            $arhive_hash = $arhive->arhive_hash;
                            $full_path_old = $this->get_arhive_path($url_exist->cid, $arhive_hash, true);
                            $full_path_new = $this->get_arhive_path($cid, $arhive_hash, true);
                            rename($full_path_old, $full_path_new);
                            $message = 'Move arhive ' . $url_exist->id . ' from ' . $url_exist->cid . ' to ' . $cid;
                            $this->log_info($message, $cid, $url_exist->id, 1);
                        } catch (Exception $exc) {
                            //echo $exc->getTraceAsString();
                        }
                    }
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

        // Status 'NEW'
        $status = 0;

        $date = $this->curr_time();
        $data = array(
            'cid' => (int) $cid,
            'pid' => (int) $pid,
            'status' => (int) $status,
            'date' => (int) $date,
            'parent_url' => (int) $parent_url,
            'link_hash' => $link_hash,
            'link' => $link,
        );

        $id = $this->db_insert($data, $this->db['url']);

        return $id;
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
        $weight = $options['service_urls']['weight'];

        if ($cache) {
            $dict[$id] = $weight;
        }
        return $weight;
    }

    public function update_url_campaing($id, $cid) {
        $sql = sprintf("UPDATE {$this->db['url']} SET cid=%d WHERE id = %d", (int) $cid, (int) $id);
        $this->db_query($sql);
    }

    public function get_url($id) {
        $sql = sprintf("SELECT * FROM {$this->db['url']} WHERE id = %d", (int) $id);
        $result = $this->db_fetch_row($sql);
        return $result;
    }

    public function get_url_by_hash($link_hash) {
        $sql = sprintf("SELECT * FROM {$this->db['url']} WHERE link_hash = '%s'", $link_hash);
        $result = $this->db_fetch_row($sql);
        return $result;
    }

    public function get_url_by_mid($mid = 0, $cid = 0) {
        $result = '';

        $mid = intval($mid);

        if (strstr($cid, ',')) {
            $cid_request = '';
            $cid_array = explode(',', $cid);
            foreach ($cid_array as $cid_data) {
                if ($cid_data) {
                    $cid_data = intval($cid_data);
                    $cid_request .= " OR cid =" . $cid_data . " ";
                }
            }
            if ($cid_request) {
                $cid_request = substr($cid_request, 3);
            }
            $q = "SELECT * FROM {$this->db['url']} WHERE pid = " . $mid . " and (" . $cid_request . ") limit 1";

            global $debug;
            if ($debug) {
                echo $q;
            }
            $result = $this->db_fetch_row($q);
        } else {
            $sql = sprintf("SELECT * FROM {$this->db['url']} WHERE pid = %d and cid = %d", (int) $mid, (int) $cid);

            global $debug;
            if ($debug) {
                echo $sql;
            }


            $result = $this->db_fetch_row($sql);
        }


        return $result;
    }

    public function get_url_by_top_movie($mid = 0, $cid = 0) {


        if (strstr($cid, ',')) {
            $d = '';
            $cid_array = explode(',', $cid);
            foreach ($cid_array as $cid_data) {
                if ($cid_data) {
                    $cid_data = intval($cid_data);
                    $d .= " OR u.cid =" . $cid_data . " ";
                }
            }
            $d = " ( " . substr($d, 3) . " ) ";
        } else {
            $cid = intval($cid);

            $d = "u.cid = " . $cid;
        }

        $sql = sprintf("SELECT u.id, u.pid, u.link, u.link_hash, p.top_movie, p.options FROM {$this->db['url']} u INNER JOIN {$this->db['posts']} p ON p.uid = u.id WHERE " . $d . " AND p.top_movie=%d limit 1", (int) $mid);

        global $debug;
        if ($debug) {
            echo $sql;
        }

        $result = $this->db_fetch_row($sql);
        return $result;
    }

    public function update_urls_status($id, $status) {
        $sql = sprintf("UPDATE {$this->db['url']} SET status=%d WHERE id = %d", (int) $status, (int) $id);
        $this->db_query($sql);
    }

    public function get_urls_query($q_req = array(), $page = 1, $perpage = 30, $orderby = '', $order = 'ASC', $count = false) {
        // New api
        //$status = -1, $page = 1, $cid = 0, $arhive_type = -1, $parser_type = -1, $links_type = -1, $orderby = '', $order = 'ASC', $perpage = 30, $date = ''
        $q_def = array(
            'status' => -1,
            'cid' => 0,
            'arhive_type' => -1,
            'parser_type' => -1,
            'links_type' => -1,
            'exp_status' => -1,
            'pid' => -1,
            'ids' => array(),
            'date' => 0,
        );

        $q = array();
        foreach ($q_def as $key => $value) {
            $q[$key] = isset($q_req[$key]) ? $q_req[$key] : $value;
        }

        // Custom status
        $status_trash = 2;
        $status_query = " WHERE u.status != " . $status_trash;
        if ($q['status'] != -1) {
            $status_query = " WHERE u.status = " . (int) $q['status'];
        }

        // Company id
        $cid_and = '';
        if ($q['cid'] > 0) {
            $cid_and = sprintf(" AND u.cid = %d", (int) $q['cid']);
        }

        // Post type filter
        $arhive_type_and = '';
        if ($q['arhive_type'] != -1) {
            if ($q['arhive_type'] == 1) {
                $arhive_type_and = " AND a.uid != 0";
            } else {
                $arhive_type_and = " AND a.uid is NULL";
            }
        }

        // Parser type filter
        $parser_type_and = ' AND (p.id is NULL OR p.multi=0)';
        if ($q['parser_type'] != -1) {
            if ($q['parser_type'] == 1) {
                $parser_type_and = " AND p.id !=0 AND p.status=1 AND p.multi=0";
            } else if ($q['parser_type'] == 2) {
                $parser_type_and = " AND p.id !=0 AND p.status=0 AND p.multi=0";
            } else {
                $parser_type_and = " AND p.id is NULL";
            }
        }

        // Links type filter
        $links_type_and = '';
        if ($q['links_type'] != -1) {
            $links_type_and = sprintf(" AND p.status_links=%d", $q['links_type']);
        }

        // Expired status filter
        $exp_status_and = '';
        if ($q['exp_status'] != -1) {
            $exp_status_and = sprintf(" AND u.exp_status=%d", $q['exp_status']);
        }

        // Links pid filter
        $links_pid_and = '';
        if ($q['pid'] != -1) {
            $links_pid_and = sprintf(" AND u.pid=%d", $q['pid']);
        }

        // Ids and
        $ids_and = '';
        if ($q['ids']) {
            $ids_and = sprintf(" AND u.id IN (%s)", implode(',', $q['ids']));
        }

        // Date filter
        $and_date = '';
        if ($q['date'] > 0) {
            $and_date = sprintf(' AND p.date < %d', $q['date']);
        }



        //Sort
        $and_orderby = '';
        $limit = '';
        if (!$count) {
            if ($orderby && in_array($orderby, $this->sort_pages)) {
                $and_orderby = ' ORDER BY ' . $orderby;
                if ($order) {
                    $and_orderby .= ' ' . $order;
                }
            } else {
                $and_orderby = " ORDER BY u.id DESC";
            }

            $page -= 1;
            $start = $page * $perpage;

            $limit = '';
            if ($perpage > 0) {
                $limit = " LIMIT $start, " . $perpage;
            }

            $select = "u.id, u.cid, u.pid, u.status, u.link_hash, u.link, u.date, u.last_upd, u.exp_status, u.upd_rating, u.parent_url,"
                    . " a.date as adate,"
                    . " p.date as pdate, p.status as pstatus, p.title as ptitle, p.year as pyear, p.id as postid, "
                    . " p.top_movie as ptop_movie, p.status_links as pstatus_links, p.rating as prating";
        } else {
            $select = "COUNT(u.id)";
        }

        $query = "SELECT " . $select
                . " FROM {$this->db['url']} u"
                . " LEFT JOIN {$this->db['arhive']} a ON u.id = a.uid"
                . " LEFT JOIN {$this->db['posts']} p ON u.id = p.uid"
                . $status_query . $ids_and . $cid_and . $links_pid_and . $exp_status_and . $arhive_type_and . $parser_type_and . $links_type_and . $and_date . $and_orderby . $limit;

        if (!$count) {
            $result = $this->db_results($query);
        } else {
            $result = $this->db_get_var($query);
        }

        return $result;
    }

    public function get_urls_query_count($q_req = array()) {
        return $this->get_urls_query($q_req, 1, 1, '', '', true);
    }

    public function get_urls_type_count($q_req = array(), $types = array(), $custom_type = '') {
        $status = -1;
        $count = $this->get_urls_query_count($q_req);
        $states = array(
            '-1' => array(
                'title' => 'All',
                'count' => $count
            )
        );
        $q_req_custom = $q_req;

        foreach ($types as $key => $value) {
            $q_req_custom[$custom_type] = $key;
            $states[$key] = array(
                'title' => $value,
                'count' => $this->get_urls_query_count($q_req_custom));
        }
        return $states;
    }

    public function get_urls($status = -1, $page = 1, $cid = 0, $arhive_type = -1, $parser_type = -1, $links_type = -1, $orderby = '', $order = 'ASC', $perpage = 30, $date = '') {
        // Old api

        $status_trash = 2;
        $status_query = " WHERE u.status != " . $status_trash;
        if ($status != -1) {
            $status_query = " WHERE u.status = " . (int) $status;
        }

        $page -= 1;
        $start = $page * $perpage;

        $limit = '';
        if ($perpage > 0) {
            $limit = " LIMIT $start, " . $perpage;
        }

        // Company id
        $cid_and = '';
        if ($cid > 0) {
            $cid_and = sprintf(" AND u.cid=%d", (int) $cid);
        }


        //Sort
        $and_orderby = '';
        if ($orderby && in_array($orderby, $this->sort_pages)) {
            $and_orderby = ' ORDER BY ' . $orderby;
            if ($order) {
                $and_orderby .= ' ' . $order;
            }
        } else {
            $and_orderby = " ORDER BY u.id DESC";
        }

        // Post type filter
        $arhive_type_and = '';
        if ($arhive_type != -1) {
            if ($arhive_type == 1) {
                $arhive_type_and = " AND a.uid != 0";
            } else {
                $arhive_type_and = " AND a.uid is NULL";
            }
        }

        // Parser type filter
        $parser_type_and = ' AND (p.id is NULL OR p.multi=0)';
        if ($parser_type != -1) {
            if ($parser_type == 1) {
                $parser_type_and = " AND p.id !=0 AND p.status=1 AND p.multi=0";
            } else if ($parser_type == 2) {
                $parser_type_and = " AND p.id !=0 AND p.status=0 AND p.multi=0";
            } else {
                $parser_type_and = " AND p.id is NULL";
            }
        }

        // Links type filter
        $links_type_and = '';
        if ($links_type != -1) {
            $links_type_and = sprintf(" AND p.status_links=%d", $links_type);
        }

        // Date filter
        $and_date = '';
        if ($date) {
            $and_date = sprintf(' AND p.date < %d', $date);
        }

        $query = "SELECT u.id, u.cid, u.pid, u.status, u.link_hash, u.link, u.date, u.last_upd, u.exp_status,"
                . " a.date as adate,"
                . " p.date as pdate, p.status as pstatus, p.title as ptitle, p.year as pyear,"
                . " p.top_movie as ptop_movie, p.status_links as pstatus_links, p.rating as prating"
                . " FROM {$this->db['url']} u"
                . " LEFT JOIN {$this->db['arhive']} a ON u.id = a.uid"
                . " LEFT JOIN {$this->db['posts']} p ON u.id = p.uid"
                . $status_query . $cid_and . $arhive_type_and . $parser_type_and . $links_type_and . $and_date . $and_orderby . $limit;

        $result = $this->db_results($query);
        return $result;
    }

    public function get_last_upd_urls($cid = 0, $status = 1, $last_upd = 0, $count = 0) {
        $query = sprintf("SELECT id, cid, pid, last_upd, status, link, link_hash FROM {$this->db['url']}"
                . " WHERE cid=%d AND status=%d AND last_upd>%d ORDER BY last_upd ASC limit %d", $cid, $status, $last_upd, $count);

        $result = $this->db_results($query);
        return $result;
    }

    public function get_last_url($cid = 0) {
        $query = sprintf("SELECT link FROM {$this->db['url']} WHERE cid=%d ORDER BY id DESC", $cid);
        $result = $this->db_get_var($query);
        return $result;
    }

    public function get_urls_count($status = -1, $cid = 0, $arhive_type = -1, $parser_type = -1, $link_type = -1) {

        // Custom status
        $status_trash = 2;
        $status_query = " AND u.status != " . $status_trash;
        if ($status != -1) {
            $status_query = " AND u.status = " . (int) $status;
        }

        // Company id
        $cid_and = '';
        if ($cid > 0) {
            $cid_and = sprintf(" AND u.cid=%d", (int) $cid);
        }

        // Arhive type filter
        $arhive_type_and = '';
        $arhive_join = '';
        if ($arhive_type != -1) {
            $arhive_join = " LEFT JOIN {$this->db['arhive']} a ON u.id = a.uid";
            if ($arhive_type == 1) {
                $arhive_type_and = " AND a.id !=0 ";
            } else {
                $arhive_type_and = " AND a.id is NULL";
            }
        }

        // Parser type filter
        $parser_type_and = '';
        $parser_join = '';        
        if ($parser_type != -1 || $link_type != -1) {
            $parser_join = " LEFT JOIN {$this->db['posts']} p ON u.id = p.uid";            
        }     
        
        if ($parser_type != -1){
            if ($parser_type == 1) {
                $parser_type_and = " AND p.id !=0 AND p.status=1 AND p.multi=0";
            } else if ($parser_type == 2) {
                $parser_type_and = " AND p.id !=0 AND p.status=0 AND p.multi=0";
            } else {
                $parser_type_and = " AND p.id is NULL";
            }            
        }
        $link_type_and = '';
        if ($link_type != -1) {
                $link_type_and = sprintf(" AND p.status_links=%d", $link_type);
            }
            
        $query = "SELECT COUNT(u.id) FROM {$this->db['url']} u"
                . $arhive_join . $parser_join
                . " WHERE u.id>0"
                . $status_query . $arhive_type_and . $parser_type_and . $link_type_and . $cid_and;

        $result = $this->db_get_var($query);
   
        return $result;
    }

    public function get_urls_expired_count($cid = 0, $exp_status = 1) {
        // Company id
        $cid_and = '';
        if ($cid > 0) {
            $cid_and = sprintf(" AND u.cid=%d", (int) $cid);
        }
        $query = sprintf("SELECT COUNT(u.id) FROM {$this->db['url']} u WHERE u.exp_status=%d" . $cid_and, (int) $exp_status);
        $result = $this->db_get_var($query);
        return $result;
    }

    public function get_all_urls($cid) {
        $query = sprintf("SELECT * FROM {$this->db['url']} WHERE cid=%d AND status!=2", $cid);
        $result = $this->db_results($query);
        return $result;
    }

    public function get_last_urls($count = 10, $status = -1, $cid = 0, $random = 0, $camp_type = 0, $custom_url_id = 0, $debug = false) {

        if ($custom_url_id > 0) {
            $query = sprintf("SELECT * FROM {$this->db['url']} WHERE id=%d", $custom_url_id);
            $result = $this->db_results($query);
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
            $result = '';
            if ($random == 1) {
                if ($debug) {
                    print "Random URLs\n";
                }
                // Get all urls
                $query = "SELECT id FROM {$this->db['url']}" . $status_query . $cid_and;
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
                if ($debug) {
                    print "Weight URLs\n";
                }
                // Pid exists
                $query = "SELECT COUNT(id) FROM {$this->db['url']} WHERE pid>0" . $cid_and;
                $pid_exists = $this->db_get_var($query);
                if ($pid_exists > 0) {
                    // Get by weight

                    $result = array();

                    $ma = $this->ml->get_ma();
                    // 1. Weight>20
                    if ($camp_type == 1) {
                        $ids = $ma->get_actors_ids_by_min_weight(10);
                    } else {

                        $ids = $ma->get_post_ids_by_min_weight(20);
                    }

                    if ($ids) {
                        $query = sprintf("SELECT * FROM {$this->db['url']}" . $status_query . $cid_and . " AND pid IN(" . implode(",", $ids) . ") ORDER BY id DESC LIMIT %d", $count);
                        $result = (array) $this->db_results($query);
                    }
                    if ($debug) {
                        print "Weight>20: $count\n";
                    }

                    if (count($result) < $count) {
                        // 2. Weight>10
                        if ($camp_type == 1) {
                            $ids = $ma->get_actors_ids_by_min_weight(5);
                        } else {
                            $ids = $ma->get_post_ids_by_min_weight(10);
                        }
                        if ($ids) {
                            $query = sprintf("SELECT * FROM {$this->db['url']}" . $status_query . $cid_and . " AND pid IN(" . implode(",", $ids) . ") ORDER BY id DESC LIMIT %d", $count);
                            $result_10 = (array) $this->db_results($query);
                            if ($result_10) {
                                if ($result) {
                                    $result = array_merge($result, $result_10);
                                } else {
                                    $result = $result_10;
                                }
                            }
                        }
                        if ($debug) {
                            print "Weight>10: $count\n";
                        }
                    }

                    if (count($result) < $count) {

                        if ($camp_type == 1) {
                            $ids = $ma->get_actors_ids_by_min_weight(0);
                        } else {
                            // 2. Weight>0
                            $ids = $ma->get_post_ids_by_min_weight(0);
                        }
                        if ($ids) {
                            $query = sprintf("SELECT * FROM {$this->db['url']}" . $status_query . $cid_and . " AND pid IN(" . implode(",", $ids) . ") ORDER BY id DESC LIMIT %d", $count);
                            $result_0 = (array) $this->db_results($query);
                            if ($result_0) {
                                if ($result) {
                                    $result = array_merge($result, $result_0);
                                } else {
                                    $result = $result_0;
                                }
                            }
                        }

                        if ($debug) {
                            print "Weight>0: $count\n";
                        }
                    }


                    if (!count($result)) {
                        // Get by id

                        if ($debug) {
                            print "Get by id\n";
                        }
                        $query = sprintf("SELECT * FROM {$this->db['url']}" . $status_query . $cid_and . " ORDER BY id DESC LIMIT %d", $count);
                        if ($debug) {
                            print $query;
                        }
                        $result = $this->db_results($query);
                    }
                } else {
                    // Get by id
                    $query = sprintf("SELECT * FROM {$this->db['url']}" . $status_query . $cid_and . " ORDER BY id DESC LIMIT %d", $count);
                    if ($debug) {
                        print $query;
                    }
                    $result = $this->db_results($query);
                }
            }
        }

        if ($debug) {
            print_r($result);
        }

        // Check movie exist

        if (count($result)) {
            $valid_result = array();
            $ma = $this->ml->get_ma();
            foreach ($result as $item) {
                if ($camp_type != 1 && $item->pid > 0) {
                    if ($ma->get_movie_by_id($item->pid)) {
                        // Post exist
                        $valid_result[] = $item;
                        if ($debug) {
                            print "Movie exist: " . $item->pid . "\n";
                        }
                    } else {
                        // Remove url
                        $this->delete_arhive_by_url_id($item->id);
                        $this->delete_url($item->id);
                        if ($debug) {
                            print "Remove URL: " . $item->id . "\n";
                        }
                    }
                } else {
                    $valid_result[] = $item;
                }
            }
            $result = $valid_result;
        }

        return $result;
    }

    public function get_expired_urls($cid = 0, $count = 100, $debug = false) {
        // Company id
        $cid_and = '';
        if ($cid > 0) {
            $cid_and = sprintf(" AND cid=%d", (int) $cid);
        }
        $query = sprintf("SELECT * FROM {$this->db['url']} WHERE exp_status=1" . $cid_and . " ORDER BY upd_rating DESC, last_upd ASC LIMIT %d", $count);
        if ($debug) {
            print_r($query);
        }
        $result = $this->db_results($query);
        return $result;
    }

    public function change_url_state($id, $status = 0, $force = false) {
        $update_status = false;
        if ($force) {
            $update_status = true;
        } else {
            $sql = sprintf("SELECT status FROM {$this->db['url']} WHERE id=%d", $id);
            $old_status = $this->db_get_var($sql);
            if ($old_status != $status) {
                $update_status = true;
            }
        }

        if ($update_status) {
            $sql = sprintf("UPDATE {$this->db['url']} SET status=%d WHERE id=%d", $status, $id);
            $this->db_query($sql);
            return true;
        }
        return false;
    }

    public function update_url($data, $uid) {
        $data['last_upd'] = $this->curr_time();
        $this->db_update($data, $this->db['url'], $uid);
    }

    public function find_expired_urls($campaign = array(), $options = array(), $debug = false) {
        /*
          // 1.w50  Last 30 days (30)
          // 2. w40 Last year  and rating 3-5 (250)
          // 3. w30  Last 3 year and rating 4-5 (200)
          //4. w20 All time and rating 4-5 (3500)
          //5. w10 Last 3 year (4000)
          //6. w0 Other (27000)
         */


        // Expired is active? 
        $ao = $options['update'];
        // Find expired URLs and change its status
        $count = $ao['num'];

        if ($debug) {
            print "Find expired URLs\n";
        }

        $curr_time = $this->curr_time();

        $cid = $campaign->id;
        $ma = $this->ml->get_ma();

        foreach ($this->rating_update as $days => $rating_arr) {
            p_r(array($days, $rating_arr));
            $min_rating = $rating_arr[0];
            $max_rating = $rating_arr[1];
            if ($min_rating != 0) {
                $ids = $ma->get_post_ids_by_weight($min_rating, $max_rating);
            }
            if ($debug) {
                p_r(count($ids));
            }
            if ($ids || $min_rating == 0) {
                $keys = array_keys($ids);
                $expire_date = $curr_time - ($days * 86400);
                $and_lastupd = ' AND last_upd<' . $expire_date;
                if ($min_rating == 0) {
                    // All posts
                    $query = sprintf("SELECT id FROM {$this->db['url']} WHERE status=1 AND cid=%d" . $and_lastupd . " ORDER BY id ASC LIMIT %d", $cid, $count);
                } else {
                    // Select ids posts
                    $query = sprintf("SELECT id FROM {$this->db['url']} WHERE status=1 AND cid=%d" . $and_lastupd . " AND pid IN(" . implode(",", $keys) . ") ORDER BY id ASC LIMIT %d", $cid, $count);
                }
                $result = (array) $this->db_results($query);
                if ($debug) {
                    p_r($query);
                    p_r($result);
                }
                if ($result) {
                    $upd_ids = array();
                    foreach ($result as $item) {
                        $upd_ids[] = $item->id;
                    }
                    // Update expire urls and upd rating
                    $sql = sprintf("UPDATE {$this->db['url']} SET exp_status=1, last_upd=%d, upd_rating=%d WHERE id IN (" . implode(",", $upd_ids) . ")", $curr_time, $min_rating);
                    $this->db_query($sql);
                    $count += sizeof($result);
                }
            }
        }
        if ($debug) {
            p_r(array('Total expired' => $count));
        }

        return $count;
    }

    public function find_urls($campaign, $options, $settings, $preview = true) {
        $find_urls = $options['find_urls'];
        $service_urls = $options['service_urls'];

        $urls = array();
        if (isset($find_urls['first']) && $find_urls['first'] != '') {
            $urls[] = htmlspecialchars(base64_decode($find_urls['first']));
        }

        $from = isset($find_urls['from']) ? (int) $find_urls['from'] : 2;
        $to = isset($find_urls['to']) ? (int) $find_urls['to'] : 3;
        $step = isset($find_urls['step']) ? (int) $find_urls['step'] : 1;
        if ($step) {
            $to = $to * $step;
        }

        if (isset($find_urls['page'])) {
            $page = htmlspecialchars(base64_decode($find_urls['page']));

            $page_first = $page;
            $page_sec = '';
            if (strstr($page, '$1')) {
                $page_arr = explode('$1', $page);
                $page_first = $page_arr[0];
                $page_sec = isset($page_arr[1]) ? $page_arr[1] : '';
            }

            for ($i = $from; $i <= $to; $i += $step) {
                $urls[] = $page_first . $i . $page_sec;
            }
        }

        $reg = isset($find_urls['match']) ? base64_decode($find_urls['match']) : '';
        $wait = isset($find_urls['wait']) ? (int) $find_urls['wait'] : 1;

        $new_url = isset($find_urls['new_url']) ? base64_decode($find_urls['new_url']) : '';

        $cid = $campaign->id;
        $ret = array();
        $total_found = 0;
        if ($reg && $urls) {
            foreach ($urls as $url) {
                $url = htmlspecialchars_decode($url);

                $code = $this->get_code_by_current_driver($url, $headers, $settings, $service_urls);

                if ($code && preg_match_all($reg, $code, $match)) {
                    foreach ($match[1] as $u) {

                        if ($new_url) {
                            // Regexp new url
                            $u = str_replace('$1', $u, $new_url);
                        } else {
                            if (preg_match('#^/#', $u)) {
                                //Short links
                                $domain = preg_replace('#^([^\/]+\:\/\/[^\/]+)(\/|\?|\#).*#', '$1', $url . '/');
                                $u = $domain . $u;
                            }
                        }

                        if (!$preview) {
                            if ($this->add_url($cid, $u)) {
                                $total_found += 1;
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

        if (!$preview) {
            $message = 'Urls found: ' . $total_found;
            $this->log_info($message, $cid, 0, 1);
        }
        return $ret;
    }

    public function proccess_cron_urls($campaign = '', $options) {
        if ($this->find_urls_in_progress($campaign, $options)) {
            return 0;
        }

        $result = $this->cron_urls($campaign, $options, false);
        if (isset($result['add_urls'])) {
            $count = sizeof($result['add_urls']);
            $message = 'Add new URLs: ' . $count;
            $this->log_info($message, $campaign->id, 0, 1);
        }

        $this->find_urls_update_progress($campaign);

        return $count;
    }

    public function proccess_gen_urls($campaign = '', $options = array(), $debug = false) {
        if ($this->find_urls_in_progress($campaign, $options)) {
            if ($debug) {
                print "Find URLs in progress\n";
            }
            return 0;
        }

        $o = $options['gen_urls'];
        $last_id = $o['last_id'];
        $settings = $this->ml->get_settings();
        $ret = $this->generate_urls($campaign, $options, $settings, $last_id, false, $debug);

        $this->find_urls_update_progress($campaign);

        $count = $ret['total'];
        return $count;
    }

    public function get_settings() {
        $settings = $this->ml->get_settings();
        return $settings;
    }

    private function find_urls_in_progress($campaign, $options) {
        $type_name = 'service_urls';
        $type_opt = $options[$type_name];

        // Already progress
        $progress = isset($type_opt['progress']) ? $type_opt['progress'] : 0;
        $currtime = $this->curr_time();
        if ($progress) {
            // Ignore old last update            
            $wait = 180; // 3 min
            if ($currtime < $progress + $wait) {
                $message = 'Find URLs is in progress already.';
                $this->log_warn($message, $campaign->id, 0, 2);
                return true;
            }
        }
        return false;
    }

    private function find_urls_update_progress($campaign) {
        $type_name = 'service_urls';

        // Update progress
        $options_upd = array();
        $options_upd[$type_name]['progress'] = 0;
        $this->update_campaign_options($campaign->id, $options_upd);
    }

    public function generate_urls($campaign, $options, $settings, $last_id = 0, $preview = true, $debug = false) {
        $ret = array();

        $gen_urls = $options['gen_urls'];

        $page = base64_decode($gen_urls['page']);
        $regexp = base64_decode($gen_urls['regexp']);
        $type = $gen_urls['type'];
        $num = $gen_urls['num'];

        //Find keys
        $keys = array();
        if (preg_match_all('/{([a-zA-Z0-9_-]+)}/', $page, $match)) {
            for ($i = 0; $i < sizeof($match[0]); $i++) {
                $keys[$match[1][$i]] = $match[0][$i];
            }
        }

        if ($debug) {
            print_r($campaign);
            print_r($options);
        }

        if (!$keys) {
            return $ret;
        }


        $ma = $this->ml->get_ma();
        $get_keys = array_keys($keys);

        if ($preview) {
            if ($campaign->type == 1) {
                // Actors
                if ($type == 'w') {
                    $posts = $ma->get_actors_by_weight($num);
                } else {
                    $posts = $ma->get_actors($type, $num);
                }
                $post = array_shift($posts);
            } else {
                // Movies
                //Get last URL to test      
                $movie_type = $this->movie_type[$type];
                $posts = $ma->get_posts($movie_type, $get_keys, 1);
                $post = array_shift($posts);
                $post = $this->get_post_custom_fields($post);
            }

            $query_page = $page;
            foreach ($keys as $key => $value) {
                $post_value = isset($post->$key) ? $post->$key : '';
                // regexp
                if ($regexp) {
                    $reg_from = $regexp;
                    $reg_to = '';
                    if (strstr($regexp, '; ')) {
                        $regexp_arr = explode('; ', $regexp);
                        $reg_from = $regexp_arr[0];
                        $reg_to = $regexp_arr[1];
                    }
                    $post_value = preg_replace($reg_from, $reg_to, $post_value);
                }
                $post_encode = urlencode($post_value);
                $query_page = str_replace($value, $post_encode, $query_page);
            }

            $service_urls = $options['service_urls'];
            $code = $this->get_code_by_current_driver($query_page, $headers, $settings, $service_urls);
            $ret['url'] = $query_page;
            $ret['content'] = $code;
            $ret['headers'] = $headers;
            return $ret;
        }

        $total_gen = 0;
        $total_new = 0;
        $ret['urls'] = array();
        $cid = $campaign->id;
        $post_last_id = 0;

        if ($campaign->type == 1) {
            // Actors     
            if ($type == 'w') {
                $posts = $ma->get_actors_by_weight($num, $last_id);
            } else {
                $posts = $ma->get_actors($type, $num, $last_id);
            }
        } else {
            // Movies
            // Get all URLs
            $movie_type = $this->movie_type[$type];
            $posts = $ma->get_posts($movie_type, $get_keys, $num, $last_id);
        }

        if ($debug) {
            print_r(array($campaign->title, $last_id));
            print_r($posts);
        }

        if ($posts) {
            $last = end($posts);
            $post_last_id = $last->id;
            foreach ($posts as $post) {
                if ($campaign->type != 1) {
                    // Get imdb field for movies
                    $post = $this->get_post_custom_fields($post);
                }
                $query_page = $page;

                foreach ($keys as $key => $value) {
                    $post_value = isset($post->$key) ? $post->$key : '';

                    if ($post_value == '') {
                        continue;
                    }
                    // regexp
                    if ($regexp) {
                        $reg_from = $regexp;
                        $reg_to = '';
                        if (strstr($regexp, '; ')) {
                            $regexp_arr = explode('; ', $regexp);
                            $reg_from = $regexp_arr[0];
                            $reg_to = $regexp_arr[1];
                        }
                        $post_value = preg_replace($reg_from, $reg_to, $post_value);
                    }
                    $post_encode = urlencode($post_value);
                    $query_page = str_replace($value, $post_encode, $query_page);
                }

                if (strstr($query_page, '{')) {
                    // Not all masks replaces
                    continue;
                }

                $pid = $post->id;
                if ($campaign->type == 1) {
                    // Actors
                    $pid = $post->aid;
                }

                if ($this->add_url($cid, $query_page, $pid)) {
                    $total_new += 1;
                }

                $ret['urls'][] = $query_page;
                $total_gen += 1;
            }
        }

        $ret['total'] = $total_gen;
        $ret['total_new'] = $total_new;
        if ($debug) {
            print_r($ret);
        }

        if (!$preview && $post_last_id) {
            $options_upd = array();
            $options_upd['gen_urls']['last_id'] = $post_last_id;
            $this->update_campaign_options($cid, $options_upd);
        }


        if ($total_gen > 0) {
            $message = 'Urls generated: ' . $total_gen;
            $this->log_info($message, $cid, 0, 1);
        }

        return $ret;
    }

    public function get_post_custom_fields($post) {
        //Custom fields
        $post->imdb = 'tt' . sprintf('%07d', $post->movie_id);
        return $post;
    }

    public function cron_urls($campaign, $options, $preview = true) {

        $cron_urls = $options['cron_urls'];

        $urls = array();
        if (isset($cron_urls['page']) && $cron_urls['page'] != '') {
            $urls[] = htmlspecialchars(base64_decode($cron_urls['page']));
        }

        $reg = isset($cron_urls['match']) ? base64_decode($cron_urls['match']) : '';
        $wait = 0;
        $cid = $campaign->id;
        $ret = $this->parse_urls($cid, $reg, $urls, $wait, $preview);
        return $ret;
    }

    private function parse_urls($cid, $reg, $urls, $wait, $preview) {

        $ret = array();
        $campaign = $this->get_campaign($cid, false);
        $options = $this->get_options($campaign);
        $service_urls = $options['service_urls'];
        $settings = $this->ml->get_settings();

        if ($reg && $urls) {
            foreach ($urls as $url) {
                $url = htmlspecialchars_decode($url);
                $code = $this->get_code_by_current_driver($url, $headers, $settings, $service_urls);
                if (preg_match_all($reg, $code, $match)) {
                    foreach ($match[1] as $u) {
                        if (preg_match('#^/#', $u)) {
                            //Short links
                            $domain = preg_replace('#^([^\/]+\:\/\/[^\/]+)(\/|\?|\#).*#', '$1', $url . '/');
                            $u = $domain . $u;
                        }

                        if (!$preview) {
                            $add = $this->add_url($cid, $u);
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

    public function update_url_pid($id = 0, $pid = 0) {
        $sql = sprintf("UPDATE {$this->db['url']} SET pid=%d WHERE id=%d", $pid, $id);
        $this->db_query($sql);
    }

    /* Arhive URLs */

    public function get_arhive_by_url_id($uid) {
        $sql = sprintf("SELECT * FROM {$this->db['arhive']} WHERE uid = %d", (int) $uid);
        $result = $this->db_fetch_row($sql);
        return $result;
    }

    public function add_arhive($item) {
        $date = $this->curr_time();
        $this->db_query(sprintf("INSERT INTO {$this->db['arhive']} (
                date,                  
                uid,
                arhive_hash                               
                ) VALUES (%d,%d,'%s')", $date, $item->id, $item->link_hash));
    }

    public function update_arhive($item) {
        $date = $this->curr_time();
        $sql = sprintf("UPDATE {$this->db['arhive']} SET            
                date=%d,                 
                arhive_hash='%s'                                   
                WHERE uid = %d", $date, $item->link_hash, $item->id
        );

        $this->db_query($sql);
    }

    public function delete_arhive($uid) {
        $arhive = $this->get_arhive_by_url_id($uid);
        if ($arhive) {
            $url = $this->get_url($uid);
            if ($url) {
                //Delete file
                $this->delete_arhive_file($url->cid, $arhive->arhive_hash);

                //Delete arhive
                $sql = sprintf("DELETE FROM {$this->db['arhive']} WHERE uid = %d", (int) $uid);
                $this->db_query($sql);
            }
        }
    }

    public function get_arhive_path($cid, $link_hash, $create_dir = false) {

        $arhive_path = $this->ml->arhive_path;
        $first_letter = substr($link_hash, 0, 1);
        $cid_path = $arhive_path . $cid . '/';
        $first_letter_path = $cid_path . $first_letter . '/';

        if ($create_dir) {
            $this->check_and_create_dir($first_letter_path);
        }
        $full_path = $first_letter_path . $link_hash;

        return $full_path;
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

    public function delete_arhive_file($cid, $link_hash) {
        $full_path = $this->get_arhive_path($cid, $link_hash);

        $remove = true;
        if (file_exists($full_path)) {
            $remove = unlink($full_path);
        }
        return $remove;
    }

    public function preview_arhive($url, $settings, $options) {
        $headers = '';

        $type_name = 'arhive';
        $type_opt = $options[$type_name];

        // Get posts (last is first)       
        $code = $this->get_code_by_current_driver($url, $headers, $settings, $type_opt);

        $body_len = strlen($code);
        $valid_body_len = $this->validate_body_len($body_len, $type_opt['body_len']);
        $ret['content'] = $code;
        $ret['headers'] = $headers;
        $ret['headers_status'] = $this->get_header_status($headers);
        $ret['valid_body'] = $valid_body_len;
        $ret['body_len'] = $body_len;
        return $ret;
    }

    public function get_code_by_current_driver($url, &$headers, $settings = array(), $type_opt = array()) {
        $use_webdriver = $type_opt['webdrivers'];
        $ip_limit = array('h' => $type_opt['tor_h'], 'd' => $type_opt['tor_d']);
        $tor_mode = $type_opt['tor_mode'];

        $chd = array();
        $custom_headers = isset($type_opt['chd']) ? trim(base64_decode($type_opt['chd'])) : '';
        if ($custom_headers) {
            if (strstr($custom_headers, "\n")) {
                $chd = explode("\n", $custom_headers);
            } else {
                $chd[] = $custom_headers;
            }
        }

        if ($use_webdriver == 1) {
            $code = $this->get_webdriver($url, $headers, $settings);
        } else if ($use_webdriver == 2) {
            // Tor webdriver            
            $tp = $this->ml->get_tp();
            $code = $tp->get_url_content($url, $headers, $ip_limit, false, $tor_mode);
        } else if ($use_webdriver == 3) {
            // Tor curl            
            $tp = $this->ml->get_tp();
            $code = $tp->get_url_content($url, $headers, $ip_limit, true, $tor_mode);
        } else {
            $use_proxy = $type_opt['proxy'];
            $code = $this->get_proxy($url, $use_proxy, $headers, $settings, $chd);
        }
        return $code;
    }

    public function validate_body_len($body_len = 0, $valid_len = 500) {
        if ($body_len > $valid_len) {
            return true;
        }
        return false;
    }

    /*
     * Parsing rules
     */

    public function get_last_arhives_no_posts($count = 10, $cid = 0, $version = 0, $no_posts = true, $debug = false, $custom_url = 0) {

        // Company id
        $cid_and = '';

        if ($cid > 0) {
            $cid_and = sprintf(" AND u.cid=%d", (int) $cid);
        }

        $and_version = '';
        if ($version > 0) {
            $and_version = sprintf(' OR p.version!=%d', $version);
        }

        $np_and = '';
        if ($no_posts) {
            $np_and = ' AND (p.uid is NULL' . $and_version . ')';
        } else {
            $np_and = ' AND (p.uid is NULL OR p.multi=0' . $and_version . ')';
        }


        if ($custom_url > 0) {

            $query = sprintf("SELECT a.uid, a.arhive_hash, u.cid, u.id as uid, u.pid as upid FROM {$this->db['arhive']} a"
                    . " INNER JOIN {$this->db['url']} u ON u.id = a.uid"
                    . " LEFT JOIN {$this->db['posts']} p ON p.uid = a.uid"
                    . " WHERE u.id=%d", (int) $custom_url);
        } else {

            $query = sprintf("SELECT a.uid, a.arhive_hash, u.cid, u.id as uid, u.pid as upid FROM {$this->db['arhive']} a"
                    . " INNER JOIN {$this->db['url']} u ON u.id = a.uid"
                    . " LEFT JOIN {$this->db['posts']} p ON p.uid = a.uid"
                    . " WHERE a.id>0 AND u.status!=4" . $np_and . $cid_and
                    . " ORDER BY a.id DESC LIMIT %d", (int) $count);
        }
        if ($debug) {
            print "$query\n";
        }

        $result = $this->db_results($query);

        return $result;
    }

    public function get_last_arhives_no_critics($count = 10, $cid = 0, $version = 0, $no_posts = true, $debug = false, $custom_url = 0) {

        // Company id
        $cid_and = '';

        if ($cid > 0) {
            $cid_and = sprintf(" AND u.cid=%d", (int) $cid);
        }

        $and_version = '';
        if ($version > 0) {
            $and_version = sprintf(' OR c.version!=%d', $version);
        }

        // Critic posts
        $cp_and = ' AND (c.uid is NULL' . $and_version . ')';

        if ($custom_url > 0) {

            $query = sprintf("SELECT a.uid, a.arhive_hash, u.cid, u.id as uid, u.pid as upid, u.link, u.link_hash, p.top_movie, p.id AS pid FROM {$this->db['arhive']} a"
                    . " INNER JOIN {$this->db['url']} u ON u.id = a.uid"
                    . " INNER JOIN {$this->db['posts']} p ON p.uid = a.uid"
                    . " LEFT JOIN {$this->db['critics']} c ON c.uid = a.uid"
                    . " WHERE u.id=%d", (int) $custom_url);
        } else {

            $query = sprintf("SELECT a.uid, a.arhive_hash, u.cid, u.id as uid, u.pid as upid, u.link, u.link_hash, p.top_movie, p.id AS pid FROM {$this->db['arhive']} a"
                    . " INNER JOIN {$this->db['url']} u ON u.id = a.uid"
                    . " INNER JOIN {$this->db['posts']} p ON p.uid = a.uid"
                    . " LEFT JOIN {$this->db['critics']} c ON c.uid = a.uid"
                    . " WHERE a.id>0 AND u.status!=4 AND p.top_movie>0" . $cp_and . $cid_and
                    . " ORDER BY a.id DESC LIMIT %d", (int) $count);
        }
        if ($debug) {
            print "$query\n";
        }

        $result = $this->db_results($query);

        return $result;
    }

    public function get_last_expired_urls_arhives($count = 10, $cid = 0) {

        // Company id
        $cid_and = '';
        if ($cid > 0) {
            $cid_and = sprintf(" AND u.cid=%d", (int) $cid);
        }

        $query = sprintf("SELECT a.uid, a.arhive_hash, u.cid FROM {$this->db['arhive']} a"
                . " INNER JOIN {$this->db['url']} u ON u.id = a.uid"
                . " LEFT JOIN {$this->db['posts']} p ON p.uid = a.uid"
                . " WHERE a.id>0 AND u.exp_status=2" . $cid_and
                . " ORDER BY u.upd_rating DESC LIMIT %d", (int) $count);

        $result = $this->db_results($query);

        return $result;
    }

    public function parse_arhives($items, $campaign, $rules_name = 'rules', $debug = false) {
        ini_set('max_execution_time', '300'); //300 seconds = 5 minutes
        set_time_limit(300);
        $ret = array();
        if ($items) {
            $cid = $campaign->id;
            $options = $this->get_options($campaign);
            $o = $options['parsing'];
            foreach ($items as $item) {
                $link_hash = $item->arhive_hash;
                $code = $this->get_arhive_file($cid, $link_hash);
                $result = array();
                if ($code) {
                    if ($debug) {
                        print "File arhive exist: $link_hash\n";
                    }
                    // Use reg rules
                    if ($rules_name == 'rules' && $o['row_status'] == 1) {
                        // Get rows, multi resulst
                        $rules_fields = $this->parser_row_rules_fields;
                        $row = $this->check_reg_post($o, 'row_rules', $code, $rules_fields);
                        if ($row['t']) {
                            $row_content = $row['t'];
                            $rules_fields = $this->parser_urls_rules_fields;
                            $result = $this->check_reg_post($o, 'rules', $row_content, $rules_fields);
                        }
                    } else if ($rules_name == 'multi') {
                        // Multi rules
                        $result = $this->use_multi_rules($o, $code);
                    } else {
                        $rules_fields = array();
                        if ($campaign->parsing_mode == 1) {
                            $rules_fields = $this->parser_urls_rules_fields;
                        }

                        // Is Multi?
                        if ($o['multi_parsing'] == 1) {
                            $rows = $this->use_multi_rules($o, $code);
                            $result_arr = array();
                            if ($rows) {
                                foreach ($rows as $row) {
                                    $result_arr[] = $this->check_reg_post($o, $rules_name, $row, $rules_fields);
                                }
                            }
                            $result = $result_arr;
                        } else {
                            $result = $this->check_reg_post($o, $rules_name, $code, $rules_fields);
                        }
                    }
                } else {
                    if ($debug) {
                        print "File arhive is empty: $link_hash\n";
                    }
                }
                $ret[$item->uid] = $result;
            }
        }
        return $ret;
    }

    public function parse_critics($items, $campaign, $preview = false, $debug = false) {
        ini_set('max_execution_time', '300'); //300 seconds = 5 minutes
        set_time_limit(300);

        $ret = array();
        if ($items) {
            $cid = $campaign->id;
            $options = $this->get_options($campaign);
            $o = $options['critics'];
            $cm = $this->ml->get_cm();
            if ($cm) {
                $cp = $cm->get_cp();
                $cprules = $cp->get_cprules();
                foreach ($items as $item) {
                    $arhive_hash = $item->arhive_hash;
                    $code = $this->get_arhive_file($cid, $arhive_hash);

                    if ($code) {
                        if ($debug) {
                            print "File arhive exist: $arhive_hash\n";
                        }

                        $id = $item->uid;
                        $ret[$id]['raw'] = $code;

                        // Default
                        // if (isset($o['p_encoding']) && $o['p_encoding'] != 'utf-8') {
                        //    $code = mb_convert_encoding($code, 'utf-8', $options['p_encoding']);
                        // }
                        // Use reg rules
                        $items = $cprules->check_reg_post($o['rules'], $code, '', $item->link);

                        $content = isset($items['d']) ? $items['d'] : '';
                        $title = isset($items['t']) ? $items['t'] : '';
                        $author = isset($items['a']) ? $items['a'] : '';
                        $date_raw = isset($items['y']) ? $items['y'] : '';

                        if ($content) {
                            $content = $cp->force_balance_tags($content);
                        }

                        if ($content) {
                            // Core filters
                            $domain = preg_replace('#^([a-z]+\:\/\/[^\/]+)(\/|\?|\#).*#', '$1', $item->link . '/');
                            $content = $cp->absoluteUrlFilter($domain, $content);
                        }
                        if ($date_raw) {
                            $date = strtotime($date_raw);
                        } else {
                            $date = $cp->curr_time();
                        }

                        $ret[$id]['title'] = $title;
                        $ret[$id]['date'] = $date;
                        $ret[$id]['date_raw'] = $date_raw;
                        $ret[$id]['author'] = $author;
                        $ret[$id]['content'] = $content;
                        $add_post = true;

                        if (!$preview) {
                            // Add post
                            // Post exist?
                            $link_hash = $item->link_hash;
                            // Check the post already in db
                            $post_exist = $cm->get_post_by_link_hash($link_hash);

                            if ($debug) {
                                print_r(array('post_exist', $post_exist));
                            }

                            if ($content && $title) {
                                if ($debug) {
                                    print_r(array('title', $title));
                                }

                                $content = $cp->force_balance_tags($content);
                                // Core filters
                                $domain = preg_replace('#^([a-z]+\:\/\/[^\/]+)(\/|\?|\#).*#', '$1', $item->link . '/');
                                $content = $cp->absoluteUrlFilter($domain, $content);

                                // MoviesLinks
                                $post_type = 5;
                                // Post status publish
                                $post_status = 1;
                                $top_movie = (int) $item->top_movie;
                                $pid = 0;

                                if ($post_exist) {
                                    // Update post
                                    $log_message = 'Update post';
                                    $pid = $post_exist->id;
                                    $data = array(
                                        'status' => $post_status,
                                        'type' => $post_type,
                                        'title' => $title,
                                        'content' => $content,
                                        'top_movie' => $top_movie,
                                    );
                                    $cm->update_post_fields($pid, $data);
                                } else {
                                    $view_type = $cm->get_post_view_type($item->link);
                                    // Add post 
                                    $log_message = 'Add post';
                                    $pid = $cm->add_post($date, $post_type, $item->link, $title, $content, $top_movie, $post_status, $view_type);

                                    if ($pid) {
                                        // Add author
                                        if ($author) {
                                            $author_type = 1;
                                            $author_status = 0;
                                            $aid = $cm->get_or_create_author_by_name($author, $author_type, $author_status);
                                        } else {
                                            $aid = $o['author'];
                                        }

                                        $cm->add_post_author($pid, $aid);
                                    } else {
                                        $log_message = "Error add post url: " . $item->link . ", campaign:" . $cid;
                                        // $status = 4;
                                        // $cp->change_url_state($id, $status);


                                        if ($debug) {
                                            print_r(array('error', $log_message));
                                        }
                                        $add_post = false;
                                    }
                                }

                                if ($pid) {
                                    // Add top movie meta                                                        
                                    // Type: 1 => 'Proper Review',
                                    $type = 1;
                                    // State: 1 => 'Approved',
                                    $state = 1;
                                    // Add meta                                    
                                    $cm->add_post_meta($top_movie, $type, $state, $pid, 0, false);
                                }
                            } else {
                                $message = 'Error URL filters';
                                if (!$title) {
                                    $message .= '. No Title';
                                }
                                if (!$content) {
                                    $message .= '. No Content';
                                }
                            }

                            // Default status
                            $meta_status = 1;

                            if (!$add_post) {
                                // Error status
                                $meta_status = 2;
                            }

                            // Add meta
                            $sql = sprintf("SELECT id FROM {$this->db['critics']} WHERE uid=%d", $id);
                            $meta_exist_id = $this->db_get_var($sql);
                            if ($debug) {
                                print_r(array('meta_exist_id', $meta_exist_id));
                            }
                            if ($meta_exist_id) {
                                // Change version
                                $data = array(
                                    'version' => $o['version'],
                                    'status' => $meta_status,
                                );
                                $this->db_update($data, $this->db['critics'], $meta_exist_id);
                            } else {
                                // Add meta
                                $curr_time = $this->curr_time();
                                $data = array(
                                    'date' => $curr_time,
                                    'last_upd' => $curr_time,
                                    'uid' => $id,
                                    'pid' => $item->pid,
                                    'critic_id' => $pid,
                                    'status' => $meta_status,
                                    'version' => $o['version'],
                                );
                                $this->db_insert($data, $this->db['critics']);
                            }

                            if ($debug) {
                                print_r(array('data', $data));
                            }

                            if ($add_post) {
                                $this->log_info($log_message, $cid, $id, 6);
                            } else {
                                $this->log_error($log_message, $cid, $id, 6);
                            }
                        }
                    } else {
                        if ($debug) {
                            print "File arhive is empty: $link_hash\n";
                        }
                    }
                }
            }
        }

        return $ret;
    }

    private function use_multi_rules($o, $code) {
        $type = $o['multi_rule_type'];
        $reg = base64_decode($o['multi_rule']);
        $ret = array();
        $match_str = '';
        // Regexp
        if ($type == 0) {
            if (preg_match_all($reg, $code, $match_all)) {
                $ret = $match_all[0];
            }
        } else {
            // Xpath
            $ret = $this->get_dom($reg, $match_str, $code, true, false);
        }
        return $ret;
    }

    private function use_reg_rule($rule, $content) {
        $reg = base64_decode($rule['r']);
        $is_array = true;
        if (!is_array($content)) {
            $is_array = false;
            $content_arr = array($content);
        } else {
            $content_arr = $content;
        }
        $rule_cnt = array();
        foreach ($content_arr as $cnt) {
            if ($rule['t'] == 'x') {
                $rule_cnt[] = $this->get_dom($reg, $rule['m'], $cnt);
            } else if ($rule['t'] == 'p') {
                $rule_cnt[] = $this->get_dom($reg, $rule['m'], $cnt, true);
            } else if ($rule['t'] == 'y') {
                $is_array = true;
                $rule_cnt[] = $this->get_dom($reg, $rule['m'], $cnt, true, false);
            } else if ($rule['t'] == 'm') {
                $rule_cnt[] = $this->get_reg_match($reg, $rule['m'], $cnt);
            } else if ($rule['t'] == 'a') {
                $rule_cnt[] = $this->get_reg_match_all($reg, $rule['m'], $cnt);
            } else if ($rule['t'] == 'b') {
                $is_array = true;
                $rule_cnt[] = $this->get_reg_match_all($reg, $rule['m'], $cnt, false);
            } else if ($rule['t'] == 'r') {
                $rule_cnt[] = $this->get_reg($reg, $rule['m'], $cnt);
            } else if ($rule['t'] == 'n') {
                //No rules   
                $rule_cnt = $cnt;
            }
        }

        if (isset($rule['s']) && $rule['s'] > 0) {
            $strip_cnt = array();
            foreach ($rule_cnt as $cnt) {
                if (!is_array($cnt)) {
                    $strip_cnt[] = $this->strip_tags_content($cnt);
                } else {
                    $strip_c = array();
                    foreach ($cnt as $c) {
                        $strip_c[] = $this->strip_tags_content($c);
                    }
                    $strip_cnt[] = $strip_c;
                }
            }
            $rule_cnt = $strip_cnt;
        }

        $ret = $rule_cnt;
        if (!$is_array && is_array($rule_cnt)) {
            $ret = array_pop($rule_cnt);
        }

        return $ret;
    }

    public function strip_tags_content($content = '') {
        $content = strip_tags($content);
        $content = preg_replace('/(  |\&nbsp;|\n)/', ' ', $content);
        $content = trim($content);
        return $content;
    }

    public function check_reg_post($o, $rules_name, $content, $rules_fields = array()) {
        $results = array();
        $rules = $o[$rules_name];

        if (!$rules_fields) {
            $rules_fields = $this->parser_rules_fields;
        }
        if ($rules && sizeof($rules)) {
            $rules_w = $this->sort_reg_rules_by_weight($rules, $rules_fields);

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

            foreach ($rules_fields as $type => $title) {
                $i = 0;
                foreach ($rules_w as $key => $rule) {
                    if ($type == $rule['f']) {

                        $type_key = $type;
                        if ($type == 'c') {
                            $type_key = $rule['k'];
                        }

                        if ($rule['a'] != 1) {
                            continue;
                        }
                        if ($rule['n'] == 1) {
                            $i += 1;
                        }

                        if (!isset($results[$type_key][$i])) {
                            $results[$type_key][$i] = $content;
                        }
                        $results[$type_key][$i] = $this->use_reg_rule($rule, $results[$type_key][$i]);
                    }
                }
            }
        }

        //implode results

        $ret_arr = array();

        foreach ($results as $type => $items) {
            $found_array = false;
            foreach ($items as $item) {
                if (is_array($item)) {
                    foreach ($item as $i) {


                        if (is_array($i)) {
                            foreach ($i as $j) {
                                $ret_arr[$type][] = $j;
                            }
                        } else {
                            $ret_arr[$type][] = $i;
                        }
                    }
                    $found_array = true;
                } else {
                    $ret_arr[$type][] = $item;
                }
            }

            if ($found_array) {
                $ret[$type] = $ret_arr[$type];
            } else {
                $ret[$type] = implode('', $ret_arr[$type]);
            }
        }
        return $ret;
    }

    public function sort_reg_rules_by_weight($rules, $parser_rules_fields = array()) {
        $sort_rules = $rules;
        if (!$parser_rules_fields) {
            $parser_rules_fields = $this->parser_rules_fields;
        }
        if ($rules) {
            $rules_w = array();
            foreach ($rules as $key => $value) {
                $rules_w[$key] = $value['w'];
            }
            asort($rules_w);
            $sort_rules = array();
            foreach ($parser_rules_fields as $id => $item) {
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

    public function get_def_parser_rule($key) {
        return $this->def_reg_rule[$key];
    }

    private function get_dom($rule, $match_str, $code, $all = false, $str = true, $glue = '') {
        $content = array();
        if ($rule && $code) {
            $code = force_balance_tags($code);
            $dom = new DOMDocument;
            libxml_use_internal_errors(true);
            $dom->loadHTML($code);
            $xpath = new DOMXPath($dom);
            $result = $xpath->query($rule);
            if (!is_null($result)) {
                foreach ($result as $element) {
                    $content[] = $this->getNodeInnerHTML($element);
                    if (!$all) {
                        break;
                    }
                }
            }
        }
        unset($dom);
        unset($xpath);
        if ($match_str) {
            $ret = array();
            foreach ($content as $item) {
                $ret[] = str_replace('$1', $item, $match_str);
            }
            $content = $ret;
        }
        if ($str) {
            $result = implode($glue, $content);
        } else {
            $result = $content;
        }
        return $result;
    }

    private function getNodeInnerHTML(DOMNode $oNode) {
        $oDom = new DOMDocument();
        foreach ($oNode->childNodes as $oChild) {
            $oDom->appendChild($oDom->importNode($oChild, true));
        }
        return $oDom->saveHTML();
    }

    private function get_reg($rule, $match_str, $content) {
        //Filters reg
        if ($rule) {
            $content = preg_replace($rule, $match_str, $content);
        }
        return $content;
    }

    private function get_reg_match($rule, $match_str, $content) {
        //Filters match
        $ret = '';
        if ($rule && $content) {
            if (preg_match($rule, $content, $match)) {
                //Math reg
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

    private function get_reg_match_all($rule, $match_str, $content, $str = true, $glue = '') {
        //Filters match all
        $ret_a = array();
        if ($rule && $content) {
            if (preg_match_all($rule, $content, $match)) {
                //Math reg                
                for ($m = 0; $m < sizeof($match[0]); $m++) {
                    $ret = '';
                    if (preg_match_all('/\$([0-9]+)/', $match_str, $match_all)) {
                        for ($i = 0; $i < sizeof($match_all[0]); $i++) {
                            $num = (int) $match_all[1][$i];
                            if (!$ret) {
                                $ret = $match_str;
                            }
                            if (isset($match[$num][$m])) {
                                $ret = str_replace($match_all[0][$i], trim($match[$num][$m]), $ret);
                            }
                        }
                    }
                    $ret_a[] = $ret;
                }
            }
        }

        if ($str) {
            $result = implode($glue, $ret_a);
        } else {
            $result = $ret_a;
        }
        return $result;
    }

    public function get_parser_fields($options, $parser_rules_fields = array()) {
        if (!$parser_rules_fields) {
            $parser_rules_fields = $this->parser_rules_fields;
        }
        $rules = $options['parsing']['rules'];
        $rules_sort = $this->sort_reg_rules_by_weight($rules, $parser_rules_fields);

        $ret = array();
        if ($rules_sort) {
            foreach ($rules_sort as $key => $rule) {
                $rule_key = $rule['f'];
                $rule_custom = $rule['k'];
                if ($rule_key == 'c') {
                    //Custom field
                    $rule_key_custom = 'c-' . $rule_custom;
                    $ret[$rule_key_custom] = $rule_custom;
                } else {
                    $rule_title = $parser_rules_fields[$rule_key];
                    $ret[$rule_key] = $rule_title;
                }
            }
        }
        return $ret;
    }

    public function get_last_posts($count = 10, $cid = 0, $status_links = -1, $status = -1, $version = -1, $min_pid = 0, $order = "DESC") {

        // Company id
        $cid_and = '';

        if ($cid > 0) {
            $cid_and = sprintf(" AND u.cid=%d", (int) $cid);
        }

        $status_and = '';
        if ($status != -1) {
            $status_and = sprintf(' AND p.status = %d', $status);
        }


        $status_links_and = '';
        if ($status_links != -1) {
            $status_links_and = sprintf(' AND p.status_links = %d', $status_links);
        }

        $version_and = '';
        if ($version != -1) {
            $version_and = sprintf(' AND p.version = %d', $version);
        }

        $and_order = "DESC";
        if ($order == "ASC") {
            $and_order = $order;
        }

        $query = sprintf("SELECT p.id, u.pid FROM {$this->db['posts']} p"
                . " INNER JOIN {$this->db['url']} u ON p.uid = u.id"
                . " WHERE p.id>%d" . $cid_and . $version_and . $status_and . $status_links_and
                . " ORDER BY p.id $and_order LIMIT %d", (int) $min_pid, (int) $count);

        $result = $this->db_results($query);
        $ret = array();
        if ($result) {
            $ulrs = array();
            $ids = array();
            foreach ($result as $item) {
                $ids[] = $item->id;
                $ulrs[$item->id] = $item->pid;
            }
            $query = "SELECT * FROM {$this->db['posts']} WHERE id IN(" . implode(',', $ids) . ")";
            $contents = $this->db_results($query);
            if ($contents) {
                foreach ($contents as $item) {
                    $ret_item = $item;
                    $ret_item->pid = $ulrs[$ret_item->id];
                    $ret[] = $ret_item;
                }
            }
        }

        return $ret;
    }

    public function get_last_arhives($cid = 0, $start = 0, $count = 10, $top_movie = 0, $last_update = 0) {
        if ($cid > 0) {
            $cid_and = sprintf(" AND u.cid=%d", (int) $cid);
        }

        $and_top_movie = ' AND p.top_movie>0';
        if ($top_movie > 0) {
            $and_top_movie = sprintf(' AND p.top_movie=%d', $top_movie);
        }
        $and_last_update = '';
        if ($last_update) {
            $and_last_update = ' AND p.last_upd > ' . $last_update . ' ';
        }

        $query = sprintf("SELECT p.top_movie, a.arhive_hash FROM {$this->db['posts']} p"
                . " INNER JOIN {$this->db['url']} u ON p.uid = u.id"
                . " INNER JOIN {$this->db['arhive']} a ON a.uid = u.id"
                . " WHERE p.id>0" . $and_top_movie . $cid_and . $and_last_update
                . " ORDER BY p.id DESC LIMIT %d,%d", (int) $start, $count);

        $result = $this->db_results($query);

        return $result;
    }

    /* Link rules */

    public function check_link_post($o, $post, $movie_id = 0) {
        $rules = $o['rules'];
        $min_match = $o['match'];
        $min_rating = $o['rating'];
        $movie_type = $this->movie_type[$o['type']];

        $results = array();
        $search_fields = array();
        $active_rules = array();

        if ($rules && sizeof($rules)) {
            $rules_w = $this->sort_link_rules_by_weight($rules);

            /*
             * Array ( [1] => Array ( 
             * [f] => t 
             * [t] => m 
             * [d] => t 
             * [m] => 
             * [r] => 10 
             * [c] => 
             * [w] => 0 
             * [a] => 1 )
             */

            //Find active rules
            foreach ($this->links_rules_fields as $type => $title) {
                $i = 0;
                foreach ($rules_w as $key => $rule) {
                    if ($type == $rule['f']) {

                        if ($rule['a'] != 1) {
                            continue;
                        }

                        $type_key = $type;

                        if (!isset($active_rules[$type_key][$i])) {
                            $active_rules[$type_key][$i] = $rule;
                            $active_rules[$type_key][$i]['content'] = $this->use_reg_rule($rule, $this->get_post_field($rule, $post));
                        }

                        $i += 1;
                    }
                }
            }

            //Get title
            $post_title_name = array();
            $title_rule = array();
            if ($active_rules['t']) {
                foreach ($active_rules['t'] as $key => $item) {

                    $field = 'title';
                    if ($key > 0) {
                        $field = $field . '-' . $key;
                    }

                    $post_name = '';
                    if ($item['content']) {
                        $post_name = trim($item['content']);
                        $post_title_name[$key] = $post_name;
                        $title_rule[$key] = $item;
                    }
                    $search_fields[$field] = $post_name;
                }
            }

            //Get year
            $post_year_name = '';
            $year_rule = '';
            if ($active_rules['y']) {
                foreach ($active_rules['y'] as $item) {
                    if ($item['content']) {
                        $post_year_name = $item['content'];
                        $year_rule = $item;
                        break;
                    }
                }
                $search_fields['year'] = $post_year_name;
            }

            //Get imdb
            $post_imdb = '';
            $imdb_rule = '';
            if ($active_rules['im']) {
                foreach ($active_rules['im'] as $item) {
                    if ($item['content']) {
                        $post_imdb = (int) preg_replace('/[^0-9]+/', '', $item['content']);
                        $imdb_rule = $item;
                        break;
                    }
                }
                $search_fields['imdb'] = $post_imdb;
            }

            //Get tmdb
            $post_tmdb = '';
            $tmdb_rule = '';
            if ($active_rules['tm']) {
                foreach ($active_rules['tm'] as $item) {
                    if ($item['content']) {
                        $post_tmdb = (int) preg_replace('/[^0-9]+/', '', $item['content']);
                        $tmdb_rule = $item;
                        break;
                    }
                }
                $search_fields['tmdb'] = $post_tmdb;
            }

            //Get link movie id
            $post_mid = '';
            $mid_rule = '';
            if ($active_rules['m']) {
                foreach ($active_rules['m'] as $item) {
                    if ($item['content']) {
                        $post_mid = (int) $item['content'];
                        $mid_rule = $item;
                        break;
                    }
                }
                $search_fields['mid'] = $post_mid;
            }

            // Get exist
            $post_exist_name = '';
            $exist_rule = '';
            if ($active_rules['e']) {
                foreach ($active_rules['e'] as $item) {
                    if ($item['content']) {
                        $post_exist_name = $item['content'];
                        $exist_rule = $item;
                        break;
                    }
                }
                $search_fields['exist'] = $post_exist_name;
            }

            // Get exist movie
            $post_exist_movie_name = '';
            $exist_movie_rule = '';
            if ($active_rules['em']) {
                foreach ($active_rules['em'] as $item) {
                    if ($item['content']) {
                        $post_exist_movie_name = $item['content'];
                        $exist_movie_rule = $item;
                        break;
                    }
                }
                $search_fields['exist_movie'] = $post_exist_movie_name;
            }

            // Get exist tv
            $post_exist_tv_name = '';
            $exist_tv_rule = '';
            if ($active_rules['et']) {
                foreach ($active_rules['et'] as $item) {
                    if ($item['content']) {
                        $post_exist_tv_name = $item['content'];
                        $exist_tv_rule = $item;
                        break;
                    }
                }
                $search_fields['exist_tv'] = $post_exist_tv_name;
            }

            // Get exist game
            $post_exist_game_name = '';
            $exist_game_rule = '';
            if ($active_rules['eg']) {
                foreach ($active_rules['eg'] as $item) {
                    if ($item['content']) {
                        $post_exist_game_name = $item['content'];
                        $exist_game_rule = $item;
                        break;
                    }
                }
                $search_fields['exist_game'] = $post_exist_game_name;
            }

            $ms = $this->ml->get_ms();
            $facets = array();
            if ($movie_id > 0) {
                $movies = $ms->search_movies_by_id($movie_id);
                //p_r(array($movies,$post_title_name));
                foreach ($post_title_name as $key => $name) {
                    $movies_title = $ms->search_movies_by_title($name, $title_rule[$key]['e'], $post_year_name, 20, $movie_type);

                    if (!isset($movies_title[$movie_id])) {
                        if ($movies[$movie_id]->title != $name) {
                            $post_title_name[$key] = '';
                        }
                    }
                }
            } else if ($movie_id == -1) {
                $movie = new stdClass();
                $movie->id = -1;
                $movies = array($movie);
            } else {
                $movies_posts = array();
                if ($post_mid) {
                    $movies_posts = $ms->search_movies_by_id($post_mid);
                }

                $movies_imdb = array();
                if ($post_imdb) {
                    // Find movies by IMDB            
                    $movies_imdb = $ms->search_movies_by_imdb($post_imdb);
                }

                $movies_tmdb = array();
                if ($post_tmdb) {
                    // Find movies by IMDB            
                    $movies_tmdb = $ms->search_movies_by_tmdb($post_tmdb);
                }

                if ($post_title_name) {
                    // Find movies by title and year
                    foreach ($post_title_name as $key => $name) {
                        if ($name) {
                            $movies_title = $ms->search_movies_by_title($name, $title_rule[$key]['e'], $post_year_name, 20, $movie_type);
                        }
                    }
                }

                $movies = array();

                if ($movies_title) {
                    $movies = array_merge($movies, $movies_title);
                }

                if ($movies_imdb) {
                    $movies = array_merge($movies, $movies_imdb);
                }

                if ($movies_tmdb) {
                    $movies = array_merge($movies, $movies_tmdb);
                }

                if ($movies_posts) {
                    $movies = array_merge($movies, $movies_posts);
                }
            }

            if ($movies) {
                /*
                  (
                  [id] => 13522
                  [title] => Calvary
                  [release] => 2014-04-11
                  [year] => 2014
                  [w] => 1727
                  )
                 */
                foreach ($movies as $movie) {

                    if ($results[$movie->id]) {
                        // Already in list
                        continue;
                    }
                    //Movie              
                    if ($post_title_name) {
                        foreach ($post_title_name as $key => $name) {

                            $field = 'title';
                            if ($key > 0) {
                                $field = $field . '-' . $key;
                            }

                            $cnt = 1;
                            $rating = $title_rule[$key]['ra'];
                            if (!$name) {
                                $cnt = 0;
                                $rating = 0;
                            }

                            $results[$movie->id][$field]['data'] = $movie->title;
                            $results[$movie->id][$field]['match'] = $cnt;
                            $results[$movie->id][$field]['rating'] = $rating;

                            $results[$movie->id]['total']['match'] += $cnt;
                            $results[$movie->id]['total']['rating'] += $rating;
                        }
                    }

                    if ($post_year_name) {
                        //Year in title query
                        $results[$movie->id]['year']['data'] = $movie->year;
                        $year_match = 0;
                        $year_rating = 0;
                        if ($movie->year == $post_year_name) {
                            $year_match = 1;
                            $year_rating = $year_rule['ra'];
                        }
                        $results[$movie->id]['year']['match'] = $year_match;
                        $results[$movie->id]['year']['rating'] = $year_rating;
                        $results[$movie->id]['total']['match'] += $year_match;
                        $results[$movie->id]['total']['rating'] += $year_rating;
                    }

                    if ($post_imdb) {
                        $results[$movie->id]['imdb']['data'] = $movie->movie_id;
                        $match = 0;
                        $rating = 0;
                        if ($movie->movie_id == $post_imdb) {
                            $match = 1;
                            $rating = $imdb_rule['ra'];
                        }
                        $results[$movie->id]['imdb']['match'] = $match;
                        $results[$movie->id]['imdb']['rating'] = $rating;
                        $results[$movie->id]['total']['match'] += $match;
                        $results[$movie->id]['total']['rating'] += $rating;
                    }

                    if ($post_tmdb) {
                        $results[$movie->id]['tmdb']['data'] = $movie->tmdb_id;
                        $match = 0;
                        $rating = 0;
                        if ($movie->tmdb_id == $post_tmdb) {
                            $match = 1;
                            $rating = $tmdb_rule['ra'];
                        }
                        $results[$movie->id]['tmdb']['match'] = $match;
                        $results[$movie->id]['tmdb']['rating'] = $rating;
                        $results[$movie->id]['total']['match'] += $match;
                        $results[$movie->id]['total']['rating'] += $rating;
                    }

                    if ($post_mid) {
                        $results[$movie->id]['mid']['data'] = $movie->id;
                        $match = 0;
                        $rating = 0;
                        if ($movie->id == $post_mid) {
                            $match = 1;
                            $rating = $mid_rule['ra'];
                        }
                        $results[$movie->id]['mid']['match'] = $match;
                        $results[$movie->id]['mid']['rating'] = $rating;
                        $results[$movie->id]['total']['match'] += $match;
                        $results[$movie->id]['total']['rating'] += $rating;
                    }

                    // Exist              
                    if ($post_exist_name) {
                        $results[$movie->id]['exist']['data'] = $post_exist_name;
                        $results[$movie->id]['exist']['match'] = 1;
                        $results[$movie->id]['exist']['rating'] = $exist_rule['ra'];

                        $results[$movie->id]['total']['match'] += 1;
                        $results[$movie->id]['total']['rating'] += $exist_rule['ra'];
                    }

                    // Exist movie
                    if ($post_exist_movie_name) {
                        if ($movie->type == 'Movie') {
                            $results[$movie->id]['exist_movie']['data'] = $post_exist_movie_name;
                            $results[$movie->id]['exist_movie']['match'] = 1;
                            $results[$movie->id]['exist_movie']['rating'] = $exist_movie_rule['ra'];

                            $results[$movie->id]['total']['match'] += 1;
                            $results[$movie->id]['total']['rating'] += $exist_movie_rule['ra'];
                        }
                    }

                    // Exist tv
                    if ($post_exist_tv_name) {
                        if ($movie->type == 'TVSeries') {
                            $results[$movie->id]['exist_tv']['data'] = $post_exist_tv_name;
                            $results[$movie->id]['exist_tv']['match'] = 1;
                            $results[$movie->id]['exist_tv']['rating'] = $exist_tv_rule['ra'];

                            $results[$movie->id]['total']['match'] += 1;
                            $results[$movie->id]['total']['rating'] += $exist_tv_rule['ra'];
                        }
                    }

                    // Exist VideoGame
                    if ($post_exist_game_name) {
                        if ($movie->type == 'VideoGame') {
                            $results[$movie->id]['exist_game']['data'] = $post_exist_game_name;
                            $results[$movie->id]['exist_game']['match'] = 1;
                            $results[$movie->id]['exist_game']['rating'] = $exist_game_rule['ra'];

                            $results[$movie->id]['total']['match'] += 1;
                            $results[$movie->id]['total']['rating'] += $exist_game_rule['ra'];
                        }
                    }

                    //Facets
                    $facets[$movie->id] = $ms->get_movie_facets($movie->id);
                }
            } else {
                return array();
            }


            // Find other fields
            /*
              <option value="t">Title (Requed)</option>
              <option value="y">Year</option>
              <option value="r">Release</option>
              <option value="a">Actors</option>
              <option value="d">Director</option>
             */

            // Release
            if ($active_rules['r']) {
                foreach ($active_rules['r'] as $rule) {
                    $content = $rule['content'];
                    if (!$content) {
                        continue;
                    }
                    $search_fields['release'][] = $content;
                    if ($rule['mu']) {
                        $content_arr = explode($rule['mu'], $content);
                    } else {
                        $content_arr = array($content);
                    }

                    foreach ($movies as $movie) {
                        $release = $movie->release;
                        foreach ($content_arr as $release_raw) {
                            $release_raw = trim($release_raw);
                            if (!$release_raw) {
                                continue;
                            }
                            $release_time = strtotime($release_raw);
                            $release_valid_date = date('Y-m-d', $release_time);
                            //print_r(array($release,$release_valid_date, $release_raw));

                            if ($release == $release_valid_date) {
                                $results[$movie->id]['release']['match'] += 1;
                                $results[$movie->id]['release']['rating'] += $rule['ra'];
                                $results[$movie->id]['release']['data'][] = $release_valid_date;
                                $results[$movie->id]['total']['match'] += 1;
                                $results[$movie->id]['total']['rating'] += $rule['ra'];
                            }
                        }
                    }
                }
                if (is_array($search_fields['release'])) {
                    $search_fields['release'] = implode('; ', $search_fields['release']);
                }
            }

            //Runtime
            if ($active_rules['rt']) {
                foreach ($active_rules['rt'] as $rule) {
                    $content = $rule['content'];
                    if (!$content) {
                        continue;
                    }
                    $search_fields['runtime'][] = $content;
                    if ($rule['mu']) {
                        $content_arr = explode($rule['mu'], $content);
                    } else {
                        $content_arr = array($content);
                    }

                    foreach ($movies as $movie) {
                        $runtime = $movie->runtime;
                        foreach ($content_arr as $runtime_raw) {
                            $runtime_raw = trim($runtime_raw);
                            if (!$runtime_raw) {
                                continue;
                            }

                            if (preg_match('/([0-9]+)h ([0-9]+)m/', $runtime_raw, $match)) {
                                $runtime_valid = ($match[1] * 60 + $match[2]) * 60;
                            } else if (strstr($runtime_raw, '*')) {
                                $runtime_raw_arr = explode('*', $runtime_raw);
                                $runtime_valid = (int) $runtime_raw_arr[0] * (int) $runtime_raw_arr[1];
                            } else {
                                $runtime_valid = (int) $runtime_raw;
                            }
                            //print_r(array($runtime, $runtime_valid, $runtime_raw));

                            if ($runtime == $runtime_valid) {
                                $results[$movie->id]['runtime']['match'] += 1;
                                $results[$movie->id]['runtime']['rating'] += $rule['ra'];
                                $results[$movie->id]['runtime']['data'][] = $runtime_valid;
                                $results[$movie->id]['total']['match'] += 1;
                                $results[$movie->id]['total']['rating'] += $rule['ra'];
                            }
                        }
                    }
                }
                if (is_array($search_fields['runtime'])) {
                    $search_fields['runtime'] = implode('; ', $search_fields['runtime']);
                }
            }


            // Actors
            if ($active_rules['a']) {
                foreach ($active_rules['a'] as $actor_rule) {
                    $post_actors_name = $actor_rule['content'];
                    if (!$post_actors_name) {
                        continue;
                    }
                    $search_fields['actors'][] = $post_actors_name;
                    if ($actor_rule['mu']) {
                        $post_actors_name_arr = explode($actor_rule['mu'], $post_actors_name);
                    } else {
                        $post_actors_name_arr = array($post_actors_name);
                    }

                    foreach ($movies as $movie) {
                        $actors = array();
                        if (isset($facets[$movie->id]['actor']['data'])) {
                            $actor_data = $facets[$movie->id]['actor']['data'];
                            foreach ($actor_data as $item) {
                                $actors[] = $item->id;
                            }
                        }
                        foreach ($post_actors_name_arr as $actor_name) {
                            $actor_name = trim($actor_name);
                            if ($actor_name) {
                                $first = '';
                                $find_actors = $ms->find_actors($actor_name, $actors);
                                if ($find_actors) {
                                    $first = array_shift($find_actors);
                                    $results[$movie->id]['actors']['match'] += 1;
                                    $results[$movie->id]['actors']['rating'] += $actor_rule['ra'];
                                    $results[$movie->id]['actors']['data'][] = $first;
                                    $results[$movie->id]['total']['match'] += 1;
                                    $results[$movie->id]['total']['rating'] += $actor_rule['ra'];
                                }
                            }
                        }
                    }
                }
            }
            if (is_array($search_fields['actors'])) {
                $search_fields['actors'] = implode('; ', $search_fields['actors']);
            }

            // Director
            if ($active_rules['d']) {
                foreach ($active_rules['d'] as $director_rule) {
                    $post_actors_name = $director_rule['content'];
                    if (!$post_actors_name) {
                        continue;
                    }
                    $search_fields['directors'][] = $post_actors_name;
                    if ($director_rule['mu']) {
                        $post_actors_name_arr = explode($director_rule['mu'], $post_actors_name);
                    } else {
                        $post_actors_name_arr = array($post_actors_name);
                    }


                    foreach ($movies as $movie) {
                        $actors = array();
                        if (isset($facets[$movie->id]['director']['data'])) {
                            $actor_data = $facets[$movie->id]['director']['data'];
                            foreach ($actor_data as $item) {
                                $actors[] = $item->id;
                            }
                        }
                        foreach ($post_actors_name_arr as $actor_name) {
                            $first = '';
                            $actor_name = trim($actor_name);
                            if ($actor_name) {
                                $find_actors = $ms->find_actors($actor_name, $actors, false);
                                if ($find_actors) {
                                    $first = array_shift($find_actors);
                                    $results[$movie->id]['directors']['match'] += 1;
                                    $results[$movie->id]['directors']['rating'] += $director_rule['ra'];
                                    $results[$movie->id]['directors']['data'][] = $first;
                                    $results[$movie->id]['total']['match'] += 1;
                                    $results[$movie->id]['total']['rating'] += $director_rule['ra'];
                                }
                            }
                        }
                    }
                }
            }
            if (is_array($search_fields['directors'])) {
                $search_fields['directors'] = implode('; ', $search_fields['directors']);
            }


            // Genre
            if ($active_rules['g']) {
                $ma = $this->ml->get_ma();
                $all_genres = $ma->get_genres_names();

                foreach ($active_rules['g'] as $genre_rule) {
                    $post_genre_name = $genre_rule['content'];
                    if (!$post_genre_name) {
                        continue;
                    }
                    $search_fields['genres'][] = $post_genre_name;
                    if ($genre_rule['mu']) {
                        $post_genre_name_arr = explode($genre_rule['mu'], $post_genre_name);
                    } else {
                        $post_genre_name_arr = array($post_genre_name);
                    }




                    foreach ($movies as $movie) {
                        $genre_ids = array();
                        if (isset($facets[$movie->id]['genre']['data'])) {
                            $genre_data = $facets[$movie->id]['genre']['data'];
                            foreach ($genre_data as $item) {
                                $genre_ids[] = $item->id;
                            }
                        }

                        foreach ($post_genre_name_arr as $genre_name) {
                            $first = '';
                            $genre_name = trim($genre_name);
                            $found_genre = isset($all_genres[$genre_name]) ? $all_genres[$genre_name] : '';

                            if ($found_genre) {
                                $found_id = $found_genre->id;

                                if (in_array($found_id, $genre_ids)) {
                                    $results[$movie->id]['genres']['match'] += 1;
                                    $results[$movie->id]['genres']['rating'] += $genre_rule['ra'];
                                    $results[$movie->id]['genres']['data'][] = $genre_name;
                                    $results[$movie->id]['total']['match'] += 1;
                                    $results[$movie->id]['total']['rating'] += $genre_rule['ra'];
                                }
                            }
                        }
                    }
                }
            }
            if (is_array($search_fields['genres'])) {
                $search_fields['genres'] = implode('; ', $search_fields['genres']);
            }

            $link_poster = $o['link_poster'];
            // Poster
            if ($link_poster) {
                $poster_field = array('d' => $o['poster_field']);
                $dst_url = $this->get_post_field($poster_field, $post);
                $movies_ids = array();
                // TODO check movie img exists

                foreach ($movies as $movie) {
                    $movies_ids[] = $movie->id;
                }

                if ($dst_url) {
                    $post->poster = $dst_url;
                    $verdict = $this->pycv2_verdict($dst_url, $movies_ids);
                    if ($verdict) {
                        if ($verdict->error_msg) {
                            // TODO error log    
                        } else {
                            $verdict->results;
                            $poster_rules = $o['poster_rules'];
                            foreach ($movies as $movie) {
                                foreach ($poster_rules as $rule_name => $rule_opt) {
                                    if ($rule_opt['active']) {
                                        $movie_id = $movie->id;

                                        $rule_val = (int) isset($verdict->results->$rule_name->$movie_id) ? $verdict->results->$rule_name->$movie_id : -1;
                                        $rule_title = $this->poster_titles[$rule_name];
                                        $search_fields[$rule_title] = $rule_opt['match'];
                                        $results[$movie->id][$rule_title]['data'] = $rule_val;
                                        if ($rule_val >= $rule_opt['match']) {
                                            $results[$movie->id][$rule_title]['match'] = 1;
                                            $results[$movie->id][$rule_title]['rating'] = $rule_opt['rating'];

                                            $results[$movie->id]['total']['match'] += 1;
                                            $results[$movie->id]['total']['rating'] += $rule_opt['rating'];
                                        }
                                    }
                                }
                            }
                        }
                    } else {
                        // TODO error log
                    }
                }
            }
        }

        $max_rating = 0;
        $max_rating_id = 0;
        foreach ($results as $mid => $value) {

            $valid = $value['total']['match'] >= $min_match ? 1 : 0;
            if ($valid) {
                $valid = $value['total']['rating'] >= $min_rating ? 1 : 0;
            }
            if ($valid && $value['total']['rating'] > $max_rating) {
                $max_rating = $value['total']['rating'];
                $max_rating_id = $mid;
            }

            $results[$mid]['total']['valid'] = $valid;
            $results[$mid]['total']['top'] = 0;
        }
        if ($max_rating_id) {
            $results[$max_rating_id]['total']['top'] = 1;
        }
        /*
          print '<pre>';
          print_r($post);
          print_r($search_fields);
          print_r($results);
          print '</pre>';
         */

        return array('fields' => $search_fields, 'results' => $results);
    }

    public function check_link_actor_post($o, $post, $url_pid = 0) {
        $rules = $o['rules'];

        $min_match = $o['match'];
        $min_rating = $o['rating'];
        $movie_type = $this->movie_type[$o['type']];

        $results = array();
        $search_fields = array();
        $pid = $post->id;
        if ($rules && sizeof($rules)) {
            $rules_w = $this->sort_link_rules_by_weight($rules, 1);

            /*
             * Array ( [1] => Array ( 
             * [f] => t 
             * [t] => m 
             * [d] => t 
             * [m] => 
             * [r] => 10 
             * [c] => 
             * [w] => 0 
             * [a] => 1 )
             */

            //Find active rules
            foreach ($this->links_rules_actor_fields as $type => $title) {
                $i = 0;
                foreach ($rules_w as $key => $rule) {
                    if ($type == $rule['f']) {

                        if ($rule['a'] != 1) {
                            continue;
                        }

                        $type_key = $type;

                        if (!isset($active_rules[$type_key][$i])) {
                            $active_rules[$type_key][$i] = $rule;
                            $active_rules[$type_key][$i]['content'] = $this->use_reg_rule($rule, $this->get_post_field($rule, $post));
                        }

                        $i += 1;
                    }
                }
            }

            // Get first
            $post_first_name = '';
            $first_rule = '';
            if ($active_rules['f']) {
                foreach ($active_rules['f'] as $item) {
                    if ($item['content']) {
                        $post_first_name = $item['content'];
                        $first_rule = $item;
                        break;
                    }
                }
                $search_fields['firstname'] = $post_first_name;
            }

            // Get lastname
            $post_last_name = '';
            $last_rule = '';
            if ($active_rules['l']) {
                foreach ($active_rules['l'] as $item) {
                    if ($item['content']) {
                        $post_last_name = $item['content'];
                        $last_rule = $item;
                        break;
                    }
                }
                $search_fields['lastname'] = $post_last_name;
            }


            // Get full name
            $post_full_name = '';
            $full_rule = '';
            if ($active_rules['n']) {

                foreach ($active_rules['n'] as $item) {
                    if ($item['content']) {
                        $post_full_name = $item['content'];
                        $full_rule = $item;
                        break;
                    }
                }
                $search_fields['fullname'] = $post_full_name;
            }

            // Get burn name
            $post_burn_name = '';
            $burn_name_rule = '';
            if ($active_rules['b']) {
                foreach ($active_rules['b'] as $item) {
                    if ($item['content']) {
                        $post_burn_name = $item['content'];
                        $burn_name_rule = $item;
                        break;
                    }
                }
                $search_fields['burnname'] = $post_burn_name;
            }

            // Get burn year
            $post_year = '';
            $year_rule = '';
            if ($active_rules['y']) {
                foreach ($active_rules['y'] as $item) {
                    if ($item['content']) {
                        $post_year = $item['content'];
                        $year_rule = $item;
                        break;
                    }
                }
                $search_fields['year'] = $post_year;
            }


            // Get exist
            $post_exist_name = '';
            $exist_rule = '';
            if ($active_rules['e']) {
                foreach ($active_rules['e'] as $item) {
                    if ($item['content']) {
                        $post_exist_name = $item['content'];
                        $exist_rule = $item;
                        break;
                    }
                }
                $search_fields['exist'] = $post_exist_name;
            }


            $ma = $this->ml->get_ma();

            $actors = array();
            if ($url_pid > 0) {
                $actor = $ma->get_actor_by_id($url_pid);
                if ($actor) {
                    $actors[] = $actor;
                }
            } else {
                $actors_name = array();
                if ($post_first_name && $post_last_name) {
                    $actors_name = $ma->get_actors_normalize_by_name($post_first_name, $post_last_name);
                } else if ($post_first_name) {
                    $actors_name = $ma->get_actors_normalize_by_name($post_first_name, '');
                } else if ($post_last_name) {
                    $actors_name = $ma->get_actors_normalize_by_name('', $post_last_name);
                }
                if ($actors_name) {
                    $actors = array_merge($actors, $actors_name);
                }

                if ($post_full_name) {
                    $actors = array_merge($actors, $ma->get_actors_by_name($post_full_name));
                }
            }

            if ($actors) {
                $actors_unique = array();
                foreach ($actors as $actor) {
                    $actors_unique[$actor->aid] = $actor;
                }
                $actors = $actors_unique;

                /*
                 * [id] => 1000 
                 * [aid] => 13335727 
                 * [firstname] => Victor 
                 * [lastname] => Fehlberg                   
                 */
                foreach ($actors as $actor) {
                    if ($results[$actor->aid]) {
                        // already exists
                        continue;
                    }

                    // Actor              
                    if ($post_first_name) {
                        $results[$actor->aid]['firstname']['data'] = $actor->firstname;
                        $results[$actor->aid]['firstname']['match'] = 1;
                        $results[$actor->aid]['firstname']['rating'] = $first_rule['ra'];

                        $results[$actor->aid]['total']['match'] += 1;
                        $results[$actor->aid]['total']['rating'] += $first_rule['ra'];
                    }
                    // Actor              
                    if ($post_last_name) {
                        $results[$actor->aid]['lastname']['data'] = $actor->lastname;
                        $results[$actor->aid]['lastname']['match'] = 1;
                        $results[$actor->aid]['lastname']['rating'] = $last_rule['ra'];

                        $results[$actor->aid]['total']['match'] += 1;
                        $results[$actor->aid]['total']['rating'] += $last_rule['ra'];
                    }
                    // Exist              
                    if ($post_exist_name) {
                        $results[$actor->aid]['exist']['data'] = $post_exist_name;
                        $results[$actor->aid]['exist']['match'] = 1;
                        $results[$actor->aid]['exist']['rating'] = $exist_rule['ra'];

                        $results[$actor->aid]['total']['match'] += 1;
                        $results[$actor->aid]['total']['rating'] += $exist_rule['ra'];
                    }

                    // Full name
                    if ($post_full_name) {

                        $post_full_name_valid = false;
                        $actor_slug = $ma->create_slug($actor->name, ' ');
                        $name_slug = $ma->create_slug($post_full_name, ' ');
                        if ($full_rule['e'] == 'e') {


                            if ($actor_slug == $name_slug) {
                                $post_full_name_valid = true;
                            }
                        } else if ($full_rule['e'] == 'm') {
                            if (strstr($actor_slug, $name_slug)) {
                                $post_full_name_valid = true;
                            }
                        }

                        //p_r(array($actor->name,$post_full_name, $post_full_name_valid));

                        if ($post_full_name_valid) {
                            $results[$actor->aid]['fullname']['data'] = $actor->name;
                            $results[$actor->aid]['fullname']['match'] = 1;
                            $results[$actor->aid]['fullname']['rating'] = $full_rule['ra'];

                            $results[$actor->aid]['total']['match'] += 1;
                            $results[$actor->aid]['total']['rating'] += $full_rule['ra'];
                        }
                        if ($post_burn_name) {
                            if ($actor->birth_name == $post_burn_name) {
                                $results[$actor->aid]['burnname']['data'] = $actor->birth_name;
                                $results[$actor->aid]['burnname']['match'] = 1;
                                $results[$actor->aid]['burnname']['rating'] = $burn_name_rule['ra'];

                                $results[$actor->aid]['total']['match'] += 1;
                                $results[$actor->aid]['total']['rating'] += $burn_name_rule['ra'];
                            }
                        }

                        if ($post_year) {
                            $actor_year = preg_replace('/^.*([0-9]{4}).*$/', "$1", $actor->burn_date);
                            $content_year = preg_replace('/^.*([0-9]{4}).*$/', "$1", $post_year);
                            if ($actor_year && $actor_year == $content_year) {
                                $results[$actor->aid]['year']['data'] = $actor_year;
                                $results[$actor->aid]['year']['match'] = 1;
                                $results[$actor->aid]['year']['rating'] = $year_rule['ra'];
                                $results[$actor->aid]['total']['match'] += 1;
                                $results[$actor->aid]['total']['rating'] += $year_rule['ra'];
                            }
                        }
                    }
                }
            } else {
                return array();
            }
        }

        //p_r($results);

        $max_rating = 0;
        $max_rating_id = 0;
        foreach ($results as $mid => $value) {

            $valid = $value['total']['match'] >= $min_match ? 1 : 0;
            if ($valid) {
                $valid = $value['total']['rating'] >= $min_rating ? 1 : 0;
            }
            if ($valid && $value['total']['rating'] > $max_rating) {
                $max_rating = $value['total']['rating'];
                $max_rating_id = $mid;
            }

            $results[$mid]['total']['valid'] = $valid;
            $results[$mid]['total']['top'] = 0;
        }
        if ($max_rating_id) {
            $results[$max_rating_id]['total']['top'] = 1;
        }
        /*
          print '<pre>';
          print_r($post);
          print_r($search_fields);
          print_r($results);
          print '</pre>';
         */

        return array('fields' => $search_fields, 'results' => $results);
    }

    public function get_post_field($field, $post) {
        if ($field['d'] == 't') {
            return $post->title;
        } else if ($field['d'] === 'y') {
            return $post->year;
        } else if ($field['d'] === 'r') {
            return $post->rel;
        } else if ($field['d'] === 'm') {
            return $post->pid;
        } else {
            //Custom field
            $option_name = preg_replace('/^c-/', '', $field['d']);
            $post_options = unserialize($post->options);
            if (isset($post_options[$option_name]))
                return base64_decode($post_options[$option_name]);
        }
        return '';
    }

    public function sort_link_rules_by_weight($rules, $camp_type = 0) {
        $sort_rules = $rules;

        $links_rules_fields = $this->links_rules_fields;
        if ($camp_type == 1) {
            $links_rules_fields = $this->links_rules_actor_fields;
        }

        if ($rules) {
            $rules_w = array();
            foreach ($rules as $key => $value) {
                $rules_w[$key] = $value['w'];
            }
            asort($rules_w);
            $sort_rules = array();
            foreach ($links_rules_fields as $id => $item) {
                foreach ($rules_w as $key => $value) {
                    if ($rules[$key]['f'] == $id) {
                        $sort_rules[$key] = $this->get_valid_link_rule($rules[$key]);
                    }
                }
            }
        }
        return $sort_rules;
    }

    public function get_valid_link_rule($rule) {
        foreach ($this->def_link_rule as $key => $value) {
            if (!isset($rule[$key])) {
                $rule[$key] = $value;
            }
        }

        return $rule;
    }

    public function get_def_link_rule($key) {
        return $this->def_link_rule[$key];
    }

    public function find_posts_links($posts = array(), $o = array(), $camp_type = 0) {
        $ret = array();
        if (sizeof($posts)) {
            foreach ($posts as $post) {
                if ($camp_type == 1) {
                    // Actors   
                    $results = $this->check_link_actor_post($o, $post);
                } else {
                    // Movies
                    $results = $this->check_link_post($o, $post);
                }
                $results['post'] = $post;
                $ret[$post->id] = $results;
            }
        }
        return $ret;
    }

    /*
     * Create URLs rules
     */

    public function find_url_posts_links($posts = array(), $o = array(), $campaign_type = 0, $debug = false) {
        $ret = array();

        if (sizeof($posts)) {
            foreach ($posts as $uid => $data) {
                $ret[$uid] = array();
                $posts_arr = array();
                $url = $this->get_url($uid);
                if ($debug) {
                    print_r($url);
                    print_r($data);
                }

                foreach ($data as $k => $v) {
                    foreach ($v as $ck => $cv) {
                        if (!$posts_arr[$ck]) {
                            $posts_arr[$ck] = array();
                        }
                        $posts_arr[$ck][$k] = $cv;
                    }
                }

                if ($posts_arr) {
                    foreach ($posts_arr as $arr) {
                        $post = $this->create_post($arr);
                        if ($debug) {
                            print_r($post);
                        }

                        $url_pid = $url->pid ? $url->pid : -1;
                        if ($campaign_type == 1) {
                            $results = $this->check_link_actor_post($o, $post, $url_pid);
                        } else {
                            $results = $this->check_link_post($o, $post, $url_pid);
                        }
                        $results['post'] = $post;
                        $ret[$uid][] = $results;
                    }
                }
            }
        }

        return $ret;
    }

    public function create_post($item) {
        $post = new stdClass();

        $post_options = array();
        $title = '';
        $year = '';
        $release = '';
        $url = '';
        foreach ($item as $key => $value) {
            if ($key == 't') {
                $title = $value;
            } else if ($key == 'y') {
                $year = $value;
            } else if ($key == 'r') {
                $release = $value;
            } else if ($key == 'u') {
                $url = $value;
            } else {
                $post_options[$key] = base64_encode($value);
            }
        }

        $post->title = $title;
        $post->year = $year;
        $post->release = $year;
        $post->url = $url;
        $post->options = $post_options;

        return $post;
    }

    /*
     * Posts
     */

    public function add_post($data = array()) {
        $date = $this->curr_time();
        $data['date'] = $date;
        $data['last_upd'] = $date;
        $this->db_insert($data, $this->db['posts']);
    }

    public function update_post($data = array(), $id = 0) {
        $data['last_upd'] = $this->curr_time();
        $this->db_update($data, $this->db['posts'], $id);
    }

    public function max_len($text = '', $max_len = 250) {

        while (strlen($text) > $max_len) {
            $pos = strpos($text, ' ', $max_len);
            if ($pos != null) {
                $text = substr($text, 0, $pos);
            } else {
                $text = substr($text, 0, $max_len - 1);
            }
        }
        return $text;
    }

    public function update_post_status($uid, $status_links) {
        $date = $this->curr_time();

        $sql = sprintf("UPDATE {$this->db['posts']} SET    
                last_upd=%d,               
                status_links=%d                                              
                WHERE uid = %d", (int) $date, (int) $status_links, (int) $uid);

        $this->db_query($sql);
    }

    public function update_posts_status($ids = [], $status_links) {
        $date = $this->curr_time();

        $sql = sprintf("UPDATE {$this->db['posts']} SET    
                last_upd=%d,               
                status_links=%d                                              
                WHERE id IN(" . implode(',', $ids) . ")", (int) $date, (int) $status_links);

        $this->db_query($sql);
    }

    public function update_post_top_movie($uid, $status_links, $top_movie, $rating) {
        $date = $this->curr_time();

        $sql = sprintf("UPDATE {$this->db['posts']} SET    
                last_upd=%d,               
                status_links=%d,
                top_movie=%d, 
                rating=%d 
                WHERE uid = %d", (int) $date, (int) $status_links, (int) $top_movie, (int) $rating, (int) $uid);

        $this->db_query($sql);
    }

    public function get_post_by_id($id) {
        $sql = sprintf("SELECT * FROM {$this->db['posts']} WHERE id = %d", (int) $id);
        $result = $this->db_fetch_row($sql);
        return $result;
    }

    public function get_post_by_uid($uid) {
        $sql = sprintf("SELECT * FROM {$this->db['posts']} WHERE uid = %d AND multi=0", (int) $uid);
        $result = $this->db_fetch_row($sql);
        return $result;
    }

    public function get_multi_posts_by_uid($uid) {
        $sql = sprintf("SELECT * FROM {$this->db['posts']} WHERE uid = %d AND multi=1", (int) $uid);
        $result = $this->db_results($sql);
        return $result;
    }

    public function get_posts_by_top_movie($top_movie = 0) {
        $sql = sprintf("SELECT * FROM {$this->db['posts']} WHERE top_movie = %d", (int) $top_movie);
        $result = $this->db_results($sql);
        return $result;
    }

    public function get_posts_count($cid) {
        $sql = sprintf("SELECT COUNT(p.id) FROM {$this->db['posts']} p INNER JOIN {$this->db['url']} u ON u.id=p.uid WHERE u.cid=%d", $cid);
        $result = $this->db_get_var($sql);
        return $result;
    }

    public function get_posts_expired_error_links($cid, $interval_min = 0) {
        $time = $this->curr_time();
        $expired = $time - ($interval_min * 60);
        $sql = sprintf("SELECT p.id FROM {$this->db['posts']} p INNER JOIN {$this->db['url']} u ON u.id=p.uid WHERE u.cid=%d AND p.status_links=2 AND p.last_upd<%d", $cid, $expired);
        $result = $this->db_results($sql);
        $ids = array();
        if ($result) {
            foreach ($result as $post) {
                $ids[] = $post->id;
            }
        }
        return $ids;
    }

    public function save_all_posts($cid) {
        $sql = sprintf("SELECT p.id FROM {$this->db['posts']} p INNER JOIN {$this->db['url']} u ON u.id=p.uid WHERE u.cid=%d", $cid);
        $ids = $this->db_results($sql);
        print_r(count((array) $ids));
        if ($ids) {

            $full_path = $this->get_full_export_path($cid);

            if (file_exists($full_path)) {
                unlink($full_path);
            }



            foreach ($ids as $item) {
                $post = $this->get_post_by_id($item->id);
                $options = unserialize($post->options);
                $content = '';
                if (isset($options['content'])) {
                    $content = base64_decode($options['content']);
                    if ($content) {

                        $content_arr = array();
                        if (preg_match_all('/<div[^>]+>.*<\/div>/Us', $content, $matches)) {
                            foreach ($matches[0] as $row) {
                                $row = strip_tags($row);
                                $row = str_replace("\n", " ", $row);
                                $row = preg_replace('/[^a-zA-Z\']+/', ' ', $row);
                                $row = preg_replace('/  /', ' ', $row);
                                $count = count(explode(' ', $row));
                                $content_arr[] = $row;
                            }
                        }

                        $content = implode(PHP_EOL, $content_arr);
                    }
                }

                if ($content) {
                    file_put_contents($full_path, $content, FILE_APPEND | LOCK_EX);
                }
            }
        }
    }

    private function strip_content_lines($content) {
        $min_words = 6;
        $max_words = 18;
        $content = strip_tags($content);
        $content = str_replace("...", " ", $content);
        $content = str_replace("\n", " ", $content);
        $content = preg_replace('/(\.|;|\?|\!)/', "\n", $content);
        $content_arr = explode("\n", $content);
        $new_lines = '';
        $append = '';
        foreach ($content_arr as $pred) {
            $pred = preg_replace('/[^a-zA-Z\']+/', ' ', $pred);
            $pred = preg_replace('/  /', ' ', $pred);
            $pred = trim($pred);
            if ($pred) {
                // Min words
                $append .= ' ' . $pred;
            }
            $append = trim($append);
            $words = explode(" ", $append);
            $len = sizeof($words);
            if ($len > $min_words) {

                if ($len > $max_words) {
                    // Max words
                    $delim = $len;
                    $k = 2;
                    while ($delim > $max_words) {
                        $delim = ceil($delim / $k);
                        $k += 1;
                    }

                    $app = '';
                    $i = 0;
                    $append = '';
                    foreach ($words as $w) {
                        $i += 1;
                        $append .= ' ' . $w;

                        if ($i > $delim) {
                            $append = trim($append);
                            if ($append) {
                                $new_lines .= $append . PHP_EOL;
                            }
                            $append = '';
                            $i = 0;
                        }
                    }
                    $append = '';
                } else {
                    $new_lines .= $append . PHP_EOL;
                    $append = '';
                }
            }
        }
        $content = $new_lines;
        return $content;
    }

    public function get_full_export_path($cid) {
        $export_path = $this->ml->export_path;
        $cid_path = $export_path . $cid . '/';
        $this->check_and_create_dir($cid_path);
        $full_path = $cid_path . 'posts.txt';

        return $full_path;
    }

    public function get_post_options($post) {
        $o = $post->options;
        $ret = array();
        if ($o) {
            $ou = unserialize($o);
            if ($ou && sizeof($ou)) {
                foreach ($ou as $key => $value) {
                    $ret[$key] = base64_decode($value);
                }
            }
        }
        return $ret;
    }

    public function delete_post_by_url_id($uid) {
        $sql = sprintf("DELETE FROM {$this->db['posts']} WHERE uid=%d", (int) $uid);
        $this->db_query($sql);
    }

    public function delete_arhive_by_url_id($uid) {

        // Delete post
        $this->delete_post_by_url_id($uid);

        // Delete arhive
        $this->delete_arhive($uid);

        // Delete log
        // $this->delete_log($uid);
        // URL Status new
        $status = 0;
        $sql = sprintf("UPDATE {$this->db['url']} SET status=%d WHERE id=%d", $status, $uid);
        $this->db_query($sql);

        return true;
    }

    public function delete_url($uid) {
        $sql = sprintf("DELETE FROM {$this->db['url']} WHERE id=%d", (int) $uid);
        $this->db_query($sql);
    }

    public function validate_arhive_len($uid) {
        $valid = false;
        $arhive = $this->get_arhive_by_url_id($uid);
        $url = $this->get_url($uid);
        if ($url) {
            $content = $this->get_arhive_file($url->cid, $arhive->arhive_hash);
            $campaign = $this->get_campaign($url->cid, true);
            if ($campaign) {
                $options = $this->get_options($campaign);
                $type_name = 'arhive';
                $type_opt = $options[$type_name];
                $body_len = strlen($content);
                $valid = $this->validate_body_len($body_len, $type_opt['body_len']);
            }
        }
        if (!$valid) {
            $this->change_url_state($uid, 4);
        }
    }

    /*
     * Actors
     */

    public function add_post_actor_meta($aid, $pid, $cid) {
        $meta_exist = $this->get_post_actor_meta($aid, $pid, $cid, 1);
        if (!$meta_exist) {
            $sql = sprintf("INSERT INTO {$this->db['actors_meta']} (aid,pid,cid) VALUES (%d,%d,%d)", (int) $aid, (int) $pid, (int) $cid);
            $this->db_query($sql);
        }
    }

    public function get_post_actor_meta($aid = 0, $pid = 0, $cid = 0, $count = 0) {
        $and_aid = '';
        if ($aid > 0) {
            $and_aid = sprintf(' AND aid=%d', $aid);
        }
        $and_pid = '';
        if ($pid > 0) {
            $and_pid = sprintf(' AND pid=%d', $pid);
        }
        $and_cid = '';
        if ($cid > 0) {
            $and_cid = sprintf(' AND cid=%d', $cid);
        }

        $limit = '';
        if ($count > 0) {
            $limit = sprintf(' LIMIT %d', $count);
        }

        $sql = "SELECT aid, pid, cid FROM {$this->db['actors_meta']} WHERE id>0" . $and_cid . $and_aid . $and_pid . $limit;
        if ($count == 1) {
            $result = $this->db_fetch_row($sql);
        } else {
            $result = $this->db_results($sql);
        }
        return $result;
    }

    /*
     * Fchan posts
     */

    public function get_fchan_posts_rating($id = 0, $count = 10) {
        $sql = sprintf("SELECT id, mid, uid FROM {$this->db['fchan_posts']}"
                . " WHERE id>%d AND status=1 ORDER BY id ASC LIMIT %d", $id, $count);
        $results = $this->db_results($sql);
        return $results;
    }

    public function get_fchan_posts($uid = 0) {
        $sql = sprintf("SELECT rating FROM {$this->db['fchan_posts']} WHERE mid=%d AND status=1", $uid);
        $results = $this->db_results($sql);
        return $results;
    }

    public function get_fchan_posts_content($uid = 0) {
        $sql = sprintf("SELECT content FROM {$this->db['fchan_posts']} WHERE mid=%d AND status=1", $uid);
        $results = $this->db_results($sql);
        return $results;
    }

    public function get_fchan_posts_found($uid = 0) {
        $sql = sprintf("SELECT posts_found FROM {$this->db['fchan_log']} WHERE uid=%d", $uid);
        $result = $this->db_get_var($sql);
        return $result;
    }

    public function get_last_pids($last_id, $count) {
        $sql = sprintf("SELECT u.pid "
                . "FROM {$this->db['url']} u "
                . "INNER JOIN {$this->db['campaign']} c ON c.id = u.cid "
                . "WHERE c.type!=1 AND u.pid>%d GROUP BY u.pid ORDER BY u.pid ASC LIMIT %d", $last_id, $count);
        $pids = $this->db_results($sql);
        return $pids;
    }

    public function get_all_movie_urls_by_pid($pid) {
        $query = sprintf("SELECT u.id FROM {$this->db['url']} u "
                . "INNER JOIN {$this->db['campaign']} c ON c.id = u.cid "
                . "WHERE c.type!=1 AND u.pid=%d AND u.status!=2", $pid);
        $result = $this->db_results($query);
        return $result;
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
      0 => 'Other',
      1 => 'Find URLs',
      2 => 'Arhive',
      3 => 'Parsing',
      4 => 'Links',
     */

    public function log($message, $cid = 0, $uid = 0, $type = 0, $status = -1) {
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

        $query = "SELECT COUNT(id) FROM {$this->db['log']} WHERE id>0" . $and_cid . $and_status . $and_type;

        $result = $this->db_get_var($query);
        return $result;
    }

    public function last_log_result($url_id = 0, $parser_id = 0, $status = -1) {

        $and_uid = '';
        if ($url_id > 0) {
            $and_uid = sprintf(' AND uid=%d', $url_id);
        }

        $and_cid = '';
        if ($parser_id > 0) {
            $and_cid = sprintf(' AND cid=%d', $parser_id);
        }

        $and_status = '';
        if ($status != -1) {
            $and_status = sprintf(' AND status=%d', $status);
        }

        $query = sprintf("SELECT type, status, message FROM {$this->db['log']} WHERE id>0" . $and_uid . $and_cid . $and_status . " ORDER BY id DESC", $url_id);

        $result = $this->db_fetch_row($query);

        return $result;
    }

    public function log_info($message, $cid, $uid, $status) {
        $this->log($message, $cid, $uid, 0, $status);
    }

    public function log_warn($message, $cid, $uid, $status) {
        $this->log($message, $cid, $uid, 1, $status);
    }

    public function log_error($message, $cid, $uid, $status) {
        $this->log($message, $cid, $uid, 2, $status);
    }

    public function delete_log($uid) {
        $sql = sprintf("DELETE FROM {$this->db['log']} WHERE uid=%d", (int) $uid);
        $this->db_query($sql);
    }

    /*
     * Other functions
     */

    public function pycv2_verdict($url = '', $ids = array()) {
        $content = array();
        try {
            $url = PY_CV2_URL . "?p=" . PY_CV2_PASS . "&ids=" . implode(',', $ids) . "&url=" . $url;
            $content = json_decode(file_get_contents($url));
        } catch (Exception $exc) {
            
        }
        return $content;
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

    public function get_proxy($url, $proxy = '', &$header = '', $settings = '', $chd = array()) {

        $ch = curl_init();
        $ss = $settings ? $settings : array();
        $curl_user_agent = isset($ss['parser_user_agent']) ? $ss['parser_user_agent'] : '';

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_ENCODING, 'deflate');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
        curl_setopt($ch, CURLOPT_TIMEOUT, 25);
        if ($curl_user_agent) {
            curl_setopt($ch, CURLOPT_USERAGENT, $curl_user_agent);
        }
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        $cookie_path = isset($ss['parser_cookie_path']) ? $ss['parser_cookie_path'] : '';

        if ($cookie_path) {
            curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_path);
            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_path);
        }

        if ($chd) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $chd);
        }

        curl_setopt($ch, CURLINFO_HEADER_OUT, true); // enable tracking

        if ($proxy) {
            $proxy = '127.0.0.1:8118';
            curl_setopt($ch, CURLOPT_PROXY, $proxy);
        }

        $response = curl_exec($ch);

        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headerSent = curl_getinfo($ch, CURLINFO_HEADER_OUT); // request headers
        $header_responce = substr($response, 0, $header_size);

        $header = "RESPONCE:\n" . $header_responce . "\nREQUEST:\n" . $headerSent;
        $body = substr($response, $header_size);

        curl_close($ch);

        return $body;
    }

    public function get_webdriver($url, &$header = '', $settings = '', $use_driver = -1) {

        $webdrivers_text = base64_decode($settings['web_drivers']);
        //http://165.227.101.220:8110/?p=ds1bfgFe_23_KJDS-F&url=
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
        //$content = '<a href="/example.com"></a><a href=/example.com ></a><img src="/testimg"><img src=/testimg2.jpg >';    
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
}
