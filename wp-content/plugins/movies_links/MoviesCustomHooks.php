<?php

/*
 * Custom hook functions from Movies Links
 */

class MoviesCustomHooks {

    private $ml = '';
    private $mp = '';

    public function __construct($ml) {
        $this->ml = $ml ? $ml : new MoviesLinks();
        $this->mp = $this->ml->get_mp();
    }

    public function add_post($campaign = array(), $post = array(), $debug = false) {

        $options = unserialize($post->options);

        // Erating logic
        if ($post->top_movie > 0) {
            $this->update_erating($post, $options, $campaign, $debug);
        }
        // Tomatoes logic
        // UNUSED DEPRECATED
        // $this->update_rotten_tomatoes($post, $options);
        // Dove.org
        if ($campaign->id == 3) {
            $this->update_dove($post, $options, $campaign, $debug);
        }

        CustomHooks::do_action('ml_add_post', ['campaign' => $campaign, 'post' => $post]);
    }

    public function add_actors($campaign = array(), $post = array(), $debug = false) {
        $options = unserialize($post->options);

        $mlr = $this->ml->get_campaing_mlr($campaign);
        if ($mlr) {
            if ($debug) {
                print_r("Found mlr for " . $campaign->title . "\n");
            }
            $mlr->hook_update_post($campaign, $post, $options, $debug);
        }
    }

    private function update_erating($post, $options, $campaign, $debug = false) {

        $simple_camps = array('kinop', 'douban', 'imdb', 'animelist');

        // Kinopoisk
        $curr_camp = '';
        if ($campaign->id == 24) {
            $curr_camp = 'kinop';
            $score_opt = array(
                'ratingKinopoisk' => 'rating',
                'ratingKinopoiskVoteCount' => 'count'
            );
        } else if ($campaign->id == 22) {
            // douban
            $curr_camp = 'douban';
            $score_opt = array(
                'rating' => 'rating',
                'ratingCount' => 'count'
            );
        } else if ($campaign->id == 18) {
            // douban
            $curr_camp = 'imdb';
            $score_opt = array(
                'rating' => 'rating',
                'count' => 'count'
            );
        } else if ($campaign->id == 27) {
            // animelist
            $curr_camp = 'animelist';
            $score_opt = array(
                'score' => 'rating',
                'count' => 'count'
            );
            // Add anime genre
            $ma = $this->ml->get_ma();
            $ma->add_genre_meta($post->top_movie, 'anime');
        } else if ($campaign->id == 20 || $campaign->id == 21) {
            // rt movies (20) and tv (21)
            $curr_camp = 'rt';
            $score_opt = array(
                'tomatometerScore' => 'rating',
                'tomatometerCount' => 'count',
                'audienceScore' => 'aurating',
                'audienceCount' => 'aucount',
            );
        }

        $to_update = array();
        foreach ($score_opt as $post_key => $db_key) {
            if (isset($options[$post_key])) {
                $field_value = base64_decode($options[$post_key]);
                $to_update[$db_key] = $field_value;
            }
        }

        if ($to_update) {

            $update_rating = false;

            $data = array();

            if (in_array($curr_camp, $simple_camps)) {
                // Update rating            
                $data[$curr_camp . '_rating'] = (int) ($to_update['rating'] * 10);
                $data[$curr_camp . '_count'] = (int) str_replace(',', '', $to_update['count']);
                $data[$curr_camp . '_date'] = $this->mp->curr_time();
                // Total
                $data['total_count'] = $data[$curr_camp . '_count'];
                $data['total_rating'] = $data[$curr_camp . '_rating'];

                if ($data['total_count'] > 0 || $data['total_rating'] > 0) {
                    $update_rating = true;
                }
            } else if ($curr_camp == 'rt') {
                // Rotten tomatoes
                $data['rt_rating'] = (int) $to_update['rating'];
                $data['rt_count'] = (int) $to_update['count'];
                $data['rt_aurating'] = (int) $to_update['aurating'];
                $data['rt_aucount'] = (int) $to_update['aucount'];
                $data['rt_date'] = $this->mp->curr_time();

                // Total count
                $data['total_count'] = $data['rt_count'] + $data['rt_aucount'];

                // Gap: audience - pro
                $data['rt_gap'] = $data['rt_aurating'] - $data['rt_rating'];

                // Total rating
                $data['total_rating'] = 0;

                if ($data['rt_rating'] && $data['rt_aurating']) {
                    $data['total_rating'] = ($data['rt_rating'] + $data['rt_aurating']) / 2;
                } else if ($data['rt_rating']) {
                    $data['total_rating'] = $data['rt_rating'];
                } else if ($data['rt_aurating']) {
                    $data['total_rating'] = $data['rt_aurating'];
                }

                if ($data['total_count'] > 0 || $data['total_rating'] > 0) {
                    $update_rating = true;
                }
            }

            if ($debug) {
                p_r($data);
            }

            if ($update_rating) {
                $ma = $this->ml->get_ma();
                $ma->update_erating($post->top_movie, $data);
            }
        }
    }

    private function update_rotten_tomatoes($post, $options) {
        // DEPRECATED UNUSED
        $score_opt = array(
            'tomatometerScore' => 'rotten_tomatoes',
            'audienceScore' => 'rotten_tomatoes_audience'
        );

        $to_update = array();
        foreach ($score_opt as $post_key => $db_key) {
            if (isset($options[$post_key])) {
                $field_value = base64_decode($options[$post_key]);
                $to_update[$db_key] = (int) $field_value;
            }
        }
        if ($to_update) {
            $ma = $this->ml->get_ma();
            $ma->update_movie_rating($post->top_movie, $to_update);
        }
    }

    /*
     * Dove
     */

    private function update_dove($post, $options, $campaign, $debug = false) {
        $cid = $campaign->id;
        $uid = $post->uid;
        $url_data = $this->mp->get_url($uid);
        $link = $url_data->link;
        $arhive = $this->mp->get_arhive_by_url_id($uid);
        $link_hash = $arhive->arhive_hash;
        $top_movie = $post->top_movie;

        if ($debug) {
            p_r($arhive);
        }

        $code = $this->mp->get_arhive_file($cid, $link_hash);
        if ($code) {
            $post_result = $this->find_in_post_page($code);
            if ($debug) {
                p_r($post_result);
            }
            if (sizeof($post_result['rating'])) {

                // Add found data to db                                        
                $rating_json = json_encode($post_result['rating']);
                $rating_info_json = '';
                if (sizeof($post_result['rating_info'])) {
                    $rating_info_json = json_encode($post_result['rating_info']);
                }

                $data = array(
                    'dove_date' => $this->mp->curr_time(),
                    'dove_link' => $link,
                    'dove_rating' => $rating_json,
                    'dove_rating_desc' => $rating_info_json
                );
                if ($debug) {
                    p_r($data);
                }
                $ma = $this->ml->get_ma();
                $ma->update_pg_rating($data, $top_movie);
                // Save log
                $message = 'Update Dove rating';
                $log_status = 0;
                $this->mp->log_info($message, $cid, $uid, $log_status);
            }
        } else {
            if ($debug) {
                print 'arhive not found';
            }
        }
    }

    /*
     * Find data in post page by regexp
     */

    private function find_in_post_page($code) {

        $rating = $rating_info = $info = array();
        // Rating grid
        if (preg_match('|<div class="rating-grid-view">.*<div class="clear content-rating-desc">|Us', $code, $match)) {
            if (preg_match_all('|<div class="hr1">([^<]+)</div>[^<]*<div class="hr2">[^<]*<div class="s([0-9]+)"|s', $match[0], $match_rating)) {
                //Found first item
                for ($i = 0; $i < sizeof($match_rating[1]); $i += 1) {
                    $key = ucfirst(trim($match_rating[1][$i]));
                    $result = trim($match_rating[2][$i]);
                    $rating[$key] = $result;
                }
            }
        }
        //$this->p_r($rating);
        //Description grid
        if (preg_match('|<div class="review-content-desc">(.*</div>)[^<]*</div>[^<]*</div>|Us', $code, $match)) {
            if (preg_match_all('|<div[^>]*><a[^>]*></a><b>([^<]+)</b>([^<]+)</div>|s', $match[0], $match_info)) {
                //Found first item
                for ($i = 0; $i < sizeof($match_info[1]); $i += 1) {
                    $key = trim(str_replace(':', '', $match_info[1][$i]));
                    $result = trim($match_info[2][$i]);
                    $rating_info[$key] = htmlspecialchars($result);
                }
            }
        }
        // $this->p_r($rating_info); 
        //Business info
        if (preg_match('|<div class="business-info">.*</div>[^<]*</div>|Us', $code, $match)) {
            if (preg_match_all('|<div><span>([^<]+)</span>(.*)</div>|Us', $match[0], $match_info)) {
                //$this->p_r($match_info);
                /*
                 *     [1] => Array
                  (
                  [0] => Company:
                  [1] => Writer:
                  [2] => Director:
                  [3] => Producer:
                  [4] => Genre:
                  [5] => Runtime:
                  [6] => Industry Rating:
                  [7] => Starring:
                  [8] => Reviewer:
                  )

                  [2] => Array
                  (
                  [0] =>  20th Century Fox Home Ent.
                  [1] =>  Sam Harper
                  [2] =>  Daniel Stern
                  [3] =>  Robert Harper
                  [4] =>  Children
                  [5] =>  103 min.
                  [6] =>  PG
                  [7] =>  Thomas Ian Nicholas,
                  Gary Busey,
                  Albert Hall,
                  Amy Morton
                  [8] =>  Edwin L. Carpenter
                  )
                 */
                for ($i = 0; $i < sizeof($match_info[1]); $i += 1) {
                    $key = trim(str_replace(':', '', $match_info[1][$i]));
                    $value = trim($match_info[2][$i]);
                    $info[$key] = $value;
                }
            }
        }

        $reliase = '';
        //Reliase
        if (preg_match('|<div class="therelease"><span>[^<]+</span>([^<]+)</div>|', $code, $match)) {
            $reliase = trim($match[1]);
        } else if (preg_match('|<div class="vidrelease"><span>[^<]+</span>([^<]+)</div>|', $code, $match)) {
            $reliase = trim($match[1]);
        }


        // Return array
        return array('rating' => $rating, 'rating_info' => $rating_info, 'info' => $info, 'reliase' => $reliase);
    }

}
