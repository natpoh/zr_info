<?php

class MoviesLinks extends MoviesAbstractDB {

    private $db;
    private $mp;
    private $ma;
    private $ms;
    private $mch;
    private $mlr = array();
    private $settings;
    private $settings_def;
    private $campaings_mlr = array('familysearch.org' => 'familysearch');
    public $arhive_path = ABSPATH . 'wp-content/uploads/movies_links/arhive/';

    public function __construct() {
        //Settings
        $this->settings_def = array(
            'parser_user_agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/84.0.4147.105 Safari/537.36',
            'parser_cookie_path' => ABSPATH . 'wp-content/uploads/movies_links_cookies.txt',
            'web_drivers' => '',
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

    public function get_posts_by_movie_id($id) {
        $sql = sprintf("SELECT * FROM {$this->db['posts']} WHERE top_movie = %d", (int) $id);
        $result = $this->db_results($sql);
        return $result;
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

    /*
     * Settings
     */

    public function get_settings() {
        if ($this->settings) {
            return $this->settings;
        }
        // Get settings from options
        $settings = unserialize(get_option('movies_links_settings'));
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
        update_option('movies_links_settings', serialize($ss));
    }

}
