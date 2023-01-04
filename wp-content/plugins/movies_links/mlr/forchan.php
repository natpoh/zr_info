<?php

/*
 * Get rating from douban and save it to meta
 */

class Forchan extends MoviesAbstractDBAn {

    private $ma;
    private $ml;
    private $mp;
    private $cid = 30;

    public function __construct($ml) {
        $this->ml = $ml ? $ml : new MoviesLinks();
        $this->mp = $this->ml->get_mp();
        $this->ma = $this->ml->get_ma();
    }

    public function forchan_cron_meta($count = 10, $force = false, $debug = false) {
        /*
         * TODO
         * 1. Get ml posts by last_upd
         * 2. Get calculate rating
         * 3. Update or create rating meta
          `douban_rating` int(11) NOT NULL DEFAULT '0',
          `douban_result` int(11) NOT NULL DEFAULT '0',
          `douban_date` int(11) NOT NULL DEFAULT '0',
         */
        $cron_key = 'forchan_cron_rating';

        $last_id = $this->get_option($cron_key, 0);
        if ($force) {
            $last_id = 0;
        }

        $cid = $this->cid;
        $status = 1;
        $last_posts = $this->mp->get_last_upd_urls($cid, $status, $last_id, $count);
        
        if ($debug){
            p_r($last_posts);
        }

        if ($last_posts) {
            $last = end($last_posts);
            $last_id = $last->last_upd;

            // Get rating            
            foreach ($last_posts as $url) {
                // Get fchan posts
                $ratings = $this->mp->get_fchan_posts($url->id);
                p_r($ratings);
                $rating_count = 0;
                $rating_update = 0;
                
                if ($ratings) {                   

                    foreach ($ratings as $item) {
                        $rating = $item->rating;
                        $rating_update += $rating;
                        $rating_count += 1;
                    }
                    if ($rating_count) {
                        $rating_update = $rating_update / $rating_count;
                    }
                }

                if ($rating_count == 0) {
                    continue;
                }

                $pid = $url->pid;
                if ($debug) {
                    p_r(array($pid, $rating_update));
                }
                $time = $this->curr_time();
                $rating_result = (int) round((($rating_update + 25) / 25), 0);

                // Update rating
                $data = array(
                    'last_upd' => $time,
                    'fchan_rating' => $rating_update,
                    'fchan_result' => $rating_result,
                    'fchan_posts' => $rating_count,
                    'fchan_date' => $time,
                    'total_rating' => $rating_result,
                );

                if ($debug) {
                    p_r($data);
                }

                $this->ma->update_erating($pid, $data);
            }

            $this->update_option($cron_key, $last_id);
        }
    }

}
