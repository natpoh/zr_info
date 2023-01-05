<?php

/*
 * Get rating from douban and save it to meta
 */

class Reviews extends MoviesAbstractDBAn {

    private $ma;
    private $ml;
    private $mp;

    public function __construct($ml) {
        $this->ml = $ml ? $ml : new MoviesLinks();
        $this->mp = $this->ml->get_mp();
        $this->ma = $this->ml->get_ma();
    }

    public function reviews_cron_meta($count = 10, $force = false, $debug = false) {
        /*
         * TODO
         * 1. Get ml posts by last_upd
         * 2. Get calculate rating
         * 3. Update or create rating meta
          `reviews_rating` int(11) NOT NULL DEFAULT '0',
          `reviews_result` int(11) NOT NULL DEFAULT '0',
         * reviews_posts
          `reviews_date` int(11) NOT NULL DEFAULT '0',
         */
        $cron_key = 'reviews_cron_rating';

        $last_id = $this->get_option($cron_key, 0);
        if ($force) {
            $last_id = 0;
        }

        // id, uid, mid, rating, result
        $last_mids = $this->ma->get_rating_movies($last_id, $count);
                
        $mids = array();
        if ($last_mids) {
            foreach ($last_mids as $item) {
                $mids[$item->fid] = 1;
            }
        }
        
        
        if ($debug) {
            p_r($mids);
            p_r(array('last_id', $last_id));
        }


        if ($mids) {
            $last_posts = array_keys($mids);
            $last = end($last_mids);
            $last_id = $last->id;

            if ($debug) {
                p_r($last_posts);
            }

            $ratings = array();
            foreach ($last_posts as $mid) {
                $last_mids = $this->ma->get_review_rating_posts($mid);
                foreach ($last_mids as $post) {
                    if ($ratings[$mid]['rating']) {
                        $ratings[$mid]['rating'] += $post->rating;
                        $ratings[$mid]['total'] += 1;
                    } else {
                        $ratings[$mid]['rating'] = $post->rating;
                        $ratings[$mid]['total'] = 1;
                    }
                }
            }


            foreach ($ratings as $pid => $post) {
                // Get fchan posts
                $rating_count = $post['total'];
                $rating_update = (int) round($post['rating'] / $rating_count, 0);

                if ($rating_count == 0) {
                    continue;
                }

                if ($debug) {
                    p_r(array($pid, $rating_update));
                }

                $time = $this->curr_time();
                $rating_result = (int) round((($rating_update + 25) / 25), 0);

                // Update rating
                $data = array(
                    'last_upd' => $time,
                    'reviews_rating' => $rating_update,
                    'reviews_result' => $rating_result,
                    'reviews_posts' => $rating_count,
                    'reviews_date' => $time,
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
