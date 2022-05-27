<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class MoviesCustomHooksCron {

    private $db;
    private $ml;
    private $mp;

    public function __construct($ml = '') {
        $this->ml = $ml ? $ml : new MoviesLinks();
        $this->mp = $this->ml->get_mp();
        $this->db = array(
            'posts' => 'movies_links_posts',
        );
    }

    public function run_cron($count = 100, $cid = 0, $debug = false, $force = false) {
        // 1. Get campaign
        if (!$cid) {
            return;
        }

        $campaign = $this->mp->get_campaign($cid);
        if (!$campaign) {
            return;
        }
        $options = $this->mp->get_options($campaign);
        // 2. Get last run id
        $last_run_id = isset($options['links']['custom_last_run_id']) ? $options['links']['custom_last_run_id'] : 0;
        if ($force) {
            $last_run_id = 0;
        }
        if ($debug) {
            print_r("Last run post id: " . $last_run_id);
        }

        // 3. Get posts        
        $result = $this->mp->get_last_posts($count, $cid, 1, -1, $last_run_id, "ASC");
        if ($debug) {
            print_r($result);
        }

        // 4. Run hook for posts
        if ($result) {

            // Udpdate last run
            $last = end($result);
            $last_id = $last->id;
            $options['links']['custom_last_run_id'] = $last_id;
            $this->mp->update_campaign_options($cid, $options);

            //Movies custom hook
            $mch = $this->ml->get_mch();
            if ($campaign->type == 1) {
                // Actors logic
                foreach ($result as $post) {
                    $mch->add_actors($campaign, $post, $debug);
                }
            } else {
                // Posts logic
                foreach ($result as $post) {
                    $mch->add_post($campaign, $post, $debug);
                }
            }
        }
    }

}
