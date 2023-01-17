<?php

class MoviesLinks extends MoviesAbstractDB {

    private $db;
    private $mp;
    private $ma;
    private $ms;
    private $tp;
    private $mch;
    private $mlr = array();
    private $settings;
    private $settings_def;
    private $campaings_mlr = array(
        'familysearch.org' => 'familysearch',
        'forebears.io' => 'forebears',
        'kinopoiskapiunofficial.tech' => 'kinopoisk',
        'douban.com' => 'douban',
        'archive.4plebs.org' => 'forchan',
        'reviews_cron' => 'reviews',
    );
    public $arhive_path = ABSPATH . 'wp-content/uploads/movies_links/arhive/';
    public $export_path = ABSPATH . 'wp-content/uploads/movies_links/export/';

    public function __construct() {
        //Settings
        $this->settings_def = array(
            'parser_user_agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/84.0.4147.105 Safari/537.36',
            'parser_cookie_path' => ABSPATH . 'wp-content/uploads/movies_links_cookies.txt',
            'web_drivers' => '',
            'tor_driver' => '148.251.54.53:8110',
            'tor_get_ip_driver' => '148.251.54.53:8110',
            'tor_ip_h' => 100,
            'tor_ip_d' => 1000,
            'tor_agent' => 0,
        );

        $this->db = array(
            'posts' => 'movies_links_posts',
            'url' => 'movies_links_url',
        );
    }

    public function init() {
        
    }

    public function get_mp() {
        // Get movies parser
        if (!$this->mp) {
            if (!class_exists('MoviesParser')) {
                require_once( MOVIES_LINKS_PLUGIN_DIR . 'MoviesParser.php' );
            }
            $this->mp = new MoviesParser($this);
        }
        return $this->mp;
    }

    public function get_ma() {
        // Get movies parser
        if (!$this->ma) {
            if (!class_exists('MoviesAbstractDBAn')) {
                require_once( MOVIES_LINKS_PLUGIN_DIR . '/db/MoviesAbstractDBAn.php' );
            }
            if (!class_exists('MoviesLinksAn')) {
                require_once( MOVIES_LINKS_PLUGIN_DIR . 'MoviesLinksAn.php' );
            }
            $this->ma = new MoviesLinksAn($this);
        }
        return $this->ma;
    }

    public function get_ms() {
        // Get movies parser
        if (!$this->ms) {
            if (!class_exists('MoviesSearch')) {
                require_once( MOVIES_LINKS_PLUGIN_DIR . 'MoviesSearch.php' );
            }
            $this->ms = new MoviesSearch($this);
        }
        return $this->ms;
    }

    public function get_tp() {
        // Get movies tor parser
        if (!$this->tp) {
            if (!class_exists('TorParser')) {
                require_once( MOVIES_LINKS_PLUGIN_DIR . 'TorParser.php' );
            }
            $this->tp = new TorParser($this);
        }
        return $this->tp;
    }

    public function get_mch() {
        // Get movies custom hook
        if (!$this->mch) {
            if (!class_exists('MoviesCustomHooks')) {
                require_once( MOVIES_LINKS_PLUGIN_DIR . 'MoviesCustomHooks.php' );
            }
            $this->mch = new MoviesCustomHooks($this);
        }
        return $this->mch;
    }

    public function get_campaign_mlr_name($campaign) {
        $title = $campaign->title;
        if (isset($this->campaings_mlr[$title])) {
            return $this->campaings_mlr[$title];
        }
        return '';
    }

    public function get_campaing_mlr($campaign) {
        $cm_key = $this->get_campaign_mlr_name($campaign);


        if ($cm_key) {
            if (isset($this->mlr[$cm_key])) {
                return $this->mlr[$cm_key];
            } else {
                if (!class_exists('MoviesAbstractDBAn')) {
                    require_once( MOVIES_LINKS_PLUGIN_DIR . '/db/MoviesAbstractDBAn.php' );
                }
                if (!class_exists($cm_key)) {
                    require_once( MOVIES_LINKS_PLUGIN_DIR . '/mlr/' . $cm_key . '.php' );
                }

                $cmc = new $cm_key($this);
                $this->mlr[$cm_key] = $cmc;
                return $cmc;
            }
        }
        return array();
    }

    public function get_posts_by_movie_id($mid) {
        $sql = sprintf("SELECT * FROM {$this->db['posts']} WHERE top_movie = %d", (int) $mid);
        $result = $this->db_results($sql);
        return $result;
    }

    public function get_post_by_movie_id_and_cid($mid, $cid) {
        $sql = sprintf("SELECT p.*, u.cid FROM {$this->db['posts']} p INNER JOIN {$this->db['url']} u ON u.id=p.uid WHERE p.top_movie = %d AND u.cid=%d", (int) $mid, (int) $cid);
        $result = $this->db_fetch_row($sql);
        return $result;
    }

    public function get_post_data($mid, $cid) {
        $post = $this->get_post_by_movie_id_and_cid($mid, $cid);
        $unserialise = array();
        if ($post) {
            $options = unserialize($post->options);
            foreach ($options as $key => $value) {
                $unserialise[$key] = base64_decode($value);
            }
            $post->opt_fields = $unserialise;
        }
        return $post;
    }

    public function get_post_options($mid = 0, $fields = array()) {
        $posts = $this->get_posts_by_movie_id($mid);
        $ret = array();

        foreach ($fields as $field) {
            $field_value = '';
            if ($posts) {
                foreach ($posts as $post) {
                    $options = unserialize($post->options);
                    if (isset($options[$field])) {
                        $field_value = base64_decode($options[$field]);
                        break;
                    }
                }
            }

            $ret[$field] = $field_value;
        }
        return $ret;
    }

    public function cron_already_run($cron_name, $wait = 10, $debug = false, $force = false) {
        if ($wait == 0) {
            $wait = 10;
        }

        $curr_time = $this->curr_time();

        // Last run
        $run_key = $this->get_cron_name($cron_name);

        $last_run = (int) $this->get_option($run_key);
        // Already progress
        $progress = $last_run ? $last_run : 0;

        if (!$force && $progress) {
            // Ignore old last update            

            $wait_sec = $wait * 60; // sec
            if ($curr_time < ($progress + $wait_sec)) {
                // Cron already progress;                    
                if ($debug) {
                    print "Cron " . $cron_name . " already progess.";
                }
                return true;
            }
        }
        return false;
    }

    public function register_cron($cron_name) {
        $curr_time = $this->curr_time();
        $run_key = $this->get_cron_name($cron_name);
        $this->update_option($run_key, $curr_time);
    }

    public function unregister_cron($cron_name) {
        $run_key = $this->get_cron_name($cron_name);
        $this->update_option($run_key, 0);
    }

    private function get_cron_name($cron_name) {
        return 'ml_cron_' . $cron_name;
    }

    /*
     * Settings
     */

    public function get_settings() {
        if ($this->settings) {
            return $this->settings;
        }
        // Get settings from options
        $settings = unserialize($this->get_option('movies_links_settings'));
        if ($settings && sizeof($settings)) {
            foreach ($this->settings_def as $key => $value) {
                if (!isset($settings[$key])) {
                    //replace empty settings to default
                    $settings[$key] = $value;
                }
            }
        } else {
            $settings = $this->settings_def;
        }
        $this->settings = $settings;
        return $settings;
    }

    public function update_settings($form) {

        $ss = $this->get_settings();
        foreach ($ss as $key => $value) {
            if (isset($form[$key])) {
                $new_value = $form[$key];
                $ss[$key] = $new_value;
            }
        }

        // Upadate cookie content
        if (isset($form['parser_cookie_text'])) {
            $cookie_path = $ss['parser_cookie_path'];
            if (file_exists($cookie_path)) {
                unlink($cookie_path);
            }
            file_put_contents($cookie_path, $form['parser_cookie_text']);
        }
        if (isset($form['web_drivers'])) {
            $ss['web_drivers'] = base64_encode($new_value);
        }

        $this->settings = $ss;
        if (function_exists('update_option')) {
            $this->update_option('movies_links_settings', serialize($ss));
        }
    }

    public function format_time($timestamp) {
        // Get time difference and setup arrays
        $difference = time() - $timestamp;
        $periods = array("second", "minute", "hour", "day", "week", "month", "years");
        $lengths = array("60", "60", "24", "7", "4.35", "12");

        // Past or present
        if ($difference >= 0) {
            $ending = "ago";
        } else {
            $difference = -$difference;
            $ending = "to go";
        }

        // Figure out difference by looping while less than array length
        // and difference is larger than lengths.
        $arr_len = count($lengths);
        for ($j = 0; $j < $arr_len && $difference >= $lengths[$j]; $j++) {
            $difference /= $lengths[$j];
        }

        // Round up     
        $difference = round($difference);

        // Make plural if needed
        if ($difference != 1) {
            $periods[$j] .= "s";
        }

        // Default format
        $text = "$difference $periods[$j] $ending";

        // over 24 hours
        if ($j > 2) {
            // future date over a day formate with year
            if ($ending == "to go") {
                if ($j == 3 && $difference == 1) {
                    $text = "Tomorrow at " . date("g:i a", $timestamp);
                } else {
                    $text = date("F j, Y \a\\t g:i a", $timestamp);
                }
                return $text;
            }

            if ($j == 3 && $difference == 1) { // Yesterday
                $text = "Yesterday at " . date("g:i a", $timestamp);
            } else if ($j == 3) { // Less than a week display -- Monday at 5:28pm
                $text = date("l \a\\t g:i a", $timestamp);
            } else if ($j < 6 && !($j == 5 && $difference == 12)) { // Less than a year display -- June 25 at 5:23am
                $text = date("F j \a\\t g:i a", $timestamp);
            } else { // if over a year or the same month one year ago -- June 30, 2010 at 5:34pm
                $text = date("F j, Y \a\\t g:i a", $timestamp);
            }
        }

        return $text;
    }

}
