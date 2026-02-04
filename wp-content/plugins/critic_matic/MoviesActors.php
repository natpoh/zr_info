<?php

/**
 * Description of MoviesActors
 * Update movie actor meta for seach
 *
 * @author brahman
 */
class MoviesActors extends AbstractDB {

    public $cm;
    public $count = array(
        'e' => array('key' => 0, 'title' => 'Exist'),
        'n' => array('key' => 1, 'title' => 'Number'),
        'p' => array('key' => 2, 'title' => 'Percent'),
    );
    public $type = array(
        'a' => array('key' => 0, 'title' => 'All'),
        's' => array('key' => 1, 'title' => 'Stars'),
        'm' => array('key' => 2, 'title' => 'Main'),
    );
    public $gender = array(
        'a' => array('key' => 0, 'title' => 'All'),
        'm' => array('key' => 2, 'title' => 'Male'),
        'f' => array('key' => 1, 'title' => 'Female'),
    );
    public $races = array(
        'a' => array('key' => 0, 'title' => 'All'),
        'w' => array('key' => 1, 'title' => 'White'),
        'ea' => array('key' => 2, 'title' => 'Asian'),
        'h' => array('key' => 3, 'title' => 'Latino'),
        'b' => array('key' => 4, 'title' => 'Black'),
        'i' => array('key' => 5, 'title' => 'Indian'),
        'm' => array('key' => 6, 'title' => 'Arab'),
        'mix' => array('key' => 7, 'title' => 'Mixed / Other'),
        'jw' => array('key' => 8, 'title' => 'Jewish'),
    );
    public $option_name = 'movies_actors_last_id';

    public function __construct($cm = '') {
        $this->cm = $cm ? $cm : new CriticMatic();
        $this->db = array(
            'movie_imdb' => 'data_movie_imdb',
            'meta_movie_actor' => 'meta_movie_actor',
            'data_actors_meta' => 'data_actors_meta',
            'cache_actor' => 'cache_movie_actor_meta',
        );
    }

    /*
     * Cron for new movies
     */

    public function run_cron($count = 100, $debug = false, $force = false) {

        $last_id = $this->get_option($this->option_name, 0);
        if ($force) {
            $last_id = 0;
        }

        if ($debug) {
            print_r(array('last_id', $last_id));
        }

        // 1. Get posts
        $sql = sprintf("SELECT id, title FROM {$this->db['movie_imdb']} WHERE id>%d limit %d", $last_id, $count);
        $results = $this->db_results($sql);
        if ($debug) {
            print_r($results);
        }

        if ($results) {
            $last = end($results);
            if ($last) {
                $this->update_option($this->option_name, $last->id);
            }

            foreach ($results as $movie) {
                $this->update_movie($movie, $debug);
            }
        }
    }

    public function hook_update_movies($mids = array(), $debug = false) {
        if (!$mids) {
            return;
        }
        if (!is_array($mids)) {
            $mids = array($mids);
        }
        $sql = "SELECT id, title FROM {$this->db['movie_imdb']} WHERE id IN(" . implode(',', $mids) . ")";
        $movies = $this->db_results($sql);
        if ($debug) {
            print_r($movies);
        }
        if ($movies) {
            foreach ($movies as $movie) {
                $this->update_movie($movie, $debug);
            }
        }
    }

    private function update_movie($movie, $debug = false) {

        $title = $movie->title;
        $mid = $movie->id;

        // Get movie actors
        $sql_actors = sprintf("SELECT a.aid AS actor_id, a.type AS actor_type, m.* FROM {$this->db['meta_movie_actor']} a"
                . " INNER JOIN {$this->db['data_actors_meta']} m ON m.actor_id = a.aid"
                . " WHERE a.mid=%d", $mid);
        $actors = $this->db_results($sql_actors);

        if ($debug) {
            print_r(array($sql_actors, $actors));
        }
        if ($actors) {
            // Calcualte actors counts
            /*
              [11] => stdClass Object
              (
              [aid] => 742550
              [type] => 2
              [n_verdict_weight] => 8
              )
             */

            $data = array();

            $all_keys = array();
            foreach ($actors as $actor) {
                $rank = $actor->n_verdict_weight;
                $gender = $actor->gender;
                $type_int = $actor->actor_type;

                $number = 'n';
                $exist = 'e';

                // Type
                foreach ($this->type as $tkey => $tvalue) {
                    if ($type_int == $tvalue['key'] || $tkey == 'a') {
                        // Gender
                        foreach ($this->gender as $gkey => $gvalue) {
                            if ($gender == $gvalue['key'] || $gkey == 'a') {
                                // Race  
                                foreach ($this->races as $rkey => $rvalue) {
                                    if ($rank == $rvalue['key'] || $rkey == 'a') {
                                        // Number
                                        $nkey = "{$number}{$tkey}{$gkey}{$rkey}";
                                        $data[$nkey] = isset($data[$nkey]) ? ($data[$nkey] + 1) : 1;
                                        // Exist
                                        $ekey = "{$exist}{$tkey}{$gkey}{$rkey}";
                                        $data[$ekey] = 1;
                                    }
                                }
                            }
                        }
                    }
                }
            }

            // Get data percent
            $percent = 'p';
            // Type
            foreach ($this->type as $tkey => $tvalue) {
                // Gender
                foreach ($this->gender as $gkey => $gvalue) {
                    // Race  
                    foreach ($this->races as $rkey => $rvalue) {
                        // Number
                        $nkey = "{$number}{$tkey}{$gkey}{$rkey}";
                        if (isset($data[$nkey])) {
                            // Percent
                            $akey = '';
                            if ($rkey != 'a') {
                                // Race percent
                                $akey = "{$number}{$tkey}{$gkey}a";
                            } else if ($gkey != 'a') {
                                // Gender percent
                                $akey = "{$number}{$tkey}a{$rkey}";
                            } else if ($tkey != 'a') {
                                // Type percent
                                $akey = "{$number}a{$gkey}{$rkey}";
                            }
                            if ($akey) {
                                $pval = (int) round(($data[$nkey] / $data[$akey]) * 100, 0);
                                $pkey = "{$percent}{$tkey}{$gkey}{$rkey}";
                                $data[$pkey] = $pval;
                            }
                        }
                    }
                }
            }

            if ($debug) {
                print_r(array($data));
            }


            $data['last_upd'] = $this->curr_time();
            $data['need_upd'] = 0;

            if ($debug) {
                print_r($data);
            }
            // Movie cache exist?
            $sql_exist = sprintf("SELECT id FROM {$this->db['cache_actor']} WHERE mid=%d", $mid);
            $exist_id = $this->db_get_var($sql_exist);
            if ($debug) {
                print_r(array('exist', $exist_id));
            }
            if ($exist_id) {
                // Update cache
                $this->sync_update_data($data, $exist_id, $this->db['cache_actor'], 10);
            } else {
                // Insert new cache                
                $data['mid'] = $mid;
                $this->sync_insert_data($data, $this->db['cache_actor'], 10);
            }
        }
    }

    public function get_movie_actors($mid = 0) {
        // Get movie actors
        $sql_actors = sprintf("SELECT a.aid AS actor_id, a.type AS actor_type, m.* FROM {$this->db['meta_movie_actor']} a"
                . " INNER JOIN {$this->db['data_actors_meta']} m ON m.actor_id = a.aid"
                . " WHERE a.mid=%d", $mid);
        $actors = $this->db_results($sql_actors);
        return $actors;
    }
    
    public function get_cache_actors($mid=0) {
        $sql = sprintf("SELECT * FROM {$this->db['cache_actor']} WHERE mid=%d", $mid);
        $actors = $this->db_results($sql);
        return $actors;
    }

}

class MoviesDirectors extends MoviesActors {

    public $type = array(
        'a' => array('key' => 0, 'title' => 'all'),
        'd' => array('key' => 1, 'title' => 'director'),
        'w' => array('key' => 2, 'title' => 'writer'),
        'c' => array('key' => 3, 'title' => 'cast_director'),
        'p' => array('key' => 4, 'title' => 'producers'),
    );
    public $option_name = 'movies_directors_last_id';

    public function __construct($cm = '') {
        $this->cm = $cm ? $cm : new CriticMatic();
        $this->db = array(
            'movie_imdb' => 'data_movie_imdb',
            'data_actors_meta' => 'data_actors_meta',
            'meta_movie_actor' => 'meta_movie_director',
            'cache_actor' => 'cache_movie_director_meta',
        );
    }

}
