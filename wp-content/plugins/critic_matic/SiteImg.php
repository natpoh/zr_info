<?php

/**
 * Create images from site urls
 *
 * @author brahman
 */
class SiteImg extends AbstractDB {

    private $cm;
    private $mp;
    private $db;
    private $ml_camp = array(
        22 => array('ekey' => 'douban_rating', 'name' => 'Douban', 'flag' => 'cn', 'ratmax' => 10, 'multipler' => 10,'rateconvert'=>20),
       // 23 => array('ekey' => 'metacritic_rating', 'name' => 'MetaCritic', 'flag' => 'mtcr', 'ratmax' => 100, 'multipler' => 1,'rateconvert'=>20),
        24 => array('ekey' => 'kinop_rating', 'name' => 'Kinopoisk', 'flag' => 'ru', 'ratmax' => 10, 'multipler' => 10,'rateconvert'=>20),
        //27 => array('ekey' => 'animelist_rating', 'name' => 'MyAnimeList', 'flag' => 'jp', 'ratmax' => 10, 'multipler' => 10,'rateconvert'=>20),
        36 => array('ekey' => 'eiga_rating', 'name' => 'Eiga', 'flag' => 'jp', 'ratmax' => 5, 'multipler' => 10,'rateconvert'=>10),
        38 => array('ekey' => 'moviemeter_rating', 'name' => 'MovieMeter', 'flag' => 'nl', 'ratmax' => 5, 'multipler' => 10,'rateconvert'=>10),
        44 => array('ekey' => 'ofdb_rating', 'name' => 'OFDb', 'flag' => 'de', 'ratmax' => 10, 'multipler' => 10,'rateconvert'=>20),
    );

    public function __construct($cm = '') {
        $this->cm = $cm ? $cm : new CriticMatic();

        $this->db = array(
            'site_img' => 'data_site_img',
            'erating' => 'data_movie_erating',
            'url' => 'movies_links_url',
        );
    }

    public function get_mp() {
        // Get movies parser
        if (!$this->mp) {
            if (!class_exists('MoviesLinks')) {
                !defined('MOVIES_LINKS_PLUGIN_DIR') ? define('MOVIES_LINKS_PLUGIN_DIR', ABSPATH . 'wp-content/plugins/movies_links/') : '';
                require_once( MOVIES_LINKS_PLUGIN_DIR . 'db/MoviesAbstractFunctions.php' );
                require_once( MOVIES_LINKS_PLUGIN_DIR . 'db/MoviesAbstractDB.php' );
                require_once( MOVIES_LINKS_PLUGIN_DIR . 'MoviesLinks.php' );
            }

            $ml = new MoviesLinks();
            // get parser
            $this->mp = $ml->get_mp();
        }
        return $this->mp;
    }

    public function get_images($mid, $debug = false) {
        $ret = array();

        # 1. Get erating
        $erating = $this->get_movie_erating($mid);
        if (!$erating) {
            return $ret;
        }


        if ($debug) {
            print_r($erating);
        }

        $ml_camp_ret = array();
        foreach ($this->ml_camp as $cid => $item) {

            $ekey = $item['ekey'];
            # Add only exist rating
            $ml_camp_ret[$cid] = $item;

            if (isset($erating->$ekey) && $erating->$ekey > 0) {
                $ml_camp_ret[$cid]['rating'] = $erating->$ekey;
            } else {
                $ml_camp_ret[$cid]['rating'] = -1;
            }
        }

        if ($debug) {
            print_r($ml_camp_ret);
        }

        if (!$ml_camp_ret) {
            return $ret;
        }

        # 2. Get urls
        # TODO get expired by movie weight

        $expire = 360;
        $urls = $this->get_img_urls_by_mid($mid);

        if ($debug) {
            print_r($urls);
        }

        $curr_time = $this->curr_time();
        $expire_date = $curr_time - ($expire * 86400);

        $exists = array();

        if ($urls) {
            foreach ($urls as $item) {
                if (isset($ml_camp_ret[$item->mlcid])) {
                    $exists[$item->mlcid] = $item;
                }
            }
        }

        if ($debug) {
            print_r(array('exists', $exists));
        }

        foreach ($this->ml_camp as $cid => $item) {
            if (isset($exists[$cid])) {

                if ($debug) {
                    print 'Update ' . $cid . "\n";
                    ;
                }

                $exist = $exists[$cid];


                $ml_camp_ret[$cid]['link'] = $exist->link;
                $ml_camp_ret[$cid]['link_hash'] = $exist->link_hash;
                $ml_camp_ret[$cid]['img'] = 0;

                $data = array(
                    'counter' => $exist->counter + 1,
                );


                if ($exist->date > 0) {
                    # Image exist

                    $ml_camp_ret[$cid]['img'] = $exist->id;

                    # 2. Check expire                   
                    if ($expire_date > $exist->date) {
                        $data['expired'] = 1;
                    }
                }

                # Update
                $this->db_update($data, $this->db['site_img'], $exist->id);
            } else {

                # Append

                if ($ml_camp_ret[$cid]['rating'] != -1) {

                    $link = $this->get_link($cid, $mid, $debug);
                    if ($debug) {
                        print_r($link);
                    }
                    if ($link) {

                        if ($debug) {
                            print 'Append ' . $cid . "\n";
                        }
                        $link_hash = $this->link_hash($link);

                        $ml_camp_ret[$cid]['link'] = $link;
                        $ml_camp_ret[$cid]['link_hash'] = $link_hash;
                        $ml_camp_ret[$cid]['img'] = 0;

                        # Add item
                        $data = array(
                            'mid' => $mid,
                            'mlcid' => $cid,
                            'counter' => 1,
                            'link_hash' => $link_hash,
                            'link' => $link,
                        );
                        if ($debug) {
                            print_r($data);
                        }
                        $this->db_insert($data, $this->db['site_img']);
                    }
                }
            }
        }

        if ($debug) {
            print_r($ml_camp_ret);
        }

        $to_ret = array();

        if ($ml_camp_ret) {
            foreach ($ml_camp_ret as $key => $value) {
                if ($value['rating'] != -1) {
                    $to_ret[$key] = $value;
                }
            }
        }

        if ($debug) {
            print_r($to_ret);
        }

        return $to_ret;
    }

    public function get_movie_erating($mid) {
        $sql = sprintf("SELECT * FROM {$this->db['erating']} WHERE movie_id=%d", (int) $mid);
        $results = $this->db_fetch_row($sql);
        return $results;
    }

    public function get_link($cid, $mid, $debug = false) {
        $mp = $this->get_mp();
        $url_data = $mp->get_url_by_top_movie($mid, $cid);

        if ($debug) {
            print_r($url_data);
        }

        $url = '';
        if ($url_data) {
            if ($cid == 24) {
                # Kinopoisk logic
                $po = $mp->get_post_options($url_data);
                if (isset($po['url'])) {
                    $url = $po['url'];
                    # Translate link
                    $turl = str_replace('https://www.kinopoisk.ru/', 'https://www-kinopoisk-ru.translate.goog/', $url);
                    $turl = $turl . 'votes/?_x_tr_sl=ru&_x_tr_tl=en&_x_tr_hl=en';
                    $url = $turl;
                }
            } else
            {
                # Other
                $url = $url_data->link;
            }
        }

        return $url;
    }

    public function get_img_urls_by_mid($mid = 0) {
        $sql = sprintf("SELECT * FROM {$this->db['site_img']} WHERE mid = %d", $mid);
        $result = $this->db_results($sql);
        return $result;
    }

    public function get_img_by_link_hash($link_hash) {
        $sql = sprintf("SELECT * FROM {$this->db['site_img']} WHERE link_hash = '%s'", $link_hash);
        $result = $this->db_fetch_row($sql);
        return $result;
    }

}
