<?php

/*
 * Critic matic class. 
 * Used for manage critic posts
 */

class CriticMatic extends AbstractDB {

    public $perpage = 30;
    private $db = array();
    public $user_can;
    private $ac;
    private $af;
    private $cav;
    private $cc;
    private $cf;
    private $cp;
    private $cs;
    private $ct;
    private $ma;
    private $ms;
    private $mw;
    private $ts;
    private $uc;
    private $si;
    private $uf;
    private $mac;
    private $wl;
    private $mdirs;
    private $wpu;

    /*
     * Posts
     */
    public $main_tabs = array(
        'home' => 'Posts overview',
        'details' => 'Posts Details',
        'meta' => 'Meta',
        'log' => 'Log',
        'add' => 'Add post',
    );
    public $post_category = array(
        0 => 'None',
        1 => 'Proper Review',
        2 => 'Contains Mention',
        3 => 'Related Article'
    );
    public $post_type = array(
        0 => 'Import',
        1 => 'Feed',
        2 => 'Manual',
        3 => 'Parser',
        4 => 'Transcript',
        5 => 'MoviesLinks',
    );
    public $post_status = array(
        1 => 'Publish',
        0 => 'Draft',
        2 => 'Trash'
    );
    public $post_view_type = array(
        0 => 'Default',
        1 => 'Youtube',
        2 => 'Odysee',
        3 => 'Bitchute'
    );
    public $post_view_type_url = array(
        'www.youtube.com' => 1,
        'odysee.com' => 2,
        'www.bitchute.com' => 3
    );
    public $post_meta_status = array(
        1 => 'With meta',
        0 => 'No meta',
    );
    public $post_tabs = array(
        'home' => 'View',
        'edit' => 'Edit',
        'trash' => 'Trash',
    );
    public $post_update = array(
        1 => 'Today',
        7 => 'Last week',
        30 => 'Last Mounth',
    );
    public $sort_pages = array('author_name', 'free', 'ftype', 'id', 'ip', 'date', 'date_add', 'title', 'last_update', 'update_interval', 'name', 'pid', 'slug', 'status', 'type', 'weight', 'wp_uid', 'show_type');

    /*
     * Authors
     */
    public $author_type = array(
        /* 0 => 'Staff', */
        1 => 'Critic',
        2 => 'Audience'
    );
    public $author_status = array(
        -1 => 'All',
        1 => 'Publish',
        0 => 'Draft',
        2 => 'Trash'
    );
    public $author_show_type = array(
        0 => 'All',
        1 => 'Hide in Home page',
    );
    public $pro_author_avatar = array(
        0 => 'None',
        1 => 'Exist',
    );
    public $authors_tabs = array(
        'home' => 'Authors list',
        'add' => 'Add a new author',
    );
    public $author_tabs = array(
        'home' => array('title' => 'View', 'sync_view' => 0),
        'posts' => array('title' => 'Posts', 'sync_view' => 0),
        'feeds' => array('title' => 'Feeds', 'sync_view' => 1),
        'parsers' => array('title' => 'Parsers', 'sync_view' => 1),
        'edit' => array('title' => 'Edit', 'sync_view' => 0),
        'trash' => array('title' => 'Trash', 'sync_view' => 0),
    );
    public $author_av_types = array(
        0 => 'Tomato',
        1 => 'Upload',
    );
    /*
     * Tags
     */
    public $tag_status = array(
        -1 => 'All',
        1 => 'Publish',
        0 => 'Draft',
        2 => 'Trash'
    );
    public $tags_tabs = array(
        'home' => 'Tags list',
        'add' => 'Add a new tag',
    );
    public $tag_tabs = array(
        'home' => 'View',
        'edit' => 'Edit',
        'trash' => 'Trash',
    );
    /*
     * Movies
     */
    public $movie_state = array(
        1 => 'Approved',
        2 => 'Auto',
        3 => 'Auto (ML)',
        0 => 'Unapproved',
    );
    public $movie_rating = array(
        0 => 'Zero rating',
        1 => 'Non zero rating',
    );
    public $movie_type = array(
        'movie' => 'Movie',
        'tvseries' => 'TV',
        'videogame' => 'VideoGame'
    );
    public $movie_tabs = array(
        'home' => 'View',
        'actors' => 'Actors',
        'index' => 'Index',
    );

    /* Audience */
    public $audience_tabs = array(
        'home' => 'Posts',
        'queue' => 'Queue',
        'iplist' => 'IP list'
    );
    public $ip_status = array(
        0 => 'None',
        1 => 'White',
        2 => 'Gray',
        3 => 'Black'
    );
    public $def_rating = array(
        'r' => 0,
        'h' => 0,
        'p' => 0,
        'm' => 0,
        'a' => 0,
        'l' => 0,
        'g' => 0,
        'v' => 0,
        'ip' => '',
    );
    private $settings;
    private $settings_def;
    // Geo data
    private $reader;
    private $reader_city;
    private $geoip;
    /* Sync */
    public $sync_status = 0;
    public $sync_client = true;
    public $sync_server = false;
    public $sync_data = true;
    public $sync_status_types = array(
        1 => 'Server',
        2 => 'Client',
    );

    public function __construct() {
        $table_prefix = DB_PREFIX_WP_AN;
        $this->db = array(
            // CM
            'posts' => $table_prefix . 'critic_matic_posts',
            'posts_links' => $table_prefix . 'critic_matic_links',
            'meta' => $table_prefix . 'critic_matic_posts_meta',
            'rating' => $table_prefix . 'critic_matic_rating',
            'tags' => $table_prefix . 'critic_matic_tags',
            'tag_meta' => $table_prefix . 'critic_matic_tag_meta',
            'authors' => $table_prefix . 'critic_matic_authors',
            'authors_meta' => $table_prefix . 'critic_matic_authors_meta',
            'movies_meta' => $table_prefix . 'critic_movies_meta',
            'ip' => $table_prefix . 'critic_matic_ip',
            'thumbs' => $table_prefix . 'critic_matic_thumbs',
            // CF
            'feed_meta' => $table_prefix . 'critic_feed_meta',
            // TS
            'transcriptions' => $table_prefix . 'critic_transcritpions',
            'reviews_rating' => 'meta_reviews_rating',
            'critic_crowd' => 'data_critic_crowd',
        );
        $this->timer_start();

        $this->get_perpage();

        //Settings
        $this->settings_def = array(
            'parser_user_agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/84.0.4147.105 Safari/537.36',
            'parser_proxy' => '',
            'parser_cookie_path' => ABSPATH . 'wp-content/uploads/critic_parser_cookies.txt',
            'parser_gdk' => '',
            'parser_gapp' => '',
            'parser_arhive_async' => 0,
            'audience_post_status' => 1,
            'posts_type_1' => 1,
            'posts_type_2' => 0,
            'posts_type_3' => 0,
            'posts_rating' => 0,
            'audience_desc' => array(
                'vote' => "<strong>「&quot;Boycott Or Support&quot;」</strong> lets people know if they should avoid watching the film even if it's free, if they should <a class=&quot;window_open&quot; href=&quot;#https://zeitgeistreviews.com/culture_warrior/&quot; target=&quot;_blank&quot; title=&quot;How to torrent films.&quot;>torrent</a> the film, or if they should spend money watching it to support the creators.",
                'rating' => "<strong>「&quot;Worthwhile Content&quot;」</strong> rates the redeeming aspects of the film.",
                'hollywood' => 'Overall Hollywood BS',
                'patriotism' => "<strong>「&quot;Neo-Marxism&quot;」</strong>, (sometimes referred to as <a class=&quot;window_open&quot; href=&quot;#https://infogalactic.com/info/Cultural_Marxism&quot; title=&quot;Link to the Vox Day wikipedia alternative site explaining what cultural marxism is.&quot; target=&quot;_blank&quot;>&quot;Cultural Marxism&quot;</a>), rates&nbsp;the&nbsp;amount&nbsp;of fanatic egalitarianism in a film. Particularly in regard to criticism of<a title=&quot;30 second YouTube clip that shows the not-so-suble criticism of America in James Cameron's 'Avatar'&quot; class=&quot;window_open&quot; href=&quot;#https://www.youtube.com/watch?v=5d5WArztDgo&amp;amp;feature=youtu.be&amp;amp;t=4m49s&quot; target=&quot;_blank&quot; rel=&quot;noopener noreferrer&quot;> nationalism</a> and<a title=&quot;IB Times article about a short Fox News clip criticizing 'The Lego Movie.'&quot; class=&quot;window_open&quot; href=&quot;#http://www.ibtimes.co.uk/fox-news-takes-aim-lego-movie-being-anti-capitalist-video-1435808&quot; target=&quot;_blank&quot; rel=&quot;noopener noreferrer&quot;> capitalism</a>.",
                'misandry' => "<strong>「&quot;Misandry&quot;」</strong>&nbsp;rates&nbsp;the&nbsp;amount&nbsp; of feminism in a film. Particularly when <a title=&quot;YouTube video of Gavin Mcinnes giving examples of 'cuck-mercials.'&quot; class=&quot;window_open&quot; href=&quot;#https://www.youtube.com/watch?v=5PaRn2-YfTI&quot; target=&quot;_blank&quot; rel=&quot;noopener noreferrer&quot;>manhood is disparaged</a>, rather than simply having strong female characters.",
                'affirmative' => "<strong>「&quot;Affirmative Action&quot;」</strong>&nbsp;rates&nbsp;how much &quot;<a title=&quot;Steven Crowder article about how even the African American cast of 'Blackish' are getting sick and tired of the redundant questions about diversity they get all the time.&quot; class=&quot;window_open&quot; href=&quot;#http://www.louderwithcrowder.com/black-ish-creator-im-tired-of-talking-about-diversity/&quot; target=&quot;_blank&quot; rel=&quot;noopener noreferrer&quot;>diversity</a>&quot; is being pushed. ( Not true diversity, but the <a class=&quot;window_open&quot; href=&quot;#https://archive.li/DPrE1&quot; target=&quot;_blank&quot; title=&quot;Hella diverse cast of black panther.&quot; >anti-White</a> checklist kind.)",
                'lgbtq' => "<strong>「&quot;LGBTQrstuvwxyz&quot;」</strong>&nbsp;rates&nbsp;the&nbsp;amount&nbsp;of <a title=&quot;Buzzfeed article celebrating the transgender character thrown into the 'Mr. Robot' script to complete their diversity bingo chart.&quot; class=&quot;window_open&quot; href=&quot;#http://www.buzzfeed.com/arianelange/mr-robot-diversity&quot; target=&quot;_blank&quot; rel=&quot;noopener noreferrer&quot;>non-tradional&nbsp;sexuality</a>&nbsp;depicted. Whether this is positive or negative is up to the user. For example, <a class=&quot;window_open&quot; href=&quot;#https://zeitgeistreviews.com/critics/1671/&quot; target=&quot;_blank&quot; title=&quot;Link to reviews by Armond, in our database.&quot;>Armond White</a> is an openly gay conservative critic filled throughout our database.",
                'god' => "<strong>「&quot;Anti-God Themes&quot;」</strong>&nbsp;rates&nbsp;the&nbsp;amount&nbsp;of slander towards God and/or <a title=&quot;Hollywood Reporter article about Pat Boone explaining why he boycotts SNL, and thinks they're cowards for not criticizing Islam as they do with 'God's Not Dead 2.'&quot; class=&quot;window_open&quot; href=&quot;#http://www.hollywoodreporter.com/news/pat-boone-accuses-snl-anti-885253&quot; target=&quot;_blank&quot; rel=&quot;noopener noreferrer&quot;>Christian</a> ethics. As with all these ratings, whether this is positive or negative is up to the reviewer. If you're a Pagan Alt Righter or Atheist Anarcho Capitalist, this may be good in your eyes.",
                'email' => "Create a password or enter an existing one.",
                'name' => "Enter your name or leave the field blank."
            ),
            'audience_cron_path' => '',
            'audience_post_edit' => 0,
            'sync_status' => 1,
            'an_weightid' => 0,
            'an_verdict_type' => 'p',
            'audience_unique' => 0,            
            'audience_top_unique' => 0,
            'score_avatar' => 50,
            'score_filter_image' => 0,
            'actors_star_ss' => '',
            'actors_main_ss' => '',
            'actors_star_wait' => 30,
            'actors_main_wait' => 30,
            'critics_unique' => 0,
        );

        $this->sync_data = DB_SYNC_DATA == 1 ? true : false;
        $this->sync_status = DB_SYNC_MODE;
        $this->sync_client = DB_SYNC_MODE == 2 ? true : false;
        $this->sync_server = DB_SYNC_MODE == 1 ? true : false;

        if ($this->sync_client) {
            unset($this->author_tabs['edit']);
        }
    }

    public function get_ac() {
        // Get actors country
        if (!$this->ac) {
            if (!class_exists('ActorsCountry')) {
                require_once( CRITIC_MATIC_PLUGIN_DIR . 'ActorsCountry.php' );
            }

            $this->ac = new ActorsCountry($this);
        }
        return $this->ac;
    }

    public function get_uc() {
        // Get UserCarma
        if (!$this->uc) {
            if (!class_exists('UserCarma')) {
                if (!class_exists('AbstractDBWp')) {
                    require_once( CRITIC_MATIC_PLUGIN_DIR . 'db/AbstractDBWp.php' );
                }
                require_once( CRITIC_MATIC_PLUGIN_DIR . 'UserCarma.php' );
            }

            $this->uc = new UserCarma($this);
        }
        return $this->uc;
    }

    public function get_cav() {
        // Get CriticAvatars
        if (!$this->cav) {
            if (!class_exists('CriticAvatars')) {
                if (!class_exists('AbstractDBWp')) {
                    require_once( CRITIC_MATIC_PLUGIN_DIR . 'db/AbstractDBWp.php' );
                }
                require_once( CRITIC_MATIC_PLUGIN_DIR . 'CriticAvatars.php' );
            }

            $this->cav = new CriticAvatars($this);
        }
        return $this->cav;
    }

    public function get_mw() {
        // Get MoviesWeight
        if (!$this->mw) {
            if (!class_exists('MoviesWeight')) {
                require_once( CRITIC_MATIC_PLUGIN_DIR . 'MoviesWeight.php' );
            }

            $this->mw = new MoviesWeight($this);
        }
        return $this->mw;
    }

    public function get_ms() {
        // Get MoviesSimpson
        if (!$this->ms) {
            if (!class_exists('MoviesSimpson')) {
                require_once( CRITIC_MATIC_PLUGIN_DIR . 'MoviesSimpson.php' );
            }

            $this->ms = new MoviesSimpson($this);
        }
        return $this->ms;
    }

    public function get_mac() {
        // Get MoviesActors
        if (!$this->mac) {
            if (!class_exists('MoviesActors')) {
                require_once( CRITIC_MATIC_PLUGIN_DIR . 'MoviesActors.php' );
            }

            $this->mac = new MoviesActors($this);
        }
        return $this->mac;
    }

    public function get_mdirs() {
        // Get MoviesDirectors
        if (!$this->mdirs) {
            if (!class_exists('MoviesActors')) {
                require_once( CRITIC_MATIC_PLUGIN_DIR . 'MoviesActors.php' );
            }

            $this->mdirs = new MoviesDirectors($this);
        }
        return $this->mdirs;
    }

    public function get_cp() {
        // Get CriticParser
        if (!$this->cp) {
            if (!class_exists('CriticParser')) {
                require_once( CRITIC_MATIC_PLUGIN_DIR . 'CriticParser.php' );
            }
            $this->cp = new CriticParser($this);
        }
        return $this->cp;
    }

    public function get_cc() {
        // Get ClearCommetns
        if (!$this->cc) {
            if (!class_exists('ClearComments')) {
                require_once( CRITIC_MATIC_PLUGIN_DIR . 'ClearComments.php' );
            }
            $this->cc = new ClearComments($this);
        }
        return $this->cc;
    }

    public function get_cf() {
        // Get CriticFeeds
        if (!$this->cf) {
            if (!class_exists('CriticFeeds')) {
                if (!class_exists('AbstractDBWp')) {
                    require_once( CRITIC_MATIC_PLUGIN_DIR . 'db/AbstractDBWp.php' );
                }
                require_once( CRITIC_MATIC_PLUGIN_DIR . 'CriticFeeds.php' );
            }

            $this->cf = new CriticFeeds($this);
        }
        return $this->cf;
    }

    public function get_cs() {
        // Get CriticSearch
        if (!$this->cs) {
            if (!class_exists('CriticSearch')) {
                require_once( CRITIC_MATIC_PLUGIN_DIR . 'CriticSearch.php' );
            }
            $this->cs = new CriticSearch($this);
        }
        return $this->cs;
    }

    public function get_ts() {
        if (!$this->ts) {
            //init 
            if (!class_exists('CriticMaticTrans')) {
                require_once( CRITIC_MATIC_PLUGIN_DIR . 'CriticMaticTrans.php' );
            }
            $this->ts = new CriticMaticTrans($this);
        }
        return $this->ts;
    }

    public function get_ct() {
        if (!$this->ct) {
            //init 
            if (!class_exists('CriticTransit')) {
                require_once( CRITIC_MATIC_PLUGIN_DIR . 'CriticTransit.php' );
            }
            $this->ct = new CriticTransit($this);
        }
        return $this->ct;
    }

    public function get_af() {
        if (!$this->af) {
            //init 
            if (!class_exists('AnalyticsFront')) {
                require_once( CRITIC_MATIC_PLUGIN_DIR . 'AnalyticsSearch.php' );
                require_once( CRITIC_MATIC_PLUGIN_DIR . 'AnalyticsFront.php' );
            }
            $this->af = new AnalyticsFront($this);
        }
        return $this->af;
    }

    public function get_ma() {
        if (!$this->ma) {
            // init cma
            if (!class_exists('MoviesAn')) {
                require_once( CRITIC_MATIC_PLUGIN_DIR . 'MoviesAn.php' );
            }
            $this->ma = new MoviesAn($this);
        }
        return $this->ma;
    }

    public function get_si() {
        // Get site img
        if (!$this->si) {
            if (!class_exists('SiteImg')) {
                require_once( CRITIC_MATIC_PLUGIN_DIR . 'SiteImg.php' );
            }
            $this->si = new SiteImg($this);
        }
        return $this->si;
    }

    public function get_uf() {
        // Get user filters
        if (!$this->uf) {
            if (!class_exists('UserFilters')) {
                require_once( CRITIC_MATIC_PLUGIN_DIR . 'UserFilters.php' );
            }
            $this->uf = new UserFilters($this);
        }
        return $this->uf;
    }

    public function get_wl() {
        if (!$this->wl) {
            if (!class_exists('WatchList')) {
                require_once( CRITIC_MATIC_PLUGIN_DIR . 'WatchList.php' );
            }
            $this->wl = new WatchList($this->cm);
        }
        return $this->wl;
    }

    public function get_wpu() {
        if (!$this->wpu) {
            if (!class_exists('AbstractDBWp')) {
                require_once( CRITIC_MATIC_PLUGIN_DIR . 'db/AbstractDBWp.php' );
            }
            if (!class_exists('WpUser')) {
                require_once( CRITIC_MATIC_PLUGIN_DIR . 'WpUser.php' );
            }
            $this->wpu = new WpUser($this->cm);
        }
        return $this->wpu;
    }

    public function get_current_user($cache = true) {
        $id = 'user';
        if ($cache) {
            static $dict;
            if (is_null($dict)) {
                $dict = array();
            }

            if (isset($dict[$id])) {
                return $dict[$id];
            }
        }

        if (function_exists('wp_get_current_user')) {
            $user = wp_get_current_user();
        } else {
            $user = new stdClass();
            $user->ID = 0;

            $wpu = $this->get_wpu();
            $user_id = $wpu->get_current_user();

            if ($user_id) {
                $user = $wpu->user;
            }
        }
        if ($cache) {
            $dict[$id] = $user;
        }
        return $user;
    }

    /*
     * Hooks
     */

    public function hook_update_post($id) {
        $pa = $this->get_post_and_author($id);
        $author_type = $pa->author_type;

        // Audience. Calculate movie rating
        if ($author_type == 2) {
            $movies_meta = $this->get_movies_data($id);

            if ($movies_meta && sizeof($movies_meta)) {
                !class_exists('PgRatingCalculate') ? include ABSPATH . "analysis/include/pg_rating_calculate.php" : '';

                //fid, type, state, rating 
                foreach ($movies_meta as $meta) {
                    PgRatingCalculate::rwt_audience($meta->fid, 1, 1);
                    PgRatingCalculate::CalculateRating('', $meta->fid, 0, 1); ///hook_update_post
                    PgRatingCalculate::add_movie_rating($meta->fid);
                }
            }

            // Clear comments
            $cc = $this->get_cc();
            $cc->check_post($pa);
        } else {
            // Find movies from critic
            if ($pa->top_movie == 0 && $pa->status == 1 && $this->sync_server) {
                $cs = $this->get_cs();
                $cs->find_movies_and_reset_meta($id);
            }
        }
    }

    /*
     * Posts get
     */

    public function get_post($id, $fm = false, $ts = false) {

        $cid_get = '';
        $cid_inner = '';
        if ($fm) {
            $cid_get = ", fm.cid AS fmcid";
            $cid_inner = " LEFT JOIN {$this->db['feed_meta']} fm ON fm.pid = p.id";
        }

        $ts_get = '';
        $ts_inner = '';
        if ($ts) {
            $ts_get = ", t.status as tstatus, t.content as tcontent";
            $ts_inner = " LEFT JOIN {$this->db['transcriptions']} t ON t.pid = p.id";
        }

        $where = sprintf(" WHERE p.id=%d", (int) $id);

        $sql = "SELECT p.id, p.date, p.date_add, p.status, p.type, p.link_hash, p.link, p.title, p.content, p.top_movie, p.top_rating, p.blur, p.view_type, p.link_id, am.aid" . $cid_get . $ts_get
                . " FROM {$this->db['posts']} p"
                . " LEFT JOIN {$this->db['authors_meta']} am ON am.cid = p.id"
                . $cid_inner . $ts_inner . $where;

        $result = $this->db_fetch_row($sql);
        return $result;
    }

    public function get_post_name_by_id($id, $cache = true) {
        //Get from cache
        if ($cache) {
            static $dict;
            if (is_null($dict)) {
                $dict = array();
            }

            if (isset($dict[$id])) {
                return $dict[$id];
            }
        }

        $sql = sprintf("SELECT title FROM {$this->db['posts']} WHERE id=%d", (int) $id);
        $result = $this->db_get_var($sql);

        if ($cache) {
            $dict[$id] = $result;
        }
        return $result;
    }

    public function get_post_cache($id, $cache = true) {
        //Get from cache
        if ($cache) {
            static $dict;
            if (is_null($dict)) {
                $dict = array();
            }

            if (isset($dict[$id])) {
                return $dict[$id];
            }
        }

        $sql = sprintf("SELECT * FROM {$this->db['posts']} WHERE id=%d", (int) $id);
        $result = $this->db_fetch_row($sql);

        if ($cache) {
            $dict[$id] = $result;
        }
        return $result;
    }

    public function get_post_and_author($id) {
        $sql = sprintf("SELECT p.id, p.date, p.date_add, p.status, p.type, p.link_hash, p.link, p.title, p.content, p.top_movie, p.blur, p.link_id, "
                . "a.id AS aid, a.name AS author_name, a.type AS author_type, a.options AS author_options, a.last_upd AS author_last_upd, a.date_add AS author_date_add "
                . "FROM {$this->db['posts']} p "
                . "INNER JOIN {$this->db['authors_meta']} am ON am.cid = p.id "
                . "INNER JOIN {$this->db['authors']} a ON a.id = am.aid "
                . "WHERE p.id=%d", (int) $id);
        $result = $this->db_fetch_row($sql);
        return $result;
    }

    public function get_post_top_movie($id) {
        $sql = sprintf("SELECT p.top_movie "
                . "FROM {$this->db['posts']} p "
                . "WHERE p.id=%d", (int) $id);
        $result = $this->db_get_var($sql);
        return $result;
    }

    public function get_post_by_link_hash($link_hash) {
        $sql = sprintf("SELECT * FROM {$this->db['posts']} WHERE link_hash = '%s'", $link_hash);
        $result = $this->db_fetch_row($sql);
        return $result;
    }

    public function get_post_by_link_hash_type($link_hash, $include_type = array(), $exclude_type = array()) {

        $inc_type = '';
        if ($include_type) {
            $inc_type = ' AND type IN(' . implode(',', $include_type) . ')';
        }
        $ex_type = '';
        if ($exclude_type) {
            $ex_type = ' AND type NOT IN(' . implode(',', $exclude_type) . ')';
        }

        $sql = sprintf("SELECT * FROM {$this->db['posts']} WHERE link_hash = '%s'" . $inc_type . $ex_type, $link_hash);
        $result = $this->db_fetch_row($sql);
        return $result;
    }

    public function get_post_type($type) {
        return isset($this->post_type[$type]) ? $this->post_type[$type] : 'None';
    }

    public function get_post_status($status) {
        return isset($this->post_status[$status]) ? $this->post_status[$status] : 'None';
    }

    public function get_post_category_name($id) {
        $name = $this->post_category[$id];
        return $name;
    }

    public function get_posts($q_req = array(), $page = 1, $perpage = 20, $orderby = '', $order = 'ASC', $count = false, $content_after = false) {
        $q_def = array(
            'status' => -1,
            'cid' => -1,
            'type' => -1,
            'aid' => 0,
            'meta_type' => -1,
            'author_type' => -1,
            'view_type' => -1,
            'ts' => -1,
            'post_update' => -1,
            'post_date' => -1,
        );

        $q = array();
        foreach ($q_def as $key => $value) {
            $q[$key] = isset($q_req[$key]) ? $q_req[$key] : $value;
        }

        // Custom status
        $status_trash = 2;
        $status_query = " WHERE p.status != " . $status_trash;
        if ($q['status'] != -1) {
            $status_query = " WHERE p.status = " . (int) $q['status'];
        }

        // Custom date update
        $and_date_add = '';
        if ($q['post_update'] != -1) {
            $date_update = $this->curr_time() - (((int) $q['post_update']) * 86400);
            $and_date_add = sprintf(" AND p.date_add>%d", $date_update);
        }

        // Custom date 
        $and_date = '';
        if ($q['post_date'] != -1) {
            $date_add = $this->curr_time() - (((int) $q['post_date']) * 86400);
            $and_date = sprintf(" AND p.date>%d", $date_add);
        }

        // Feed company id
        $cid_inner = $cid_and = $cid_get = '';
        if ($q['cid'] != -1) {
            $cid_get = ", fm.cid AS fmcid";
            if ($q['cid'] > 0) {
                $cid_inner = " INNER JOIN {$this->db['feed_meta']} fm ON fm.pid = p.id";
                $cid_and = sprintf(" AND fm.cid=%d", (int) $q['cid']);
            } else {
                $cid_inner = " LEFT JOIN {$this->db['feed_meta']} fm ON fm.pid = p.id";
            }
        }

        // Author filter
        $aid_and = '';
        if ($q['aid'] > 0) {
            $aid_and = sprintf(" AND am.aid = %d", (int) $q['aid']);
        }

        //Post type filter
        $type_and = '';
        if ($q['type'] != -1) {
            $type_and = sprintf(" AND p.type =%d", (int) $q['type']);
        }

        // View type filter
        $view_type_and = '';
        if ($q['view_type'] != -1) {
            $view_type_and = sprintf(" AND p.view_type =%d", (int) $q['view_type']);
        }

        // Transcriptions
        $ts_get = '';
        $ts_inner = '';
        $ts_and = '';
        if ($q['ts'] != -1) {
            $ts_inner = " LEFT JOIN {$this->db['transcriptions']} t ON t.pid = p.id";
            if ($q['ts'] == 10) {
                $ts_and = " AND t.id IS NULL";
            } else {
                $ts_and = sprintf(" AND t.status =%d", (int) $q['ts']);
            }
            $ts_get = ", t.status AS tstatus, t.content AS tcontent";
        }

        // Author type
        $atype_inner = '';
        $atype_and = '';
        if ($q['author_type'] != -1) {
            $atype_inner = " INNER JOIN {$this->db['authors']} a ON a.id = am.aid";
            $atype_and = sprintf(" AND a.type = %d", $q['author_type']);
        }

        // Meta type filter
        $meta_type_and = '';
        if ($q['meta_type'] != -1) {
            if ($q['meta_type'] == 1) {
                $meta_type_and = " AND p.top_movie != 0";
            } else {
                $meta_type_and = " AND p.top_movie = 0";
            }
        }


        //Sort
        $and_orderby = '';
        $limit = '';
        if (!$count) {

            if ($orderby && in_array($orderby, $this->sort_pages)) {
                $and_orderby = ' ORDER BY ' . $orderby;
                if ($order) {
                    $and_orderby .= ' ' . $order;
                }
            } else {
                $and_orderby = " ORDER BY p.id DESC";
            }

            $page -= 1;
            $start = $page * $perpage;

            if ($perpage > 0) {
                $limit = " LIMIT $start, " . $perpage;
            }

            if (!$content_after) {
                $select = " p.id, p.date, p.date_add, p.status, p.type, p.link_hash, p.link, p.title, p.content, p.top_movie, p.blur, p.view_type,p.link_id, am.aid" . $cid_get . $ts_get;
            } else {
                $select = " p.id, am.aid" . $cid_get . $ts_get;
            }
        } else {
            $select = " COUNT(p.id)";
        }

        $sql = "SELECT" . $select
                . " FROM {$this->db['posts']} p"
                . " LEFT JOIN {$this->db['authors_meta']} am ON am.cid = p.id"
                . $atype_inner . $cid_inner . $ts_inner . $status_query . $cid_and . $and_date_add . $and_date . $aid_and . $type_and . $view_type_and . $ts_and . $meta_type_and . $atype_and . $and_orderby . $limit;

        if (!$count) {
            //  print $sql;
            $result = $this->db_results($sql);
            $total = array();
            if ($content_after && $result) {
                $items = array();
                foreach ($result as $item) {
                    $items[$item->id] = $item;
                }
                $ids = array_keys($items);
                $select = " p.id, p.date, p.date_add, p.status, p.type, p.link_hash, p.link, p.title, p.content, p.top_movie, p.blur, p.view_type, p.link_id";
                $sql = "SELECT" . $select . " FROM {$this->db['posts']} p WHERE id IN(" . implode(",", $ids) . ")";

                $content = $this->db_results($sql);

                foreach ($content as $item) {
                    foreach ($items[$item->id] as $key => $value) {
                        $item->$key = $value;
                    }
                    $total[] = $item;
                }
                $result = $total;
            }
        } else {
            $result = $this->db_get_var($sql);
        }
        return $result;
    }

    public function get_posts_last_update($type = -1) {

        //Post type filter
        $type_and = '';
        if ($type != -1) {
            $type_and = sprintf(" AND type =%d", (int) $type);
        }

        $sql = "SELECT date_add FROM {$this->db['posts']} WHERE id>0" . $type_and . " ORDER by date_add DESC limit 1";

        $result = $this->db_get_var($sql);
        return $result;
    }

    public function post_actions($exclude = array()) {
        foreach ($this->post_tabs as $key => $value) {
            if (in_array($key, $exclude)) {
                continue;
            }
            $feed_actions[$key] = array('title' => $value);
        }
        return $feed_actions;
    }

    public function get_post_count($q_req = array()) {
        return $this->get_posts($q_req, $page = 1, 1, '', '', true);
    }

    public function get_post_type_count($q_req = array(), $types = array(), $custom_type = '') {
        $status = -1;
        $count = $this->get_post_count($q_req);
        $states = array(
            '-1' => array(
                'title' => 'All',
                'count' => $count
            )
        );
        $q_req_custom = $q_req;

        foreach ($types as $key => $value) {
            $q_req_custom[$custom_type] = $key;
            $states[$key] = array(
                'title' => $value,
                'count' => $this->get_post_count($q_req_custom));
        }
        return $states;
    }

    public function post_edit_validate($form_state) {

        if (isset($form_state['trash'])) {
            // Trash
        } else if (isset($form_state['add_post'])) {
            if ($form_state['title'] == '') {
                return __('Enter the title');
            }

            if ($form_state['link'] == '') {
                return __('Enter the link');
            }

            if ($form_state['author_id'] == '') {
                return __('Select the author');
            }
        } else {
            // Edit
            if ($form_state['title'] == '') {
                return __('Enter the title');
            }

            /* if ($form_state['link'] == '') {
              return __('Enter the link');
              } */

            /* if ($form_state['author'] == '') {
              return __('Select the author');
              } */
        }

        $nonce = wp_verify_nonce($_POST['critic-feeds-nonce'], 'critic-feeds-options');
        if (!$nonce) {
            return __('Error validate nonce');
        }

        return true;
    }

    /*
     * Posts set
     * 
     * type:
      0 => 'Import',
      1 => 'Feed',
      2 => 'Manual'
     */

    public function add_post($date = 0, $type = 0, $link = '', $title = '', $content = '', $top_movie = 0, $status = 1, $view_type = 0, $blur = 0, $sync = true) {
        $link_hash = '';
        $link_id = 0;
        if ($link) {
            $link_hash = $this->link_hash($link);
            //Check the post already in db
            if ($this->get_post_by_link_hash($link_hash)) {
                return 0;
            }

            $site_name = $this->clean_site_name($link);
            $link_id = $this->get_or_create_post_link_by_name($site_name);
        }

        $date_add = $this->curr_time();

        //Clear UTF8
        #$content = $this->clear_utf8($content);


        $data = array(
            'date' => $date,
            'date_add' => $date_add,
            'status' => $status,
            'type' => $type,
            'blur' => $blur,
            'link_hash' => $link_hash,
            'link' => $link,
            'title' => $title,
            'content' => $content,
            'top_movie' => $top_movie,
            'view_type' => $view_type,
            'link_id' => $link_id,
        );

        $id = $this->sync_insert_data($data, $this->db['posts'], $this->sync_client, $sync);

        return $id;
    }

    
    public function get_all_feed_urls($cid) {
        $query = sprintf("SELECT p.link FROM {$this->db['posts']} p INNER JOIN {$this->db['feed_meta']} m ON p.id = m.pid WHERE m.cid=%d", $cid);    
        $result = $this->db_results($query);
        return $result;
    }
    
    public function clear_utf8($text) {
        return preg_replace('/[\x{10000}-\x{10FFFF}]/u', "", $text);
    }

    public function add_post_meta($fid = 0, $type = 0, $state = 0, $cid = 0, $rating = 0, $update_top_movie = true) {
        // Validate values        
        if ($fid > 0 && $cid > 0) {
            //Get post meta
            $sql = sprintf("SELECT id FROM {$this->db['meta']} WHERE fid='%d' AND cid='%d'", $fid, $cid);
            $id = $this->db_get_var($sql);
            if (!$id) {
                $data = array(
                    'fid' => $fid,
                    'type' => $type,
                    'state' => $state,
                    'cid' => $cid,
                    'rating' => $rating,
                );

                $id = $this->sync_insert_data($data, $this->db['meta'], $this->sync_client, $this->sync_data);
                if ($update_top_movie) {
                    $this->update_critic_top_movie($cid);
                }
            }
            return $id;
        }
        return false;
    }

    public function update_post_meta($fid = 0, $type = 0, $state = 0, $cid = 0, $rating = 0) {
        // Validate values
        if ($fid > 0 && $cid > 0) {
            //Get post meta
            $sql = sprintf("SELECT fid FROM {$this->db['meta']} WHERE cid=%d AND fid=%d", (int) $cid, (int) $fid);
            $meta_exist = $this->db_get_var($sql);
            if ($meta_exist) {
                //Validate old post author

                $db_meta = $this->get_critic_meta($cid, $fid);
                if ($db_meta) {
                    $data = array(
                        'type' => $type,
                        'state' => $state,
                        'rating' => $rating
                    );
                    $this->sync_update_data($data, $db_meta->id, $this->db['meta'], $this->sync_data);
                }

                $this->update_critic_top_movie($cid);
            } else {
                $this->add_post_meta($fid, $type, $state, $cid, $rating);
            }

            return true;
        }
        return false;
    }

    public function remove_post_meta($cid = 0, $fid = 0) {
        $sql = sprintf("DELETE FROM {$this->db['meta']} WHERE cid=%d AND fid=%d", (int) $cid, (int) $fid);
        $this->db_query($sql);
        $this->update_critic_top_movie($cid);
    }

    public function add_feed_post_meta($cid = 0, $pid = 0) {
        // Validate values
        if ($cid > 0 && $pid > 0) {
            //Get author meta
            $sql = sprintf("SELECT cid FROM {$this->db['feed_meta']} WHERE pid='%d'", $pid);
            $meta_exist = $this->db_get_var($sql);
            if (!$meta_exist) {
                //Meta not exist
                $sql = sprintf("INSERT INTO {$this->db['feed_meta']} (cid, pid) VALUES (%d, %d)", (int) $cid, (int) $pid);
                $this->db_query($sql);
            }
            return true;
        }
        return false;
    }

    public function get_feed_count($id) {
        $query = sprintf("SELECT COUNT(id) FROM {$this->db['feed_meta']} WHERE  cid=%d", $id);
        $result = $this->db_get_var($query);
        return $result;
    }

    public function get_feed_by_pid($pid) {
        $query = sprintf("SELECT cid FROM {$this->db['feed_meta']} WHERE pid=%d", $pid);
        $result = $this->db_get_var($query);
        return $result;
    }

    public function update_critic_top_movie($cid) {
        // Update top movie link after any change in critic meta
        // Get movies meta list
        $top_meta_movie = $this->get_top_critics_meta($cid);
        $top_movie = $top_meta_movie ? $top_meta_movie->fid : 0;
        $top_rating = $top_meta_movie ? $top_meta_movie->rating : 0;
        // Get critic top link
        $post_movie = $this->get_post_top_movie($cid);

        // Update critic top link
        if ($top_movie != $post_movie) {
            $date_add = $this->curr_time();
            $data = array(
                'top_movie' => $top_movie,
                'date_add' => $date_add,
                'top_rating' => $top_rating,
            );
            $this->sync_update_data($data, $cid, $this->db['posts'], $this->sync_data);
        }
    }

    public function update_post($id, $date, $status, $link, $title, $content, $type, $blur = 0) {
        $date_add = $this->curr_time();
        $link_hash = '';
        $link_id = 0;
        if ($link) {
            $link_hash = $this->link_hash($link);
            $site_name = $this->clean_site_name($link);
            $link_id = $this->get_or_create_post_link_by_name($site_name);
        }

        $top_movie = 0;

        //Clear UTF8
        #$content = $this->clear_utf8($content);

        $data = array(
            'date' => $date,
            'date_add' => $date_add,
            'status' => $status,
            'type' => $type,
            'blur' => $blur,
            'link_hash' => $link_hash,
            'link' => $link,
            'title' => $title,
            'content' => $content,
            'top_movie' => $top_movie,
            'link_id' => $link_id,
        );
        $this->sync_update_data($data, $id, $this->db['posts'], $this->sync_data);

        $this->hook_update_post($id);
    }

    public function update_post_fields($id, $data) {
        $date_add = $this->curr_time();
        $data['date_add'] = $date_add;
        $this->sync_update_data($data, $id, $this->db['posts'], $this->sync_data);

        $this->hook_update_post($id);
    }

    public function update_post_date_add($id) {
        $date = $this->curr_time();
        $data = array(
            'date_add' => $date,
        );
        $this->sync_update_data($data, $id, $this->db['posts'], $this->sync_data);
    }

    public function update_post_content($id, $content) {
        $date = $this->curr_time();
        $data = array(
            'date_add' => $date,
            'content' => $content,
        );
        $this->sync_update_data($data, $id, $this->db['posts'], $this->sync_data);
        $this->hook_update_post($id);
    }

    public function post_edit_submit($form_state) {
        $result_id = 0;
        $status = $form_state['status'] ? $form_state['status'] : 0;
        $date_str = $form_state['date'];
        if (preg_match('|^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$|', $date_str)) {
            $date = strtotime($date_str);
        } else {
            $date = $this->curr_time();
        }
        $date_add = $this->curr_time();
        $author = isset($form_state['author']) ? $form_state['author'] : 0;
        $blur = isset($form_state['blur']) ? $form_state['blur'] : 0;
        $title = stripslashes($form_state['title']);
        $link = $form_state['link'];
        $link_hash = $this->link_hash($link);
        $content = stripslashes($form_state['content']);

        if ($form_state['id']) {
            //EDIT
            $id = $form_state['id'];

            //Validate old post author
            $data = array(
                'date' => $date,
                'date_add' => $date_add,
                'status' => $status,
                'blur' => $blur,
                'link_hash' => $link_hash,
                'link' => $link,
                'title' => $title,
                'content' => $content,
            );

            $this->sync_update_data($data, $id, $this->db['posts'], $this->sync_data);

            $result_id = $id;

            //Udate author
            if ($author) {
                $post = $this->get_post($id);
                if ($post->aid != $author) {
                    $this->remove_post_author($id);
                    $this->add_post_author($id, $author);
                }
            }

            // Add new meta
            $meta_id_new = (int) $form_state['meta_id_new'];
            if ($meta_id_new) {
                $meta_type_new = (int) $form_state['meta_type_new'];
                $meta_state_new = (int) $form_state['meta_state_new'];
                $meta_rating_new = (int) $form_state['meta_rating_new'];
                $this->add_post_meta($meta_id_new, $meta_type_new, $meta_state_new, $id, $meta_rating_new);
            }

            // Update meta
            $meta_to_update = array();
            foreach ($form_state as $key => $value) {
                if (preg_match('/meta_type_([0-9]+)/', $key, $match)) {
                    $fid = $match[1];
                    $meta_to_update[$fid] = array(
                        'type' => $form_state['meta_type_' . $fid],
                        'state' => $form_state['meta_state_' . $fid],
                        'rating' => $form_state['meta_rating_' . $fid]
                    );
                }
            }

            if (sizeof($meta_to_update)) {
                foreach ($meta_to_update as $fid => $value) {
                    $type = (int) $value['type'];
                    $state = (int) $value['state'];
                    $rating = $value['rating'];

                    $db_meta = $this->get_critic_meta($id, $fid);
                    if ($db_meta) {
                        $data = array(
                            'type' => $type,
                            'state' => $state,
                            'rating' => $rating
                        );
                        $this->sync_update_data($data, $db_meta->id, $this->db['meta'], $this->sync_data);
                    }
                }
                $this->update_critic_top_movie($id);
            }
        } else {
            //ADD
            /*
              `id` int(11) unsigned NOT NULL auto_increment,
              `date` int(11) NOT NULL DEFAULT '0',
              `date_add` int(11) NOT NULL DEFAULT '0',
              `status` int(11) NOT NULL DEFAULT '1',
              `type` int(11) NOT NULL DEFAULT '0',
              `link_hash` varchar(255) NOT NULL default '',
              `link` text default NULL,
              `title` text default NULL,
              `content` text default NULL,
             */

            $type = 2;
            $top_movie = 0;
            $data = array(
                'date' => $date,
                'date_add' => $date_add,
                'status' => $status,
                'type' => $type,
                'blur' => $blur,
                'link_hash' => $link_hash,
                'link' => $link,
                'title' => $title,
                'content' => $content,
                'top_movie' => $top_movie
            );

            $id = $this->sync_insert_data($data, $this->db['posts'], $this->sync_client, $this->sync_data);

            //Add author meta
            if ($author) {
                $this->add_post_author($id, $author);
            }

            $result_id = $id;
        }

        //Add tag meta
        if ($tags && sizeof($tags)) {
            foreach ($tags as $tag_id) {
                $this->add_author_tag($result_id, $tag_id);
            }
        }

        //Rating
        $rating = array();
        foreach ($form_state as $key => $value) {
            if (preg_match('/rating_([a-z]+)/', $key, $match)) {
                $rating_name = $match[1];
                if ($rating_name == 'r') {
                    $value = (float) round($value, 1);
                    if ($value > 5) {
                        $value = 5;
                    }
                    if ($value < 0) {
                        $value = 0;
                    }
                }
                $rating[$rating_name] = $value;
            }
        }

        if ($rating) {
            $rating_old = $this->get_post_rating($result_id);
            $options = array();
            foreach ($this->def_rating as $key => $value) {
                if (isset($rating[$key])) {
                    $options[$key] = trim($rating[$key]);
                } else {
                    // IP and email fields
                    if (isset($rating_old[$key])) {
                        $options[$key] = $rating_old[$key];
                    }
                }
            }
            if ($options) {
                $this->update_post_rating($result_id, $options);
            }
        }

        $this->hook_update_post($result_id);

        return $result_id;
    }

    public function post_add_submit($form_state) {
        $form_state['author'] = $form_state['author_id'];
        return $this->post_edit_submit($form_state);
    }

    public function trash_post($form_state) {
        $result = 0;
        $status = isset($form_state['status']) ? $form_state['status'] : 0;

        if ($form_state['id']) {
            // To trash
            $id = $form_state['id'];

            $data = array(
                'date_add' => $this->curr_time(),
                'status' => $status
            );
            $this->db_update($data, $this->db['posts'], $id);
            $this->hook_update_post($id);
            $this->critic_delta_cron();

            $result = $id;
        }
        return $result;
    }

    public function trash_post_by_id($id) {
        // To trash
        $data = array(
            'date_add' => $this->curr_time(),
            'status' => 2
        );
        $this->sync_update_data($data, $id, $this->db['posts'], $this->sync_data);
        $this->hook_update_post($id);
        $this->critic_delta_cron();

        return true;
    }

    public function change_post_state($id, $status = 0) {

        $sql = sprintf("SELECT status FROM {$this->db['posts']} WHERE id=%d", $id);
        $old_status = $this->db_get_var($sql);
        if ($old_status != $status) {
            $data = array(
                'date_add' => $this->curr_time(),
                'status' => $status
            );
            $this->sync_update_data($data, $id, $this->db['posts'], $this->sync_data);
            $this->hook_update_post($id);
            $this->critic_delta_cron();

            return true;
        }
        return false;
    }

    /*
     * Post meta get
     */

    public function get_meta($status = 0, $page = 1, $type = -1, $rating = -1, $orderby = '', $order = 'ASC') {
        $page -= 1;
        $start = $page * $this->perpage;

        // Custom status
        $status_query = "";
        if ($status != -1) {
            $status_query = sprintf(" AND m.state = %d", (int) $status);
        }

        //Post type filter
        $type_and = '';
        if ($type != -1) {
            $type_and = sprintf(" AND m.type = %d", (int) $type);
        }

        //Custom rating
        $rating_query = "";
        if ($rating != -1) {
            if (($rating) == 0) {
                $rating_query = sprintf(" AND m.rating = 0");
            } else {
                $rating_query = sprintf(" AND m.rating > 0");
            }
        }

        //Sort
        $and_orderby = '';
        $and_order = '';
        /* if ($orderby && in_array($orderby, $this->sort_pages)) {
          $and_orderby = ' ORDER BY ' . $orderby;
          if ($order) {
          $and_orderby .= ' ' . $order;
          }
          } else { */
        $and_orderby = " ORDER BY m.id DESC";
        //}

        $limit = '';
        if ($this->perpage > 0) {
            $limit = " LIMIT $start, " . $this->perpage;
        }

        $sql = "SELECT m.id, m.fid, m.type, m.state, m.cid, m.rating, "
                . "p.title AS post_title, p.date AS post_date, "
                . "a.id AS author_id, a.name AS author_name, a.type AS author_type, a.last_upd AS author_last_upd, a.date_add AS author_date_add "
                . "FROM {$this->db['meta']} m "
                . "INNER JOIN {$this->db['posts']} p ON m.cid = p.id "
                . "INNER JOIN {$this->db['authors_meta']} am ON am.cid = p.id "
                . "INNER JOIN {$this->db['authors']} a ON a.id = am.aid "
                . "WHERE m.id>0" . $status_query . $type_and . $rating_query . $and_orderby . $limit;

        $result = $this->db_results($sql);

        return $result;
    }

    public function get_critic_meta($cid = 0, $fid = 0) {
        $sql = sprintf("SELECT id, fid, type, state, cid, rating FROM {$this->db['meta']} WHERE cid=%d AND fid=%d LIMIT 1", $cid, $fid);
        $result = $this->db_fetch_row($sql);
        return $result;
    }

    public function get_meta_states() {
        $count = $this->get_meta_count();
        $feed_states = array(
            '-1' => array(
                'title' => 'All',
                'count' => $count
            )
        );
        foreach ($this->movie_state as $key => $value) {
            $feed_states[$key] = array(
                'title' => $value,
                'count' => $this->get_meta_count($key));
        }
        return $feed_states;
    }

    public function get_meta_type($status) {
        $count = $this->get_meta_count($status);
        $feed_states = array(
            '-1' => array(
                'title' => 'All',
                'count' => $count
            )
        );
        foreach ($this->post_category as $key => $value) {
            $feed_states[$key] = array(
                'title' => $value,
                'count' => $this->get_meta_count($status, -1, $key));
        }
        return $feed_states;
    }

    public function get_meta_rating($status) {
        $count = $this->get_meta_count($status);
        $feed_states = array(
            '-1' => array(
                'title' => 'All',
                'count' => $count
            )
        );
        foreach ($this->movie_rating as $key => $value) {
            $feed_states[$key] = array(
                'title' => $value,
                'count' => $this->get_meta_count($status, $key));
        }
        return $feed_states;
    }

    public function get_meta_count($status = -1, $rating = -1, $type = -1) {
        // Custom status
        $status_query = "";
        if ($status != -1) {
            $status_query = sprintf(" AND state = %d", (int) $status);
        }

        //Custom rating
        $rating_query = "";
        if ($rating != -1) {
            if (($rating) == 0) {
                $rating_query = sprintf(" AND rating = 0");
            } else {
                $rating_query = sprintf(" AND rating > 0");
            }
        }

        //Custom type
        $type_query = "";
        if ($type != -1) {
            $type_query = sprintf(" AND type = %d", $type);
        }

        $query = "SELECT COUNT(id) FROM {$this->db['meta']} WHERE id>0" . $status_query . $rating_query . $type_query;

        $result = $this->db_get_var($query);
        return $result;
    }

    public function bulk_meta_remove($ids = array(), $mid = 0) {
        if (!$mid || !$ids) {
            return false;
        }

        $sql = sprintf("DELETE FROM {$this->db['meta']} WHERE id IN(" . implode(',', $ids) . ")");
        $this->db_query($sql);
        $this->update_critic_top_movie($mid);
        return true;
    }

    public function bulk_meta_update($ids = array(), $meta_state = 0, $mid = 0) {
        if (!$mid || !$ids) {
            return false;
        }

        $data = array(
            'state' => $meta_state,
        );

        foreach ($ids as $id) {
            $this->sync_update_data($data, $id, $this->db['meta'], $this->sync_data);
        }

        $this->update_critic_top_movie($mid);
        return true;
    }

    /*
     * Post link
     */

    public function post_link_cron($count = 10, $debug = false, $force = false) {
        # 1. Get posts no links
        $sql = sprintf("SELECT id, link FROM {$this->db['posts']} WHERE link_hash!='' AND link_id=0 LIMIT %d", $count);
        $results = $this->db_results($sql);

        if ($debug) {
            print_r($results);
        }

        if ($results) {
            # 2. Add link
            foreach ($results as $item) {
                $site_name = $this->clean_site_name($item->link);
                $link_id = $this->get_or_create_post_link_by_name($site_name);

                if ($debug) {
                    print_r(array($site_name, $link_id));
                }

                $data = array(
                    'link_id' => $link_id,
                );
                $this->sync_update_data($data, $item->id, $this->db['posts'], $this->sync_data, 10);
            }
        }
    }

    public function get_or_create_post_link_by_name($name = '') {
        //Get from cache
        static $dict;
        if (is_null($dict)) {
            $dict = array();
        }

        if (isset($dict[$name])) {
            return $dict[$name];
        }

        //Get name id
        $sql = sprintf("SELECT id FROM {$this->db['posts_links']} WHERE site='%s'", $this->escape($name));
        $id = $this->db_get_var($sql);

        if (!$id) {
            // Create the name
            $data = array(
                'site' => $name,
            );

            $id = $this->sync_insert_data($data, $this->db['posts_links'], $this->sync_client, $this->sync_data);
        }

        // Add to cache
        $dict[$name] = $id;

        return $id;
    }

    public function get_post_links_by_names($names = array()) {
        $ret = array();
        if ($names) {
            $names_and = "'" . implode("','", $names) . "'";
            $sql = "SELECT id, site FROM {$this->db['posts_links']} WHERE site IN (" . $names_and . ")";
            $results = $this->db_results($sql);
            if ($results) {
                foreach ($results as $value) {
                    $ret[$value->site] = $value->id;
                }
            }
        }
        return $ret;
    }

    public function get_author_post_link_by_site($aid, $site_key) {
        $sql = sprintf("SELECT p.id, p.link FROM {$this->db['posts']} p "
                . "INNER JOIN {$this->db['authors_meta']} am ON am.cid = p.id "
                . "WHERE am.aid=%d AND p.link_id=%d ORDER BY p.id DESC", $aid, $site_key);

        $result = $this->db_fetch_row($sql);
        return $result;
    }

    public function get_post_links($cache = true) {
        //Get from cache

        $name = 'links';
        static $dict;
        if (is_null($dict)) {
            $dict = array();
        }

        if (isset($dict[$name]) && $cache) {
            return $dict[$name];
        }

        //Get name id
        $sql = "SELECT * FROM {$this->db['posts_links']}";
        $results = $this->db_results($sql);

        $items = array();
        if ($results && $cache) {
            foreach ($results as $item) {
                $items[$item->id] = $item->site;
            }
            // Add to cache
            $dict[$name] = $items;
        }

        return $items;
    }

    public function clean_site_name($url) {
        # 1. Clear
        $domain = strtolower(parse_url($url, PHP_URL_HOST));
        $domain = preg_replace('/^www\./', '', $domain);

        # 2. Replace
        if ($domain == 'youtu.be') {
            $domain = 'youtube.com';
        }

        return $domain;
    }

    /*
     * Authors get
     */

    public function get_last_authors_name() {
        //Custom type


        $sql = "SELECT `id`, `name`, `type`, `options`, `wp_uid`, `show_type`  FROM {$this->db['authors']} WHERE id>0 ORDER BY id DESC LIMIT 1";

        $result = $this->db_results($sql);
        return $result;
    }

    public function get_author($id, $cache = false) {
        if ($cache) {
            static $dict;
            if (is_null($dict)) {
                $dict = array();
            }

            if (isset($dict[$id])) {
                return $dict[$id];
            }
        }
        //Get author id
        $sql = sprintf("SELECT * FROM {$this->db['authors']} WHERE id=%d", (int) $id);
        $author = $this->db_fetch_row($sql);

        if ($cache) {
            $dict[$id] = $author;
        }
        return $author;
    }

    public function get_post_author($cid) {
        $sql = sprintf("SELECT a.* FROM {$this->db['authors']} a INNER JOIN {$this->db['authors_meta']} am ON am.aid = a.id  WHERE am.cid=%d LIMIT 1", $cid);
        $result = $this->db_fetch_row($sql);
        return $result;
    }

    public function get_author_by_name($name, $cache = false, $type = -1, $multi = false, $wp_uid = -1) {
        if ($cache) {
            static $dict;
            if (is_null($dict)) {
                $dict = array();
            }

            if (isset($dict[$name])) {
                return $dict[$name];
            }
        }

        //Custom type
        $type_and = '';
        if ($type != -1) {
            $type_and = sprintf(" AND type = %d", (int) $type);
        }

        //Custom wp_id
        $wpuid_and = '';
        if ($wp_uid != -1) {
            $wpuid_and = sprintf(" AND wp_uid = %d", (int) $wp_uid);
        }

        //Get author id
        $sql = sprintf("SELECT * FROM {$this->db['authors']} WHERE name='%s'" . $type_and . $wpuid_and, $this->escape($name));

        if ($multi) {
            $author = $this->db_results($sql);
        } else {
            $author = $this->db_fetch_row($sql);
        }
        if ($cache) {
            $dict[$name] = $author;
        }
        return $author;
    }

    public function get_author_by_wp_uid($id, $cache = false) {
        if ($cache) {
            static $dict;
            if (is_null($dict)) {
                $dict = array();
            }

            if (isset($dict[$id])) {
                return $dict[$id];
            }
        }
        //Get author id
        $sql = sprintf("SELECT * FROM {$this->db['authors']} WHERE wp_uid=%d", (int) $id);
        $author = $this->db_fetch_row($sql);

        if ($cache) {
            $dict[$id] = $author;
        }
        return $author;
    }

    public function get_post_wp_author($post_id, $cache = true) {
        if ($cache) {
            static $dict;
            if (is_null($dict)) {
                $dict = array();
            }

            if (isset($dict[$post_id])) {
                return $dict[$post_id];
            }
        }
        //Get author id
        $sql = sprintf("SELECT a.wp_uid"
                . " FROM {$this->db['posts']} p"
                . " INNER JOIN {$this->db['authors_meta']} am ON am.cid = p.id"
                . " INNER JOIN {$this->db['authors']} a ON a.id = am.aid "
                . " WHERE p.id=%d", (int) $post_id);
        $author = $this->db_get_var($sql);

        if ($cache) {
            $dict[$post_id] = $author;
        }
        return $author;
    }

    public function get_authors_by_ids($ids, $cache = true) {
        $key = md5(implode(',', $ids));
        if ($cache) {
            static $dict;
            if (is_null($dict)) {
                $dict = array();
            }

            if (isset($dict[$key])) {
                return $dict[$key];
            }
        }
        $sql = sprintf("SELECT * FROM {$this->db['authors']} WHERE id IN(%s)", implode(',', $ids));
        $result = $this->db_results($sql);
        $arr = array();
        if (sizeof($result)) {
            foreach ($result as $tag) {
                $arr[$tag->id] = $tag;
            }
        }
        $dict[$key] = $arr;
        return $arr;
    }

    public function get_author_last_upd($key, $cache = true) {
        if ($cache) {
            static $dict;
            if (is_null($dict)) {
                $dict = array();
            }

            if (isset($dict[$key])) {
                return $dict[$key];
            }
        }
        $sql = sprintf("SELECT last_upd FROM {$this->db['authors']} WHERE id=%d", $key);
        $result = $this->db_get_var($sql);

        $dict[$key] = $result;
        return $result;
    }

    public function find_authors($name_or_id, $limit = 10, $type = -1, $status = 1) {
        $name_or_id = strip_tags($name_or_id);
        $name_or_id = preg_replace('/[^\w\d ]+/', '', $name_or_id);

        $and_id = " AND name LIKE '{$name_or_id}%'";
        if (preg_match('/([0-9]+)/', $name_or_id, $match)) {
            $id = $match[1];
            $and_id = " AND (id=" . $id . " OR name LIKE '{$name_or_id}%')";
        }

        $and_type = '';
        if ($type != -1) {
            $and_type = sprintf(' AND type = %d', $type);
        }

        $and_status = '';
        if ($status != -1) {
            $and_status = sprintf(' AND status = %d', $status);
        }


        $and_limit = '';
        if ($limit) {
            $and_limit = sprintf(' LIMIT %d', $limit);
        }

        $sql = "SELECT * FROM {$this->db['authors']} WHERE id>0 " . $and_type . $and_status . $and_id . $and_limit;
        $results = $this->db_results($sql);
        return $results;
    }

    public function bulk_change_author($ids = array(), $author_id = 0) {
        if (!$ids || !$author_id) {
            return false;
        }

        $data = array(
            'aid' => $author_id,
        );

        foreach ($ids as $cid) {
            $item = $this->get_post_author_meta($cid);
            if ($item) {
                $this->sync_update_data($data, $item->id, $this->db['authors_meta'], $this->sync_data);
            }
        }

        return true;
    }

    public function get_post_author_meta($cid) {
        $sql = sprintf("SELECT * FROM {$this->db['authors_meta']} WHERE cid=%d LIMIT 1", $cid);
        $result = $this->db_fetch_row($sql);
        return $result;
    }

    public function get_author_type($type) {
        return isset($this->author_type[$type]) ? $this->author_type[$type] : 'None';
    }

    public function get_author_status($status) {
        return isset($this->author_status[$status]) ? $this->author_status[$status] : 'None';
    }

    public function authors_actions($exclude = array()) {
        $author_tabs = $this->get_sync_tabs($this->author_tabs);
        foreach ($author_tabs as $key => $value) {
            if (in_array($key, $exclude)) {
                continue;
            }
            $feed_actions[$key] = array('title' => $value);
        }
        return $feed_actions;
    }

    public function get_authors_query($q_req = array(), $page = 1, $perpage = 20, $orderby = '', $order = 'ASC', $count = false) {
        $q_def = array(
            'status' => -1,
            'type' => -1,
            'avatar' => -1,
            'tag' => 0,
        );

        $q = array();
        foreach ($q_def as $key => $value) {
            $q[$key] = isset($q_req[$key]) ? $q_req[$key] : $value;
        }

        $filters_and = '';

        // Custom status
        $status_trash = 2;
        $filters_and = " WHERE a.status != " . $status_trash;
        if ($q['status'] != -1) {
            $filters_and = sprintf(" WHERE a.status = %d", (int) $q['status']);
        }

        //Custom type

        if ($q['type'] != -1) {
            $filters_and .= sprintf(" AND a.type = %d", (int) $q['type']);
        }

        // Avatar

        if ($q['avatar'] != -1) {
            $filters_and .= sprintf(" AND a.avatar = %d", (int) $q['avatar']);
        }

        //Custom tag

        $tags_inner = '';
        if ($q['tag'] > 0) {
            $tags_inner = " INNER JOIN {$this->db['tag_meta']} t ON t.cid = a.id ";
            $filters_and .= sprintf(" AND t.tid = %d", $q['tag']);
        }

        //Sort
        $and_orderby = '';
        $limit = '';
        if (!$count) {

            if ($orderby && in_array($orderby, $this->sort_pages)) {
                $and_orderby = ' ORDER BY ' . $orderby;
                if ($order) {
                    $and_orderby .= ' ' . $order;
                }
            } else {
                $and_orderby = " ORDER BY name ASC";
            }

            $page -= 1;
            $start = $page * $perpage;

            if ($perpage > 0) {
                $limit = " LIMIT $start, " . $perpage;
            }

            $select = 'a.id, a.status, a.type, a.name, a.options, a.wp_uid, a.show_type, a.avatar, a.avatar_name, a.avatar_type, a.last_upd AS author_last_upd, a.date_add AS author_date_add';
        } else {
            $select = " COUNT(a.id)";
        }


        $sql = "SELECT " . $select . " FROM {$this->db['authors']} a" . $tags_inner . $filters_and . $and_orderby . $limit;

        if (!$count) {
            $result = $this->db_results($sql);
        } else {
            $result = $this->db_get_var($sql);
        }

        return $result;
    }

    public function get_authors($status = 0, $page = 1, $tag = 0, $type = -1, $orderby = '', $order = 'ASC') {
        // DEPRECATED
        $page -= 1;
        $start = $page * $this->perpage;

        // Custom status
        $status_trash = 2;
        $status_query = " WHERE a.status != " . $status_trash;
        if ($status != -1) {
            $status_query = sprintf(" WHERE a.status = %d", (int) $status);
        }

        //Custom type
        $type_and = '';
        if ($type != -1) {
            $type_and = sprintf(" AND a.type = %d", (int) $type);
        }

        //Custom tag
        $tags_and = '';
        $tags_inner = '';
        if ($tag > 0) {
            $tags_inner = " INNER JOIN {$this->db['tag_meta']} t ON t.cid = a.id ";
            $tags_and = sprintf(" AND t.tid = %d", $tag);
        }

        //Sort
        $and_orderby = '';
        if ($orderby && in_array($orderby, $this->sort_pages)) {
            $and_orderby = ' ORDER BY ' . $orderby;
            if ($order) {
                $and_orderby .= ' ' . $order;
            }
        } else {
            $and_orderby = " ORDER BY name ASC";
        }

        $limit = '';
        if ($this->perpage > 0) {
            $limit = " LIMIT $start, " . $this->perpage;
        }

        $sql = "SELECT a.id, a.status, a.type, a.name, a.options, a.wp_uid, a.show_type, a.last_upd AS author_last_upd, a.date_add AS author_date_add FROM {$this->db['authors']} a" . $tags_inner . $status_query . $tags_and . $type_and . $and_orderby . $limit;

        $result = $this->db_results($sql);

        return $result;
    }

    public function get_all_authors($type = -1, $exclude_type = -1) {
        //Custom type
        $type_and = '';
        if ($type != -1) {
            $type_and = sprintf(" AND type = %d", (int) $type);
        }

        $ex_type_and = '';
        if ($exclude_type != -1) {
            $ex_type_and = sprintf(" AND type != %d", (int) $exclude_type);
        }
        $sql = "SELECT * FROM {$this->db['authors']} WHERE id>0" . $type_and . $ex_type_and . " ORDER BY name ASC";
        $result = $this->db_results($sql);
        return $result;
    }

    public function get_authors_query_count($q_req = array()) {
        return $this->get_authors_query($q_req, $page = 1, 1, '', '', true);
    }

    public function get_author_type_count($q_req = array(), $types = array(), $custom_type = '', $all = true) {
        $status = -1;
        $count = $this->get_authors_query_count($q_req);
        if ($all) {
            $states = array(
                '-1' => array(
                    'title' => 'All',
                    'count' => $count
                )
            );
        } else {
            $states = array();
        }
        $q_req_custom = $q_req;

        foreach ($types as $key => $value) {
            $q_req_custom[$custom_type] = $key;
            $states[$key] = array(
                'title' => $value,
                'count' => $this->get_authors_query_count($q_req_custom));
        }
        return $states;
    }

    public function get_authors_count($status = -1, $tag = 0, $type = -1) {
        // DEPRECATED
        // Custom status
        $status_trash = 2;
        $status_query = " AND a.status != " . $status_trash;
        if ($status != -1) {
            $status_query = sprintf(" AND a.status = %d", (int) $status);
        }

        //Custom tag
        $tags_and = '';
        $tags_inner = '';
        if ($tag > 0) {
            $tags_inner = " INNER JOIN {$this->db['tag_meta']} t ON t.cid = a.id ";
            $tags_and = sprintf(" AND t.tid = %d", $tag);
        }

        //Custom type
        $type_and = '';
        if ($type != -1) {
            $type_and = sprintf(" AND a.type = %d", (int) $type);
        }

        $query = "SELECT COUNT(a.id) FROM {$this->db['authors']} a" . $tags_inner . " WHERE a.id > 0" . $status_query . $type_and . $tags_and;
        $result = $this->db_get_var($query);
        return $result;
    }

    public function get_author_post_count($aid) {
        $query = sprintf("SELECT COUNT(id) FROM {$this->db['authors_meta']} WHERE aid = %d", (int) $aid);
        $result = $this->db_get_var($query);
        return $result;
    }

    public function get_author_id_by_secret_key($key = 0, $type = 0) {
        $authors = $this->get_all_authors($type);
        $ret = 0;
        if (sizeof($authors)) {
            foreach ($authors as $author) {
                $options = unserialize($author->options);
                if (isset($options['secret']) && $options['secret'] == $key) {
                    $ret = $author->id;
                    break;
                }
            }
        }
        return $ret;
    }

    public function get_author_states($type = -1) {
        $count = $this->get_authors_count(-1, 0, $type);
        $feed_states = array(
            '-1' => array(
                'title' => 'All',
                'count' => $count
            )
        );
        foreach ($this->author_status as $key => $value) {
            $feed_states[$key] = array(
                'title' => $value,
                'count' => $this->get_authors_count($key, 0, $type));
        }
        return $feed_states;
    }

    public function get_author_types($all = true) {
        $count = $this->get_authors_count();
        if ($all) {
            $feed_states = array(
                '-1' => array(
                    'title' => 'All',
                    'count' => $count
                )
            );
        } else {
            $feed_states = array();
        }
        foreach ($this->author_type as $key => $value) {
            $feed_states[$key] = array(
                'title' => $value,
                'count' => $this->get_authors_count(-1, 0, $key));
        }
        return $feed_states;
    }

    public function author_edit_validate($form_state) {

        if (isset($form_state['trash'])) {
            // Trash
        } else {
            // Edit
            if ($form_state['name'] == '') {
                return __('Enter the name');
            }
        }

        $nonce = wp_verify_nonce($_POST['critic-feeds-nonce'], 'critic-feeds-options');
        if (!$nonce) {
            return __('Error validate nonce');
        }

        return true;
    }

    /*
     * Authors set
     */

    public function get_or_create_author_by_name($name = '', $author_type = 0, $status = 1, $options = array()) {
        //Get from cache
        static $dict;
        if (is_null($dict)) {
            $dict = array();
        }

        if (isset($dict[$name])) {
            return $dict[$name];
        }

        //Get author id
        $sql = sprintf("SELECT id FROM {$this->db['authors']} WHERE name='%s' AND type=%d", $this->escape($name), (int) $author_type);
        $id = $this->db_get_var($sql);

        if (!$id) {
            $id = $this->create_author_by_name($name, $author_type, $status, $options);
        }
        // Add to cache
        $dict[$name] = $id;

        return $id;
    }

    public function create_author_by_name($name, $author_type = 0, $status = 1, $options = array(), $wp_uid = 0) {
        $opt_str = serialize($options);
        // Create the author
        $curr_time = $this->curr_time();
        $data = array(
            'status' => $status,
            'type' => $author_type,
            'name' => $name,
            'options' => $opt_str,
            'wp_uid' => $wp_uid,
            'date_add' => $curr_time,
            'last_upd' => $curr_time,
        );

        $id = $this->sync_insert_data($data, $this->db['authors'], $this->sync_client, $this->sync_data);

        return $id;
    }

    public function add_post_author($pid = 0, $author_id = 0) {
        // Validate values
        if ($pid > 0 && $author_id > 0) {
            //Get author meta

            $data = array(
                'aid' => $author_id,
                'cid' => $pid,
            );

            $id = $this->sync_insert_data($data, $this->db['authors_meta'], $this->sync_client, $this->sync_data);

            return $id;
        }
        return false;
    }

    public function remove_post_author($pid) {
        $sql = sprintf("DELETE FROM {$this->db['authors_meta']} WHERE cid = %d", (int) $pid);
        $this->db_query($sql);
    }

    public function author_edit_submit($form_state) {
        $result_id = 0;
        $status = (int) $form_state['status'];
        $from = (int) $form_state['type'];
        $name = $form_state['name'];
        $show_type = (int) isset($form_state['show_type']) ? $form_state['show_type'] : 0;
        $tags = isset($form_state['post_category']) ? $form_state['post_category'] : array();

        $options = array();
        $options['autoblur'] = $form_state['autoblur'];
        $options['image'] = $form_state['image'];
        $options['secret'] = $form_state['secret'];

        $opt_str = serialize($options);
        $curr_time = $this->curr_time();

        if ($form_state['id']) {
            // UPDATE
            $id = $form_state['id'];
            $author = $this->get_author($id);
            $opt_prev = unserialize($author->options);
            foreach ($options as $key => $value) {
                $opt_prev[$key] = $value;
            }
            $opt_str = serialize($opt_prev);

            $data = array(
                'status' => $status,
                'type' => $from,
                'name' => stripslashes($name),
                'options' => $opt_str,
                'show_type' => $show_type,
                'last_upd' => $curr_time,
            );

            $this->sync_update_data($data, $id, $this->db['authors'], $this->sync_data);

            $result_id = $id;

            //Tags. Remove old meta and add new
            // $this->remove_author_tags($id);
        } else {
            //ADD
            /*
              `id` int(11) unsigned NOT NULL auto_increment,
              `status` int(11) NOT NULL DEFAULT '1',
              `type` int(11) NOT NULL DEFAULT '0',
              `name` varchar(255) NOT NULL default '',
              `options` text default NULL,
             */
            $data = array(
                'status' => $status,
                'type' => $from,
                'name' => $name,
                'options' => $opt_str,
                'show_type' => $show_type,
                'date_add' => $curr_time,
                'last_upd' => $curr_time,
            );

            //Return id
            $id = $this->sync_insert_data($data, $this->db['authors'], $this->sync_client, $this->sync_data);

            $result_id = $id;
        }

        //Add tag meta


        $old_tags = $this->get_author_tags($result_id);

        if ($old_tags) {
            foreach ($old_tags as $old_tag) {
                if (!in_array($old_tag->id, $tags)) {
                    $this->remove_author_tag($result_id, $old_tag->id);
                }
            }
        }
        if ($tags) {
            foreach ($tags as $tag_id) {
                $this->add_author_tag($result_id, $tag_id);
            }
        }


        return $result_id;
    }

    public function update_author($author) {
        $options = $author->options;
        $author_prev = $this->get_author($author->id);
        $opt_prev = unserialize($author_prev->options);
        foreach ($options as $key => $value) {
            $opt_prev[$key] = $value;
        }
        $opt_str = serialize($opt_prev);

        $data = array(
            'status' => $author->status,
            'type' => $author->type,
            'name' => $author->name,
            'options' => $opt_str,
            'last_upd' => $this->curr_time(),
        );

        $this->sync_update_data($data, $author->id, $this->db['authors'], $this->sync_data);
    }

    public function update_author_status($aid, $status) {
        $sql = sprintf("SELECT status FROM {$this->db['authors']} WHERE id=%d", $aid);
        $old_status = $this->db_get_var($sql);
        if ($old_status != $status) {
            $data = array(
                'last_upd' => $this->curr_time(),
                'status' => $status,
            );
            $this->sync_update_data($data, $aid, $this->db['authors'], $this->sync_data);
            return true;
        }
        return false;
    }

    public function update_author_wp_uid($id = 0, $wp_uid = 0) {
        $data = array(
            'wp_uid' => $wp_uid,
            'last_upd' => $this->curr_time(),
        );

        $this->sync_update_data($data, $id, $this->db['authors'], $this->sync_data);
    }

    public function trash_author($form_state) {
        $result = 0;
        $status = isset($form_state['status']) ? $form_state['status'] : 0;

        if ($form_state['id']) {
            // To trash
            $id = $form_state['id'];
            $data = array(
                'status' => $status,
                'last_upd' => $this->curr_time(),
            );
            $this->sync_update_data($data, $id, $this->db['authors'], $this->sync_data);
            $result = $id;
        }
        return $result;
    }

    public function get_author_last_update($type = -1) {

        //Post type filter
        $type_and = '';
        if ($type != -1) {
            $type_and = sprintf(" AND type=%d", (int) $type);
        }

        $sql = "SELECT last_upd FROM {$this->db['authors']} WHERE id>0" . $type_and . " ORDER by last_upd DESC limit 1";

        $result = $this->db_get_var($sql);
        return $result;
    }
    
    public function get_aid($wp_uid) {
        $author = $this->get_author_by_wp_uid($wp_uid);
        $aid = 0;
        if ($author) {
            $aid = $author->id;
        } else {
            // Get remote aid for a new author                
            $author_status = 1;
            $unic_id = $this->unic_id();
            $options = array('audience' => $unic_id);
            $author_type = 2;
            $user = $this->get_current_user();
            $author_name = $user->display_name;
            $aid = $this->create_author_by_name($author_name, $author_type, $author_status, $options, $wp_uid);
        }
        return $aid;
    }

    /*
     * Tags get
     */

    public function get_tag($id) {
        $sql = sprintf("SELECT id, status, name, slug FROM {$this->db['tags']} WHERE id=%d", (int) $id);
        $result = $this->db_fetch_row($sql);
        return $result;
    }

    public function get_tags_by_ids($ids) {
        $sql = sprintf("SELECT id, status, name, slug FROM {$this->db['tags']} WHERE id IN(%s)", implode(',', $ids));
        $result = $this->db_results($sql);
        $tags_arr = array();
        if (sizeof($result)) {
            foreach ($result as $tag) {
                $tags_arr[$tag->id] = $tag;
            }
        }
        return $tags_arr;
    }

    public function get_tag_by_slug($slug, $cache = true) {
        //Get from cache
        static $dict;
        if (is_null($dict)) {
            $dict = array();
        }
        if ($cache && isset($dict[$slug])) {
            return $dict[$slug];
        }
        $sql = sprintf("SELECT id, status, name FROM {$this->db['tags']} WHERE slug='%s'", $this->escape($slug));
        $result = $this->db_fetch_row($sql);
        $dict[$slug] = $result;
        return $result;
    }

    public function get_tags($status = -1, $page = 1, $orderby = '', $order = 'ASC') {
        $page -= 1;
        $start = $page * $this->perpage;

        // Custom status
        $status_trash = 2;
        $status_query = " WHERE status != " . $status_trash;
        if ($status != -1) {
            $status_query = " WHERE status = " . (int) $status;
        }

        //Sort
        $and_orderby = '';
        if ($orderby && in_array($orderby, $this->sort_pages)) {
            $and_orderby = ' ORDER BY ' . $orderby;
            if ($order) {
                $and_orderby .= ' ' . $order;
            }
        } else {
            $and_orderby = " ORDER BY name ASC";
        }

        $limit = '';
        if ($this->perpage > 0) {
            $limit = " LIMIT $start, " . $this->perpage;
        }

        $sql = "SELECT id, status, name, slug FROM {$this->db['tags']} " . $status_query . $and_orderby . $limit;

        $result = $this->db_results($sql);

        return $result;
    }

    public function get_author_tags($aid = 0, $status = -1, $cache = true) {
        //Get from cache
        static $dict;
        if (is_null($dict)) {
            $dict = array();
        }

        if (isset($dict[$aid]) && $cache) {
            return $dict[$aid];
        }

        // Custom status
        $status_trash = 2;
        $status_query = " AND t.status != " . $status_trash;
        if ($status != -1) {
            $status_query = sprintf(" AND t.status = %d", (int) $status);
        }

        $sql = sprintf("SELECT t.id, t.name, t.slug FROM {$this->db['tag_meta']} m "
                . "INNER JOIN {$this->db['tags']} t ON m.tid = t.id"
                . " WHERE cid=%d" . $status_query, (int) $aid);
        $result = $this->db_results($sql);

        $dict[$aid] = $result;

        return $result;
    }

    public function get_tags_count($status = -1) {
        // Custom status
        $status_trash = 2;
        $status_query = " WHERE status != " . $status_trash;
        if ($status != -1) {
            $status_query = " WHERE status = " . (int) $status;
        }

        $query = "SELECT COUNT(id) FROM {$this->db['tags']} " . $status_query;
        $result = $this->db_get_var($query);
        return $result;
    }

    public function tag_actions($exclude = array()) {
        foreach ($this->tag_tabs as $key => $value) {
            if (in_array($key, $exclude)) {
                continue;
            }
            $feed_actions[$key] = array('title' => $value);
        }
        return $feed_actions;
    }

    public function get_tag_status($status) {
        return isset($this->tag_status[$status]) ? $this->tag_status[$status] : 'None';
    }

    public function get_tag_states() {
        $count = $this->get_tags_count();
        $feed_states = array(
            '-1' => array(
                'title' => 'All',
                'count' => $count
            )
        );
        foreach ($this->tag_status as $key => $value) {
            $feed_states[$key] = array(
                'title' => $value,
                'count' => $this->get_tags_count($key));
        }
        return $feed_states;
    }

    public function tag_edit_validate($form_state) {

        if (isset($form_state['trash'])) {
            // Trash
        } else {
            // Edit
            if ($form_state['name'] == '') {
                return __('Enter the tag name');
            }

            if ($form_state['slug'] == '') {
                return __('Enter the tag slug');
            }
        }

        $nonce = wp_verify_nonce($_POST['critic-feeds-nonce'], 'critic-feeds-options');
        if (!$nonce) {
            return __('Error validate nonce');
        }

        return true;
    }

    /*
     * Tags set
     */

    public function add_author_tag($aid = 0, $tag_id = 0) {
        //Cache name
        static $dict;
        if (is_null($dict)) {
            $dict = array();
        }
        $name = $aid . '-' . $tag_id;
        if (isset($dict[$name])) {
            return true;
        }

        // Validate values
        if ($aid > 0 && $tag_id > 0) {
            //Get tag meta
            $sql = sprintf("SELECT tid FROM {$this->db['tag_meta']} WHERE cid='%d' and tid='%d'", $aid, $tag_id);
            $meta_exist = $this->db_get_var($sql);

            if (!$meta_exist) {
                //Meta not exist
                $data = array(
                    'tid' => $tag_id,
                    'cid' => $aid
                );

                $this->sync_insert_data($data, $this->db['tag_meta'], $this->sync_client, $this->sync_data);
            }

            $dict[$name] = 1;

            return true;
        }
        return false;
    }

    public function remove_author_tag($aid, $tid) {

        $data = array(
            'tid' => $tid,
            'cid' => $aid,
        );
        $this->sync_delete_multi($data, $this->db['tag_meta'], $sync_data = $this->sync_data, 10);
    }

    public function remove_author_tags($aid) {
        $tags = $this->get_author_tags($aid);
        if ($tags) {
            foreach ($tags as $tag) {
                $data = array(
                    'tid' => $tag->id,
                    'aid' => $aid,
                );
                $this->sync_delete_multi($data, $this->db['tag_meta'], $sync_data = $this->sync_data, 10);
            }
        }
    }

    public function get_or_create_tag_id($name = '', $slug = '') {
        //Get from cache
        static $dict;
        if (is_null($dict)) {
            $dict = array();
        }

        if (isset($dict[$name])) {
            return $dict[$name];
        }

        //Get tag id
        $sql = sprintf("SELECT id FROM {$this->db['tags']} WHERE name='%s'", $this->escape($name));
        $id = $this->db_get_var($sql);

        if (!$id) {
            $data = array(
                'name' => $name,
                'slug' => $slug
            );

            $id = $this->sync_insert_data($data, $this->db['tags'], $this->sync_client, $this->sync_data);
        }
        // Add to cache
        $dict[$name] = $id;

        return $id;
    }

    public function tag_edit_submit($form_state) {
        $result_id = 0;
        $status = $form_state['status'];
        $name = $this->escape($form_state['name']);
        $slug = $this->escape($form_state['slug']);

        $data = array(
            'status' => $status,
            'name' => $name,
            'slug' => $slug
        );

        if ($form_state['id']) {
            $id = (int) $form_state['id'];
            //EDIT  
            $this->sync_update_data($data, $id, $this->db['tags'], $this->sync_data);

            $result_id = $id;
        } else {
            //ADD
            $id = $this->sync_insert_data($data, $this->db['tags'], $this->sync_client, $this->sync_data);
            //Return id            
            $result_id = $id;
        }

        return $result_id;
    }

    public function trash_tag($form_state) {
        $result = 0;
        $status = isset($form_state['status']) ? $form_state['status'] : 0;

        if ($form_state['id']) {
            // To trash
            $id = $form_state['id'];

            $data = array(
                'status' => $status
            );
            $this->sync_update_data($data, $id, $this->db['tags'], $this->sync_data);
            $result = $id;
        }
        return $result;
    }

    /*
     * Thumbs
     */

    public function get_thumb($cid) {
        $sql = sprintf("SELECT url FROM {$this->db['thumbs']} WHERE cid=%d", (int) $cid);
        $result = $this->db_get_var($sql);
        return $result;
    }

    public function add_thumb($cid = 0, $url = '') {
        $data = array(
            'cid' => $cid,
            'url' => $url,
            'date' => $this->curr_time()
        );
        $this->db_insert($data, $this->db['thumbs']);
    }

    /*
     * Movies get
     */

    public function get_movies_data($cid = 0, $fid = 0) {
        $fid_and = '';
        if ($fid > 0) {
            $fid_and = sprintf(' AND fid=%d', $fid);
        }
        $sql = sprintf("SELECT id, fid, type, state, rating FROM {$this->db['meta']} WHERE cid=%d" . $fid_and, (int) $cid);
        $result = $this->db_results($sql);
        return $result;
    }

    public function get_movie_state_name($id) {
        $name = $this->movie_state[$id];
        return $name;
    }

    public function get_all_movie_meta($pid) {
        // Deprecated unused
        $movie_meta = get_post_meta($pid);
        $ret = array();
        foreach ($movie_meta as $key => $value) {
            if (strstr($key, '_wpmoly_movie_')) {
                $new_key = ucfirst(str_replace('_', ' ', str_replace('_wpmoly_movie_', '', $key)));
                $ret[$new_key] = $value[0];
            }
        }
        return $ret;
    }

    public function get_movie_meta($pid) {
        // Deprecated. Unused
        $fields = array(
            'Title' => '_wpmoly_movie_title',
            'Release date' => '_wpmoly_movie_release_date',
            'Runtime' => '_wpmoly_movie_runtime',
            'Director' => '_wpmoly_movie_director',
            'Cast' => '_wpmoly_movie_cast'
        );

        //_wpmoly_movie_overview

        $movie_meta = get_post_meta($pid);

        $ret = array();
        foreach ($fields as $name => $value) {
            if (isset($movie_meta[$value][0])) {
                $ret[$name] = $movie_meta[$value][0];
            } else {
                $ret[$name] = '';
            }
        }
        return $ret;
    }

    public function get_critics_meta_by_movie($pid) {
        $sql = sprintf("SELECT id, cid, type, state, rating "
                . "FROM {$this->db['meta']} "
                . "WHERE fid=%d", (int) $pid);
        $result = $this->db_results($sql);
        return $result;
    }

    public function get_critics_meta_weights($fid = 0) {
        $sql = sprintf("SELECT cid, rating "
                . "FROM {$this->db['meta']} "
                . "WHERE fid=%d", $fid);
        $results = $this->db_results($sql);
        $ret = array();
        if ($results) {
            foreach ($results as $item) {
                $ret[$item->cid] = $item->rating;
            }
            arsort($ret);
        }
        return $ret;
    }

    public function get_top_critics_meta($cid) {
        // 1. Get meta where state = Approved
        $sql = sprintf("SELECT * "
                . "FROM {$this->db['meta']} "
                . "WHERE cid=%d AND type>0 AND state=1 ORDER BY rating DESC", (int) $cid);
        $result = $this->db_fetch_row($sql);

        if ($result) {
            return $result;
        }

        // 2. Get any state
        $sql = sprintf("SELECT * "
                . "FROM {$this->db['meta']} "
                . "WHERE cid=%d AND type>0 AND state>0 ORDER BY rating DESC", (int) $cid);
        $result = $this->db_fetch_row($sql);

        if (!$result) {
            $result = 0;
        }

        return $result;
    }

    public function get_critic_meta_state($cid, $fid) {
        $sql = sprintf("SELECT state, type "
                . "FROM {$this->db['meta']} "
                . "WHERE cid=%d AND fid=%d", (int) $cid, (int) $fid);
        $result = $this->db_fetch_row($sql);
        return $result;
    }

    public function get_critics_meta_and_posts_by_movie($pid) {
        $sql = sprintf("SELECT m.cid, m.type, m.state, m.rating, p.title, p.link, a.name "
                . "FROM {$this->db['meta']} m "
                . "INNER JOIN {$this->db['posts']} p ON p.id = m.cid "
                . "INNER JOIN {$this->db['authors_meta']} am ON am.cid = p.id "
                . "INNER JOIN {$this->db['authors']} a ON a.id = am.aid "
                . "WHERE fid=%d", (int) $pid);
        $result = $this->db_results($sql);
        return $result;
    }

    public function find_top_rating_no_meta($limit, $debug) {
        // Find critic post that no top_rating

        if ($debug) {
            $sql = sprintf("SELECT COUNT(p.id) FROM {$this->db['posts']} p "
                    . "LEFT JOIN {$this->db['meta']} m ON p.id = m.cid "
                    . "WHERE p.top_movie > 0 AND m.id is NULL ORDER BY p.id DESC LIMIT %d", (int) $limit);

            $count = $this->db_get_var($sql);
            print "Count: $count\n";
        }

        $sql = sprintf("SELECT DISTINCT p.id, p.top_movie FROM {$this->db['posts']} p "
                . "LEFT JOIN {$this->db['meta']} m ON p.id = m.cid "
                . "WHERE p.top_movie > 0 AND m.id is NULL ORDER BY p.id DESC LIMIT %d", (int) $limit);

        $posts = $this->db_results($sql);
        if ($debug) {
            print_r($posts);
        }
        if (sizeof($posts)) {
            foreach ($posts as $post) {
                $this->update_critic_top_movie($post->id);
            }
        }
    }

    public function find_top_rating_meta($limit, $debug) {
        // Find critic post that no top_rating
        $sql = sprintf("SELECT DISTINCT p.id FROM {$this->db['posts']} p "
                . "INNER JOIN {$this->db['meta']} m ON p.id = m.cid "
                . "WHERE p.top_movie <= 1 ORDER BY p.id DESC LIMIT %d", (int) $limit);

        $posts = $this->db_results($sql);
        if (sizeof($posts)) {
            foreach ($posts as $post) {
                $this->update_critic_top_movie($post->id);
            }
        }
    }

    public function get_top_movie($cid) {
        $top_meta = $this->get_top_critics_meta($cid);
        $top_movie = 0;
        if ($top_meta) {
            $top_movie = $top_meta->fid;
        }
        return $top_movie;
    }

    public function get_critic_slug($post) {
        //TODO refactor
        $reg = '/[^a-zA-Z0-9\-_ ]+/U';
        $author_type = $this->get_author_type($post->author_type);
        $author_name = str_replace(' ', '_', preg_replace($reg, '', $post->author_name));
        $post_title = str_replace(' ', '_', $this->crop_text(preg_replace($reg, '', $post->title), 50, false));
        return $post->id . '-' . $author_type . '-' . $author_name . '-' . $post_title;
    }

    /*
     * Rating get
     */

    public function get_post_rating($cid) {
        $sql = sprintf("SELECT * FROM {$this->db['rating']} WHERE cid = %d", (int) $cid);
        $result = $this->db_fetch_row($sql);
        $ret = $this->get_rating_array($result);
        return $ret;
    }

    public function get_post_rating_id($cid) {
        $sql = sprintf("SELECT id FROM {$this->db['rating']} WHERE cid = %d", (int) $cid);
        $result = $this->db_get_var($sql);
        return $result;
    }

    public function get_rating_array($result) {
        $ret = array();
        if ($result) {
            $rating = array();
            $rating_fields = array(
                'rating' => 'r',
                'hollywood' => 'h',
                'patriotism' => 'p',
                'misandry' => 'm',
                'affirmative' => 'a',
                'lgbtq' => 'l',
                'god' => 'g',
                'vote' => 'v',
                'ip' => 'ip');

            foreach ($result as $key => $value) {
                if (isset($rating_fields[$key])) {
                    $rating[$rating_fields[$key]] = $value;
                }
            }

            foreach ($this->def_rating as $key => $value) {
                $ret[$key] = isset($rating[$key]) ? $rating[$key] : $value;
            }
        }
        return $ret;
    }

    public function get_post_rating_full($cid) {
        // UNUSED
        return $this->get_post_rating($cid);
    }

    public function get_post_rating_def() {
        return $this->def_rating;
    }

    public function get_rating_from_postmeta($meta) {
        $options = array();

        //Add post rating
        if ($meta['wpcr3_review_rating'][0]) {
            $options['r'] = trim($meta['wpcr3_review_rating'][0]);
        }
        if ($meta['wpcr3_review_rating_hollywood'][0]) {
            $options['h'] = trim($meta['wpcr3_review_rating_hollywood'][0]);
        }
        if ($meta['wpcr3_review_rating_patriotism'][0]) {
            $options['p'] = trim($meta['wpcr3_review_rating_patriotism'][0]);
        }
        if ($meta['wpcr3_review_rating_misandry'][0]) {
            $options['m'] = trim($meta['wpcr3_review_rating_misandry'][0]);
        }
        if ($meta['wpcr3_review_rating_affirmative'][0]) {
            $options['a'] = trim($meta['wpcr3_review_rating_affirmative'][0]);
        }
        if ($meta['wpcr3_review_rating_lgbtq'][0]) {
            $options['l'] = trim($meta['wpcr3_review_rating_lgbtq'][0]);
        }
        if ($meta['wpcr3_review_rating_god'][0]) {
            $options['g'] = trim($meta['wpcr3_review_rating_god'][0]);
        }
        if ($meta['wpcr3_rating_vote'][0]) {
            $options['v'] = trim($meta['wpcr3_rating_vote'][0]);
        }
        // Other fields
        $ip = $meta['wpcr3_review_ip'][0];
        if ($ip && $ip != '127.0.0.1') {
            $options['ip'] = trim($meta['wpcr3_review_ip'][0]);
        }
        if ($meta['wpcr3_review_email'][0]) {
            $options['em'] = trim($meta['wpcr3_review_email'][0]);
        }
        if ($meta['wpcr3_review_website'][0]) {
            $options['we'] = trim($meta['wpcr3_review_website'][0]);
        }
        return $options;
    }

    /*
     * Rating set
     */

    public function add_rating($cid = 0, $rating = array(), $force = false) {

        //Check the post already in db
        $already = $this->get_post_rating($cid);
        if ($already) {
            if ($force) {
                return $this->update_post_rating($cid, $rating);
            }
            return '';
        }

        $ret = array();
        foreach ($this->def_rating as $key => $value) {
            $ret[$key] = isset($rating[$key]) ? $rating[$key] : $value;
        }

        $options = '';

        $data = array(
            'cid' => $cid,
            'rating' => round((float) $ret['r'], 1),
            'hollywood' => $ret['h'],
            'patriotism' => $ret['p'],
            'misandry' => $ret['m'],
            'affirmative' => $ret['a'],
            'lgbtq' => $ret['l'],
            'god' => $ret['g'],
            'vote' => $ret['v'],
            'ip' => $ret['ip'],
            'options' => $options
        );

        try {
            $id = $this->sync_insert_data($data, $this->db['rating'], false, $this->sync_data);
        } catch (Exception $exc) {
            $id = 0;
        }
        return $id;
    }

    public function update_post_rating($cid, $rating) {
        if ($cid && $rating) {

            $ret = array();
            foreach ($this->def_rating as $key => $value) {
                $ret[$key] = isset($rating[$key]) ? $rating[$key] : $value;
            }

            $options = '';

            $data = array(
                'rating' => round((float) $ret['r'], 1),
                'hollywood' => $ret['h'],
                'patriotism' => $ret['p'],
                'misandry' => $ret['m'],
                'affirmative' => $ret['a'],
                'lgbtq' => $ret['l'],
                'god' => $ret['g'],
                'vote' => $ret['v'],
                'ip' => $ret['ip'],
                'options' => $options
            );

            $rid = $this->get_post_rating_id($cid);
            $this->sync_update_data($data, $rid, $this->db['rating'], $this->sync_data);

            return true;
        }
        return false;
    }

    public function transit_post_rating($id, $content, $update_content = true) {
        $staff_rating = $this->find_staff_rating($content);
        if ($staff_rating) {
            // Add rating
            $add = $this->add_rating($id, $staff_rating, true);
            if ($update_content) {
                // Update content
                $regv = '#\[[^\]]+\]#';
                $new_content = $content;
                $new_content = preg_replace($regv, '', $new_content);
                if ($new_content != $content) {
                    $this->update_post_content($id, $new_content);
                }
            }
        }
        if (!$staff_rating) {
            $staff_rating = $this->find_staff_rating_from_images($content);
            if ($staff_rating) {
                $add = $this->add_rating($id, $staff_rating, true);
                // Update content
                if ($update_content) {
                    $regexps = $this->get_rating_regs();
                    $new_content = $content;
                    foreach ($regexps as $reg => $key) {
                        $new_content = preg_replace($reg, '', $new_content);
                    }
                    $new_content = preg_replace('/<p>[^<]*<br[^>]*>[^<]*<br[^>]*>.*<\/p>/Us', '', $new_content);
                    if ($new_content != $content) {
                        $this->update_post_content($id, $new_content);
                    }
                }
            }
        }
        return $staff_rating;
    }

    public function find_staff_rating($content) {
        // Get rating code    
        $meta = array();
        $regv = '#\[stfu_ratings([^\]]+)\]#';
        if (preg_match($regv, $content, $mach)) {
            $content = str_replace($mach[0], '', $content);
            $array = explode(' ', $mach[1]);
            foreach ($array as $val) {
                if ($val) {
                    $val = explode('=', $val);
                    $current_type = trim($val[0]);
                    $current_value = trim(str_replace('"', '', $val[1]));
                    $curentpercent = 0;
                    if (strstr($current_value, '.')) {
                        $current_value_array = explode('.', $current_value);
                        $current_value = $current_value_array[0];
                        $curentpercent = 1;
                    }
                    if ($current_type == 'worthwhile') {
                        $current_type = 'wpcr3_review_rating';
                        $meta[$current_type] = array($current_value);
                    } else if ($current_type == 'slider') {
                        $current_type = 'wpcr3_rating_vote';
                        if ($current_value == 'pay') {
                            $current_value = 1;
                        } else if ($current_value == 'free') {
                            $current_value = 3;
                        } else if ($current_value == 'skip') {
                            $current_value = 2;
                        }
                        $meta[$current_type] = array($current_value);
                    } else {
                        if ($current_value == 0) {
                            continue;
                        }
                        $type = 'wpcr3_review_rating_' . $current_type;
                        $meta[$type] = array($current_value);
                    }
                }
            }
            return $this->get_rating_from_postmeta($meta);
        }
        return array();
    }

    public function get_rating_regs() {
        $rating_regs = array(
            "/<img[^>]+wp-content\/uploads\/2017\/01\/01_star_(\d)_and_(\d)half_out_of_5[^>]+>/" => 'worthwhile',
            "/<img[^>]+wp-content\/uploads\/2017\/01\/02_poop_(\d)_and_(\d)half_out_of_5[^>]+>/" => 'hollywood',
            "/<img[^>]+wp-content\/uploads\/2017\/02\/03_PTRT_(\d)_and_(\d)half_out_of[^>]+>/" => 'patriotism',
            "/<img[^>]+wp-content\/uploads\/2017\/01\/04_CNT_(\d)_and_(\d)half_out_of_5[^>]+>/" => 'misandry',
            "/<img[^>]+wp-content\/uploads\/2017\/01\/05_profit_muhammad_(\d)_and_(\d)half_out_of_5[^>]+>/" => 'affirmative',
            "/<img[^>]+wp-content\/uploads\/2017\/01\/06_queer_(\d)_and_(\d)half_out_of_5[^>]+>/" => 'lgbt',
            "/<img[^>]+wp-content\/uploads\/2017\/01\/07_cliche_not_brave_(\d)_and_(\d)half_out_of_5[^>]+>/" => 'god',
            "/<img[^>]+2017\/02\/slider_green_pay_drk\.png[^>]+>/" => 1,
            "/<img[^>]+2017\/02\/slider_red_skip_drk\.png[^>]+>/" => 2,
            "/<img[^>]+2017\/01\/slider_orange_free\.png[^>]+>/" => 3,
            "/<img[^>]+2017\/01\/stfu_ratings_slider_width_3[^>]+>/" => 0,
        );
        return $rating_regs;
    }

    public function find_staff_rating_from_images($content) {

        $rating_array = array();

        $rating_regs = $this->get_rating_regs();
        foreach ($rating_regs as $reg => $key) {
            if (preg_match($reg, $content, $match)) {
                if (is_int($key)) {
                    if ($key === 0) {
                        continue;
                    }
                    $rating_array['wpcr3_rating_vote'] = array($key);
                } else if ($key == 'worthwhile') {
                    $rating_array['wpcr3_review_rating'] = array($match[1]);
                } else {
                    $rating_array['wpcr3_review_rating_' . $key] = array($match[1]);
                }
            }
        }

        if ($rating_array) {
            return $this->get_rating_from_postmeta($rating_array);
        }
        return array();
    }

    public function validate_link_hash($link, $link_hash) {
        if ($link) {
            $new_hash = $this->link_hash($link);
            if ($new_hash == $link_hash) {
                return true;
            }
        }
        return false;
    }

    public function update_link_hash($id, $link) {
        if ($link) {
            $link_hash = $this->link_hash($link);

            $data = array(
                'link_hash' => $link_hash,
            );

            $this->sync_update_data($data, $id, $this->db['posts'], $this->sync_data);

            return $link_hash;
        }
        return '';
    }

    public function update_post_rating_options() {
        //One time task
        $sql = "SELECT id, cid, options FROM {$this->db['rating']} WHERE options !=''";
        $results = $this->db_results($sql);
        if ($results) {
            /*
              [r] => 4
              [h] => 1
              [p] => 1
              [m] => 1
              [a] => 1
              [l] => 1
              [g] => 2
              [v] => 1
              [ip] => 50.27.114.245
             */
            foreach ($results as $result) {
                if ($result->options) {
                    $rating = unserialize($result->options);
                    if ($rating) {
                        $ret = array();
                        foreach ($this->def_rating as $key => $value) {
                            $ret[$key] = isset($rating[$key]) ? $rating[$key] : $value;
                        }

                        $options = '';
                        $data = array(
                            'rating' => $ret['r'],
                            'hollywood' => $ret['h'],
                            'patriotism' => $ret['p'],
                            'misandry' => $ret['m'],
                            'affirmative' => $ret['a'],
                            'lgbtq' => $ret['l'],
                            'god' => $ret['g'],
                            'vote' => $ret['v'],
                            'ip' => $ret['ip'],
                            'options' => $options
                        );
                        $id = $result->id;
                        $this->sync_update_data($data, $id, $this->db['rating'], $this->sync_data);
                    }
                }
            }
            print 'Done';
        }
    }

    /*
     * Crowdsource IP
     */

    public function bulk_change_ip_list_type_crowd($ids, $status = '', $table = '') {

        if ($ids && sizeof($ids)) {
            foreach ($ids as $cid) {

                $sql = sprintf("SELECT `ip` FROM `data_{$table}` WHERE id = %d", (int) $cid);

                $ip = $this->db_get_var($sql);

                if ($ip) {

                    //IP exist. Change status
                    $type = 0;
                    if ($status == 'wl') {
                        $type = 1;
                    }if ($status == 'gl') {
                        $type = 2;
                    }if ($status == 'bl') {
                        $type = 3;
                    }

                    if ($type == 0) {
                        //remove ip
                        $this->remove_ip_from_list($ip);
                    } else {
                        //Change status
                        $this->change_ip_type($ip, $type);
                    }
                }
            }
        }
    }

    /*
     * Audience IP
     */

    public function bulk_change_ip_list_type($ids, $status = '') {

        if ($ids && sizeof($ids)) {
            foreach ($ids as $cid) {
                $rating = $this->get_post_rating($cid);
                if ($rating['ip']) {
                    $ip = $rating['ip'];
                    //IP exist. Change status
                    $type = 0;
                    if ($status == 'wl') {
                        $type = 1;
                    }if ($status == 'gl') {
                        $type = 2;
                    }if ($status == 'bl') {
                        $type = 3;
                    }

                    if ($type == 0) {
                        //remove ip
                        $this->remove_ip_from_list($ip);
                    } else {
                        //Change status
                        $this->change_ip_type($ip, $type);
                    }
                }
            }
        }
    }

    public function bulk_change_ip_list_type_by_ips($ips, $status = '') {

        if ($ips && sizeof($ips)) {
            foreach ($ips as $id) {
                $type = 0;
                if ($status == 'wl') {
                    $type = 1;
                }if ($status == 'gl') {
                    $type = 2;
                }if ($status == 'bl') {
                    $type = 3;
                }

                $ip_item = $this->get_ip_by_id($id);
                if ($ip_item) {
                    $ip = $ip_item->ip;
                    if ($type == 0) {
                        //remove ip
                        $this->remove_ip_from_list($ip);
                    } else {
                        //Change status
                        $this->change_ip_type($ip, $type);
                    }
                }
            }
        }
    }

    public function change_ip_type($ip, $type) {
        $changed = false;
        //Get IP
        $ip_exist = $this->get_ip($ip);
        if ($ip_exist) {
            //Update IP
            if ($type != $ip_exist->type) {
                $this->update_ip_type_by_id($ip_exist->id, $type);
                $changed = true;
            }
        } else {
            //Add IP
            $this->add_ip($ip, $type);
            $changed = true;
        }
    }

    public function get_ip_by_id($id) {
        $sql = sprintf("SELECT id,type,ip FROM {$this->db['ip']} WHERE id = %d", (int) $id);
        $result = $this->db_fetch_row($sql);
        return $result;
    }

    public function get_ip($ip) {
        $sql = sprintf("SELECT id,type,ip FROM {$this->db['ip']} WHERE ip = '%s'", $this->escape($ip));
        $result = $this->db_fetch_row($sql);
        return $result;
    }

    public function add_ip($ip, $type) {
        $sql = sprintf("INSERT INTO {$this->db['ip']} (type, ip) VALUES ('%d', '%s')", (int) $type, $ip);
        $this->db_query($sql);
    }

    public function get_or_create_ip($ip = '', $type = 0) {
        if (!$ip) {
            return 0;
        }
        $ip_data = $this->get_ip($ip);
        if ($ip_data) {
            return $ip_data;
        }

        $this->add_ip($ip, $type);
        $id = $this->getInsertId('id', $this->db['ip']);
        $ip_data = $this->get_ip($ip);
        return $ip_data;
    }

    public function update_ip_type_by_id($id, $type = 0) {
        $sql = sprintf("UPDATE {$this->db['ip']} SET type=%d WHERE id=%d", (int) $type, (int) $id);
        $this->db_query($sql);
    }

    public function remove_ip_from_list($ip) {
        $sql = sprintf("DELETE FROM {$this->db['ip']} WHERE ip='%s'", $this->escape($ip));
        $this->db_query($sql);
    }

    public function get_ips($status, $page, $orderby, $order) {
        $page -= 1;
        $start = $page * $this->perpage;

        //Type
        $status_query = '';
        if ($status != -1) {
            $status_query = " WHERE type = " . (int) $status;
        }

        //Sort
        $and_orderby = '';
        if ($orderby && in_array($orderby, $this->sort_pages)) {
            $and_orderby = ' ORDER BY ' . $orderby;
            if ($order) {
                $and_orderby .= ' ' . $order;
            }
        } else {
            $and_orderby = " ORDER BY id DESC";
        }

        $limit = '';
        if ($this->perpage > 0) {
            $limit = " LIMIT $start, " . $this->perpage;
        }

        $sql = "SELECT id, type, ip FROM {$this->db['ip']}" . $status_query . $and_orderby . $limit;

        $result = $this->db_results($sql);

        return $result;
    }

    public function get_ip_count($status = -1) {
        // Custom status

        $status_query = '';
        if ($status != -1) {
            $status_query = " WHERE type = " . (int) $status;
        }

        $query = "SELECT COUNT(id) FROM {$this->db['ip']}" . $status_query;

        $result = $this->db_get_var($query);
        return $result;
    }

    public function get_ip_states() {
        $count = $this->get_ip_count();
        $states = array(
            '-1' => array(
                'title' => 'All',
                'count' => $count
            )
        );
        foreach ($this->ip_status as $key => $value) {
            $states[$key] = array(
                'title' => $value,
                'count' => $this->get_ip_count($key));
        }
        return $states;
    }

    /* Review crowd */

    public function submit_review_crowd($data) {
        /*
         * stdClass Object ( [
         * id] => 2 
         * [review_id] => 36799 
         * [broken_link] => 0 
         * [source_link] => 
         * [incorrect_item] => 1 
         * [movies] => {"43234":"remove","11733":"mention"} 
         * [irrelevant] => 0 
         * [remove] => 0 
         * [blur] => 0 
         * [comment] => 
         * [user] => 254 
         * [ip] => 188.234.245.4 
         * [add_time] => 1638608052 
         * [status] => 1 )
         */

        $cid = $data->review_id;

        //Remove item
        if ($data->remove) {
            // Set trash status
            $this->trash_post_by_id($cid);
            return true;
        }

        $ret = false;

        // Update meta
        if ($data->incorrect_item && $data->movies) {
            $meta_arr = json_decode($data->movies);
            if ($meta_arr && sizeof($meta_arr)) {
                foreach ($meta_arr as $fid => $action) {
                    $sql = sprintf("SELECT * FROM {$this->db['meta']} WHERE cid=%d AND fid=%d", (int) $cid, (int) $fid);
                    $meta_exist = $this->db_fetch_row($sql);

                    /*
                      State:
                      1 => 'Approved',
                      2 => 'Auto',
                      0 => 'Unapproved'
                     */
                    $state = 1;
                    /*
                     * Type:
                      0 => 'None',
                      1 => 'Proper Review',
                      2 => 'Contains Mention',
                      3 => 'Related Article'
                     */
                    $type = 0;

                    if ($meta_exist) {
                        $state = $meta_exist->state;
                        $type = $meta_exist->type;
                    }
                    if ($action == 'remove') {
                        //Remove meta
                        $state = 0;
                        $type = 0;
                    } else {
                        $state = 1;
                        if ($action == 'proper') {
                            $type = 1;
                        } else if ($action == 'mention') {
                            $type = 2;
                        } else if ($action == 'related') {
                            $type = 3;
                        }
                    }

                    if ($meta_exist) {
                        //Update meta
                        if ($state != $meta_exist->state || $type != $meta_exist->type) {
                            $this->update_post_meta($fid, $type, $state, $cid, $meta_exist->rating);
                            //Update post cache
                            $this->update_post_date_add($cid);
                            $ret = true;
                        }
                    } else {
                        //Add meta
                        $this->add_post_meta($fid, $type, $state, $cid);
                        $ret = true;
                    }
                }
            }
        }

        $post = '';

        //Broken link
        if ($data->broken_link && $data->source_link) {
            // Update post link
            $post = $this->get_post($cid);
            if ($post) {
                $link = $data->source_link;
                $this->update_post($cid, $post->date, $post->status, $link, $post->title, $post->content, $post->type);
                $ret = true;
            }
        }

        //Blur the content
        if ($data->blur) {
            if (!$post) {
                $post = $this->get_post($cid);
            }
            if ($post) {
                if (!$post->blur) {
                    $post_blur = 1;
                    $this->update_post($cid, $post->date, $post->status, $post->link, $post->title, $post->content, $post->type, $post_blur);
                    $ret = true;
                }
            }
        }

        return $ret;
    }

    /*
     * Settings
     */

    public function get_settings($cache = true) {
        if ($cache && $this->settings) {
            return $this->settings;
        }
        // Get settings from options
        $settings = unserialize($this->get_option('critic_matic_settings', '', false));

        if ($settings && sizeof($settings)) {
            foreach ($this->settings_def as $key => $value) {
                if (!isset($settings[$key])) {
                    //replace empty settings to default
                    $settings[$key] = $value;
                } else {
                    if ($key == 'audience_desc') {
                        $audience_desc = $settings[$key];
                        $au_decode = array();
                        foreach ($audience_desc as $k => $v) {
                            $au_decode[$k] = base64_decode($v);
                        }
                        $settings[$key] = $au_decode;
                    } else if ($key == 'games_tags') {
                        $settings[$key] = base64_decode($settings[$key]);
                    } else if ($key == 'actors_star_ss' || $key == 'actors_main_ss') {
                        $settings[$key] = base64_decode($settings[$key]);
                    }
                }
            }
        } else {
            $settings = $this->settings_def;
        }
        $this->settings = $settings;
        return $settings;
    }

    public function update_settings($form) {

        $settings_prev = unserialize($this->get_option('critic_matic_settings', false));

        $ss = $settings_prev;
        foreach ($form as $key => $value) {
            if (isset($this->settings_def[$key])) {
                $ss[$key] = $value;
            }
        }

        if (isset($form['posts'])) {
            $ss['critics_unique'] = $form['critics_unique'] ? 1 : 0;
            $ss['posts_type_1'] = $form['posts_type_1'] ? 1 : 0;
            $ss['posts_type_2'] = $form['posts_type_2'] ? 1 : 0;
            $ss['posts_type_3'] = $form['posts_type_3'] ? 1 : 0;
            $ss['audience_unique'] = $form['audience_unique'] ? 1 : 0;            
            $ss['audience_top_unique'] = $form['audience_top_unique'] ? 1 : 0;
        }

        if (isset($form['parser_proxy'])) {
            $ss['parser_proxy'] = base64_encode($form['parser_proxy']);
            $ss['parser_arhive_async'] = $form['parser_arhive_async'] ? 1 : 0;
        }

        if (isset($form['games_tags'])) {
            $ss['games_tags'] = base64_encode($form['games_tags']);
        }

        $audience_desc_encode = array();
        if (isset($form['audience_descriptions'])) {
            foreach ($this->settings_def['audience_desc'] as $key => $value) {
                $audience_desc_encode[$key] = base64_encode(trim($form['au_' . $key]));
            }
            $ss['audience_desc'] = $audience_desc_encode;
        }

        // Upadate cookie content
        if (isset($form['parser_cookie_text'])) {
            $cookie_path = $this->settings_def['parser_cookie_path'];
            if (file_exists($cookie_path)) {
                unlink($cookie_path);
            }
            file_put_contents($cookie_path, $form['parser_cookie_text']);
        }

        // Actors rating logic
        if (isset($form['actors_rating'])) {
            $actors_star_ss = preg_replace('#^.*(/search/.*)$#', "$1", $form['actors_star_ss']);
            $actors_main_ss = preg_replace('#^.*(/search/.*)$#', "$1", $form['actors_main_ss']);
            $ss['actors_star_ss'] = base64_encode($actors_star_ss);
            $ss['actors_main_ss'] = base64_encode($actors_main_ss);
            if ($form['stars_reset'] || $form['main_reset']) {
                try {
                    if (!class_exists('MoviesActorWeight')) {
                        require_once( CRITIC_MATIC_PLUGIN_DIR . 'MoviesActorWeight.php' );
                    }
                    $maw = new MoviesActorWeight($this);
                    if ($form['stars_reset']) {
                       $opt = new MoviesActorStarOptions();
                       $opt->reset();
                    } else {
                       $opt = new MoviesActorMainOptions(); 
                       $opt->reset();
                    }
                } catch (Exception $exc) {
                    
                }
            }
        }

        // Update options        
        $this->update_option('critic_matic_settings', serialize($ss));

        // Update settings
        $this->settings = $this->get_settings();
    }

    public function get_parser_proxy($cache = true) {
        $id = 1;
        if ($cache) {
            static $dict;
            if (is_null($dict)) {
                $dict = array();
            }

            if (isset($dict[$id])) {
                return $dict[$id];
            }
        }
        $proxy_arr = array();
        $ss = $this->get_settings();
        if ($ss['parser_proxy']) {
            $proxy_text = base64_decode($ss['parser_proxy']);

            if ($proxy_text) {
                if (strstr($proxy_text, "\n")) {
                    $proxy_arr = explode("\n", $proxy_text);
                } else {
                    $proxy_arr = array($proxy_text);
                }
            }
        }
        if ($cache) {
            $dict[$id] = $proxy_arr;
        }
        return $proxy_arr;
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
                    print "Cron " . $cron_name . " already progress.";
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
        return 'cm_cron_' . $cron_name;
    }

    public function get_critic_crowd($link_hash = '') {
        $sql = sprintf("SELECT * FROM {$this->db['critic_crowd']} WHERE link_hash='%s'", $link_hash);
        $result = $this->db_fetch_row($sql);
        return $result;
    }

    public function unic_id() {
        $ip = $this->get_remote_ip();
        $unic_id = md5($_SERVER["HTTP_USER_AGENT"] . $ip);
        return $unic_id;
    }

    /*
     * Other
     */

    public function crop_text($text = '', $length = 10, $tchk = true) {
        if (strlen($text) > $length) {
            $pos = strpos($text, ' ', $length);
            if ($pos != null)
                $text = substr($text, 0, $pos);
            if ($tchk) {
                $text = $text . '...';
            }
        }
        return $text;
    }

    public function add_sphinx_counter() {
        // maxdocid FROM sph_counter WHERE name = "critic"
        $names = array('critic', 'movie', 'tvseries');
        foreach ($names as $name) {
            $sql = sprintf("SELECT maxdocid FROM sph_counter WHERE name='%s'", $name);
            $id = $this->db_get_var($sql);

            if (!$id) {
                $sql = sprintf("INSERT INTO sph_counter (maxdocid, name) VALUES (%d, '%s')", 0, $name);
                $this->db_query($sql);
            }
        }
    }

    // User permissions
    public function user_can() {
        global $user_ID;
        if (user_can($user_ID, 'editor') || user_can($user_ID, 'administrator')) {
            return true;
        }
        return false;
    }

    private function get_perpage() {
        $this->perpage = isset($_GET['perpage']) ? (int) $_GET['perpage'] : $this->perpage;
        return $this->perpage;
    }

    public function get_sync_tabs($tabs) {
        $ret = array();
        foreach ($tabs as $key => $item) {
            $title = $item['title'];
            $view = $item['sync_view'];

            if ($view > 0 && $this->sync_status != $view) {
                continue;
            }
            $ret[$key] = $title;
        }
        return $ret;
    }

    public function critic_delta_cron() {
        $ts_dir = ABSPATH . "wp-content/uploads/docker_sphinx.txt";
        if (file_exists($ts_dir)) {
            unlink($ts_dir);
        }

        $data = array(
            'cmd' => 'critic_delta',
        );

        if (!defined('SYNC_HOST')) {
            return false;
        }
        $host = SYNC_HOST;
        return $this->post($data, $host);
    }

    public function get_post_view_type($url = '') {
        $view_type = 0;
        foreach ($this->post_view_type_url as $str => $val) {
            if (strstr($url, $str)) {
                $view_type = $val;
                break;
            }
        }
        return $view_type;
    }

    public function get_critic_verdict($pid) {
        $sql = sprintf("SELECT * FROM {$this->db['reviews_rating']} WHERE cid=%d", (int) $pid);
        $result = $this->db_fetch_row($sql);
        return $result;
    }

    /*
     * Geo data   
     */

    public function get_geo_flag_by_ip($ip, $size = 24) {
        $ret = array('code' => '', 'name' => '', 'path' => '');
        if (!$ip) {
            return $ret;
        }

        static $dict;
        if (is_null($dict)) {
            $dict = array();
        }
        // Country code
        if (isset($dict[$ip])) {
            $country_code = $dict[$ip];
        } else {
            $data = $this->getGeoData($ip);
            $country_code = isset($data['country_code']) ? $data['country_code'] : '';
            $dict[$ip] = $country_code;
        }

        // Sizes
        $sizes = array(16, 24, 32, 48, 64);

        if (!in_array($size, $sizes)) {
            $size = 24;
        }

        $country_name = '';

        // Country path
        $country_path = '';
        if ($country_code) {
            $country_name = $this->getCountryByCode($country_code);
            $country_path = '/wp-content/plugins/critic_matic/lib/geoip/flags-iso/' . $size . '/' . strtolower($country_code) . '.png';
            if (!file_exists(ABSPATH . $country_path)) {
                $country_path = '';
            }
        }

        return array('code' => $country_code, 'name' => $country_name, 'path' => $country_path);
    }

    /**
     * функция определяет ip адрес по глобальному массиву $_SERVER
     * ip адреса проверяются начиная с приоритетного, для определения возможного использования прокси
     * @return ip-адрес
     */
    function get_remote_ip() {
        $ip = false;
        if (isset($_SERVER['HTTP_CF_CONNECTING_IP']))
            $ipa[] = trim($_SERVER['HTTP_CF_CONNECTING_IP']);

        if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
            $ipa[] = trim(strtok($_SERVER['HTTP_X_FORWARDED_FOR'], ','));

        if (isset($_SERVER['HTTP_CLIENT_IP']))
            $ipa[] = $_SERVER['HTTP_CLIENT_IP'];

        if (isset($_SERVER['REMOTE_ADDR']))
            $ipa[] = $_SERVER['REMOTE_ADDR'];

        if (isset($_SERVER['HTTP_X_REAL_IP']))
            $ipa[] = $_SERVER['HTTP_X_REAL_IP'];

        // проверяем ip-адреса на валидность начиная с приоритетного.
        foreach ($ipa as $ips) {
            //  если ip валидный обрываем цикл, назначаем ip адрес и возвращаем его
            if ($this->is_valid_ip($ips)) {
                $ip = $ips;
                break;
            }
        }

        return $ip;
    }

    /**
     * функция для проверки валидности ip адреса
     * @param ip адрес в формате 1.2.3.4
     * @return bolean : true - если ip валидный, иначе false
     */
    private function is_valid_ip($ip = null) {
        if (preg_match("#^([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})$#", $ip))
            return true; // если ip-адрес попадает под регулярное выражение, возвращаем true

        return false; // иначе возвращаем false
    }

    public function getGeoData($ip = '') {

        if ($ip == '127.0.0.1') {
            return '';
        }

        $record = $this->getGeoIp2Data($ip);

        if (isset($record->country->isoCode)) {
            $res = $record->country->isoCode;
        }

        if (!$res) {
            $res = $this->getGeoIpCountry($ip);
        }

        if ($res) {
            // Krym
            $res = $this->validateCrym($res, $ip);
            return array('country_code' => $res);
        }

        return '';
    }

    private function get_reader() {
        if (!$this->reader) {
            if (!class_exists('GeoIp2')) {
                // geoip2
                require_once(ABSPATH . "wp-content/plugins/critic_matic/lib/geoip2/geoip2.phar");
                // use GeoIp2\Database\Reader;                    
            }

            $country_db = 'GeoLite2-Country.mmdb';
            $this->reader = new GeoIp2\Database\Reader(ABSPATH . 'wp-content/plugins/critic_matic/lib/geoip2/db/' . $country_db);
        }
        return $this->reader;
    }

    private function get_reader_city() {
        if (!$this->reader_city) {
            if (!class_exists('GeoIp2')) {
                // geoip2
                require_once(ABSPATH . "wp-content/plugins/critic_matic/lib/geoip2/geoip2.phar");
                // use GeoIp2\Database\Reader;                    
            }

            $city_db = 'GeoLite2-City.mmdb';
            $this->reader_city = new GeoIp2\Database\Reader(ABSPATH . 'wp-content/plugins/critic_matic/lib/geoip2/db/' . $city_db);
        }
        return $this->reader_city;
    }

    private function get_geoip() {
        if (!$this->geoip) {
            if (!function_exists('GeoIP_record_by_addr')) {
                require_once( ABSPATH . 'wp-content/plugins/critic_matic/lib/geoip/geoipcity.inc' );
            }
            $this->geoip = geoip_open(ABSPATH . "wp-content/plugins/critic_matic/lib/geoip/db/GeoIP.dat", GEOIP_STANDARD);
        }
        return $this->geoip;
    }

    private function close_geoip() {
        if (function_exists('geoip_close')) {
            geoip_close($this->geoip);
        }
    }

    // Geoip 2
    private function getGeoIp2Data($ip) {

        if ($ip == '127.0.0.1') {
            return '';
        }

        $reader = $this->get_reader();

        try {
            $record = $reader->country($ip);
        } catch (Exception $e) {
            $record = '';
        }

        return $record;
    }

    private function getGeoIpCountry($ip) {
        $res = '';

        $gi = $this->get_geoip();
        try {
            $res = geoip_country_code_by_addr($gi, $ip);
        } catch (Exception $e) {
            //return ''
        }

        return $res;
    }

    private function getGeoIp2CityData($ip) {

        if ($ip == '127.0.0.1') {
            return '';
        }

        $reader_city = $this->get_reader_city();
        try {
            $record = $reader_city->city($ip);
        } catch (Exception $e) {
            $record = '';
        }

        return $record;
    }

    private function validateCrym($ccode, $ip) {
        if ($ccode == 'UA') {
            $record = $this->getGeoIp2CityData($ip);
            if (isset($record->city->name)) {
                // Russian cities    
                $rucity = array('Sevastopol', 'Simferopol', 'Luhansk', 'Donetsk', 'Feodosiya');
                if (in_array($record->city->name, $rucity)) {
                    $ccode = 'RU';
                }
            }

            // Ip mask search
            if ($ccode != 'RU') {
                $rus_ips = array('194.127.112.', '194.127.113.');
                foreach ($rus_ips as $mask) {
                    if (strstr($ip, $mask)) {
                        $ccode = 'RU';
                        break;
                    }
                }
            }
        }
        return $ccode;
    }

    public function getCountryByCode($code) {
        if (!$code)
            return '';
        $names = array(
            "AP" => "Asia/Pacific Region",
            "EU" => "Europe",
            "AD" => "Andorra",
            "AE" => "the United Arab Emirates",
            "AF" => "Afghanistan",
            "AG" => "Antigua and Barbuda",
            "AI" => "Anguilla",
            "AL" => "Albania",
            "AM" => "Armenia",
            "CW" => "Curcao",
            "AO" => "Angola",
            "AQ" => "Antarctica",
            "AR" => "Argentina",
            "AS" => "American Samoa",
            "AT" => "Austria",
            "AU" => "Australia",
            "AW" => "Aruba",
            "AZ" => "Azerbaijan",
            "BA" => "Bosnia and Herzegovina",
            "BB" => "Barbados",
            "BD" => "Bangladesh",
            "BE" => "Belgium",
            "BF" => "Burkina Faso",
            "BG" => "Bulgaria",
            "BH" => "Bahrain",
            "BI" => "Burundi",
            "BJ" => "Benin",
            "BM" => "Bermuda",
            "BN" => "Brunei Darussalam",
            "BO" => "Bolivia",
            "BR" => "Brazil",
            "BS" => "Bahamas",
            "BT" => "Bhutan",
            "BV" => "Bouvet Island",
            "BW" => "Botswana",
            "BY" => "Belarus",
            "BZ" => "Belize",
            "CA" => "Canada",
            "CC" => "Cocos (Keeling) Islands",
            "CD" => "the Democratic Republic of the Congo",
            "CF" => "the Central African Republic",
            "CG" => "Congo",
            "CH" => "Switzerland",
            "CI" => "Cote D'Ivoire",
            "CK" => "Cook Islands",
            "CL" => "Chile",
            "CM" => "Cameroon",
            "CN" => "China",
            "CO" => "Colombia",
            "CR" => "Costa Rica",
            "CU" => "Cuba",
            "CV" => "Cape Verde",
            "CX" => "Christmas Island",
            "CY" => "Cyprus",
            "CZ" => "the Czech Republic",
            "DE" => "Germany",
            "DJ" => "Djibouti",
            "DK" => "Denmark",
            "DM" => "Dominica",
            "DO" => "the Dominican Republic",
            "DZ" => "Algeria",
            "EC" => "Ecuador",
            "EE" => "Estonia",
            "EG" => "Egypt",
            "EH" => "Western Sahara",
            "ER" => "Eritrea",
            "ES" => "Spain",
            "ET" => "Ethiopia",
            "FI" => "Finland",
            "FJ" => "Fiji",
            "FK" => "the Falkland Islands",
            "FM" => "Micronesia",
            "FO" => "Faroe Islands",
            "FR" => "France",
            "SX" => "Sint Maarten (Dutch part)",
            "GA" => "Gabon",
            "GB" => "the United Kingdom",
            "GD" => "Grenada",
            "GE" => "Georgia",
            "GF" => "French Guiana",
            "GH" => "Ghana",
            "GI" => "Gibraltar",
            "GL" => "Greenland",
            "GM" => "Gambia",
            "GN" => "Guinea",
            "GP" => "Guadeloupe",
            "GQ" => "Equatorial Guinea",
            "GR" => "Greece",
            "GS" => "South Georgia and the South Sandwich Islands",
            "GT" => "Guatemala",
            "GU" => "Guam",
            "GW" => "Guinea-Bissau",
            "GY" => "Guyana",
            "HK" => "Hong Kong",
            "HM" => "Heard Island and McDonald Islands",
            "HN" => "Honduras",
            "HR" => "Croatia",
            "HT" => "Haiti",
            "HU" => "Hungary",
            "ID" => "Indonesia",
            "IE" => "Ireland",
            "IL" => "Israel",
            "IN" => "India",
            "IO" => "British Indian Ocean Territory",
            "IQ" => "Iraq",
            "IR" => "Iran Islamic Republic of",
            "IS" => "Iceland",
            "IT" => "Italy",
            "JM" => "Jamaica",
            "JO" => "Jordan",
            "JP" => "Japan",
            "KE" => "Kenya",
            "KG" => "Kyrgyzstan",
            "KH" => "Cambodia",
            "KI" => "Kiribati",
            "KM" => "Comoros",
            "KN" => "Saint Kitts and Nevis",
            "KP" => "Korea Democratic People's Republic of",
            "KR" => "Korea Republic of",
            "KW" => "Kuwait",
            "KY" => "Cayman Islands",
            "KZ" => "Kazakhstan",
            "LA" => "Lao People's Democratic Republic",
            "LB" => "Lebanon",
            "LC" => "Saint Lucia",
            "LI" => "Liechtenstein",
            "LK" => "Sri Lanka",
            "LR" => "Liberia",
            "LS" => "Lesotho",
            "LT" => "Lithuania",
            "LU" => "Luxembourg",
            "LV" => "Latvia",
            "LY" => "Libya",
            "MA" => "Morocco",
            "MC" => "Monaco",
            "MD" => "Moldova",
            "MG" => "Madagascar",
            "MH" => "Marshall Islands",
            "MK" => "Macedonia",
            "ML" => "Mali",
            "MM" => "Myanmar",
            "MN" => "Mongolia",
            "MO" => "Macau",
            "MP" => "Northern Mariana Islands",
            "MQ" => "Martinique",
            "MR" => "Mauritania",
            "MS" => "Montserrat",
            "MT" => "Malta",
            "MU" => "Mauritius",
            "MV" => "Maldives",
            "MW" => "Malawi",
            "MX" => "Mexico",
            "MY" => "Malaysia",
            "MZ" => "Mozambique",
            "NA" => "Namibia",
            "NC" => "New Caledonia",
            "NE" => "Niger",
            "NF" => "Norfolk Island",
            "NG" => "Nigeria",
            "NI" => "Nicaragua",
            "NL" => "the Netherlands",
            "NO" => "Norway",
            "NP" => "Nepal",
            "NR" => "Nauru",
            "NU" => "Niue",
            "NZ" => "New Zealand",
            "OM" => "Oman",
            "PA" => "Panama",
            "PE" => "Peru",
            "PF" => "French Polynesia",
            "PG" => "Papua New Guinea",
            "PH" => "Philippines",
            "PK" => "Pakistan",
            "PL" => "Poland",
            "PM" => "Saint Pierre and Miquelon",
            "PN" => "Pitcairn Islands",
            "PR" => "Puerto Rico",
            "PS" => "Palestinian Territory",
            "PT" => "Portugal",
            "PW" => "Palau",
            "PY" => "Paraguay",
            "QA" => "Qatar",
            "RE" => "Reunion",
            "RO" => "Romania",
            "RU" => "the Russian Federation",
            "RW" => "Rwanda",
            "SA" => "Saudi Arabia",
            "SB" => "Solomon Islands",
            "SC" => "Seychelles",
            "SD" => "Sudan",
            "SE" => "Sweden",
            "SG" => "Singapore",
            "SH" => "Saint Helena",
            "SI" => "Slovenia",
            "SJ" => "Svalbard and Jan Mayen",
            "SK" => "Slovakia",
            "SL" => "Sierra Leone",
            "SM" => "San Marino",
            "SN" => "Senegal",
            "SO" => "Somalia",
            "SR" => "Suriname",
            "ST" => "Sao Tome and Principe",
            "SV" => "El Salvador",
            "SY" => "Syrian Arab Republic",
            "SZ" => "Swaziland",
            "TC" => "Turks and Caicos Islands",
            "TD" => "Chad",
            "TF" => "French Southern Territories",
            "TG" => "Togo",
            "TH" => "Thailand",
            "TJ" => "Tajikistan",
            "TK" => "Tokelau",
            "TM" => "Turkmenistan",
            "TN" => "Tunisia",
            "TO" => "Tonga",
            "TL" => "Timor-Leste",
            "TR" => "Turkey",
            "TT" => "Trinidad and Tobago",
            "TV" => "Tuvalu",
            "TW" => "Taiwan",
            "TZ" => "Tanzania",
            "UA" => "Ukraine",
            "UG" => "Uganda",
            "UM" => "the United States Minor Outlying Islands",
            "US" => "the United States",
            "UY" => "Uruguay",
            "UZ" => "Uzbekistan",
            "VA" => "Holy See (Vatican City State)",
            "VC" => "Saint Vincent and the Grenadines",
            "VE" => "Venezuela",
            "VG" => "Virgin Islands British",
            "VI" => "Virgin Islands U.S.",
            "VN" => "Vietnam",
            "VU" => "Vanuatu",
            "WF" => "Wallis and Futuna",
            "WS" => "Samoa",
            "YE" => "Yemen",
            "YT" => "Mayotte",
            "RS" => "Serbia",
            "ZA" => "South Africa",
            "ZM" => "Zambia",
            "ME" => "Montenegro",
            "ZW" => "Zimbabwe",
            "A1" => "Anonymous Proxy",
            "A2" => "Satellite Provider",
            "O1" => "Other",
            "AX" => "Aland Islands",
            "GG" => "Guernsey",
            "IM" => "the Isle of Man",
            "JE" => "Jersey",
            "BL" => "Saint Barthelemy",
            "MF" => "Saint Martin",
            "BQ" => "Bonaire Saint Eustatius and Saba",
        );
        if (isset($names[$code]))
            return $names[$code];
        else
            return $code;
    }

    public function get_domain_by_url($url = '') {
        $domain = preg_replace('#^([a-z]+\:\/\/[^\/]+)(\/|\?|\#).*#', '$1', $url . '/');
        return $domain;
    }

    public function post($data = array(), $host = '', $timeout = 1) {
        $ss = $this->get_settings();
        $curl_user_agent = $ss['parser_user_agent'];
        $fields_string = http_build_query($data);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $host);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);       
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        if ($curl_user_agent){
            curl_setopt($ch, CURLOPT_USERAGENT, $curl_user_agent);
        }
        
        $result = curl_exec($ch);

        return $result;
    }

    public function theme_table($data) {
        $ret = '';
        if (!empty($data)) {
            $ret .= '<table class="wp-list-table widefat striped table-view-list"><tr>';

            // Получение заголовков (названий полей) из первого объекта
            $firstObject = $data[0];
            foreach ($firstObject as $key => $value) {
                $ret .= "<th>$key</th>";
            }
            $ret .= "</tr>";

            // Вывод данных
            foreach ($data as $object) {
                $ret .= "<tr>";
                foreach ($object as $value) {
                    $ret .= "<td>$value</td>";
                }
                $ret .= "</tr>";
            }

            $ret .= "</table>";
        } else {
            $ret .= "Data not found.";
        }
        return $ret;
    }
}
