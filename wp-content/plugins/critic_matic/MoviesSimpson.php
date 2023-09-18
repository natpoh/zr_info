<?php

/**
 * Calculate Simpson Diversity index for all movies
 * 
 * TODO:
 * 1. Cron for new movies
 * 2. Hook for new or updated actors
 *
 * @author brahman
 */
class MoviesSimpson extends AbstractDB {

    public function __construct() {
        $this->db = array(
            'movie_imdb' => 'data_movie_imdb',
            'meta_movie_actor' => 'meta_movie_actor',
            'data_actors_meta' => 'data_actors_meta',
            'data_woke' => 'data_woke',
        );
    }

    public function run_cron($count = 100, $debug = false, $force = false) {
        $option_name = 'movies_simpson_last_id';
        $last_id = $this->get_option($option_name, 0);
        if ($force) {
            $last_id = 0;
        }

        if ($debug) {
            print_r(array('last_id', $last_id));
        }

        // 1. Get posts
        $sql = sprintf("SELECT id, title, country, year FROM {$this->db['movie_imdb']} WHERE id>%d limit %d", $last_id, $count);
        $results = $this->db_results($sql);
        if ($debug) {
            print_r($results);
        }

        if ($results) {
            $last = end($results);
            if ($last) {
                $this->update_option($option_name, $last->id);
            }

            foreach ($results as $movie) {
               $this->update_movie($movie, $debug) ;
            }
        }
    }
    
    public function hook_update_movie($mid=0, $debug=false){
        $sql = sprintf("SELECT id, title, country, year FROM {$this->db['movie_imdb']} WHERE id=%d", $mid);
        $movie = $this->db_fetch_row($sql);
        if ($debug) {
            print_r($movie);
        }
        if ($movie){
            $this->update_movie($movie, $debug) ;
        }
    }

    private function update_movie($movie, $debug=false) {

        $title = $movie->title;
        $country = $movie->country;
        $year = $movie->year;
        $mid = $movie->id;

        // Get movie actors
        $sql_actors = sprintf("SELECT a.aid, a.type, m.n_forebears_rank FROM {$this->db['meta_movie_actor']} a"
                . " INNER JOIN {$this->db['data_actors_meta']} m ON m.actor_id = a.aid"
                . " WHERE a.mid=%d AND m.n_forebears_rank>0", $mid);
        $actors = $this->db_results($sql_actors);

        if ($debug) {
            //print_r($actors);
        }
        if ($actors) {
            // Simpson's Diversity = 1 - (870 / (48*(48-1))) = 0.61
            /*
              [11] => stdClass Object
              (
              [aid] => 742550
              [type] => 2
              [n_forebears_rank] => 8
              )
             */

            $races = array();
            /*
             * 0 - all
             */
            foreach ($actors as $actor) {
                $rank = $actor->n_forebears_rank;
                $type = $actor->type;
                $races[0][$rank] = isset($races[0][$rank]) ? ($races[0][$rank] + 1) : 1;

                $races[$type][$rank] = isset($races[$type][$rank]) ? ($races[$type][$rank] + 1) : 1;
            }
            if ($debug) {
                print_r($races);
            }
            $simpson = array();
            foreach ($races as $type => $rank) {
                $race_total = 0;
                $race_total_sp = 0;
                foreach ($rank as $race) {
                    $race_total += $race;
                    $race_sp = $race * ($race - 1);
                    $race_total_sp += $race_sp;
                }
                $del = $race_total * ($race_total - 1);
                if ($del == 0) {
                    $sm = 0;
                } else {
                    $sm = 1 - ($race_total_sp / $del);
                }
                $simpson[$type] = (int) ($sm * 100);
            }
            if ($debug) {
                /*
                  (
                  [0] => 63
                  [1] => 100
                  [2] => 56
                  [3] => 59
                  )
                 * 
                  0 - All
                  1 - Star
                  2 - Main
                  3 - Extra
                 */
                print_r($simpson);
            }

            //last_update
            $data = array(
                'last_update' => $this->curr_time(),
                'simpson_all' => isset($simpson[0]) ? $simpson[0] : 0,
                'simpson_star' => isset($simpson[1]) ? $simpson[1] : 0,
                'simpson_main' => isset($simpson[2]) ? $simpson[2] : 0,
                'simpson_extra' => isset($simpson[3]) ? $simpson[3] : 0,
            );

            if ($debug) {
                print_r($data);
            }
            // Woke exist?
            $sql_woke = sprintf("SELECT id FROM {$this->db['data_woke']} WHERE mid=%d", $mid);
            $exist_id = $this->db_get_var($sql_woke);
            if ($debug) {
                print_r(array('exist', $exist_id));
            }
            if ($exist_id) {
                // Update woke
               
                $this->sync_update_data($data, $exist_id, $this->db['data_woke'], true, 10);
            } else {
                // Insert new woke
                $data['title'] = $title;
                $data['mid'] = $mid;
                $data['country'] = $country;
                $data['year'] = (int) $year;

                $this->sync_insert_data($data, $this->db['data_woke'], false, true, 10);
            }
        }
    }

}
