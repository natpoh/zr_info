<?php

/**
 * Find critic posts by sphinxsearch
 *
 * @author brahman
 * 
 * 
 * TODO 
 * add top movie link for all user select critic posts
 * Add page for view critics meta: user, auto, no meta
 * 
 */
class CriticSearch extends AbstractDB {

    //Limit of first search
    public $perpage = 30;
    private $cm;
    private $ma;
    private $db;
    private $sps;
    private $wpdb;
    private $search_settings = '';
    private $search_setting_valid_pocent = 10;
    public $facet_limit = 10;
    public $facet_max_limit = 200;
    public $filter_actor_and = '';
    public $filter_custom_and = array();
    public $default_search_settings = array(
        'limit' => 100,
        'name_point_title' => 20,
        'name_point' => 10,
        'name_words_multipler' => 0.2,
        'release_point_title' => 10,
        'release_point' => 10,
        'runtime_point' => 10,
        'director_point' => 5,
        'cast_point' => 5,
        'min_valid_point' => 33,
        'update_old_meta' => 1,
        'need_release' => 1980,
        'name_equals' => 20,
        'quote_title' => 10,
        'quote_content' => 10,
        'game_tag_point' => 5,
        'games_tags' => '',
    );
    private $log_type = array(
        0 => 'Info',
        1 => 'Warning',
        2 => 'Error',
    );
    private $log_status = array(
        0 => 'Add meta',
        1 => 'Update meta',
        2 => 'Remove meta',
        3 => 'Trash dublicate',
        4 => 'Ignore dublicate',
    );
    // Facets
    public $facets = array(
        'movies' => array('release', 'type', 'genre', 'provider', 'providerfree', 'mkw',
            'actors', 'dirs',
            'rating', 'country', 'race', 'dirrace', 'lgbt', 'woke',
            'race_cast', 'race_dir', 'gender_cast', 'gender_dir'),
        'critics' => array('release', 'type', 'movie', 'genre', 'author', 'state', 'tags', 'from',/* 'related',*/)
    );
    public $audience_facets = array(
        'auvote' => array('title' => 'SUGGESTION', 'titlesm' => 'SUGGESTION', 'name_pre' => 'AU ', 'filter_pre' => 'Audience SUGGESTION ', 'icon' => 'vote', 'group' => 'woke'),
        'aurating' => array('title' => 'OVERALL', 'titlesm' => 'OVERALL', 'name_pre' => 'AU OVERALL ', 'filter_pre' => 'Audience OVERALL ', 'icon' => 'rating', 'group' => 'woke'),
        'auaffirmative' => array('title' => 'AFFIRMATIVE ACTION', 'titlesm' => 'AFF ACT', 'name_pre' => 'AU AA ', 'filter_pre' => 'Audience AFFIRMATIVE ACTION ', 'icon' => 'affirmative', 'group' => 'woke'),
        'augod' => array('title' => 'FEDORA TIPPING', 'titlesm' => 'FEDORA T', 'name_pre' => 'FEDORA TIPPING ', 'filter_pre' => 'Audience FEDORA TIPPING ', 'icon' => 'god', 'group' => 'woke'),
        /* 'auhollywood' => array('title' => 'HOLLYWOOD BS', 'name_pre' => 'AU HOLLYWOOD BS ', 'filter_pre' => 'Audience HOLLYWOOD BS ', 'icon' => 'hollywood'), */
        'aulgbtq' => array('title' => 'GAY STUFF', 'titlesm' => 'GAY STUFF', 'name_pre' => 'GAY STUFF ', 'filter_pre' => 'Audience GAY STUFF ', 'icon' => 'lgbtq', 'group' => 'woke'),
        'aumisandry' => array('title' => 'FEMINISM', 'titlesm' => 'FEMINISM', 'name_pre' => 'AU FEMINISM ', 'filter_pre' => 'Audience FEMINISM ', 'icon' => 'misandry', 'group' => 'woke'),
        'auneo' => array('title' => 'NEO-MARXISM', 'titlesm' => 'NEO-MARXISM', 'name_pre' => 'AU NEO-MARXISM ', 'filter_pre' => 'Audience NEO-MARXISM ', 'icon' => 'patriotism', 'group' => 'woke')
    );
    public $rating_facets = array(
        'rrwt' => array('title' => 'Rating', 'titlesm' => 'Rating', 'name_pre' => 'Rating ', 'filter_pre' => 'Rating ', 'max_count' => 60, 'multipler' => 10, 'main' => 1, 'group' => 'rating'),
        'rating' => array('title' => 'Family Friend Score', 'titlesm' => 'FFS', 'name_pre' => 'FFS ', 'filter_pre' => 'FFS ', 'max_count' => 60, 'multipler' => 10, 'group' => 'woke'),
        'woke' => array('title' => 'Wokeness', 'titlesm' => 'Wokeness', 'name_pre' => 'Wokeness ', 'filter_pre' => 'Wokeness ', 'max_count' => 110, 'multipler' => 1, 'group' => 'woke', 'main' => 1),
        'lgbt' => array('title' => 'LGBT', 'titlesm' => 'LGBT', 'name_pre' => 'LGBT ', 'filter_pre' => 'LGBT ', 'max_count' => 110, 'multipler' => 1, 'group' => 'woke'),
        'rimdb' => array('title' => 'IMDb', 'titlesm' => 'IMDb', 'name_pre' => 'IMDb ', 'filter_pre' => 'IMDb Rating ', 'max_count' => 110, 'multipler' => 10, 'group' => 'rating', 'icon' => 'imdb'),
        'rrt' => array('title' => 'Rotten Tomatoes', 'titlesm' => 'RT', 'name_pre' => 'RT ', 'filter_pre' => 'Rotten Tomatoes ', 'max_count' => 110, 'group' => 'rating', 'icon' => 'rt'),
        'rrta' => array('title' => 'Rotten Tomatoes Audience', 'titlesm' => 'RT Audience', 'name_pre' => 'RTA ', 'filter_pre' => 'Rotten Tomatoes Audience ', 'max_count' => 110, 'group' => 'rating', 'icon' => 'rt'),
        'rrtg' => array('title' => 'Rotten Tomatoes % Gap', 'titlesm' => 'RT % Gap', 'name_pre' => 'RT%G ', 'filter_pre' => 'Rotten Tomatoes % Gap ', 'max_count' => 220, 'shift' => -100, 'sort' => 'asc', 'group' => 'rating', 'icon' => 'rt'),
        'rkp' => array('title' => 'Kinopoisk', 'titlesm' => 'Kinopoisk', 'name_pre' => 'KP ', 'filter_pre' => 'Kinopoisk ', 'max_count' => 110, 'multipler' => 10, 'group' => 'rating', 'icon' => 'kinop'),
        'rdb' => array('title' => 'Douban', 'titlesm' => 'Douban', 'name_pre' => 'DB ', 'filter_pre' => 'Douban ', 'max_count' => 110, 'multipler' => 10, 'group' => 'rating', 'icon' => 'douban'),
        'ranl' => array('title' => 'MyAnimeList', 'titlesm' => 'MyAnimeList', 'name_pre' => 'MyAnLi ', 'filter_pre' => 'MyAnLi ', 'max_count' => 110, 'multipler' => 10, 'group' => 'rating', 'icon' => 'mal'),
        'rfn' => array('title' => '4chan', 'titlesm' => '4chan', 'name_pre' => '4chan ', 'filter_pre' => '4chan ', 'max_count' => 110, 'multipler' => 10, 'group' => 'rating', 'icon' => 'fchan'),
        'rrev' => array('title' => 'Critic Reviews', 'titlesm' => 'Critic Reviews', 'name_pre' => 'RW ', 'filter_pre' => 'Reviews ', 'max_count' => 110, 'multipler' => 10, 'group' => 'rating', 'icon' => 'zr'),
            //'rtotal' => array('title' => 'Total rating', 'name_pre' => 'Total ', 'filter_pre' => 'Total rating '),
    );
    public $popularity_facets = array(
        'crwt' => array('title' => 'Popularity', 'titlesm' => 'Popularity', 'name_pre' => 'Pop ', 'filter_pre' => 'Popularity ', 'main' => 1, 'group' => 'pop'),
        'cimdb' => array('title' => 'IMDb', 'titlesm' => 'IMDb', 'name_pre' => 'IMDb ', 'filter_pre' => 'IMDb Rating ', 'group' => 'pop', 'icon' => 'imdb'),
        'crt' => array('title' => 'Rotten Tomatoes', 'titlesm' => 'RT', 'name_pre' => 'RT ', 'filter_pre' => 'Rotten Tomatoes ', 'group' => 'pop', 'icon' => 'rt'),
        'crta' => array('title' => 'Rotten Tomatoes Audience', 'titlesm' => 'RT Audience', 'name_pre' => 'RTA ', 'filter_pre' => 'Rotten Tomatoes Audience ', 'group' => 'pop', 'icon' => 'rt'),
        'ckp' => array('title' => 'Kinopoisk', 'name_pre' => 'KP ', 'titlesm' => 'Kinopoisk', 'filter_pre' => 'Kinopoisk ', 'group' => 'pop', 'icon' => 'kinop'),
        'cdb' => array('title' => 'Douban', 'name_pre' => 'DB ', 'titlesm' => 'Douban', 'filter_pre' => 'Douban ', 'group' => 'pop', 'icon' => 'douban'),
        'canl' => array('title' => 'MyAnimeList', 'titlesm' => 'MyAnimeList', 'name_pre' => 'MyAnLi ', 'filter_pre' => 'MyAnLi ', 'group' => 'pop', 'icon' => 'mal'),
        'cfn' => array('title' => '4chan', 'titlesm' => '4chan', 'name_pre' => '4chan ', 'filter_pre' => '4chan ', 'group' => 'pop', 'icon' => 'fchan'),
        //crev unused
        'pop' => array('title' => 'Reviews', 'titlesm' => 'Reviews', 'name_pre' => 'Reviews ', 'filter_pre' => 'Reviews ', 'group' => 'pop', 'icon' => 'zr'),
            //'pop' => array('title' => 'Emotions', 'group' => 'pop', 'icon' => ''),
    );
    public $facets_race_cast = array(
        'race' => array('filter' => 'actor', 'name' => 'actor_all', 'title' => 'Cast race', 'name_pre' => 'Cast '),
        'starrace' => array('filter' => 'actorstar', 'name' => 'actor_star', 'title' => 'Star race', 'name_pre' => 'Star '),
        'mainrace' => array('filter' => 'actormain', 'name' => 'actor_main', 'title' => 'Main race', 'name_pre' => 'Main ')
    );
    public $actor_filters = array(
        'actor' => array('filter' => 'actor_all', 'title' => 'Actor', 'name_pre' => '', 'placeholder' => ''),
        'actorstar' => array('filter' => 'actor_star', 'title' => 'Actor star', 'name_pre' => 'Star: ', 'placeholder' => 'star'),
        'actormain' => array('filter' => 'actor_main', 'title' => 'Actor main', 'name_pre' => 'Main: ', 'placeholder' => 'main'),
    );
    public $facets_gender = array(
        'gender' => array('title' => 'Cast gender', 'name_pre' => 'Cast '),
        'stargender' => array('title' => 'Star gender', 'name_pre' => 'Star '),
        'maingender' => array('title' => 'Main gender', 'name_pre' => 'Main ')
    );
    public $race_gender = array(
        'race' => 'gender',
        'starrace' => 'stargender',
        'mainrace' => 'maingender'
    );
    public $facets_race_directors = array(
        'dirrace' => array('filter' => 'dirall', 'name' => 'director_all', 'title' => 'All Production race', 'name_pre' => 'All Production '),
        'dirsrace' => array('filter' => 'dir', 'name' => 'director_dir', 'title' => 'Directors race', 'name_pre' => 'Directors '),
        'writersrace' => array('filter' => 'dirwrite', 'name' => 'director_write', 'title' => 'Writers race', 'name_pre' => 'Writers '),
        'castdirrace' => array('filter' => 'dircast', 'name' => 'director_cast', 'title' => 'Casting Directors race', 'name_pre' => 'Casting Directors '),
        'producerrace' => array('filter' => 'dirprod', 'name' => 'director_prod', 'title' => 'Producers race', 'name_pre' => 'Producers ')
    );
    public $director_filters = array(
        'dirall' => array('filter' => 'director_all', 'title' => 'Production', 'name_pre' => 'Production ', 'placeholder' => 'all'),
        'dir' => array('filter' => 'director_dir', 'title' => 'Director', 'name_pre' => 'Director: ', 'placeholder' => 'director'),
        'dirwrite' => array('filter' => 'director_write', 'title' => 'Writer', 'name_pre' => 'Writer: ', 'placeholder' => 'writer'),
        'dircast' => array('filter' => 'director_cast', 'title' => 'Casting director', 'name_pre' => 'Casting dir: ', 'placeholder' => 'casting'),
        'dirprod' => array('filter' => 'director_prod', 'title' => 'Producer', 'name_pre' => 'Producer: ', 'placeholder' => 'producer'),
    );
    public $race_gender_dir = array(
        'dirrace' => 'dirgender',
        'dirsrace' => 'dirsgender',
        'writersrace' => 'writergender',
        'castdirrace' => 'castgender',
        'producerrace' => 'producergender'
    );
    public $facets_gender_dir = array(
        'dirgender' => array('title' => 'All Production gender', 'name_pre' => 'All Production '),
        'dirsgender' => array('title' => 'Directors gender', 'name_pre' => 'Directors '),
        'writergender' => array('title' => 'Writers gender', 'name_pre' => 'Writers '),
        'castgender' => array('title' => 'Casting Directors gender', 'name_pre' => 'Casting Directors '),
        'producergender' => array('title' => 'Producers gender', 'name_pre' => 'Producers ')
    );
    // Default search filters
    public $search_filters = array(
        'type' => array(
            'movies' => array('key' => 'Movie', 'title' => 'Movies'),
            'tv' => array('key' => 'TVSeries', 'title' => 'TV Series'),
            'videogame' => array('key' => 'VideoGame', 'title' => 'Video Games'),
        ),
        'author_type' => array(
            'staff' => array('key' => 0, 'title' => 'Staff'),
            'critic' => array('key' => 1, 'title' => 'Critic'),
            'audience' => array('key' => 2, 'title' => 'Audience'),
        ),
        'state' => array(
            'proper' => array('key' => 1, 'title' => 'Proper Review'),
            'contains' => array('key' => 2, 'title' => 'Contains Mention'),
            'related' => array('key' => 3, 'title' => 'Related Article'),
        ),
        'price' => array(
            'free' => array('key' => 1, 'title' => 'Watch free'),
        ),
        'race' => array(
            'w' => array('key' => 1, 'title' => 'White'),
            'ea' => array('key' => 2, 'title' => 'Asian'),
            'h' => array('key' => 3, 'title' => 'Latino'),
            'b' => array('key' => 4, 'title' => 'Black'),
            'i' => array('key' => 5, 'title' => 'Indian'),
            'm' => array('key' => 6, 'title' => 'Arab'),
            'mix' => array('key' => 7, 'title' => 'Mixed / Other'),
            'jw' => array('key' => 8, 'title' => 'Jewish'),
        /* 'njw' => array('key' => 9, 'title' => 'NJW'),
          'ind' => array('key' => 10, 'title' => 'IND'), */
        ),
        'gender' => array(
            'male' => array('key' => 2, 'title' => 'Male'),
            'female' => array('key' => 1, 'title' => 'Female'),
        ),
        'auvote' => array(
            'skip' => array('key' => 2, 'title' => 'Skip It'),
            'free' => array('key' => 3, 'title' => 'Consume If Free'),
            'pay' => array('key' => 1, 'title' => 'Pay To Consume'),
        ),
        'movie' => array('key' => 'id', 'name_pre' => 'Movie ', 'filter_pre' => 'Movie'),
            /* UNUSED
             * 'rf' => array(
              'lgbt' => array('key' => 'lgbt', 'title' => 'LGBT'),
              'woke' => array('key' => 'woke', 'title' => 'Woke'),
              ), */
    );

    public function __construct($cm) {
        $this->cm = $cm;
        $this->db = array(
            //CS
            'log' => DB_PREFIX_WP . 'critic_search_log',
        );
        $this->get_perpage();

        $audience_facets = array_keys($this->audience_facets);
        //Merge facets
        $this->facets['movies'] = array_merge($this->facets['movies'], $audience_facets);
        $this->facets['movies'] = array_merge($this->facets['movies'], array_keys($this->rating_facets));

        //Critics facets
        $this->facets['critics'] = array_merge($this->facets['critics'], $audience_facets);
    }

    private function connect() {
        if ($this->sps) {
            return $this->sps;
        }
        try {
            $this->sps = new PDO("mysql:host=" . SPHINX_SEARCH_HOST . ";dbname=''");
        } catch (PDOException $e) {
            print "Error!: " . $e->getMessage() . "<br/>";
            die();
        }
        return $this->sps;
    }

    public function get_ma() {
        if (!$this->ma) {
            if (!class_exists('MoviesAn')) {
                require_once( CRITIC_MATIC_PLUGIN_DIR . 'MoviesAn.php' );
            }
            $this->ma = new MoviesAn($this->cm);
        }
        return $this->ma;
    }

    public function get_wpdb() {
        if (!$this->wpdb) {
            $this->wpdb = new AbstractDBWp();
        }
        return $this->wpdb;
    }

    /*
     * Create a new meta data for movies
     */

    public function run_cron($count = 100, $debug = false, $expire = 30) {

        // Get critics and reset the movies list from Critic Matic.
        $this->search_critic_posts_in_index($debug);
        $ma = $this->get_ma();
        // Find new meta
        $movies = $ma->get_expired_movies($count, $expire);

        if ($debug) {
            print_r($movies);
        }

        if ($movies && sizeof($movies)) {
            foreach ($movies as $movie) {
                $this->update_movie($movie, $debug);
            }
        }
    }

    public function update_movie($movie, $debug = false, $bulk = false, $ids = array(), $force = false) {
        $ma = $this->get_ma();
        $mid = $movie->id;

        // Update movie meta
        if (!$bulk) {
            $ma->update_movies_meta($mid);
        }
        if ($debug) {
            print_r($movie);
        }
        $critics_search = $this->search_critics($movie, $debug, $ids);

        // Get old movie meta
        $old_meta = array();
        $meta_ids = $this->cm->get_critics_meta_by_movie($mid);
        if (sizeof($meta_ids)) {
            foreach ($meta_ids as $meta_item) {
                $cid = $meta_item->cid;
                $state = $meta_item->state;
                $rating = $meta_item->rating;
                $type = $meta_item->type;
                $old_meta[$cid] = array('state' => $state, 'rating' => $rating, 'type' => $type, 'found' => 0);
            }
        }

        if ($debug) {
            print_r($critics_search);
        }

        $search_valid = [];
        if ($critics_search) {
            $search_valid = $critics_search['valid'];
            if ($bulk) {
                $bulk_valid = array();
                foreach ($search_valid as $cid => $item) {
                    if (in_array($cid, $ids)) {
                        $bulk_valid[$cid] = $item;
                    }
                }
                if ($force) {
                    $search_other = $critics_search['other'];
                    if ($search_other) {
                        foreach ($search_other as $cid => $item) {
                            if (in_array($cid, $ids)) {
                                $bulk_valid[$cid] = $item;
                            }
                        }
                    }
                }

                $search_valid = $bulk_valid;
            }
        }

        // Update critics meta
        if ($search_valid) {
            $top_rating = 0;
            // Add top movie id to critic post
            $top_critic = 0;
            foreach ($search_valid as $cid => $item) {

                if ($item['total'] > $top_rating) {
                    $top_critic = $cid;
                }

                // Valid critic: add or update
                if (isset($old_meta[$cid])) {
                    //item already exist. Update if need
                    $update_meta = false;

                    if ($old_meta[$cid]['rating'] != $item['total']) {
                        // Change rating
                        $update_meta = true;
                    }

                    /*
                      State:
                      0 => 'Unapproved',
                      1 => 'Approved',
                      2 => 'Auto',
                     */
                    $state = $old_meta[$cid]['state'];
                    $type = $old_meta[$cid]['type'];
                    if ($state == 2 && $type != $item['type']) {
                        // Change type for auto search items only
                        /*
                          $post_category = array(
                          0 => 'None',
                          1 => 'Proper Review',
                          2 => 'Contains Mention',
                          3 => 'Related Article'
                          );
                         */
                        $update_meta = true;
                    }

                    if ($force) {
                        // Force approved critic
                        $state = 1;
                        $update_meta = true;
                    }

                    if ($update_meta) {

                        // Update
                        $this->cm->update_post_meta($mid, $type, $state, $cid, $item['total']);

                        //Add log
                        $score_str = '';
                        if ($item['score'] && sizeof($item['score'])) {
                            foreach ($item['score'] as $key => $value) {
                                $score_str .= "$key: $value; ";
                            }
                        }
                        $type_str = 'Type: ' . $this->cm->get_post_category_name($item['type']) . ' (' . $this->cm->get_post_category_name($old_meta[$cid]['type']) . '). ';
                        $rating_str = 'Rating: ' . $item['total'] . ' (' . $old_meta[$cid]['rating'] . '). ';
                        $message = trim($type_str . $rating_str . $score_str);
                        $this->log_update_meta($message, $cid, $mid);
                    }

                    $old_meta[$cid]['found'] = 1;
                } else {
                    //item not exist. Add.
                    //State is auto search
                    $state = 2;

                    if ($force) {
                        // Force approved critic
                        $state = 1;
                    }

                    $this->cm->add_post_meta($mid, $item['type'], $state, $cid, $item['total']);

                    //Add log
                    $score_str = '';
                    if ($item['score'] && sizeof($item['score'])) {
                        foreach ($item['score'] as $key => $value) {
                            $score_str .= "$key: $value; ";
                        }
                    }
                    $type_str = 'Type: ' . $this->cm->get_post_category_name($item['type']) . '. ';
                    $rating_str = 'Rating: ' . $item['total'] . '. ';
                    $message = trim($type_str . $rating_str . $score_str);
                    $this->log_add_meta($message, $cid, $mid);
                }
            }
        }

        if (!$bulk) {
            $remove_old_meta = true;
            if ($remove_old_meta && sizeof($old_meta)) {
                // Remove old meta that not found
                foreach ($old_meta as $cid => $item) {
                    if ($item['found'] != 1) {
                        //remove only auto search items
                        if ($item['state'] == 2) {
                            $this->cm->remove_post_meta($cid, $mid);

                            //Add log
                            $message = '';
                            $this->log_remove_meta($message, $cid, $mid);
                        }
                    }
                }
            }
        }
    }

    /*
     * Search critics
     */

    public function search_critics($post = '', $debug = false, $cids = array()) {
        /*
         * (
          [id] => 26292
          [movie_id] => 3501632
          [rwt_id] => 26364
          [tmdb_id] => 284053
          [title] => Thor: Ragnarok
          [post_name] => thor-ragnarok
          [type] => Movie
          [genre] => Action,Adventure,Comedy
          [release] => 2017-11-03
          [year] => 2017
          [country] => United States,Australia
          [language] => English
          [production] => {"co0008970":"Walt Disney Pictures","co0051941":"Marvel Studios","co0227773":"Government of Australia"}
          [actors] => {"s":{"1165110":"Chris Hemsworth","1089991":"Tom Hiddleston","949":"Cate Blanchett","749263":"Mark Ruffalo"},"m":{"252961":"Idris Elba","156":"Jeff Goldblum","1935086":"Tessa Thompson","881631":"Karl Urban","164":"Anthony Hopkins","1212722":"Benedict Cumberbatch","169806":"Taika Waititi","1344302":"Rachel House","317":"Clancy Brown","38355":"Tadanobu Asano","829032":"Ray Stevenson","1157048":"Zachary Levi","5126360":"Georgia Blizzard","1690855":"Amali Golden","1292661":"Luke Hemsworth","554":"Sam Neill"},"e":{"252961":"Idris Elba","156":"Jeff Goldblum","1935086":"Tessa Thompson","881631":"Karl Urban","164":"Anthony Hopkins","1212722":"Benedict Cumberbatch","169806":"Taika Waititi","1344302":"Rachel House","317":"Clancy Brown","38355":"Tadanobu Asano","829032":"Ray Stevenson","1157048":"Zachary Levi","5126360":"Georgia Blizzard","1690855":"Amali Golden","1292661":"Luke Hemsworth","554":"Sam Neill","1787506":"Charlotte Nicdao","2694682":"Ash Ricardo","5647934":"Shalom Brune-Franklin","6273186":"Taylor Hemsworth","1959207":"Cohen Holloway","6844615":"Alia Seror-O'Neill","9353954":"Sophia Laryea","6460320":"Steven Oliver","3312641":"Hamish Parkinson","46591":"Jasper Bagg","9353956":"Sky Castanho","3996908":"Shari Sebbens","9353957":"Richard Green","9353958":"Sol Castanho","7031512":"Jet Tranter","9353959":"Samantha Hopper","3504152":"Eloise Winestock","2373827":"Rob Mayes","9053405":"Jordan Abbey-Young","5841691":"Bashir Ally","2919072":"Jade Amantea","10235488":"Bridgette Armstrong","6958305":"Brenton Ashe","7355003":"Tier Ataing","7701671":"David James Austin","6742616":"Natalie Baker","5705297":"Sydney Shea Barker","7490971":"Donnie Baxter","4762553":"Annisa Belonogoff","6280480":"Lexy Bernardo","5458958":"Hunter Stratton Boland","3886662":"Otto Bots","7489400":"Nicholas Burton","7128902":"Rosco Campbell","5714605":"Gabby Carbon","7824205":"Greta Carew-Johns","9329143":"Annikki Chand","6755658":"Chris Charteris","3916488":"Jo Christiaans","6750622":"Brodie Cornish","3090931":"Jacob Crawford","354":"Matt Damon","7612830":"Cameron Dean","2445165":"Suzanne Dervish-Ali","8345609":"Liam Hop Yek Dodds","10217527":"Liam Donnelly","8941030":"Brittany Dugan","8173819":"Sasha Dulics","5934380":"Aimee Duroux","9602653":"Simon Durrell","9457361":"Shinaed Evans","6958310":"Tracie Filmer","3730537":"Rachel Forsyth","1932878":"Michael M. Foster","8359959":"Melissa Frances","5081742":"Sean Frazer","6271883":"Daniel Goodwin","2310590":"Adam Green","337705":"Charles Green","6467129":"Andrew Groundwater","1092087":"Sam Hargrave","6352619":"Dylan Kai Harris","8932491":"Roberto Harrison","9187777":"Jared Hasmuk","6280833":"Tahlia Jade Holt","8580985":"Bobby Hoskins","9254208":"Apollo Jackson","9545423":"Charmain Jackson","7463409":"Cale Kampers","9042926":"Nathan Kennedy","8132341":"Dean Kenny","6698421":"Joel Knights","1342744":"David Knijnenburg","7531404":"Stephanie Kutty","5379637":"Matt LaBorde","9201765":"Demetri Landell","8547335":"Alice Lanesbury","493605":"Liz Layton","498278":"Stan Lee","9045142":"Braden Lewis","9045148":"Jordan Lewis","3300498":"Scott Loeser","8727569":"Dan Logovik","4732154":"Steven Lunavich","9826790":"Alexandra MacDonald","5567730":"Georgia Mae","8504806":"Lambert Majambele","6640428":"Mervyn Marriott","3111958":"Tracey Lee Maxwell","7590478":"Mollie McGregor","7590479":"Sophia McGregor","5009680":"Andrew S. McMillan","5225647":"Declan McMurray","7651406":"Abhishek Mehta","5841693":"Salvatore Merenda","3279186":"Anthony Miller","7634704":"Paris Moletti","3338315":"Sam Monaghan","4292253":"Stephen Murdoch","5301802":"Gideon Mzembe","5259748":"Demetrice Nguyen","7517557":"Jip Panosot","2655187":"Kai Pantano","7929024":"Anna Patch","7666175":"Samuel Peacock","9331946":"Damien Picketts","8135316":"Erin Hayley Powell","8169489":"Jon Quested","4609551":"Greg Rementer","7712113":"Martin Reyes","5911020":"Stephanie Riggio","7344525":"Lachlan Robbie","742467":"Paul Rosenblum","6581070":"Keen Ruffalo","7680726":"Michael Stent","4292353":"Ryan Tarran","5040113":"Lara Thomas","5039089":"Tennille Thomas","7532793":"Josh Torr","10308555":"Noa Tsuchiya","1731601":"Krystal Vayda","7197071":"Stephen Vining","8231948":"Jason Virgil","7470011":"Beatrice Ward","6655408":"Ken Watanabe","4438615":"Chelsea Winstanley","5857265":"Tara Wraith","8181936":"Mikey Wulff","9340993":"Elizabeth Xu","3837570":"Mike Zarate","6845005":"Connor Zegenhagen"}}
          [producers] => {"22285":"executive producer","1384406":"associate producer","195669":"executive producer","270559":"producer (produced by) (p.g.a.)","335343":"co-producer","358411":"executive producer","498278":"executive producer","1961168":"executive producer"}
          [director] => 169806
          [cast_director] => 278168
          [box_usa] => 315058289
          [box_world] => 853983911
          [productionBudget] => 180000000
          [keywords] => superhero,marvel comics,based on comic book,marvel cinematic universe,female villain
          [description] => Imprisoned on the planet Sakaar, Thor must race against time to return to Asgard and stop Ragnarök, the destruction of his world, at the hands of the powerful and ruthless villain Hela.
          [data] => {"image":"https:\/\/m.media-amazon.com\/images\/M\/MV5BMjMyNDkzMzI1OF5BMl5BanBnXkFtZTgwODcxODg5MjI@._V1_.jpg","creator":{"Organization":"8970,51941,227773,","Person":"3069408,1219736,1236653,"}}
          [contentrating] => PG-13
          [rating] => 7.9
          [add_time] => 1629540493
          [runtime] => 7800
          )
         */
        $pid = $post->id;
        $post_type = $post->type;
        $this->timer_start();
        $ret = array();
        $data = array();
        $debug_data = array();
        $ss = $this->get_search_settings();

        $title = strip_tags($post->title);

        // Get weight
        $post_weight = $post->weight;
        $post_title_weight = $post->title_weight;

        // Get release time
        $year = $post->year;
        $release = $post->release;
        if (!$release) {
            $release = "{$year}-01-01";
        }


        $release_time = strtotime($release);

        $num = 1;
        // If worlds count < small title need quotes title in content
        $small_titles = 2;
        $min_title_weight = 10;

        if ($title) {
            $num = sizeof(explode(' ', $title));
            if ($num == 0) {
                $num = 1;
            }
        }
        // Search by title
        if ($title) {
            $data = $this->search_by_title_and_date($title, $year, $release_time, $ss['limit'], true, $cids);
            if ($debug) {
                $debug_data['title keyword'] = $title;
                $debug_data['search results'] = sizeof((array) $data);
            }
        }

        // Type of related posts
        $meta_search = array();

        $ids = array();
        // Search custom fields
        if (sizeof($data)) {
            foreach ($data as $item) {
                $ids[] = $item->id;
                $meta_search[$item->id] = 1;
                $ret[$item->id]['title'] = $item->title;
                $ret[$item->id]['date'] = $item->post_date;
                $ret[$item->id]['content'] = $item->content;
                if (strstr($item->t, '<b>')) {
                    if (preg_match_all('/<b>([^<]+)<\/b>/', $item->t, $title_match)) {
                        $ret[$item->id]['found']['title'] = $title_match[1];
                    }
                    if ($debug) {
                        $ret[$item->id]['debug']['title'] = $item->t;
                    }
                }

                if (strstr($item->c, '<b>')) {
                    $content = htmlspecialchars($item->c);
                    $content = str_replace('&lt;b&gt;', '<b>', $content);
                    $content = str_replace('&lt;/b&gt', '</b>', $content);
                    if (preg_match_all('/<b>([^<]+)<\/b>/', $content, $title_match)) {
                        $ret[$item->id]['found']['content'] = $title_match[1];
                    }
                    if ($debug) {
                        $ret[$item->id]['debug']['content'] = $content;
                    }
                }

                $ret[$item->id]['w'] = $item->w;
            }
        }

        if (!sizeof($ids)) {
            return array();
        }

        $need_release = false;
        $valid_release = array();

        // Search Release date

        if (!$year) {
            return [];
        }

        if ($debug) {
            $debug_data['year'] = $year;
        }

        if ($ss['need_release'] > $year) {
            // Old movie. Need relese date in content
            $need_release = true;
        }

        $year_found = $this->search_in_ids($ids, $year, $debug);
        if (sizeof($year_found)) {
            foreach ($year_found as $item) {
                $w = (int) $item->w;

                if ($w >= 10) {
                    $ret[$item->id]['total'] += $ss['release_point_title'];
                    $ret[$item->id]['score']['release_title'] = $ss['release_point_title'];
                } else {
                    $ret[$item->id]['total'] += $ss['release_point'];
                    $ret[$item->id]['score']['release'] = $ss['release_point'];
                }

                $valid_release[] = $item->id;

                if ($debug) {
                    if ($w >= 10) {
                        $ret[$item->id]['debug']['year title'] = $item->t;
                    }
                    if ($w != 10) {
                        $ret[$item->id]['debug']['year content'] = $item->c;
                    }
                }
            }
        }

        // Search Runtime
        $runtime = $post->runtime;
        if ($runtime) {
            if ($debug) {
                $debug_data['runtime'] = $runtime;
            }
            $runtime_found = $this->search_in_ids($ids, $runtime, $debug);
            if (sizeof($runtime_found)) {
                foreach ($runtime_found as $item) {
                    $w = (int) $item->w;
                    $ret[$item->id]['total'] += $ss['runtime_point'];
                    $ret[$item->id]['score']['runtime'] = $ss['runtime_point'];

                    if ($debug) {
                        if ($w >= 10) {
                            $ret[$item->id]['debug']['runtime title'] = $item->t;
                        }
                        if ($w != 10) {
                            $ret[$item->id]['debug']['runtime content'] = $item->c;
                        }
                    }
                }
            }
        }

        $ma = $this->get_ma();

        // Search Director
        $directors = $ma->get_directors($post->id);
        if ($directors) {
            $director_names = array();
            foreach ($directors as $director) {
                $name = $director->name;
                $i = 0;
                if ($name) {
                    if ($i > $max_directors) {
                        break;
                    }
                    $director_names[$name] = '"' . $this->filter_text($name) . '"';
                    $i += 1;
                }
            }
            if ($director_names) {
                $director_str = implode(' ', $director_names);
                $director_keywords = $this->wildcards_maybe_query($director_str, $debug);

                if ($debug) {
                    $debug_data['director'] = implode(', ', $director_names);
                }

                // Find directors in movie ids
                $director_found = $this->search_in_ids($ids, $director_keywords, $debug);
                if (sizeof($director_found)) {
                    foreach ($director_found as $item) {
                        $w = (int) $item->w;
                        $ret[$item->id]['total'] += $ss['director_point'];
                        $ret[$item->id]['score']['director'] = $ss['director_point'];

                        if ($debug) {
                            if ($w >= 10) {
                                $ret[$item->id]['debug']['director title'] = $item->t;
                            }
                            if ($w != 10) {
                                $ret[$item->id]['debug']['director content'] = $item->c;
                            }
                        }
                    }
                }
            }
        }


        //Search Cast

        $actors = $ma->get_actors($post->id);

        if ($actors) {
            $cast_search = array();

            foreach ($actors as $actor) {
                $name = $actor->name;
                $i = 0;
                if ($name) {
                    if ($i > $max_actors) {
                        break;
                    }
                    $cast_search[$name] = '"' . $this->filter_text($name) . '"';
                    $i += 1;
                }
            }

            if ($cast_search) {

                if ($debug) {
                    $debug_data['cast'] = implode(', ', $cast_search);
                }

                // Find actors im movie ids
                $cast_found = $this->search_in_ids($ids, $cast_search, $debug);

                if (sizeof($cast_found)) {
                    foreach ($cast_found as $item) {
                        $w = (int) $item->w;
                        $ret[$item->id]['total'] += $ss['cast_point'];
                        $ret[$item->id]['score']['cast'] = $ss['cast_point'];
                        if ($debug) {
                            if ($w >= 10) {
                                $ret[$item->id]['debug']['cast title'] = $item->t;
                            }
                            if ($w != 10) {
                                $ret[$item->id]['debug']['cast content'] = $item->c;
                            }
                        }
                    }
                }
            }
        }

        // Games tags
        $games_tags = $ss['games_tags'];
        if ($games_tags) {
            $games_tags_arr = explode(',', $games_tags);
            $tag_search = array();
            $lower_title = strtolower($title);
            foreach ($games_tags_arr as $name) {
                if (strstr($lower_title, strtolower($name))) {
                    continue;
                }

                $tag_search[$name] = '"' . $this->filter_text($name) . '"';
            }
            $debug_data['game tags'] = implode(', ', $tag_search);
            $game_tags_found = $this->search_in_ids($ids, $tag_search, $debug);
            if (sizeof($game_tags_found)) {

                foreach ($game_tags_found as $item) {
                    $w = (int) $item->w;
                    if ($post_type == 'VideoGame') {
                        $ret[$item->id]['total'] += $ss['game_tag_point'];
                        $ret[$item->id]['score']['games_tags'] = $ss['game_tag_point'];
                    } else {
                        $ret[$item->id]['total'] -= $ss['game_tag_point'];
                        $ret[$item->id]['score']['games_tags'] = -$ss['game_tag_point'];
                    }


                    if ($debug) {
                        if ($w >= 10) {
                            $ret[$item->id]['debug']['game tag title'] = $item->t;
                        }
                        if ($w != 10) {
                            $ret[$item->id]['debug']['game tag content'] = $item->c;
                        }
                    }
                }
            }
        }


        //Title weight
        $result = array(
            'valid' => array(),
            'other' => array()
        );

        foreach ($ret as $id => $value) {
            $content_tags = false;
            $title_tags = false;
            // Critic type:
            // 1 => 'Proper Review'
            // 2 => 'Contains Mention'
            // 3 => 'Related'

            $critic_type = 2;

            $title_w = (int) $value['w'];
            $post_title = $value['title'];

            if ($title_w >= 10) {
                // Keywords found in critic title                
                // Find title in search bold
                $valid_title = false;
                if (isset($value['found']['title'])) {
                    foreach ($value['found']['title'] as $v) {
                        $find_title = $v;
                        // "strstr" instead of "=" since a "release" can be present
                        if (strstr($find_title, $title) || strstr($find_title, strtoupper($title))) {
                            $ret[$id]['debug']['title valid'] = 'Found title in search';
                            $valid_title = true;
                            break;
                        }
                    }
                }

                if ($valid_title) {

                    // Name words multipler       
                    $points = 0;
                    if ($ss['name_words_multipler'] > 0) {
                        $points = (($num - 1) * $ss['name_words_multipler']);
                    }

                    $title_points = $ss['name_point_title'];
                    $points = (int) round($title_points * $points, 0);

                    $ret[$id]['score']['name_title'] = $title_points;
                    $ret[$id]['score']['name_title_words_multipler'] = $points;
                    $ret[$id]['total'] += $points + $title_points;


                    // Find bold tags
                    $find_html = $this->find_html_tags($title, $value['title']);
                    if ($find_html) {
                        $title_tags = true;
                        $found_tags = array();
                        foreach ($find_html as $tag => $count) {
                            $found_tags[] = "{$tag} => {$count}";
                        }
                        $ret[$id]['score']['title_tags'] = $ss['quote_title'];
                        $ret[$id]['total'] += $ss['quote_title'];
                        $ret[$id]['debug']['title tags'] = implode('; ', $found_tags);
                    }

                    // Find quote tags
                    $find_quote = $this->find_quote_tags($title, strip_tags($value['title']));
                    if ($find_quote) {
                        $title_tags = true;
                        $found_quotes = array();
                        foreach ($find_quote as $tag => $count) {
                            $found_quotes[] = "{$tag} => {$count}";
                        }
                        $ret[$id]['score']['title_quotes'] = $ss['quote_title'];
                        $ret[$id]['total'] += $ss['quote_title'];
                        ;
                        $ret[$id]['debug']['title quoutes'] = implode('; ', $found_quotes);
                    }

                    // Proper review
                    if (($post_title_weight >= $min_title_weight && $num >= $small_titles ) || $title_tags) {
                        $critic_type = 1;
                    }
                }
            }

            if ($title_w != 10) {
                // Keywords found in critic description
                $valid_desc = false;
                if (isset($value['found']['content'])) {
                    foreach ($value['found']['content'] as $v) {
                        $find_value = $v;
                        if (strstr($find_value, $title) || strstr($find_value, strtoupper($title))) {
                            $ret[$id]['debug']['content valid'] = 'Found content in search';
                            $valid_desc = true;
                            break;
                        }
                    }
                }
                if ($valid_desc) {
                    // Name words multipler
                    $points = 0;
                    if ($ss['name_words_multipler'] > 0) {
                        $points = (($num - 1) * $ss['name_words_multipler']);
                    }

                    $desc_points = $ss['name_point'];
                    $points = (int) round($ss['name_point'] * $points);

                    $ret[$id]['score']['name_desc'] = $desc_points;
                    $ret[$id]['score']['name_desc_words_multipler'] = $points;
                    $ret[$id]['total'] += $desc_points + $points;

                    // Find bold tags
                    $find_html = $this->find_html_tags($title, $value['content']);
                    if ($find_html) {
                        $content_tags = true;
                        $found_tags = array();
                        foreach ($find_html as $tag => $count) {
                            $found_tags[] = "{$tag} => {$count}";
                        }
                        $ret[$id]['score']['content_tags'] = $ss['quote_content'];
                        $ret[$id]['total'] += $ss['quote_content'];
                        $ret[$id]['debug']['content tags'] = implode('; ', $found_tags);
                    }

                    // Find quote tags
                    $find_quote = $this->find_quote_tags($title, strip_tags($value['content']));
                    if ($find_quote) {
                        $content_tags = true;
                        $found_quotes = array();
                        foreach ($find_quote as $tag => $count) {
                            $found_quotes[] = "{$tag} => {$count}";
                        }
                        $ret[$id]['score']['content_quotes'] = $ss['quote_content'];
                        $ret[$id]['total'] += $ss['quote_content'];
                        $ret[$id]['debug']['content quoutes'] = implode('; ', $found_quotes);
                    }
                }
            }

            $dates = array();
            $title_equals = false;
            // Find equals title
            $critic_clear = $this->clear_critic_title($value['title'], $year);
            $movie_clear = $this->clear_critic_title($title, $year);
            $ret[$id]['debug']['title equals'] = "$critic_clear != $movie_clear";
            if ($critic_clear == $movie_clear) {
                $title_equals = true;
                $ret[$id]['score']['titles_equals'] = $ss['name_equals'];
                $ret[$id]['total'] += $ss['name_equals'];
                $ret[$id]['debug']['title equals'] = "$critic_clear == $movie_clear";
                // Proper review
                $critic_type = 1;
            } else {
                // 1. Find another date in title
                $dates = $this->find_dates($value['title'], $title);

                if ($dates) {
                    $year_valid = false;
                    if ($year) {
                        if (in_array($year, $dates)) {
                            // Year vaild
                            $year_valid = true;
                        }
                    }
                }
                // 2. Find quotes from another movie in title
                // 3. Find another movie in title
                //$top_movie = $this->find_top_movie_by_title($movie_clear, $critic_clear, 1);
                //p_r(array($movie_clear,$critic_clear,$top_movie));                
            }

            if ($ret[$id]['score']) {
                arsort($ret[$id]['score']);
            }

            // Auto critic type
            $ret[$id]['type'] = $critic_type;



            $valid = true;


            if ($post_type == 'VideoGame' && $valid) {
                // Need video tags to valid
                if (!isset($ret[$id]['score']['games_tags'])) {
                    $valid = false;
                    $reason = 'Game tags not found';
                    $ret[$id]['debug']['game tags found'] = $reason;
                }
            }

            if ($valid) {
                if ($post_title_weight < $min_title_weight || $num < $small_titles) {
                    // Small title weight

                    $valid = false;
                    $reason = '';
                    if ($title_tags) {
                        $valid = true;
                        $reason = 'Valid: Title tags';
                    } else if ($content_tags) {
                        $valid = true;
                        $reason = 'Valid: Content tags';
                    } else if ($title_equals) {
                        $valid = true;
                        $reason = 'Valid: Title equals';
                    }
                    if ($num < $small_titles) {
                        $ret[$id]['debug']['small title'] = "$num < $small_titles. $reason";
                    }
                    if ($post_title_weight < $min_title_weight) {
                        $ret[$id]['debug']['small title weigth'] = "$post_title_weight < $min_title_weight. $reason";
                    }
                }
            }

            if ($dates && $critic_type == 1) {
                // Check date for proper review
                $valid_text = 'Valid';
                if (!$year_valid) {
                    $valid = false;
                    $valid_text = 'Invalid ' . $year;
                    $ret[$id]['score']['dates_exists'] = 'Invalid';
                }
                $ret[$id]['debug']['dates'] = 'Found dates in title: ' . implode(';', $dates) . '. ' . $valid_text;
            }

            // Check old release
            if ($need_release) {
                $ret[$id]['score']['release_exist'] = 'True';
                if (!in_array($id, $valid_release)) {
                    $ret[$id]['score']['release_exist'] = 'False';
                    $valid = false;
                }
            }


            if ($valid) {
                // Check min score
                $valid = $ret[$id]['total'] >= $ss['min_valid_point'] ? true : false;
                if (!$valid) {
                    // If small score, critic type related article
                    if ($ret[$id]['total'] > 0) {
                        $ret[$id]['type'] = 3;
                        $valid = true;
                    }
                }
            }


            $ret[$id]['valid'] = $valid;
            $ret[$id]['timer'] = $this->timer_stop();

            if ($valid) {
                $result['valid'][$id] = $ret[$id];
            } else {
                $result['other'][$id] = $ret[$id];
            }
        }

        if ($debug) {
            $result['debug'] = $debug_data;
        }

        return $result;
    }

    private function find_dates($text, $title = '') {
        $curr_time = $this->curr_time();
        $max_year = ((int) gmdate('Y', $curr_time)) + 2;
        $min_year = 1850;
        $results = array();
        if (preg_match_all('#([0-9]{4})#', $text, $match)) {
            $years = $match[1];
            foreach ($years as $year) {
                if ($min_year < $year && $year < $max_year) {
                    if (!strstr($title, $year)) {
                        // Year not a part of title. 
                        $results[] = $year;
                    }
                }
            }
        }
        return $results;
    }

    private function clear_critic_title($title, $year = '') {
        $title = str_replace($year, '', $title);
        $title = preg_replace('#movie review#i', '', $title);
        $title = preg_replace('#review#i', '', $title);
        $title = strip_tags($title);
        $title = preg_replace('#[^\w\d\' ]+#', '', $title);
        $title = preg_replace('#  #', ' ', $title);
        $title = trim(strtolower($title));
        return $title;
    }

    private function find_movie_by_title_unused() {
        // UNUSED DEPRECATED

        $keywords = implode(' ', $this->satinize_phrases($post_title, $title_to_validate));

        $names = array();
        if ($keywords) {
            $names = $this->search_movies_by_title($keywords, $num, 100);

            if ($debug) {
                $ret[$id]['debug']['names keywords'] = $keywords;
            }
        }

        $names_valid = array();
        if (sizeof($names)) {
            foreach ($names as $name) {
                $ret[$id]['debug']['movies found'][] = $name->title;

                if ($title == $name->title) {
                    $names_valid[$name->id] = $name->title;
                    $ret[$id]['debug']['movies valid'][] = $name->title;
                }
            }
        }


        if (isset($names_valid[$pid])) {
            $valid_title = true;



            // Add validate for small titles
            if ($valid_title && $num < $small_titles) {

                // Need date in title
                $valid_title = false;
                if ($year && strstr($post_title, $year)) {
                    $valid_title = true;
                    $ret[$id]['debug']['title valid'] = 'Found date in title';
                }

                // Equals
                if ($post_title == $names_valid[$pid]) {
                    $valid_title = true;
                    $ret[$id]['debug']['title valid'] = 'Titles is equals';
                }

                if (!$valid_title) {
                    // Regexp
                    $reg_tags = $this->get_reg_tags();
                    if (preg_match_all($reg_tags, $post_title, $match)) {
                        foreach ($match[1] as $v) {
                            $find_title = strip_tags($v);
                            if ($find_title == $title) {
                                $ret[$id]['debug']['title valid'] = 'Found title in tags';
                                $valid_title = true;
                                break;
                            }
                        }
                    }
                }

                if (!$valid_title) {
                    $reg_quotes = $this->get_reg_quotes();
                    if (preg_match_all($reg_quotes, $this->validate_title_chars(strip_tags($post_title)), $match)) {
                        foreach ($match[1] as $v) {
                            $find_title = $v;
                            if ($find_title == $title) {
                                $ret[$id]['debug']['title valid'] = 'Found title in quotes';
                                $valid_title = true;
                                break;
                            }
                        }
                    }
                }

                if (!$valid_title && $num > 1) {
                    if (isset($value['found']['title'])) {
                        foreach ($value['found']['title'] as $v) {
                            $find_title = $v;
                            if ($find_title == $title) {
                                $ret[$id]['debug']['title valid'] = 'Found title in search bolds';
                                $valid_title = true;
                                break;
                            }
                        }
                    }
                }
            }
        }
    }

    public function find_bold_text($text = '') {
        // Find any selections in the text
        // 1. html tags
        $found = array();
        if (preg_match_all('#(?:<i>|<em>|<b>|<strong>|<h[0-9]+>)([^<]+)(?:</i>|</em>|</b>|</strong>|</h[0-9]+>)#', $text, $match)) {
            $found = $match[1];
        }

        // Find quotes
        $clear_text = strip_tags($text);
        if (preg_match_all('#"([^"]+)"#', $clear_text, $match)) {
            $found = $found + $match[1];
        }
        if (preg_match_all('#`([^`]+)`#', $clear_text, $match)) {
            $found = $found + $match[1];
        }
        /* if (preg_match_all('#\'([^\']+)\'#', $clear_text, $match)) {
          $found = $found + $match[1];
          } */
        /* if (preg_match_all('#([A-Z ]{3,100})#', $clear_text, $match)) {
          $found = $found + $match[1];
          } */
        return $found;
    }

    /*
     * Add custom movie critics meta
     */

    public function bulk_add_critics_meta($mid = 0, $ids = array(), $force = false) {
        if (!$mid || !$ids) {
            return false;
        }

        $ma = $this->get_ma();
        $movie = $ma->get_post($mid);

        $debug = false;
        $bulk = true;

        $this->update_movie($movie, $debug, $bulk, $ids, $force);

        return true;
    }

    /*
     * Search critic by movie title
     * Any match in critic title or critic content
     */

    public function search_by_title($title = '', $limit = 1000, $debug = false) {
        //not audience authors
        $author_type = 2;

        $snippet = '';
        if ($debug) {
            $snippet = ', SNIPPET(title, QUERY()) t, SNIPPET(content, QUERY()) c';
        }

        $sql = sprintf("SELECT id, title, weight() w" . $snippet . " FROM critic "
                . "WHERE MATCH('@(title,content)=\"%s\"') AND author_type!=%d LIMIT %d "
                . "OPTION ranker=expr('sum(user_weight)'), "
                . "field_weights=(title=10, content=1) ", $title, $author_type, $limit);

        $result = $this->sdb_results($sql);
        return $result;
    }

    public function search_by_title_and_date($title = '', $year = '', $release_time = 0, $limit = 1000, $debug = false, $ids = array()) {
        //not audience authors
        $author_type = 2;

        $snippet = '';
        if ($debug) {
            $snippet = ', SNIPPET(title, QUERY()) t, SNIPPET(content, QUERY()) c';
        }

        //Example: "=The =Widow"
        $keyword = '"=' . str_replace(' ', ' =', $title) . '"';
        if ($year) {
            $keyword .= ' MAYBE ' . $year;
        }

        $and_release = '';
        if ($release_time != 0) {
            $and_release = ' AND post_date > ' . $release_time;
        }

        $ids_and = '';
        if ($ids) {
            $ids_and = ' AND id IN(' . implode(',', $ids) . ')';
        }

        $sql = sprintf("SELECT id, title, post_date, content, weight() w" . $snippet . " FROM critic "
                . "WHERE MATCH('@(title,content) ($keyword)') AND author_type!=%d" . $ids_and . $and_release . " LIMIT %d "
                . "OPTION ranker=expr('sum(user_weight)'), "
                . "field_weights=(title=10, content=1) ", $author_type, $limit);


        $result = $this->sdb_results($sql);

        return $result;
    }

    /*
     * Search in ids list
     */

    public function search_in_ids($ids, $title, $debug = false) {

        $snippet = '';
        if ($debug) {
            $snippet = ', SNIPPET(title, QUERY()) t, SNIPPET(content, QUERY()) c';
        }

        $title_query = '';
        if (!is_array($title)) {
            $title_query = sprintf("@(title,content) %s", $title);
        } else {
            $title_query_arr = array();
            foreach ($title as $value) {
                $title_query_arr[] = sprintf("@(title,content) \"%s\"", $value);
            }
            $title_query = implode('|', $title_query_arr);
        }

        $sql = sprintf("SELECT id,  weight() w" . $snippet . " FROM critic "
                . "WHERE MATCH('" . $title_query . "') "
                . "AND id IN(" . implode(',', $ids) . ") "
                . "OPTION ranker=expr('sum(user_weight)'), "
                . "field_weights=(title=10, content=1) ", $text);
        $result = $this->sdb_results($sql);
        return $result;
    }

    public function search_movies($title, $content) {
        $movies = array();

        $title = $this->validate_title_chars($title);

        //Find dates
        if (preg_match('| ([0-9]{4})|', $title, $match)) {
            $year = (int) $match[1];
        }

        $years = array();

        if ($content) {
            if (preg_match_all('| ([0-9]{4})|', $content, $match)) {
                $years = $match[1];
            }
        }
        $years_valid = array();
        if ($year) {
            $years[] = $year;
        }

        $current_year = gmdate('Y', time());
        $max_year = $current_year + 2;
        $min_year = 1900;

        if ($years and sizeof($years)) {
            foreach ($years as $year) {
                if ($min_year < $year && $year < $max_year) {
                    $years_valid[$year] = $year;
                }
            }
        }
        //print_r($years_valid);
        $years_string = '';

        if (sizeof($years_valid) && $title) {
            $years_string = implode(' ', $years_valid);
        }


        $movies = array();
        $mode = ' ';
        $k = array();

        // Regexp
        $reg_tags = $this->get_reg_tags();
        $reg_quotes = $this->get_reg_quotes();

        if ($title) {

            $keywords = $this->satinize_keywords($title);

            if ($years_string) {
                $keywords .= ' ' . $years_string;
            }
            $limit = 10;

            $movies['title'] = $this->front_search_movies_an($keywords, $mode, true);
            $k['title'] = $keywords;

            // Find tags in title
            if (preg_match_all($reg_tags, $title, $match)) {
                $keywords_tag = $this->satinize_keywords(implode(' ', $match[1]));
                if ($years_string) {
                    $keywords_tag .= ' ' . $years_string;
                }
                $movies['title_tags'] = $this->front_search_movies_an($keywords_tag, $mode, true);
                $k['title_tags'] = $keywords_tag;
            }

            // Find quotes in title
            if (preg_match_all($reg_quotes, strip_tags($title), $match)) {
                $keywords_tag = $this->satinize_keywords(implode(' ', $match[1]));
                if ($years_string) {
                    $keywords_tag .= ' ' . $years_string;
                }
                $movies['title_quotes'] = $this->front_search_movies_an($keywords_tag, $mode, true);
                $k['title_quotes'] = $keywords_tag;
            }
        }

        // Find quotes in content
        $quotes = '';
        if ($content) {
            // Find tags
            if (preg_match_all($reg_tags, $content, $match)) {
                $keywords_tag = $this->satinize_keywords($this->cm->crop_text(implode(' ', $match[1]), 100, false));
                if ($years_string) {
                    $keywords_tag .= ' ' . $years_string;
                }
                $movies['content_tags'] = $this->front_search_movies_an($keywords_tag, $mode, true);
                $k['content_tags'] = $keywords_tag;
            }
            // Content quotes
            if (preg_match_all($reg_quotes, $this->validate_title_chars(strip_tags($content)), $match)) {
                $keywords_tag = $this->satinize_keywords($this->cm->crop_text(implode(' ', $match[1]), 100, false));

                if ($years_string) {
                    $keywords_tag .= ' ' . $years_string;
                }
                $movies['content_quotes'] = $this->front_search_movies_an($keywords_tag, $mode, true);
                $k['content_quotes'] = $keywords_tag;
            }
        }

        return array('keywords' => $k, 'movies' => $movies);
    }

    private function find_quote_tags($name = '', $content = '') {
        // “Blade Runner”
        // ‘Blade Runner 2049’
        $html_tags = array(['"', '"'], ['`', '`'], ['\'', '\''], ['“', '”'], ['‘', '’']);
        $found_tags = array();
        foreach ($html_tags as $tag) {
            $tag_string = $tag[0] . $name . $tag[1];
            if (strstr($content, $tag_string)) {
                $found_tags[$tag[0]] += 1;
            }
        }
        return $found_tags;
    }

    private function find_html_tags($name = '', $content = '') {
        $html_tags = array('b', 'strong', 'em', 'i');
        $found_tags = array();
        foreach ($html_tags as $tag) {
            $tag_string = "<{$tag}>{$name}</{$tag}>";
            if (strstr($content, $tag_string)) {
                $found_tags[$tag] += 1;
            }
        }
        return $found_tags;
    }

    private function get_reg_tags() {
        $html_tags = array('b', 'strong', 'em', 'i');
        $reg = array();
        foreach ($html_tags as $tag) {
            $reg['from'][] = "<" . $tag . "[^>]*>";
            $reg['to'][] = "</" . $tag . ">";
        }
        $reg_tags = '#(?:' . implode('|', $reg['from']) . ')([^<]+)(?:' . implode('|', $reg['to']) . ')#Us';
        return $reg_tags;
    }

    private function get_reg_quotes() {
        $reg_quotes = '#(?:"|\'|`|“|‘)([^<]+)(?:"|\'|`|”|’)#Us';
        return $reg_quotes;
    }

    private function validate_title_chars($title) {
        $title = str_replace('&#039;', '"', $title);
        $title = str_replace('&lsquo;', '‘', $title);
        $title = str_replace('&rsquo;', '’', $title);
        $title = str_replace('&ldquo;', '“', $title);
        $title = str_replace('&rdquo;', '”', $title);


        return $title;
    }

    public function satinize_keywords($title) {
        $keywords = '';
        $strip_title = strip_tags($title);
        if (preg_match_all('/[\w\d]+/', $strip_title, $match)) {
            $keywords = implode(' ', $match[0]);
        }
        return $keywords;
    }

    public function satinize_phrases($post_title = '', $title = '') {
        $title = strtolower($title);
        $post_title = htmlspecialchars_decode(strip_tags($post_title));
        $post_title = preg_replace("/(?:'|’)s([^\w]+)/", "s$1", $post_title); //’s Fascist Pigs Podcast

        $keywords = array();
        if (preg_match_all('/[\w\d ]+/', $post_title, $match)) {
            $mach_str = strtolower(implode(' ', $match[0]));
            $kws = explode(' ', $mach_str);

            foreach ($kws as $item) {
                $item = trim($item);
                if ($item) {
                    if ($title) {
                        if (strstr($title, $item)) {
                            $keywords[] = $item;
                        }
                    } else {
                        $keywords[] = $item;
                    }
                }
            }
        }

        return $keywords;
    }

    /*
     * Search movies by critic title     
     */

    public function search_movies_by_title($title = '', $num = 1, $limit = 10, $type = '') {
        $title = str_replace("'", "\'", $title);
        $title_query = '';
        if (!is_array($title)) {
            $title_query = sprintf("@title \"%s\"/%d", $title, $num);
        } else {
            $title_query_arr = array();
            foreach ($title as $value) {
                $title_query_arr[] = sprintf("@title \"%s\"/%d", $value, $num);
            }
            $title_query = implode('|', $title_query_arr);
        }

        $allow_types = array("'Movie'", "'TVseries'", "'VideoGame'");
        $type_and = ""; // " AND type IN(" . implode(',', $allow_types) . ")";
        if ($type) {
            $type_and = sprintf(' AND type="%s"', $type);
        }


        $sql = sprintf("SELECT id, title FROM movie_an "
                . "WHERE id>0" . $type_and . " AND MATCH('" . $title_query . "') LIMIT %d", $limit);

        $result = $this->sdb_results($sql);
        //print $sql;
        //print_r($result);
        return $result;
    }

    public function find_top_movie_by_title($title = '', $critic_title = '', $limit = 10, $type = '') {
        $title = str_replace("'", "\'", $title);
        $critic_title_arr = explode(' ', $critic_title);
        $critic_title_maybe = implode('|', $critic_title_arr);
        $critic_title_maybe = str_replace("'", "\'", $critic_title_maybe);
        $title_query = '';
        $title_query = sprintf("@title ((\"%s\") MAYBE (%s))", $title, $critic_title_maybe);

        $allow_types = array("'Movie'", "'TVseries'", "'VideoGame'");
        $type_and = ""; // " AND type IN(" . implode(',', $allow_types) . ")";
        if ($type) {
            $type_and = sprintf(' AND type="%s"', $type);
        }

        $sql = sprintf("SELECT id, title, type, SNIPPET(title, QUERY()) t, weight() w FROM movie_an "
                . "WHERE id>0" . $type_and . " AND MATCH('" . $title_query . "') ORDER BY w DESC LIMIT %d", $limit);

        $result = $this->sdb_results($sql);

        return $result;
    }

    /*
     * Front search db
     */

    public function front_search_critics($keyword = '', $limit = 20, $start = 0, $sort = array(), $filters = array(), $facets = array(), $show_meta = true) {

        //Sort logic
        $order = '';
        if ($sort) {
            /*
             * key: 'title', 'rating', 'date', 'rel'             
             * type: desc, asc
             */
            $sort_key = $sort['sort'];
            $sort_type = $sort['type'] == 'desc' ? 'DESC' : 'ASC';
            if ($sort_key == 'id') {
                $order = ' ORDER BY id ' . $sort_type;
            } else if ($sort_key == 'title') {
                $order = ' ORDER BY title ' . $sort_type;
            } else if ($sort_key == 'date') {
                $order = ' ORDER BY post_date ' . $sort_type;
            } else if ($sort_key == 'rel') {
                $order = ' ORDER BY w ' . $sort_type;
            }
        } else {
            // Default weight
            $order = ' ORDER BY w DESC';
        }

        // Filters logic
        $filters_and = '';
        if ($filters) {
            /*
             * key: author_type
             * value: 1
             */
            foreach ($filters as $key => $value) {
                //$filters_and .= sprintf(" AND %s='%s'", $this->escape($key), $this->escape($value));
            }

            /*
             * SELECT *, IN(brand_id,1,2,3,4) AS b FROM facetdemo WHERE MATCH('Product') AND b=1 LIMIT 0,10
              FACET brand_name, brand_id BY brand_id ORDER BY brand_id ASC
              FACET property ORDER BY COUNT(*) DESC
              FACET INTERVAL(price,200,400,600,800) ORDER BY FACET() ASC
              FACET categories ORDER BY FACET() ASC;
             */
        }

        $facets_and = '';
        if ($facets) {
            if (in_array('author_type', $facets)) {
                $facets_and .= " FACET author_type ORDER BY COUNT(*) DESC";
            }
        }

        $and_key = '';
        $snippet = ', title t, content c';
        if ($keyword) {
            $keyword = str_replace("'", "\'", $keyword);
            $match_query = $this->wildcards_maybe_query($keyword, false);
            $and_key = sprintf(" AND MATCH('@(title,content) (%s)')", $match_query);
            $snippet = ', SNIPPET(title, QUERY()) t, SNIPPET(content, QUERY()) c';
        }

        $sql = sprintf("SELECT id, date_add, weight() w, author_type" . $snippet
                . " FROM critic"
                . " WHERE top_movie>0" . $filters_and . $and_key . $order . " LIMIT %d,%d" . $facets_and, $start, $limit);


        $facets_arr = array();
        if ($facets) {
            $multi_result = $this->sdb_multi_results($sql);
            $result = $multi_result[0];
            foreach ($multi_result as $key => $value) {
                foreach ($facets as $_fkey => $f_value) {
                    if ($key == $f_key + 1) {
                        $facets_arr[$f_value] = $value;
                    }
                }
            }
        } else {
            $result = $this->sdb_results($sql);
        }

        if (!$show_meta) {
            return $result;
        }

        $total = $this->get_last_meta_total();

        return array('result' => $result, 'total' => $total, 'facets' => $facets_arr);
    }

    public function front_search_any_movies_by_title_an($title = '', $limit = 20, $start = 0, $show_meta = false) {
        $title = stripslashes($title);
        $title = addslashes($title);
        $match_query = $this->wildcards_maybe_query($title);
        $match = sprintf("'@(title,year) ((^%s$)|(" . $match_query . "))'", $title);

        $allow_types = array("'Movie'", "'TVseries'", "'VideoGame'");
        $type_and = " AND type IN(" . implode(',', $allow_types) . ")";


        // Default weight
        $order = ' ORDER BY w DESC';


        $sql = sprintf("SELECT id, rwt_id, title, year, type, weight() w FROM movie_an " .
                "WHERE id>0" . $type_and . " AND MATCH({$match}) $order LIMIT %d,%d", $start, $limit);

        $result = $this->sdb_results($sql);

        if (!$show_meta) {
            return $result;
        }

        $total = $this->get_last_meta_total();

        return array('result' => $result, 'total' => $total);
    }

    public function front_search_critics_multi($keyword = '', $limit = 20, $start = 0, $sort = array(), $filters = array(), $facets = false, $show_meta = true, $widlcard = false, $fields = array()) {

        //Sort logic
        $order = $this->get_order_query_critics($sort);

        // Movie weight logic        

        if (isset($sort['sort']) && $sort['sort'] == 'mw') {
            $start = 0;
            $limit = 10000;
        }

        //Keywords logic
        $match = '';
        if ($keyword) {
            $keyword = str_replace("'", "\'", $keyword);
            $search_keywords = $this->wildcards_maybe_query($keyword, $widlcard, ' ');
            $search_query = sprintf("'@(title,content,mtitle,myear) (%s)'", $search_keywords);
            $match = " AND MATCH(:match)";
        }

        $ret = array('list' => array(), 'count' => 0);
        $this->connect();
        $query_type = 'critics';

        // Filters logic
        $filters_and = $this->get_filters_query($filters, array(), $query_type);

        // Snipper logic
        if ($keyword) {
            $snippet = ', SNIPPET(title, QUERY()) t, SNIPPET(content, QUERY()) c, SNIPPET(mtitle, QUERY()) mt';
        }

        $custom_fields = '';
        if ($fields) {
            $custom_fields = ', ' . implode(', ', $fields) . ' ';
        }

        // Main sql
        $sql = sprintf("SELECT id, date_add, weight() w, author_type" . $snippet . $custom_fields . $order['select']
                . " FROM critic WHERE status=1" . $filters_and . $match . $order['order'] . " LIMIT %d,%d ", $start, $limit);

        //print_r($sql);
        //exit;
        //Get result
        $ret = $this->movie_results($sql, $match, $search_query);

        // Simple result
        if (!$show_meta) {
            return $ret['list'];
        }

        // Facets logic
        $facets_arr = array();
        if ($facets) {
            $facets_arr = $this->critic_facets($filters, $match, $search_query, $query_type);
        }

        $ret['facets'] = $facets_arr;
        return $ret;
    }

    public function front_search_movies_multi($keyword = '', $limit = 20, $start = 0, $sort = array(), $filters = array(), $facets = false, $show_meta = true, $widlcard = true, $show_main = true) {
        //Keywords logic
        $match = '';
        if ($keyword) {
            $search_keywords = $this->wildcards_maybe_query($keyword, $widlcard, ' ');
            $search_query = sprintf("'@(title,year) ((^%s$)|(%s))'", $keyword, $search_keywords);
            $match = " AND MATCH(:match)";
        }

        $ret = array('list' => array(), 'count' => 0);
        $this->connect();

        // Main logic
        if ($show_main) {

            //Sort logic
            $order = $this->get_order_query($sort);

            // Filters logic
            $filters_and = $this->get_filters_query($filters);

            // Main sql
            $sql = sprintf("SELECT id, rwt_id, title, release, type, year, weight() w, rrt, rrta, rrtg" . $order['select']
                    . " FROM movie_an WHERE id>0" . $filters_and . $match . $order['order'] . " LIMIT %d,%d ", $start, $limit);

            $ret = $this->movie_results($sql, $match, $search_query);

            // Simple result
            if (!$show_meta) {
                return $ret['list'];
            }
        }

        // Facets logic
        $facets_arr = array();
        if ($facets) {
            $facets_arr = $this->movies_facets($filters, $match, $search_query, $facets);
        }

        $ret['facets'] = $facets_arr;
        return $ret;
    }

    public function front_search_critic_movies($keyword = '', $limit = 20, $start = 0, $sort = array(), $filters = array(), $facets = false, $show_meta = true, $widlcard = false) {

        //Keywords logic
        $match = '';
        if ($keyword) {
            $keyword = str_replace("'", "\'", $keyword);
            $search_keywords = $this->wildcards_maybe_query($keyword, $widlcard, ' ');
            $search_query = sprintf("'@(title,content,mtitle,myear) (%s)'", $search_keywords);
            $match = " AND MATCH(:match)";
        }

        $ret = array('list' => array(), 'count' => 0);
        $this->connect();

        $query_type = 'critics';

        // Filters logic
        $filters_and = $this->get_filters_query($filters, array(), $query_type);


        // Main sql
        $sql = sprintf("SELECT GROUPBY() AS id, mtitle AS title, year_int as year,  weight() w FROM critic"
                . " WHERE top_movie>0" . $filters_and . $match
                . "  GROUP BY top_movie ORDER BY w DESC LIMIT %d,%d ", $start, $limit);

        //Get result
        $ret = $this->movie_results($sql, $match, $search_query);

        // Simple result
        if (!$show_meta) {
            return $ret['list'];
        }

        return $ret;
    }

    public function movie_results($sql, $match, $search_query) {
        //Get result
        $stmt = $this->sps->prepare($sql);

        if ($match) {
            $stmt->bindValue(':match', $search_query, PDO::PARAM_STR);
        }

        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_OBJ);

        // Total found
        $meta = $this->sps->query("SHOW META")->fetchAll();
        foreach ($meta as $m) {
            $meta_map[$m['Variable_name']] = $m['Value'];
        }
        $total_found = $meta_map['total_found'];

        return array('list' => $result, 'count' => $total_found);
    }

    public function critic_facets($filters, $match, $search_query, $query_type) {
        $facet_list = $this->facets['critics'];
        $sql_arr = $this->critic_facets_sql($facet_list, $filters, $match, $query_type);
        $facets_arr = $this->movies_facets_get($facet_list, $sql_arr, $match, $search_query);
        return $facets_arr;
    }

    public function critic_facets_sql($facet_list, $filters, $match, $query_type) {
        $skip = array();
        $sql_arr = array();
        $expand = isset($filters['expand']) ? $filters['expand'] : '';
        $audience_facets = array_keys($this->audience_facets);

        foreach ($facet_list as $facet) {
            if ($facet == 'release') {
                $filters_and = $this->get_filters_query($filters, $facet, $query_type);
                $sql_arr[] = "SELECT GROUPBY() as id, COUNT(*) as cnt FROM critic WHERE status=1 AND year_int>0" . $filters_and . $match
                        . " GROUP BY year_int ORDER BY year_int ASC LIMIT 0,200";
                $sql_arr[] = "SHOW META";
            } else if ($facet == 'author') {
                $filters_and = $this->get_filters_query($filters, 'author', $query_type);
                $sql_arr[] = "SELECT GROUPBY() as id, COUNT(*) as cnt FROM critic WHERE status=1" . $filters_and . $match
                        . " GROUP BY author_type ORDER BY cnt DESC LIMIT 0,10";
                $sql_arr[] = "SHOW META";
            } else if ($facet == 'tags') {
                $limit = $expand == 'tags' ? $this->facet_max_limit : $this->facet_limit;
                $filters_and = $this->get_filters_query($filters, 'tags', $query_type);
                $sql_arr[] = "SELECT GROUPBY() as id, COUNT(*) as cnt FROM critic WHERE status=1" . $filters_and . $match
                        . " GROUP BY tags ORDER BY cnt DESC LIMIT 0,$limit";
                $sql_arr[] = "SHOW META";
            } else if ($facet == 'from') {
                $limit = $expand == 'from' ? $this->facet_max_limit : $this->facet_limit;
                $filters_and = $this->get_filters_query($filters, 'from', $query_type);
                $sql_arr[] = "SELECT GROUPBY() as id, COUNT(*) as cnt FROM critic WHERE status=1 AND author_type!=2" . $filters_and . $match
                        . " GROUP BY aid ORDER BY cnt DESC LIMIT 0,$limit";
                $sql_arr[] = "SHOW META";
            } else if ($facet == 'genre') {
                $limit = $expand == 'genre' ? $this->facet_max_limit : $this->facet_limit;
                $filters_and = $this->get_filters_query($filters, 'genre', $query_type);
                $sql_arr[] = "SELECT GROUPBY() as id, COUNT(*) as cnt FROM critic WHERE status=1" . $filters_and . $match
                        . " GROUP BY genre ORDER BY cnt DESC LIMIT 0,$limit";
                $sql_arr[] = "SHOW META";
            } else if ($facet == 'type') {
                $filters_and = $this->get_filters_query($filters, 'type', $query_type);
                $sql_arr[] = "SELECT GROUPBY() as id, COUNT(*) as cnt FROM critic WHERE status=1" . $filters_and . $match
                        . " GROUP BY type ORDER BY cnt DESC LIMIT 0,10";
                $sql_arr[] = "SHOW META";
            } else if ($facet == 'state') {
                $filters_facet = $filters;
                unset($filters_facet['state']);
                $filters_and = $this->get_filters_query($filters_facet, '', $query_type);

                $sql_arr[] = "SELECT GROUPBY() as id, COUNT(*) as cnt FROM critic WHERE status=1" . $filters_and . $match
                        . " GROUP BY state ORDER BY cnt DESC LIMIT 0,10";
                $sql_arr[] = "SHOW META";
            } else if (in_array($facet, $audience_facets)) {
                $filters_and = $this->get_filters_query($filters, $facet, $query_type);
                $sql_arr[] = "SELECT GROUPBY() as id, COUNT(*) as cnt FROM critic WHERE status=1" . $filters_and . $match
                        . " GROUP BY " . $facet . " ORDER BY " . $facet . " ASC LIMIT 0,6";
                $sql_arr[] = "SHOW META";
            } else if ($facet == 'movie') {
                $filters_and = $this->get_filters_query($filters, $facet, $query_type);
                $sql_arr[] = "SELECT GROUPBY() AS id, COUNT(*) as cnt, mtitle AS title, year_int as year FROM critic"
                        . " WHERE status=1" . $filters_and . $match
                        . "  GROUP BY top_movie ORDER BY year_int DESC LIMIT 0,100";
                $sql_arr[] = "SHOW META";
            } /*else if ($facet == 'related') {
                $filters_facet = $filters;
                $filters_facet['state'] = 'related';
                $filters_and = $this->get_filters_query($filters_facet, '', $query_type);

                $sql_arr[] = "SELECT COUNT(*) as cnt FROM critic WHERE status=1 AND top_movie=0" . $filters_and . $match;

                $sql_arr[] = "SHOW META";
            }*/
        }

        return array('sql_arr' => $sql_arr, 'skip' => $skip);
    }

    public function movies_facets($filters, $match, $search_query, $facets) {
        // All facets
        $facet_list = $this->facets['movies'];
        if ($facets && is_array($facets)) {
            $facet_list = $facets;
        }

        $sql_arr = $this->movies_facets_sql($facet_list, $filters, $match);
        $facets_arr = $this->movies_facets_get($facet_list, $sql_arr, $match, $search_query);
        return $facets_arr;
    }

    public function movies_facets_sql($facet_list, $filters, $match) {
        $skip = array();
        $sql_arr = array();
        $expand = isset($filters['expand']) ? $filters['expand'] : '';
        $audience_facets = array_keys($this->audience_facets);
        $rating_facets = array_keys($this->rating_facets);
        foreach ($facet_list as $facet) {
            if ($facet == 'release') {
                $filters_and = $this->get_filters_query($filters, $facet);
                $sql_arr[] = "SELECT GROUPBY() as id, COUNT(*) as cnt FROM movie_an WHERE year_int>0" . $filters_and . $match
                        . " GROUP BY year_int ORDER BY year_int ASC LIMIT 0,200";
                $sql_arr[] = "SHOW META";
            } else if ($facet == 'type') {
                $filters_and = $this->get_filters_query($filters, 'type');
                $sql_arr[] = "SELECT GROUPBY() as id, COUNT(*) as cnt FROM movie_an WHERE id>0" . $filters_and . $match
                        . " GROUP BY type ORDER BY cnt DESC LIMIT 0,10";
                $sql_arr[] = "SHOW META";
            } else if ($facet == 'country') {
                $limit = $expand == 'country' ? $this->facet_max_limit : $this->facet_limit;
                $filters_and = $this->get_filters_query($filters, 'country');
                $sql_arr[] = "SELECT GROUPBY() as id, COUNT(*) as cnt FROM movie_an WHERE id>0" . $filters_and . $match
                        . " GROUP BY country ORDER BY cnt DESC LIMIT 0,$limit";
                $sql_arr[] = "SHOW META";
            } else if ($facet == 'genre') {
                $limit = $expand == 'genre' ? $this->facet_max_limit : $this->facet_limit;
                $filters_and = $this->get_filters_query($filters, array('genre', 'minus-genre'));
                $sql_arr[] = "SELECT GROUPBY() as id, COUNT(*) as cnt FROM movie_an WHERE id>0" . $filters_and . $match
                        . " GROUP BY genre ORDER BY cnt DESC LIMIT 0,$limit";
                $sql_arr[] = "SHOW META";
            } else if ($facet == 'mkw') {
                $limit = $expand == 'mkw' ? $this->facet_max_limit : $this->facet_limit;
                $filters_and = $this->get_filters_query($filters, array('mkw'), 'movies', $facet);
                $max_option = '';
                if ($limit > 1000) {
                    $max_option = ' OPTION max_matches=' . $limit;
                }
                $sql_arr[] = "SELECT GROUPBY() as id, COUNT(*) as cnt FROM movie_an WHERE id>0" . $filters_and . $match
                        . " GROUP BY " . $facet . " ORDER BY cnt DESC LIMIT 0,$limit" . $max_option;
                $sql_arr[] = "SHOW META";
            } else if ($facet == 'actors') {
                // Cast actor logic
                $facet_active = $this->get_active_race_facet($filters);

                if (isset($this->facets_race_cast[$facet_active])) {
                    $race_name = $this->facets_race_cast[$facet_active]['name'];
                    $limit = $expand == $this->facets_race_cast[$facet_active]['filter'] ? $this->facet_max_limit : $this->facet_limit;
                    $filters_and = $this->get_filters_query($filters, $this->facets_race_cast[$facet_active]['filter']);
                    $sql_arr[] = "SELECT GROUPBY() as id, COUNT(*) as cnt FROM movie_an WHERE id>0" . $filters_and . $this->filter_actor_and . $match
                            . " GROUP BY " . $race_name . " ORDER BY cnt DESC LIMIT 0,$limit";
                    $sql_arr[] = "SHOW META";
                } else {
                    $skip[] = $facet;
                }
            } else if ($facet == 'dirs') {
                // Directors logic
                $facet_active = $this->get_active_director_facet($filters);

                if (isset($this->facets_race_directors[$facet_active])) {
                    $race_name = $this->facets_race_directors[$facet_active]['name'];
                    $limit = $expand == $this->facets_race_directors[$facet_active]['filter'] ? $this->facet_max_limit : $this->facet_limit;
                    $filters_and = $this->get_filters_query($filters, $this->facets_race_directors[$facet_active]['filter']);
                    $sql_arr[] = "SELECT GROUPBY() as id, COUNT(*) as cnt FROM movie_an WHERE id>0" . $filters_and . $this->filter_actor_and . $match
                            . " GROUP BY " . $race_name . " ORDER BY cnt DESC LIMIT 0,$limit";
                    $sql_arr[] = "SHOW META";
                } else {
                    $skip[] = $facet;
                }
            } else if ($facet == 'provider') {
                $limit = $this->facet_max_limit;
                $filters_and = $this->get_filters_query($filters, array('provider', 'price'));
                $sql_arr[] = "SELECT GROUPBY() as id, COUNT(*) as cnt FROM movie_an WHERE id>0" . $filters_and . $match
                        . " GROUP BY provider ORDER BY cnt DESC LIMIT 0,$limit";
                $sql_arr[] = "SHOW META";
            } else if ($facet == 'providerfree') {
                $limit = $this->facet_max_limit;
                $filters_and = $this->get_filters_query($filters, array('provider', 'price'));
                $sql_arr[] = "SELECT GROUPBY() as id, COUNT(*) as cnt FROM movie_an WHERE id>0" . $filters_and . $match
                        . " GROUP BY providerfree ORDER BY cnt DESC LIMIT 0,$limit";
                $sql_arr[] = "SHOW META";
            } else if ($facet == 'race') {
                $limit = $this->facet_limit;
                $filters_and = $this->get_filters_query($filters, array('race', 'minus-race'));
                $sql_arr[] = "SELECT GROUPBY() as id, COUNT(*) as cnt FROM movie_an WHERE id>0" . $filters_and . $match
                        . " GROUP BY race ORDER BY cnt DESC LIMIT 0,$limit";
                $sql_arr[] = "SHOW META";
            } else if ($facet == 'dirrace') {
                $limit = $this->facet_limit;
                $filters_and = $this->get_filters_query($filters, array('dirrace', 'minus-dirrace'));
                $sql_arr[] = "SELECT GROUPBY() as id, COUNT(*) as cnt FROM movie_an WHERE id>0" . $filters_and . $match
                        . " GROUP BY dirrace ORDER BY cnt DESC LIMIT 0,$limit";
                $sql_arr[] = "SHOW META";
            } else if ($facet == 'budget') {
                $filters_and = $this->get_filters_query($filters, array('budget'));
                $sql_arr[] = "SELECT GROUPBY() as id, COUNT(*) as cnt FROM movie_an WHERE id>0" . $filters_and . $match
                        . " GROUP BY budget ORDER BY budget DESC LIMIT 0,$limit";
                $sql_arr[] = "SHOW META";
            } else if ($facet == 'race_cast') {
                // Race actor logic
                $facet_active = $this->get_active_race_facet($filters);
                if (isset($this->facets_race_cast[$facet_active])) {
                    $limit = $this->facet_limit;
                    $filters_and = $this->get_filters_query($filters, array($facet_active, 'minus-' . $facet_active));
                    $sql_arr[] = "SELECT GROUPBY() as id, COUNT(*) as cnt FROM movie_an WHERE id>0" . $filters_and . $match
                            . " GROUP BY " . $facet_active . " ORDER BY cnt DESC LIMIT 0,$limit";
                    $sql_arr[] = "SHOW META";
                } else {
                    $skip[] = $facet;
                }
            } else if ($facet == 'race_dir') {
                // Race director logic
                $facet_active = $this->get_active_director_facet($filters);
                if (isset($this->facets_race_directors[$facet_active])) {
                    $limit = $this->facet_limit;
                    $filters_and = $this->get_filters_query($filters, array($facet_active, 'minus-' . $facet_active));
                    $sql_arr[] = "SELECT GROUPBY() as id, COUNT(*) as cnt FROM movie_an WHERE id>0" . $filters_and . $match
                            . " GROUP BY " . $facet_active . " ORDER BY cnt DESC LIMIT 0,$limit";
                    $sql_arr[] = "SHOW META";
                } else {
                    $skip[] = $facet;
                }
            } else if ($facet == 'gender_cast') {
                // Gender actor logic
                $facet_active = $this->get_active_gender_facet($filters);
                if (isset($this->facets_gender[$facet_active])) {
                    $limit = $this->facet_limit;
                    $filters_and = $this->get_filters_query($filters, array($facet_active, 'minus-' . $facet_active));
                    $sql_arr[] = "SELECT GROUPBY() as id, COUNT(*) as cnt FROM movie_an WHERE id>0" . $filters_and . $match
                            . " GROUP BY " . $facet_active . " ORDER BY cnt DESC LIMIT 0,$limit";
                    $sql_arr[] = "SHOW META";
                } else {
                    $skip[] = $facet;
                }
            } else if ($facet == 'gender_dir') {
                // Gender director logic
                $facet_active = $this->get_active_gender_dir_facet($filters);

                if (isset($this->facets_gender_dir[$facet_active])) {
                    $limit = $this->facet_limit;
                    $filters_and = $this->get_filters_query($filters, array($facet_active, 'minus-' . $facet_active));
                    $sql_arr[] = "SELECT GROUPBY() as id, COUNT(*) as cnt FROM movie_an WHERE id>0" . $filters_and . $match
                            . " GROUP BY " . $facet_active . " ORDER BY cnt DESC LIMIT 0,$limit";
                    $sql_arr[] = "SHOW META";
                } else {
                    $skip[] = $facet;
                }
            } else if (in_array($facet, $audience_facets)) {
                $filters_and = $this->get_filters_query($filters, $facet);
                $sql_arr[] = "SELECT GROUPBY() as id, COUNT(*) as cnt FROM movie_an WHERE id>0" . $filters_and . $match
                        . " GROUP BY " . $facet . " ORDER BY " . $facet . " ASC LIMIT 0,6";
                $sql_arr[] = "SHOW META";
            } else if (in_array($facet, $rating_facets)) {
                $max_count = isset($this->rating_facets[$facet]['max_count']) ? $this->rating_facets[$facet]['max_count'] : 100;
                $filters_and = $this->get_filters_query($filters, $facet);
                if ($facet == 'rrtg') {
                    $filters_and .= " AND rrta>0 AND rrt>0";
                }
                $sql_arr[] = "SELECT GROUPBY() as id, COUNT(*) as cnt FROM movie_an WHERE id>0" . $filters_and . $match
                        . " GROUP BY " . $facet . " ORDER BY " . $facet . " ASC LIMIT 0," . $max_count;
                $sql_arr[] = "SHOW META";
            }
            /*
             * UNUSED
             * else if ($facet == 'lgbt') {
              $filters_and = $this->get_filters_query($filters, array('rf', 'minus-rf'));
              $sql_arr[] = "SELECT GROUPBY() as id, COUNT(*) as cnt FROM movie_an WHERE id>0" . $filters_and . $match
              . " GROUP BY lgbt ORDER BY cnt DESC LIMIT 0,10";
              $sql_arr[] = "SHOW META";
              } else if ($facet == 'woke') {
              $filters_and = $this->get_filters_query($filters, array('rf', 'minus-rf'));
              $sql_arr[] = "SELECT GROUPBY() as id, COUNT(*) as cnt FROM movie_an WHERE id>0" . $filters_and . $match
              . " GROUP BY woke ORDER BY cnt DESC LIMIT 0,10";
              $sql_arr[] = "SHOW META";
              } */
        }
        return array('sql_arr' => $sql_arr, 'skip' => $skip);
    }

    public function movies_facets_get($facet_list, $data_arr, $match, $search_query) {
        $facets_arr = array();
        $sql_arr = $data_arr['sql_arr'];
        $skip = $data_arr['skip'];
        if (sizeof($sql_arr)) {

            $sql = implode('; ', $sql_arr);

            $this->sps->setAttribute(PDO::ATTR_EMULATE_PREPARES, 1);

            $stmt = $this->sps->prepare($sql);
            if ($match) {
                $stmt->bindValue(':match', $search_query, PDO::PARAM_STR);
            }
            $stmt->execute();
            $rows = array();
            do {
                $rows[] = $stmt->fetchAll(PDO::FETCH_OBJ);
            } while ($stmt->nextRowset());

            $i = 0;
            foreach ($facet_list as $facet) {
                if (in_array($facet, $skip)) {
                    continue;
                }
                if ($rows[$i] && $rows[$i + 1]) {
                    $facets_arr[$facet]['data'] = $rows[$i];
                    $facets_arr[$facet]['meta'] = $rows[$i + 1];
                }
                $i += 2;
            }
        }
        return $facets_arr;
    }

    public function movies_facet_single_get($sql, $search_query) {
        $this->connect();
        $stmt = $this->sps->prepare($sql);
        $stmt->bindValue(':match', $search_query, PDO::PARAM_STR);
        $stmt->execute();
        $value = $stmt->fetchAll(PDO::FETCH_OBJ);
        $meta = $this->sps->query("SHOW META")->fetchAll();

        return array('data' => $value, 'meta' => $meta);
    }

    public function find_actors($keyword, $ids = array(), $cast = '') {
        $search_keywords = $this->wildcards_maybe_query($keyword, true, ' ');
        $ids_and = '';
        if (sizeof($ids)) {
            $ids_and = " AND actor_id IN (" . implode(',', $ids) . ")";
        }
        $actor_db = 'actor_star';
        if ($cast == 'all' || $cast == 'main') {
            $actor_db = 'actor_' . $cast;
        }
        $sql = sprintf("SELECT actor_id, name FROM " . $actor_db . " WHERE MATCH('%s')" . $ids_and . ' LIMIT 1000', $search_keywords);
        $result = $this->sdb_results($sql);
        $results = array();
        if (sizeof($result)) {
            foreach ($result as $item) {
                $results[$item->actor_id] = $item->name;
            }
        }
        return $results;
    }

    private function get_order_query_critics($sort = array()) {
        //Sort logic
        $order = '';
        $select = '';
        if ($sort) {
            /*
             * key: 'title', 'rating', 'date', 'rel'             
             * type: desc, asc
             */
            $sort_key = $sort['sort'];
            $sort_type = $sort['type'] == 'desc' ? 'DESC' : 'ASC';
            $audience_facets = array_keys($this->audience_facets);
            if ($sort_key == 'id') {
                $order = ' ORDER BY id ' . $sort_type;
            } else if ($sort_key == 'title') {
                $order = ' ORDER BY title ' . $sort_type;
            } else if ($sort_key == 'date') {
                $order = ' ORDER BY post_date ' . $sort_type;
            } else if ($sort_key == 'rel') {
                $order = ' ORDER BY w ' . $sort_type;
            } else if ($sort_key == 'mw') {
                $order = ' ORDER BY id DESC';
            } else if (in_array($sort_key, $audience_facets) || $sort_key == 'pop') {
                if ($sort_type == 'DESC') {
                    $order = ' ORDER BY ' . $sort_key . ' DESC';
                } else {
                    $order = ' ORDER BY ' . $sort_key . '_valid ASC';
                    $select = ', IF(' . $sort_key . '>0, ' . $sort_key . ', 999) as ' . $sort_key . '_valid';
                }
            }
        } else {
            // Default weight
            $order = ' ORDER BY w DESC';
        }
        return array('order' => $order, 'select' => $select);
    }

    public function get_order_query($sort = array()) {
        //Sort logic
        $order = '';
        $select = '';
        if ($sort) {
            /*
             * key: 'title', 'rating', 'date', 'rel'             
             * type: desc, asc
             */
            $sort_key = $sort['sort'];
            $sort_type = $sort['type'] == 'desc' ? 'DESC' : 'ASC';

            $audience_facets = array_keys($this->audience_facets);
            $rating_facets = array_keys($this->rating_facets);
            $popularity_facets = array_keys($this->popularity_facets);

            if ($sort_key == 'title') {
                $order = ' ORDER BY title ' . $sort_type;
            } else if ($sort_key == 'date') {
                if ($sort_type == 'DESC') {
                    $order = ' ORDER BY year_int ' . $sort_type . ', release_ts ' . $sort_type;
                } else {
                    $order = ' ORDER BY year_int_valid ASC';
                    $select = ', IF(year_int>0, year_int, 9999) as year_int_valid';
                }
            } else if ($sort_key == 'rel') {
                $order = ' ORDER BY w ' . $sort_type;
            } else if ($sort_key == 'rating') {
                $order = ' ORDER BY rating ' . $sort_type;
            } else if ($sort_key == 'div') {
                if ($sort_type == 'DESC') {
                    $order = ' ORDER BY diversity DESC';
                } else {
                    $order = ' ORDER BY diversity_valid ASC';
                    $select = ', IF(diversity>0, diversity, 999) as diversity_valid';
                }
            } else if ($sort_key == 'fem') {
                if ($sort_type == 'DESC') {
                    $order = ' ORDER BY female DESC';
                } else {
                    $order = ' ORDER BY female_valid ASC';
                    $select = ', IF(female>0, female, 999) as female_valid';
                }
            } else if (in_array($sort_key, $rating_facets) || in_array($sort_key, $audience_facets) || in_array($sort_key, $popularity_facets)) {

                $select = ", " . $sort_key . " as sortval";

                if ($sort_type == 'DESC') {
                    $order = ' ORDER BY ' . $sort_key . ' DESC';
                } else {
                    $order = ' ORDER BY ' . $sort_key . '_valid ASC';
                    $select .= ', IF(' . $sort_key . '>0, ' . $sort_key . ', 999) as ' . $sort_key . '_valid';
                }
            }
        } else {
            // Default weight
            $order = ' ORDER BY w DESC';
        }
        return array('order' => $order, 'select' => $select);
    }

    public function get_filters_query($filters = array(), $exlude = array(), $query_type = 'movies', $curr_filter = '') {
        // Filters logic
        $filters_and = '';
        if (!isset($filters['release'])) {
            $release = date('Y', time());
            $filters['release'] = '0-' . ($release + 1);
        }

        if ($query_type == 'critics') {
            $top_movie_sql = " AND top_movie>0";

            if (!isset($filters['state'])) {
                $filters['state'] = array('related', 'contains', 'proper');
            }

            if (isset($filters['state'])) {

                if (is_array($filters['state']) && sizeof($filters['state']) == 1) {
                    $filters['state'] = $filters['state'][0];
                }

                if (is_array($filters['state'])) {
                    /*if (in_array('related', $filters['state'])) {
                        unset($filters['state'][array_search('related', $filters['state'])]);
                        // $filters_and .= $this->filter_multi_value('state', $filters['state']);

                        $not = array();
                        foreach ($this->search_filters['state'] as $key => $value) {
                            if ($key == 'related') {
                                //continue;
                            }
                            if (!in_array($key, $filters['state'])) {
                                $not[] = $value['key'];
                            }
                        }

                        $top_movie_sql = " AND state NOT IN (" . implode(',', $not) . ")";
                    } else {*/
                        $filters_and .= $this->filter_multi_value('state', $filters['state']);
                    /*}*/
                } else {
                    /*if ($filters['state'] == 'related') {
                        unset($filters['state']);
                        $top_movie_sql = " AND top_movie=0";
                    } else {*/
                        $filters_and .= $this->filter_multi_value('state', $filters['state']);
                    /*}*/
                }
            }
            $filters_and .= $top_movie_sql;
        }

        if ($query_type == 'movies') {
            $filters_and .= " AND title!=''";
        }

        if (sizeof($filters)) {
            foreach ($filters as $key => $value) {
                $minus = false;
                if (strstr($key, 'minus-')) {
                    $key = str_replace('minus-', '', $key);
                    $minus = true;
                }

                // Get titles
                if ($key == 'mkw') {
                    $value = is_array($value) ? $value : array($value);

                    $titles = $this->get_keywords_titles($value);
                    if ($titles) {
                        foreach ($titles as $slug => $title) {
                            $this->search_filters[$key][$slug] = array('key' => $slug, 'title' => $title);
                        }
                    }
                } else if ($key == 'genre') {
                    // Genre
                    $ma = $this->get_ma();
                    $value = is_array($value) ? $value : array($value);
                    foreach ($value as $slug) {
                        $genre = $ma->get_genre_by_slug($slug, true);
                        $this->search_filters[$key][$slug] = array('key' => $genre->id, 'title' => $genre->name);
                    }
                } else if ($key == 'provider') {
                    // Provider
                    $ma = $this->get_ma();
                    $value = is_array($value) ? $value : array($value);
                    foreach ($value as $slug) {
                        $prov = $ma->get_provider_by_slug($slug, true);
                        $this->search_filters[$key][$slug] = array('key' => $prov->pid, 'title' => $prov->name);
                    }
                } else if ($key == 'actor' || $key == 'actorstar' || $key == 'actormain') {
                    // Actor       
                    $value = is_array($value) ? $value : array($value);
                    $names = $this->get_actor_names($value);
                    foreach ($value as $id) {
                        $this->search_filters[$key][$id] = array('key' => $id, 'title' => $names[$id]);
                    }
                    $actor_filter = $this->actor_filters[$key]['filter'];
                } else if ($key == 'dirall' || $key == 'dir' || $key == 'dirwrite' || $key == 'dircast' || $key == 'dirprod') {
                    // Director  
                    $value = is_array($value) ? $value : array($value);
                    $names = $this->get_actor_names($value);
                    foreach ($value as $id) {
                        $this->search_filters[$key][$id] = array('key' => $id, 'title' => $names[$id]);
                    }
                    $actor_filter = $this->director_filters[$key]['filter'];
                } else if ($key == 'country') {
                    // Country
                    $ma = $this->get_ma();
                    $value = is_array($value) ? $value : array($value);
                    foreach ($value as $slug) {
                        $country = $ma->get_country_by_slug($slug, true);
                        $this->search_filters[$key][$slug] = array('key' => $country->id, 'title' => $country->name);
                    }
                } else if ($key == 'from') {
                    // From author
                    $value = is_array($value) ? $value : array($value);
                    $authors = $this->cm->get_authors_by_ids($value);
                    foreach ($value as $slug) {
                        // Todo get author by slug
                        $this->search_filters[$key][$slug] = array('key' => $slug, 'title' => $authors[$slug]->name);
                    }
                } else if ($key == 'tags') {
                    // Tags                       
                    $value = is_array($value) ? $value : array($value);
                    foreach ($value as $slug) {
                        $tag = $this->cm->get_tag_by_slug($slug);
                        $this->search_filters[$key][$slug] = array('key' => $tag->id, 'title' => $tag->name);
                    }
                } else if ($key == 'movie') {
                    // Movie                 
                    $value = is_array($value) ? $value : array($value);
                    $names = $this->get_movie_names($value);

                    foreach ($value as $id) {
                        $this->search_filters[$key][$id] = array('key' => $id, 'title' => $names[$id]);
                    }
                }


                // Exclude filter
                if (is_array($exlude)) {
                    if (in_array($key, $exlude)) {
                        continue;
                    }
                } else if ($key == $exlude) {
                    continue;
                }


                if ($query_type == 'movies' || $query_type == 'critics') {
                    if ($key == 'genre') {
                        // Genre
                        $filters_and .= $this->filter_multi_value($key, $value, true, $minus);
                    } else if ($key == 'type') {
                        // Type
                        $filters_and .= $this->filter_multi_value('type', $value);
                    } else if ($key == 'release') {
                        // Release
                        $dates = explode('-', $value);
                        $release_from = (int) $dates[0];
                        $release_to = (int) $dates[1];
                        if ($release_from == $release_to) {
                            $filters_and .= sprintf(" AND year_int=%d", $release_from);
                        } else {
                            $filters_and .= sprintf(" AND year_int >=%d AND year_int < %d", $release_from, $release_to);
                        }
                    } else if (isset($this->audience_facets[$key])) {
                        // AU Rating
                        if ($key == 'auvote') {
                            $filters_and .= $this->filter_multi_value($key, $value);
                        } else {
                            $dates = explode('-', $value);
                            $from = (int) $dates[0];
                            $to = (int) $dates[1];
                            if ($from == $to) {
                                $filters_and .= sprintf(" AND " . $key . " =%d", $from);
                            } else {
                                $filters_and .= sprintf(" AND " . $key . " >%d AND " . $key . " <= %d", $from, $to);
                            }
                        }
                    }
                }

                if ($query_type == 'movies') {
                    if ($key == 'provider') {
                        // Provider
                        $filters_and .= $this->filter_multi_value($key, $value, true);
                    } else if ($key == 'price') {
                        // Provider price
                        $ma = $this->get_ma();
                        $pay_type = 1;
                        $list = $ma->get_providers_by_type($pay_type);
                        $filters_and .= $this->filter_multi_value('provider', $list, true);
                    } else if ($key == 'actor' || $key == 'actorstar' || $key == 'actormain') {
                        // Actor       
                        $filters_and .= $this->filter_multi_value($actor_filter, $value);
                    } else if ($key == 'dirall' || $key == 'dir' || $key == 'dirwrite' || $key == 'dircast' || $key == 'dirprod') {
                        // Director  
                        $filters_and .= $this->filter_multi_value($actor_filter, $value);
                    } else if ($key == 'rating') {
                        // Rating
                        $dates = explode('-', $value);
                        $from = (int) $dates[0];
                        $to = (int) $dates[1];
                        if ($from == $to) {
                            $filters_and .= sprintf(" AND rating=%d", $from);
                        } else {
                            $filters_and .= sprintf(" AND rating >%d AND rating <= %d", $from, $to);
                        }
                    } else if ($key == 'country') {
                        // Country
                        $filters_and .= $this->filter_multi_value($key, $value, true, $minus);
                    } else if ($key == 'race' || isset($this->facets_race_cast[$key])) {
                        // Race 
                        $filters_and .= $this->filter_multi_value($key, $value, true, $minus);
                    } else if ($key == 'dirrace' || isset($this->facets_race_directors[$key])) {
                        // Race directors
                        $filters_and .= $this->filter_multi_value($key, $value, true, $minus);
                    } else if ($key == 'gender' || isset($this->facets_gender[$key])) {
                        // Gender 
                        $filters_and .= $this->filter_multi_value($key, $value, true, $minus);
                    } else if ($key == 'dirgender' || isset($this->facets_gender_dir[$key])) {
                        // Gender dirs
                        $filters_and .= $this->filter_multi_value($key, $value, true, $minus);
                    } else if (isset($this->rating_facets[$key])) {
                        $dates = explode('-', $value);
                        $from = (int) $dates[0];
                        $to = (int) $dates[1];

                        if ($from == $to) {
                            $filters_and .= sprintf(" AND " . $key . "=%d", $from);
                        } else {
                            $filters_and .= sprintf(" AND " . $key . " >%d AND " . $key . " <= %d", $from, $to);
                        }
                        if ($key == 'rrtg') {
                            $filters_and .= " AND rrta>0 AND rrt>0";
                        }
                    } else if ($key == 'rf') {
                        $value = is_array($value) ? $value : array($value);
                        foreach ($value as $slug) {
                            if ($this->search_filters[$key][$slug]) {
                                $filters_and .= $this->filter_multi_value($slug, 1, false, $minus, true, true, false);
                            }
                        }
                    } else if ($key == 'mkw') {
                        // Movie Keywords
                        $filters_and .= $this->filter_multi_value($key, $value, true, $minus);
                    }
                } else if ($query_type == 'critics') {
                    if ($key == 'author') {
                        // Author
                        $filters_and .= $this->filter_multi_value('author_type', $value);
                    } else if ($key == 'from') {
                        // From author
                        $filters_and .= $this->filter_multi_value('aid', $value, true);
                    } else if ($key == 'tags') {
                        // Tags                       
                        $filters_and .= $this->filter_multi_value($key, $value, true);
                    } else if ($key == 'state') {
                        // Type
                        // $filters_and .= $this->filter_multi_value('state', $value);
                    } else if ($key == 'movie') {
                        // Movie                 
                        $filters_and .= $this->filter_multi_value('movies', $value, true);
                    }
                }
            }
        }


        if ($curr_filter && isset($this->filter_custom_and[$curr_filter])) {
            $filters_and .= $this->filter_custom_and[$curr_filter];
        }

        return $filters_and;
    }

    public function filter_multi_value($key, $value, $multi = false, $not = false, $any = true, $not_all = true, $not_and = true) {
        $filters_and = '';
        $and = 'ANY';
        if (!$any) {
            $and = 'ALL';
        }

        $and_not = 'ALL';
        if (!$not_all) {
            $and_not = 'ANY';
        }

        if (is_array($value) && sizeof($value) == 1) {
            $value = $value[0];
        }

        if (is_array($value)) {
            $provider_valid_arr = array();
            foreach ($value as $item) {
                $filter = $this->get_search_filter($key, $item);
                if ($filter !== '') {
                    $provider_valid_arr[] = $filter;
                }
            }
            if (sizeof($provider_valid_arr)) {
                if (!$not) {
                    if ($multi) {
                        // https://sphinxsearch.com/bugs/view.php?id=2627
                        $filters_and .= sprintf(" AND $and(%s) IN (%s)", $key, implode(',', $provider_valid_arr));
                    } else {
                        $filters_and .= sprintf(" AND %s IN (%s)", $key, implode(',', $provider_valid_arr));
                    }
                } else {
                    // Filter not
                    $and_any = '';
                    if ($not_and) {
                        $and_any = sprintf(" AND ANY(%s)>0", $key);
                    }
                    if ($multi) {
                        foreach ($provider_valid_arr as $filter) {
                            $filters_and .= sprintf(" AND $and_not(%s)!=%s" . $and_any, $key, $filter);
                        }
                    } else {
                        foreach ($provider_valid_arr as $filter) {
                            $filters_and .= sprintf(" AND %s!=%s" . $and_any, $key, $filter);
                        }
                    }
                }
            }
        } else {
            $filter = $this->get_search_filter($key, $value);
            if ($filter !== '') {
                if (!$not) {
                    if ($multi) {
                        $filters_and .= sprintf(" AND $and(%s)=%s", $key, $filter);
                    } else {
                        $filters_and .= sprintf(" AND %s=%s", $key, $filter);
                    }
                } else {
                    // Filter not
                    $and_any = '';
                    if ($not_and) {
                        $and_any = sprintf(" AND ANY(%s)>0", $key);
                    }
                    if ($multi) {
                        $filters_and .= sprintf(" AND ALL(%s)!=%s" . $and_any, $key, $filter);
                    } else {
                        $filters_and .= sprintf(" AND %s!=%s" . $and_any, $key, $filter);
                    }
                }
            }
        }
        return $filters_and;
    }

    private function get_search_filter($key, $value) {
        $filter = '';
        if ($key == 'dirrace' || isset($this->facets_race_cast[$key]) || isset($this->facets_race_directors[$key])) {
            $key = 'race';
        } else if (isset($this->facets_gender[$key])) {
            $key = 'gender';
        } else if (isset($this->facets_gender_dir[$key])) {
            $key = 'gender';
        }

        if (isset($this->search_filters[$key][$value])) {
            $filter = $this->search_filters[$key][$value]['key'];
            if (!$this->is_int($filter)) {
                $filter = "'" . $filter . "'";
            }
        } else {
            $filter = $value;
            if (!$this->is_int($value)) {
                $filter = "'" . $value . "'";
            }
        }
        return $filter;
    }

    public function front_search_movies_an($keyword = '', $mode = ' MAYBE ', $need_year = false, $type = '', $start = 0, $limit = 20, $show_meta = false) {

        $widlcard = false;

        // Default weight
        $order = ' ORDER BY w DESC';

        //Custom type
        $and_type = '';
        if ($type) {
            $and_type = sprintf(" AND type='%s'", $type);
        }

        $year_and = '';
        if ($need_year) {
            $year_and = ' AND year>0';
        }

        $match = '';
        if ($keyword) {
            $keyword = str_replace("'", "\'", $keyword);
            $match_query = $this->wildcards_maybe_query($keyword, $widlcard, $mode);

            if ($mode == " ") {
                $match = sprintf(" AND MATCH('@(title,year) ((^%s$)|(\"%s\"/1))')", $keyword, $match_query);
            } else {
                $match = sprintf(" AND MATCH('@(title,year) ((^%s$)|(%s))')", $keyword, $match_query);
            }
        }

        $sql = sprintf("SELECT id, rwt_id, title, release, type, year, weight() w FROM movie_an WHERE id>0"
                . $year_and . $and_type . $match . $order . " LIMIT %d,%d", $start, $limit);

        $result = $this->sdb_results($sql);

        if (!$show_meta) {
            return $result;
        }

        $total = $this->get_last_meta_total();

        return array('result' => $result, 'total' => $total);
    }

    public function get_cast_tabs() {
        $tabs_arr = array(
            'stars' => array('facet' => 'starrace', 'title' => 'Stars'),
            'main' => array('facet' => 'mainrace', 'title' => 'Main'),
            'all' => array('facet' => 'race', 'title' => 'All'),
        );
        return $tabs_arr;
    }

    public function get_default_cast_tab() {
        return 'starrace';
    }

    public function get_active_race_facet($filters) {
        $ret = $this->get_default_cast_tab();
        $tabs = $this->get_cast_tabs();
        $active_tab = isset($filters['cast']) ? $filters['cast'] : '';
        foreach ($tabs as $slug => $value) {
            if ($slug == $active_tab) {
                $ret = $value['facet'];
            }
        }
        return $ret;
    }

    public function get_active_gender_facet($filters) {
        $race = $this->get_active_race_facet($filters);
        $gender = $this->race_gender[$race];
        return $gender;
    }

    public function get_active_gender_dir_facet($filters) {
        $race = $this->get_active_director_facet($filters);
        $gender = $this->race_gender_dir[$race];
        return $gender;
    }

    public function get_director_tabs() {
        $tabs_arr = array(
            'all' => array('facet' => 'dirrace', 'title' => 'All'),
            'directors' => array('facet' => 'dirsrace', 'title' => 'Directors'),
            'writers' => array('facet' => 'writersrace', 'title' => 'Writers'),
            'cast-directors' => array('facet' => 'castdirrace', 'title' => 'Casting Directors'),
            'producers' => array('facet' => 'producerrace', 'title' => 'Producers'),
        );

        return $tabs_arr;
    }

    public function get_default_director_tab() {
        return 'dirrace';
    }

    public function get_active_director_facet($filters) {
        $ret = $this->get_default_director_tab();
        $tabs = $this->get_director_tabs();
        $active_tab = isset($filters['director']) ? $filters['director'] : '';
        foreach ($tabs as $slug => $value) {
            if ($slug == $active_tab) {
                $ret = $value['facet'];
            }
        }
        return $ret;
    }

    /*
     * Other search functions
     */

    private function get_last_meta_total() {
        $meta = $this->sdb_results("SHOW META");
        $total = 0;
        if (sizeof($meta)) {
            foreach ($meta as $value) {
                if ($value->Variable_name == 'total_found') {
                    $total = $value->Value;
                    break;
                }
            }
        }
        return $total;
    }

    public function wildcards_maybe_query($keyword, $wildcars = true, $mode = ' MAYBE ') {
        $keyword = trim($keyword);

        $match_query = $keyword;
        if ($wildcars) {
            $match_query = "($keyword)|($keyword*)";
        }

        if (strstr($keyword, " ")) {
            // Multi keywords
            $keyword_arr = explode(' ', $keyword);
            $match_query_arr = array();
            foreach ($keyword_arr as $value) {
                if ($wildcars) {
                    if (filter_var($value, FILTER_VALIDATE_INT) === false) {
                        $value = "(($value)|($value*))";
                    }
                }
                $match_query_arr[] = $value;
            }
            $match_query = implode($mode, $match_query_arr);
        }
        return $match_query;
    }

    public function get_search_settings() {
        if ($this->search_settings) {
            return $this->search_settings;
        }
        // Get search settings from options
        $settings = unserialize($this->get_option('critic_search_settings'));
        if ($settings && sizeof($settings)) {
            foreach ($this->default_search_settings as $key => $value) {
                if (!isset($settings[$key])) {
                    //replace empty settings to default
                    $settings[$key] = $value;
                }
            }
        } else {
            $settings = $this->default_search_settings;
        }
        $this->search_settings = $settings;
        return $settings;
    }

    public function update_search_settings($form) {

        $ss = $this->get_search_settings();
        foreach ($ss as $key => $value) {
            if (isset($form[$key])) {
                $new_value = $form[$key];
                $def_value = $this->get_settings_range($key);
                if ($new_value > $def_value['max']) {
                    $new_value = $def_value['max'];
                } else if ($new_value < $def_value['min']) {
                    $new_value = $def_value['min'];
                }

                $ss[$key] = $new_value;
            }
        }
        $this->search_settings = $ss;
        $this->update_option('critic_search_settings', serialize($ss));
    }

    public function get_actor_names($ids, $cache = true) {
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
        $sql = sprintf("SELECT actor_id, name FROM actor_all WHERE actor_id IN (%s) LIMIT 1000", implode(',', $ids));
        $result = $this->sdb_results($sql);
        $ret = array();
        if ($result) {
            foreach ($result as $actor) {
                $ret[$actor->actor_id] = $actor->name;
            }
        }

        $dict[$key] = $ret;

        return $ret;
    }

    public function critic_in_index($id) {
        $sql = sprintf("SELECT id FROM critic WHERE id = %d", $id);

        $result = $this->sdb_results($sql);
        $ret = false;
        if (isset($result[0]->id)) {
            $ret = true;
        }
        return $ret;
    }

    public function get_critic_last_upd($id) {
        $sql = sprintf("SELECT date_add FROM critic WHERE id = %d", $id);

        $result = $this->sdb_results($sql);
        $ret = 0;
        if (isset($result[0]->date_add)) {
            $ret = $result[0]->date_add;
        }
        return $ret;
    }

    /*
     * Keywords logic
     */

    public function get_keywords_titles($ids) {
        $limit = count($ids);
        $sql = "SELECT id, name FROM movie_keywords WHERE id IN(" . implode(',', $ids) . ") LIMIT 0," . $limit;
        $results = $this->sdb_results($sql);
        $ret = array();
        if ($results) {
            foreach ($results as $item) {
                $ret[$item->id] = $item->name;
            }
        }
        return $ret;
    }

    public function find_keywords_ids($keyword) {
        $search_keywords = $this->wildcards_maybe_query($keyword, true, ' ');

        $sql = sprintf("SELECT id, name FROM movie_keywords WHERE MATCH('((^%s)|(^%s*))') LIMIT 1000", $keyword, $keyword);
        $result = $this->sdb_results($sql);
        $results = array();
        if (sizeof($result)) {
            foreach ($result as $item) {
                $results[$item->id] = $item->name;
            }
        }
        return $results;
    }

    function timer_start() {
        global $timestart;
        $timestart = microtime(1);
    }

    function timer_stop($precision = 3) {
        global $timestart;
        $mtime = microtime(1);
        $timetotal = $mtime - $timestart;
        $r = number_format($timetotal, $precision);

        return $r;
    }

    /*
     * Search new posts ids from critic matic
     */

    public function get_search_ids() {
        $ids_str = $this->get_option('feed_matic_search_ids', '');
        $ids = array();
        if ($ids_str) {
            $ids = unserialize($ids_str);
        }
        return $ids;
    }

    private function update_search_ids($ids) {
        $ids_str = serialize($ids);
        $this->update_option('feed_matic_search_ids', $ids_str);
    }

    private function search_critic_posts_in_index($debug = false) {
        $ids = $this->get_search_ids();
        if (sizeof($ids)) {
            if ($debug) {
                print_r($ids);
            }
            $new_ids_to_search = array();
            foreach ($ids as $id) {
                if ($this->critic_in_index($id)) {
                    // Post in index. Search movies and update its meta
                    if ($debug) {
                        print 'Post in index: ' . $id . '<br />';
                    }
                    $this->find_movies_and_reset_meta($id, $debug);
                } else {
                    // Post not index. Add to next queue
                    if ($debug) {
                        print 'Post not index: ' . $id . "<br />\n";
                    }
                    $post = $this->cm->get_post($id);
                    if ($post->status == 1) {
                        $new_ids_to_search[] = $id;
                    }
                }
            }
            $this->update_search_ids($new_ids_to_search);
        }
    }

    public function find_movies_and_reset_meta($id, $debug = false) {
        $item = $this->cm->get_post($id);
        $movies_search = $this->search_movies($item->title, $item->content);
        if ($debug) {
            print_r($movies_search);
        }
        if (sizeof($movies_search['movies'])) {
            $ma = $this->get_ma();
            foreach ($movies_search['movies'] as $movie_type) {
                if (sizeof($movie_type)) {
                    foreach ($movie_type as $movie) {
                        // Reset movie meta date
                        $ma->reset_movie_meta_date($movie->id);
                    }
                }
            }
        }
    }

    public function get_last_critics($a_type = -1, $limit = 10, $movie_id = 0, $start = 0, $tags = array(), $meta_type = array(), $min_rating = 0, $vote = 0, $min_au = 0, $max_au = 0, $vote_type = 0) {

        $filters_and = '';

        if ($a_type != -1) {
            $filters_and .= sprintf(' AND author_type = %d', $a_type);
        }

        if ($movie_id > 0) {
            $filters_and .= $this->filter_multi_value('movies', $movie_id, true);
        }

        if ($min_rating) {
            // TODO min rating
        }

        if ($min_au) {
            $filters_and .= sprintf(" AND aurating>%d", $min_au);
        }

        if ($max_au) {
            $filters_and .= sprintf(" AND aurating<=%d", $max_au);
        }

        if ($meta_type) {
            // TODO meta type
        }

        // Odrer by rating desc
        $order = " ORDER BY post_date DESC";
        if ($movie_id > 0) {
            $order = " ORDER BY post_date DESC";
        }

        // Tag logic
        if ($tags) {
            $filters_and .= $this->filter_multi_value('tags', $tags, true);
        }

        // Vote logic
        if ($vote > 0) {
            $filters_and .= sprintf(" AND auvote=%d", $vote);
        }

        // Vote type:
        $and_select = '';
        if ($vote_type > 0) {
            if ($vote_type == 1) {
                /*
                  Positive
                  5 stars
                  4 stars
                  3 stars (pay to watch)
                 */
                $and_select = ", IF(aurating=4 OR aurating=5,1,IF(aurating=3 AND auvote=1,1,0)) AS filter ";
                $filters_and .= " AND filter=1";
            } if ($vote_type == 2) {
                /*
                  Negative
                  3 stars (watch if free)
                  3 stars (skip it)
                  2 stars
                  1 stars
                  0 stars
                 */
                $and_select = ", IF(aurating=0 OR aurating=1 OR aurating=2,1,IF(aurating=3 AND auvote!=1,1,0)) AS filter ";
                $filters_and .= " AND filter=1";
            }
        }

        $sql = sprintf("SELECT id, date_add, top_movie, author_name" . $and_select . " FROM critic WHERE status=1 AND top_movie>0" . $filters_and . $order . " LIMIT %d,%d", $start, $limit);        
        $results = $this->sdb_results($sql);
        // $meta = $this->sdb_results("SHOW META");
        

        return $results;
    }

    /*
     * Find povtor
     */

    function find_post_povtor($title = '', $pid = 0, $aid = 0, $debug = false) {

        $povtor = false;
        $length = 200;
        $min_precent = 90;

        $title = $this->clear_text($title, $length);

        $wordsArr = $this->getUniqueWords($title);
        if ($debug) {
            p_r(array($title, $wordsArr, $pid));
        }

        $povtors = $this->find_by_sphinx($title, $pid, $aid, $debug);

        $valid_povtors = array();
        if ($povtors) {
            foreach ($povtors as $key => $povtor) {
                $searchArr = $this->getUniqueWords(strip_tags($povtor->title));
                $precent = $this->get_min_percent($searchArr, $wordsArr);

                if ($debug) {
                    p_r(array('Title percent', $key, $precent));
                }
                if ($precent >= $min_precent) {

                    // Validate percent content
                    if ($precent != 100) {
                        // Get content
                        $post_cache = $this->cm->get_post_cache($pid);
                        $post_domain = $this->cm->get_domain_by_url($post_cache->link);
                        $post_cache2 = $this->cm->get_post_cache($key);
                        $post_domain2 = $this->cm->get_domain_by_url($post_cache2->link);
                        $precent_c = 0;
                        if ($debug) {
                            p_r(array('Domains', $post_domain, $post_domain2));
                        }
                        if ($post_domain != $post_domain2) {
                            $post_content = $this->getUniqueWords(strip_tags($post_cache->content));
                            $post_content2 = $this->getUniqueWords(strip_tags($post_cache2->content));
                            if ($post_content || $post_content2) {
                                $precent_c = $this->get_min_percent($post_content, $post_content2);
                            }
                            if ($debug) {
                                p_r(array('Content percent', $key, $precent_c));
                            }

                            if ($precent_c >= $min_precent) {
                                $povtor->percent = array($precent, $precent_c);
                                $valid_povtors[$key] = $povtor;
                            }
                        }
                    } else {
                        $povtor->percent = $precent;
                        $valid_povtors[$key] = $povtor;
                    }
                }
            }
        }

        if ($debug) {
            p_r($valid_povtors);
        }


        return $valid_povtors;
    }

    public function get_min_percent($first = array(), $sec = array()) {
        $precent_first = $this->compareResults($first, $sec);
        $precent_sec = $this->compareResults($sec, $first);
        $precent = $precent_first;
        if ($precent_sec < $precent_first) {
            $precent = $precent_sec;
        }
        $precent = round($precent, 2);
        return $precent;
    }

    public function clear_text($text = '', $length = 10, $filter = true) {

        if ($text) {
            $text = html_entity_decode($text);
            $text = preg_replace("/<[^>]*>/", ' ', $text);
            $text = strip_tags($text);
            if ($filter) {
                $text = preg_replace('/[^a-zA-Z0-9\']+/', ' ', $text);
                $text = preg_replace('/[ ]+/', ' ', $text);
                $text = str_replace("\t", '', $text);
                $text = str_replace("\n", '', $text);
            }

            if (strlen($text) > $length) {
                $pos = strpos($text, ' ', $length);
                if ($pos != null) {
                    $text = substr($text, 0, $pos);
                }
            }
        }
        return $text;
    }

    function compareResults($searchArr, $wordsArr) {

        $count = sizeof($wordsArr);
        $find = 0;
        foreach ($wordsArr as $word) {
            if (in_array($word, $searchArr)) {
                $find++;
            } else {
                // echo " <b>$word</b>, ";
            }
        }
        $precent = ($find > 0) ? 100 * $find / $count : 0;
        return $precent;
    }

    function find_by_sphinx($title, $pid, $aid, $debug = false) {

        $ret = '';
        $limit = 5;

        $t = $this->wildcards_maybe_query($title, false);

        $search_query = sprintf("'@(title) (%s)'", $t);
        $match = " AND MATCH(:match)";

        $snippet = ', SNIPPET(title, QUERY()) t, SNIPPET(content, QUERY()) c';


        $sql = sprintf("SELECT id, title, aid, weight() w" . $snippet . " FROM critic "
                . "WHERE author_type!=2 AND aid=%d AND id!=%d" . $match . " LIMIT %d", $aid, $pid, $limit);

        $this->connect();
        $result = $this->movie_results($sql, $match, $search_query);
        if ($debug) {
            p_r(array($sql, $search_query, $result));
        }


        $povtors = array();
        if ($result['count'] > 0) {
            foreach ($result['list'] as $item) {
                $povtor = new stdClass();
                $povtor->title = $item->t;
                $povtor->content = $item->c;
                $povtor->pid = $item->id;
                $povtors[$item->id] = $povtor;
            }
        }
        return $povtors;
    }

    function getUniqueWords($words) {
        if (preg_match_all("#([\p{L}0-9]+)#uis", $words, $matchesarray)) {
            $wordsArr = array_unique($matchesarray[0]);
            return $wordsArr;
        }
    }

    /* Newsfilter */

    public function find_in_newsfilter($post = '', $limit = 5, $debug = false) {
        $ma = $this->get_ma();
        $db_keywords = $ma->get_nf_keywords($post->id);
        if ($db_keywords) {
            $keywords = $db_keywords;
        } else {
            $keywords = $this->get_nf_keywords($post, $debug);
            $ma->add_nf_keywords($keywords, $post->id);
        }
        $search_query = sprintf("'@(title,content) %s'", $keywords);
        $match = " AND MATCH(:match)";
        $start = 0;


        $order = ' ORDER BY w DESC';
        $snippet = ', SNIPPET(title, QUERY()) t, SNIPPET(content, QUERY()) c';

        $sql = sprintf("SELECT id, cid, last_parsing as pdate, date, link, site, type, bias, biastag, nresult, description" . $snippet . ", weight() w"
                . " FROM sites_links WHERE type=0 " . $match . $order . " LIMIT %d,%d ", $start, $limit);

        $this->connect();
        $result = $this->movie_results($sql, $match, $search_query);
        if ($debug) {
            print_r(array($sql, $search_query, $result));
        }

        // Facets logic                
        $facet_list = array('bias', 'biasrating');
        $sql_arr = $this->nf_facets_sql($facet_list, $match);
        $facets_arr = $this->movies_facets_get($facet_list, $sql_arr, $match, $search_query);
        if ($debug) {
            print_r($facets_arr);
        }
        $result['facets'] = $facets_arr;

        return $result;
    }

    public function find_in_newsfilter_raw($keywords = '', $limit = 5, $debug = false) {


        //$search_query = sprintf("'@(title,content) %s'", $keywords);
        $search_query = sprintf("'@(title) \"%s\"'", $keywords);
        $match = " AND MATCH(:match)";
        $start = 0;


        $order = ' ORDER BY w DESC';
        //$snippet = ', SNIPPET(title, QUERY()) t, SNIPPET(content, QUERY()) c';
        $snippet = ', SNIPPET(title, QUERY()) t';

        $sql = sprintf("SELECT id" . $snippet . ", weight() w"
                . " FROM sites_links_raw WHERE id>0 " . $match . $order . " LIMIT %d,%d ", $start, $limit);

        $this->connect();
        $result = $this->movie_results($sql, $match, $search_query);
        if ($debug) {
            p_r(array($sql, $search_query, $result));
        }

        return $result;
    }

    public function nf_facets_sql($facet_list, $match) {
        $skip = array();
        $sql_arr = array();
        $limit = 100;
        $search_db = 'sites_links';

        foreach ($facet_list as $facet) {

            if ($facet == 'bias') {
                $sql_arr[] = "SELECT GROUPBY() as id, COUNT(*) as cnt FROM " . $search_db . " WHERE id>0" . $match
                        . " GROUP BY bias ORDER BY bias ASC LIMIT " . $limit;
                $sql_arr[] = "SHOW META";
            } else if ($facet == 'biasrating') {
                $sql_arr[] = "SELECT GROUPBY() as id, COUNT(*) as cnt, SUM(nresult) AS nresults"
                        . " FROM " . $search_db . " WHERE nresult>0 " . $match
                        . " GROUP BY bias ORDER BY bias ASC LIMIT " . $limit;
                $sql_arr[] = "SHOW META";
            }
        }
        return array('sql_arr' => $sql_arr, 'skip' => $skip);
    }

    public function get_nf_keywords($post, $debug) {
        /*
         * stdClass Object
          (
          [id] => 72749
          [movie_id] => 12530246
          [rwt_id] => 0
          [tmdb_id] => 715931
          [title] => Emancipation
          [post_name] => emancipation
          [type] => Movie
          [genre] => Action,Thriller
          [release] => 2022-12-09
          [year] => 2022
          [country] => United States
          [language] => English
          [production] => {"co0546168":"Apple TV+","co0719257":"CAA Media Finance","co0035535":"Escape Artists"}
          [actors] =>
          [producers] =>
          [director] =>
          [cast_director] =>
          [writer] =>
          [box_usa] =>
          [box_world] =>
          [productionBudget] => 120000000
          [keywords] => psychological thriller,killed,freedom
          [description] => A runaway slave forges through the swamps of Louisiana on a tortuous journey to escape plantation owners that nearly killed him.
          [data] => {"imdb_title":"Emancipation (2022)","image":"https:\/\/m.media-amazon.com\/images\/M\/MV5BN2RiY2RmMjItMDc1My00ZmViLWJkM2YtZjExNDI5MGM2ZWNiXkEyXkFqcGdeQXVyODk4OTc3MTY@._V1_.jpg","year":2022,"creator":{"Organization":"546168,719257,35535,","Person":"171651,"}}
          [contentrating] => R
          [rating] => 5.4
          [add_time] => 1670774410
          [runtime] => 7920
          [weight] => 0
          [weight_upd] => 0
          )
         */
        if ($debug) {
            print_r($post);
        }
        $keywords = '';

        $title = '"' . $this->filter_text($post->title) . '"';
        //$title = $this->filter_text($post->title);

        $keywords = $title;

        $filelds = array('review');
        // Year
        $year = (int) $post->year;
        if ($year) {
            $filelds[$year] = $year;
        }

        $ma = $this->get_ma();

        // Search Director
        $directors = $ma->get_directors($post->id);
        if ($directors) {
            $max_directors = 3;
            foreach ($directors as $director) {
                $name = $director->name;
                $i = 0;
                if ($name) {
                    if ($i > $max_directors) {
                        break;
                    }
                    $filelds[$name] = $this->filter_text($name);
                    $i += 1;
                }
            }
        }


        // Actors
        $actors = $ma->get_actors($post->id);

        if ($actors) {
            $max_actors = 3;
            foreach ($actors as $actor) {
                $name = $actor->name;
                $i = 0;
                if ($name) {
                    if ($i > $max_actors) {
                        break;
                    }
                    $filelds[$name] = $this->filter_text($name);
                    $i += 1;
                }
            }
        }


        $production = array();
        if ($post->production) {
            $p_obj = json_decode($post->production);
            if ($p_obj) {
                $i = 0;
                $max_prod = 3;
                foreach ($p_obj as $p) {
                    if ($i > $max_prod) {
                        break;
                    }
                    $filelds[$p] = '"' . $this->filter_text($p) . '"';
                    $i += 1;
                }
            }
        }

        if ($filelds) {
            $keywords .= ' MAYBE (' . implode('|', $filelds) . ')';
        }

        return $keywords;
    }

    public function filter_text($text = '') {
        $text = strip_tags($text);
        $text = preg_replace('/[^a-zA-Z0-9\']+/', ' ', $text);
        $text = trim(preg_replace('/  /', ' ', $text));
        return $text;
    }

    /*
     * Log
     * message - string
     * cid - critic id
     * mid - movie id
     * type:
      0 => 'Info',
      1 => 'Warning',
      2 => 'Error'

      status:
      0 => 'Add meta',
      1 => 'Update meta',
      2 => 'Remove meta',
      3 => 'Trash dublicate post',
      4 => 'Ignore dublicate post',

     */

    public function get_log_type($type) {
        return isset($this->log_type[$type]) ? $this->log_type[$type] : 'None';
    }

    public function get_log_status($type) {
        return isset($this->log_status[$type]) ? $this->log_status[$type] : 'None';
    }

    public function log_add_meta($message, $cid, $mid) {
        $this->log($message, $cid, $mid, 0, 0);
    }

    public function log_update_meta($message, $cid, $mid) {
        $this->log($message, $cid, $mid, 0, 1);
    }

    public function log_remove_meta($message, $cid, $mid) {
        $this->log($message, $cid, $mid, 0, 2);
    }

    public function log_trash_dublicate($message, $cid, $mid = 0) {
        $this->log($message, $cid, $mid, 0, 3);
    }

    public function log_ignore_dublicate($message, $cid, $mid = 0) {
        $this->log($message, $cid, $mid, 0, 4);
    }

    public function log($message, $cid = 0, $mid = 0, $type = 0, $status = 0) {
        $this->get_wpdb();
        $time = $this->curr_time();
        $this->wpdb->db_query(sprintf("INSERT INTO {$this->db['log']} (date, cid, mid, type, status, message) VALUES (%d, %d, %d, %d, %d, '%s')", $time, $cid, $mid, $type, $status, $this->escape($message)));
    }

    /*
     * Post meta log
     */

    public function get_log_count($status = -1, $type = -1) {
        $this->get_wpdb();

        $and_status = '';
        if ($status != -1) {
            $and_status = sprintf(" AND status=%d", (int) $status);
        }

        $and_type = '';
        if ($type != -1) {
            $and_type = sprintf(" AND type=%d", (int) $type);
        }

        $query = "SELECT COUNT(id) FROM {$this->db['log']} WHERE id>0" . $and_status . $and_type;
        $result = $this->wpdb->db_get_var($query);
        return $result;
    }

    public function get_log($page = 1, $mid = 0, $cid = 0, $count = 0, $status = -1, $type = -1) {
        $this->get_wpdb();
        $page -= 1;
        $start = $page * $this->perpage;

        $limit = '';
        if ($this->perpage > 0) {
            $limit = " LIMIT $start, " . $this->perpage;
        }
        if ($count > 0) {
            $limit = " LIMIT $start, " . $count;
        }


        $mid_and = '';
        if ($mid > 0) {
            $mid_and = sprintf(' AND mid=%d', $mid);
        }

        $cid_and = '';
        if ($cid > 0) {
            $cid_and = sprintf(' AND cid=%d', $cid);
        }

        $and_status = '';
        if ($status != -1) {
            $and_status = sprintf(" AND status=%d", (int) $status);
        }

        $and_type = '';
        if ($type != -1) {
            $and_type = sprintf(" AND type=%d", (int) $type);
        }

        $order = " ORDER BY id DESC";
        $sql = sprintf("SELECT id, date, cid, mid, type, status, message FROM {$this->db['log']} WHERE id>0" . $and_status . $and_type . $mid_and . $cid_and . $order . $limit);

        $result = $this->wpdb->db_results($sql);

        return $result;
    }

    public function get_count_log_status() {

        $count = $this->get_log_count();
        $states = array(
            '-1' => array(
                'title' => 'All',
                'count' => $count
            )
        );
        foreach ($this->log_status as $key => $value) {
            $states[$key] = array(
                'title' => $value,
                'count' => $this->get_log_count($key));
        }
        return $states;
    }

    public function get_count_log_type($status = -1) {
        $count = $this->get_log_count($status);
        $states = array(
            '-1' => array(
                'title' => 'All',
                'count' => $count
            )
        );
        foreach ($this->log_type as $key => $value) {
            $states[$key] = array(
                'title' => $value,
                'count' => $this->get_log_count($status, $key));
        }
        return $states;
    }

    public function get_settings_range($param) {
        $procent = $this->search_setting_valid_pocent;
        $min_setting = $def_setting = $max_setting = 0;

        if (isset($this->default_search_settings[$param])) {
            $def_setting = $this->default_search_settings[$param];

            $min_setting = $def_setting - ($def_setting * $procent) / 100;
            $max_setting = $def_setting + ($def_setting * $procent) / 100;

            if ($def_setting > 5) {
                $min_setting = (int) $min_setting;
                $max_setting = (int) $max_setting;
            }
        }
        return array('min' => $min_setting, 'def' => $def_setting, 'max' => $max_setting);
    }

    private function get_perpage() {
        $this->perpage = isset($_GET['perpage']) ? (int) $_GET['perpage'] : $this->perpage;
        return $this->perpage;
    }

    public function get_movie_names($ids) {
        $sql = sprintf("SELECT id, title, year_int as year FROM movie_an WHERE id IN (%s) LIMIT 1000", implode(',', $ids));
        $result = $this->sdb_results($sql);
        $ret = array();
        if ($result) {
            foreach ($result as $m) {
                $ret[$m->id] = $m->title . ' (' . $m->year . ')';
            }
        }
        return $ret;
    }

    public function get_movie_by_id($id = 0) {
        $sql = sprintf("SELECT * FROM movie_an WHERE id=%d LIMIT 1", (int) $id);
        $result = $this->sdb_results($sql);
        $result = array_pop($result);
        return $result;
    }

    //Abstract DB
    public function sdb_query($sql) {
        $this->connect();
        $this->sps->query($sql);
    }

    public function sdb_results($sql, $array = []) {
        $this->connect();
        $sth = $this->sps->prepare($sql);
        $sth->execute($array);
        $data = $sth->fetchAll(PDO::FETCH_OBJ);
        return $data;
    }

    public function sdb_multi_results($sql, $array = []) {
        $this->connect();
        $sth = $this->sps->prepare($sql);
        $sth->execute($array);
        do {
            $data[] = $sth->fetchAll(PDO::FETCH_OBJ);
        } while ($sth->nextRowset());
        return $data;
    }

}
