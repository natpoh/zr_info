<?php

/**
 * Custom functions for forebears.io parser
 *
 * @author brahman
 */
class Forebears extends MoviesAbstractDBAn {

    private $ml = '';
    public $sort_pages = array('id', 'lastname', 'topcountryname', 'topcountryrank');
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
        'Abkhazia' => 'Russia',
        'Akrotiri and Dhekelia' => 'United Kingdom',
        'Congo' => 'Republic of the Congo',
        'CuraÃ§ao' => 'Netherlands',
        'East Timor' => 'Timor-Leste',
        'Ivory Coast' => 'Central African Republic',
        'North Korea' => 'Korea',
        'Northern Cyprus' => 'Cyprus',
        'Northern Ireland' => 'Ireland',
        'Saint Barthelemy' => 'Saint Barthélemy',
        'Somaliland' => 'Somalia',
        'South Ossetia' => 'Georgia',
        'Transnistria' => 'Moldova',
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
            'lastnames' => 'data_forebears_lastnames',
            'country' => 'data_forebears_country',
            'meta_fb' => 'meta_forebears',
            'population' => 'data_population_country',
            'verdict' => 'data_forebears_verdict',
        );
    }

    public function hook_update_post($campaign = array(), $post = array(), $options = array(), $debug = false) {
        $score_opt = array(
            'topcountry' => 'topcountry',
            'country' => 'country'
        );

        $to_update = array();
        foreach ($score_opt as $post_key => $db_key) {
            if (isset($options[$post_key])) {
                $field_value = base64_decode($options[$post_key]);
                $to_update[$db_key] = $field_value;
            }
        }
        $topcountry = '';
        $top_rank = 0;
        $top_country_rank = '';
        $country = '';
        if ($to_update) {
            $country_meta = array();
            if ($to_update['topcountry']) {
                $topcountry = trim($to_update['topcountry']);
            }

            if ($to_update['country'] && $topcountry) {
                $country = $to_update['country'];
                if (strstr($country, ';')) {
                    $c_arr = explode(';', $country);
                    foreach ($c_arr as $value) {
                        if (strstr($value, ', ')) {
                            $val_arr = explode(', ', $value);
                            $c = trim($val_arr[0]);
                            // incidence
                            $t = (int) trim(str_replace(',', '', $val_arr[1]));
                            // frequency
                            $frequency = str_replace('1:', '', $val_arr[2]);
                            $frequency = (int) trim(str_replace(',', '', $frequency));
                            //rank
                            $rank = (int) trim(str_replace(',', '', $val_arr[3]));
                            if ($top_rank == 0 || $rank < $top_rank) {
                                $top_rank = $rank;
                                $top_country_rank = $c;
                            }

                            $country_meta[] = array('c' => $c, 't' => $t, 'f' => $frequency, 'r' => $rank);
                        }
                    }
                }
            }
        }

        $lastname = trim($post->title);

        if ($debug) {
            print_r(array($lastname, $topcountry, $top_country_rank, $country_meta));
        }

        if ($lastname && $topcountry) {

            // Add name to db
            $top_country_id = $this->get_or_create_country($topcountry);

            $last_name_id = $this->get_lastname_id($lastname);


            $top_country_rank_id = 0;
            if ($top_country_rank) {
                $top_country_rank_id = $this->get_or_create_country($top_country_rank);
            }

            $user_data = array(
                'add_time' => $this->curr_time(),
                'lastname' => $lastname,
                'topcountry' => $top_country_id,
                'topcountry_rank' => $top_country_rank_id,
            );

            $name_exist = false;
            if (!$last_name_id) {
                // Add lastname
                $this->db_insert($user_data, $this->db['lastnames']);
                //Get the id
                $last_name_id = $this->getInsertId('id', $this->db['lastnames']);
                if ($debug) {
                    print "Add lastname $last_name_id:$lastname\n";
                }
            } else {
                // Update lastname
                $this->db_update($user_data, $this->db['lastnames'], $last_name_id);
                if ($debug) {
                    print "Lastname exist $last_name_id:$lastname\n";
                }
                $name_exist = true;
            }

            if (!$name_exist) {
                // Add meta for non-exist names

                if ($country_meta) {
                    foreach ($country_meta as $item) {
                        // Get country
                        $c_id = $this->get_or_create_country($item['c']);
                        $data = array(
                            'ccount' => (int) $item['t'],
                            'freq' => (int) $item['f'],
                            'area_rank' => (int) $item['r'],
                        );
                        if ($debug) {
                            print_r($data);
                        }
                        $exist_id = $this->get_country_meta($last_name_id, $c_id);

                        if ($exist_id) {
                            $this->db_update($data, $this->db['meta_fb'], $exist_id);
                            if ($debug) {
                                print_r(array("Update name meta $exist_id\n", $data, $exist_id));
                            }
                        } else {
                            $data['nid'] = (int) $last_name_id;
                            $data['cid'] = (int) $c_id;

                            $this->db_insert($data, $this->db['meta_fb']);
                            if ($debug) {
                                print_r(array("Insert name meta\n", $data));
                            }
                        }
                    }
                }
            }
        }
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



        $query = "SELECT l.id, l.lastname, c.country as topcountryname, cr.country as topcountryrank"
                . " FROM {$this->db['lastnames']} l"
                . " INNER JOIN {$this->db['country']} c ON c.id=l.topcountry"
                . " LEFT JOIN {$this->db['country']} cr ON cr.id=l.topcountry_rank"
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

    public function get_country_meta($last_name_id, $c_id) {
        $sql = sprintf("SELECT id FROM {$this->db['meta_fb']} WHERE nid=%d AND cid=%d limit 1", $last_name_id, $c_id);
        $id = $this->db_get_var($sql);
        return $id;
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
        $sql = sprintf("SELECT id FROM {$this->db['country']} WHERE country='%s'", $this->escape($name));
        $id = $this->db_get_var($sql);

        if (!$id) {
            $id = $this->create_country_by_name($name);
        }
        // Add to cache
        $dict[$name] = $id;

        return $id;
    }

    public function get_all_countries() {
        $sql = "SELECT country FROM {$this->db['country']}";
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
        $sql = sprintf("SELECT m.ccount, c.country FROM {$this->db['meta_fb']} m"
                . " INNER JOIN {$this->db['country']} c ON c.id=m.cid"
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
        $sql = sprintf("SELECT country FROM {$this->db['country']} WHERE id=%d", (int) $id);
        $country = $this->db_get_var($sql);

        // Add to cache
        $dict[$id] = $country;

        return $country;
    }

    public function create_country_by_name($name) {
        $sql = sprintf("INSERT INTO {$this->db['country']} (country) VALUES ('%s')", $this->escape($name));
        $this->db_query($sql);
        //Get the id
        $id = $this->getInsertId('id', $this->db['country']);
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

                return array(
                    'country' => $country_name,
                    'cca2' => $population[$country_name]['cca2'],
                    'races' => $ret,
                    'simpson' => $simpson,
                    'top_race' => $population[$country_name]['top_race'],
                );
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
        $sql = "SELECT country_name, cca2, ethnic_array_result, simpson, top_race FROM {$this->db['population']}";
        $results = $this->db_results($sql);
        if ($results) {
            foreach ($results as $item) {
                $ret[$item->country_name] = array(
                    'cca2' => $item->cca2,
                    'ethnic' => json_decode($item->ethnic_array_result),
                    'simpson' => $item->simpson,
                    'top_race' => $item->top_race,
                );
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

                $races_arr = $this->get_country_races($country, $country_count, $simpson);
                if ($races_arr) {
                    $race_str = array();
                    $race_str_arr = array();
                    $cca2 = $races_arr['cca2'];
                    foreach ($races_arr['races'] as $race => $count) {
                        if ($count > 0) {
                            $race_str[] = $race . ": " . $count;
                            $race_small = $this->race_small[$race];
                            $race_str_arr[$race_small] = $count;
                            $race_total[$race] += $count;
                        }
                    }
                    $rows_race[] = $races_arr['country'] . ': ' . implode(', ', $race_str);
                    $rows_race_arr[$cca2] = $race_str_arr;

                    $rows_total_arr[$cca2] = $country_count;
                    $total += $country_count;

                    $rows_total[] = $country . ': ' . $country_count . '; S(' . round($races_arr['simpson'], 2) . ')';
                }
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

    public function calculate_top_verdict($item = '') {

        $race_total = array();
        $rows_total = array();
        $rows_race = array();
        $rows_total_arr = array();
        $rows_race_arr = array();
        $total = 0;
        $verdict = 0;
        $top_race = 0;
        $top_race_name = '';

        $country = $item->topcountryrank;
        $country_count = 100;

        $races_arr = array();
        if ($country) {
            $races_arr = $this->get_country_races($country, $country_count, false);
        }
        if ($races_arr) {
            $race_str = array();
            $race_str_arr = array();
            $cca2 = $races_arr['cca2'];
            $top_race = $races_arr['top_race'];
            foreach ($races_arr['races'] as $race => $count) {
                if ($count > 0) {
                    $race_str[] = $race . ": " . $count;
                    $race_small = $this->race_small[$race];
                    $race_str_arr[$race_small] = $count;
                    $race_total[$race] += $count;
                }
            }
            $rows_race[] = $races_arr['country'] . ': ' . implode(', ', $race_str);
            $rows_race_arr[$cca2] = $race_str_arr;

            $rows_total_arr[$cca2] = $country_count;
            $total += $country_count;

            $rows_total[] = $country . ': ' . $country_count . '<br /> - simpson: ' . $races_arr['simpson'];

            arsort($race_total);

            $verdict = array_keys($race_total)[0];
            $total_str = array();
            foreach ($race_total as $race => $cnt) {
                $total_str[] = $race . ': ' . $cnt;
            }
            $rows_total[] = 'Total: ' . $total;
            $rows_race[] = 'Total: ' . implode(', ', $total_str);
        }

        if ($top_race){
                        
            foreach ($this->race_small as $key => $value) {
                if ($value==$top_race){
                    $top_race_name = $key;
                    break;
                }
            }
        }

        return array(
            'rows_total' => $rows_total,
            'rows_race' => $rows_race,
            'rows_race_arr' => $rows_race_arr,
            'rows_total_arr' => $rows_total_arr,
            'verdict' => $verdict,
            'top_race' => $top_race,
            'top_race_name' => $top_race_name,
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

    public function cron_actor_verdict($count = 100, $simpson = true, $debug = false) {

        // 1. Get lastnames
        $sql = sprintf("SELECT l.id, l.lastname, cr.country as topcountryrank, c.country as topcountryname"
                . " FROM {$this->db['lastnames']} l"
                . " INNER JOIN {$this->db['country']} c ON c.id=l.topcountry"
                . " INNER JOIN {$this->db['country']} cr ON cr.id=l.topcountry_rank"
                . " LEFT JOIN {$this->db['verdict']} v ON v.lastname=l.lastname"
                . " WHERE v.id is NULL ORDER BY l.id DESC LIMIT %d", (int) $count);
        $result = $this->db_results($sql);

        if ($debug) {
            print_r($result);
        }

        if (!$result) {
            return;
        }

        foreach ($result as $item) {

            // 2. Calculate vedrict
            $verdict_arr = $this->calculate_fs_verdict($item->id, $simpson);
            if ($debug) {
                print_r(array('verdict', $verdict_arr));
            }
            $verdict_data = $this->theme_verdict($verdict_arr);

            // 3. Calculate top verdict
            $top_arr = $this->calculate_top_verdict($item);
            if ($debug) {
                print_r(array('top verdict', $top_arr));
            }
            $top_data = $this->theme_verdict($top_arr);

                                       
            $verdict_rank = $top_data['verdict'];
            // Custom verdict         
            $top_race = $top_arr['top_race'];
            if ($top_race) {
                $verdict_rank = $top_race;
            }

            // 4. Add verdict to db
            $last_upd = time();
            $lastname = $this->escape($item->lastname);
            $data = array(
                'last_upd' => $last_upd,
                'verdict' => $verdict_data['verdict'],
                'lastname' => $lastname,
                'description' => $verdict_data['desc'],
                'verdict_rank' => $top_data['verdict'],
                'description_rank' => $top_data['desc'],
            );

            if ($debug) {
                print_r($data);
            }

            $this->db_insert($data, $this->db['verdict']);
        }
    }

    private function theme_verdict($verdict_arr) {
        $verdict_int = $this->race_small[$verdict_arr['verdict']] ? $this->race_small[$verdict_arr['verdict']] : 0;
        $desc_arr = array('total' => $verdict_arr['rows_total_arr'], 'race' => $verdict_arr['rows_race_arr']);
        $desc = json_encode($desc_arr);
        return array('verdict' => $verdict_int, 'desc' => $desc);
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
