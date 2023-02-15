<?php

class MoviesWeight extends AbstractDB {

    private $db;

    public function __construct() {
        $this->db = array(
            'movie_imdb' => 'data_movie_imdb',
            'words_weight' => 'data_words_weight',
        );
    }

    public function run_cron($count = 100, $debug = false, $force = false) {
        $option_name = 'movies_weight_last_id';
        $last_id = $this->get_option($option_name, 0);
        if ($force) {
            $last_id = 0;
        }

        if ($debug) {
            p_r(array('last_id', $last_id));
        }

        // 1. Get posts
        $sql = sprintf("SELECT id, title FROM {$this->db['movie_imdb']} WHERE id>%d limit %d", $last_id, $count);
        $results = $this->db_results($sql);
        if ($debug) {
            p_r($results);
        }

        if ($results) {
            $last = end($results);
            if ($last) {
                $this->update_option($option_name, $last->id);
            }

            $curr_time = $this->curr_time();
            foreach ($results as $item) {
                $title = $item->title;
                $clear_title = $this->clear_title($title);
                $words = explode(' ', $clear_title);
                
                $total_weight = 0;
                $multipler = 1;
                $weights = array();
                foreach ($words as $word) {
                    $popular = $this->get_word_weight(trim($word));
                    $weight = 1000/$popular;
                    $weights[]=$weight;
                    $total_weight += $weight;
                    $total_weight*=$multipler;
                    $multipler*=1.5;
                }
                $total_weight = (int)round($total_weight,0);
                if ($debug){
                    p_r(array($title, $words, $weights, $total_weight, $multipler));
                }
                // Update weight
                $data = array(
                    'title_weight'=>$total_weight,
                    'title_weight_upd'=>$curr_time,
                );
                if ($debug){
                    p_r($data);
                }
                $this->sync_update_data($data, $item->id, $this->db['movie_imdb'], true, 10);
            }
        }
    }

    private function get_word_weight($word = '', $cache = true) {
        if ($cache) {
            static $dict;
            if (is_null($dict)) {
                $dict = array();
            }

            if (isset($dict[$word])) {
                return $dict[$word];
            }
        }

        $sql = sprintf("SELECT weight FROM {$this->db['words_weight']} WHERE name='%s'", $this->escape($word));
        $result = $this->db_get_var($sql);
        if (!$result) {
            $result = 100;
        }

        if ($cache) {
            $dict[$word] = $result;
        }
        return $result;
    }

    private function clear_title($title) {
        $title = strip_tags($title);
        $title = str_replace("'", "", $title);
        $title = preg_replace('#[^\w\d ]+#', ' ', $title);
        $title = preg_replace('#  #', ' ', $title);
        return $title;
    }

}
