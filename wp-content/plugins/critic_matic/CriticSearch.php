<?php

/**
 * Find critic posts by sphinxsearch
 *
 * @author brahman
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
    public $sps;
    private $wpdb;
    private $search_settings = '';
    private $search_prc = array(
        'def' => 10,
        'limit' => 200,
    );
    public $facet_limit = 10;
    public $facet_max_limit = 200;
    public $facet_weight_def = 100;
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
    public $sort_default = array(
        'date' => array('title' => 'Date', 'def' => 'desc', 'main' => 1, 'tabs' => array('movies', 'critics', 'games', 'international', 'ethnicity', 'filters'),),
        'rel' => array('title' => 'Relevance', 'def' => 'desc', 'main' => 1, 'tabs' => array('movies', 'critics', 'games', 'international', 'ethnicity', 'filters'),),
    );
    public $filters = array(
        'p' => '',
        'tab' => '',
        'sort' => '',
        'expand' => '',
        'show' => '',
        'hide' => '',
        //'director' => '',
        // TODO refactor
        'price' => '',
        'current' => '',
    );
    public $hide_facets = array();
    public $facets = array();
    public $facet_titles = array();
    public $facet_parents = array();
    public $facet_all_parents = array();
    public $facets_data = array();
    public $search_sort = array();
    public $facet_parent = array();
    public $facet_tabs = array();
    public $budget_min = 100;
    public $budget_max = 500000;
    public $facet_data = array(
        'release' => array(
            'title' => 'Release Date',
            'tabs' => array('movies', 'critics', 'games', 'international', 'ethnicity'),
            'weight' => 10,
        ),
        'type' => array(
            'title' => 'Types',
            'tabs' => array('movies', 'critics', 'international', 'ethnicity'),
            'weight' => 20,
        ),
        'genre' => array(
            'title' => 'Genres',
            'tabs' => array('movies', 'critics', 'games', 'international', 'ethnicity'),
            'minus' => 1,
            'andor' => 'or',
            'weight' => 30,
        ),
        'platform' => array(
            'title' => 'Platform',
            'tabs' => array('games', 'international', 'ethnicity'),
            'minus' => 1,
            'weight' => 30,
        ),
        'provider' => array(
            'title' => 'Provider',
            'tabs' => array('movies', 'international', 'ethnicity'),
            'weight' => 40,
        ),
        'providerfree' => array(
            'title' => 'Watch free',
            'tabs' => array('movies', 'international', 'ethnicity'),
            'weight' => 40,
        ),
        'ratings' => array(
            'title' => 'Ratings',
            'tabs' => array('movies', 'games', 'international', 'ethnicity'),
            'is_parent' => 1,
            'hide' => 1,
            'weight' => 60,
            'childs' => array(
                'ratingtitle' => array('title' => 'Ratings <span data-value="tooltip_zr_agregate_rating" class="nte_info"></span>', 'is_title' => 1, 'group' => 'rating'),
                'rrwt' => array('title' => 'ZR Aggregate Rating', 'facet' => 'rating', 'eid' => 'erwt', 'titlesm' => 'ZR Rating', 'max_count' => 100, 'multipler' => 10, 'group' => 'rating', 'sorted' => 1, 'sort_second' => 'crwt', 'sort_second_title' => 'votes', 'icon' => 'zr', 'tabs' => array('movies', 'games', 'international', 'ethnicity'), 'hide' => 1, 'minus' => 1, 'zero' => 1, 'max' => 1),
                'rimdb' => array('title' => 'IMDb', 'facet' => 'rating', 'eid' => 'eimdb', 'titlesm' => 'IMDb', 'max_count' => 100, 'multipler' => 10, 'group' => 'rating', 'icon' => 'imdb', 'sorted' => 1, 'sort_second' => 'cimdb', 'sort_second_title' => 'votes', 'tabs' => array('movies', 'games', 'international', 'ethnicity'), 'hide' => 1, 'minus' => 1, 'zero' => 1, 'max' => 1),
                'ropencritic' => array('title' => 'OpenCritic', 'facet' => 'rating', 'eid' => 'eopencritic', 'titlesm' => 'OpenCritic', 'max_count' => 100, 'multipler' => 1, 'group' => 'rating', 'icon' => 'opencritic', 'sorted' => 1, 'sort_second' => 'copencritic', 'sort_second_title' => 'votes', 'tabs' => array('games', 'international', 'ethnicity'), 'hide' => 1, 'minus' => 1, 'zero' => 1, 'max' => 1),
                'rmc' => array('title' => 'Metacritic MetaScore', 'facet' => 'rating', 'eid' => 'emc', 'titlesm' => 'MetaScore', 'max_count' => 100, 'multipler' => 1, 'group' => 'rating', 'icon' => 'mtcr', 'sorted' => 1, 'tabs' => array('movies', 'games', 'international', 'ethnicity'), 'hide' => 1, 'minus' => 1, 'zero' => 1, 'max' => 1),
                'rmu' => array('title' => 'Metacritic UserScore', 'facet' => 'rating', 'eid' => 'emu', 'titlesm' => 'UserScore', 'max_count' => 100, 'multipler' => 10, 'group' => 'rating', 'icon' => 'mtcr', 'sorted' => 1, 'tabs' => array('movies', 'games', 'international', 'ethnicity'), 'hide' => 1, 'minus' => 1, 'zero' => 1, 'max' => 1),
                'rmg' => array('title' => 'Metacritic % Gap', 'facet' => 'rating', 'eid' => 'emg', 'titlesm' => 'Metacritic % Gap', 'max_count' => 200, 'shift' => -100, 'multipler' => 1, 'group' => 'rating', 'icon' => 'mtcr', 'sorted' => 1, 'tabs' => array('movies', 'games', 'international', 'ethnicity'), 'hide' => 1, 'minus' => 1, 'zero' => 1, 'max' => 1),
                'ranl' => array('title' => 'MyAnimeList', 'facet' => 'rating', 'eid' => 'eanl', 'titlesm' => 'MyAnimeList', 'max_count' => 100, 'multipler' => 10, 'group' => 'rating', 'icon' => 'mal', 'sorted' => 1, 'sort_second' => 'canl', 'sort_second_title' => 'votes', 'tabs' => array('movies', 'international', 'ethnicity'), 'hide' => 1, 'minus' => 1, 'zero' => 1, 'max' => 1),
                'rrt' => array('title' => 'Rotten Tomatoes', 'facet' => 'rating', 'eid' => 'ert', 'titlesm' => 'Rotten Tomatoes', 'max_count' => 100, 'group' => 'rating', 'icon' => 'rt', 'sorted' => 1, 'sort_second' => 'crt', 'sort_second_title' => 'votes', 'tabs' => array('movies', 'international', 'ethnicity'), 'hide' => 1, 'minus' => 1, 'zero' => 1, 'max' => 1),
                'rrta' => array('title' => 'Rotten Tomatoes Audience', 'facet' => 'rating', 'eid' => 'erta', 'titlesm' => 'Rotten Tomatoes Audience', 'max_count' => 100, 'group' => 'rating', 'icon' => 'rt', 'sorted' => 1, 'sort_second' => 'crta', 'sort_second_title' => 'votes', 'tabs' => array('movies', 'international', 'ethnicity'), 'hide' => 1, 'minus' => 1, 'zero' => 1, 'max' => 1),
                'rrtg' => array('title' => 'Rotten Tomatoes % Gap', 'facet' => 'rating', 'eid' => 'ertg', 'titlesm' => 'Rotten Tomatoes % Gap', 'max_count' => 200, 'shift' => -100, 'group' => 'rating', 'icon' => 'rt', 'sorted' => 1, 'tabs' => array('movies', 'international', 'ethnicity'), 'hide' => 1, 'minus' => 1, 'zero' => 1, 'max' => 1),
                //// 'rfn' => array('title' => '4chan', 'facet' => 'rating', 'eid' => 'efn',  'titlesm' => '4chan',  'max_count' => 100, 'multipler' => 10, 'group' => 'rating', 'icon' => 'fchan', 'sorted' => 1, 'minus' => 1,'zero'=>1,'max'=>1),
                // 'rau' => array('title' => 'ZR User Score', 'titlesm' => 'ZR User Score',  'icon' => 'zr', 'group' => 'rating', 'hide' => 1, 'sorted' => 1, 'minus' => 1,'zero'=>1,),
                'aurating' => array('title' => 'ZR User Score', 'facet' => 'rating', 'eid' => 'eaurating', 'titlesm' => 'ZR User Score', 'max_count' => 5, 'multipler' => 1, 'icon' => 'zr', 'group' => 'rating', 'sorted' => 1, 'minus' => 1, 'hide' => 1, 'minus' => 1, 'zero' => 1, 'max' => 1, 'tabs' => array('movies', 'games', 'critics', 'international', 'ethnicity'),),
                'emotions' => array('title' => 'Emotions', 'facet' => 'rating', 'titlesm' => 'Emotions', 'max_count' => 100, 'multipler' => 1, 'group' => 'rating', 'sorted' => 1, 'minus' => 1, 'hide' => 1, 'minus' => 1, 'zero' => 1, 'max' => 1, 'tabs' => array('critics'),),
                'rrev' => array('title' => 'ZR Critics (beta)', 'facet' => 'rating', 'eid' => 'erev', 'titlesm' => 'ZR Critics', 'max_count' => 100, 'multipler' => 10, 'group' => 'rating', 'icon' => 'zr', 'sorted' => 1, 'sort_second' => 'pop', 'sort_second_title' => 'votes', 'tabs' => array('movies', 'games', 'international', 'ethnicity'), 'hide' => 1, 'minus' => 1, 'zero' => 1, 'max' => 1,),
                // Global
                'gratingtitle' => array('title' => 'Global Ratings', 'is_title' => 1, 'group' => 'rating', 'tabs' => array('movies', 'international', 'ethnicity'), 'hide' => 1,),
                'rdb' => array('title' => 'Douban', 'facet' => 'rating', 'eid' => 'edb', 'titlesm' => 'Douban', 'max_count' => 100, 'multipler' => 10, 'group' => 'rating', 'icon' => 'douban', 'sorted' => 1, 'sort_second' => 'cdb', 'sort_second_title' => 'votes', 'tabs' => array('movies', 'international', 'ethnicity'), 'hide' => 1, 'minus' => 1, 'zero' => 1, 'max' => 1,),
                'reiga' => array('title' => 'Eiga', 'facet' => 'rating', 'eid' => 'eeiga', 'titlesm' => 'Eiga', 'max_count' => 50, 'multipler' => 10, 'group' => 'rating', 'icon' => 'jp', 'sorted' => 1, 'sort_second' => 'ceiga', 'sort_second_title' => 'votes', 'tabs' => array('movies', 'international', 'ethnicity'), 'hide' => 1, 'minus' => 1, 'zero' => 1, 'max' => 1,),
                'rkp' => array('title' => 'Kinopoisk', 'facet' => 'rating', 'eid' => 'ekp', 'titlesm' => 'Kinopoisk', 'max_count' => 100, 'multipler' => 10, 'group' => 'rating', 'icon' => 'kinop', 'sorted' => 1, 'sort_second' => 'ckp', 'sort_second_title' => 'votes', 'tabs' => array('movies', 'international', 'ethnicity'), 'hide' => 1, 'minus' => 1, 'zero' => 1, 'max' => 1,),
                'rmm' => array('title' => 'MovieMeter', 'facet' => 'rating', 'eid' => 'emm', 'titlesm' => 'MovieMeter', 'max_count' => 50, 'multipler' => 10, 'group' => 'rating', 'icon' => 'nl', 'sorted' => 1, 'sort_second' => 'cmm', 'sort_second_title' => 'votes', 'tabs' => array('movies', 'international', 'ethnicity'), 'hide' => 1, 'minus' => 1, 'zero' => 1, 'max' => 1,),
                'rofdb' => array('title' => 'OFDb', 'facet' => 'rating', 'eid' => 'eofdb', 'titlesm' => 'OFDb', 'max_count' => 100, 'multipler' => 10, 'group' => 'rating', 'icon' => 'de', 'sorted' => 1, 'sort_second' => 'cofdb', 'sort_second_title' => 'votes', 'tabs' => array('movies', 'international', 'ethnicity'), 'hide' => 1, 'minus' => 1, 'zero' => 1, 'max' => 1,),
            ),
        ),
        'actorsdata' => array(
            'title' => 'Cast',
            'tabs' => array('movies', 'games', 'international', 'ethnicity'),
            'is_parent' => 1,
            'hide' => 1,
            'weight' => 80,
            'childs' => array(
                'cast' => array(
                    // Tabs
                    'title' => 'Cast',
                    'type' => 'tabs',
                    'def-tab' => 'star',
                    'childs' => array(
                        'star' => array(
                            'type' => 'tab',
                            'title' => 'Stars',
                            'childs' => array(
                                'starrace' => array(
                                    'filter' => 'actorstar', 'parent' => 'race', 'name' => 'actor_star', 'title' => 'Race (Stars)', 'title_tag' => 'Stars', 'filter_key' => 'race', 'minus' => 1,
                                    'childs' => array('star' => array('type' => 'generate', 'parent' => 'race',),),
                                ),
                                'stargender' => array(
                                    'parent' => 'gender', 'title' => 'Gender (Stars)', 'title_tag' => 'Stars', 'filter_key' => 'gender', 'minus' => 1,
                                    'childs' => array('star' => array('type' => 'generate', 'parent' => 'gender',),),
                                ),
                                'sphoto' => array('parent' => 'castphoto', 'title' => 'Star conditions', 'filter_key' => 'sphoto', 'minus' => 1, 'hide' => 1),
                                'actorstar' => array('filter' => 'actor_star', 'parent' => 'actors', 'title' => 'Actor Star', 'placeholder' => 'star', 'minus' => 1,),
                                'simstar' => array('parent' => 'simpson', 'title' => 'Literal Diversity (Stars)', 'facet' => 'rating', 'titlesm' => 'LD (Stars)', 'multipler' => 100, 'max_count' => 100, 'group' => 'woke', 'minus' => 1, 'sort_w' => 21,),
                                'countrystar' => array('filter' => 'countrystar', 'parent' => 'actorscountry', 'title' => 'Actor Surname (Star)', 'title_tag' => 'Star Surname', 'placeholder' => 'star', 'minus' => 1,),
                            ),
                        ),
                        'main' => array(
                            'type' => 'tab',
                            'title' => 'Supporting',
                            'childs' => array(
                                'mainrace' => array(
                                    'filter' => 'actormain', 'parent' => 'race', 'name' => 'actor_main', 'title' => 'Race (Supporting)', 'title_tag' => 'Supporting', 'filter_key' => 'race', 'minus' => 1,
                                    'childs' => array('main' => array('type' => 'generate', 'parent' => 'race',),),
                                ),
                                'maingender' => array(
                                    'parent' => 'gender', 'title' => 'Gender (Supporting)', 'title_tag' => 'Supporting', 'filter_key' => 'gender', 'minus' => 1,
                                    'childs' => array('main' => array('type' => 'generate', 'parent' => 'gender',),),
                                ),
                                'mphoto' => array('parent' => 'castphoto', 'title' => 'Supporting conditions', 'filter_key' => 'mphoto', 'minus' => 1, 'hide' => 1),
                                'actormain' => array('filter' => 'actor_main', 'parent' => 'actors', 'title' => 'Actor Supporting', 'placeholder' => 'main', 'minus' => 1,),
                                'simmain' => array('parent' => 'simpson', 'title' => 'Literal Diversity (Supporting)', 'facet' => 'rating', 'titlesm' => 'LD (Supporting)', 'multipler' => 100, 'max_count' => 100, 'group' => 'woke', 'minus' => 1, 'sort_w' => 21,),
                                'countrymain' => array('filter' => 'countrymain', 'parent' => 'actorscountry', 'title' => 'Actor Surname (Supporting)', 'title_tag' => 'Supporting Surname', 'placeholder' => 'main', 'minus' => 1,),
                            ),
                        ),
                        'all' => array(
                            'type' => 'tab',
                            'title' => 'All Cast',
                            'childs' => array(
                                'race' => array(
                                    'filter' => 'actor', 'parent' => 'race', 'name' => 'actor_all', 'title' => 'Race (All Cast)', 'title_tag' => 'All Cast', 'filter_key' => 'race', 'minus' => 1,
                                    'childs' => array('all' => array('type' => 'generate', 'parent' => 'race',),),
                                ),
                                'gender' => array(
                                    'parent' => 'gender', 'title' => 'Gender (All Cast)', 'title_tag' => 'All Cast', 'filter_key' => 'gender', 'minus' => 1,
                                    'childs' => array('all' => array('type' => 'generate', 'parent' => 'gender',),),
                                ),
                                'aphoto' => array('parent' => 'castphoto', 'title' => 'Actors conditions', 'filter_key' => 'aphoto', 'minus' => 1, 'hide' => 1),
                                'actor' => array('filter' => 'actor_all', 'parent' => 'actors', 'title' => 'Actor', 'placeholder' => '', 'minus' => 1,),
                                'simall' => array('parent' => 'simpson', 'title' => 'Literal Diversity', 'facet' => 'rating', 'titlesm' => 'LD', 'multipler' => 100, 'max_count' => 100, 'group' => 'woke', 'sorted' => 1, 'minus' => 1, 'sort_w' => 21,),
                                'countryall' => array('filter' => 'countryall', 'parent' => 'actorscountry', 'title' => 'Actor Surname', 'title_tag' => 'Cast Surname', 'placeholder' => '', 'minus' => 1,),
                            ),
                        ),
                    ),
                ),
            ),
            'race_gender' => array(
                'race' => 'gender',
                'starrace' => 'stargender',
                'mainrace' => 'maingender',
            ),
            'race_country' => array(
                'race' => 'countryall',
                'starrace' => 'countrystar',
                'mainrace' => 'countrymain',
            ),
            'race_simpson' => array(
                'race' => 'simall',
                'starrace' => 'simstar',
                'mainrace' => 'simmain',
            ),
            'race_simpson_mf' => array(
                'race' => 'simmfall',
                'starrace' => 'simmfstar',
                'mainrace' => 'simmfmain',
            ),
        ),
        'dirsdata' => array(
            'title' => 'Crew',
            'tabs' => array('movies', 'games', 'international', 'ethnicity'),
            'is_parent' => 1,
            'hide' => 1,
            'weight' => 90,
            'childs' => array(
                'director' => array(
                    // Tabs
                    'title' => 'Directors',
                    'type' => 'tabs',
                    'def-tab' => 'alldir',
                    'childs' => array(
                        'alldir' => array(
                            'type' => 'tab',
                            'title' => 'All Crew',
                            'childs' => array(
                                'dirrace' => array(
                                    'filter' => 'dirall', 'parent' => 'race', 'name' => 'director_all', 'title' => 'Race (All Crew)', 'title_tag' => 'All Crew', 'filter_key' => 'race', 'minus' => 1,
                                    'childs' => array('alldir' => array('type' => 'generate', 'parent' => 'race',),),
                                ),
                                'dirgender' => array('parent' => 'gender', 'title' => 'Gender (All Crew)', 'title_tag' => 'All Crew', 'filter_key' => 'gender', 'minus' => 1,
                                    'childs' => array('alldir' => array('type' => 'generate', 'parent' => 'gender',),),
                                ),
                                'dirall' => array('filter' => 'director_all', 'parent' => 'dirs', 'title' => 'Production', 'placeholder' => 'all', 'minus' => 1,),
                            ),
                        ),
                        'directors' => array(
                            'type' => 'tab',
                            'title' => 'Directors',
                            'childs' => array(
                                'dirsrace' => array('filter' => 'dir', 'parent' => 'race', 'name' => 'director_dir', 'title' => 'Race (Directors)', 'title_tag' => 'Directors', 'filter_key' => 'race', 'minus' => 1,
                                    'childs' => array('directors' => array('type' => 'generate', 'parent' => 'race',),),
                                ),
                                'dirsgender' => array('parent' => 'gender', 'title' => 'Gender (Directors)', 'title_tag' => 'Directors', 'filter_key' => 'gender', 'minus' => 1,
                                    'childs' => array('directors' => array('type' => 'generate', 'parent' => 'gender',),),
                                ),
                                'dir' => array('filter' => 'director_dir', 'parent' => 'dirs', 'title' => 'Director', 'placeholder' => 'director', 'minus' => 1,),
                            ),
                        ),
                        'writers' => array(
                            'type' => 'tab',
                            'title' => 'Writers',
                            'childs' => array(
                                'writersrace' => array('filter' => 'dirwrite', 'parent' => 'race', 'name' => 'director_write', 'title' => 'Race (Writers)', 'title_tag' => 'Writers', 'filter_key' => 'race', 'minus' => 1,
                                    'childs' => array('writers' => array('type' => 'generate', 'parent' => 'race',),),
                                ),
                                'writergender' => array('parent' => 'gender', 'title' => 'Gender (Writers)', 'title_tag' => 'Writers', 'filter_key' => 'gender', 'minus' => 1,
                                    'childs' => array('writers' => array('type' => 'generate', 'parent' => 'gender',),),
                                ),
                                'dirwrite' => array('filter' => 'director_write', 'parent' => 'dirs', 'title' => 'Writer', 'placeholder' => 'writer', 'minus' => 1,),
                            ),
                        ),
                        'cast-directors' => array(
                            'type' => 'tab',
                            'title' => 'Casting Directors',
                            'childs' => array(
                                'castdirrace' => array('filter' => 'dircast', 'parent' => 'race', 'name' => 'director_cast', 'title' => 'Race (Casting Directors)', 'title_tag' => 'Casting Directors', 'filter_key' => 'race', 'minus' => 1,
                                    'childs' => array('cast-directors' => array('type' => 'generate', 'parent' => 'race',),),
                                ),
                                'castgender' => array('parent' => 'gender', 'title' => 'Gender (Casting Directors)', 'title_tag' => 'Casting Directors', 'filter_key' => 'gender', 'minus' => 1,
                                    'childs' => array('cast-directors' => array('type' => 'generate', 'parent' => 'gender',),),
                                ),
                                'dircast' => array('filter' => 'director_cast', 'parent' => 'dirs', 'title' => 'Casting director', 'placeholder' => 'casting', 'minus' => 1,),
                            ),
                        ),
                        'producers' => array(
                            'type' => 'tab',
                            'title' => 'Producers',
                            'childs' => array(
                                'producerrace' => array('filter' => 'dirprod', 'parent' => 'race', 'name' => 'director_prod', 'title' => 'Race (Producers)', 'title_tag' => 'Producers', 'filter_key' => 'race', 'minus' => 1,
                                    'childs' => array('producers' => array('type' => 'generate', 'parent' => 'race',),),
                                ),
                                'producergender' => array('parent' => 'gender', 'title' => 'Gender (Producers)', 'title_tag' => 'Producers', 'filter_key' => 'gender', 'minus' => 1,
                                    'childs' => array('producers' => array('type' => 'generate', 'parent' => 'gender',),),
                                ),
                                'dirprod' => array('filter' => 'director_prod', 'parent' => 'dirs', 'title' => 'Producer', 'placeholder' => 'producer', 'minus' => 1,),
                            ),
                        ),
                    ),
                ),
            ),
            'race_gender_dir' => array(
                'dirrace' => 'dirgender',
                'dirsrace' => 'dirsgender',
                'writersrace' => 'writergender',
                'castdirrace' => 'castgender',
                'producerrace' => 'producergender',
            ),
        ),
        'indiedata' => array(
            'title' => 'indie',
            'tabs' => array('movies', 'games', 'international', 'ethnicity'),
            'is_parent' => 1,
            'hide' => 1,
            'weight' => 70,
            'childs' => array(
                'indie' => array(
                    'title' => 'indie',
                    'minus' => 1,
                ),
                'isfranchise' => array(
                    'title' => 'isfranchise',
                ),
                'reboot' => array(
                    'title' => 'reboot',
                ),
                'remake' => array(
                    'title' => 'remake',
                ),
                'sequel' => array(
                    'title' => 'sequel',
                ),
                'prequel' => array(
                    'title' => 'prequel',
                ),
                'bigdist' => array(
                    'title' => 'bigdist',
                ),
                'meddist' => array(
                    'title' => 'meddist',
                ),
                'indidist' => array(
                    'title' => 'indidist',
                ),
                'franchise' => array(
                    'title' => 'Franchise',
                    'minus' => 1,
                    'hide' => 1,
                ),
                'production' => array(
                    'title' => 'Production',
                    'minus' => 1,
                    'hide' => 1,
                ),
                'distributor' => array(
                    'title' => 'Distributor',
                    'minus' => 1,
                    'hide' => 1,
                ),
            ),
        ),
        'findata' => array(
            'title' => 'Finances',
            'tabs' => array('movies', 'games', 'international', 'ethnicity'),
            'is_parent' => 1,
            'hide' => 1,
            'weight' => 100,
            'childs' => array(
                'budget' => array('title' => 'Budget', 'facet' => 'rating', 'eid' => 'ebudget', 'titlesm' => 'Budget', 'group' => 'indie', 'sorted' => 1,),
                'boxprofit' => array('title' => 'Profit', 'facet' => 'rating', 'eid' => 'eboxworld', 'titlesm' => 'Profit', 'group' => 'indie', 'hide' => 1, 'sorted' => 1,),
                'boxworld' => array('title' => 'Worldwide Box Office', 'facet' => 'rating', 'eid' => 'eboxworld', 'titlesm' => 'Worldwide Box Office', 'group' => 'indie', 'hide' => 1, 'sorted' => 1,),
                'boxint' => array('title' => 'International Box Office', 'facet' => 'rating', 'eid' => 'eboxint', 'titlesm' => 'International Box Office', 'group' => 'indie', 'hide' => 1, 'sorted' => 1,),
                'boxusa' => array('title' => 'Domestic Box Office', 'facet' => 'rating', 'eid' => 'eboxusa', 'titlesm' => 'Domestic Box Office', 'group' => 'indie', 'hide' => 1, 'sorted' => 1,),
            ),
        ),
        'popdata' => array(
            'title' => 'Popularity',
            'tabs' => array('movies', 'games', 'international', 'ethnicity'),
            'is_parent' => 1,
            'weight' => 110,
            'no_data' => 1,
            'childs' => array(
                'poptitle' => array('title' => 'Most Talked About', 'is_title' => 1, 'group' => 'pop', 'no_data' => 1,),
                'crwt' => array('title' => 'Popularity Total', 'eid' => 'erwt', 'titlesm' => 'Popularity', 'group' => 'pop', 'icon' => 'zr', 'sorted' => 1, 'no_data' => 1,),
                'cimdb' => array('title' => 'IMDb', 'eid' => 'eimdb', 'titlesm' => 'IMDb', 'group' => 'pop', 'icon' => 'imdb', 'sorted' => 1, 'no_data' => 1,),
                'copencritic' => array('title' => 'OpenCritic', 'eid' => 'eopencritic', 'titlesm' => 'OpenCritic', 'group' => 'pop', 'icon' => 'opencritic', 'sorted' => 1, 'tabs' => array('games', 'international', 'ethnicity'), 'no_data' => 1,),
                'canl' => array('title' => 'MyAnimeList', 'eid' => 'eanl', 'titlesm' => 'MyAnimeList', 'group' => 'pop', 'icon' => 'mal', 'sorted' => 1, 'tabs' => array('movies', 'international', 'ethnicity'), 'no_data' => 1,),
                'crt' => array('title' => 'Rotten Tomatoes', 'eid' => 'ert', 'titlesm' => 'Rotten Tomatoes', 'group' => 'pop', 'icon' => 'rt', 'sorted' => 1, 'tabs' => array('movies', 'international', 'ethnicity'), 'no_data' => 1,),
                'crta' => array('title' => 'Rotten Tomatoes Audience', 'eid' => 'erta', 'titlesm' => 'Rotten Tomatoes Audience', 'group' => 'pop', 'icon' => 'rt', 'sorted' => 1, 'tabs' => array('movies', 'international', 'ethnicity'), 'no_data' => 1,),
                'pop' => array('title' => 'ZR Critics', 'eid' => 'epop', 'titlesm' => 'ZR Critics', 'group' => 'pop', 'icon' => 'zr', 'sorted' => 1, 'no_data' => 1,),
                'caudience' => array('title' => 'ZR User Score', 'titlesm' => 'ZR User Score', 'group' => 'pop', 'icon' => 'zr', 'sorted' => 1, 'no_data' => 1,),
                'cfn' => array('title' => '4chan', 'titlesm' => '4chan', 'eid' => 'efn', 'group' => 'pop', 'icon' => 'fchan', 'sorted' => 1, 'tabs' => array('movies', 'international', 'ethnicity'), 'no_data' => 1,),
                // Global
                'gpoptitle' => array('title' => 'Global Popularity', 'is_title' => 1, 'group' => 'pop', 'tabs' => array('movies', 'international', 'ethnicity'), 'no_data' => 1,),
                'cdb' => array('title' => 'Douban', 'eid' => 'edb', 'titlesm' => 'Douban', 'group' => 'pop', 'icon' => 'douban', 'sorted' => 1, 'tabs' => array('movies', 'international', 'ethnicity'), 'no_data' => 1,),
                'ceiga' => array('title' => 'Eiga', 'eid' => 'eeiga', 'titlesm' => 'Eiga', 'group' => 'pop', 'icon' => 'jp', 'sorted' => 1, 'tabs' => array('movies', 'international', 'ethnicity'), 'no_data' => 1,),
                'ckp' => array('title' => 'Kinopoisk', 'eid' => 'ekp', 'titlesm' => 'Kinopoisk', 'group' => 'pop', 'icon' => 'kinop', 'sorted' => 1, 'tabs' => array('movies', 'international', 'ethnicity'), 'no_data' => 1,),
                'cmm' => array('title' => 'MovieMeter', 'eid' => 'emm', 'titlesm' => 'MovieMeter', 'group' => 'pop', 'icon' => 'nl', 'sorted' => 1, 'tabs' => array('movies', 'international', 'ethnicity'), 'no_data' => 1,),
                'cofdb' => array('title' => 'OFDb', 'eid' => 'eofdb', 'titlesm' => 'OFDb', 'group' => 'pop', 'icon' => 'de', 'sorted' => 1, 'tabs' => array('movies', 'international', 'ethnicity'), 'no_data' => 1,),
            ),
        ),
        'mkw' => array(
            'title' => 'Keywords',
            'tabs' => array('movies', 'games', 'international', 'ethnicity'),
            'minus' => 1,
            'hide' => 1,
            'weight' => 120,
        ),
        'country' => array(
            'title' => 'Country',
            'tabs' => array('movies', 'games', 'international', 'ethnicity'),
            'minus' => 1,
            'hide' => 1,
            'weight' => 130,
        ),
        'lang' => array(
            'title' => 'Language',
            'tabs' => array('movies', 'games', 'international', 'ethnicity'),
            'minus' => 1,
            'hide' => 1,
            'weight' => 130,
        ),
        'wokedata' => array(
            'title' => 'woke',
            'tabs' => array('movies', 'games', 'international', 'ethnicity', 'critics'),
            'is_parent' => 1,
            'hide' => 1,
            'weight' => 65,
            'childs' => array(
                'zrwoke' => array('title' => 'ZR Woke Meter', 'facet' => 'rating', 'eid' => 'ezrwoke', 'titlesm' => 'ZR Woke', 'max_count' => 10, 'multipler' => 1, 'group' => 'woke', 'icon' => 'zr_woke', 'sorted' => 1, 'minus' => 1, 'hide' => 1, 'minus' => 1, 'zero' => 1, 'max' => 1, 'sort_w' => 10),
                'rating' => array('title' => 'Family Friendly Score', 'facet' => 'rating', 'eid' => 'erating', 'titlesm' => 'FFS', 'max_count' => 50, 'multipler' => 10, 'group' => 'woke', 'icon' => 'zr_family', 'sorted' => 1, 'hide' => 1, 'minus' => 1, 'zero' => 1, 'max' => 1, 'sort_w' => 10,),
                // Filetrs
                'woketitle' => array('title' => 'Forced Diversity<span data-value="tooltip_zr_forced_diversity" class="nte_info"></span>', 'is_title' => 1, 'group' => 'woke', 'no_data' => 1, 'tabs' => array('movies', 'games', 'international', 'ethnicity'), 'sort_w' => 20,),
                'div' => array('title' => '"Diversity" %', 'eid' => 'ediversity', 'def' => 'desc', 'group' => 'woke', 'no_data' => 1, 'sorted' => 1, 'tabs' => array('movies', 'international', 'ethnicity'), 'sort_w' => 20,),
                'fem' => array('title' => 'Female %', 'eid' => 'efemale', 'def' => 'desc', 'group' => 'woke', 'no_data' => 1, 'sorted' => 1, 'tabs' => array('movies', 'international', 'ethnicity'), 'sort_w' => 20,),
                // IMDB Keywords
                // 'imdbratingtitle' => array('title' => 'Keyword Matches<span data-value="tooltip_zr_keyword_matches" class="nte_info"></span>', 'is_title' => 1, 'group' => 'woke', 'tabs' => array('movies', 'games', 'international', 'ethnicity'), 'sort_w' => 30,),
                'kmwoke' => array(
                    'title' => 'Keyword Matches', 'titlesm' => 'Keyword Matches', 'group' => 'woke', 'minus' => 1, 'icon' => 'imdb', 'sort_w' => 30,
                    'childs' => array(
                        'woke' => array('title' => 'Possibly Woke', 'facet' => 'rating', 'eid' => 'ewoke', 'titlesm' => 'Possibly Woke', 'max_count' => 20, 'multipler' => 1, 'group' => 'woke', 'icon' => 'zr_woke', 'sorted' => 1, 'hide' => 1, 'minus' => 1, 'zero' => 1, 'max' => 1, 'sort_w' => 30,),
                        'lgbt' => array(
                            'title' => 'LGBTQ', 'facet' => 'rating', 'eid' => 'elgbt', 'titlesm' => 'LGBTQ', 'max_count' => 20, 'multipler' => 1, 'group' => 'woke', 'icon' => 'zr_lgbt', 'sorted' => 1, 'hide' => 1, 'minus' => 1, 'zero' => 1, 'sort_w' => 30, 'sort_zero' => 1, 'max' => 1,
                            'childs' => array(
                                'lgb' => array('title' => 'LGB', 'facet' => 'rating', 'eid' => 'elgb', 'titlesm' => 'LGB', 'max_count' => 20, 'multipler' => 1, 'group' => 'woke', 'icon' => 'zr_lgbt', 'hide' => 1, 'minus' => 1, 'zero' => 1, 'max' => 1, 'sort_w' => 30),
                                'qtia' => array('title' => 'QTIA+', 'facet' => 'rating', 'eid' => 'eqtia', 'titlesm' => 'QTIA', 'max_count' => 20, 'multipler' => 1, 'group' => 'woke', 'icon' => 'zr_lgbt', 'hide' => 1, 'minus' => 1, 'zero' => 1, 'max' => 1, 'sort_w' => 30),
                            ),
                        ),
                    ),
                ),
                // Ratings
                'reviewwoketitle' => array('title' => 'Review Sites<span data-value="tooltip_zr_woke_search" class="nte_info"></span>', 'is_title' => 1, 'group' => 'woke', 'tabs' => array('movies', 'international', 'ethnicity'), 'sort_w' => 40,),
                'bechdeltest' => array('title' => 'BechdelTest', 'facet' => 'select', 'eid' => 'ebechdeltest', 'titlesm' => 'BechdelTest', 'max_count' => 5, 'multipler' => 1, 'group' => 'woke', 'hide' => 1, 'sorted' => 1, 'minus' => 1, 'sort_w' => 40, 'max' => 1,),
                'rcherry' => array('title' => 'CherryPicks', 'icon' => 'CherryPicks', 'facet' => 'rating', 'eid' => 'echerry', 'titlesm' => 'CherryPicks', 'max_count' => 100, 'multipler' => 1, 'group' => 'woke', 'sorted' => 1, 'hide' => 1, 'minus' => 1, 'zero' => 1, 'max' => 1, 'sort_w' => 40,),
                'rmedia' => array('title' => 'MediaVersity', 'icon' => 'MediaVersity', 'facet' => 'rating', 'eid' => 'emedia', 'titlesm' => 'MediaVersity', 'max_count' => 50, 'multipler' => 10, 'group' => 'woke', 'sorted' => 1, 'hide' => 1, 'minus' => 1, 'zero' => 1, 'max' => 1, 'sort_w' => 40,),
                'mediaversity' => array('title' => 'MediaVersity A-F', 'eid' => 'emedia', 'facet' => 'select', 'titlesm' => 'MediaVersity', 'max_count' => 20, 'multipler' => 1, 'group' => 'woke', 'hide' => 1, 'minus' => 1, 'sort_w' => 40,),
                'wokeornot' => array('title' => 'WokerNot', 'eid' => 'ewokeornot', 'facet' => 'rating', 'titlesm' => 'WokerNot', 'max_count' => 100, 'multipler' => 1, 'group' => 'woke', 'sorted' => 1, 'minus' => 1, 'hide' => 1, 'minus' => 1, 'zero' => 1, 'max' => 1, 'sort_w' => 40, 'sort_zero' => 1, 'sort_exist' => 1),
                'worthit' => array('title' => 'WorthItOrWoke', 'eid' => 'eworthit', 'facet' => 'select', 'titlesm' => 'WorthIt', 'max_count' => 5, 'multipler' => 1, 'group' => 'woke', 'hide' => 1, 'sorted' => 1, 'minus' => 1, 'sort_w' => 40),
                // Audience  
                'auratingtitle' => array('title' => 'Audience Warnings<span data-value="tooltip_zr_audience_warnings" class="nte_info"></span>', 'is_title' => 1, 'group' => 'woke', 'sort_w' => 50,),
                'auvote' => array('title' => 'SUGGESTION', 'eid' => 'eauvote', 'facet' => 'select', 'titlesm' => 'SUGGESTION', 'max_count' => 5, 'multipler' => 1, 'icon' => 'vote', 'hide' => 1, 'group' => 'woke', 'minus' => 1, 'sort_w' => 50, 'sort_zero' => 1, 'max' => 1,),
                'auneo' => array('title' => 'NEO-MARXISM', 'facet' => 'rating', 'eid' => 'eauneo', 'titlesm' => 'NEO-MARXISM', 'max_count' => 5, 'multipler' => 1, 'icon' => 'patriotism', 'group' => 'woke', 'sorted' => 1, 'hide' => 1, 'minus' => 1, 'zero' => 1, 'max' => 1, 'sort_w' => 50, 'sort_zero' => 1,),
                'aumisandry' => array('title' => 'FEMINISM', 'facet' => 'rating', 'eid' => 'eaumisandry', 'titlesm' => 'FEMINISM', 'max_count' => 5, 'multipler' => 1, 'icon' => 'misandry', 'group' => 'woke', 'sorted' => 1, 'hide' => 1, 'minus' => 1, 'zero' => 1, 'max' => 1, 'sort_w' => 50, 'sort_zero' => 1,),
                'auaffirmative' => array('title' => 'AFFIRMATIVE ACTION', 'facet' => 'rating', 'eid' => 'eauaffirmative', 'titlesm' => 'Audience AFFIRMATIVE ACTION', 'max_count' => 5, 'multipler' => 1, 'icon' => 'affirmative', 'group' => 'woke', 'sorted' => 1, 'hide' => 1, 'minus' => 1, 'zero' => 1, 'max' => 1, 'sort_w' => 50, 'sort_zero' => 1,),
                'aulgbtq' => array('title' => 'GAY STUFF', 'facet' => 'rating', 'eid' => 'eaulgbtq', 'titlesm' => 'GAY STUFF', 'max_count' => 5, 'multipler' => 1, 'icon' => 'lgbtq', 'group' => 'woke', 'sorted' => 1, 'hide' => 1, 'minus' => 1, 'zero' => 1, 'max' => 1, 'sort_w' => 50, 'sort_zero' => 1,),
                'augod' => array('title' => 'FEDORA TIPPING', 'facet' => 'rating', 'eid' => 'eaugod', 'titlesm' => 'FEDORA TIPPING', 'max_count' => 5, 'multipler' => 1, 'icon' => 'god', 'group' => 'woke', 'sorted' => 1, 'hide' => 1, 'minus' => 1, 'zero' => 1, 'max' => 1, 'sort_w' => 50, 'sort_zero' => 1,),
            ),
        ),
        'sortdata' => array(
            'title' => 'Sort data',
            'is_parent' => 1,
            'tabs' => array('movies', 'games', 'international', 'ethnicity', 'critics'),
            'childs' => array(
                // woke
                'ezrwoke' => array('tabs' => array('movies', 'international', 'ethnicity'),),
                'erating' => array('tabs' => array('movies', 'international', 'ethnicity'),),
                'ewoke' => array('tabs' => array('movies', 'international', 'ethnicity'),),
                'elgbt' => array('tabs' => array('movies', 'international', 'ethnicity'),),
                'elgb' => array('tabs' => array('movies', 'international', 'ethnicity'),),
                'eqtia' => array('tabs' => array('movies', 'international', 'ethnicity'),),
                'echerry' => array(),
                'emedia' => array('tabs' => array('movies', 'international', 'ethnicity'),),
                'ewokeornot' => array('tabs' => array('movies', 'international', 'ethnicity'),),
                'eworthit' => array('tabs' => array('movies', 'international', 'ethnicity'),),
                'ebechdeltest' => array('tabs' => array('movies', 'international', 'ethnicity'),),
                'ediversity' => array('tabs' => array('movies', 'international', 'ethnicity'),),
                'efemale' => array('tabs' => array('movies', 'international', 'ethnicity'),),
                // audience
                'eauvote' => array(),
                'eaurating' => array(),
                'eauneo' => array(),
                'eaumisandry' => array(),
                'eauaffirmative' => array(),
                'eaulgbtq' => array(),
                'eaugod' => array(),
                'eauaffirmative' => array(),
                // ratings
                'erwt' => array('tabs' => array('movies', 'international', 'ethnicity'),),
                'eimdb' => array('tabs' => array('movies', 'international', 'ethnicity'),),
                'emc' => array('tabs' => array('movies', 'international', 'ethnicity'),),
                'emu' => array('tabs' => array('movies', 'international', 'ethnicity'),),
                'eanl' => array('tabs' => array('movies', 'international', 'ethnicity'),),
                'ert' => array('tabs' => array('movies', 'international', 'ethnicity'),),
                'erta' => array('tabs' => array('movies', 'international', 'ethnicity'),),
                'ertg' => array('tabs' => array('movies', 'international', 'ethnicity'),),
                'efn' => array('tabs' => array('movies', 'international', 'ethnicity'),),
                'erev' => array('tabs' => array('movies', 'international', 'ethnicity'),),
                'edb' => array('tabs' => array('movies', 'international', 'ethnicity'),),
                'eeiga' => array('tabs' => array('movies', 'international', 'ethnicity'),),
                'emm' => array('tabs' => array('movies', 'international', 'ethnicity'),),
                'eofdb' => array('tabs' => array('movies', 'international', 'ethnicity'),),
                // finances
                'eboxusa' => array('tabs' => array('movies', 'international', 'ethnicity'),),
                'eboxworld' => array('tabs' => array('movies', 'international', 'ethnicity'),),
                'ebudget' => array('tabs' => array('movies', 'international', 'ethnicity'),),
                'eboxint' => array('tabs' => array('movies', 'international', 'ethnicity'),),
                'eanl' => array('tabs' => array('movies', 'international', 'ethnicity'),),
                'pop' => array('tabs' => array('critics'),),
            ),
        ),
        'price' => array(
            'title' => 'Price',
            'tabs' => array('movies', 'games', 'critics'),
        ),
        'wl' => array(
            'title' => 'Watchlist',
            'tabs' => array('movies', 'games', 'international', 'ethnicity'),
        ),
        'author' => array(
            'title' => 'Author',
            'tabs' => array('critics'),
            'weight' => 20,
        ),
        'state' => array(
            'title' => 'Types',
            'tabs' => array('critics'),
            'weight' => 25,
        ),
        'movie' => array(
            'title' => 'Movies',
            'tabs' => array('critics'),
            'weight' => 35,
        ),
        'tags' => array(
            'title' => 'Tags',
            'tabs' => array('critics'),
            'weight' => 60,
        ),
        'ctags' => array(
            'title' => 'Critic Tags',
            'tabs' => array('critics'),
            'weight' => 70,
        ),
        'from' => array(
            'title' => 'From author',
            'tabs' => array('critics', 'filters', 'watchlists','comments'),
            'weight' => 70,
        ),
        'site' => array(
            'title' => 'From site',
            'tabs' => array('critics'),
            'weight' => 80,
        ),
        
        // Filters
        'ftab' => array(
            'title' => 'Category',
            'tabs' => array('filters'),
            'sorted' => 1,
            'weight' => 10,
        ),
        'ctype' => array(
            'title' => 'Type',
            'tabs' => array('comments'),
            'weight' => 70,
        ),
        'cstatus' => array(
            'admin'=>1,
            'title' => 'Status',
            'tabs' => array('comments'),
            'weight' => 70,
        ),
    );
    // Facets
    // Search sort: /sort_title_desc
    public $def_search_sort = array(
        'movies' => array(
            'title' => array('title' => 'Title', 'def' => 'asc', 'main' => 1, 'group' => 'def'),
            'date' => array('title' => 'Date', 'def' => 'desc', 'main' => 1, 'group' => 'def'),
            'random' => array('title' => 'Random', 'def' => 'desc', 'main' => 1, 'group' => 'def'),
            'ratingsort' => array('title' => 'Ratings', 'group' => 'rating', 'main' => 1, 'sorted' => 1,),
            'rating' => array('title' => 'Family Friendly Score', 'def' => 'desc', 'group' => 'woke'),
            'popsort' => array('title' => 'Popularity', 'group' => 'pop', 'main' => 1, 'sorted' => 1,),
            'wokesort' => array('title' => 'Wokeness', 'group' => 'woke', 'main' => 1, 'sorted' => 1,),
            'finsort' => array('title' => 'Finances', 'group' => 'indie', 'main' => 1, 'sorted' => 1,),
            'rel' => array('title' => 'Relevance', 'def' => 'desc', 'main' => 1, 'group' => 'def'),
            'cast' => array('title' => 'Cast type', 'def' => 'desc', 'main' => 1, 'group' => 'def'),
        ),
        'games' => array(
            'title' => array('title' => 'Title', 'def' => 'asc', 'main' => 1, 'group' => 'def'),
            'date' => array('title' => 'Date', 'def' => 'desc', 'main' => 1, 'group' => 'def'),
            'random' => array('title' => 'Random', 'def' => 'desc', 'main' => 1, 'group' => 'def'),
            'ratingsort' => array('title' => 'Ratings', 'group' => 'rating', 'main' => 1, 'sorted' => 1,),
            'rating' => array('title' => 'Family Friendly Score', 'def' => 'desc', 'group' => 'woke'),
            'popsort' => array('title' => 'Popularity', 'group' => 'pop', 'main' => 1, 'sorted' => 1,),
            'wokesort' => array('title' => 'Wokeness', 'group' => 'woke', 'main' => 1, 'sorted' => 1,),
            'finsort' => array('title' => 'Finances', 'group' => 'indie', 'main' => 1, 'sorted' => 1,),
            'rel' => array('title' => 'Relevance', 'def' => 'desc', 'main' => 1, 'group' => 'def'),
            'cast' => array('title' => 'Cast type', 'def' => 'desc', 'main' => 1, 'group' => 'def'),
        ),
        'critics' => array(
            'title' => array('title' => 'Title', 'def' => 'asc', 'main' => 1, 'group' => 'def'),
            'date' => array('title' => 'Date', 'def' => 'desc', 'main' => 1, 'group' => 'def'),
            'random' => array('title' => 'Random', 'def' => 'desc', 'main' => 1, 'group' => 'def'),
            'rel' => array('title' => 'Relevance', 'def' => 'desc', 'main' => 1, 'group' => 'def'),
            'ratingsort' => array('title' => 'Ratings', 'group' => 'rating', 'main' => 1, 'sorted' => 1,),
            'titlepop' => array('title' => 'Rating', 'group' => 'woke', 'main' => 1,),
            //'pop' => array('title' => 'Emotions', 'def' => 'desc', 'group' => 'woke',),
            'mw' => array('title' => 'Weight', 'def' => 'desc', 'main' => 1, 'group' => 'def')
        ),
        'international' => array(
            'title' => array('title' => 'Title', 'def' => 'asc', 'main' => 1, 'group' => 'def'),
            'date' => array('title' => 'Date', 'def' => 'desc', 'main' => 1, 'group' => 'def'),
            'ratingsort' => array('title' => 'Ratings', 'group' => 'rating', 'main' => 1, 'sorted' => 1,),
            'rating' => array('title' => 'Family Friendly Score', 'def' => 'desc', 'group' => 'woke'),
            'popsort' => array('title' => 'Popularity', 'group' => 'pop', 'main' => 1, 'sorted' => 1,),
            'wokesort' => array('title' => 'Wokeness', 'group' => 'woke', 'main' => 1, 'sorted' => 1,),
            'finsort' => array('title' => 'Finances', 'group' => 'indie', 'main' => 1, 'sorted' => 1,),
            'rel' => array('title' => 'Relevance', 'def' => 'desc', 'main' => 1, 'group' => 'def'),
            'cast' => array('title' => 'Cast type', 'def' => 'desc', 'main' => 1, 'group' => 'def'),
        ),
        'ethnicity' => array(
            'title' => array('title' => 'Title', 'def' => 'asc', 'main' => 1, 'group' => 'def'),
            'date' => array('title' => 'Date', 'def' => 'desc', 'main' => 1, 'group' => 'def'),
            'ratingsort' => array('title' => 'Ratings', 'group' => 'rating', 'main' => 1, 'sorted' => 1,),
            'rating' => array('title' => 'Family Friendly Score', 'def' => 'desc', 'group' => 'woke'),
            'popsort' => array('title' => 'Popularity', 'group' => 'pop', 'main' => 1, 'sorted' => 1,),
            'wokesort' => array('title' => 'Wokeness', 'group' => 'woke', 'main' => 1, 'sorted' => 1,),
            'finsort' => array('title' => 'Finances', 'group' => 'indie', 'main' => 1, 'sorted' => 1,),
            'rel' => array('title' => 'Relevance', 'def' => 'desc', 'main' => 1, 'group' => 'def'),
            'cast' => array('title' => 'Cast type', 'def' => 'desc', 'main' => 1, 'group' => 'def'),
        ),
        'filters' => array(
            'title' => array('title' => 'Title', 'def' => 'asc', 'main' => 1, 'group' => 'def'),
            'date' => array('title' => 'Date', 'def' => 'desc', 'main' => 1, 'group' => 'def'),
            'random' => array('title' => 'Random', 'def' => 'desc', 'main' => 1, 'group' => 'def'),
            'frating' => array('title' => 'Rating', 'def' => 'desc', 'main' => 1, 'group' => 'def'),
            'rel' => array('title' => 'Relevance', 'def' => 'desc', 'main' => 1, 'group' => 'def'),
        ),
        'watchlists' => array(
            'title' => array('title' => 'Title', 'def' => 'asc', 'main' => 1, 'group' => 'def'),
            'date' => array('title' => 'Date', 'def' => 'desc', 'main' => 1, 'group' => 'def'),
            'random' => array('title' => 'Random', 'def' => 'desc', 'main' => 1, 'group' => 'def'),
            'frating' => array('title' => 'Rating', 'def' => 'desc', 'main' => 1, 'group' => 'def'),
            'rel' => array('title' => 'Relevance', 'def' => 'desc', 'main' => 1, 'group' => 'def'),
        ),        
        'comments' => array(          
            'date' => array('title' => 'Date', 'def' => 'desc', 'main' => 1, 'group' => 'def'),
            'random' => array('title' => 'Random', 'def' => 'desc', 'main' => 1, 'group' => 'def'),
            'frating' => array('title' => 'Rating', 'def' => 'desc', 'main' => 1, 'group' => 'def'),
            'rel' => array('title' => 'Relevance', 'def' => 'desc', 'main' => 1, 'group' => 'def'),
        ),
    );
    // Default search filters
    public $search_filters = array(
        'type' => array(
            'movies' => array('key' => 'Movie', 'title' => 'Movies'),
            'tv' => array('key' => 'TVSeries', 'title' => 'TV / Streaming'),
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
            'none' => array('key' => 0, 'title' => 'None'),
        ),
        'price' => array(
            'free' => array('key' => 1, 'title' => 'Watch free'),
        ),
        'ftab' => array(
            'movies' => array('key' => 1, 'title' => 'Movies/TV'),
            'games' => array('key' => 2, 'title' => 'Games'),
            'critics' => array('key' => 3, 'title' => 'Reviews'),
            'international' => array('key' => 4, 'title' => 'International'),
            'ethnicity' => array('key' => 5, 'title' => 'Ethnicity'),
        ),
        'race' => array(
            'a' => array('key' => 0, 'title' => 'All'),
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
        'sphoto' => array(
            'exist' => array('key' => 1, 'title' => 'Stars has Photo'),
        ),
        'mphoto' => array(
            'exist' => array('key' => 1, 'title' => 'Supporting has Photo'),
        ),
        'aphoto' => array(
            'exist' => array('key' => 1, 'title' => 'Cast has Photo'),
        ),
        'auvote' => array(
            'skip' => array('key' => 2, 'title' => 'Skip It'),
            'free' => array('key' => 3, 'title' => 'Consume If Free'),
            'pay' => array('key' => 1, 'title' => 'Pay To Consume'),
        ),
        'movie' => array('key' => 'id', 'title' => 'Movie ',),
        'indie' => array(
            'isfranchise' => array('key' => 'isfranchise', 'title' => 'Franchise'),
            'reboot' => array('key' => 'reboot', 'title' => 'Reboot'),
            'remake' => array('key' => 'remake', 'title' => 'Remake'),
            'prequel' => array('key' => 'prequel', 'title' => 'Prequel'),
            'sequel' => array('key' => 'sequel', 'title' => 'Sequel'),
            'bigdist' => array('key' => 'bigdist', 'title' => 'The “Big Five”'),
            'meddist' => array('key' => 'meddist', 'title' => 'Mini-majors'),
            'indidist' => array('key' => 'bigdist', 'title' => 'Independent Studios (USA)'),
        ),
        'mediaversity' => array(
            'a' => array('key' => 1, 'title' => 'A', 'color' => 3),
            'b' => array('key' => 2, 'title' => 'B', 'color' => 3),
            'c' => array('key' => 3, 'title' => 'C', 'color' => 2),
            'd' => array('key' => 4, 'title' => 'D', 'color' => 2),
            'e' => array('key' => 5, 'title' => 'E', 'color' => 2),
            'f' => array('key' => 6, 'title' => 'F', 'color' => 1),
        ),
        'worthit' => array(
            'worthit' => array('key' => 1, 'title' => 'Worth it', 'color' => 1),
            'nonwoke' => array('key' => 2, 'title' => 'Non-Woke', 'color' => 1),
            'wokeish' => array('key' => 3, 'title' => 'Woke-ish', 'color' => 2),
            'woke' => array('key' => 4, 'title' => 'Woke', 'color' => 3),
        ),
        'bechdeltest' => array(
            'nowomen' => array('key' => 1, 'title' => 'No 2 women', 'color' => 1),
            'notalk' => array('key' => 2, 'title' => 'No women talking', 'color' => 2),
            'talk' => array('key' => 3, 'title' => 'Talk about a man', 'color' => 2),
            'pass' => array('key' => 4, 'title' => 'Passed!', 'color' => 3),
        ),
        'kmwoke' => array(
            'woke' => array('key' => 'ewoke', 'title' => 'Possibly Woke', 'color' => 3),
            'lgbt' => array('key' => 'elgbt', 'title' => 'LGBTQ', 'color' => 3),
            'lgb' => array('key' => 'elgb', 'title' => 'LGB', 'color' => 3),
            'qtia' => array('key' => 'eqtia', 'title' => 'QTIA+', 'color' => 3),
        ),
        'ctype' => array(
            'critic' => array('key' => 0, 'title' => 'Reviews'),
            'page' => array('key' => 1, 'title' => 'Pages'),
            'movies' => array('key' => 2, 'title' => 'Movies/TV'),
            'games' => array('key' => 3, 'title' => 'Video Games'),
        ),
        'cstatus' => array(
            'pending' => array('key' => '0', 'title' => 'Pending'),
            'approve' => array('key' => '1', 'title' => 'Approve'),
            'spam' => array('key' => 'spam', 'title' => 'Spam'),
            'trash' => array('key' => 'trash', 'title' => 'Trash'),            
        ),
    );

    // Actors cache

    public $actorscache = array();

    public function __construct($cm = '') {
        $this->cm = $cm ? $cm : new CriticMatic();
        $this->db = array(
            //CS
            'log' => DB_PREFIX_WP . 'critic_search_log',
        );
        $this->get_perpage();

        // Init facets
        $this->facets = array();
        $this->search_sort = $this->def_search_sort;
        $this->hide_facets = array();

        $this->actorscache = array();
        foreach ($this->facet_data as $key => $facet) {
            $this->init_filters($key, $facet);
        }
    }

    public function generate_facet($type = '', $facet_tab = '', $parent = '', $main_parent = '') {
        // type: star, main, all
        // tab: race, gender
        // Init actor filters

        $rtab = array('a' => 'all', 's' => 'star', 'm' => 'main');
        /*
         * Main parent: actorsdata, dirsdata
         */
        $key_pre = '';
        $theme = 'cast';
        if ($main_parent == 'actorsdata') {

            if ($type == 'star') {
                $rtab = array('s' => 'star');
            } else if ($type == 'main') {
                $rtab = array('m' => 'main');
            } else if ($type == 'all') {
                $rtab = array('a' => 'all');
            }
        } else if ($main_parent == 'dirsdata') {
            $key_pre = 'd';
            $theme = 'director';
            if ($type == 'directors') {
                $rtab = array('d' => 'directors');
            } else if ($type == 'writers') {
                $rtab = array('w' => 'writers');
            } else if ($type == 'cast-directors') {
                $rtab = array('c' => 'cast-directors');
            } else if ($type == 'producers') {
                $rtab = array('p' => 'producers');
            } else if ($type == 'alldir') {
                $rtab = array('a' => 'alldir');
            }
        }

        /*
         * print "$type, $facet_tab, $parent, $main_parent\n";
          star, race, starrace, actorsdata
          star, gender, stargender, actorsdata
          main, race, mainrace, actorsdata
          main, gender, maingender, actorsdata
          all, race, race, actorsdata
          all, gender, gender, actorsdata
         */
        $rcount = array('e' => 'exist', 'p' => 'percent');

        $rgender = array('a' => 'all', 'm' => 'male', 'f' => 'female');
        $rrace = $this->search_filters['race'];
        if ($facet_tab == 'race') {
            // Only all genders for race select             
            $rgender = array('a' => 'all');
        } else if ($facet_tab == 'gender') {
            // Only all races for gender select
            $rrace = array('a' => array('key' => 0, 'title' => 'All'),);
        }

        $tabs = array('movies', 'games', 'international', 'ethnicity');
        foreach ($rcount as $ckey => $cvalue) {
            foreach ($rtab as $tckey => $tvalue) {
                /* if ($type != $tvalue) {
                  continue;
                  } */
                foreach ($rgender as $gkey => $gvalue) {
                    foreach ($rrace as $rkey => $rvalue) {
                        try {
                            $key_str = "{$key_pre}{$ckey}{$tckey}{$gkey}{$rkey}";
                            $race_item = array('race' => $rkey);
                            if ($ckey == 'e') {
                                $child_key = "{$key_pre}p{$tckey}{$gkey}{$rkey}";
                                $exist_key = "{$key_pre}e{$tckey}{$gkey}{$rkey}";
                                $gender_title = '';
                                if ($gkey != 'a') {
                                    $gender_title = $this->search_filters['gender'][$gvalue]['title'] . ' ';
                                }
                                $type_title = $this->facets_data[$tvalue]['title'];

                                $race_title = '';
                                if ($rkey != 'a') {
                                    $race_title = $rvalue['title'];
                                }
                                $child_title = "{$type_title};{$gender_title};{$race_title}";

                                $child_value = array(
                                    'theme' => $theme,
                                    'facet' => 'rating',
                                    'minus' => 1,
                                    'hide' => 0,
                                    'name_after' => '%',
                                    'title' => $child_title,
                                    'name_after' => '%',
                                );
                                $race_item['childs'] = array($child_key => $child_value);
                                $this->facets_data[$child_key] = $child_value;
                                $this->facet_parents[$child_key] = $main_parent;
                                $this->facet_all_parents[$exist_key] = $parent;
                                $this->facet_all_parents[$child_key] = $exist_key;
                            }
                            $this->actorscache[$theme][$cvalue][$tvalue][$gvalue][$key_str] = $race_item;
                            // Add filters
                            $this->filters[$key_str] = '';
                            if ($ckey == 'e') {
                                // Only exist type facets
                                $this->hide_facets[$key_str] = 1;
                            } else {
                                $this->filters['minus-' . $key_str] = '';
                            }
                            foreach ($tabs as $tab) {
                                $this->facets[$tab][] = $key_str;
                            }
                        } catch (Exception $exc) {
                            
                        }
                    }
                }
            }
        }
    }

    public function init_filters($key, $facet, $parent = '', $main_parent = '') {

        if (isset($facet['type']) && $facet['type'] == 'generate') {
            $this->generate_facet($key, $facet['parent'], $parent, $main_parent);
            return;
        }

        if ($parent) {
            // Parent logic
            $this->facet_all_parents[$key] = $parent;
            if (!$main_parent) {
                $main_parent = $parent;
            }
            $this->facet_parents[$key] = $main_parent;
        }

        $this->facets_data[$key] = $facet;

        if (isset($facet['tabs'])) {
            $this->facet_tabs[$key] = $facet['tabs'];
            foreach ($facet['tabs'] as $tab) {
                if (isset($facet['is_title']) && $facet['is_title'] == 1) {
                    $this->search_sort[$tab][$key] = $facet;
                    continue;
                }


                // Facets logic
                $this->facets[$tab][] = $key;
                if (isset($facet['sorted'])) {
                    //Sort
                    $def_sort = isset($facet['sort']) ? $facet['sort'] : 'desc';
                    $main = isset($facet['main']) ? 1 : 0;
                    $icon = isset($facet['icon']) ? $facet['icon'] : '';
                    $group = isset($facet['group']) ? $facet['group'] : '';
                    $eid = isset($facet['eid']) ? $facet['eid'] : '';
                    $sort_w = isset($facet['sort_w']) ? $facet['sort_w'] : 0;
                    //$sort_second = isset($facet['sort_second']) ? $facet['sort_second'] : '';
                    $sort_append = array('title' => $facet['title'], 'def' => $def_sort, 'main' => $main, 'icon' => $icon, 'group' => $group, 'eid' => $eid, 'sort_w' => $sort_w);
                    $this->search_sort[$tab][$key] = $sort_append;
                } else if (isset($facet['sort']) && $facet['sort']) {
                    // Search sort logic                    
                    $this->search_sort[$tab][$key] = $facet['sort'];
                }
            }
        }

        if (isset($facet['is_title']) && $facet['is_title'] == 1) {
            $this->facet_titles[$key] = 1;
            return;
        }
        // Add filters
        $this->filters[$key] = '';

        if (isset($facet['minus']) && $facet['minus'] == 1) {
            $this->filters['minus-' . $key] = '';
        }

        if (isset($facet['andor'])) {
            if ($facet['andor'] == 'or') {
                $this->filters['and-' . $key] = '';
                if (isset($facet['minus']) && $facet['minus'] == 1) {
                    $this->filters['and-minus-' . $key] = '';
                }
            } else {
                $this->filters['or-' . $key] = '';
                if (isset($facet['minus']) && $facet['minus'] == 1) {
                    $this->filters['or-minus-' . $key] = '';
                }
            }
        }

        // Hide logic
        if (isset($facet['hide']) && $facet['hide'] == 1) {
            $this->hide_facets[$key] = 1;
        }

        // Parent name logic
        /* if (isset($facet['parent'])) {
          $this->facet_parent[$key] = $facet['parent'];
          $this->facet_all_parents[$key] = $facet['parent'];
          } */

        // Childs
        if (isset($facet['childs'])) {
            if (!$main_parent) {
                $main_parent = $key;
            }

            foreach ($facet['childs'] as $ckey => $child) {
                if (!isset($child['tabs'])) {
                    $child['tabs'] = $facet['tabs'];
                }
                $this->init_filters($ckey, $child, $key, $main_parent);
            }
        }
    }

    public function connect() {
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

    public function get_last_parent($facet) {
        $last_parent = isset($this->facet_parents[$facet]) ? $this->facet_parents[$facet] : $facet;
        return $last_parent;
    }

    public function get_first_parent($facet, $cache = true) {
        if (strstr($facet, 'minus-')) {
            $facet = str_replace('minus-', '', $facet);
        }
        $parents = $this->get_parents($facet, $cache);
        $first_parent = $facet;
        if ($parents) {
            foreach ($parents as $pkey => $parent) {
                if ($parent == $facet) {
                    $next_key = $pkey + 1;
                    if (isset($parents[$next_key])) {
                        $first_parent = $parents[$next_key];
                    }
                    break;
                }
            }
        }
        return $first_parent;
    }

    public function get_parents($facet, $cache = true) {
        if (strstr($facet, 'minus-')) {
            $facet = str_replace('minus-', '', $facet);
        }
        if ($cache) {
            static $dict;
            if (is_null($dict)) {
                $dict = array();
            }

            if (isset($dict[$facet])) {
                return $dict[$facet];
            }
        }
        $facet_name = $facet;
        $parents = array($facet);

        while (true) {
            if (isset($this->facet_all_parents[$facet_name])) {
                $facet_name = $this->facet_all_parents[$facet_name];
                $parents[] = $facet_name;
            } else {
                break;
            }
        }


        if ($cache) {
            $dict[$facet] = $parents;
        }

        return $parents;
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
            $ret[$id]['debug']['critic type'][] = "Contains Mention:$critic_type. Reason: default";

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
                        $find_title_clear = $this->filter_movie_tile($find_title);
                        $title_clear = $this->filter_movie_tile($title);
                        //print "$find_title->$find_title_clear, $title->$title_clear<br />";
                        if (strstr($find_title_clear, $title_clear) || strstr($find_title_clear, strtoupper($title_clear))) {
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
                        $ret[$id]['debug']['title quoutes'] = implode('; ', $found_quotes);
                    }

                    // Proper review
                    if (($post_title_weight >= $min_title_weight && $num >= $small_titles ) || $title_tags) {
                        $critic_type = 1;
                        $reason = array();
                        if ($title_tags) {
                            $reason[] = 'Title tags';
                        }
                        if ($post_title_weight >= $min_title_weight && $num >= $small_titles) {
                            $reason[] = "post_title_weight:$post_title_weight >= min_title_weight:$min_title_weight && num:$num >= small_titles:$small_titles";
                        }
                        $ret[$id]['debug']['critic type'][] = "<br />Proper review:$critic_type. Reason: " . implode('; ', $reason);
                    }
                }
            }

            if ($title_w != 10) {
                // Keywords found in critic description
                $valid_desc = false;
                if (isset($value['found']['content'])) {
                    foreach ($value['found']['content'] as $v) {
                        $find_value = $v;
                        $find_value_clear = $this->filter_movie_tile($find_value);
                        $title_clear = $this->filter_movie_tile($title);
                        if (strstr($find_value_clear, $title_clear) || strstr($find_value_clear, strtoupper($title_clear))) {
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
            $year_invalid = false;
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
                $ret[$id]['debug']['critic type'][] = "<br />Proper review:$critic_type. Reason clear: Critic  title:$critic_clear == Movie title:$movie_clear";
            } else {
                // 1. Find another date in title
                $dates = $this->find_dates($value['title'], $title);

                if ($dates) {
                    if (!in_array($year, $dates)) {
                        // Year vaild
                        $year_invalid = true;
                    }
                }
                // 2. Find quotes from another movie in title
                // 3. Find another movie in title
                //$top_movie = $this->find_top_movie_by_title($movie_clear, $critic_clear, 1);
                //p_r(array($movie_clear,$critic_clear,$top_movie));                
            }

            // Find movie keywodrs in title for no proper reviews
            if ($critic_type != 1) {
                $reg_clear = '#[^\w\d\ ]+#';
                $post_title_clear = preg_replace($reg_clear, '', $post_title);
                $title_clear = preg_replace($reg_clear, '', $title);
                $title_arr = explode(' ', $title_clear);
                $title_key_score = 3;

                foreach ($title_arr as $key) {
                    if (strstr($post_title_clear, $key)) {
                        $ret[$id]['debug']['title_keys'] .= $key . '; ';
                        $ret[$id]['score']['title_keys_score'] += $title_key_score;
                        $ret[$id]['total'] += $title_key_score;
                    }
                }
            }


            if ($ret[$id]['score']) {
                arsort($ret[$id]['score']);
            }

            // Auto critic type
            $ret[$id]['type'] = $critic_type;
            $ret[$id]['debug']['other'] = array();

            $valid = true;
            $ret[$id]['debug']['valid'][] = "True: Default";

            if ($post_type == 'VideoGame' && $valid) {
                // Need video tags to valid
                if (!isset($ret[$id]['score']['games_tags'])) {
                    $valid = false;
                    $reason = 'Game tags not found';
                    $ret[$id]['debug']['valid'][] = "<br />False: Post type is VideoGame and Game tags not found";
                }
            }

            if ($valid) {
                if ($post_title_weight < $min_title_weight || $num < $small_titles) {
                    // Small title weight

                    $valid = false;
                    if ($num < $small_titles) {
                        $ret[$id]['debug']['valid'][] = "<br />False: Small title length: $num < $small_titles";
                    }
                    if ($post_title_weight < $min_title_weight) {
                        $ret[$id]['debug']['valid'][] = "<br />False: Small title weigth: $post_title_weight < $min_title_weight";
                    }

                    $reason = '';
                    if ($title_tags) {
                        $valid = true;
                        $ret[$id]['debug']['valid'][] = "<br />True: Title tags found";
                    } else if ($content_tags) {
                        $valid = true;
                        $ret[$id]['debug']['valid'][] = "<br />True: Content tags found";
                    } else if ($title_equals) {
                        $valid = true;
                        $ret[$id]['debug']['valid'][] = "<br />True: Titles equals";
                    }
                }
            }

            // Check date for proper review
            if ($dates && $critic_type == 1) {
                $valid_text = 'Valid';
                if ($year_invalid) {
                    $valid = false;
                    $ret[$id]['debug']['valid'][] = "<br />False: Invalid Year. Maybe this review is from another movie with a similar title and a different date.";
                }
                $ret[$id]['debug']['dates'] = 'Found dates in title: ' . implode(';', $dates) . '. ' . $valid_text;
            }

            // Check old release
            if ($need_release) {
                $ret[$id]['score']['release_exist'] = 'True';
                if (!in_array($id, $valid_release)) {
                    $ret[$id]['score']['release_exist'] = 'False';
                    $valid = false;
                    $ret[$id]['debug']['valid'][] = "<br />False: Movie date is old ($year) and valid release year not found in post";
                }
            }

            // Check another movies the same name
            $title_clear = strtolower($this->filter_movie_tile($title));
            $all_movies = $this->search_movies_by_title($title_clear);
            if ($all_movies) {
                $another_movies = array();
                foreach ($all_movies as $movie) {
                    $mtitle = strtolower($this->filter_movie_tile($movie->title));
                    if ($mtitle == $title_clear && $movie->year != $year) {
                        $another_movies[$movie->year] = $movie->id;
                    }
                    // Sort movies by year
                    krsort($another_movies);
                }
                if (sizeof($another_movies) > 0) {
                    // Found another movies. Need check date   
                    $first_movie_year = array_pop(array_keys($another_movies));
                    $ret[$id]['debug']['other'][] = 'Found another movies: ' . implode(',', array_keys($another_movies));
                    if ($first_movie_year > $year) {
                        $ret[$id]['debug']['other'][] = '<br />Found a newer (' . $first_movie_year . ') movie with the same title, id=' . $another_movies[$first_movie_year];
                        if (!in_array($id, $valid_release)) {
                            $ret[$id]['score']['release_exist'] = 'False';
                            $valid = false;
                            $ret[$id]['debug']['valid'][] = "<br />False: Movie is dublicated ($year < $first_movie_year) and valid release year not found in post";
                        }
                    }
                }
            }

            if ($valid) {
                // Check min score
                $valid = $ret[$id]['total'] >= $ss['min_valid_point'] ? true : false;
                if ($valid) {
                    $ret[$id]['debug']['valid'][] = "<br />True: Score:" . $ret[$id]['total'] . ">= Valid:" . $ss['min_valid_point'];
                } else {
                    $ret[$id]['debug']['valid'][] = "<br />True, but change critic type to Related Article: Score:" . $ret[$id]['total'] . "< Valid:" . $ss['min_valid_point'];
                    // If small score, critic type related article
                    if ($ret[$id]['total'] > 0) {
                        $ret[$id]['type'] = 3;
                        $ret[$id]['debug']['critic type'][] = "<br />Related Article:3. Score:" . $ret[$id]['total'] . "< Valid:" . $ss['min_valid_point'];
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
        $title = str_replace('&amp;', 'and', $title);
        $title = str_replace('&', 'and', $title);
        $title = preg_replace('#[^\w\d\' ]+#', '', $title);
        $title = preg_replace('#  #', ' ', $title);
        $title = trim(strtolower($title));

        return $title;
    }

    private function filter_movie_tile($title) {
        $title = str_replace('&amp;', 'and', $title);
        $title = str_replace('&', 'and', $title);
        $title = preg_replace('#[^\w\d\' ]+#', '', $title);
        $title = preg_replace('#  #', ' ', $title);
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

        $title = str_replace("'", "\'", $title);

        $snippet = '';
        if ($debug) {
            $snippet = ', SNIPPET(title, QUERY()) t, SNIPPET(content, QUERY()) c';
        }

        //Example: "=The =Widow"
        $keyword = '"=' . str_replace(' ', ' =', $title) . '"';
        if (strstr($keyword, '&amp;')) {
            $keyword = str_replace('&amp;', '&', $keyword);
        }
        if (strstr($keyword, '&')) {
            $keyword = '(' . str_replace('=&', '&', $keyword) . '|' . str_replace('&', 'and', $keyword) . ')';
        }
        //$keyword = str_replace("=&", "&", $keyword);
        if ($year) {
            $keyword .= ' MAYBE ' . $year;
        }

        $and_release = '';
        if ($release_time != 0) {
            $and_release = ' AND post_date > ' . ($release_time - 86400 * 30 * 6);
        }

        $ids_and = '';
        if ($ids) {
            $ids_and = ' AND id IN(' . implode(',', $ids) . ')';
        }

        $sql = sprintf("SELECT id, title, post_date, content, weight() w" . $snippet . " FROM critic "
                . "WHERE MATCH('@(title,content) ($keyword)') AND status=1 AND author_type!=%d" . $ids_and . $and_release . " LIMIT %d "
                . "OPTION ranker=expr('sum(user_weight)'), "
                . "field_weights=(title=10, content=1) ", $author_type, $limit);

        $result = $this->sdb_results($sql);

        //    print $sql.'<br/>';
        //    $meta = $this->sdb_results("SHOW META");
        //    print_r($meta);        

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
        // <b>Blade Runner</b>
        // <em>Blade Runner 2049</em>
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


        $sql = sprintf("SELECT id, title, year FROM movie_an "
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

    public function front_search_critics_multi($keyword = '', $limit = 20, $start = 0, $sort = array(), $filters = array(), $facets = false, $show_meta = true, $widlcard = false, $fields = array(), $show_main = true) {

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

        if ($show_main) {
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
            $sql = sprintf("SELECT id, date_add, weight() w, author_type" . $snippet . $custom_fields . $order['select'] . $filters_and['select']
                    . " FROM critic WHERE id>0" . $filters_and['filter'] . $match . $order['order'] . " LIMIT %d,%d ", $start, $limit);

            //Get result
            $ret = $this->movie_results($sql, $match, $search_query);
        }

         /*
              print_r($filters_and);
              print_r(array($match, $search_query));
              print_r($sql);
              print_r($ret);

              $meta = $this->sps->query("SHOW META")->fetchAll();
              print_r($meta);
              exit;
   */
        
        // Simple result
        if (!$show_meta) {
            return $ret['list'];
        }

        // Facets logic         
        $facets_arr = array();
        if ($facets) {
            $facets_arr = $this->critic_facets($filters, $match, $search_query, $query_type, $facets);
        }
        $ret['facets'] = $facets_arr;
        return $ret;
    }

    public function front_search_movies_multi($keyword = '', $limit = 20, $start = 0, $sort = array(), $filters = array(), $facets = false, $show_meta = true, $widlcard = true, $show_main = true, $fields = array()) {

        $m_mkw = '';
        if ($filters['mkw']) {
            $mkw = $filters['mkw'];
            if (is_array($mkw)) {
                $mkw = implode('|', $mkw);
            }
            $m_mkw = " @(mkw_str) (" . $mkw . ")";
        }

        if (!isset($filters['type'])) {
            $filters['type'] = array('movies', 'tv');
        }

        //Keywords logic
        $match = '';
        if ($keyword) {
            $search_keywords = $this->wildcards_maybe_query($keyword, $widlcard, ' ');
            $search_query = sprintf("'@(title,year) ((^%s$)|(%s))" . $m_mkw . "'", $keyword, $search_keywords);
            $match = " AND MATCH(:match)";
        } else {
            if ($m_mkw) {
                $search_query = "'" . $m_mkw . "'";
                $match = " AND MATCH(:match)";
            }
        }

        $ret = array('list' => array(), 'count' => 0);
        $this->connect();
        gmi('search connect');

        // Main logic
        if ($show_main) {

            //Sort logic
            $order = $this->get_order_query($sort, $filters);

            // Filters logic
            $filters_and = $this->get_filters_query($filters);

            // Custom fields
            $custom_fields = '';
            if ($fields) {
                $custom_fields = ', ' . implode(', ', $fields) . ' ';
            }

            // Main sql
            $sql = sprintf("SELECT id, rwt_id, title, release, type, year, weight() w, rrt, rrta, rrtg, movie_id" . $custom_fields . $filters_and['select'] . $order['select']
                    . " FROM movie_an WHERE id>0" . $filters_and['filter'] . $match . $order['order'] . " LIMIT %d,%d ", $start, $limit);

            $ret = $this->movie_results($sql, $match, $search_query);

            /*
              print_r($filters_and);
              print_r(array($match, $search_query));
              print_r($sql);
              print_r($ret);

              $meta = $this->sps->query("SHOW META")->fetchAll();
              print_r($meta);
              exit;
             */

            gmi('main sql');
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
        gmi('get facets');

        $ret['facets'] = $facets_arr;
        return $ret;
    }

    public function front_search_games_multi($keyword = '', $limit = 20, $start = 0, $sort = array(), $filters = array(), $facets = false, $show_meta = true, $widlcard = true, $show_main = true, $fields = array()) {

        $filters['type'] = array('videogame');

        $m_mkw = '';
        if ($filters['mkw']) {
            $mkw = $filters['mkw'];
            if (is_array($mkw)) {
                $mkw = implode('|', $mkw);
            }
            $m_mkw = " @(mkw_str) (" . $mkw . ")";
        }

        //Keywords logic
        $match = '';
        if ($keyword) {
            $search_keywords = $this->wildcards_maybe_query($keyword, $widlcard, ' ');
            $search_query = sprintf("'@(title,year) ((^%s$)|(%s))" . $m_mkw . "'", $keyword, $search_keywords);
            $match = " AND MATCH(:match)";
        } else {
            if ($m_mkw) {
                $search_query = "'" . $m_mkw . "'";
                $match = " AND MATCH(:match)";
            }
        }

        $ret = array('list' => array(), 'count' => 0);
        $this->connect();
        gmi('search connect');
        $query_type = 'games';

        // Main logic
        if ($show_main) {

            //Sort logic
            $order = $this->get_order_query($sort, $filters);

            // Filters logic            
            $filters_and = $this->get_filters_query($filters, array(), $query_type);

            // Custom fields
            $custom_fields = '';
            if ($fields) {
                $custom_fields = ', ' . implode(', ', $fields) . ' ';
            }

            // Main sql
            $sql = sprintf("SELECT id, rwt_id, title, release, type, year, weight() w, movie_id" . $custom_fields . $order['select'] . $filters_and['select']
                    . " FROM movie_an WHERE id>0" . $filters_and['filter'] . $match . $order['order'] . " LIMIT %d,%d ", $start, $limit);

            $ret = $this->movie_results($sql, $match, $search_query);
            gmi('main sql');
            // Simple result
            if (!$show_meta) {
                return $ret['list'];
            }
        }

        // Facets logic
        $facets_arr = array();
        if ($facets) {
            $facets_arr = $this->movies_facets($filters, $match, $search_query, $facets, $query_type);
        }
        gmi('get facets');

        $ret['facets'] = $facets_arr;
        return $ret;
    }

    public function front_search_filters_multi($aid = 0, $keyword = '', $limit = 20, $start = 0, $sort = array(), $filters = array(), $facets = false, $show_meta = true, $widlcard = true, $show_main = true, $fields = array()) {

        //Keywords logic
        $match = '';
        if ($keyword) {
            $keyword = str_replace("'", "\'", $keyword);
            $search_keywords = $this->wildcards_maybe_query($keyword, $widlcard, ' ');
            $search_query = sprintf("'@(title,content) (%s)'", $search_keywords);
            $match = " AND MATCH(:match)";
        }

        $ret = array('list' => array(), 'count' => 0);
        $this->connect();
        gmi('search connect');
        $query_type = 'filters';

        // Main logic
        if ($show_main) {

            //Sort logic
            $order = $this->get_order_query_filters($sort);

            // Filters logic            
            $filters_and = $this->get_filters_query($filters, array(), $query_type, '', $aid);

            // Custom fields
            $custom_fields = '';
            if ($fields) {
                $custom_fields = ', ' . implode(', ', $fields) . ' ';
            }

            // Main sql
            $sql = sprintf("SELECT id, aid, wp_uid, fid, publish, date, last_upd, frating, title, content, img, ftab, link, weight() w" . $custom_fields . $order['select'] . $filters_and['select']
                    . " FROM filters WHERE id>0" . $filters_and['filter'] . $match . $order['order'] . " LIMIT %d,%d ", $start, $limit);

            $ret = $this->movie_results($sql, $match, $search_query);
            gmi('main sql');
            // Simple result
            if (!$show_meta) {
                return $ret['list'];
            }
        }

        // Facets logic
        $facets_arr = array();
        if ($facets) {
            $facets_arr = $this->movies_facets($filters, $match, $search_query, $facets, $query_type, $aid);
        }
        gmi('get facets');

        $ret['facets'] = $facets_arr;
        return $ret;
    }

    public function front_search_watchlists_multi($aid = 0, $keyword = '', $limit = 20, $start = 0, $sort = array(), $filters = array(), $facets = false, $show_meta = true, $widlcard = true, $show_main = true, $fields = array()) {

        //Keywords logic
        $match = '';
        if ($keyword) {
            $keyword = str_replace("'", "\'", $keyword);
            $search_keywords = $this->wildcards_maybe_query($keyword, $widlcard, ' ');
            $search_query = sprintf("'@(title,content) (%s)'", $search_keywords);
            $match = " AND MATCH(:match)";
        }

        $ret = array('list' => array(), 'count' => 0);
        $this->connect();
        gmi('search connect');
        $query_type = 'watchlists';

        // Main logic
        if ($show_main) {

            //Sort logic
            $order = $this->get_order_query_filters($sort);

            // Filters logic            
            $filters_and = $this->get_filters_query($filters, array(), $query_type, '', $aid);

            // Custom fields
            $custom_fields = '';
            if ($fields) {
                $custom_fields = ', ' . implode(', ', $fields) . ' ';
            }

            // Main sql
            $sql = sprintf("SELECT id, aid, wp_uid, top_mid, publish, date, last_upd, frating, title, content, type, items, weight() w" . $custom_fields . $order['select'] . $filters_and['select']
                    . " FROM watchlists WHERE id>0" . $filters_and['filter'] . $match . $order['order'] . " LIMIT %d,%d ", $start, $limit);

            $ret = $this->movie_results($sql, $match, $search_query);

            gmi('main sql');
            // Simple result
            if (!$show_meta) {
                return $ret['list'];
            }
        }

        // Facets logic
        $facets_arr = array();
        if ($facets) {
            $facets_arr = $this->movies_facets($filters, $match, $search_query, $facets, $query_type, $aid);
        }
        gmi('get facets');

        $ret['facets'] = $facets_arr;
        return $ret;
    }

    public function front_search_comments_multi($aid = 0, $keyword = '', $limit = 20, $start = 0, $sort = array(), $filters = array(), $facets = false, $show_meta = true, $widlcard = true, $show_main = true, $fields = array()) {

        // Keywords logic
        $match = '';
        if ($keyword) {
            $keyword = str_replace("'", "\'", $keyword);
            $search_keywords = $this->wildcards_maybe_query($keyword, $widlcard, ' ');
            $search_query = sprintf("'@(comment_content) (%s)'", $search_keywords);
            $match = " AND MATCH(:match)";
        }

        $ret = array('list' => array(), 'count' => 0);
        $this->connect();
        gmi('search connect');
        $query_type = 'comments';

        // Main logic
        if ($show_main) {

            //Sort logic
            $order = $this->get_order_query_filters($sort);

            // Filters logic            
            $filters_and = $this->get_filters_query($filters, array(), $query_type, '', $aid);

            // Custom fields
            $custom_fields = '';
            if ($fields) {
                $custom_fields = ', ' . implode(', ', $fields) . ' ';
            }

            // Main sql
            $sql = sprintf("SELECT id, comment_ID, comment_post_ID, comment_author, comment_author_email, comment_author_url,comment_author_IP,
                    comment_date, comment_date_gmt, comment_content, cstatus as comment_approved, comment_type, comment_parent, user_id, ctype as post_type, 
                    aid,comment_childs,comment_hide,last_upd, frating, weight() w" . $custom_fields . $order['select'] . $filters_and['select']
                    . " FROM comments WHERE id>0" . $filters_and['filter'] . $match . $order['order'] . " LIMIT %d,%d ", $start, $limit);

            $ret = $this->movie_results($sql, $match, $search_query);

         /*
              print_r($filters_and);
              print_r(array($match, $search_query));
              print_r($sql);
              print_r($ret);

              $meta = $this->sps->query("SHOW META")->fetchAll();
              print_r($meta);
              exit;
          */
            
            gmi('main sql');
            // Simple result
            if (!$show_meta) {
                return $ret['list'];
            }
        }

        // Facets logic
        $facets_arr = array();
        if ($facets) {
            $facets_arr = $this->movies_facets($filters, $match, $search_query, $facets, $query_type, $aid);
        }
        gmi('get facets');

        $ret['facets'] = $facets_arr;
        return $ret;
    }

    
    
    public function get_search_query($keyword = '', $filters = array(), $widlcard = true, $exclude = array()) {
        $search_query = '';

        $keys = array('mkw');
        $custom_query = '';
        foreach ($keys as $key) {

            // Exclude filter
            if (is_array($exclude)) {
                if (in_array($key, $exclude)) {
                    continue;
                }
            } else if ($key == $exclude) {
                continue;
            }

            if ($key == 'mkw') {
                if ($filters['mkw']) {
                    $mkw = $filters['mkw'];
                    if (is_array($mkw)) {
                        $mkw = implode('|', $mkw);
                    }
                    $custom_query .= " @(mkw_str) (" . $mkw . ")";
                }
            }
        }




        //Keywords logic
        if ($keyword) {
            $search_keywords = $this->wildcards_maybe_query($keyword, $widlcard, ' ');
            $search_query = sprintf("'@(title,year) ((^%s$)|(%s))" . $custom_query . "'", $keyword, $search_keywords);
        } else {
            if ($custom_query) {
                $search_query = "'" . $custom_query . "'";
            }
        }
        return $search_query;
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
        $sql = sprintf("SELECT GROUPBY() AS id, mtitle AS title, year_int as year,  weight() w" . $filters_and['select'] . " FROM critic"
                . " WHERE top_movie>0" . $filters_and['filter'] . $match
                . "  GROUP BY top_movie ORDER BY w DESC LIMIT %d,%d ", $start, $limit);

        //Get result
        $ret = $this->movie_results($sql, $match, $search_query);

        // Simple result
        if (!$show_meta) {
            return $ret['list'];
        }

        return $ret;
    }

    public function front_search_actors_list($keyword = '', $limit = 20, $start = 0, $group_field = 'actor_star', $filters = array(), $widlcard = true, $fields = array()) {

        $m_mkw = '';
        if ($filters['mkw']) {
            $mkw = $filters['mkw'];
            if (is_array($mkw)) {
                $mkw = implode('|', $mkw);
            }
            $m_mkw = " @(mkw_str) (" . $mkw . ")";
        }

        if (!isset($filters['type'])) {
            $filters['type'] = array('movies', 'tv');
        }

        //Keywords logic
        $match = '';
        if ($keyword) {
            $search_keywords = $this->wildcards_maybe_query($keyword, $widlcard, ' ');
            $search_query = sprintf("'@(title,year) ((^%s$)|(%s))" . $m_mkw . "'", $keyword, $search_keywords);
            $match = " AND MATCH(:match)";
        } else {
            if ($m_mkw) {
                $search_query = "'" . $m_mkw . "'";
                $match = " AND MATCH(:match)";
            }
        }

        $this->connect();

        // Main logic
        // Filters logic
        $filters_and = $this->get_filters_query($filters);

        // Custom fields
        $custom_fields = '';
        if ($fields) {
            $custom_fields = ', ' . implode(', ', $fields) . ' ';
        }

        $max_option = '';
        $stlimit = $start + $limit;
        if ($stlimit > 1000) {
            $max_option = ' OPTION max_matches=' . $stlimit;
        }
        // Main sql
        $sql = "SELECT GROUPBY() as aid, COUNT(*) as cnt" . $filters_and['select'] . " FROM movie_an WHERE id>0" . $filters_and['filter'] . $this->filter_actor_and . $match
                . " GROUP BY " . $group_field . " ORDER BY cnt DESC, aid DESC LIMIT {$start}, {$limit}" . $max_option;

        $ret = $this->movie_results($sql, $match, $search_query);
        /*
          print_r($filters_and);
          print_r(array($match, $search_query));
          print_r($sql);
          print_r($ret);

          $meta = $this->sps->query("SHOW META")->fetchAll();
          print_r($meta);
          exit;
         */


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

    public function critic_facets($filters, $match, $search_query, $query_type, $facets = true) {
        $facet_list = $this->facets['critics'];
        if (!$facets) {
            $exist_keys = array_keys($this->facet_data['sortdata']['childs']);
            $valid_list = array();
            foreach ($facet_list as $facet) {
                if (in_array($facet, $exist_keys)) {
                    $valid_list[] = $facet;
                }
            }
            $facet_list = $valid_list;
        }
        $sql_arr = $this->critic_facets_sql($facet_list, $filters, $match, $query_type);
        $facets_arr = $this->movies_facets_get($sql_arr, $match, $search_query);
        /* p_r($sql_arr);
          p_r($facets_arr);
          exit; */
        return $facets_arr;
    }

    public function critic_facets_sql($facet_list, $filters, $match, $query_type) {
        $skip = array();
        $sql_arr = array();
        $expand = isset($filters['expand']) ? $filters['expand'] : '';
        $woke_facets = $this->facet_data['wokedata']['childs'];

        foreach ($facet_list as $facet) {
            if (isset($this->facet_data[$facet]['is_parent'])) {
                continue;
            }
            if (isset($this->facet_data[$facet]['no_data'])) {
                continue;
            }

            $curr_facet = isset($this->facets_data[$facet]) ? $this->facets_data[$facet] : array();
            if (isset($curr_facet['no_data'])) {
                continue;
            }

            if ($facet == 'release') {
                $filters_and = $this->get_filters_query($filters, $facet, $query_type);
                $sql_arr[$facet] = "SELECT GROUPBY() as id, COUNT(*) as cnt" . $filters_and['select'] . " FROM critic WHERE id>0 AND year_int>0" . $filters_and['filter'] . $match
                        . " GROUP BY year_int ORDER BY year_int ASC LIMIT 0,200";
            } else if ($facet == 'author') {
                $filters_and = $this->get_filters_query($filters, 'author', $query_type);
                $sql_arr[$facet] = "SELECT GROUPBY() as id, COUNT(*) as cnt" . $filters_and['select'] . " FROM critic WHERE id>0" . $filters_and['filter'] . $match
                        . " GROUP BY author_type ORDER BY cnt DESC LIMIT 0,10";
            } else if ($facet == 'tags') {
                $limit = $expand == 'tags' ? $this->facet_max_limit : $this->facet_limit;
                $filters_and = $this->get_filters_query($filters, 'tags', $query_type);
                $sql_arr[$facet] = "SELECT GROUPBY() as id, COUNT(*) as cnt" . $filters_and['select'] . " FROM critic WHERE id>0" . $filters_and['filter'] . $match
                        . " GROUP BY tags ORDER BY cnt DESC LIMIT 0,$limit";
            } else if ($facet == 'ctags') {
                $limit = $expand == $facet ? $this->facet_max_limit : $this->facet_limit;
                $filters_and = $this->get_filters_query($filters, ['tags','state','status'], $query_type);
                $sql_arr[$facet] = "SELECT GROUPBY() as id, COUNT(*) as cnt" . $filters_and['select'] . " FROM critic WHERE id>0" . $filters_and['filter'] . $match
                        . " GROUP BY {$facet} ORDER BY cnt DESC LIMIT 0,$limit";
            }else if ($facet == 'from') {
                $limit = $expand == 'from' ? $this->facet_max_limit : $this->facet_limit;
                $filters_and = $this->get_filters_query($filters, 'from', $query_type);
                $sql_arr[$facet] = "SELECT GROUPBY() as id, COUNT(*) as cnt" . $filters_and['select'] . " FROM critic WHERE id>0 AND author_type!=2" . $filters_and['filter'] . $match
                        . " GROUP BY aid ORDER BY cnt DESC LIMIT 0,$limit";
            } else if ($facet == 'site') {
                $limit = $expand == $facet ? $this->facet_max_limit : $this->facet_limit;
                $filters_and = $this->get_filters_query($filters, $facet, $query_type);
                $sql_arr[$facet] = "SELECT GROUPBY() as id, COUNT(*) as cnt" . $filters_and['select'] . " FROM critic WHERE id>0 AND author_type!=2" . $filters_and['filter'] . $match
                        . " GROUP BY " . $facet . " ORDER BY cnt DESC LIMIT 0,$limit";
            } else if ($facet == 'genre') {
                $limit = $expand == 'genre' ? $this->facet_max_limit : $this->facet_limit;
                $filters_and = $this->get_filters_query($filters, 'genre', $query_type);
                $sql_arr[$facet] = "SELECT GROUPBY() as id, COUNT(*) as cnt" . $filters_and['select'] . " FROM critic WHERE id>0" . $filters_and['filter'] . $match
                        . " GROUP BY genre ORDER BY cnt DESC LIMIT 0,$limit";
            } else if ($facet == 'type') {
                $filters_and = $this->get_filters_query($filters, 'type', $query_type);
                $sql_arr[$facet] = "SELECT GROUPBY() as id, COUNT(*) as cnt" . $filters_and['select'] . " FROM critic WHERE id>0" . $filters_and['filter'] . $match
                        . " GROUP BY type ORDER BY cnt DESC LIMIT 0,10";
            } else if ($facet == 'state') {
                $filters_facet = $filters;
                unset($filters_facet['state']);
                $filters_and = $this->get_filters_query($filters_facet, 'state', $query_type);

                $sql_arr[$facet] = "SELECT GROUPBY() as id, COUNT(*) as cnt" . $filters_and['select'] . " FROM critic WHERE id>0" . $filters_and['filter'] . $match
                        . " GROUP BY state ORDER BY cnt DESC LIMIT 0,10";
            } else if (isset($woke_facets[$facet])) {

                $active_facet = $woke_facets[$facet];
                if (isset($active_facet['no_data'])) {
                    continue;
                }
                $max_count = isset($active_facet['max_count']) ? $active_facet['max_count'] : 6;
                $exclude_keys = array($facet, 'if_woke');
                if ($facet == 'kmwoke') {
                    // Keywords match woke

                    foreach ($this->search_filters['kmwoke'] as $kkey => $kvalue) {
                        $exclude_keys[] = $kvalue['key'];
                        $exclude_keys[] = $kvalue[$kkey];
                    }

                    foreach ($this->search_filters['kmwoke'] as $kkey => $kvalue) {
                        $ikey = $kvalue['key'];
                        $filters_and = $this->get_filters_query($filters, $exclude_keys);
                        $sql_arr[$ikey] = "SELECT COUNT(*) as cnt" . $filters_and['select'] . " FROM critic WHERE id>0" . $filters_and['filter'] . $match
                                . " AND " . $ikey . ">0";

                        // Childs
                        $child = $kkey;
                        $local_facet = isset($this->facets_data[$child]) ? $this->facets_data[$child] : array();
                        $max_count = isset($local_facet['max_count']) ? $local_facet['max_count'] : 20;
                        $item_collapsed = $this->is_hide_facet($child, $filters);
                        if (!$item_collapsed) {
                            $filters_and = $this->get_filters_query($child, $exclude_keys);
                            $sql_arr[$child] = "SELECT GROUPBY() as id, COUNT(*) as cnt" . $filters_and['select'] . " FROM critic WHERE id>0" . $filters_and['filter'] . $match
                                    . " GROUP BY " . $child . " ORDER BY " . $child . " ASC LIMIT 0," . $max_count;
                        }
                    }
                } else {

                    $filters_and = $this->get_filters_query($filters, $exclude_keys, $query_type);

                    if ($facet == 'rrtg') {
                        $filters_and['filter'] .= " AND rrta>0 AND rrt>0";
                    }
                    if ($facet == 'rmg') {
                        $filters_and['filter'] .= " AND rmu>0 AND rmc>0";
                    }
                    $sql_arr[$facet] = "SELECT GROUPBY() as id, COUNT(*) as cnt" . $filters_and['select'] . " FROM critic WHERE id>0" . $filters_and['filter'] . $match
                            . " GROUP BY " . $facet . " ORDER BY " . $facet . " ASC LIMIT 0," . $max_count;
                }
            } else if ($facet == 'movie') {
                $filters_and = $this->get_filters_query($filters, $facet, $query_type);
                $sql_arr[$facet] = "SELECT GROUPBY() AS id, COUNT(*) as cnt" . $filters_and['select'] . ", mtitle AS title, year_int as year FROM critic"
                        . " WHERE status=1" . $filters_and['filter'] . $match
                        . "  GROUP BY top_movie ORDER BY year_int DESC LIMIT 0,100";
            } else if (isset($this->facet_data['sortdata']['childs'][$facet])) {
                // Sort data                
                $filters_and = $this->get_filters_query($filters, $facet, $query_type);
                $sql_arr[$facet] = "SELECT COUNT(*) as cnt" . $filters_and['select'] . " FROM critic WHERE id>0" . $filters_and['filter'] . $match
                        . " AND " . $facet . ">0";
            }
        }

        // p_r($sql_arr);
        // exit;

        return $sql_arr;
    }

    public function movies_facets($filters, $match, $search_query, $facets, $tab = 'movies', $aid = 0) {
        // All facets
        if ($facets && is_array($facets)) {
            $facet_list = $facets;
            // Find childs
            $parents = array_values($this->facet_parents);
            /* print_r($this->facet_parents);
              print_r($this->facet_all_parents);
              exit; */
            $new_list = array();
            foreach ($facet_list as $facet) {
                if (!$this->is_hide_facet($facet, $filters, $tab) || isset($this->facets_data[$facet]['eid'])) {
                    $new_list[] = $facet;
                    if (in_array($facet, $parents)) {
                        // Add childs
                        foreach ($this->facet_parents as $ckey => $cvalue) {
                            if ($cvalue == $facet) {
                                if (!$this->is_hide_facet($ckey, $filters)) {
                                    $new_list[] = $ckey;
                                }
                            }
                        }
                    }
                }
            }
            $facet_list = $new_list;
        } else {
            $facet_list = $this->facets[$tab];
            if (!$facets) {
                $exist_keys = array_keys($this->facet_data['sortdata']['childs']);
                $valid_list = array();
                foreach ($facet_list as $facet) {
                    if (in_array($facet, $exist_keys)) {
                        $valid_list[] = $facet;
                    }
                }
                $facet_list = $valid_list;
            }

            $show_facets = array();
            foreach ($facet_list as $facet) {
                if (!$this->is_hide_facet($facet, $filters)) {
                    $parent = isset($this->facet_parents[$facet]) ? $this->facet_parents[$facet] : '';
                    if (!$parent || !$this->is_hide_facet($parent, $filters)) {
                        $show_facets[] = $facet;
                    }
                }
            }
            $facet_list = $show_facets;
        }
        // Todo actorsdata childs

        if ($tab == 'filters') {
            $sql_arr = $this->filters_facets_sql($facet_list, $filters, $match, $aid);
        } else if ($tab == 'watchlists') {
            $sql_arr = $this->watchlists_facets_sql($facet_list, $filters, $match, $aid);
        } else if ($tab == 'comments') {
            $sql_arr = $this->comments_facets_sql($facet_list, $filters, $match, $aid);
        }else {
            $sql_arr = $this->movies_facets_sql($facet_list, $filters, $match);
        }
        $facets_arr = $this->movies_facets_get($sql_arr, $match, $search_query);
        /*
          print_r($facet_list);
          print_r($sql_arr);
          print_r(array_keys($facets_arr));
          print_r($facets_arr);
          $meta = $this->sps->query("SHOW META")->fetchAll();
          print_r($meta);
          exit;
         */

        return $facets_arr;
    }

    public function movies_facets_sql($facet_list, $filters, $match) {
        $sql_arr = array();
        $expand = isset($filters['expand']) ? $filters['expand'] : '';

        foreach ($facet_list as $facet) {
            if (isset($this->facet_data[$facet]['is_parent'])) {
                continue;
            }

            if (isset($this->facet_data[$facet]['no_data'])) {
                continue;
            }

            if (isset($this->facet_titles[$facet])) {
                continue;
            }

            $curr_facet = isset($this->facets_data[$facet]) ? $this->facets_data[$facet] : array();
            if (isset($curr_facet['no_data'])) {
                continue;
            }

            if (isset($curr_facet['facet']) && $curr_facet['facet'] == 'rating') {
                // Rating facets                
                $show_facet = true;

                if ($show_facet) {
                    $curr_parents = $this->get_parents($facet);
                    if (in_array('actorsdata', $curr_parents) || in_array('dirsdata', $curr_parents)) {
                        $show_facet = false;
                    }
                }

                if ($show_facet) {
                    $max_count = isset($curr_facet['max_count']) ? $curr_facet['max_count'] : 6;

                    $exclude_keys = array($facet);
                    if (isset($this->facet_data['wokedata']['childs'][$facet])) {
                        $exclude_keys[] = 'if_woke';
                    }
                    $filters_and = $this->get_filters_query($filters, $exclude_keys);

                    if (isset($this->facet_data['findata']['childs'][$facet])) {
                        // Finances facets                   
                        $sql_arr[$facet] = "SELECT GROUPBY() as id, COUNT(*) as cnt" . $filters_and['select'] . ", FLOOR({$facet}/100000)*100 as bgt FROM movie_an"
                                . " WHERE {$facet}>0" . $filters_and['filter'] . $match
                                . " GROUP BY bgt ORDER BY {$facet} ASC LIMIT 0,1000";
                    } else {
                        // All rating facets              
                        if ($facet == 'rrtg') {
                            $filters_and['filter'] .= " AND rrta>0 AND rrt>0";
                        }
                        if ($facet == 'rmg') {
                            $filters_and['filter'] .= " AND rmu>0 AND rmc>0";
                        }
                        $sql_arr[$facet] = "SELECT GROUPBY() as id, COUNT(*) as cnt" . $filters_and['select'] . " FROM movie_an WHERE id>0" . $filters_and['filter'] . $match
                                . " GROUP BY " . $facet . " ORDER BY " . $facet . " ASC LIMIT 0," . $max_count;
                    }
                    // Show and continue
                    continue;
                }
            }

            if ($facet == 'release') {
                $filters_and = $this->get_filters_query($filters, $facet);
                $sql_arr[$facet] = "SELECT GROUPBY() as id, COUNT(*) as cnt" . $filters_and['select'] . " FROM movie_an WHERE year_int>0" . $filters_and['filter'] . $match
                        . " GROUP BY year_int ORDER BY year_int ASC LIMIT 0,200";
            } else if ($facet == 'type') {
                $filters_and = $this->get_filters_query($filters, 'type');
                $and_type = " AND type!='videogame'";
                if (isset($filters['type']) && in_array('videogame', $filters['type'])) {
                    $and_type = '';
                }
                $sql_arr[$facet] = "SELECT GROUPBY() as id, COUNT(*) as cnt" . $filters_and['select'] . " FROM movie_an WHERE id>0" . $and_type . $filters_and['filter'] . $match
                        . " GROUP BY type ORDER BY cnt DESC LIMIT 0,10";
            } else if ($facet == 'country') {
                $limit = $expand == 'country' ? $this->facet_max_limit : $this->facet_limit;
                $filters_and = $this->get_filters_query($filters, 'country');
                $sql_arr[$facet] = "SELECT GROUPBY() as id, COUNT(*) as cnt" . $filters_and['select'] . " FROM movie_an WHERE id>0" . $filters_and['filter'] . $match
                        . " GROUP BY country ORDER BY cnt DESC LIMIT 0,$limit";
            } else if ($facet == 'lang') {
                $limit = $expand == $facet ? $this->facet_max_limit : $this->facet_limit;
                $filters_and = $this->get_filters_query($filters, $facet);
                $sql_arr[$facet] = "SELECT GROUPBY() as id, COUNT(*) as cnt" . $filters_and['select'] . " FROM movie_an WHERE id>0" . $filters_and['filter'] . $match
                        . " GROUP BY " . $facet . " ORDER BY cnt DESC LIMIT 0,$limit";
            } else if ($facet == 'genre') {
                $limit = $expand == $facet ? $this->facet_max_limit : $this->facet_limit;
                $filters_and = $this->get_filters_query($filters, 'genre');
                $sql_arr[$facet] = "SELECT GROUPBY() as id, COUNT(*) as cnt" . $filters_and['select'] . " FROM movie_an WHERE id>0" . $filters_and['filter'] . $match
                        . " GROUP BY genre ORDER BY cnt DESC LIMIT 0,$limit";
            } else if ($facet == 'platform') {
                $limit = $expand == $facet ? $this->facet_max_limit : $this->facet_limit;
                $filters_and = $this->get_filters_query($filters, $facet);
                $sql_arr[$facet] = "SELECT GROUPBY() as id, COUNT(*) as cnt" . $filters_and['select'] . " FROM movie_an WHERE id>0" . $filters_and['filter'] . $match
                        . " GROUP BY {$facet} ORDER BY cnt DESC LIMIT 0,$limit";
            } else if ($facet == 'mkw') {
                $limit = $expand == 'mkw' ? $this->facet_max_limit : $this->facet_limit;
                $filters_and = $this->get_filters_query($filters, array('mkw'), 'movies', $facet);
                $max_option = '';
                if ($limit > 1000) {
                    $max_option = ' OPTION max_matches=' . $limit;
                }
                $sql_arr[$facet] = "SELECT GROUPBY() as id, COUNT(*) as cnt" . $filters_and['select'] . " FROM movie_an WHERE id>0" . $filters_and['filter'] . $match
                        . " GROUP BY " . $facet . " ORDER BY cnt DESC LIMIT 0,$limit" . $max_option;
            } else if ($facet == 'franchise') {
                $limit = $expand == 'franchise' ? $this->facet_max_limit : $this->facet_limit;
                $filters_and = $this->get_filters_query($filters, array('franchise'), 'movies', $facet);
                $max_option = '';
                if ($limit > 1000) {
                    $max_option = ' OPTION max_matches=' . $limit;
                }
                $sql_arr[$facet] = "SELECT GROUPBY() as id, COUNT(*) as cnt" . $filters_and['select'] . " FROM movie_an WHERE id>0" . $filters_and['filter'] . $match
                        . " GROUP BY " . $facet . " ORDER BY cnt DESC LIMIT 0,$limit" . $max_option;
            } else if ($facet == 'distributor' || $facet == 'production') {
                $limit = $expand == $facet ? $this->facet_max_limit : $this->facet_limit;
                $filters_and = $this->get_filters_query($filters, array($facet), 'movies', $facet);
                $max_option = '';
                if ($limit > 1000) {
                    $max_option = ' OPTION max_matches=' . $limit;
                }
                $sql_arr[$facet] = "SELECT GROUPBY() as id, COUNT(*) as cnt" . $filters_and['select'] . " FROM movie_an WHERE id>0" . $filters_and['filter'] . $match
                        . " GROUP BY " . $facet . " ORDER BY cnt DESC LIMIT 0,$limit" . $max_option;
            } else if ($facet == 'provider') {
                $limit = $this->facet_max_limit;
                $filters_and = $this->get_filters_query($filters, array('provider', 'price'));
                $sql_arr[$facet] = "SELECT GROUPBY() as id, COUNT(*) as cnt" . $filters_and['select'] . " FROM movie_an WHERE id>0" . $filters_and['filter'] . $match
                        . " GROUP BY provider ORDER BY cnt DESC LIMIT 0,$limit";

                // Provider free
                $facet_free = 'providerfree';
                $sql_arr[$facet_free] = "SELECT GROUPBY() as id, COUNT(*) as cnt" . $filters_and['select'] . " FROM movie_an WHERE id>0" . $filters_and['filter'] . $match
                        . " GROUP BY providerfree ORDER BY cnt DESC LIMIT 0,$limit";
            } else if (isset($this->facet_data['wokedata']['childs'][$facet])) {
                $exclude_keys = array('if_woke');
                if ($facet == 'kmwoke') {
                    // Keywords match woke

                    foreach ($this->search_filters['kmwoke'] as $kkey => $kvalue) {
                        $exclude_keys[] = $kvalue['key'];
                        $exclude_keys[] = $kvalue[$kkey];
                    }

                    foreach ($this->search_filters['kmwoke'] as $kkey => $kvalue) {
                        $ikey = $kvalue['key'];
                        $filters_and = $this->get_filters_query($filters, $exclude_keys);
                        $sql_arr[$ikey] = "SELECT COUNT(*) as cnt" . $filters_and['select'] . " FROM movie_an WHERE id>0" . $filters_and['filter'] . $match
                                . " AND " . $ikey . ">0";

                        // Childs
                        $child = $kkey;
                        $local_facet = isset($this->facets_data[$child]) ? $this->facets_data[$child] : array();
                        $max_count = isset($local_facet['max_count']) ? $local_facet['max_count'] : 20;
                        $item_collapsed = $this->is_hide_facet($child, $filters);
                        if (!$item_collapsed) {
                            $filters_and = $this->get_filters_query($child, $exclude_keys);
                            $sql_arr[$child] = "SELECT GROUPBY() as id, COUNT(*) as cnt" . $filters_and['select'] . " FROM movie_an WHERE id>0" . $filters_and['filter'] . $match
                                    . " GROUP BY " . $child . " ORDER BY " . $child . " ASC LIMIT 0," . $max_count;
                        }
                    }
                } else {
                    // Other facets
                    $max_count = isset($curr_facet['max_count']) ? $curr_facet['max_count'] : 10;
                    $exclude_keys[] = $facet;
                    $filters_and = $this->get_filters_query($filters, $exclude_keys);

                    $sql_arr[$facet] = "SELECT GROUPBY() as id, COUNT(*) as cnt" . $filters_and['select'] . " FROM movie_an WHERE id>0" . $filters_and['filter'] . $match
                            . " GROUP BY " . $facet . " ORDER BY " . $facet . " ASC LIMIT 0," . $max_count;
                }
            } else if ($facet == 'isfranchise' || $facet == 'reboot' || $facet == 'remake' || $facet == 'sequel' || $facet == 'prequel') {
                $filters_and = $this->get_filters_query($filters, 'indie');
                $sql_arr[$facet] = "SELECT 1 as id, COUNT(*) as cnt" . $filters_and['select'] . " FROM movie_an WHERE id>0" . $filters_and['filter'] . $match
                        . " AND " . $facet . "=1 ORDER BY cnt DESC LIMIT 1";
            } else if (in_array($facet, array('bigdist', 'meddist', 'indidist'))) {
                $filters_and = $this->get_filters_query($filters, 'indie');
                $sql_arr[$facet] = "SELECT 1 as id, COUNT(*) as cnt" . $filters_and['select'] . " FROM movie_an WHERE id>0" . $filters_and['filter'] . $match
                        . " AND {$facet}=1 ORDER BY cnt DESC LIMIT 1";
            } else if ($facet == 'movie') {
                $filters_and = $this->get_filters_query($filters, 'movie');
                $sql_arr[$facet] = "SELECT id, title, year_int as year FROM movie_an WHERE id>0" . $filters_and['filter'] . $match
                        . " ORDER BY year DESC LIMIT 0,100";
            } else if (isset($this->facet_data['sortdata']['childs'][$facet])) {
                // Sort data                
                $filters_and = $this->get_filters_query($filters, $facet);
                $sql_arr[$facet] = "SELECT COUNT(*) as cnt" . $filters_and['select'] . " FROM movie_an WHERE id>0" . $filters_and['filter'] . $match
                        . " AND " . $facet . ">0";
            } else {
                $curr_parents = $this->get_parents($facet);
                $actors_facet = '';
                if (in_array('actorsdata', $curr_parents)) {
                    // Actors facet
                    $actors_facet = 'cast';
                } else if (in_array('dirsdata', $curr_parents)) {
                    // Directors facet
                    $actors_facet = 'director';
                }
                if ($actors_facet) {
                    /*
                      [0] => esaw
                      [1] => esaea
                      [2] => esah
                      [3] => esab
                      [4] => esai
                      [5] => esam
                      [6] => esamix
                      [7] => esajw
                      [8] => esma
                      [9] => esfa
                      [10] => sphoto
                      [11] => simstar
                      [12] => countrystar
                     */

                    // Race actor logic               
                    $active_tab_name = isset($filters[$actors_facet]) ? $filters[$actors_facet] : $this->facets_data[$actors_facet]['def-tab'];
                    $active_tab = $this->facets_data[$actors_facet]['childs'][$active_tab_name];
                    $race_facets = isset($this->actorscache[$actors_facet]['exist'][$active_tab_name]) ? $this->actorscache[$actors_facet]['exist'][$active_tab_name] : array();
                    $childs = array_keys($active_tab['childs']);

                    if (in_array($facet, $childs)) {

                        $cvalue = $active_tab['childs'][$facet];
                        $cparent = isset($cvalue['parent']) ? $cvalue['parent'] : '';

                        if ($cparent == 'race') {
                            // Race facets
                            if ($race_facets) {

                                $exclude_keys = array_merge(array_merge(array_keys($this->actorscache[$actors_facet]['exist']['all']['all']), array_keys($this->actorscache[$actors_facet]['exist']['star']['all'])), array_keys($this->actorscache[$actors_facet]['exist']['main']['all']));
                                $exclude_percent = array_merge(array_merge(array_keys($this->actorscache[$actors_facet]['percent']['all']['all']), array_keys($this->actorscache[$actors_facet]['percent']['star']['all'])), array_keys($this->actorscache[$actors_facet]['percent']['main']['all']));
                                $exclude_keys = array_merge($exclude_keys, $exclude_percent);
                                foreach ($race_facets['all'] as $rkey => $rval) {
                                    $race = $rval['race'];
                                    if ($race == 'a') {
                                        continue;
                                    }
                                    $filters_and = $this->get_filters_query($filters, $exclude_keys);
                                    // exist sql
                                    $sql_arr[$rkey] = "SELECT COUNT(*) as cnt" . $filters_and['select'] . " FROM movie_an WHERE id>0" . $filters_and['filter'] . $match
                                            . " AND " . $rkey . ">0";

                                    $item_collapsed = $this->is_hide_facet($rkey, $filters);
                                    if (!$item_collapsed) {
                                        $childs = isset($rval['childs']) ? $rval['childs'] : array();
                                        if ($childs) {
                                            $limit = 100;
                                            foreach ($childs as $child => $child_val) {
                                                $sql_arr[$child] = "SELECT GROUPBY() as id, COUNT(*) as cnt" . $filters_and['select'] . " FROM movie_an WHERE id>0" . $filters_and['filter'] . $match
                                                        . " GROUP BY " . $child . " ORDER BY " . $child . " ASC LIMIT 0,{$limit}";
                                            }
                                        }
                                    }
                                }
                            }
                        } else if ($cparent == 'gender') {
                            // Gender actor logic
                            if ($race_facets) {
                                $gender_arr = array('male', 'female');
                                foreach ($gender_arr as $gender) {
                                    $exclude_keys = array_merge(array_merge(array_keys($this->actorscache[$actors_facet]['exist']['all'][$gender]), array_keys($this->actorscache[$actors_facet]['exist']['star'][$gender])), array_keys($this->actorscache[$actors_facet]['exist']['main'][$gender]));
                                    $exclude_percent = array_merge(array_merge(array_keys($this->actorscache[$actors_facet]['percent']['all'][$gender]), array_keys($this->actorscache[$actors_facet]['percent']['star'][$gender])), array_keys($this->actorscache[$actors_facet]['percent']['main'][$gender]));
                                    $exclude_keys = array_merge($exclude_keys, $exclude_percent);

                                    foreach ($race_facets[$gender] as $rkey => $rval) {
                                        $race = $rval['race'];
                                        if ($race == 'a') {
                                            $filters_and = $this->get_filters_query($filters, $exclude_keys);
                                            // exist sql
                                            $sql_arr[$rkey] = "SELECT COUNT(*) as cnt" . $filters_and['select'] . " FROM movie_an WHERE id>0" . $filters_and['filter'] . $match
                                                    . " AND " . $rkey . ">0";

                                            $item_collapsed = $this->is_hide_facet($rkey, $filters);
                                            if (!$item_collapsed) {
                                                $childs = isset($rval['childs']) ? $rval['childs'] : array();
                                                if ($childs) {
                                                    $limit = 100;
                                                    foreach ($childs as $child => $child_val) {
                                                        $sql_arr[$child] = "SELECT GROUPBY() as id, COUNT(*) as cnt" . $filters_and['select'] . " FROM movie_an WHERE id>0" . $filters_and['filter'] . $match
                                                                . " GROUP BY " . $child . " ORDER BY " . $child . " ASC LIMIT 0,{$limit}";
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        } else if ($cparent == 'castphoto') {
                            // Star photo
                            $limit = 2;
                            $filters_and = $this->get_filters_query($filters, $facet);
                            $sql_arr[$facet] = "SELECT GROUPBY() as id, COUNT(*) as cnt" . $filters_and['select'] . " FROM movie_an WHERE id>0" . $filters_and['filter'] . $this->filter_actor_and . $match
                                    . " GROUP BY " . $facet . " ORDER BY cnt DESC LIMIT 0,$limit";
                        } else if ($cparent == 'actors' || $cparent == 'dirs') {
                            // Cast actor logic
                            $limit = $expand == $facet ? $this->facet_max_limit : $this->facet_limit;
                            $filters_and = $this->get_filters_query($filters, $cvalue['filter']);
                            $sql_arr[$facet] = "SELECT GROUPBY() as id, COUNT(*) as cnt" . $filters_and['select'] . " FROM movie_an WHERE id>0" . $filters_and['filter'] . $this->filter_actor_and . $match
                                    . " GROUP BY " . $cvalue['filter'] . " ORDER BY cnt DESC LIMIT 0,$limit";
                        } else if ($cparent == 'simpson') {
                            // Simpson actor logic
                            $max_count = isset($cvalue['max_count']) ? $cvalue['max_count'] : 110;
                            $filters_and = $this->get_filters_query($filters, $facet);
                            $sql_arr[$facet] = "SELECT GROUPBY() as id, COUNT(*) as cnt" . $filters_and['select'] . " FROM movie_an WHERE id>0" . $filters_and['filter'] . $match
                                    . " GROUP BY " . $facet . " ORDER BY " . $facet . " ASC LIMIT 0," . $max_count;
                        } else if ($cparent == 'actorscountry') {
                            // Country actor logic                                             
                            $limit = $expand == $cvalue['filter'] ? $this->facet_max_limit : $this->facet_limit;
                            $filters_and = $this->get_filters_query($filters, $facet);
                            $sql_arr[$facet] = "SELECT GROUPBY() as id, COUNT(*) as cnt" . $filters_and['select'] . " FROM movie_an WHERE id>0" . $filters_and['filter'] . $match
                                    . " GROUP BY " . $facet . " ORDER BY cnt DESC LIMIT 0,$limit";
                        }
                    }
                }
            }
        }

        return $sql_arr;
    }

    public function filters_facets_sql($facet_list, $filters, $match, $aid = 0) {
        $sql_arr = array();
        $expand = isset($filters['expand']) ? $filters['expand'] : '';

        foreach ($facet_list as $facet) {
            if (isset($this->facet_data[$facet]['is_parent'])) {
                continue;
            }

            if (isset($this->facet_data[$facet]['no_data'])) {
                continue;
            }

            if (isset($this->facet_titles[$facet])) {
                continue;
            }

            if ($facet == 'ftab') {
                $limit = $expand == $facet ? $this->facet_max_limit : $this->facet_limit;
                $filters_and = $this->get_filters_query($filters, $facet, 'filters', '', $aid);
                $sql_arr[$facet] = "SELECT GROUPBY() as id, COUNT(*) as cnt" . $filters_and['select'] . " FROM filters WHERE id>0" . $filters_and['filter'] . $match
                        . " GROUP BY " . $facet . " ORDER BY " . $facet . " ASC LIMIT 0," . $limit;
            } else if ($facet == 'from') {
                $limit = $expand == 'from' ? $this->facet_max_limit : $this->facet_limit;
                $filters_and = $this->get_filters_query($filters, $facet, 'filters', '', $aid);
                $sql_arr[$facet] = "SELECT GROUPBY() as id, COUNT(*) as cnt" . $filters_and['select'] . " FROM filters WHERE id>0" . $filters_and['filter'] . $match
                        . " GROUP BY aid ORDER BY cnt DESC LIMIT 0," . $limit;
            }
        }

        return $sql_arr;
    }

    public function watchlists_facets_sql($facet_list, $filters, $match, $aid = 0) {
        $sql_arr = array();
        $expand = isset($filters['expand']) ? $filters['expand'] : '';

        foreach ($facet_list as $facet) {
            if (isset($this->facet_data[$facet]['is_parent'])) {
                continue;
            }

            if (isset($this->facet_data[$facet]['no_data'])) {
                continue;
            }

            if (isset($this->facet_titles[$facet])) {
                continue;
            }

            if ($facet == 'from') {
                $limit = $expand == 'from' ? $this->facet_max_limit : $this->facet_limit;
                $filters_and = $this->get_filters_query($filters, $facet, 'filters', '', $aid);
                $sql_arr[$facet] = "SELECT GROUPBY() as id, COUNT(*) as cnt" . $filters_and['select'] . " FROM watchlists WHERE id>0" . $filters_and['filter'] . $match
                        . " GROUP BY aid ORDER BY cnt DESC LIMIT 0," . $limit;
            }
        }

        return $sql_arr;
    }
    
    public function comments_facets_sql($facet_list, $filters, $match, $aid = 0) {
        $sql_arr = array();
        $expand = isset($filters['expand']) ? $filters['expand'] : '';

        foreach ($facet_list as $facet) {
            if (isset($this->facet_data[$facet]['is_parent'])) {
                continue;
            }

            if (isset($this->facet_data[$facet]['no_data'])) {
                continue;
            }

            if (isset($this->facet_titles[$facet])) {
                continue;
            }

            if ($facet == 'from') {
                $limit = $expand == 'from' ? $this->facet_max_limit : $this->facet_limit;
                $filters_and = $this->get_filters_query($filters, $facet, 'comments', '', $aid);
                $sql_arr[$facet] = "SELECT GROUPBY() as id, COUNT(*) as cnt" . $filters_and['select'] . " FROM comments WHERE id>0" . $filters_and['filter'] . $match
                        . " GROUP BY aid ORDER BY cnt DESC LIMIT 0," . $limit;
            } else if ($facet == 'ctype') {
                $limit = $expand == 'ctype' ? $this->facet_max_limit : $this->facet_limit;
                $filters_and = $this->get_filters_query($filters, $facet, 'comments', '', $aid);
                $sql_arr[$facet] = "SELECT GROUPBY() as id, COUNT(*) as cnt" . $filters_and['select'] . " FROM comments WHERE id>0" . $filters_and['filter'] . $match
                        . " GROUP BY {$facet} ORDER BY cnt DESC LIMIT 0," . $limit;
            } else if ($facet == 'cstatus') {
                $limit = $expand == 'cstatus' ? $this->facet_max_limit : $this->facet_limit;
                $filters_and = $this->get_filters_query($filters, $facet, 'comments', '', $aid);
                $sql_arr[$facet] = "SELECT GROUPBY() as id, COUNT(*) as cnt" . $filters_and['select'] . " FROM comments WHERE id>0" . $filters_and['filter'] . $match
                        . " GROUP BY {$facet} LIMIT 0," . $limit;
            }
                      
        }
        
        return $sql_arr;
    }
    
    public function movies_facets_get($sql_dict, $match, $search_query) {
        $facets_arr = array();
        $sql_arr = array();
        if ($sql_dict) {
            foreach ($sql_dict as $facet => $sql) {
                $sql_arr[] = $sql;
                $sql_arr[] = "SHOW META";
            }
        }

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
            foreach ($sql_dict as $facet => $sql) {
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
            $simple_facets = array_keys($this->facet_data['wokedata']['childs']);
            $curr_facet = isset($this->facets_data[$sort_key]) ? $this->facets_data[$sort_key] : array();
            if ($sort_key == 'id') {
                $order = ' ORDER BY id ' . $sort_type;
            } else if ($sort_key == 'title') {
                $order = ' ORDER BY title ' . $sort_type;
            } else if ($sort_key == 'date') {
                $order = ' ORDER BY post_date ' . $sort_type;
            } else if ($sort_key == 'rel') {
                $order = ' ORDER BY w ' . $sort_type;
            } else if ($sort_key == 'id') {
                $order = ' ORDER BY id ' . $sort_type;
            } else if ($sort_key == 'mw') {
                $order = ' ORDER BY id DESC';
            } else if ($sort_key == 'random') {
                $order = ' ORDER BY RAND()';
            } /*else if (isset($curr_facet['sort_zero']) && $curr_facet['sort_zero'] == 1) {
                if ($curr_facet['sort_exist'] && $curr_facet['eid']) {
                    $parent = $curr_facet['eid'];
                    $order = ' ORDER BY ' . $sort_key . '_valid ASC';
                    $select .= ', IF(' . $sort_key . '>=0 AND ' . $parent . '=1, ' . $sort_key . ', 999) as ' . $sort_key . '_valid';
                } else {
                    $order = ' ORDER BY ' . $sort_key . ' ASC';
                }
            }*/ else if (in_array($sort_key, $simple_facets) || $sort_key == 'emotions' || $sort_key == 'aurating') {
                if ($sort_key == 'emotions') {
                    $sort_key = 'pop';
                }
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

    private function get_order_query_filters($sort = array()) {
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

            if ($sort_key == 'id') {
                $order = ' ORDER BY id ' . $sort_type;
            } else if ($sort_key == 'title') {
                $order = ' ORDER BY title ' . $sort_type;
            } else if ($sort_key == 'date') {
                $order = ' ORDER BY date ' . $sort_type;
            } else if ($sort_key == 'frating') {
                $order = ' ORDER BY frating ' . $sort_type;
            } else if ($sort_key == 'rel') {
                $order = ' ORDER BY w ' . $sort_type;
            } else if ($sort_key == 'id') {
                $order = ' ORDER BY id ' . $sort_type;
            } else if ($sort_key == 'mw') {
                $order = ' ORDER BY id DESC';
            } else if ($sort_key == 'random') {
                $order = ' ORDER BY RAND()';
            }
        } else {
            // Default weight
            $order = ' ORDER BY w DESC';
        }
        return array('order' => $order, 'select' => $select);
    }

    public function get_order_query($sort = array(), $filters = array()) {
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

            // $simple_facets = array_keys($this->facet_data['ratings']['childs']);
            // $simple_facets = array_merge($simple_facets, array_keys($this->facet_data['popdata']['childs']));
            $simple_facets = array_keys($this->facet_data['popdata']['childs']);
            // $simple_facets = array_merge($simple_facets, array_keys($this->facet_data['findata']['childs']));
            $simple_facets = array_merge($simple_facets, array_keys($this->facet_data['wokedata']['childs']));
            // $simple_facets = array_merge($simple_facets, array('simall', 'simstar', 'simmain'));

            $curr_facet = isset($this->facets_data[$sort_key]) ? $this->facets_data[$sort_key] : array();

            if (isset($curr_facet['facet']) && $curr_facet['facet'] == 'rating') {
                $select = ", " . $sort_key . " as sortval";
                // Any ratings 
                if ($sort_type == 'DESC') {
                    $order = ' ORDER BY ' . $sort_key . ' DESC';
                } else {
                    if (isset($curr_facet['sort_zero']) && $curr_facet['sort_zero'] == 1) {
                        if ($curr_facet['sort_exist'] && $curr_facet['eid']) {
                            $parent = $curr_facet['eid'];
                            $order = ' ORDER BY ' . $sort_key . '_valid ASC';
                            $select .= ', IF(' . $sort_key . '>=0 AND ' . $parent . '=1, ' . $sort_key . ', 999) as ' . $sort_key . '_valid';
                        } else {
                            $order = ' ORDER BY ' . $sort_key . ' ASC';
                        }
                    } else {
                        $order = ' ORDER BY ' . $sort_key . '_valid ASC';
                        $select .= ', IF(' . $sort_key . '>0, ' . $sort_key . ', 999) as ' . $sort_key . '_valid';
                    }
                }

                if (isset($curr_facet['sort_second'])) {
                    $sort_second = $curr_facet['sort_second'];
                    $second_facet = isset($this->facets_data[$sort_second]) ? $this->facets_data[$sort_second] : array();
                    if ($second_facet) {
                        $select .= ", " . $sort_second . " as sortsecond";
                        $order .= ', ' . $sort_second . ' DESC';
                    }
                }
            } else if (isset($curr_facet['sort_zero']) && $curr_facet['sort_zero'] == 1) {
                $select = ", " . $sort_key . " as sortval";
                $order = ' ORDER BY ' . $sort_key . ' ' . $sort_type;
            } else if ($sort_key == 'title') {
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
            } else if ($sort_key == 'cast') {

                if (isset($filters['actor'])) {
                    if (is_array($filters['actor'])) {
                        $actor_id = (int) $filters['actor'][0];
                    } else {
                        $actor_id = (int) $filters['actor'];
                    }
                    $select = ', IF(IN(actor_star, ' . $actor_id . '),3,IF(IN(actor_main, ' . $actor_id . '),2,1)) AS cast_score';

                    $order = ' ORDER BY cast_score ' . $sort_type . ', rrwt DESC, year_int DESC';
                }
            } else if ($sort_key == 'id') {
                $order = ' ORDER BY id ' . $sort_type;
            } else if ($sort_key == 'rating') {
                $order = ' ORDER BY rating ' . $sort_type;
            } else if ($sort_key == 'random') {
                $order = ' ORDER BY RAND()';
            } else if ($sort_key == 'div') {
                if ($sort_type == 'DESC') {
                    $order = ' ORDER BY diversity DESC';
                } else {
                    $order = ' ORDER BY diversity_valid ASC';
                    $select = ', IF(diversity>=0, diversity, 999) as diversity_valid';
                }
            } else if ($sort_key == 'fem') {
                if ($sort_type == 'DESC') {
                    $order = ' ORDER BY female DESC';
                } else {
                    $order = ' ORDER BY female_valid ASC';
                    $select = ', IF(female>0, female, 999) as female_valid';
                }
            } else if (in_array($sort_key, $simple_facets)) {
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

    public function get_default_release() {
        $release = '0-' . (date('Y', time()) + 1);
        return $release;
    }

    public function get_filter_titles($key, $value) {
        if ($key == 'mkw') {
            $value = is_array($value) ? $value : array($value);

            $titles = $this->get_keywords_titles($value, true);
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
                $this->search_filters['genre'][$slug] = array('key' => $genre->id, 'title' => $genre->name);
            }
        } else if ($key == 'platform') {
            // Platform
            $ma = $this->get_ma();
            $value = is_array($value) ? $value : array($value);
            foreach ($value as $slug) {
                $genre = $ma->get_platform_by_slug($slug, true);
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
        } else if ($key == 'countryall' || $key == 'countrystar' || $key == 'countrymain') {
            // Actor Country     
            $ma = $this->get_ma();
            $value = is_array($value) ? $value : array($value);
            foreach ($value as $slug) {
                $country = $ma->get_country_by_slug($slug, true);
                $this->search_filters[$key][$slug] = array('key' => $country->id, 'title' => $country->name);
            }
        } else if ($key == 'actor' || $key == 'actorstar' || $key == 'actormain') {
            // Actor       
            $value = is_array($value) ? $value : array($value);
            $names = $this->get_actor_names($value, true);
            foreach ($value as $id) {
                $this->search_filters[$key][$id] = array('key' => $id, 'title' => $names[$id]);
            }
        } else if ($key == 'dirall' || $key == 'dir' || $key == 'dirwrite' || $key == 'dircast' || $key == 'dirprod') {
            // Director  
            $value = is_array($value) ? $value : array($value);
            $names = $this->get_actor_names($value, true);
            foreach ($value as $id) {
                $this->search_filters[$key][$id] = array('key' => $id, 'title' => $names[$id]);
            }
        } else if ($key == 'country') {
            // Country
            $ma = $this->get_ma();
            $value = is_array($value) ? $value : array($value);
            foreach ($value as $slug) {
                $country = $ma->get_country_by_slug($slug, true);
                $this->search_filters[$key][$slug] = array('key' => $country->id, 'title' => $country->name);
            }
        } else if ($key == 'lang') {
            // Language
            $ma = $this->get_ma();
            $value = is_array($value) ? $value : array($value);
            foreach ($value as $slug) {
                $country = $ma->get_lanuage_by_slug($slug, true);
                $this->search_filters[$key][$slug] = array('key' => $country->id, 'title' => $country->title);
            }
        } else if ($key == 'from') {
            // From author
            $value = is_array($value) ? $value : array($value);
            $authors = $this->cm->get_authors_by_ids($value, true);
            foreach ($value as $slug) {
                // Todo get author by slug
                $this->search_filters[$key][$slug] = array('key' => $slug, 'title' => $authors[$slug]->name);
            }
        } else if ($key == 'site') {
            // From site
            $value = is_array($value) ? $value : array($value);
            $sites = $this->cm->get_post_links(true);
            foreach ($value as $slug) {
                // Todo get author by slug
                $title = $sites[$slug];
                if (!$title) {
                    $title = 'none';
                }
                $this->search_filters[$key][$slug] = array('key' => $slug, 'title' => $title);
            }
        } else if ($key == 'tags') {
            // Tags                       
            $value = is_array($value) ? $value : array($value);
            foreach ($value as $slug) {
                $tag = $this->cm->get_tag_by_slug($slug, true);
                $this->search_filters[$key][$slug] = array('key' => $tag->id, 'title' => $tag->name);
            }
        } else if ($key == 'ctags') {
            // Tags                       
            $value = is_array($value) ? $value : array($value);
            foreach ($value as $slug) {
                $tag = $this->cm->get_camp_tag_by_slug($slug, true);
                $this->search_filters[$key][$slug] = array('key' => $tag->id, 'title' => $tag->name);
            }
        } else if ($key == 'movie') {
            // Movie                 
            $value = is_array($value) ? $value : array($value);
            $names = $this->get_movie_names($value, true);

            foreach ($value as $id) {
                $this->search_filters[$key][$id] = array('key' => $id, 'title' => $names[$id]);
            }
        } else if ($key == 'franchise') {
            $value = is_array($value) ? $value : array($value);
            $titles = $this->get_franchise_titles($value);
            if ($titles) {
                foreach ($titles as $slug => $title) {
                    $this->search_filters[$key][$slug] = array('key' => $slug, 'title' => $title);
                }
            }
        } else if ($key == 'distributor' || $key == 'production') {
            $value = is_array($value) ? $value : array($value);
            $titles = $this->get_distributor_titles($value);
            if ($titles) {
                foreach ($titles as $slug => $title) {
                    $this->search_filters[$key][$slug] = array('key' => $slug, 'title' => $title);
                }
            }
        }
    }

    public function in_exclude($key, $exlude) {
        $ret = false;
        if (is_array($exlude)) {
            if (in_array($key, $exlude)) {
                $ret = true;
            }
        } else if ($key == $exlude) {
            $ret = true;
        }
        return $ret;
    }

    public function get_filters_query($filters = array(), $exlude = array(), $query_type = 'movies', $curr_filter = '', $aid = 0) {
        // Filters logic
        $filters_and = array();
        $select_and = array();
  
        if ($query_type == 'filters') {
            $select_and['upub'] = "IF(publish OR aid={$aid},1,0) as upub";
            $filters_and['upub'] = "upub=1";
        } else if ($query_type == 'watchlists') {
            $select_and['upub'] = "IF((publish AND items>0) OR aid={$aid},1,0) as upub";
            $filters_and['upub'] = "upub=1";
        } else if ($query_type == 'comments') {                        
            if (!$this->cm->is_admin()){
                // Show only publish posts for all users
                $select_and['upub'] = "IF(cstatus='1' OR IF(cstatus='0' AND aid={$aid},1,0),1,0) as upub";
                $filters_and['upub'] = "upub=1";
            } else {
                // Admin default view
                if (!$filters['cstatus']){
                    $filters['cstatus']=array('approve','pending');
                }                
            }     
            
        } else {
            if (!isset($filters['release'])) {
                $filters['release'] = $this->get_default_release();
            }
        }

        if ($query_type == 'critics') {
            #$top_movie_sql = "top_movie>0";

            if (!isset($filters['state'])) {
                $filters['state'] = array('related', 'contains', 'proper');
            }
            
            // Status
            if (!isset($filters['status'])) {
                $filters_and['status'] = $this->filter_multi_value('status', 1);
            }           
            
            
            if ($this->in_exclude('state', $exlude)) {
                unset($filters['state']);
            }
            
            if ($this->in_exclude('status', $exlude)) {
                $filters_and['status'] = $this->filter_multi_value('status', array(0,1));
            }

            if (isset($filters['state'])) {

                if (is_array($filters['state']) && sizeof($filters['state']) == 1) {
                    $filters['state'] = $filters['state'][0];
                }

                if (is_array($filters['state'])) {
                    $filters_and['state'] = $this->filter_multi_value('state', $filters['state'], true);
                } else {
                    $filters_and['state'] = $this->filter_multi_value('state', $filters['state'], true);
                }
            }
            #$filters_and['top_movie'] = $top_movie_sql;
        }

        if ($query_type == 'movies' || $query_type == 'games') {
            $filters_and['title'] = "title!=''";
        }

        if (sizeof($filters)) {
            foreach ($filters as $key_data => $value) {

                $filter = $this->getSearchFilter($key_data);
                $key = $filter->filter;
                $minus = $filter->minus;
                $and = $filter->and;
                $or = $filter->or;

                // Get current facet                
                $curr_facet = isset($this->facets_data[$key]) ? $this->facets_data[$key] : array();
                if (isset($curr_facet['type']) && $curr_facet['type'] == 'tabs') {
                    continue;
                }

                // Get titles
                $this->get_filter_titles($key, $value);

                // Exclude filter
                if (!$and) {
                    if ($this->in_exclude($key, $exlude)) {
                        continue;
                    }
                }

                if ($query_type == 'filters' || $query_type == 'watchlists') {
                    // Filters
                    if ($key == 'ftab') {
                        // Ftab
                        $filters_and[$key] = $this->filter_multi_value($key, $value);
                    } else if ($key == 'from') {
                        // From author
                        $filters_and[$key] = $this->filter_multi_value('aid', $value, true);
                    }
                    continue;
                } else if ($query_type == 'comments') {
                    
                    if ($key == 'from') {
                        // From author
                        $filters_and[$key] = $this->filter_multi_value('aid', $value, true);
                    }if ($key == 'ctype') {
                        // Comment type
                        $filters_and[$key] = $this->filter_multi_value('ctype', $value);
                    }if ($key == 'cstatus') {
                        // Comment status
                        if ($this->cm->is_admin()){
                            $force_string=true;
                            $filters_and[$key] = $this->filter_multi_value('cstatus', $value, false, false, true,  true,  false,  false, $force_string);
                        }
                    }
                    continue;
                } else if ($query_type == 'critics') {

                    if ($key == 'author') {
                        // Author
                        $filters_and[$key] = $this->filter_multi_value('author_type', $value);
                    } else if ($key == 'from') {
                        // From author
                        $filters_and[$key] = $this->filter_multi_value('aid', $value, true);
                    } else if ($key == 'site') {
                        // From author
                        $filters_and[$key] = $this->filter_multi_value($key, $value, true);
                    } else if ($key == 'tags') {
                        // Tags                       
                        $filters_and[$key] = $this->filter_multi_value($key, $value, true);
                    }  else if ($key == 'ctags') {                        
                        // Tags                   
                        unset($filters_and['state']);                        
                        $filters_and['status'] = $this->filter_multi_value('status', array(0,1));
                        $filters_and[$key] = $this->filter_multi_value($key, $value, true);
                    } else if ($key == 'state') {
                        // Type
                        // $filters_and[]= $this->filter_multi_value('state', $value);
                    } else if ($key == 'movie') {
                        // Movie                 
                        $filters_and[$key] = $this->filter_multi_value('movies', $value, true);
                    }
                } else {
                    // Movies, Games, Analytics
                    if ($key == 'wl') {
                        // Watchlist
                        // Get watchlist
                        $wl = $this->cm->get_wl();
                        $value_int = is_array($value) ? array_pop($value) : $value;
                        $wp_uid = isset($filters['wp_uid']) ? $filters['wp_uid'] : 0;
                        $ids = $wl->get_list_movies($wp_uid, $value_int);
                        if ($ids) {
                            $filters_and[$key] = "id IN(" . implode(',', $ids) . ")";
                        }
                    }
                }

                // All
                if (isset($this->facet_data['findata']['childs'][$key])) {
                    // Finances                    
                    $data_arr = explode('-', $value);
                    $from = ((int) $data_arr[0]) * 1000;
                    $to = ((int) $data_arr[1]) * 1000;
                    $budget_min = $this->budget_min * 1000;
                    $budget_max = $this->budget_max * 1000;

                    if ($from == $to) {
                        if ($from == $budget_min) {
                            $filters_and[$key] = sprintf("{$key} > 0 AND {$key}<=%d", $from);
                        } else if ($from == $budget_max) {
                            $filters_and[$key] = sprintf("{$key}>=%d", $to);
                        } else {
                            $filters_and[$key] = sprintf("{$key}=%d", $from);
                        }
                    } else {
                        if ($from == $budget_min && $to == $budget_max) {
                            $filters_and[$key] = "{$key} > 0";
                        } else if ($from == $budget_min) {
                            $filters_and[$key] = sprintf("{$key} > 0 AND {$key} < %d", $to);
                        } else if ($to == $budget_max) {
                            $filters_and[$key] = sprintf("{$key} >= %d", $from);
                        } else {
                            $filters_and[$key] = sprintf("{$key} >=%d AND {$key} < %d", $from, $to);
                        }
                    }
                } else if (isset($curr_facet['facet']) && $curr_facet['facet'] == 'rating') {
                    if ($value == 'use' || $value == 'minus') {
                        $parent_key = $curr_facet['eid'];
                        if ($value == 'use') {
                            $filters_and[$key] = $parent_key . "=1";
                        } else {
                            $filters_and[$key] = $parent_key . "=0";
                        }
                    } else {
                        $dates = explode('-', $value);
                        $from = (int) $dates[0];
                        $to = (int) $dates[1];

                        if (isset($this->facet_data['wokedata']['childs'][$key])) {
                            // Woke ratings
                            if (!$minus) {
                                if ($from == $to) {
                                    $filters_and['if_woke'][] = "{$key} = {$from}";
                                } else {
                                    if ($from != 0) {
                                        $filters_and['if_woke'][] = "{$key} >= {$from} AND {$key} <= {$to}";
                                    } else {
                                        $filters_and['if_woke'][] = "{$key}<={$to}";
                                    }
                                }
                            } else {
                                /* if ($from == $to) {
                                  $filters_and['if_woke'][] = "{$key}!={$from}";
                                  } else {
                                  $filters_and['if_woke'][] = "{$key}<{$from} OR {$key}>{$to}";
                                  } */
                                if ($from == $to) {
                                    $key_filter = $key . '_filter';
                                    $select_and[$key] = "IF({$key}!={$from},1,0) AS {$key_filter} ";
                                    $filters_and[$key] = "{$key_filter}=1";
                                } else {
                                    $key_filter = $key . '_filter';
                                    $select_and[$key] = "IF({$key}<{$from} OR {$key}>{$to},1,0) AS {$key_filter} ";
                                    $filters_and[$key] = "{$key_filter}=1";
                                }
                            }
                        } else {
                            // Other ratings
                            if (!$minus) {
                                if ($from == $to) {
                                    $filters_and[] = sprintf($key . "=%d", $from);
                                } else {
                                    if ($from != 0) {
                                        $filters_and[$key] = "{$key} >= {$from} AND {$key} <= {$to}";
                                    } else {
                                        $key_filter = $key . '_filter';
                                        $select_and[$key] = "IF({$key}<={$to},1,0) AS {$key_filter} ";
                                        $filters_and[$key] = "{$key_filter}=1";
                                    }
                                }
                            } else {
                                if ($from == $to) {
                                    $key_filter = $key . '_filter';
                                    $select_and[$key] = "IF({$key}!={$from},1,0) AS {$key_filter} ";
                                    $filters_and[$key] = "{$key_filter}=1";
                                } else {
                                    $key_filter = $key . '_filter';
                                    $select_and[$key] = "IF({$key}<{$from} OR {$key}>{$to},1,0) AS {$key_filter} ";
                                    $filters_and[$key] = "{$key_filter}=1";
                                }
                            }
                            if ($key == 'rrtg') {
                                $filters_and[$key] .= " AND rrta>0 AND rrt>0";
                            }
                            if ($facet == 'rmg') {
                                $filters_and[$key] .= " AND rmu>0 AND rmc>0";
                            }
                        }
                    }
                } else if ($key == 'genre') {

                    // Genre
                    if ($and) {
                        if ($minus) {
                            $filters_and[$key] = $this->filter_multi_value($key, $value, true, $minus, true, false, true, true);
                        } else {
                            $filters_and[$key] = $this->filter_multi_value($key, $value, true, $minus, true, false, true, true);
                        }
                    } else {
                        $filters_and[$key] = $this->filter_multi_value($key, $value, true, $minus);
                    }
                } else if ($key == 'platform') {
                    // Platform                        
                    $filters_and[$key] = $this->filter_multi_value($key, $value, true, $minus);
                } else if ($key == 'type') {
                    // Type
                    $filters_and[$key] = $this->filter_multi_value('type', $value);
                } else if ($key == 'id') {
                    // id
                    $filters_and[$key] = sprintf("id>%d", $value);
                } else if ($key == 'release') {
                    // Release
                    $dates = explode('-', $value);
                    $release_from = (int) $dates[0];
                    if ($release_from == 0) {
                        $release_from = 1;
                    }
                    $release_to = (int) $dates[1];
                    if ($release_from == $release_to) {
                        $filters_and[$key] = sprintf("year_int=%d", $release_from);
                    } else {
                        $filters_and[$key] = sprintf("year_int >=%d AND year_int < %d", $release_from, $release_to);
                    }
                } else if (isset($this->facet_data['wokedata']['childs'][$key])) {
                    if ($key == 'kmwoke') {

                        $value_loc = $value;
                        if (!is_array($value_loc)) {
                            $value_loc = array($value_loc);
                        }
                        $for_filter = array();
                        foreach ($value_loc as $kmkey) {
                            $kfilter = $this->search_filters[$key][$kmkey]['key'];

                            if ($this->in_exclude($kfilter, $exlude)) {
                                continue;
                            }

                            if ($minus) {
                                $for_filter[] = $kfilter . "=0";
                            } else {
                                $filters_and['if_woke'][] = $kfilter . "=1";
                            }
                        }
                        if ($minus) {
                            if ($for_filter) {
                                $filters_and[$key] = implode(' AND ', $for_filter);
                            }
                        }
                    } else {
                        if (!$minus) {
                            $filter_items = $value;
                            if (!is_array($value)) {
                                $filter_items = [$value];
                            }
                            foreach ($filter_items as $filter_item) {
                                $filters_and['if_woke'][] = $this->filter_multi_value($key, $filter_item, false);
                            }
                        } else {
                            $filters_and[$key] = $this->filter_multi_value($key, $value, true, $minus);
                        }
                    }
                } else if ($key == 'provider') {
                    // Provider
                    $filters_and[$key] = $this->filter_multi_value($key, $value, true);
                } else if ($key == 'price') {
                    // Provider price
                    $ma = $this->get_ma();
                    $pay_type = 1;
                    $list = $ma->get_providers_by_type($pay_type);
                    $filters_and[$key] = $this->filter_multi_value('provider', $list, true);
                } else if ($key == 'country') {
                    // Country
                    $filters_and[$key] = $this->filter_multi_value($key, $value, true, $minus);
                } else if ($key == 'lang') {
                    // lang
                    $filters_and[$key] = $this->filter_multi_value($key, $value, true, $minus);
                } else if (isset($this->facet_data['dirsdata']['childs'][$key])) {
                    if ($key == 'dirall' || $key == 'dir' || $key == 'dirwrite' || $key == 'dircast' || $key == 'dirprod') {
                        // Director  
                        $actor_filter = $this->facet_data['dirsdata']['childs'][$key]['filter'];
                        $filters_and[$key] = $this->filter_multi_value($actor_filter, $value, true, $minus);
                    } else {
                        // Race directors
                        // Gender dirs
                        $filters_and[$key] = $this->filter_multi_value($key, $value, true, $minus);
                    }
                } else if ($key == 'indie') {
                    $value = is_array($value) ? $value : array($value);
                    $for_filter = array();
                    foreach ($value as $slug) {
                        if ($this->search_filters[$key][$slug]) {
                            $for_filter[] = $this->filter_multi_value($slug, 1, false, $minus, true, true, false);
                        }
                    }
                    if ($for_filter) {
                        $filters_and[$key] = implode(' AND ', $for_filter);
                    }
                } else if ($key == 'mkw') {
                    // Movie Keywords
                    $filters_and[$key] = $this->filter_multi_value($key, $value, true, $minus);
                } else if ($key == 'franchise') {
                    // Franchise
                    $filters_and[$key] = $this->filter_multi_value($key, $value, true, $minus);
                } else if ($key == 'distributor' || $key == 'production') {
                    // Distributor
                    $filters_and[$key] = $this->filter_multi_value($key, $value, true, $minus);
                } else {

                    $curr_parent = $this->get_last_parent($key);

                    $actors_facet = '';
                    if ($curr_parent == 'actorsdata') {
                        // Actors facet
                        $actors_facet = 'cast';
                    } else if ($curr_parent == 'dirsdata') {
                        // Directors facet
                        $actors_facet = 'director';
                    }
                    if ($actors_facet) {

                        $cparent = isset($curr_facet['parent']) ? $curr_facet['parent'] : '';

                        if ($cparent == 'race') {
                            // Race
                            $first_parent = $this->get_first_parent($key);
                            $race_facets = isset($this->actorscache[$actors_facet]['exist'][$first_parent]) ? $this->actorscache[$actors_facet]['exist'][$first_parent] : array();
                            //print $first_parent . "\n";
                            //print_r(array_keys($this->actorscache[$actors_facet]['exist']));
                            if ($race_facets) {
                                $for_filter = array();
                                foreach ($race_facets['all'] as $rkey => $rval) {
                                    $racekey = $rval['race'];
                                    // Exclude filter
                                    if (!$and) {
                                        if ($this->in_exclude($rkey, $exlude)) {
                                            continue;
                                        }
                                    }

                                    if (is_array($value)) {
                                        if (in_array($racekey, $value)) {
                                            $for_filter[] = $this->filter_multi_value($rkey, 1, false, $minus);
                                        }
                                    } else {
                                        if ($racekey == $value) {
                                            $for_filter[] = $this->filter_multi_value($rkey, 1, false, $minus);
                                        }
                                    }
                                }
                                if ($for_filter) {
                                    $filters_and[$key] = implode(' AND ', $for_filter);
                                }
                            }
                        } else if ($cparent == 'gender') {
                            // Gender                           
                            $first_parent = $this->get_first_parent($key);
                            $race_facets = isset($this->actorscache[$actors_facet]['exist'][$first_parent]) ? $this->actorscache[$actors_facet]['exist'][$first_parent] : array();
                            if ($race_facets) {
                                $for_filter = array();
                                foreach ($this->search_filters['gender'] as $gkey => $gitem) {
                                    foreach ($race_facets[$gkey] as $rkey => $rval) {
                                        $racekey = $rval['race'];
                                        if ($racekey == 'a') {

                                            if (!$and) {
                                                if ($this->in_exclude($rkey, $exlude)) {
                                                    continue;
                                                }
                                            }
                                            if (is_array($value)) {
                                                if (in_array($gkey, $value)) {
                                                    $for_filter[] = $this->filter_multi_value($rkey, 1, false, $minus);
                                                }
                                            } else {
                                                if ($gkey == $value) {
                                                    $for_filter[] = $this->filter_multi_value($rkey, 1, false, $minus);
                                                }
                                            }
                                        }
                                    }
                                }
                                if ($for_filter) {
                                    $filters_and[$key] = implode(' AND ', $for_filter);
                                }
                            }
                        } else {
                            if ($key == 'sphoto' || $key == 'mphoto' || $key == 'aphoto') {
                                // sphoto    
                                // $filters_and[$key]= $this->filter_multi_value($key, $value, true, $minus, false, true, false);
                                $filters_and[$key] = $this->filter_multi_value($key, $value, false, $minus);
                            } else {
                                // other
                                $custom_filter = isset($curr_facet['filter']) ? $curr_facet['filter'] : $key;
                                $filters_and[$key] = $this->filter_multi_value($custom_filter, $value, true, $minus);
                            }
                        }
                    }
                }
            }
        }


        if ($curr_filter && isset($this->filter_custom_and[$curr_filter])) {
            $filters_and[$curr_filter] = $this->filter_custom_and[$curr_filter];
        }

        // Woke logic

        if ($filters_and['if_woke']) {
            if ($this->in_exclude('if_woke', $exlude)) {
                unset($filters_and['if_woke']);
            } else {
                $select_and['if_woke'] = $this->woke_recursion($filters_and['if_woke']) . " AS if_woke";
                $filters_and['if_woke'] = "if_woke=1";
            }
        }

        /*
          foreach ($filters_and as $key => $value) {
          if(is_array($value)){
          $str_select = "IF(". implode(' OR ', $value).",1,0) as {$key}";
          $str_filter = "{$key}=1";
          $filters_and[$key]=$str_filter;
          $select_and[$key]=$str_select;
          }
          } */



        $select_str = implode(', ', array_values($select_and));
        if ($select_str) {
            $select_str = ',' . $select_str;
        }



        $filters_str = implode(' AND ', array_values($filters_and));
        if ($filters_str) {
            $filters_str = ' AND ' . $filters_str;
        }
        return array(
            'filter' => $filters_str,
            'select' => $select_str,
        );
    }

    private function woke_recursion($filters = array()) {
        $filter = array_pop($filters);
        if ($filter) {
            return "IF(" . $filter . ",1," . $this->woke_recursion($filters) . ")";
        } else {
            return "0";
        }
    }

    public function filter_multi_value($key, $value, $multi = false, $not = false, $any = true, $not_all = true, $not_and = false, $split_all = false, $force_string=false) {
        $filters_and = [];
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
                $filter = $this->get_search_filter($key, $item, $force_string);
                if ($filter !== '') {
                    $provider_valid_arr[] = $filter;
                }
            }
            if (sizeof($provider_valid_arr)) {
                if (!$not) {
                    if ($multi) {
                        if ($split_all) {
                            foreach ($provider_valid_arr as $provider) {
                                $filters_and[] = sprintf("$and(%s)=%s", $key, $provider);
                            }
                        } else {
                            // https://sphinxsearch.com/bugs/view.php?id=2627
                            $filters_and[] = sprintf("$and(%s) IN (%s)", $key, implode(',', $provider_valid_arr));
                        }
                    } else {
                        $filters_and[] = sprintf("%s IN (%s)", $key, implode(',', $provider_valid_arr));
                    }
                } else {
                    // Filter not
                    $and_any = '';
                    if ($not_and) {
                        $and_any = sprintf("ANY(%s)>0", $key);
                    }
                    if ($multi) {
                        if ($split_all) {
                            foreach ($provider_valid_arr as $provider) {
                                $filters_and[] = sprintf("$and_not(%s)!=%s", $key, $provider);
                            }
                            $filters_and[] = $and_any;
                        } else {
                            foreach ($provider_valid_arr as $filter) {
                                $filters_and[] = sprintf("$and_not(%s)!=%s" . $and_any, $key, $filter);
                            }
                        }
                    } else {
                        foreach ($provider_valid_arr as $filter) {
                            $filters_and[] = sprintf("%s!=%s" . $and_any, $key, $filter);
                        }
                    }
                }
            }
        } else {
            $filter = $this->get_search_filter($key, $value, $force_string);
            if ($filter !== '') {
                if (!$not) {
                    if ($multi) {
                        $filters_and[] = sprintf("$and(%s)=%s", $key, $filter);
                    } else {
                        $filters_and[] = sprintf("%s=%s", $key, $filter);
                    }
                } else {
                    // Filter not
                    $and_any = '';
                    if ($not_and) {
                        $and_any = sprintf("ANY(%s)>0", $key);
                    }
                    if ($multi) {
                        $filters_and[] = sprintf("ALL(%s)!=%s" . $and_any, $key, $filter);
                    } else {
                        $filters_and[] = sprintf("%s!=%s" . $and_any, $key, $filter);
                    }
                }
            }
        }
        $ret = '';

        if ($filters_and) {
            $ret = implode(' AND ', $filters_and);
        }
        return $ret;
    }

    private function get_search_filter($key, $value, $force_string=false) {
        $filter = '';

        $curr_facet = isset($this->facets_data[$key]) ? $this->facets_data[$key] : array();
        if (isset($curr_facet['filter_key'])) {
            $key = $curr_facet['filter_key'];
        }

        if (isset($this->search_filters[$key][$value])) {
            $filter = $this->search_filters[$key][$value]['key'];
            if (!$this->is_int($filter)||$force_string) {
                $filter = "'" . $filter . "'";
            }
        } else {
            $filter = $value;            
            if (!$this->is_int($value)||$force_string) {
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
                $match_query_or = $this->wildcards_maybe_query($keyword, $widlcard, '|');
                $match = sprintf(" AND MATCH('@(title,year) ((^%s$)|(%s))')", $keyword, $match_query_or);
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
        // DEPRECATED
        $tabs_arr = array(
            'stars' => array('facet' => 'starrace', 'title' => 'Stars'),
            'main' => array('facet' => 'mainrace', 'title' => 'Supporting'),
            'all' => array('facet' => 'race', 'title' => 'All'),
        );
        return $tabs_arr;
    }

    public function get_default_cast_tab() {
        // DEPRECATED
        return 'starrace';
    }

    public function get_active_race_facet($filters) {
        // DEPRECATED
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

    public function get_active_gender_dir_facet($filters) {
        // DEPRECATED
        $race = $this->get_active_director_facet($filters);
        $gender = $this->facet_data['dirsdata']['race_gender_dir'][$race];
        return $gender;
    }

    public function get_director_tabs() {
        // DEPRECATED
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
        // DEPRECATED
        return 'dirrace';
    }

    public function get_active_director_facet($filters) {
        // DEPRECATED
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
                if ($value == '&') {
                    continue;
                }
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
        if ($cache) {
            $dict[$key] = $ret;
        }
        return $ret;
    }

    public function get_distributors_names($ids, $cache = true) {
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
        $sql = sprintf("SELECT id, name FROM movie_distributors WHERE id IN (%s) LIMIT 1000", implode(',', $ids));
        $result = $this->sdb_results($sql);
        $ret = array();
        if ($result) {
            foreach ($result as $item) {
                $ret[$item->id] = $item->name;
            }
        }
        if ($cache) {
            $dict[$key] = $ret;
        }
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

    public function get_keywords_titles($ids, $cache = true) {
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
        $limit = count($ids);
        $sql = "SELECT id, name FROM movie_keywords WHERE id IN(" . implode(',', $ids) . ") LIMIT 0," . $limit;
        $results = $this->sdb_results($sql);
        $ret = array();
        if ($results) {
            foreach ($results as $item) {
                $ret[$item->id] = $item->name;
            }
        }
        if ($cache) {
            $dict[$key] = $ret;
        }
        return $ret;
    }

    public function get_franchise_titles($ids) {
        $ma = $this->get_ma();
        $ret = $ma->get_franchises_by_ids($ids);
        return $ret;
    }

    public function get_distributor_titles($ids) {
        # $ma = $this->get_ma();
        # $ret = $ma->get_distributors_by_ids($ids);
        $ret = $this->get_distributors_names($ids);
        return $ret;
    }

    public function find_distributor_ids($keyword) {
        $sql = sprintf("SELECT id, name, types FROM movie_distributors WHERE MATCH('((^%s)|(^%s*))') AND ANY(types)=1 LIMIT 1000", $keyword, $keyword);
        $result = $this->sdb_results($sql);
        $results = array();
        if (sizeof($result)) {
            foreach ($result as $item) {
                $results[$item->id] = $item->name;
            }
        }
        return $results;
    }

    public function find_production_ids($keyword) {
        $sql = sprintf("SELECT id, name FROM movie_distributors WHERE MATCH('((^%s)|(^%s*))') AND ANY(types)=0 LIMIT 1000", $keyword, $keyword);
        $result = $this->sdb_results($sql);

        $results = array();
        if (sizeof($result)) {
            foreach ($result as $item) {
                $results[$item->id] = $item->name;
            }
        }
        return $results;
    }

    public function find_keywords_ids($keyword, $tab_key = '') {
        # $search_keywords = $this->wildcards_maybe_query($keyword, true, ' ');
        $filter_kw = $this->filter_text($keyword);
        $filter_kw = str_replace("'", "\'", $filter_kw);

        $and_type = ' AND ANY(types) IN(1)';
        if ($tab_key == 'games') {
            $and_type = ' AND ANY(types) IN(2)';
        }

        $sql = sprintf("SELECT id, name FROM movie_keywords WHERE MATCH('((^%s)|(^%s*))')" . $and_type . " LIMIT 1000", $filter_kw, $filter_kw);
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

    public function get_last_critics($a_type = -1, $limit = 10, $movie_id = 0, $start = 0, $tags = array(), $meta_type = array(), $min_rating = 0, $vote = 0, $min_au = 0, $max_au = 0, $vote_type = 0, $unique_authors = 0, $aid = 0) {

        $filters_and = [];

        if ($a_type != -1) {
            $filters_and[] = sprintf('author_type = %d', $a_type);
        }

        if ($movie_id > 0) {
            $filters_and[] = $this->filter_multi_value('movies', $movie_id, true);
        }

        if ($min_rating) {
            // TODO min rating
        }

        if ($min_au) {
            $filters_and[] = sprintf("aurating>%d", $min_au);
        }

        if ($max_au) {
            $filters_and[] = sprintf("aurating<=%d", $max_au);
        }

        if ($meta_type) {
            // TODO meta type
            $filters_and[] = "ANY(state) IN (" . implode(',', $meta_type) . ")";
        }


        $order = " ORDER BY post_date DESC";
        /* if ($movie_id > 0) {
          $order = " ORDER BY post_date DESC";
          } */

        // Tag logic
        if ($tags) {
            $filters_and[] = $this->filter_multi_value('tags', $tags, true);
        }

        // Vote logic
        if ($vote > 0) {
            $filters_and[] = sprintf("auvote=%d", $vote);
        }

        // Vote type:
        $and_select = '';
        if ($vote_type > 0) {
            if ($vote_type == 1) {
                /*
                  Positive
                 * 
                 *  IF(r.rating>=3.5 AND r.vote IN (1,3),1,IF(r.rating>=3 AND r.vote=1,1,0))
                 */
                $and_select = ", IF(aurating>=3.8 AND auvote !=2,1,IF(aurating>=3 AND auvote=1,1,0)) AS filter ";
                $filters_and[] = "filter=1";
            } if ($vote_type == 2) {
                /*
                  Negative
                  $vote_type_and = " AND r.rating < 3";
                 */
                // $and_select = ", IF(aurating=0 OR aurating=1 OR aurating=2,1,0) AS filter ";
                // $and_select = ", IF(aurating < 3,1,0) AS filter ";
                // $filters_and[]= " AND filter=1";
                $filters_and[] = "aurating>0 AND aurating<2.3";
            }
        }

        // Hide home author

        if ($movie_id == 0) {
            $filters_and[] = 'author_show_type!=1';
        }

        if ($aid) {
            $filters_and[] = 'aid=' . $aid;
        }

        $filters_str = implode(' AND ', $filters_and);
        if ($filters_str) {
            $filters_str = ' AND ' . $filters_str;
        }


        if ($unique_authors) {
            $sql = sprintf("SELECT GROUPBY() as aid, COUNT(*) as cnt" . $and_select . " FROM critic WHERE status=1 AND top_movie>0" . $filters_str . " GROUP BY aid" . $order . " LIMIT %d,%d", $start, $limit);
        } else {
            $sql = sprintf("SELECT id, post_date as date, date_add, top_movie, aid, author_name, aurating" . $and_select . " FROM critic WHERE status=1 AND top_movie>0" . $filters_str . $order . " LIMIT %d,%d", $start, $limit);
        }
        $results = $this->sdb_results($sql);
        /* if ($unique_authors){
          $meta = $this->sdb_results("SHOW META");
          print_r(array($sql, $results,$meta));
          exit;
          } */
        return $results;
    }

    /*
     * Find povtor
     */

    function find_post_povtor($title = '', $pid = 0, $aid = 0, $debug = false) {

        $povtor = false;
        $length = 200;
        $min_precent_all = 90;
        $min_precent_day = 70;

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
                if ($precent >= $min_precent_day) {

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
                            // different sources
                            $one_day = false;
                            if ((($post_cache2->date - 86400) < $post_cache->date) &&
                                    ($post_cache->date < ($post_cache2->date + 86400))) {
                                // One day
                                $min_percent = $min_precent_day;
                                $one_day = true;
                            } else {
                                $min_percent = $min_precent_all;
                            }

                            p_r(array(array('Dates', $post_cache->date, $post_cache2->date)), array('Min percent', $min_percent));

                            if ($precent >= $min_percent) {

                                $post_content = $this->getUniqueWords(strip_tags($post_cache->content));
                                $post_content2 = $this->getUniqueWords(strip_tags($post_cache2->content));
                                if ($post_content || $post_content2) {
                                    $precent_c = $this->get_min_percent($post_content, $post_content2);
                                }
                                if ($debug) {
                                    p_r(array('Content percent', $key, $precent_c));
                                }

                                if ($precent_c >= $min_percent || $one_day) {
                                    $povtor->percent = array($precent, $precent_c);
                                    $valid_povtors[$key] = $povtor;
                                }
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

    public function getUniqueWords($words) {
        if (preg_match_all("#([\p{L}0-9]+)#uis", $words, $matchesarray)) {
            $wordsArr = array_unique($matchesarray[0]);
            return $wordsArr;
        }
    }

    public function is_hide_facet($facet = '', $filters = array(), $tab = '') {


        if ($tab && isset($this->facet_tabs[$facet]) && !in_array($tab, $this->facet_tabs[$facet])) {
            return true;
        }

        if (isset($this->hide_facets[$facet])) {
            // In hide list

            if (!isset($filters['show'])) {
                return true;
            }

            if (is_string($filters['show']) && $facet == $filters['show']) {
                return false;
            }

            if (is_array($filters['show']) && in_array($facet, $filters['show'])) {
                return false;
            }

            // Def hide
            return true;
        } else {
            // No hide list

            if (!isset($filters['hide'])) {
                return false;
            }
            if (is_string($filters['hide']) && $facet == $filters['hide']) {
                return true;
            }
            if (is_array($filters['hide']) && in_array($facet, $filters['hide'])) {
                return true;
            }
            // Def show
            return false;
        }
    }

    public function hide_facet_class($facet = '', $filters = array()) {
        // Get def facet
        $hide_facet = '';
        $is_hide = $this->is_hide_facet($facet, $filters);

        if (isset($this->hide_facets[$facet])) {
            $hide_facet = ' defhide';

            if ($is_hide) {
                $hide_facet .= ' collapsed';
            }
        } else {
            $hide_facet = ' defshow';

            if ($is_hide) {
                $hide_facet .= ' collapsed';
            }
        }
        return $hide_facet;
    }

    public function related_movies($mid = 0, $limit = 1000, $strict_type = 0, $debug = false) {
        // Get related movies
        $movie = $this->get_movie_by_id($mid);
        if ($debug) {
            // print_r($movie);
        }
        $genre = $movie->genre;
        $mkw = $movie->mkw;

        $match = '';
        if ($genre || $mkw) {
            if ($genre) {
                $m_genre = " @(genre_str) (" . str_replace(',', '|', $genre) . ")";
            }
            if ($mkw) {
                $mkw_arr = explode(',', $mkw);
                $max_arr_len = 500;
                if (sizeof($mkw_arr) > $max_arr_len) {
                    $mkw_arr = array_slice($mkw_arr, 0, $max_arr_len);
                }

                $mkw = implode('|', $mkw_arr);

                $m_mkw = " @(mkw_str) (" . $mkw . ")";
            }

            $match = " AND MATCH('" . $m_genre . $m_mkw . "')";
        } else {
            return array();
        }

        $type_sql = '';
        if ($strict_type == 1) {
            $type_sql = " AND type='" . $movie->type . "'";
        } else if ($strict_type == 2) {
            $type_sql = " AND type IN('Movie','TVSeries')";
        }

        $sql = sprintf("SELECT id, title, genre, mkw, weight() w"
                . " FROM movie_an WHERE id!={$mid}" . $type_sql . $match . " ORDER BY w DESC LIMIT %d ", $limit);

        if ($debug) {
            print_r($sql);
        }

        $result = $this->sdb_results($sql);

        if ($debug) {
            $meta = $this->sdb_results("SHOW META");
            print_r($meta);
        }
        return $result;
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
                $sql_arr[$facet] = "SELECT GROUPBY() as id, COUNT(*) as cnt FROM " . $search_db . " WHERE id>0" . $match
                        . " GROUP BY bias ORDER BY bias ASC LIMIT " . $limit;
            } else if ($facet == 'biasrating') {
                $sql_arr[$facet] = "SELECT GROUPBY() as id, COUNT(*) as cnt, SUM(nresult) AS nresults"
                        . " FROM " . $search_db . " WHERE nresult>0 " . $match
                        . " GROUP BY bias ORDER BY bias ASC LIMIT " . $limit;
            }
        }
        return $sql_arr;
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
        $procent = isset($this->search_prc[$param]) ? $this->search_prc[$param] : $this->search_prc['def'];

        $min_setting = $def_setting = $max_setting = 0;

        if (isset($this->default_search_settings[$param])) {
            $def_setting = $this->default_search_settings[$param];

            $min_setting = $def_setting - ($def_setting * $procent) / 100;
            $max_setting = $def_setting + ($def_setting * $procent) / 100;

            if ($def_setting > 5) {
                $min_setting = (int) $min_setting;
                $max_setting = (int) $max_setting;
            }
            if ($min_setting < 0) {
                $min_setting = 0;
            }
        }
        return array('min' => $min_setting, 'def' => $def_setting, 'max' => $max_setting);
    }

    private function get_perpage() {
        $perpage = isset($_GET['perpage']) ? (int) $_GET['perpage'] : 0;
        if (!$perpage) {
            $perpage = isset($_POST['perpage']) ? (int) $_POST['perpage'] : $this->perpage;
        }

        $this->perpage = $perpage;

        return $this->perpage;
    }

    public function get_movie_names($ids, $cache = true) {
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
        $sql = sprintf("SELECT id, title, year_int as year FROM movie_an WHERE id IN (%s) LIMIT 1000", implode(',', $ids));
        $result = $this->sdb_results($sql);
        $ret = array();
        if ($result) {
            foreach ($result as $m) {
                $ret[$m->id] = $m->title . ' (' . $m->year . ')';
            }
        }
        if ($cache) {
            $dict[$key] = $ret;
        }
        return $ret;
    }

    public function get_zr_movie_id($title = '', $year = '', $debug = false) {
        $ret = 0;

        $title = str_replace("'", "\'", $title);
        $search_query = sprintf("'@title \"%s\" @year %d'", $title, $year);

        $sql = sprintf("SELECT id, title FROM movie_an "
                . "WHERE id>0 AND MATCH(:match) LIMIT 1");

        if ($debug) {
            print_r(array($search_query, $sql));
        }

        $this->connect();
        $stmt = $this->sps->prepare($sql);
        $stmt->bindValue(':match', $search_query, PDO::PARAM_STR);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_OBJ);
        if ($debug) {
            print_r($data);
        }

        if (isset($data[0]->id)) {
            $ret = $data[0]->id;
        }
        return $ret;
    }

    public function getSearchFilter($key = '', $cache = true) {
        if ($cache) {
            static $dict;
            if (is_null($dict)) {
                $dict = array();
            }

            if (isset($dict[$key])) {
                return $dict[$key];
            }
        }

        $ret = new SearchFilter($key);

        if ($cache) {
            $dict[$key] = $ret;
        }

        return $ret;
    }

    public function get_movie_by_id($id = 0) {
        $sql = sprintf("SELECT * FROM movie_an WHERE id=%d LIMIT 1", (int) $id);
        $result = $this->sdb_results($sql);
        $result = array_pop($result);
        return $result;
    }

    public function get_budget_array() {
        $max_key = $this->budget_max;
        $karay = array();
        $k = $this->budget_min;
        while ($k < $max_key) {
            $karay[] = $k;
            $k = round($k * 1.2, 0);
            $klen = strlen('' . $k);
            $k = round($k / pow(10, $klen - 2), 0) * pow(10, $klen - 2);
        }
        $karay[] = $max_key;
        return $karay;
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

class SearchFilter {

    public $data = '';
    public $filter = '';
    public $minus = false;
    public $and = false;
    public $or = false;

    public function __construct($name = '') {
        $this->data = $name;
        $clear_key = $name;
        if (strstr($clear_key, 'minus-')) {
            $clear_key = str_replace('minus-', '', $clear_key);
            $this->minus = true;
        }

        if (strstr($clear_key, 'and-')) {
            $clear_key = str_replace('and-', '', $clear_key);
            $this->and = true;
        }

        if (strstr($clear_key, 'or-')) {
            $clear_key = str_replace('or-', '', $clear_key);
            $this->or = true;
        }
        $this->filter = $clear_key;
    }
}
