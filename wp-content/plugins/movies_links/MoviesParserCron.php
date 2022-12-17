<?php

class MoviesParserCron extends MoviesAbstractDB {

    private $max_cron_time = 20;
    private $ml;
    private $mp;
    private $cron_types = array(
        1 => 'arhive',
        2 => 'parsing',
        3 => 'links',
        4 => 'cron_urls',
        5 => 'gen_urls',
    );

    public function __construct($ml = '') {
        $this->ml = $ml ? $ml : new MoviesLinks();
        $this->mp = $this->ml->get_mp();
    }

    public function run_cron($cron_type = 1, $debug = false, $force = false) {
        $count = $this->process_all($cron_type, $debug, $force);
        return $count;
    }

    public function process_all($cron_type, $debug = false, $force = false) {
        $campaigns = $this->mp->get_campaigns(1, -1, 1, '', 'ASC', 0);
        if ($debug) {
            print_r($campaigns);
        }
        $count = 0;
        foreach ($campaigns as $campaign) {
            $count += $this->check_time_campaign($campaign, $cron_type, $debug, $force);
            $time = (int) $this->timer_stop(0);
            if ($time > $this->max_cron_time) {
                break;
            }
        }
        return $count;
    }

    public function check_time_campaign($campaign, $cron_type, $debug = false, $force = false) {
        $count = 0;
        $options = $this->mp->get_options($campaign);

        $type_name = isset($this->cron_types[$cron_type]) ? $this->cron_types[$cron_type] : '';

        if (!$type_name || !isset($options[$type_name])) {
            return $count;
        }

        $type_opt = $options[$type_name];
        $active = $type_opt['status'];
        if ($active == 1) {
            $update_interval = $type_opt['interval'];
            $update_last_time = $type_opt['last_update'];

            $next_update = $update_last_time + $update_interval * 60;
            $currtime = $this->curr_time();

            if ($currtime > $next_update || $force) {
                // Update timer
                $options_upd = array();
                $options_upd[$type_name]['last_update'] = $currtime;
                $this->mp->update_campaign_options($campaign->id, $options_upd);

                $count = $this->process_campaign($campaign, $options, $type_name, $debug);
            }
        }
        return $count;
    }

    public function process_campaign($campaign, $options, $type_name, $debug = false) {

        if ($debug) {
            print_r($type_name);
        }

        if ($type_name == 'arhive') {
            $count = $this->proccess_arhive($campaign, $options);
        } else if ($type_name == 'parsing') {
            if ($campaign->type == 2) {
                $count = $this->proccess_parsing_create_urls($campaign, $options, false, $debug);
            } else {
                $count = $this->proccess_parsing($campaign, $options);
            }
        } else if ($type_name == 'links') {
            $count = $this->proccess_links($campaign, $options);
        } else if ($type_name == 'cron_urls') {
            $count = $this->mp->proccess_cron_urls($campaign, $options);
            if ($count) {
                // Unpaused arhives            
                $this->start_paused_module($campaign, 'arhive', $options);
            }
        } else if ($type_name == 'gen_urls') {
            $count = $this->mp->proccess_gen_urls($campaign, $options, $debug);
            if ($count) {
                // Unpaused arhives            
                $this->start_paused_module($campaign, 'arhive', $options);
            }
        } else if ($type_name == 'delete_garbage') {
            
        }



        return $count;
    }

    private function proccess_arhive($campaign, $options) {
        $type_name = 'arhive';
        $type_opt = $options[$type_name];

        // Already progress
        $progress = isset($type_opt['progress']) ? $type_opt['progress'] : 0;
        $currtime = $this->curr_time();
        if ($progress) {
            // Ignore old last update            
            $wait = 180; // 3 min
            if ($currtime < $progress + $wait) {
                $message = 'Archiving is in progress already.';
                $this->mp->log_warn($message, $campaign->id, 0, 2);
                return 0;
            }
        }

        // Update progress
        $options_upd = array();
        $options_upd[$type_name]['progress'] = $currtime;
        $this->mp->update_campaign_options($campaign->id, $options_upd);


        // Get posts (last is first)        
        $urls_count = $type_opt['num'];

        // Random urls
        $random_urls = $type_opt['random'];

        // Get last urls
        $status = 0;
        $urls = $this->mp->get_last_urls($urls_count, $status, $campaign->id, $random_urls);

        $count = count((array) $urls);
        if ($count) {
            $this->get_async_cron($campaign, $type_name);
            // $this->arhive_urls($campaign, $options, $urls);
        } else {
            // Campaign done
            // Status auto-stop
            /*
              status:
              0 => 'Other',
              1 => 'Find URLs',
              2 => 'Arhive',
              3 => 'Parsing',
              4 => 'Links',
             */
            $options_upd = array();
            $options_upd[$type_name]['status'] = 3;
            $this->mp->update_campaign_options($campaign->id, $options_upd);
            $message = 'All URLs parsed to arhive';
            $this->mp->log_info($message, $campaign->id, 0, 2);
        }
        return $count;
    }

    public function get_async_cron($campaign, $type_name = '') {
        $site_url = get_site_url();
        $url = $site_url . '/wp-content/plugins/movies_links/cron/async_cron.php?p=8ggD_23_2D0DSF-F&type=' . $type_name . '&cid=' . $campaign->id;

        $this->mp->send_curl_no_responce($url);
    }

    private function arhive_urls($campaign, $options, $urls = array()) {
        $type_name = 'arhive';
        $type_opt = $options[$type_name];
        if ($urls) {
            foreach ($urls as $item) {
                $this->arhive_url($item, $campaign, $type_opt);
            }
        }

        // Unpaused parsing            
        $this->start_paused_module($campaign, 'parsing', $options);

        // Remove proggess flag
        $options_upd = array();
        $options_upd[$type_name]['progress'] = 0;
        $this->mp->update_campaign_options($campaign->id, $options_upd);
    }

    private function proccess_parsing($campaign, $options, $force = false) {
        ini_set('max_execution_time', '300'); //300 seconds = 5 minutes
        set_time_limit(300);

        $type_name = 'parsing';
        $cid = $campaign->id;
        $type_opt = $options[$type_name];

        // Get posts (last is first)        
        $urls_count = $type_opt['num'];
        $count = 0;

        // Get last posts
        $last_posts = $this->mp->get_last_arhives_no_posts($urls_count, $cid);

        if ($last_posts) {
            $items = $this->mp->parse_arhives($last_posts, $campaign);
            foreach ($items as $uid => $item) {
                if ($item) {

                    if ($type_opt['multi_parsing'] == 1) {
                        // Multi post parsing
                        $content = '';
                        foreach ($item as $key => $value) {
                            $row = '<div id="' . $key . '">'."\n";
                            foreach ($value as $row_key => $row_value) {
                                $row .= '<p class="' . $row_key . '">' . trim($row_value) . "</p>\n";
                            }
                            $row .= "</div>\n";
                            $content .= $row;
                        }
                        $item = array(
                            't' => 'Multi ' . $uid,
                            'content' => $content
                        );
                    }
                    
                    $this->parsing_post_add($item, $cid, $uid, $force);

                    $count += 1;
                } else {
                    $message = 'Can not parse post data';
                    $this->mp->log_error($message, $cid, $uid, 3);
                    // Status error
                    $status = 4;
                    $this->mp->change_url_state($uid, $status, true);
                }
            }
            // Unpaused links            
            $this->start_paused_module($campaign, 'links', $options);
        } else {
            // Campaign done
            // Status auto-stop
            $options_upd = array();
            $options_upd[$type_name]['status'] = 3;
            $this->mp->update_campaign_options($campaign->id, $options_upd);
            $message = 'All arhives parsed to posts';
            $this->mp->log_info($message, $campaign->id, 0, 3);
        }
        return $count;
    }

    private function parsing_post_add($item, $cid, $uid, $force) {
        // Add post
        
        $post_exist = $this->mp->get_post_by_uid($uid);
        
        if (!$post_exist || $force) {
            $title = '';
            $year = '';
            $release = '';
            $post_options = array();
            foreach ($item as $key => $value) {
                if ($key == 't') {
                    $title = $value;
                } else if ($key == 'y') {
                    $year = $value;
                } else if ($key == 'r') {
                    $release = $value;
                } else {
                    $post_options[$key] = base64_encode($value);
                }
            }

            // Status publish
            $status = 1;
            if (!$title) {
                // Can't find title
                $status = 0;
            }

            if (!$post_exist) {

                $top_movie = 0;
                $rating = 0;
                $this->mp->add_post($uid, $status, $title, $release, $year, $post_options, $top_movie, $rating);

                if ($title) {
                    $message = 'Add post: ' . $title;
                    $this->mp->log_info($message, $cid, $uid, 3);
                } else {
                    $message = 'Can not parse the Title';
                    $this->mp->log_error($message, $cid, $uid, 3);
                }
            } else {
                //Force update post
                $top_movie = $post_exist->top_movie;
                $rating = $post_exist->rating;
                $this->mp->update_post($uid, $status, $title, $release, $year, $post_options, $top_movie, $rating);
            }
        } else {
            $message = 'Post already exist';
            $this->mp->log_warn($message, $cid, $uid, 3);
        }
    }

    private function proccess_parsing_create_urls($campaign, $options, $force = false, $debug = false) {
        $type_name = 'parsing';
        $cid = $campaign->id;
        $type_opt = $options[$type_name];

        // Get posts (last is first)        
        $urls_count = $type_opt['num'];
        $count = 0;

        // Get last posts
        $last_posts = $this->mp->get_last_arhives_no_posts($urls_count, $cid);
        if ($debug) {
            print_r($last_posts);
        }

        if ($last_posts) {

            $items = $this->mp->parse_arhives($last_posts, $campaign);
            if ($debug) {
                print_r($items);
            }
            $lo = $options['links'];
            $urls = $this->mp->find_url_posts_links($items, $lo, $debug);
            $ms = $this->ml->get_ms();

            $cid_dst = $lo['camp'];
            $new_url_weight = $lo['weight'];

            if ($debug) {
                print_r($urls);
            }

            if ($urls && $cid_dst) {
                foreach ($urls as $uid => $found) {
                    $post_exist = $this->mp->get_post_by_uid($uid);

                    $movie_id = 0;

                    if (!$post_exist || $force) {
                        $url = $this->mp->get_url($uid);
                        $movie = array();
                        $movie_id = $url->pid ? $url->pid : 0;

                        $post_options = array();

                        $status = 0;



                        // Add urls
                        $add_urls = array();

                        if ($found) {

                            foreach ($found as $item) {
                                if ($item['results'] && sizeof($item['results']) > 0) {
                                    $first_result = array_pop($item['results']);
                                    if ($first_result && $first_result['total']['valid'] && $first_result['total']['valid'] == 1) {
                                        // Add link
                                        if ($item['post']->url) {
                                            $add_urls[] = $item['post']->url;
                                            $count += 1;
                                        }
                                    }
                                }
                            }
                        }

                        if ($debug) {
                            print_r($add_urls);
                        }

                        if ($add_urls) {
                            // Add urls
                            foreach ($add_urls as $to_add) {
                                $this->mp->add_url($cid_dst, $to_add, $movie_id, $new_url_weight);
                            }
                            // Parsed done
                            $status = 1;
                            foreach ($add_urls as $key => $value) {
                                $post_options[$key] = base64_encode($value);
                            }
                        }

                        $title = 'no title';
                        $year = '';
                        $release = '';
                        if ($movie_id) {
                            $movie_data = $ms->search_movies_by_id($movie_id);

                            if ($movie_data && $movie_data[$movie_id]) {
                                $movie = $movie_data[$url->pid];
                            }
                        }

                        if ($movie) {
                            if ($debug) {
                                print_r($movie);
                            }
                            $title = $movie->title;
                            $year = $movie->year;
                            $release = $movie->release;
                        }

                        // Add post
                        if (!$post_exist) {
                            $top_movie = $movie_id;
                            $rating = 0;
                            $this->mp->add_post($uid, $status, $title, $release, $year, $post_options, $top_movie, $rating);

                            if ($status == 1) {
                                $message = 'Add post and URLs: ' . $title;
                                $this->mp->log_info($message, $cid, $uid, 3);
                            } else {
                                $message = 'Can not find URLs';
                                $this->mp->log_error($message, $cid, $uid, 3);
                            }
                        } else {
                            //Force update post
                            $top_movie = $post_exist->top_movie;
                            $rating = $post_exist->rating;
                            $this->mp->update_post($uid, $status, $title, $release, $year, $post_options, $top_movie, $rating);
                        }
                    } else {
                        $message = 'Post already exist';
                        $this->mp->log_warn($message, $cid, $uid, 3);
                    }
                }
            } else {
                if ($debug) {
                    print "Can not find urls: $cid\n";
                }
            }
        } else {
            // Campaign done
            // Status auto-stop
            $options_upd = array();
            $options_upd[$type_name]['status'] = 3;
            $this->mp->update_campaign_options($campaign->id, $options_upd);
            $message = 'All arhives parsed to posts';
            $this->mp->log_info($message, $campaign->id, 0, 3);
        }
        return $count;
    }

    private function proccess_links($campaign, $options, $force = false) {
        $type_name = 'links';
        $cid = $campaign->id;
        $type_opt = $options[$type_name];

        //Movies custom hook
        $mch = $this->ml->get_mch();

        // Get posts (last is first)        
        $urls_count = $type_opt['num'];
        $count = 0;

        // Get last posts
        $last_posts = $this->mp->get_last_posts($urls_count, $cid, 0, 1);

        if ($last_posts) {

            $o = $options['links'];
            $items = $this->mp->find_posts_links($last_posts, $o, $campaign->type);

            foreach ($items as $pid => $item) {

                $post = $item['post'];
                $fields = $item['fields'];
                $results = $item['results'];

                if ($results) {
                    if ($campaign->type == 1) {
                        // Actors
                        /*
                         *  [13336028] => Array
                          (
                          [lastname] => Array
                          (
                          [data] => Caskey
                          [match] => 1
                          [rating] => 10
                          )

                          [total] => Array
                          (
                          [match] => 1
                          [rating] => 10
                          [valid] => 1
                          [top] => 1
                          )

                          )
                         */
                        $find_last = 0;
                        $valid_actors = array();
                        foreach ($results as $aid => $data) {
                            if ($data['total']['valid'] == 1) {
                                // Add meta
                                // $this->mp->add_post_actor_meta($aid, $pid, $cid);
                                $find_last = $aid;
                                $valid_actors[] = $aid;
                            }
                        }

                        if ($find_last) {
                            // Add link
                            $status = 1;
                            $rating = $results[$find_last]['total']['rating'];
                            $this->mp->update_post_top_movie($post->uid, $status, $find_last, $rating);

                            $message = "Found author link: name: " . $post->title . "; aid: $find_last; rating: $rating";
                            $this->mp->log_info($message, $cid, $post->uid, 4);

                            $mch->add_actors($campaign, $post);
                        } else {
                            $this->mp->update_post_status($post->uid, 2);
                            $message = 'Found posts is not valid';
                            $this->mp->log_warn($message, $cid, $post->uid, 4);
                        }
                    } else {
                        // Movies

                        $find_movie = 0;
                        foreach ($results as $mid => $data) {
                            if ($data['total']['top'] == 1) {
                                $find_movie = $mid;
                                break;
                            }
                        }
                        if ($find_movie) {
                            // Add link
                            $status = 1;
                            $rating = $results[$find_movie]['total']['rating'];
                            $this->mp->update_post_top_movie($post->uid, $status, $find_movie, $rating);

                            $message = "Found post link: title: " . $post->title . "; mid: $find_movie; rating: $rating";
                            $this->mp->log_info($message, $cid, $post->uid, 4);

                            $post->top_movie = $find_movie;
                            $mch->add_post($campaign, $post);
                        } else {
                            $this->mp->update_post_status($post->uid, 2);
                            $message = 'Found posts is not valid';
                            $this->mp->log_warn($message, $cid, $post->uid, 4);
                        }
                    }
                } else {
                    // Link post not found
                    $this->mp->update_post_status($post->uid, 2);
                    $message = 'Link post not found';
                    $this->mp->log_error($message, $cid, $post->uid, 4);
                }
                $count += 1;
            }
        } else {
            // Campaign done
            // Status auto-stop
            $options_upd = array();
            $options_upd[$type_name]['status'] = 3;
            $this->mp->update_campaign_options($campaign->id, $options_upd);
            $message = 'All posts linked to movies';
            $this->mp->log_info($message, $campaign->id, 0, 4);
        }
        return $count;
    }

    private function start_paused_module($campaign, $module, $options) {
        $options_upd = array();
        if (isset($options[$module])) {
            $status = $options[$module]['status'];
            // Update status
            if ($status == 3) {
                $options_upd[$module]['status'] = 1;
            }
        }

        if ($options_upd) {
            $this->mp->update_campaign_options($campaign->id, $options_upd);
            $message = 'Module unpaused: ' . $module;
            $mtype = $this->mp->log_modules[$module] ? $this->mp->log_modules[$module] : 0;
            $this->mp->log_info($message, $campaign->id, 0, $mtype);
        }
    }

    private function arhive_url($item, $campaign, $type_opt, $force = false) {

        /*
          [id] => 21
          [cid] => 2
          [pid] => 0
          [status] => 0
          [link_hash] => 3b70b8c52eb19970befb224f69fda669e02c430e
          [link] => https://www.the-numbers.com/movie/Saphead-The#tab=summary
         */

        //1. Url item exist?
        $arhive_exist = $this->mp->get_arhive_by_url_id($item->id);

        if ($arhive_exist && !$force) {
            return;
        }

        //2. Parse Url
        // Status - Parsing
        $status = 5;
        $this->mp->change_url_state($item->id, $status, true);

        $url = $item->link;
        $link_hash = $item->link_hash;
        $first_letter = substr($link_hash, 0, 1);
        $settings = $this->ml->get_settings();


        // Get posts (last is first)       
        $code = $this->mp->get_code_by_current_driver($url, $headers, $settings, $type_opt);

        // Validate headers
        $header_status = $this->mp->get_header_status($headers);

        if ($header_status == 403) {
            // Status - 403 error
            $status = 4;
            $this->mp->change_url_state($item->id, $status, true);
            $message = 'Error 403 Forbidden';
            $this->mp->log_error($message, $item->cid, $item->id, 2);
            return;
        } else if ($header_status == 500) {
            // Status - 500 error
            $status = 4;
            $this->mp->change_url_state($item->id, $status, true);
            $message = 'Error 500 Internal Server Error';
            $this->mp->log_error($message, $item->cid, $item->id, 2);
            return;
        } else if ($header_status == 404) {
            // Status - 404
            $status = 4;
            $this->mp->change_url_state($item->id, $status, true);
            $message = 'Error 404 Not found';
            $this->mp->log_error($message, $item->cid, $item->id, 2);
            return;
        }
        // Other statuses
        $error_statuses = array(401, 402, 429);
        if (in_array($header_status, $error_statuses)) {
            // Status - 404
            $status = 4;
            $this->mp->change_url_state($item->id, $status, true);
            $message = 'Error ' . $header_status;
            $this->mp->log_error($message, $item->cid, $item->id, 2);
            return;
        }

        if ($code) {
            // Validate body
            $valid_body_len = $this->mp->validate_body_len($code, $type_opt['body_len']);
            if (!$valid_body_len) {
                $status = 4;
                $this->mp->change_url_state($item->id, $status, true);
                $message = 'Error validate body length: ' . strlen($code);
                $this->mp->log_error($message, $item->cid, $item->id, 2);
                return;
            }
        } else {
            // Status - error
            $status = 4;
            $this->mp->change_url_state($item->id, $status, true);
            $message = 'Can not get code from URL';
            $this->mp->log_error($message, $item->cid, $item->id, 2);
            return;
        }

        $arhive_path = $this->ml->arhive_path;
        $cid_path = $arhive_path . $item->cid . '/';
        $first_letter_path = $cid_path . $first_letter . '/';

        $full_path = $first_letter_path . $link_hash;


        $this->mp->check_and_create_dir($first_letter_path);

        if (file_exists($full_path)) {
            unlink($full_path);
        }

        // Save code to arhive folder
        $gzdata = gzencode($code, 9);

        file_put_contents($full_path, $gzdata);

        // Add arhive db object
        if ($arhive_exist) {
            $this->mp->update_arhive($item);
            $message = 'Update arhive';
            $this->mp->log_info($message, $item->cid, $item->id, 2);
        } else {
            $message = 'Add arhive';
            $this->mp->add_arhive($item);
            $this->mp->log_info($message, $item->cid, $item->id, 2);
        }
        // Status - exist
        $status = 1;
        $this->mp->change_url_state($item->id, $status, true);
    }

    /*
     * Cron async
     */

    public function run_cron_async($cid = 0, $type_name = '', $debug = false) {

        if (!$cid) {
            return;
        }

        if ($type_name == 'arhive') {
            $campaign = $this->mp->get_campaign($cid);
            $options = $this->mp->get_options($campaign);
            $type_opt = $options[$type_name];
            $urls_count = $type_opt['num'];

            // Get last urls
            $status = 0;

            // Random urls
            $random_urls = $type_opt['random'];
            $urls = $this->mp->get_last_urls($urls_count, $status, $campaign->id, $random_urls, $debug);

            $count = count((array) $urls);
            if ($debug) {
                print_r(array('Arhive count', $count));
            }
            if ($count) {
                $this->arhive_urls($campaign, $options, $urls);
            }

            // Delete garbage
            // Delete error arhives
            $del_pea = $type_opt['del_pea'];
            if ($del_pea == 1) {
                // Delete arhives witch error posts
                $del_pea_int = $type_opt['del_pea_int'];
                $parser_type = 2;
                $count = 10;
                $curr_time = $this->curr_time();
                $expire = $curr_time - $del_pea_int * 60;
                $urls = $this->mp->get_urls(-1, 1, $cid, -1, $parser_type, -1, '', 'ASC', $count, $expire);
                if ($debug) {
                    print_r(array('Delete arhives witch post error', $urls));
                }
                if ($urls) {
                    foreach ($urls as $url) {
                        $this->mp->delete_arhive_by_url_id($url->id);
                    }
                }
            }

            // Delete error urls
            $service_opt = $options['service_urls'];
            $del_pea = $service_opt['del_pea'];
            if ($del_pea == 1) {
                // Delete arhives witch error url                
                $status = 4;
                $count = $service_opt['del_pea_cnt'];
                $curr_time = $this->curr_time();

                $urls = $this->mp->get_urls($status, 1, $cid, -1, -1, -1, '', 'ASC', $count);
                if ($debug) {
                    print_r(array('Delete arhives witch url error', $urls));
                }
                if ($urls) {
                    foreach ($urls as $url) {
                        $this->mp->delete_arhive_by_url_id($url->id);
                    }
                }
            }
        }
    }

}
