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
                        if ($debug){
                            print_r(array($aid,$country,$item->country));
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

    private function get_total_country($meta, $def = '') {
        if ($meta->crowdsource) {
            return $meta->crowdsource;
        }
        if ($meta->ethnic) {
            return $meta->ethnic;
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
