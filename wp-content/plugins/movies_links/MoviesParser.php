<?php

class MoviesParser extends MoviesAbstractDB {

    private $ml = '';
    private $db = '';
    private $def_options = '';
    public $sort_pages = array('id', 'date', 'adate', 'pdate', 'title', 'last_update', 'update_interval', 'name', 'pid', 'status', 'type', 'weight');

    public function __construct($ml = '') {
        $this->ml = $ml ? $ml : new MoviesLinks();

        $this->db = array(
            'arhive' => 'movies_links_arhive',
            'campaign' => 'movies_links_campaign',
            'log' => 'movies_links_log',
            'posts' => 'movies_links_posts',
            'url' => 'movies_links_url',
            'actors_meta' => 'actors_meta',
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
                    'del_pea_int' => 1440,
                    'tor_h' => 20,
                    'tor_d' => 100,
                    'tor_mode' => 0,
                    'body_len' => 500,
                    'chd' => '',
                ),
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
                ),
            ),
        );
    }

    public $campaign_modules = array('cron_urls', 'gen_urls', 'arhive', 'parsing', 'links');
    public $log_modules = array(
        'cron_urls' => 1,
        'gen_urls' => 1,
        'arhive' => 2,
        'parsing' => 3,
        'links' => 4,
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
        't' => 'Title',
        'y' => 'Year',
        'c' => 'Custom',
    );
    public $parser_rules_actor_fields = array(
        't' => 'Title',
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
    );
    public $links_rules_actor_fields = array(
        'f' => 'Firstname',
        'l' => 'Lastname',
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
    private $movie_type = array(
        'a' => '',
        'm' => 'Movie',
        't' => 'TVSeries'
    );

    /*
     * Campaign
     */

    public function get_campaigns($status = -1, $type = -1, $page = 1, $orderby = '', $order = 'ASC', $perpage = 30) {
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



        $sql = sprintf("SELECT c.id, c.date, c.status, c.type, c.title, c.site, c.options "
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

    public function update_campaign($status, $title, $site, $type, $id) {
        $sql = sprintf("UPDATE {$this->db['campaign']} SET            
                status=%d,
                type=%d, 
                title='%s', 
                site='%s'                                  
                WHERE id = %d", $status, $type, $title, $site, $id
        );


        $this->db_query($sql);
    }

    public function add_campaing($status, $title, $site, $type = 0) {
        $date = $this->curr_time();
        $this->db_query(sprintf("INSERT INTO {$this->db['campaign']} (
                date, 
                status, 
                type,
                title,
                site                
                ) VALUES (
                %d,%d,%d,'%s','%s')"
                        . "", $date, $status, $type, $title, $site
        ));

        //Return id
        $id = $this->getInsertId('id', $this->db['campaign']);

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

        $query = "SELECT COUNT(*) FROM {$this->db['campaign']}" . $status_query . $type_query . $aid_and;
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

    public function add_url($cid, $link, $pid = 0, $weight = 0) {
        $link_hash = $this->link_hash($link);
        $url_exist = $this->get_url_by_hash($link_hash);

        if ($url_exist) {
            $epid = $url_exist->pid;
            if (!$epid && $pid) {
                // Update post pid
                $this->update_url_pid($url_exist->id, $pid);
            }
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

        // Status 'NEW'
        $status = 0;


        $sql = sprintf("INSERT INTO {$this->db['url']} (cid,pid,status,link_hash,link) "
                . "VALUES ('%d','%d','%d','%s','%s')", (int) $cid, (int) $pid, (int) $status, $link_hash, $this->escape($link));

        $this->db_query($sql);

        //Return id
        $id = $this->getInsertId('id', $this->db['url']);


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

    public function get_urls($status = -1, $page = 1, $cid = 0, $arhive_type = -1, $parser_type = -1, $links_type = -1, $orderby = '', $order = 'ASC', $perpage = 30, $date = '') {
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
        $parser_type_and = '';
        if ($parser_type != -1) {
            if ($parser_type == 1) {
                $parser_type_and = " AND p.id !=0 AND p.status=1";
            } else if ($parser_type == 2) {
                $parser_type_and = " AND p.id !=0 AND p.status=0";
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

        $query = "SELECT u.id, u.cid, u.pid, u.status, u.link_hash, u.link,"
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
        if ($arhive_type != -1) {
            if ($arhive_type == 1) {
                $arhive_type_and = " AND a.id !=0 ";
            } else {
                $arhive_type_and = " AND a.id is NULL";
            }
        }

        // Parser type filter
        $parser_type_and = '';
        if ($parser_type != -1) {
            if ($parser_type == 1) {
                $parser_type_and = " AND p.id !=0 AND p.status=1";
            } else if ($parser_type == 2) {
                $parser_type_and = " AND p.id !=0 AND p.status=0";
            } else {
                $parser_type_and = " AND p.id is NULL";
            }
        }

        $link_type_and = '';
        if ($link_type != -1) {
            $link_type_and = sprintf(" AND p.status_links=%d", $link_type);
        }

        $query = "SELECT COUNT(*) FROM {$this->db['url']} u"
                . " LEFT JOIN {$this->db['arhive']} a ON u.id = a.uid"
                . " LEFT JOIN {$this->db['posts']} p ON u.id = p.uid"
                . " WHERE u.id>0"
                . $status_query . $arhive_type_and . $parser_type_and . $link_type_and . $cid_and;

        $result = $this->db_get_var($query);
        return $result;
    }

    public function get_all_urls($cid) {
        $query = sprintf("SELECT * FROM {$this->db['url']} WHERE cid=%d AND status!=2", $cid);
        $result = $this->db_results($query);
        return $result;
    }

    public function get_last_urls($count = 10, $status = -1, $cid = 0, $random = 0) {
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
        if ($random == 0) {
            $query = sprintf("SELECT * FROM {$this->db['url']}" . $status_query . $cid_and . " ORDER BY id DESC LIMIT %d", $count);
            $result = $this->db_results($query);
        } else {
            // Get all urls
            $query = "SELECT id FROM {$this->db['url']}" . $status_query . $cid_and;
            $items = $this->db_results($query);
            if ($items) {
                $ids = array();
                foreach ($items as $item) {
                    $ids[] = $item->id;
                }
                shuffle($ids);
                $i = 0;
                $random_ids = array();
                foreach ($ids as $id) {
                    $random_ids[] = $id;
                    if ($i > $count) {
                        break;
                    }
                    $i += 1;
                }
                // Get random urls
                $query = "SELECT * FROM {$this->db['url']} WHERE id IN(" . implode(",", $random_ids) . ")";
                $result = $this->db_results($query);
            }
        }


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

    public function find_urls($campaign, $options, $settings, $preview = true) {
        $find_urls = $options['find_urls'];
        $service_urls = $options['service_urls'];

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
        $ret = array();
        $total_found = 0;
        if ($reg && $urls) {
            foreach ($urls as $url) {
                $url = htmlspecialchars_decode($url);

                $code = $this->get_code_by_current_driver($url, $headers, $settings, $service_urls);

                if ($code && preg_match_all($reg, $code, $match)) {
                    foreach ($match[1] as $u) {
                        if (preg_match('#^/#', $u)) {
                            //Short links
                            $domain = preg_replace('#^([^\/]+\:\/\/[^\/]+)(\/|\?|\#).*#', '$1', $url . '/');
                            $u = $domain . $u;
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

        $this->fund_urls_update_progress($campaign);

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
                $actors = $ma->get_actors($type, 10);
                $post = array_shift($actors);
            } else {
                // Movies
                //Get last URL to test            
                $posts = $ma->get_posts($type, $get_keys, 1);
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
            $posts = $ma->get_actors($type, $num, $last_id);
        } else {
            // Movies
            // Get all URLs
            $posts = $ma->get_posts($type, $get_keys, $num, $last_id);
        }

        if ($debug) {
            print_r(array($campaign->title, $last_id));
            print_r($posts);
        }

        if ($posts) {
            foreach ($posts as $post) {
                $post = $this->get_post_custom_fields($post);
                if ($post_last_id < $post->id) {
                    $post_last_id = $post->id;
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
                    $pid = 0;
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

        if (!$preview && $post_last_id) {
            $options_upd = array();
            $options_upd['gen_urls']['last_id'] = $post_last_id;
            $this->update_campaign_options($cid, $options_upd);
        }


        $message = 'Urls generated: ' . $total_gen;
        $this->log_info($message, $cid, 0, 1);

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

    public function get_arhive_file($cid, $link_hash) {
        $arhive_path = $this->ml->arhive_path;
        $first_letter = substr($link_hash, 0, 1);
        $cid_path = $arhive_path . $cid . '/';
        $first_letter_path = $cid_path . $first_letter . '/';
        $full_path = $first_letter_path . $link_hash;

        $gzcontent = file_get_contents($full_path);



        $content = '';
        if ($gzcontent) {
            $content = gzdecode($gzcontent);
        }


        return $content;
    }

    public function delete_arhive_file($cid, $link_hash) {
        $arhive_path = $this->ml->arhive_path;
        $first_letter = substr($link_hash, 0, 1);
        $cid_path = $arhive_path . $cid . '/';
        $first_letter_path = $cid_path . $first_letter . '/';
        $full_path = $first_letter_path . $link_hash;
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

        $valid_body_len = $this->validate_body_len($code, $type_opt['body_len']);
        $ret['content'] = $code;
        $ret['headers'] = $headers;
        $ret['headers_status'] = $this->get_header_status($headers);
        $ret['valid_body'] = $valid_body_len;
        return $ret;
    }

    public function get_code_by_current_driver($url, &$headers, $settings, $type_opt) {
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

    public function validate_body_len($code = '', $valid_len = 500) {
        $body_len = strlen($code);
        if ($body_len > $valid_len) {
            return true;
        }
        return false;
    }

    /*
     * Parsing rules
     */

    public function get_last_arhives_no_posts($count = 10, $cid = 0, $no_posts = true) {

        // Company id
        $cid_and = '';

        if ($cid > 0) {
            $cid_and = sprintf(" AND u.cid=%d", (int) $cid);
        }
        $np_and = '';
        if ($no_posts) {
            $np_and = ' AND p.uid is NULL';
        }

        $query = sprintf("SELECT a.uid, a.arhive_hash, u.cid FROM {$this->db['arhive']} a"
                . " INNER JOIN {$this->db['url']} u ON u.id = a.uid"
                . " LEFT JOIN {$this->db['posts']} p ON p.uid = a.uid"
                . " WHERE a.id>0 " . $np_and . $cid_and
                . " ORDER BY a.id DESC LIMIT %d", (int) $count);

        $result = $this->db_results($query);

        return $result;
    }

    public function parse_arhives($items, $campaign, $rules_name = 'rules') {
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
                    } else {
                        $rules_fields = array();
                        if ($campaign->type == 2) {
                            $rules_fields = $this->parser_urls_rules_fields;
                        }
                        $result = $this->check_reg_post($o, $rules_name, $code, $rules_fields);
                    }
                }
                $ret[$item->uid] = $result;
            }
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

    public function get_last_posts($count = 10, $cid = 0, $status_links = -1, $status = -1, $min_pid = 0, $order = "DESC") {

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

        $and_order = "DESC";
        if ($order == "ASC") {
            $and_order = $order;
        }

        $query = sprintf("SELECT p.*, u.pid FROM {$this->db['posts']} p"
                . " INNER JOIN {$this->db['url']} u ON p.uid = u.id"
                . " WHERE p.id>%d" . $cid_and . $status_and . $status_links_and
                . " ORDER BY p.id $and_order LIMIT %d", (int) $min_pid, (int) $count);


        $result = $this->db_results($query);

        return $result;
    }

    public function get_last_arhives($cid = 0, $start = 0, $count = 10, $top_movie = 0) {
        if ($cid > 0) {
            $cid_and = sprintf(" AND u.cid=%d", (int) $cid);
        }

        $and_top_movie = ' AND p.top_movie>0';
        if ($top_movie > 0) {
            $and_top_movie = sprintf(' AND p.top_movie=%d', $top_movie);
        }

        $query = sprintf("SELECT p.top_movie, a.arhive_hash FROM {$this->db['posts']} p"
                . " INNER JOIN {$this->db['url']} u ON p.uid = u.id"
                . " INNER JOIN {$this->db['arhive']} a ON a.uid = u.id"
                . " WHERE p.id>0" . $and_top_movie . $cid_and
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
            $post_title_name = '';
            $title_rule = '';
            if ($active_rules['t']) {
                foreach ($active_rules['t'] as $item) {
                    if ($item['content']) {
                        $post_title_name = $item['content'];
                        $title_rule = $item;
                        break;
                    }
                }
                $search_fields['title'] = $post_title_name;
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

            $ms = $this->ml->get_ms();
            $facets = array();
            if ($movie_id) {
                $movies = $ms->search_movies_by_id($movie_id);
                $movies_title = $ms->search_movies_by_title($post_title_name, $title_rule['e'], $post_year_name, 20, $movie_type);

                if (!isset($movies_title[$movie_id])) {
                    if ($movies[$movie_id]->title != $post_title_name) {
                        $post_title_name = '';
                    }
                }
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

                $movies_title = array();
                if ($post_title_name) {
                    // Find movies by title and year
                    $movies_title = $ms->search_movies_by_title($post_title_name, $title_rule['e'], $post_year_name, 20, $movie_type);
                }

                $movies = array_merge($movies_imdb, $movies_title);

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
                    //Movie              
                    if ($post_title_name) {
                        $results[$movie->id]['title']['data'] = $movie->title;
                        $results[$movie->id]['title']['match'] = 1;
                        $results[$movie->id]['title']['rating'] = $title_rule['ra'];

                        $results[$movie->id]['total']['match'] += 1;
                        $results[$movie->id]['total']['rating'] += $title_rule['ra'];
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
            if (is_array($search_fields['directors'])) {
                $search_fields['directors'] = implode('; ', $search_fields['directors']);
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

    public function check_link_actor_post($o, $post) {
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

            if ($post_first_name && $post_last_name) {
                $actors = $ma->get_actors_normalize_by_name($post_first_name, $post_last_name);
            } else if ($post_first_name) {
                $actors = $ma->get_actors_normalize_by_name($post_first_name, '');
            } else if ($post_last_name) {
                $actors = $ma->get_actors_normalize_by_name('', $post_last_name);
            }



            if ($actors) {
                /*
                 * [id] => 1000 
                 * [aid] => 13335727 
                 * [firstname] => Victor 
                 * [lastname] => Fehlberg                   
                 */
                foreach ($actors as $actor) {
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
                }
            } else {
                return array();
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

    public function find_url_posts_links($posts = array(), $o = array(), $debug = false) {
        $ret = array();

        if (sizeof($posts)) {
            foreach ($posts as $uid => $data) {
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
                        $results = $this->check_link_post($o, $post, $url->pid);
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

    public function add_post($uid, $status, $title, $release, $year, $options, $top_movie, $rating) {
        /*
         * `id` int(11) unsigned NOT NULL auto_increment,
          `date` int(11) NOT NULL DEFAULT '0',
          `uid` int(11) NOT NULL DEFAULT '0',
          `top_movie` int(11) NOT NULL DEFAULT '0',
          `rating` int(11) NOT NULL DEFAULT '0',
          `status` int(11) NOT NULL DEFAULT '0',
          `title` varchar(255) NOT NULL default '',
          `rel` varchar(255) NOT NULL default '',
          `year` int(11) NOT NULL DEFAULT '0',
          `options` text default NULL,
         */
        $date = $this->curr_time();
        $opt_str = serialize($options);
        $max_len = 250;


        while (strlen($title) > $max_len) {
            $pos = strpos($title, ' ', $max_len);
            if ($pos != null) {
                $title = substr($title, 0, $pos);
            } else {
                $title = substr($title, 0, $max_len - 1);
            }
        }


        $sql = sprintf("INSERT INTO {$this->db['posts']} (date,last_upd,uid,top_movie,rating,status,year,title,rel,options)"
                . " VALUES (%d,%d,%d,%d,%d,%d,%d,'%s','%s','%s')", (int) $date, (int) $date, (int) $uid, (int) $top_movie, (int) $rating, (int) $status, (int) $year, $this->escape($title), $this->escape($release), $opt_str);

        $this->db_query($sql);
    }

    public function update_post($uid, $status, $title, $release, $year, $options, $top_movie, $rating) {

        $date = $this->curr_time();
        $opt_str = serialize($options);

        $sql = sprintf("UPDATE {$this->db['posts']} SET            
                date=%d, 
                last_upd=%d, 
                top_movie=%d, 
                rating=%d, 
                status=%d, 
                year=%d, 
                title='%s', 
                rel='%s', 
                options='%s'                                  
                WHERE uid = %d", (int) $date, (int) $date, (int) $top_movie, (int) $rating, (int) $status, (int) $year, $this->escape($title), $this->escape($release), $opt_str, (int) $uid);

        $this->db_query($sql);
    }

    public function update_post_status($uid, $status_links) {
        $date = $this->curr_time();

        $sql = sprintf("UPDATE {$this->db['posts']} SET    
                last_upd=%d,               
                status_links=%d                                              
                WHERE uid = %d", (int) $date, (int) $status_links, (int) $uid);

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

    public function get_post_by_uid($uid) {
        $sql = sprintf("SELECT * FROM {$this->db['posts']} WHERE uid = %d", (int) $uid);
        $result = $this->db_fetch_row($sql);
        return $result;
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
                $valid = $this->validate_body_len($content, $type_opt['body_len']);
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

        $query = "SELECT COUNT(*) FROM {$this->db['log']} WHERE id>0" . $and_cid . $and_status . $and_type;

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

}
