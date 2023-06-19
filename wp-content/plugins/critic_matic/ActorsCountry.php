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
            'actors_ethnic' => 'data_actors_ethnic',
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
                $fb_country = $item->country;
                if ($fb_country == 'Saint Helena') {
                    $fb_country = 'United States';
                }

                # Get actors
                $actors = $this->get_actors_by_last_name($item->lastname);
                if ($actors) {
                    if ($debug) {
                        print_r(array('actors: ', $actors));
                    }
                    foreach ($actors as $actor) {
                        $aid = $actor->aid;
                        # Update or Create meta
                        $field = 'forebears';
                        $country = $ma->get_or_create_country_by_name($fb_country, true);
                        if ($country) {
                            if ($debug) {
                                print_r(array($actor, $country, $fb_country));
                            }
                            $this->update_actor_meta($aid, $country, $field);
                        } else {
                            if ($debug) {
                                print_r(array('Not found contry for: ', $actor, $fb_country));
                            }
                        }
                        $country = '';
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

    public function add_ethnic($post, $url) {
        $aid = $post->top_movie;
        $options = unserialize($post->options);
        // Add country
        $str_bplace = isset($options['bplace']) ? base64_decode($options['bplace']) : '';
        $place = $this->validate_place($str_bplace);
        if ($place) {

            $ma = $this->cm->get_ma();
            $country = $ma->get_country_by_name($place, true);
            if ($country) {
                $field = 'ethnic';
                $this->update_actor_meta($aid, $country, $field);
            }
        }

        // Add ethnic data
        $score_opt = array(
            'Title' => 'Name',
            'date' => 'DateBirth',
            'ethnicity' => 'Ethnicity',
            'bname' => 'BirthName',
            'bplace' => 'PlaceBirth',
            'tags' => 'Tags',
        );
        return;
        $to_update = array();
        foreach ($score_opt as $post_key => $db_key) {
            if (isset($options[$post_key])) {
                $field_value = base64_decode($options[$post_key]);
                if ($field_value) {
                    $to_update[$db_key] = $field_value;
                }
            }
        }

        if ($to_update) {
            $to_update['last_update'] = $this->curr_time();

            // Add link
            $to_update['Link'] = $url->link;

            // Data exist?
            $sql = sprintf("SELECT * FROM {$this->db['actors_ethnic']} WHERE actor_id=%d", $aid);
            $actor_exist = $this->db_fetch_row($sql);

            if ($actor_exist) {
                $this->sync_update_data($to_update, $actor_exist->id, $this->db['actors_ethnic'], $this->cm->sync_data, 10);
            } else {
                $to_update['actor_id'] = $aid;
                $this->sync_insert_data($to_update, $this->db['actors_ethnic'], $this->cm->sync_client, $this->cm->sync_data, 10);
            }
        }
    }

    public function test_ethnic() {
        // Get movielinks data an show unique countryes

        if (!class_exists('MoviesLinks')) {
            !defined('MOVIES_LINKS_PLUGIN_DIR') ? define('MOVIES_LINKS_PLUGIN_DIR', ABSPATH . 'wp-content/plugins/movies_links/') : '';
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
