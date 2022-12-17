<?php

/* TODO
 * get pg_rating
 *  if last update date is expire
 * parse data
 *  save raw data to temp cache
 * validate data     
 * update rating
 *  save info to db
 */

/**
 * Parser for Dove.org
 *
 * @author brahman
 */
class ParserDove {

    // Datebase for analytics
    private $db;
    private $curl;
    public $log;
    //Write the log
    public $write_log = true;
    //Cache a parsing result
    public $cache_result = false;
    public $use_proxy = false;
    //Paths to cache
    public $search_cahe_path = ABSPATH . 'analysis/cache_request/dove.org/search/';
    public $posts_cahe_path = ABSPATH . 'analysis/cache_request/dove.org/posts/';
    //Min procent to valid compare
    public $min_compare = 50;
    public $debug_on = false;

    public function __construct() {
        //Init db names
        $this->db = array(
            'movie_imdb' => 'data_movie_imdb',
            'pg_rating' => 'data_pg_rating',
        );

        //Init curl
        $this->curl = new GETCURL();

        //Check dirs
        FileService::check_and_create_abs_dir($this->search_cahe_path);
        FileService::check_and_create_abs_dir($this->posts_cahe_path);

        //Log
        $this->log = new FileLog(ABSPATH . 'temp/paser_dove.log');
    }

    public function update($limit = 1, $wait_days = 30, $sleep = 1) {
        $rows = $this->next_step($limit, $wait_days);

        $this->debug($rows);

        if (sizeof($rows)) {
            foreach ($rows as $row) {
                $id = $row->id;
                $title = $row->title;
                $year = (int) $row->year;
                $log = "| " . $title;
                $add_to_db = false;

                //Parse title from dove.org                
                //Name: The Trial of Old Drum
                //URL: https://dove.org/?s=The+Trial+of+Old+Drum

                $title_arr = $this->clear_title_arr($title);
                $title_req = implode('+', $title_arr);
                $title_clear = implode(' ', $title_arr);

                //Search URL
                $search_url = "https://dove.org/?s=" . $title_req;
                $this->debug($title . ' - ' . $search_url . '<br />');

                //Get url
                $search_data = $this->get_cache($search_url, $this->search_cahe_path);

                if ($search_data) {
                    //Find title
                    //Return array (1,data)      
                    //0. Unknow error              
                    //1. Found title
                    //2. Not found
                    $result = $this->find_in_search_page($search_data);

                    $this->debug($search_title);

                    if ($result[0] == 1) {
                        $link = $result[1][0];
                        $title_found = trim($result[1][1]);
                        $title_found_arr = $this->clear_title_arr($title_found);
                        $title_found_clear = implode(' ', $title_found_arr);

                        //Compare titles
                        // 100% - ok
                        // >50% - need compare other fields
                        // >50% - result incorrect
                        $compare = $this->compare_titles($title_clear, $title_found_clear);
                        $this->debug('<b>' . $title_clear . "</b> - " . $title_found_clear . ' - ' . $compare . '<br />');
                        if ($compare > $this->min_compare) {
                            //Get the post page                            
                            $post_data = $this->get_cache($link, $this->posts_cahe_path);
                            if ($post_data) {
                                $post_result = $this->find_in_post_page($post_data);
                                if (sizeof($post_result['rating'])) {
                                    // Check compare result
                                    //Need validations yaer validation
                                    $post_year = '';
                                    if ($post_result['reliase']) {

                                        if (preg_match('|.* ([0-9]{4})$|', $post_result['reliase'], $match)) {
                                            $post_year = (int) $match[1];
                                        }
                                    }

                                    $this->debug("$year vs $post_year<br />");

                                    if ($year === $post_year) {
                                        $add_to_db = true;
                                    } else {
                                        //Different years                                       
                                        $msg = "Different years (" . $year . '!==' . $post_year . ") ";
                                    }

                                    if ($compare != 100) {
                                        //Need other validations 
                                        // TODO validate: Company, Writer, Runtime
                                        $msg = "Compare is small (" . $compare . "%): " . $title_found_clear;
                                        $add_to_db = false;
                                    }

                                    if ($add_to_db) {
                                        //$this->p_r($post_result);
                                        $msg = "Insert data to db (" . $compare . "%): " . $title_found_clear;
                                        $this->log_info($msg . $log);
                                        //Add found data to db                                        
                                        $this->update_rating_data($id, $post_result, $link);
                                    } else {
                                        $this->log_warn($msg . $log);
                                    }
                                } else {
                                    // Error parse rating from content                                    
                                    $hash = $this->cache_result ? md5($link) : '';
                                    $msg = "Error parse rating from content: " . $link . ' ' . $hash;
                                    $this->log_err($msg . $log);
                                }
                            } else {
                                // Can not parse post url
                                $msg = "Can not parse post url: " . $link;
                                $this->log_err($msg . $log);
                            }
                        } else {
                            // Titles not compare
                            $msg = "Titles not compare (" . $compare . "%): " . $title_found_clear;
                            $this->log_warn($msg . $log);
                        }
                    } else if ($result[0] == 2) {
                        // Title is: Not found
                        $msg = "Not found:" . $search_url;
                        $this->log_info($msg . $log);
                    } else {
                        // Other error with parse url
                        $hash = $this->cache_result ? md5($search_url) : '';
                        $msg = "Other error with parse url: " . $search_url . ' ' . $hash;
                        $this->log_err($msg . $log);
                    }
                } else {
                    //Can not parse search url
                    $msg = "Can not parse search url: " . $search_url;
                    $this->log_err($msg . $log);
                }

                if (!$add_to_db) {
                    //Update date
                    $this->update_date($id);
                }

                //Sleep
                if ($sleep > 0) {
                    sleep($sleep);
                }
            }
        }
    }

    /*
     * Get empty rating rows, and old rows
     * 
     * limit - numbers of rows
     * wait_days - days for wait before next find if info not found.
     */

    private function next_step($limit = 1, $wait_days = 30) {
        //Get data from db
        $time = time();

        //Current time minus days (in seconds).
        $next_date = $time - $wait_days * 86400;

        $sql = sprintf("SELECT movie.movie_id, movie.title, movie.year, rating.id FROM {$this->db['movie_imdb']} movie "
                . "INNER JOIN {$this->db['pg_rating']} rating ON movie.movie_id = rating.movie_id "
                . "WHERE rating.dove_date < %d AND rating.dove_link is NULL limit %d", (int) $next_date, (int) $limit);

        $this->debug($sql);
        $results = Pdo_an::db_results($sql);

        return $results;
    }

    /*
     * Insert rating data to DB
     */

    private function update_rating_data($id, $data, $link) {
        $rating_json = json_encode($data['rating']);
        $rating_info_json = '';
        if (sizeof($data['rating_info'])) {
            $rating_info_json = json_encode($data['rating_info']);
        }
        $date = time();

        //sanitization link
        $link_sat = stripslashes($link);

        //$this->p_r($rating_json);
        //$this->p_r($rating_info_json);
        Pdo_an::db_query(sprintf("UPDATE {$this->db['pg_rating']} SET dove_date=%d, dove_link='%s', dove_rating='%s', dove_rating_desc='%s' WHERE id = %d", (int) $date, $link_sat, $rating_json, $rating_info_json, (int) $id));
    }

    /*
     * Update rating date in DB
     */

    private function update_date($id) {
        $date = time();
        Pdo_an::db_query(sprintf("UPDATE {$this->db['pg_rating']} SET dove_date=%d WHERE id = %d", (int) $date, (int) $id));
    }

    /*
     * Compare first and second titles
     */

    private function compare_titles($first, $second) {
        // Lower words
        $low_first = strtolower($first);
        $low_sec = strtolower($second);

        // Compare srtings
        if ($low_first == $low_sec) {
            return 100;
        }

        $result = 0;

        //Compare arrays
        $first_a = explode(" ", $low_first);
        $second_a = explode(" ", $low_sec);

        $total = sizeof($first_a);
        $total_sec = sizeof($second_a);
        if ($total) {
            $i = 0;
            foreach ($first_a as $word) {
                if (in_array($word, $second_a)) {
                    $i += 1;
                }
            }
            $result_first = $i * 100 / $total;

            $i = 0;
            foreach ($second_a as $word) {
                if (in_array($word, $first_a)) {
                    $i += 1;
                }
            }
            $result_sec = $i * 100 / $total_sec;

            // max result = 99
            $result = (int) round(($result_first + $result_sec) / 2, 0);
            if ($result == 100) {
                $result -= 1;
            }
        }
        return $result;
    }

    public function json_bug_fix() {
        $sql = "SELECT id, dove_rating_desc FROM {$this->db['pg_rating']} WHERE dove_rating_desc REGEXP '\:\"\"[^,}]+'";
        $results = Pdo_an::db_results($sql);
        if (sizeof($results)) {
            foreach ($results as $item) {
                $id = $item->id;
                $desc = $item->dove_rating_desc;
                print_r($desc . "\n");
                $new_desc = array();
                if (preg_match_all('/"([^"]+)"\:"(.*)"(?:,|})/Us', $desc, $match)) {
                    print_r($match);
                    for ($i = 0; $i < sizeof($match[1]); $i++) {
                        $new_desc[$match[1][$i]] = htmlspecialchars($match[2][$i]);
                    }
                }
                print_r($new_desc);
                $json_new_desc = json_encode($new_desc);
                print_r($json_new_desc . "\n");
                print_r(json_decode($json_new_desc));
                Pdo_an::db_query(sprintf("UPDATE {$this->db['pg_rating']} SET dove_rating_desc='%s' WHERE id = %d", $json_new_desc, $id));
            }
        }
    }

    /*
     * Get data form cache
     * TODO expire cache and remove old cache
     */

    private function get_cache($url = '', $cache_path = '') {
        if ($this->cache_result) {
            //Use cache
            $hash = md5($url);
            $hash_path = $cache_path . $hash.'.gz';
            if (file_exists($hash_path)) {
                $gz_content = file_get_contents($hash_path);
                return gzdecode($gz_content);
            } else {
                $data = $this->curl->getCurlCookie($url, $this->use_proxy);
                $gz_data = gzencode($data);
                file_put_contents($hash_path, $gz_data);
                return $data;
            }
        } else {
            //Do not use cache            
            return $this->curl->getCurlCookie($url, $this->use_proxy);
        }
    }

    /*
     * Find data in search page by regexp
     */

    private function find_in_search_page($code) {
        // Get header
        $not_found_title = 'Nothing Found';
        if (preg_match('|<header class="entry-header">[^<]*<h1 class="entry-title">([^<]*)</h1>[^<]*</header>|s', $code, $match)) {
            if ($match[1] == $not_found_title) {
                //Not found
                return array(2);
            }
        }

        // Find data
        if (preg_match('|<h2 class="entry-title">[^<]*<a href="([^"]+)">([^<]*)</a>[^<]*</h2>|s', $code, $match)) {
            //Found first item
            return array(1, array($match[1], $match[2]));
        }

        // Unknow error
        return array(0);
    }

    /*
     * Find data in post page by regexp
     */

    private function find_in_post_page($code) {

        $rating = $rating_info = $info = array();
        // Rating grid
        if (preg_match('|<div class="rating-grid-view">.*<div class="clear content-rating-desc">|Us', $code, $match)) {
            if (preg_match_all('|<div class="hr1">([^<]+)</div>[^<]*<div class="hr2">[^<]*<div class="s([0-9]+)"|s', $match[0], $match_rating)) {
                //Found first item
                for ($i = 0; $i < sizeof($match_rating[1]); $i += 1) {
                    $key = ucfirst(trim($match_rating[1][$i]));
                    $result = trim($match_rating[2][$i]);
                    $rating[$key] = $result;
                }
            }
        }
        //$this->p_r($rating);
        //Description grid
        if (preg_match('|<div class="review-content-desc">(.*</div>)[^<]*</div>[^<]*</div>|Us', $code, $match)) {
            if (preg_match_all('|<div[^>]*><a[^>]*></a><b>([^<]+)</b>([^<]+)</div>|s', $match[0], $match_info)) {
                //Found first item
                for ($i = 0; $i < sizeof($match_info[1]); $i += 1) {
                    $key = trim(str_replace(':', '', $match_info[1][$i]));
                    $result = trim($match_info[2][$i]);
                    $rating_info[$key] = htmlspecialchars($result);
                }
            }
        }
        // $this->p_r($rating_info); 
        //Business info
        if (preg_match('|<div class="business-info">.*</div>[^<]*</div>|Us', $code, $match)) {
            if (preg_match_all('|<div><span>([^<]+)</span>(.*)</div>|Us', $match[0], $match_info)) {
                //$this->p_r($match_info);
                /*
                 *     [1] => Array
                  (
                  [0] => Company:
                  [1] => Writer:
                  [2] => Director:
                  [3] => Producer:
                  [4] => Genre:
                  [5] => Runtime:
                  [6] => Industry Rating:
                  [7] => Starring:
                  [8] => Reviewer:
                  )

                  [2] => Array
                  (
                  [0] =>  20th Century Fox Home Ent.
                  [1] =>  Sam Harper
                  [2] =>  Daniel Stern
                  [3] =>  Robert Harper
                  [4] =>  Children
                  [5] =>  103 min.
                  [6] =>  PG
                  [7] =>  Thomas Ian Nicholas,
                  Gary Busey,
                  Albert Hall,
                  Amy Morton
                  [8] =>  Edwin L. Carpenter
                  )
                 */
                for ($i = 0; $i < sizeof($match_info[1]); $i += 1) {
                    $key = trim(str_replace(':', '', $match_info[1][$i]));
                    $value = trim($match_info[2][$i]);
                    $info[$key] = $value;
                }
            }
        }

        $reliase = '';
        //Reliase
        if (preg_match('|<div class="therelease"><span>[^<]+</span>([^<]+)</div>|', $code, $match)) {
            $reliase = trim($match[1]);
        } else if (preg_match('|<div class="vidrelease"><span>[^<]+</span>([^<]+)</div>|', $code, $match)) {
            $reliase = trim($match[1]);
        }


        // Return array
        return array('rating' => $rating, 'rating_info' => $rating_info, 'info' => $info, 'reliase' => $reliase);
    }

    //UNUSED
    private function get_rating_from_grid($code) {
        // Rating grid
        if (preg_match_all('|<div class="s([0-5]+)"[^>]* title=\'&lt;b&gt;([^\']+)&lt;/b&gt;([^\']+)\'|s', $code, $match_rating)) {
            //Found first item
            //$this->p_r($match_rating);
            /*
              [1] => Array
              (
              [0] => 1
              [1] => 2
              [2] => 1
              [3] => 2
              [4] => 0
              [5] => 1
              )

              [2] => Array
              (
              [0] => Sex:
              [1] => Language:
              [2] => Violence:
              [3] => Drugs:
              [4] => Nudity:
              [5] => Other:
              )

              [3] => Array
              (
              [0] => A couple of innuendos including when the kids mention a girl &quot;is stacked&quot;; a producer tells a kid he needs to be &quot;more sexy&quot; for a commercial he is filming.
              [1] => H-2; S-1; Bu*t-4; Butthead-1; G/OMG-21; &quot;Dear God in Heaven&quot;-1; A few words like &quot;dork&quot; and &quot;moron&quot;; Holy Christmas-1; &quot;You suck&quot; said in jest.
              [2] => A woman punches an obnoxious agent and says maybe she should have killed him.
              [3] => Drinking in a few scenes; the smoking of a cigar.
              [4] => None
              [5] => An agent attempts to trick his client&amp;apos;s mother into signing a contract which benefits himself but the deal falls apart; a boy borrows his dad&amp;apos;s motor for a boat without telling him; the kids take an unsupervised boat ride.
              )

             */
            for ($i = 0; $i < sizeof($match_rating[1]); $i += 1) {
                $key = trim(str_replace(':', '', $match_rating[2][$i]));
                $result = trim($match_rating[1][$i]);
                $desc = trim($match_rating[3][$i]);
                $rating[$key] = array('result' => $result, 'desc' => $desc);
            }
        }
    }

    /*
     * Clear chars
     */

    private function clear_title_arr($title) {
        $ret = array($title);

        //Remove all unicode simbols
        $title = preg_replace('|&#[0-9]{4};|', '', $title);

        if (preg_match_all('/[a-zA-Z0-9&]+/', $title, $match)) {
            $ret = $match[0];
        }
        return $ret;
    }

    private function debug($data) {
        if ($this->debug_on) {
            $this->p_r($data);
        }
    }

    private function p_r($text) {
        print '<pre>';
        print_r($text);
        print '</pre>';
    }

    /*
     * Log
     */

    private function log_err($msg) {
        if (!$this->write_log) {
            return;
        }
        $this->debug($msg);
        $this->log->err($msg);
    }

    private function log_warn($msg) {
        if (!$this->write_log) {
            return;
        }
        $this->debug($msg);
        $this->log->warn($msg);
    }

    private function log_info($msg) {
        if (!$this->write_log) {
            return;
        }
        $this->debug($msg);
        $this->log->info($msg);
    }

    private function log_debug($msg) {
        if (!$this->write_log) {
            return;
        }
        $this->debug($msg);
        $this->log->debug($msg);
    }

    /*
     * Add colums to DB
     */

    public function install() {
        return;
        /*
          dove_date
          dove_link
          dove_rating
          dove_rating_desk
          dove_result
         */

//        $sql_date = "ALTER TABLE `{$this->db['pg_rating']}` ADD `dove_date` int(11) NOT NULL DEFAULT '0'";
//        $sql_link = "ALTER TABLE `{$this->db['pg_rating']}` ADD `dove_link` text DEFAULT NULL";
//        $sql_rating = "ALTER TABLE `{$this->db['pg_rating']}` ADD `dove_rating` text DEFAULT NULL";
//        $sql_desc = "ALTER TABLE `{$this->db['pg_rating']}` ADD `dove_rating_desc` text DEFAULT NULL";
//        $sql_result = "ALTER TABLE `{$this->db['pg_rating']}` ADD `dove_result` varchar(255) NOT NULL DEFAULT ''";
//
//        Pdo_an::db_query($sql_date);
//        Pdo_an::db_query($sql_link);
//        Pdo_an::db_query($sql_rating);
//        Pdo_an::db_query($sql_desc);
//        Pdo_an::db_query($sql_result);
    }

}
