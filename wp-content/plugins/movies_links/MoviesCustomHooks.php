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

        if ($post->top_movie > 0) {
            // Erating logic
            $this->update_erating($post, $options, $campaign, $debug);

            // Franchises
            $this->update_franchises($post, $options, $campaign, $debug);

            // Distributors
            $this->update_distributors($post, $options, $campaign, $debug);
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

        $simple_camps = array('kinop', 'douban', 'imdb', 'animelist', 'eiga', 'moviemeter');

        // Kinopoisk
        $curr_camp = '';
        $one_five = false;
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
        } else if ($campaign->id == 36) {
            // eiga
            $curr_camp = 'eiga';
            $one_five = true;
            $score_opt = array(
                'rating' => 'rating',
                'count' => 'count'
            );
        } else if ($campaign->id == 38) {
            // moviemeter
            $curr_camp = 'moviemeter';
            $one_five = true;
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
        } else if ($campaign->id == 23) {
            // metacritic
            $curr_camp = 'metacritic';
            $score_opt = array(
                'metascore' => 'rating',
                'userscore' => 'userscore'
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
            $ma = $this->ml->get_ma();

            if (in_array($curr_camp, $simple_camps)) {
                // Update rating     
                $camp_rating = $to_update['rating'] * 10;
                $total_rating = $camp_rating;
                 
                if ($one_five) {
                    $total_rating = $ma->five_to_ten($total_rating);
                }

                $camp_count = str_replace(',', '', $to_update['count']);

                $data[$curr_camp . '_rating'] = (int) $camp_rating;
                $data[$curr_camp . '_count'] = (int) $camp_count;
                $data[$curr_camp . '_date'] = $this->mp->curr_time();
                // Total
                $data['total_count'] = $data[$curr_camp . '_count'];
                $data['total_rating'] = (int) $total_rating;

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
            } else if ($curr_camp == 'metacritic') {

                $rating = (int) $to_update['rating'];
                $userscore = (int) ($to_update['userscore'] * 10);
                // metacritic
                $data[$curr_camp . '_rating'] = $rating;
                $data[$curr_camp . '_userscore'] = $userscore;
                $data[$curr_camp . '_date'] = $this->mp->curr_time();

                $total_rating = 0;
                if ($rating && $userscore) {
                    $total_rating = (int) ($rating + $userscore) / 2;
                } else if ($userscore) {
                    $total_rating = $userscore;
                } else {
                    $total_rating = $rating;
                }

                // Total
                $data['total_rating'] = $total_rating;

                if ($data['total_rating'] > 0) {
                    $update_rating = true;
                }
            }

            if ($debug) {
                p_r($data);
            }

            if ($update_rating) {
                
                $ma->update_erating($post->top_movie, $data);
            }
        }
    }



    private function update_franchises($post, $options, $campaign, $debug = false) {
        if ($campaign->id == 33) {

            $ma = $this->ml->get_ma();



            $upd_opt = array(
                'Distributor' => 'dist_name',
                'Distributor link' => 'dist_link',
                'Franchise' => 'fr_name',
                'Franchise link' => 'fr_link',
            );

            $to_update = array();
            foreach ($upd_opt as $post_key => $db_key) {
                $field_value = '';
                if (isset($options[$post_key])) {
                    $field_value = base64_decode($options[$post_key]);
                }
                $to_update[$db_key] = $field_value;
            }
            if ($to_update) {
                // Distributor
                $dist_id = $ma->add_movie_distributor($to_update['dist_name'], $to_update['dist_link']);

                // Franchise
                $fr_id = $ma->add_movie_franchise($to_update['fr_name'], $to_update['fr_link']);

                // Add indi data
                /*
                  `movie_id` int(11) NOT NULL DEFAULT '0',
                  `date` int(11) NOT NULL DEFAULT '0',
                  `distributor` int(11) NOT NULL DEFAULT '0',
                  `franchise` int(11) NOT NULL DEFAULT '0',
                 */
                $data = array(
                    'distributor' => $dist_id,
                    'franchise' => $fr_id,
                );
                $ma->update_indie($post->top_movie, $data);
            }
        }
    }

    private function update_distributors($post, $options, $campaign, $debug = false) {
        if ($campaign->id == 34) {
            $ma = $this->ml->get_ma();
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
                // Prouduction:
                $prod_names = array('production', 'distribution');
                $prod_data = array();
                // {"id":"production","name":"Production Companies","section":{"items":[{"id":"co0283444","rowTitle":"DC Entertainment","rowLink":"/company/co0283444?ref_=ttco_co_0","listContent":[{"text":""}]},{"id":"co0179825","rowTitle":"The Safran Company","rowLink":"/company/co0179825?ref_=ttco_co_1","listContent":[{"text":""}]},{"id":"co0002663","rowTitle":"Warner Bros.","rowLink":"/company/co0002663?ref_=ttco_co_2","listContent":[{"text":""}]}],"total":3,"endCursor":"Mw=="}}
                // {"id":"distribution","name":"Distributors","section":{"items":[{"id":"co0237749","rowTitle":"Kinomania","rowLink":"/company/co0237749?ref_=ttco_co_0","listContent":[{"text":"(Ukraine, 2023)","subText":"(theatrical)"}]},{"id":"co0826866","rowTitle":"Warner Bros. Holland","rowLink":"/company/co0826866?ref_=ttco_co_1","listContent":[{"text":"(Netherlands, 2023)","subText":"(theatrical)"}]},{"id":"co0519888","rowTitle":"Warner Bros. Pictures Germany","rowLink":"/company/co0519888?ref_=ttco_co_2","listContent":[{"text":"(Germany, 2023)","subText":"(theatrical)"}]},{"id":"co0498895","rowTitle":"Warner Bros. Pictures","rowLink":"/company/co0498895?ref_=ttco_co_3","listContent":[{"text":"(Argentina, 2023)","subText":"(theatrical)"}]},{"id":"co0816712","rowTitle":"Warner Bros. Pictures","rowLink":"/company/co0816712?ref_=ttco_co_4","listContent":[{"text":"(United Kingdom, 2023)","subText":"(theatrical)"}]}],"total":9,"endCursor":"OA=="}},
                foreach ($prod_names as $name) {
                    if (preg_match('/{"id":"' . $name . '".*"endCursor":"[^"]+"}}/U', $code, $match)) {
                        try {
                            $prod_data[$name] = json_decode($match[0]);
                        } catch (Exception $ex) {
                            
                        }
                    }
                }

                if ($debug) {
                    print_r($prod_data);
                }

                if ($prod_data) {
                    foreach ($prod_data as $name => $data) {
                        /*
                         * TODO
                         * get data with regexp
                         * if total count > data count, change state
                         */

                        // Check post count
                        $total_count = isset($data->section->total) ? $data->section->total : 0;
                        if ($total_count > 5) {
                            if ($debug) {
                                print "Total count: {$total_count}. Try to get data from regexp\n";
                            }

                            $total_match = 0;

                            if (preg_match('/<div data-testid="sub-section-' . $name . '".*<\/ul><\/div><\/section>/Us', $code, $match)) {
                                if (preg_match_all('/<a class="ipc-metadata-list-item__label[^>]+href="\/company\/([co0-9]+)\?[^>]+">([^<]+)<\/a>(<div class="ipc-metadata-list-item__content-container">.*<\/div>)/Us', $match[0], $match_link)) {
                                    if ($debug) {
                                        print_r($match_link);
                                    }
                                    $total_match = sizeof($match_link[0]);
                                    for ($i = 0; $i < $total_match; $i++) {
                                        $data_link = $match_link[1][$i];
                                        $data_title = $match_link[2][$i];
                                        $data_text = strip_tags($match_link[3][$i]);

                                        if ($data_title) {
                                            $this->add_distributor($ma, $top_movie, $name, $data_title, $data_link, $data_text, $debug);
                                        }
                                    }
                                }
                            }

                            if ($total_count > $total_match) {
                                if ($debug) {
                                    print_r("Need advanced parsing post. Move URL status to error. $total_count > $total_match\n");
                                }
                                //$this->mp->change_url_state($uid, 4);
                            }
                        } else {
                            if ($debug) {
                                print "Total count: {$total_count}. Get data from json\n";
                            }
                            $data_items = isset($data->section->items) ? $data->section->items : array();
                            if ($data_items) {
                                foreach ($data_items as $item) {
                                    /*
                                      [id] => co0408520
                                      [rowTitle] => CEL Film Distribution
                                      [rowLink] => /company/co0408520?ref_=ttco_co_2
                                      [listContent] => Array
                                      (
                                      [0] => stdClass Object
                                      (
                                      [text] => (Australia, 1986)
                                      [subText] => (theatrical)
                                      )

                                      )
                                     */
                                    $data_link = $item->id;
                                    $data_title = $item->rowTitle;
                                    $data_text = isset($item->listContent[0]->text) ? $item->listContent[0]->text : '';

                                    if ($data_title) {
                                        $this->add_distributor($ma, $top_movie, $name, $data_title, $data_link, $data_text, $debug);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    private function add_distributor($ma, $top_movie, $name, $data_title, $data_link, $data_text, $debug) {
        // Add meta
        if ($name == 'production') {
            // Add production
            $dist_id = $ma->add_movie_distributor($data_title, $data_link);

            // Add meta
            $meta_id = $ma->add_distributor_meta($top_movie, $dist_id);

            if ($debug) {
                print_r(array($name, $dist_id, $data_title, $data_text, $meta_id));
            }
        } else {
            // Add distributor
            if (strstr($data_text, 'United States') || strstr($data_text, 'World-wide')) {
                $dist_id = $ma->add_movie_distributor($data_title, $data_link);
                // Add meta
                $meta_type = 1;
                $meta_id = $ma->add_distributor_meta($top_movie, $dist_id, $meta_type);
                if ($debug) {
                    print_r(array($name, $dist_id, $data_title, $data_text, $meta_id));
                }
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
