<?php

/*
 * Get rating from douban and save it to meta
 */

class Forchan extends MoviesAbstractDBAn {

    private $ma;
    private $ml;
    private $mp;

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
        $min_title_weight = 10;

        $last_id = $this->get_option($cron_key, 0);
        if ($force) {
            $last_id = 0;
        }

        // id, uid, mid, rating, result
        $last_mids = $this->mp->get_fchan_posts_rating($last_id, $count);
        $mids = array();
        if ($last_mids) {
            foreach ($last_mids as $item) {
                $mids[$item->mid] = $item->uid;
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
                $movie = $this->ma->get_movie_by_id($mid);
                //p_r($movie);
                /*
                  [weight] => 0
                  [weight_upd] => 1675739701
                  [title_weight] => 0
                  [title_weight_upd] => 0
                 */
                $title_weight = $movie->title_weight;
                if ($debug) {
                    print_r(array('title_weight',$title_weight));
                }

                if ($title_weight >= $min_title_weight) {
                    $last_mids = $this->mp->get_fchan_posts($mid);
                    foreach ($last_mids as $post) {
                        if ($ratings[$mid]['rating']) {
                            $ratings[$mid]['rating'] += $post->rating;
                            $ratings[$mid]['total'] += 1;
                        } else {
                            $ratings[$mid]['rating'] = $post->rating;
                            $ratings[$mid]['total'] = 1;
                            $ratings[$mid]['valid'] = 1;
                        }
                    }
                } else {
                    // Empty rating
                    $ratings[$mid]['rating'] = 0;
                    $ratings[$mid]['total'] = 0;
                    $ratings[$mid]['valid'] = 0;
                }
            }

            if ($debug) {
                p_r($ratings);
            }
            $time = $this->curr_time();
            foreach ($ratings as $pid => $post) {
                // Get fchan posts

                $valid = $post['valid'];
                if ($valid) {
                    $rating_count = $post['total'];
                    $rating_update = (int) round($post['rating'] / $rating_count, 0);

                    if ($rating_count == 0) {
                        continue;
                    }

                    if ($debug) {
                        p_r(array($pid, $rating_update));
                    }



                    // Get fchan_posts_found
                    $uid = $mids[$pid];
                    $fchan_posts_found = $this->mp->get_fchan_posts_found($uid);
                    if (!$fchan_posts_found) {
                        $fchan_posts_found = $rating_count;
                    }


                    // Update rating
                    $data = array(
                        'last_upd' => $time,
                        'fchan_rating' => $rating_update,
                        'fchan_posts_found' => $fchan_posts_found,
                        'fchan_posts' => $rating_count,
                        'fchan_date' => $time,
                        'total_rating' => 0,
                        'total_count' => $fchan_posts_found,
                    );
                } else {
                    // Clear invalid rating
                    $data = array(
                        'last_upd' => $time,
                        'fchan_rating' => 0,
                        'fchan_posts_found' => 0,
                        'fchan_posts' => 0,
                        'fchan_date' => $time,
                        'total_rating' => 0,
                        'total_count' => 0,
                    );
                }
                if ($debug) {
                    p_r($data);
                }

                $this->ma->update_erating($pid, $data);
            }

            $this->update_option($cron_key, $last_id);
        }
    }

}
