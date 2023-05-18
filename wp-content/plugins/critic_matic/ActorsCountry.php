<?php

/**
 * Description of ActorsCountry
 *
 * @author brahman
 * 
 * TODO
 * cron: get new names from forebears (familysearch) and create (update) meta
 * hook: create (update) meta from new actor lastname
 */
class ActorsCountry extends AbstractDB {

    private $cm;
    private $db;

    public function __construct($cm = '') {
        $this->cm = $cm ? $cm : new CriticMatic();
        $this->db = array(
            'actors_normalize' => 'data_actors_normalize',
            'movie_country' => 'data_movie_country',
            'actors_country' => 'data_actors_country',
            'forebears_lastnames' => 'data_forebears_lastnames',
            'forebears_country' => 'data_forebears_country',
        );
    }

    public function run_cron($count = 10, $debug = false, $force = false) {
        $option_name = 'actors_country_last_id';
        $last_id = $this->get_option($option_name, 0);
        if ($force) {
            $last_id = 0;
        }

        if ($debug) {
            p_r(array('last_id', $last_id));
        }

        // 1. Get lastnames
        $sql = sprintf("SELECT n.id, n.lastname, c.country FROM {$this->db['forebears_lastnames']} n"
                . " INNER JOIN {$this->db['forebears_country']} c ON c.id=n.topcountry_rank"
                . " WHERE n.id>%d ORDER BY n.id ASC limit %d", $last_id, $count);
        $results = $this->db_results($sql);

        if ($results) {
            $last = end($results);
            if ($debug) {
                print 'last id: ' . $last->id . "\n";
            }
            if ($last) {
                $this->update_option($option_name, $last->id);
            }

            $ma = $this->cm->get_ma();

            foreach ($results as $item) {
                print_r($item);
                /*

                  stdClass Object
                  (
                  [id] => 4
                  [lastname] => Dunlow
                  [country] => United States
                  )
                 */

                # Get actors
                $actors = $this->get_actors_by_last_name($item->lastname);
                if ($actors) {
                    foreach ($actors as $actor) {
                        $aid = $actor->aid;
                        # Update or Create meta
                        $field = 'forebears';
                        $country = $ma->get_or_create_country_by_name($item->country, true);
                        if ($debug) {
                            print_r(array($aid, $country, $item->country));
                        }
                        $this->update_actor_meta($aid, $country, $field);
                    }
                }
            }
        }
    }

    public function get_actors_by_last_name($lastname) {
        $sql = sprintf("SELECT aid FROM {$this->db['actors_normalize']} WHERE lastname='%s'", $lastname);
        $results = $this->db_results($sql);
        return $results;
    }

    public function update_actor_meta($aid = 0, $country = 0, $field = 'forebears') {
        $sql = sprintf("SELECT * FROM {$this->db['actors_country']} WHERE actor_id=%d", $aid);
        $meta_exist = $this->db_fetch_row($sql);

        if ($meta_exist) {
            # Update meta
            $data = array();
            $data[$field] = $country;
            $meta_exist->$field = $country;
            $data['result'] = $this->get_total_country($meta_exist, $country);
            $this->sync_update_data($data, $meta_exist->id, $this->db['actors_country'], $this->cm->sync_data, 10);
        } else {
            # Add meta
            $data = array(
                'actor_id' => $aid,
                'result' => $country,
            );
            $data[$field] = $country;
            $this->sync_insert_data($data, $this->db['actors_country'], $this->cm->sync_client, $this->cm->sync_data, 10);
        }
    }

    /*
     * Etchic logic
     */

    public function add_ethnic($post) {
        $options = unserialize($post->options);
        $str_bplace = isset($options['bplace']) ? base64_decode($options['bplace']) : '';
        $place = $this->validate_place($str_bplace);
        if ($place){
            $aid = $post->top_movie;
            $ma = $this->cm->get_ma();
            $country = $ma->get_country_by_name($place, true);
            if ($cid){
                $field = 'ethnic';
                $this->update_actor_meta($aid, $country, $field);
            }
        }
        
    }

    public function test_ethnic() {
        // Get movielinks data an show unique countryes

        if (!class_exists('MoviesLinks')) {
            require_once( MOVIES_LINKS_PLUGIN_DIR . 'db/MoviesAbstractFunctions.php' );
            require_once( MOVIES_LINKS_PLUGIN_DIR . 'db/MoviesAbstractDBAn.php' );
            require_once( MOVIES_LINKS_PLUGIN_DIR . 'db/MoviesAbstractDB.php' );
            require_once( MOVIES_LINKS_PLUGIN_DIR . 'MoviesLinks.php' );
        }

        $ml = new MoviesLinks();
        $cid = 25;
        $mp = $ml->get_mp();
        // $campaign = $mp->get_campaign($cid);

        $posts = $mp->get_last_posts(10000, $cid, 1);
        if ($posts) {
            $places = array();
            foreach ($posts as $post) {

                $options = unserialize($post->options);

                $str_bplace = isset($options['bplace']) ? base64_decode($options['bplace']) : '';
                $place = $this->validate_place($str_bplace);

                if ($place) {
                    $places[$place] += 1;
                }
            }

            arsort($places);
            print_r($places);
        }
    }

    private function validate_place($place) {

        if (preg_match('/\((?:now|present-day) (.+)\)/', $place, $match)) {
            $place = $match[1];
        }
        if (strstr($place, ', ')) {
            $str_bplace_arr = explode(', ', $place);
            $place = $str_bplace_arr[sizeof($str_bplace_arr) - 1];
        }


        $place = preg_replace('/\([a-z A-Z]+\)/', '', $place);
        $place = preg_replace('/\[[a-z A-Z]+\]/', '', $place);


        $replace = array(
            'U.S.' => 'United States',
            'U.S' => 'United States',
            'US' => 'United States',
            'USA' => 'United States',
            'UK' => 'United Kingdom',
            'U.K.' => 'United Kingdom',
        );

        foreach ($replace as $key => $value) {
            if (strstr($place, $key)) {
                $place = str_replace($key, $value, $place);
                break;
            }
        }

        $place = trim($place);

        return $place;
    }

    /*
     * Other
     */

    private function get_total_country($meta, $def = '') {
        if ($meta->crowdsource) {
            return $meta->crowdsource;
        }
        if ($meta->ethnic) {
            return $meta->ethnic;
        }

        if ($meta->forebears) {
            return $meta->forebears;
        }
        if ($meta->familysearch) {
            return $meta->familysearch;
        }
        return $def;
    }

}
