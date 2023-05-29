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
        22 => array('ekey' => 'kinop_rating', 'name' => 'Kinopoisk', 'flag' => 'ru', 'ratmax' => 10, 'multipler' => 10),
        24 => array('ekey' => 'douban_rating', 'name' => 'Douban', 'flag' => 'cn', 'link' => 'douban', 'ratmax' => 10, 'multipler' => 10),
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
                !defined(MOVIES_LINKS_PLUGIN_DIR) ? define('MOVIES_LINKS_PLUGIN_DIR', ABSPATH . 'wp-content/plugins/movies_links/') : '';
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

        $ml_camp_ret = $this->ml_camp;
        foreach ($this->ml_camp as $cid => $item) {

            $ekey = $item['ekey'];
            if (isset($erating->$ekey) && $erating->$ekey > 0) {
                # Add only exist rating
                $ml_camp_ret[$cid] = $item;
                $ml_camp_ret[$cid]['rating'] = $erating->$ekey;
            }
        }

        if ($debug) {
            print_r($ml_camp_ret);
        }

        if (!$ml_camp_ret) {
            return $ret;
        }
        # 2. Get urls

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

        foreach ($this->ml_camp as $cid => $item) {
            if (isset($exists[$cid])) {

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
                $link_data = $this->get_link($cid, $mid);
                if ($debug) {
                    print_r($link_data);
                }
                if ($link_data) {
                    $link = $link_data->link;
                    $link_hash = $link_data->link_hash;

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

        return $ml_camp_ret;
    }

    public function get_movie_erating($mid) {
        $sql = sprintf("SELECT * FROM {$this->db['erating']} WHERE movie_id=%d", (int) $mid);
        $results = $this->db_fetch_row($sql);
        return $results;
    }

    public function get_link($cid, $mid) {
        $mp = $this->get_mp();
        $url = $mp->get_url_by_mid($mid, $cid);
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
