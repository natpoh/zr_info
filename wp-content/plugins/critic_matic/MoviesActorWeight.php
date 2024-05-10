<?php

/**
 * Description of MoviesActorWeight
 *
 * @author brahman
 */
class MoviesActorWeight extends AbstractDB {

    public $cm;

    public function __construct($cm = '') {
        $this->cm = $cm ? $cm : new CriticMatic();
        $this->db = array(
            'weight' => 'meta_actor_weight',
        );
    }

    public function run_cron($type = 0, $search_limit = 100, $debug = false, $force = false) {
        // Get options data      
        if ($type == 0) {
            $group_field = 'actor_star';
            $url_field = 'actors_star_ss';
            $wait_fied = 'actors_star_wait';
            $options = new MoviesActorStarOptions();
        } else if ($type == 1) {
            $group_field = 'actor_main';
            $url_field = 'actors_main_ss';
            $wait_fied = 'actors_main_wait';
            $options = new MoviesActorMainOptions();
        }
        $ss = $this->cm->get_settings();

        $url = $ss[$url_field];
        $wait_interval = (int) $ss[$wait_fied];
        $curr_time = $this->curr_time();

        if ($debug) {
            print_r(array(
                $url,
                $type,
                $wait_interval,
                $options->to_array()
            ));
        }

        // Check wait
        $last_done = $options->last_done;
        if ($last_done) {
            $next_step = $last_done + $wait_interval * 30;
            if ($curr_time < $next_step) {
                if ($debug) {
                    print "Wait {$curr_time} < {$next_step}\n";
                }
                return false;
            }
        }

        $page = $options->page;
        $version = $options->version;
        $result = $this->search_results($url, $page, $search_limit, $group_field);
        /*
          [list] =>  ([aid] => 41003, [cnt] => 25)
          [count] => 20425
         */
        if ($debug) {
            print_r($result);
        }
        if ($result['list']) {
            foreach ($result['list'] as $item) {
                $this->update_item($item->aid, $item->cnt, $type, $version, $debug);
            }
            // Add next page            
            $options->page += 1;
            $options->update();
        } else {
            // Add last done
            $options->page = 1;
            $options->last_done = $curr_time;
            $options->update();
            // Remove old versions
            if ($type == 0) {
                $this->remove_old_stars($version);
            } else {
                $this->remove_old_main($version);
            }
        }
    }

    public function get_statistics($type = 0) {
        // Get options data      
        if ($type == 0) {
            $group_field = 'actor_star';
            $url_field = 'actors_star_ss';
            $wait_fied = 'actors_star_wait';
            $options = new MoviesActorStarOptions();
        } else if ($type == 1) {
            $group_field = 'actor_main';
            $url_field = 'actors_main_ss';
            $wait_fied = 'actors_main_wait';
            $options = new MoviesActorMainOptions();
        }
        $ss = $this->cm->get_settings();

        $url = $ss[$url_field];
        $wait_interval = (int) $ss[$wait_fied];
        $curr_time = $this->curr_time();

        // Check wait
        $status = 'In progress';
        $last_done = $options->last_done;
        $next_step = 0;
        if ($last_done) {
            $next_step = $last_done + $wait_interval * 30;
            if ($curr_time < $next_step) {
                $status = 'Waiting';
            }
        }
        // Search result
        $result = $this->search_results($url, 1, 10, $group_field);
        $found_count = 0;
        if ($result) {
            $found_count = $result['count'];
        }
        $page = $options->page;
        $version = $options->version;
        // Found in DB
        if ($type==0){
            $items_count = $this->get_stars_count($version);
        } else {
            $items_count = $this->get_main_count($version);
        }
        $progress = round(($items_count/$found_count)*100,2);

        $ret = array(
            'Status' => $status,
            'Found in search' => $found_count,
            'DB count' => $items_count,
            'Progress'=>$progress.'%',
            'Version' => $this->curr_date($version),
            'Current page' => $page,
            'Last done' => $last_done ? $this->curr_date($last_done) : '-',
            'Next step' => $next_step ? $this->curr_date($next_step) : '-',
        );
        return $ret;
    }

    private function get_stars_count($version = 0) {
        $sql = sprintf("SELECT COUNT(*) FROM {$this->db['weight']} WHERE star_ver=%d", $version);
        return $this->db_get_var($sql);
    }

    private function get_main_count($version = 0) {
        $sql = sprintf("SELECT COUNT(*) FROM {$this->db['weight']} WHERE main_ver=%d", $version);
        return $this->db_get_var($sql);
    }

    private function remove_old_stars($version) {
        // Remove only stars
        $sql = sprintf("DELETE FROM {$this->db['weight']} WHERE star_ver=%d AND main_ver=0", (int) $version);
        $this->db_query($sql);
    }

    private function remove_old_main($version) {
        // Remove only main
        $sql = sprintf("DELETE FROM {$this->db['weight']} WHERE main_ver=%d AND star_ver=0", (int) $version);
        $this->db_query($sql);
    }

    public function update_item($aid = 0, $weight = 0, $type = 0, $version = 0, $debug = false) {
        // Get item
        $sql = sprintf("SELECT * FROM {$this->db['weight']} WHERE aid=%d", $aid);
        $item = $this->db_fetch_row($sql);
        if ($item) {
            // Update
            if ($type == 0) {
                $this->update_star($item, $weight, $version, $debug);
            } else {
                $this->update_main($item, $weight, $version, $debug);
            }
        } else {
            // Insert
            if ($type == 0) {
                $this->insert_star($aid, $weight, $version, $debug);
            } else {
                $this->insert_main($aid, $weight, $version, $debug);
            }
        }
    }

    private function insert_star($aid = 0, $weight = 0, $version = 0, $debug = false) {
        $total_weight = $weight * 10;
        $data = array(
            'aid' => $aid,
            'star_weight' => $weight,
            'star_date' => $this->curr_time(),
            'star_ver' => $version,
            'total_weight' => $total_weight
        );
        if ($debug) {
            print_r(array('insert_star', $data));
        }
        $this->db_insert($data, $this->db['weight']);
    }

    private function insert_main($aid = 0, $weight = 0, $version = 0, $debug = false) {
        $total_weight = $weight;
        $data = array(
            'aid' => $aid,
            'main_weight' => $weight,
            'main_date' => $this->curr_time(),
            'main_ver' => $version,
            'total_weight' => $total_weight
        );
        if ($debug) {
            print_r(array('insert_main', $data));
        }
        $this->db_insert($data, $this->db['weight']);
    }

    private function update_star($item, $weight = 0, $version = 0, $debug = false) {
        $data = array(
            'star_weight' => $weight,
            'star_date' => $this->curr_time(),
            'star_ver' => $version,
        );
        $total_weight = $weight * 10;
        if ($item->main_weight) {
            $total_weight += $item->main_weight;
        }
        $data['total_weight'] = $total_weight;
        if ($debug) {
            print_r(array('update_star', $data));
        }
        $this->db_update($data, $this->db['weight'], $item->id);
    }

    private function update_main($item, $weight = 0, $version = 0, $debug = false) {
        $data = array(
            'main_weight' => $weight,
            'main_date' => $this->curr_time(),
            'main_ver' => $version,
        );
        $total_weight = $weight;
        if ($item->star_weight) {
            $total_weight += $item->star_weight;
        }
        $data['total_weight'] = $total_weight;
        if ($debug) {
            print_r(array('update_main', $data));
        }
        $this->db_update($data, $this->db['weight'], $item->id);
    }

    public function search_results($url, $page, $search_limit, $group_field) {
        // Search logic
        $last_req = $_SERVER['REQUEST_URI'];
        $_SERVER['REQUEST_URI'] = $url;

        $search_front = new CriticFront();
        $search_front->init_search_filters();

        $start = 0;
        if ($page > 1) {
            $start = ($page - 1) * $search_limit;
        }
        $filters = $search_front->get_search_filters();

        $result = $search_front->cs->front_search_actors_list($search_front->keywords, $search_limit, $start, $group_field, $filters);

        $_SERVER['REQUEST_URI'] = $last_req;
        return $result;
    }
}

class MoviesActorStarOptions extends AbstractDB {

    public $version = 0;
    public $page = 1;
    public $last_done = 0;
    public $option_name = 'actors_star_options';

    public function __construct() {
        $options = $this->get_option($this->option_name);
        $this->version = isset($options['version']) ? $options['version'] : 0;
        $this->page = isset($options['page']) ? $options['page'] : 1;
        $this->last_done = isset($options['last_done']) ? $options['last_done'] : 0;
    }

    public function reset() {
        $this->version = $this->curr_time();
        $this->page = 1;
        $this->last_done = 0;
        $this->update_option($this->option_name, $this->to_array());
    }

    public function update() {
        $this->update_option($this->option_name, $this->to_array());
    }

    public function to_array() {
        $options = array(
            'version' => $this->version,
            'page' => $this->page,
            'last_done' => $this->last_done,
        );
        return $options;
    }
}

class MoviesActorMainOptions extends MoviesActorStarOptions {

    public $option_name = 'actors_main_options';
}
