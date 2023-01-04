<?php

/*
 * Get rating from douban and save it to meta
 */

class Douban extends MoviesAbstractDBAn {

    private $ma;
    private $ml;
    private $mp;    
    private $cid = 22;

    public function __construct($ml) {
        $this->ml = $ml ? $ml : new MoviesLinks();
        $this->mp = $this->ml->get_mp();
        $this->ma = $this->ml->get_ma();
    }

    public function douban_cron_meta($count = 10, $force = false, $debug = false) {
        /*
         * TODO
         * 1. Get ml posts by last_upd
         * 2. Get calculate rating
         * 3. Update or create rating meta
          `douban_rating` int(11) NOT NULL DEFAULT '0',
          `douban_result` int(11) NOT NULL DEFAULT '0',
          `douban_date` int(11) NOT NULL DEFAULT '0',
         */
        $cron_key = 'douban_cron_rating';

        $last_id = $this->get_option($cron_key, 0);
        if ($force) {
            $last_id = 0;
        }

        $cid = $this->cid;
        $status = 1;
        $last_posts = $this->mp->get_last_posts($count, $cid, -1, $status, $last_id, "ASC");

        if ($last_posts) {
            $last = end($last_posts);
            $last_id = $last->id;

            // Get rating            
            foreach ($last_posts as $post) {
                $options = unserialize($post->options);

                $score_opt = array(
                    'rating',
                );

                $rating_update = 0;
                foreach ($score_opt as $post_key) {
                    if (isset($options[$post_key])) {
                        $field_value = base64_decode($options[$post_key]);
                        $rating_update = (int) ($field_value * 10);
                    }
                }
                
                if ($rating_update==0){
                    continue;
                }
                
                $pid = $post->pid;
                if ($debug) {
                    p_r(array($pid, $rating_update));
                }
                $time = $this->curr_time();

                $rating_result = (int) round((($rating_update + 25) / 25), 0);
                // Update rating
                $data = array(
                    'last_upd' => $time,
                    'douban_rating' => $rating_update,
                    'douban_result' => $rating_result,
                    'douban_date' => $time,
                    'total_rating'=>$rating_result,
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
