<?php

/**
 * Get urls content from Tor proxy
 *
 * @author brahman
 */
class TorParser extends MoviesAbstractDB {

    private $ml;
    private $db;
    // public $web_driver = '148.251.54.53:8110';
    public $web_driver = '';
    public $get_ip_url = '';
    public $service_min_life_time = 300; // 5 min
    public $service_used_time = 300; // 5 min
    public $service_life_time = 3600; // 60 min
    public $min_valid_ips = 3;
    public $tor_reboot_dir = ABSPATH . 'wp-content/uploads/tor';
    public $ip_limit = array(
        'h' => 30,
        'd' => 1000,
    );
    public $sort_pages = array('date', 'id', 'ip', 'drivers', 'dst_url', 'user_agents', 'url_meta', 'last_upd', 'last_reboot', 'status');

    public function __construct($ml = '') {
        $this->ml = $ml ? $ml : new MoviesLinks();
        $this->db = array(
            'drivers' => 'tor_drivers',
            'ip' => 'tor_ip',
            'dst_url' => 'tor_dst_url',
            'user_agents' => 'tor_user_agents',
            'ip_meta' => 'tor_ip_meta',
            'log' => 'tor_log',
        );
        $ss = $this->ml->get_settings();
        $this->web_driver = $ss['tor_driver'];
        $this->get_ip_url = $ss['tor_get_ip_driver'];
        $this->ip_limit = array(
            'h' => $ss['tor_ip_h'],
            'd' => $ss['tor_ip_d'],
        );
    }

    public function run_cron($type = 0, $debug = false, $force = false) {
        // 1. Get services
        $services = $this->get_services(array(), 1, 0);
        if ($debug) {
            print_r($services);
        }

        $curr_time = $this->curr_time();

        if ($services) {
            foreach ($services as $service) {
                $status = $service->status;
                $type = $service->type;
                $last_reboot = $service->last_reboot;
                $last_upd = $service->last_upd;
                if ($status == 3) {
                    // Reboot status
                    $already_reboot = $this->service_is_reboot($service->id);
                    if (!$already_reboot || $type == 1) {
                        // Get IP
                        if ($debug) {
                            print "Get IP\n";
                        }
                        $this->update_service_ip($service->id, 'Auto', 3);
                    } else {
                        if ($debug) {
                            print "Wait reboot\n";
                        }
                    }
                } else if ($status == 1) {
                    $service_life_time = $curr_time - $last_reboot;
                    if ($service_life_time > $this->service_life_time) {
                        // Reboot old services
                        $message = 'Old ' . $service_life_time . ' > ' . $this->service_life_time;
                        $this->reboot_service($service->id, $message);
                        if ($debug) {
                            print "Reboot old service $service_life_time > " . $this->service_life_time . "\n";
                        }
                    } else {
                        if ($debug) {
                            print "Service active $service_life_time < " . $this->service_life_time . "\n";
                        }
                    }
                }
            }
        }
    }

    public function get_url_content($url = '', &$header, $ip_limit = array(), $curl = false, $debug = false) {
        $content = '';
        $get_url_data = $this->get_tor_url($url, $ip_limit, $log_data, $debug);
        $get_url = $get_url_data['url'];
        if ($get_url) {

            $service_id = $log_data['driver'];
            // Service used
            $date = $this->curr_time();
            $data_upd = array(
                'last_upd' => $date,
            );
            $this->update_service_field($data_upd, $service_id);

            if (!$curl) {
                // Webdriver
                $data = $this->curl($get_url, $header);
            } else {
                $user_agent = $get_url_data['agent'];
                $proxy = $get_url_data['proxy'];
                $data = $this->curl($url, $header, $user_agent, $proxy);
            }

            if ($debug) {
                print_r($header);
                print_r($data);
            }
            
            $status = $this->get_header_status($header);
            if ($debug) {
                print "Status: $status\n";
            }

            if ($debug) {
                print_r($log_data);
            }

            if ($status == 200 || $status == 301) {
                $content = $data;
                // Add log
                $message = 'Parser URL: ' . $status;
                $this->log_info($message, $log_data);
            } else {
                // Add log
                $message = 'Error parser URL: ' . $status;
                $this->log_error($message, $log_data);

                /* if ($status == 403) {
                  $message = 'Parsing error ' . $status;
                  $this->reboot_service($log_data['driver'], $message, false, $debug);
                  } */
            }
        }
        return $content;
    }

    private function get_tor_url($url = '', $ip_limit = array(), &$log_data = array(), $debug = false) {
        if (!$ip_limit) {
            $ip_limit = $this->ip_limit;
        }
        $ret = array(
            'url' => '',
            'agent' => '',
            'proxy' => '',
        );

        $curr_time = $this->curr_time();

        // 1. Get dst site id
        $site_name = $this->get_site_name($url);
        $site_id = $this->get_or_create_site_id($site_name);
        if ($debug) {
            print_r(array($site_name, $site_id));
        }
        // 2. Get active services
        $q_req = array(
            'status' => 1,
        );

        $services = $this->get_services($q_req, 1, 0);
        if ($debug) {
            print_r($services);
        }
        // 3. Get available ips
        $ips = array();
        $ips_error = array();
        $serv_arr = array();
        if ($services) {
            foreach ($services as $service) {
                if ($service->ip) {
                    $serv_arr[$service->id] = $service;
                    $ip_id = $service->ip;
                    $ip_name = $this->get_ip_name_by_id($ip_id);

                    // Get last hour count
                    $q_req = array(
                        'status' => 1,
                        'type' => 2,
                        'ip' => $ip_id,
                        'date_gt' => $curr_time - 3600
                    );

                    $ip_error_last_hour_count = $this->get_logs($q_req, 1, 0, 'date', 'DESC', true);
                    if ($ip_error_last_hour_count) {
                        // Get last error ips
                        $message = 'Last parsing error: ' . $ip_error_last_hour_count;

                        if ($service->type == 0 && ($curr_time - $this->service_used_time) > $service->last_upd) {
                            $ips_error[$service->last_reboot] = array(
                                'service' => $service->id,
                                'name' => $ip_name,
                                'ip_id' => $ip_id,
                                'message' => $message,
                            );
                        }
                    } else {
                        $q_req['type'] = 0;
                        // Valid ips
                        $ip_last_hour_count = $this->get_logs($q_req, 1, 0, 'date', 'DESC', true);
                        $ip_last_hour_limit_gen = rand($ip_limit['h'] - (int) ($ip_limit['h'] / 2), $ip_limit['h'] + (int) ($ip_limit['h'] / 2));

                        $q_req['date_gt'] = $curr_time - 86400;
                        $ip_last_day_count = $this->get_logs($q_req, 1, 0, 'date', 'DESC', true);
                        $ip_last_day_limit_gen = rand($ip_limit['d'] - (int) ($ip_limit['d'] / 2), $ip_limit['d'] + (int) ($ip_limit['d'] / 2));

                        $ips[$service->last_reboot] = array(
                            'service' => $service->id,
                            'name' => $ip_name,
                            'ip_id' => $ip_id,
                            'h' => $ip_last_hour_count,
                            'd' => $ip_last_day_count,
                            'hg' => $ip_last_hour_limit_gen,
                            'dg' => $ip_last_day_limit_gen,
                        );
                    }
                }
            }
        }
        if ($debug) {
            print "Ips available\n";
            print_r($ips);
        }



        // 4. Validate ips
        $ips_valid = array();
        $ips_on_limit = array();
        if ($ips) {
            foreach ($ips as $last_reboot => $item) {
                $service_id = $item['service'];
                $service = $serv_arr[$service_id];
                $hour = false;
                $day = false;
                if ($item['h'] > $item['hg']) {
                    $message = 'IP Hour limit: ' . $item['h'] . ' > ' . $item['hg'];
                    $hour = true;
                }
                if ($item['d'] > $item['dg']) {
                    $message = 'IP Day limit: ' . $item['d'] . ' > ' . $item['dg'];
                    $day = true;
                }

                if ($hour || $day) {
                    // Do not reboot a last update service
                    $item['message'] = $message;
                    if ($service->type == 0 && ($curr_time - $this->service_used_time) > $service->last_upd) {
                        $ips_on_limit[$last_reboot] = $item;
                    }
                    /* $this->reboot_service($service_id, $message, false, $debug);
                      if ($debug) {
                      print $message . "\n";
                      } */
                    continue;
                }

                $ips_valid[$last_reboot] = $item;
            }
        }

        krsort($ips_error);
        ksort($ips_on_limit);
        ksort($ips_valid);

        if ($debug) {
            print "Ips valid\n";
            print_r($ips_valid);
            print "Ips error\n";
            print_r($ips_error);
            print "Ips on limit\n";
            print_r($ips_on_limit);
        }

        if (!$ips_valid) {
            return '';
        }

        $ips_valid_count = sizeof($ips_valid);

        if ($ips_valid_count < $this->min_valid_ips) {
            $need_to_reboot = $this->min_valid_ips - $ips_valid_count;
            for ($i = 0; $i < $need_to_reboot; $i++) {
                // Reboot error ips
                if (sizeof($ips_error)) {
                    $to_reboot = array_pop($ips_error);
                    $message = $to_reboot['message'];
                    $this->reboot_service($to_reboot['service'], $message, false, $debug);
                } else if (sizeof($ips_on_limit)) {
                    $to_reboot = array_pop($ips_on_limit);
                    $message = $to_reboot['message'];
                    $this->reboot_service($to_reboot['service'], $message, false, $debug);
                }
            }
        }

        $service_ip = array();
        foreach ($ips_valid as $item) {
            $service_ip[$item['service']] = $item['ip_id'];
        }

        $ip_ids = array_values($service_ip);
        if ($debug) {
            print "Ips valid ids\n";
            print_r($ip_ids);
        }

        // 5. Get last ip_ids from log
        $sql = "SELECT ip FROM {$this->db['log']} WHERE status=1 AND type=0 AND IP IN(" . implode(',', $ip_ids) . ") ORDER BY date DESC LIMIT 1";
        $last_ip = $this->db_get_var($sql);
        if (!$last_ip) {
            // Random ip from ids
            // shuffle($ip_ids);
            // Get last ip
            $last_ip = current($ip_ids);
        }

        if ($debug) {
            print "Last valid ip $last_ip\n";
        }

        $service_id = array_search($last_ip, $service_ip);
        if (!$service_id) {
            $service_id = current(array_keys($service_ip));
        }

        // 5. Tor url
        $service = $serv_arr[$service_id];
        if ($debug) {
            print_r($service);
        }

        $get_url='';
        if ($service) {
            $agent = $this->get_agent_name_by_id($service->agent);
            $proxy = $service->url;
            $agent_encode = urlencode($agent);
            $url_encode = urlencode($url);
            $get_url = 'http://' . $this->web_driver . '/?p=ds1bfgFe_23_KJDS-F&proxy=' . $proxy . '&agent=' . $agent_encode . '&url=' . $url_encode;
            if ($debug) {
                print_r(array($url, $get_url));
            }

            $log_data = array(
                'driver' => $service->id,
                'ip' => $service->ip,
                'agent' => $service->agent,
                'url' => $site_id,
                'status' => 1,
                'dst_url' => $url,
            );

            $ret['url'] = $get_url;
            $ret['agent'] = $agent;
            $ret['proxy'] = $proxy;
        }

        return $ret;
    }

    /*
     * Tor services
     */

    public function get_service($id, $cache = false) {
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

        $sql = sprintf("SELECT * FROM {$this->db['drivers']} WHERE id = %d", $id);
        $result = $this->db_fetch_row($sql);
        if ($cache) {
            $dict[$id] = $result;
        }
        return $result;
    }

    public function get_services($q_req = array(), $page = 1, $perpage = 20, $orderby = '', $order = 'ASC', $count = false) {
        $q_def = array(
            'last_upd' => -1,
            'last_reboot' => -1,
            'status' => -1,
            'type' => -1,
            'ip' => -1,
            'agent' => -1,
            'name' => -1,
            'url' => -1,
        );

        $q = array();
        foreach ($q_def as $key => $value) {
            $q[$key] = isset($q_req[$key]) ? $q_req[$key] : $value;
        }

        // Custom status
        $status_trash = 2;
        $status_query = " WHERE p.status != " . $status_trash;
        if (is_array($q['status'])) {
            $status_query = " WHERE p.status IN (" . implode(',', $q['status']) . ")";
        } else if ($q['status'] != -1) {
            $status_query = " WHERE p.status = " . (int) $q['status'];
        }

        $type_query = '';
        if ($q['type'] != -1) {
            $type_query = " AND p.type = " . (int) $q['type'];
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
                $and_orderby = " ORDER BY p.id DESC";
            }

            $page -= 1;
            $start = $page * $perpage;

            if ($perpage > 0) {
                $limit = " LIMIT $start, " . $perpage;
            }

            $select = " p.*";
        } else {
            $select = " COUNT(*)";
        }

        $sql = "SELECT" . $select
                . " FROM {$this->db['drivers']} p"
                . $status_query . $type_query . $and_orderby . $limit;

        if (!$count) {
            $result = $this->db_results($sql);
        } else {
            $result = $this->db_get_var($sql);
        }
        return $result;
    }

    public function get_services_count($q_req = array()) {
        return $this->get_services($q_req, $page = 1, 1, '', '', true);
    }

    public function add_service($status, $name, $url, $type) {
        $data = array(
            'last_upd' => $this->curr_time(),
            'status' => $status,
            'type' => $type,
            'name' => $name,
            'url' => $url,
        );
        $this->db_insert($data, $this->db['drivers']);
        $id = $this->getInsertId('id', $this->db['drivers']);
        return $id;
    }

    public function update_service($status, $name, $url, $type, $id) {
        $data = array(
            'last_upd' => $this->curr_time(),
            'status' => $status,
            'type' => $type,
            'name' => $name,
            'url' => $url,
        );
        $this->db_update($data, $this->db['drivers'], $id);
    }

    public function update_service_field($data = array(), $id = 0) {
        if ($data && $id) {
            $this->db_update($data, $this->db['drivers'], $id);
        }
    }

    public function get_service_name_by_id($id = 0) {
        $val = '';
        if ($id) {
            $sql = "SELECT name FROM {$this->db['drivers']} WHERE id=?";
            $val = $this->db_get_var($sql, array($id));
        }
        return $val;
    }

    public function service_is_reboot($id) {
        $service = $this->get_service($id, true);
        $name = $service->name;
        $tor_path = $this->tor_reboot_dir . '/' . $name;
        if (file_exists($tor_path)) {
            return true;
        }
        return false;
    }

    public function reboot_service($id, $reboot_message = '', $force = false, $debug = false) {
        // 1. Add reboot hook
        $curr_time = $this->curr_time();
        $service = $this->get_service($id, true);

        if (!$force) {
            $last_reboot = $service->last_reboot;
            $service_life_time = $curr_time - $last_reboot;
            if ($service_life_time > $this->service_min_life_time) {
                // Hour limit. Reboot
                if ($debug) {
                    print "Reboot\n";
                }
            } else {
                if ($debug) {
                    print "No reboot\n";
                }
                return false;
            }
        }
        $type = $service->type;

        if ($type == 0) {
            // Tor logic

            $name = $service->name;
            $tor_path = $this->tor_reboot_dir . '/' . $name;

            if (!file_exists($this->tor_reboot_dir)) {
                mkdir($this->tor_reboot_dir, 0777, true);
            }

            if (file_exists($tor_path)) {
                return true;
            }

            // File not exist
            $time = $this->curr_time();
            file_put_contents($tor_path, $time);
        }

        // 2. Update service
        $date = $this->curr_time();
        $data = array(
            'last_upd' => $date,
            'last_reboot' => $date,
            'status' => 3,
            'ip' => 0,
            'agent' => 0,
        );

        $this->update_service_field($data, $id);

        // 3. Log
        $message = 'Reboot service';

        if ($reboot_message) {
            $message .= '. ' . $reboot_message;
        }

        $q_arr = array(
            'driver' => $id,
            'status' => 3,
        );
        $this->log_info($message, $q_arr);
        return true;
    }

    /*
     * Agents
     */

    public function get_agents($q_req = array(), $page = 1, $perpage = 20, $orderby = '', $order = 'ASC', $count = false) {
        $q_def = array(
            'ip' => -1,
            'random' => 0,
        );

        $q = array();

        foreach ($q_def as $key => $value) {
            $q[$key] = isset($q_req[$key]) ? $q_req[$key] : $value;
        }

        // IP status
        $and_ip = '';
        if ($q['ip'] != -1) {
            if ($q['ip'] == 1) {
                $and_ip = " AND m.agent IS NOT NULL";
            } else {
                $and_ip = " AND m.agent IS NULL";
            }
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
                $and_orderby = " ORDER BY p.id DESC";
            }

            if ($q['random']) {
                $and_orderby = " ORDER BY RAND()";
            }

            $page -= 1;
            $start = $page * $perpage;

            if ($perpage > 0) {
                $limit = " LIMIT $start, " . $perpage;
            }

            $select = " p.id, p.user_agent, ip.ip, m.date";
        } else {
            $select = " COUNT(*)";
        }

        $sql = "SELECT" . $select
                . " FROM {$this->db['user_agents']} p"
                . " LEFT JOIN {$this->db['ip_meta']} m ON p.id = m.agent"
                . " LEFT JOIN {$this->db['ip']} ip ON ip.id = m.ip"
                . " WHERE p.id>0" . $and_ip . $and_orderby . $limit;


        if (!$count) {
            $result = $this->db_results($sql);
        } else {
            $result = $this->db_get_var($sql);
        }
        return $result;
    }

    public function get_agents_count($q_req = array()) {
        return $this->get_agents($q_req, $page = 1, 1, '', '', true);
    }

    private function create_agent_id($name) {
        $data = array(
            'user_agent' => $name,
        );
        $this->db_insert($data, $this->db['user_agents']);
        $id = $this->getInsertId('id', $this->db['user_agents']);
        return $id;
    }

    private function get_agent_id_by_name($name) {
        $sql = "SELECT id FROM {$this->db['user_agents']} WHERE user_agent=?";
        $id = $this->db_get_var($sql, array($name));
        return $id;
    }

    public function add_agent_id($name) {
        $id = 0;

        if (!$this->get_agent_id_by_name($name)) {
            $id = $this->create_agent_id($name);
        }
        return $id;
    }

    public function get_or_create_ip_agent($ip_id) {
        // 1. Get ip meta
        $agent_id = $this->get_agent_id_by_ip($ip_id);
        if ($agent_id) {
            // Agent exist
            return $agent_id;
        }
        // 1. Get empty agents
        $q_req = array(
            'ip' => 0,
            'random' => 1
        );
        $agents = $this->get_agents($q_req, 1, 1);
        if ($agents) {
            $item = current($agents);
            $agent_id = $item->id;
            $this->add_agent_ip_id($agent_id, $ip_id);

            // Log
            $message = 'Add Agent to IP';
            $q_arr = array(
                'ip' => $ip_id,
                'agent' => $agent_id,
            );
            $this->log_info($message, $q_arr);

            return $agent_id;
        } else {
            // Remove old agents                                    
            $message = 'Need more agents. Remove old';
            $this->log_warn($message);

            $this->remove_old_agents(10);
            $agent_id = $this->get_or_create_ip_agent($ip_id);
            return $agent_id;
        }
    }

    public function get_agent_name_by_id($id = 0) {
        $val = '';
        if ($id) {
            $sql = "SELECT user_agent FROM {$this->db['user_agents']} WHERE id=?";
            $val = $this->db_get_var($sql, array($id));
        }
        return $val;
    }

    /*
     * Agent, Ip meta
     */

    public function get_ips($q_req = array(), $page = 1, $perpage = 20, $orderby = '', $order = 'ASC', $count = false) {
        $q_def = array(
            'agent' => -1,
        );

        $q = array();

        foreach ($q_def as $key => $value) {
            $q[$key] = isset($q_req[$key]) ? $q_req[$key] : $value;
        }

        // Agent status
        $and_ip = '';
        if ($q['agent'] != -1) {
            if ($q['agent'] == 1) {
                $and_ip = " AND m.ip IS NOT NULL";
            } else {
                $and_ip = " AND m.ip IS NULL";
            }
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
                $and_orderby = " ORDER BY ip.id DESC";
            }

            $page -= 1;
            $start = $page * $perpage;

            if ($perpage > 0) {
                $limit = " LIMIT $start, " . $perpage;
            }

            $select = " ip.id, ip.ip, p.user_agent, m.date";
        } else {
            $select = " COUNT(*)";
        }

        $sql = "SELECT" . $select
                . " FROM {$this->db['ip']} ip"
                . " LEFT JOIN {$this->db['ip_meta']} m ON ip.id = m.ip"
                . " LEFT JOIN {$this->db['user_agents']} p ON p.id = m.agent"
                . " WHERE ip.id>0" . $and_ip . $and_orderby . $limit;


        if (!$count) {
            $result = $this->db_results($sql);
        } else {
            $result = $this->db_get_var($sql);
        }
        return $result;
    }

    public function get_ips_count($q_req = array()) {
        return $this->get_ips($q_req, $page = 1, 1, '', '', true);
    }

    public function get_agent_id_by_ip($ip_id) {
        $sql = "SELECT agent FROM {$this->db['ip_meta']} WHERE ip=?";
        $id = $this->db_get_var($sql, array($ip_id));
        return $id;
    }

    private function add_agent_ip_id($agent_id, $ip_id) {
        $date = $this->curr_time();
        $data = array(
            'date' => $date,
            'agent' => $agent_id,
            'ip' => $ip_id,
        );
        $this->db_insert($data, $this->db['ip_meta']);
        $id = $this->getInsertId('id', $this->db['ip_meta']);
        return $id;
    }

    private function remove_old_agents($count = 10) {
        $sql = "DELETE FROM {$this->db['ip_meta']} ORDER BY date ASC LIMIT " . (int) $count;
        $this->db_query($sql);
    }

    /*
     * Ip
     */

    public function update_service_ip($id, $update_message = '', $error_status = 3) {
        // Get ip
        $ip_id = $this->get_tor_ip_id($id);
        $date = $this->curr_time();
        $data = array(
            'last_upd' => $date,
            'ip' => 0,
            'agent' => 0,
            'status' => $error_status,
        );
        $ret = false;
        if ($ip_id) {
            $ret = true;
            // Get user agent
            $agent_id = $this->get_or_create_ip_agent($ip_id);

            $data = array(
                'last_upd' => $date,
                'ip' => $ip_id,
                'agent' => $agent_id,
                'status' => 1,
            );

            // Log
            $message = 'Update IP';
            if ($update_message) {
                $message .= '. ' . $update_message;
            }
            $q_arr = array(
                'driver' => $id,
                'status' => 2,
                'ip' => $ip_id,
                'agent' => $agent_id,
            );
            $this->log_info($message, $q_arr);
        } else {
            // Error           

            $message = 'Can not get IP';
            if ($update_message) {
                $message .= '. ' . $update_message;
            }
            $q_arr = array(
                'driver' => $id,
                'status' => 2,
            );
            $this->log_error($message, $q_arr);
        }
        $this->update_service_field($data, $id);
        return $ret;
    }

    public function get_tor_ip_id($cid = 0) {
        $ip_id = 0;
        $ip = $this->get_tor_ip($cid);
        if ($ip) {
            $ip_id = $this->get_or_create_ip_id($ip);
        }
        return $ip_id;
    }

    public function get_tor_ip($id) {
        $service = $this->get_service($id, true);
        $proxy = $service->url;
        $get_url = 'http://' . $this->web_driver . '/?p=ds1bfgFe_23_KJDS-F&nodriver=1&proxy=' . $proxy . '&url=http://' . $this->get_ip_url . '/?getip=1';
        $content = file_get_contents($get_url);
        $ip = '';
        if (preg_match('/([0-9]+\.[0-9]+\.[0-9]+\.[0-9]+)/', $content, $match)) {
            $ip = $match[1];
        }
        return $ip;
    }

    public function get_ip_name_by_id($id = 0) {
        $val = '';
        if ($id) {
            $sql = "SELECT ip FROM {$this->db['ip']} WHERE id=?";
            $val = $this->db_get_var($sql, array($id));
        }
        return $val;
    }

    private function get_or_create_ip_id($name) {
        $id = $this->get_ip_id_by_name($name);
        if (!$id) {
            $id = $this->create_ip_id($name);
        }
        return $id;
    }

    private function create_ip_id($name) {
        $data = array(
            'ip' => $name,
        );
        $this->db_insert($data, $this->db['ip']);
        $id = $this->getInsertId('id', $this->db['ip']);
        return $id;
    }

    private function get_ip_id_by_name($name) {
        $sql = "SELECT id FROM {$this->db['ip']} WHERE ip=?";
        $id = $this->db_get_var($sql, array($name));
        return $id;
    }

    /*
     * Site
     */

    public function get_site_name_by_id($id = 0) {
        $val = '';
        if ($id) {
            $sql = "SELECT url FROM {$this->db['dst_url']} WHERE id=?";
            $val = $this->db_get_var($sql, array($id));
        }
        return $val;
    }

    private function get_or_create_site_id($name) {
        $site_id = $this->get_site_id_by_url_name($name);

        if (!$site_id) {
            $site_id = $this->create_site_id_by_url_name($name);
        }
        return $site_id;
    }

    private function create_site_id_by_url_name($name) {
        $data = array(
            'url' => $name,
        );
        $this->db_insert($data, $this->db['dst_url']);
        $id = $this->getInsertId('id', $this->db['dst_url']);
        return $id;
    }

    private function get_site_id_by_url_name($name) {
        $sql = "SELECT id FROM {$this->db['dst_url']} WHERE url=?";
        $id = $this->db_get_var($sql, array($name));
        return $id;
    }

    private function get_site_name($url) {
        $site = $url;
        if (preg_match('#^([^/]*//[^/]+)/#', $url, $match)) {
            $site = $match[1];
        }
        return $site;
    }

    /*
     * Log
     */

    public function get_logs($q_req = array(), $page = 1, $perpage = 20, $orderby = '', $order = 'ASC', $count = false) {
        $q_def = array(
            'type' => -1,
            'status' => -1,
            'driver' => -1,
            'ip' => -1,
            'date_gt' => -1,
            'date_lt' => -1,
        );

        $q = array();

        foreach ($q_def as $key => $value) {
            $q[$key] = isset($q_req[$key]) ? $q_req[$key] : $value;
        }

        // Type
        $and_type = '';
        if ($q['type'] != -1) {
            $and_type = " AND l.type = " . (int) $q['type'];
        }

        // Status
        $and_status = '';
        if ($q['status'] != -1) {
            $and_status = " AND l.status = " . (int) $q['status'];
        }

        // IP
        $and_ip = '';
        if ($q['ip'] != -1) {
            $and_ip = " AND l.ip = " . (int) $q['ip'];
        }

        // Date
        $and_date_gt = '';
        if ($q['date_gt'] != -1) {
            $and_date_gt = " AND l.date > " . (int) $q['date_gt'];
        }

        $and_date_lt = '';
        if ($q['date_lt'] != -1) {
            $and_date_lt = " AND l.date < " . (int) $q['date_lt'];
        }

        // Driver status
        $and_driver = '';
        if ($q['driver'] != -1) {
            $and_driver = " AND l.driver = " . (int) $q['driver'];
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
                $and_orderby = " ORDER BY l.id DESC";
            }

            $page -= 1;
            $start = $page * $perpage;

            if ($perpage > 0) {
                $limit = " LIMIT $start, " . $perpage;
            }

            $select = " l.*";
        } else {
            $select = " COUNT(*)";
        }

        $sql = "SELECT" . $select
                . " FROM {$this->db['log']} l"
                . " WHERE l.id>0" . $and_type . $and_status . $and_ip . $and_driver . $and_date_gt . $and_date_lt . $and_orderby . $limit;


        if (!$count) {
            $result = $this->db_results($sql);
        } else {
            $result = $this->db_get_var($sql);
        }
        return $result;
    }

    public function get_logs_count($q_req = array()) {
        return $this->get_logs($q_req, $page = 1, 1, '', '', true);
    }

    public function log($message = '', $type = 0, $q_arr = array()) {
        /*
         * type:
          0 => 'Info',
          1 => 'Warning',
          2 => 'Error'

          status:
          0 => 'Other',


          `id` int(11) unsigned NOT NULL auto_increment,
          `date` int(11) NOT NULL DEFAULT '0',
          `driver` int(11) NOT NULL DEFAULT '0',
          `ip` int(11) NOT NULL DEFAULT '0',
          `agent` int(11) NOT NULL DEFAULT '0',
          `url` int(11) NOT NULL DEFAULT '0',
          `type` int(11) NOT NULL DEFAULT '0',
          `status` int(11) NOT NULL DEFAULT '0',
          `message` varchar(255) NOT NULL default '',
          `dst_url` text default NULL,
         */
        $time = $this->curr_time();
        $data = array(
            'date' => $time,
            'message' => $message,
            'type' => $type,
        );
        if ($q_arr) {
            foreach ($q_arr as $key => $value) {
                $data[$key] = $value;
            }
        }
        $this->db_insert($data, $this->db['log']);
    }

    public function log_info($message, $q_arr) {
        $this->log($message, 0, $q_arr);
    }

    public function log_warn($message, $q_arr) {
        $this->log($message, 1, $q_arr);
    }

    public function log_error($message, $q_arr) {
        $this->log($message, 2, $q_arr);
    }

    public function curl($url, &$header = '', $curl_user_agent = '', $proxy = '') {
        $ss = $settings ? $settings : array();
        if (!$curl_user_agent) {
            $curl_user_agent = isset($ss['parser_user_agent']) ? $ss['parser_user_agent'] : '';
        }

        $ch = curl_init();

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

        $cookie_path = isset($ss['parser_cookie_path']) ? $ss['parser_cookie_path'] : '';

        if ($cookie_path) {
            curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_path);
            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_path);
        }
        curl_setopt($ch, CURLINFO_HEADER_OUT, true); // enable tracking
        // No cache
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Cache-Control: no-cache"));
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, TRUE);

        if ($proxy) {
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
    
    public function get_header_status($headers) {
        $status = 200;
        
        if ($headers) {
            if (preg_match_all('/HTTP[\/0-9\.]+*[^\d]+([0-9]{3})/', $headers, $match)) {
                $status = $match[1][(sizeof($match[1]) - 1)];
            }
        }
        return $status;
    }
}
