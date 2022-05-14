<?php

/**
 * Custom functions for familysearch.org parser
 *
 * @author brahman
 */
class Familysearch extends MoviesAbstractDBAn {

    private $ml = '';
    public $sort_pages = array('id', 'lastname', 'topcountryname');
    public $country_names = array(
        'Africa' => 'Central African Republic',
        'Asia' => 'Kazakhstan',
        'At Sea' => 'Greenland',
        'Austral Islands' => 'Australia',
        'Bailiwick of Guernsey' => 'Guernsey',
        'Bailiwick of Jersey' => 'Jersey',
        'Bosnia-Herzegovina' => 'Bosnia and Herzegovina',
        'Caribbean' => 'Caribbean Netherlands',
        'Channel Islands' => 'Jersey',
        'Curaçao and Dependencies' => 'Curaçao',
        'Côte d\'Ivoire' => 'Cote d Ivoire',
        'Danzig' => 'Poland',
        'Democratic Republic of the Congo' => 'DR Congo',
        'England' => 'United Kingdom',
        'Europe' => 'Germany',
        'Macedonia' => 'North Macedonia',
        'Mainland China' => 'China',
        'Marquesas Islands' => 'French Polynesia',
        'North Sea' => 'Greenland',
        'Pacific Ocean' => 'Papua New Guinea',
        'Saint Helena' => 'Saint Helena',
        'Scotland' => 'United Kingdom',
        'Swaziland' => 'South Africa',
        'Virgin Islands' => 'British Virgin Islands',
        'Wales' => 'United Kingdom',
        'West Indies' => 'Cuba',
        'World' => '',
    );
    public $race_small = array(
        'White' => 1,
        'Asian' => 2,
        'Latino' => 3,
        'Black' => 4,
        'Indian' => 5,
        'Arab' => 6,
        'Mixed / Other' => 7,
        'Jewish' => 8,
        'Indigenous' => 9,
    );

    //put your code here
    public function __construct($ml) {
        $this->ml = $ml ? $ml : new MoviesLinks();
        $this->db = array(
            'movie_imdb' => 'data_movie_imdb',
            'data_country' => 'data_movie_country',
            'meta_country' => 'meta_movie_country',
            'meta_actor' => 'meta_movie_actor',
            'meta_director' => 'meta_movie_director',
            'actors_normalize' => 'data_actors_normalize',
            'actors_imdb' => 'data_actors_imdb',
            'lastnames' => 'data_lastnames',
            'fs_country' => 'data_familysearch_country',
            'meta_fs' => 'meta_familysearch',
            'population' => 'data_population_country',
            'verdict' => 'data_familysearch_verdict',
        );
    }

    public function get_posts($page = 1, $orderby = '', $order = 'ASC', $perpage = 30) {
        // Get lastnames
        $page -= 1;
        $start = $page * $perpage;

        $limit = '';
        if ($perpage > 0) {
            $limit = " LIMIT $start, " . $perpage;
        }

        //Sort
        $and_orderby = '';
        if ($orderby && in_array($orderby, $this->sort_pages)) {
            $and_orderby = ' ORDER BY ' . $orderby;
            if ($order) {
                $and_orderby .= ' ' . $order;
            }
        } else {
            $and_orderby = " ORDER BY l.id DESC";
        }



        $query = "SELECT l.id, l.lastname, c.country as topcountryname"
                . " FROM {$this->db['lastnames']} l"
                . " INNER JOIN {$this->db['fs_country']} c ON c.id=l.topcountry"
                . $and_orderby . $limit;


        $result = $this->db_results($query);
        return $result;
    }

    public function get_posts_count() {
        $sql = "SELECT COUNT(id) FROM {$this->db['lastnames']}";
        $cnt = $this->db_get_var($sql);
        return $cnt;
    }

    public function get_lastname_id($name = '') {
        $sql = sprintf("SELECT id FROM {$this->db['lastnames']} WHERE lastname='%s' limit 1", $this->escape($name));
        $id = $this->db_get_var($sql);
        return $id;
    }

    public function create_lastname($name = '', $country = 0) {
        $sql = sprintf("INSERT INTO {$this->db['lastnames']} (lastname,topcountry) VALUES ('%s',%d)", $this->escape($name), $country);
        $this->db_query($sql);
        //Get the id
        $id = $this->getInsertId('id', $this->db['lastnames']);
        return $id;
    }

    public function add_country_meta($last_name_id = 0, $c_id = 0, $t = 0) {
        $sql = sprintf("INSERT INTO {$this->db['meta_fs']} (nid,cid,ccount) VALUES (%d,%d,%d)", $last_name_id, $c_id, $t);
        $this->db_query($sql);
    }

    public function get_or_create_country($name = '') {
        //Get from cache
        static $dict;
        if (is_null($dict)) {
            $dict = array();
        }

        if (isset($dict[$name])) {
            return $dict[$name];
        }

        //Get author id
        $sql = sprintf("SELECT id FROM {$this->db['fs_country']} WHERE country='%s'", $this->escape($name));
        $id = $this->db_get_var($sql);

        if (!$id) {
            $id = $this->create_country_by_name($name);
        }
        // Add to cache
        $dict[$name] = $id;

        return $id;
    }

    public function get_all_countries() {
        $sql = "SELECT country FROM {$this->db['fs_country']}";
        $result = $this->db_results($sql);
        $ret = array();
        if ($result) {
            foreach ($result as $value) {
                $ret[] = $value->country;
            }
        }
        asort($ret);
        return $ret;
    }

    public function get_countries_by_lasnameid($lastname_id) {
        $sql = sprintf("SELECT m.ccount, c.country FROM {$this->db['meta_fs']} m"
                . " INNER JOIN {$this->db['fs_country']} c ON c.id=m.cid"
                . " WHERE nid=%d", (int) $lastname_id);
        $results = $this->db_results($sql);
        $ret = array();
        if ($results) {
            foreach ($results as $item) {
                $ret[$item->country] = $item->ccount;
            }
        }
        arsort($ret);
        return $ret;
    }

    public function get_country_name($id) {
        static $dict;
        if (is_null($dict)) {
            $dict = array();
        }

        if (isset($dict[$id])) {
            return $dict[$id];
        }

        //Get author id
        $sql = sprintf("SELECT country FROM {$this->db['fs_country']} WHERE id=%d", (int) $id);
        $country = $this->db_get_var($sql);

        // Add to cache
        $dict[$id] = $country;

        return $country;
    }

    public function create_country_by_name($name) {
        $sql = sprintf("INSERT INTO {$this->db['fs_country']} (country) VALUES ('%s')", $this->escape($name));
        $this->db_query($sql);
        //Get the id
        $id = $this->getInsertId('id', $this->db['fs_country']);
        return $id;
    }

    public function get_country_races($country, $count, $use_simpson = false) {
        $population = $this->get_population();
        $ret = array();
        $country_name = $country;
        if (isset($this->country_names[$country])) {
            $country_name = $this->country_names[$country];
        }
        if ($country_name) {
            $simpson = 1;
            if ($use_simpson) {
                $simpson = $population[$country_name]['simpson'];                
            }
            
            if (isset($population[$country_name]['ethnic'])) {
                foreach ($population[$country_name]['ethnic'] as $race => $percent) {
                    $ret[$race] = round(($percent * $count * $simpson) / 100, 2);
                }
                return array('country' => $country_name, 'cca2' => $population[$country_name]['cca2'], 'races' => $ret, 'simpson' => $simpson);
            }
        }
        return array();
    }

    public function get_population() {
        static $population;
        if ($population) {
            return $population;
        }

        $ret = array();
        $sql = "SELECT country_name, cca2, ethnic_array_result, simpson FROM {$this->db['population']}";
        $results = $this->db_results($sql);
        if ($results) {
            foreach ($results as $item) {
                $ret[$item->country_name] = array('cca2' => $item->cca2, 'ethnic' => json_decode($item->ethnic_array_result), 'simpson' => $item->simpson);
            }
        }
        $population = $ret;
        return $population;
    }

    public function calculate_fs_verdict($name_id = 0, $simpson = false) {

        $countryes = $this->get_countries_by_lasnameid($name_id);

        $race_total = array();
        $rows_total = array();
        $rows_race = array();
        $rows_total_arr = array();
        $rows_race_arr = array();
        $total = 0;
        $verdict = 0;

        if ($countryes) {
            foreach ($countryes as $country => $country_count) {
                
                $rows_total_arr[addslashes($country)] = $country_count;
                $total += $country_count;
                $races_arr = $this->get_country_races($country, $country_count, $simpson);
                if ($races_arr) {
                    $race_str = array();
                    $race_str_arr = array();
                    foreach ($races_arr['races'] as $race => $count) {
                        if ($count > 0) {
                            $race_str[] = $race . ": " . $count;
                            $race_small = $this->race_small[$race];
                            $race_str_arr[$race_small] = $count;
                            $race_total[$race] += $count;
                        }
                    }
                    $rows_race[] = $races_arr['country'] . ': ' . implode(', ', $race_str);
                    $rows_race_arr[$races_arr['cca2']] = $race_str_arr;
                }
                
                $rows_total[] = $country . ': ' . $country_count.'<br /> - simpson: '.$races_arr['simpson'];
            }
            arsort($race_total);

            $verdict = array_keys($race_total)[0];
            $total_str = array();
            foreach ($race_total as $race => $cnt) {
                $total_str[] = $race . ': ' . $cnt;
            }
            $rows_total[] = 'Total: ' . $total;
            $rows_race[] = 'Total: ' . implode(', ', $total_str);
        }


        return array(
            'rows_total' => $rows_total,
            'rows_race' => $rows_race,
            'rows_race_arr' => $rows_race_arr,
            'rows_total_arr' => $rows_total_arr,
            'verdict' => $verdict,
        );
    }

    public function calculate_simpson($population = array()) {
        /*
          [cca2] => AI
          [ethnic] => stdClass Object
          (
          [Black] => 85.21
          [Latino] => 4.9
          [Mixed / Other] => 3.8
          [White] => 3.2
          [Indigenous] => 1.9
          [Indian] => 1
          )
         */
        $countries = array();
        foreach ($population as $country => $data) {
            $cca2 = isset($data['cca2']) ? $data['cca2'] : '';
            $ethnic = isset($data['ethnic']) ? $data['ethnic'] : array();
            $freq_total = 0;
            $index_total = 0;
            $simpson = 0.5;
            if ($ethnic) {
                foreach ($ethnic as $race => $cnt) {
                    $freq = $cnt * 100;
                    $index = $freq * ($freq - 1);
                    $freq_total += $freq;
                    $index_total += $index;
                }
                $simpson = round($index_total / ($freq_total * ($freq_total - 1)), 4);
            }
            // Insert simpson to db
            $this->insert_simpson($cca2, $simpson);
            $countries[$country] = array($cca2, $simpson, $ethnic);
        }
        return $countries;
    }

    public function insert_simpson($cca2, $simpson) {
        $sql = sprintf("UPDATE {$this->db['population']} SET simpson='%s' WHERE cca2 = '%s'", $simpson, $cca2);
        $this->db_query($sql);
    }

    /*
     * Cron actor vedrict
     */

    public function cron_actor_verdict($count = 100, $simpson=false, $debug = false) {

        // 1. Get lastnames
        $sql = sprintf("SELECT l.id, l.lastname, c.country as topcountryname"
                . " FROM {$this->db['lastnames']} l"
                . " INNER JOIN {$this->db['fs_country']} c ON c.id=l.topcountry"
                . " LEFT JOIN {$this->db['verdict']} v ON v.lastname=l.lastname"
                . " WHERE v.id is NULL ORDER BY l.id DESC LIMIT %d", (int) $count);
        $result = $this->db_results($sql);

        if ($debug) {
            print_r($result);
        }

        if (!$result) {
            return;
        }
        $commit_id = '';

        $array_update_family = [];

        foreach ($result as $item) {
            // 2. Calculate vedrict
            $verdict_arr = $this->calculate_fs_verdict($item->id, $simpson);
            if ($debug) {
                print_r($verdict_arr);
            }
            // 3. Add verdict to db
            $verdict_int = $this->race_small[$verdict_arr['verdict']] ? $this->race_small[$verdict_arr['verdict']] : 0;
            $last_upd = time();
            $lastname = $this->escape($item->lastname);
            $desc_arr = array('total' => $verdict_arr['rows_total_arr'], 'race' => $verdict_arr['rows_race_arr']);
            $desc = json_encode($desc_arr);
            $sql = sprintf("INSERT INTO {$this->db['verdict']} (last_upd,verdict,lastname,description) VALUES (%d,%d,'%s','%s')", $last_upd, $verdict_int, $lastname, $desc);

            $this->db_query($sql);

            // Get id
            $id = Pdo_an::last_id();

            if ($id) {
                $array_update_family[$id] = 1;
            }
        }
    }

    public function get_verdict_by_lastname($lastname) {
        $sql = sprintf("SELECT last_upd, verdict, lastname, description FROM {$this->db['verdict']} WHERE lastname='%s' LIMIT 1", $this->escape($lastname));
        $result = $this->db_fetch_row($sql);
        return $result;
    }

    public function get_verdict_name($int) {
        return array_search($int, $this->race_small);
    }

}
