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
                $this->update_option($option_name, $last->id);
            }

            $curr_time = $this->curr_time();
            foreach ($results as $item) {
                $title = $item->title;
                $clear_title = $this->clear_title_second($title);
                $words = explode(' ', $clear_title);
                $total_weight = 0;
                $multipler = 1;
                $weights = array();
                foreach ($words as $word) {
                    $word = trim($word);
                    if ($word) {
                        $popular = $this->get_word_weight(trim($word));
                        $weight = 1000 / $popular;
                        $weights[] = $weight;
                        $total_weight += $weight;
                        $total_weight *= $multipler;
                        $multipler *= 1.5;
                    }
                }
                $total_weight = (int) round($total_weight, 0);
                if ($debug) {
                    print_r(array($title, $words, $weights, $total_weight, $multipler));
                }
                // Update weight
                $data = array(
                    'title_weight' => $total_weight,
                    'title_weight_upd' => $curr_time,
                );
                if ($debug) {
                    print_r($data);
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

    public function clear_title_second($string, $glue = ' ') {
        $string = str_replace('&', ' and ', $string);
        $string = preg_replace("/('|`)/", "", $string);
        $string = preg_replace("/([0-9]+)/", "", $string);


        $table = array(
            'Š' => 'S', 'š' => 's', 'Đ' => 'Dj', 'đ' => 'dj', 'Ž' => 'Z', 'ž' => 'z', 'Č' => 'C', 'č' => 'c', 'Ć' => 'C', 'ć' => 'c',
            'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'A', 'Ç' => 'C', 'È' => 'E', 'É' => 'E',
            'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O',
            'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O', 'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ý' => 'Y', 'Þ' => 'B', 'ß' => 'Ss',
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'a', 'ç' => 'c', 'è' => 'e', 'é' => 'e',
            'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ð' => 'o', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o',
            'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ø' => 'o', 'ù' => 'u', 'ü' => 'u', 'ú' => 'u', 'û' => 'u', 'ý' => 'y', 'ý' => 'y', 'þ' => 'b',
            'ÿ' => 'y', 'Ŕ' => 'R', 'ŕ' => 'r', '/' => '-', ' ' => '-'
        );

        // -- Remove duplicated spaces
        $stripped = preg_replace(array('/\s{2,}/', '/[\t\n]/'), ' ', trim($string));

        // -- Returns the slug
        $slug = strtolower(strtr($stripped, $table));
        $slug = preg_replace('~[^\pL\d]+~u', $glue, $slug);

        $slug = trim($slug);

        return $slug;
    }

}
