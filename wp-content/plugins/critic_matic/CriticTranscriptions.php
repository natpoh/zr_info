<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
if (!class_exists('AbstractDBTC')) {
    require_once( CRITIC_MATIC_PLUGIN_DIR . '/db/AbstractDBTC.php' );
}

class CriticTranscriptions extends AbstractDBTC {

    private $db;
    private $cm;
    private $cp;
    private $gs;
    private $client;
    private $dev_key = 'AIzaSyAd1i0gRHQHDU3sahODkYiZtf5b7CluuF8';
    private $dev_project = 'rwt yotube';

    public function __construct($cm) {
        $table_prefix = DB_PREFIX_WP_AN;
        $this->cm = $cm;
        $this->db = array(
            'youtube' => 'youtube',
            'therightstuff' => 'therightstuff',
            'bitchute' => 'bitchute',
            // TS
            'transcriptions' => $table_prefix . 'critic_transcritpions',
        );
    }

    public function get_cp() {
        // Get criti
        if (!$this->cp) {
            //init cp
            if (!class_exists('CriticParser')) {
                require_once( CRITIC_MATIC_PLUGIN_DIR . 'CriticParser.php' );
            }
            $this->cp = new CriticParser($this->cm);
        }
        return $this->cp;
    }

    private function init_client() {
        if (!$this->client) {
            /**
             * https://developers.google.com/youtube/v3/docs/videos/list?apix=true&apix_params=%7B%22part%22%3A%5B%22snippet%22%5D%2C%22id%22%3A%5B%22TOAqMhKcP_Q%2CcBEmK39XFYQ%22%5D%7D
             * https://github.com/googleapis/google-api-php-client/releases
             * 
             * Sample PHP code for youtube.videos.list
             * See instructions for running these code samples locally:
             * https://developers.google.com/explorer-help/guides/code_samples#php
             */
            if (!class_exists('Google_Client')) {
                $gs_name = CRITIC_MATIC_PLUGIN_DIR . 'lib/google-api-php-client--PHP7.4/vendor/autoload.php';
                if (file_exists($gs_name)) {
                    require_once CRITIC_MATIC_PLUGIN_DIR . 'lib/google-api-php-client--PHP7.4/vendor/autoload.php';
                } else {
                    print 'Goolge client lib not exist';
                    exit;
                }
            }

            try {
                $client = new Google_Client();
                $client->setApplicationName($this->dev_project);
                $client->setDeveloperKey($this->dev_key);
                $this->client = $client;
            } catch (Exception $e) {
                print_r($e);
                exit;
            }
        }

        return $this->client;
    }

    private function init_gs() {
        if (!$this->gs) {
            $client = $this->init_client();
            try {
                // Define service object for making API requests.
                $this->gs = new Google_Service_YouTube($client);
            } catch (Exception $e) {
                print_r($e);
                exit;
            }
        }
        return $this->gs;
    }

    public function transit_therightstuff($count = 10, $debug = false, $force = false) {
        $videos = $this->get_therightstuff_videos($count);
        /*
         *  [id] => 1
          [title] => The Daily Shoah! Episode 14.88 pt VIII: The 2014.88 Year In Review
          [post_url] => https://therightstuff.biz/2014/12/29/the-daily-shoah-episode-14-88-pt-viii-the-2014-88-year-in-review/
          [author] => Seventh Son
          [publish_date] => 2014-12-29 20:44:58
          [content] => <p>The Death Panel gather to reflect on the best year for making 14/88 jokes.<span id="more-234"></span> Also, don’t miss Mike’s appearance on the AltRight Podcast today!</p>
          <p>And have a Happy Jew Year!</p>
          <p>0:00 INTRO<br/>
          2:45 TRS Amber Alert: Toilet Law?<br/>
          3:34 TRS Mailbag<br/>
          16:00 The Year Of The Dindu<br/>
          42:50 The Year of Feminism; Frozen<br/>
          50:45 Bulby’s Bookshelf: Judaism’s Strange Gods<br/>
          1:03:58 The Merchant Minute with Morrakiu<br/>
          1:08:00 2015 Teasers<br/>
          1:17:50 Xmas with Enoch</p>

          [file_url] => https://archive.org/download/TRS1488IX/TRS1488VIII.mp3
          [is_paywall] => 0
          [transcriptions] => [0:00:00 - 0:00:07]
          if you don't like what we tell you to believe in we'll kill you
          [mega_url] => https://mega.co.nz/#!79dgARTQ!Var-n7BiuEemOHNLzcG5n_u6WTn-D2CcWcLkNfS4KlA
          [pid] => 0
         */
        if ($videos && sizeof($videos)) {

            foreach ($videos as $video) {

                $link = $video->post_url;

                // Get this post
                $link_hash = $this->cm->link_hash($link);

                //Check the post already in db
                $old_post = $this->cm->get_post_by_link_hash($link_hash);

                if ($old_post && $force == false) {
                    // Post exist, continue
                    $pid = -1;
                    $this->update_youtube_pid($video->id, $pid);
                    if ($debug) {
                        print "The post already exist: $link\n";
                    }
                    continue;
                }

                //Date 
                $date = strtotime($video->publish_date);

                //Add a new post
                // print date('d.m.Y', $date) . '<br />';
                //Get the content
                $content = $this->bitchute_content_filter($video->transcriptions);
                $desc = '<div class="desc">' . $video->content . '</div>';
                $content = $desc . $content;

                $title = $video->title;
                // Type - Transcript
                $type = 4;
                //Author
                //$author_name = trim($video->author);
                $author_name = 'The Right Stuff';

                if ($old_post) {

                    $this->cm->update_post($old_post->id, $date, $old_post->status, $link, $title, $content, $type);
                    $cm_id = $old_post->id;

                    if ($debug) {
                        print "Update post: $cm_id, $date, $type, $link, $title\n";
                    }
                } else {

                    $cm_id = $this->cm->add_post($date, $type, $link, $title, $content);

                    if ($debug) {
                        print "Add post:$cm_id, $date, $type, $link, $title\n";
                    }
                }


                if ($cm_id > 0) {

                    $this->update_therightstuff_pid($video->id, $cm_id);

                    //Add post author to new post (source)
                    if (!$old_post) {
                        // 1 - pro
                        $author_type = 1;
                        $author_id = $this->cm->get_or_create_author_by_name($author_name, $author_type);
                        if ($author_id) {
                            //Add post author
                            $this->cm->add_post_author($cm_id, $author_id);
                        }
                    }


                    $this->append_id($cm_id);
                }


                //break;
            }
        }
    }

    public function transit_bitchute($count = 10, $debug = false, $force = false) {
        $videos = $this->get_bitchute_videos($count);
        /*
         * 0] => stdClass Object
          (
          [id] => 1
          [critic_name] => Millennial Woes
          [channel_url] => https://www.bitchute.com/channel/OUbfw7ulVP2n/
          [title] => The Ballad of Kraut and the Skeptics (Millenniyule 2017 #36: Jean-Francois Gariepy)
          [page_url] => https://bitchute.com/video/NMX1hYFvWAaj/
          [file_url] => https://seed191.bitchute.com/OUbfw7ulVP2n/NMX1hYFvWAaj.mp4
          [transcriptions] => [0:00:00 - 0:00:07]
          hello and welcome to Millennial 2017 number 36
          [pid] => 0
          )
          )
         */
        if ($videos && sizeof($videos)) {

            foreach ($videos as $video) {

                $link = $video->page_url;

                // Get this post
                $link_hash = $this->cm->link_hash($link);

                //Check the post already in db
                $old_post = $this->cm->get_post_by_link_hash($link_hash);

                if ($old_post && $force == false) {
                    // Post exist, continue
                    $pid = -1;
                    $this->update_youtube_pid($video->id, $pid);
                    if ($debug) {
                        print "The post already exist: $link\n";
                    }
                    continue;
                }

                //Date 
                $date = $this->find_bitchute_date($link, $debug);
                if (!$date) {
                    // Can not get the date
                    $pid = -2;
                    $this->update_youtube_pid($video->id, $pid);
                    if ($debug) {
                        print "Can not get the date: $link\n";
                    }
                    continue;
                }

                //Add a new post
                // print date('d.m.Y', $date) . '<br />';
                //Get the content
                $content = $this->bitchute_content_filter($video->transcriptions);
                $channel_id = '<div data-channel="' . $video->channel_url . '"></div>';
                $content = $channel_id . $content;

                $title = $video->title;
                // Type - Transcript
                $type = 4;
                //Author
                $author_name = trim($video->critic_name);

                if ($old_post) {

                    $this->cm->update_post($old_post->id, $date, $old_post->status, $link, $title, $content, $type);
                    $cm_id = $old_post->id;

                    if ($debug) {
                        print "Update post: $cm_id, $date, $type, $link, $title\n";
                    }
                } else {

                    $cm_id = $this->cm->add_post($date, $type, $link, $title, $content);

                    if ($debug) {
                        print "Add post:$cm_id, $date, $type, $link, $title\n";
                    }
                }


                if ($cm_id > 0) {

                    $this->update_bitchute_pid($video->id, $cm_id);

                    //Add post author to new post (source)
                    if (!$old_post) {
                        // 1 - pro
                        $author_type = 1;
                        $author_id = $this->cm->get_or_create_author_by_name($author_name, $author_type);
                        if ($author_id) {
                            //Add post author
                            $this->cm->add_post_author($cm_id, $author_id);
                        }
                    }


                    $this->append_id($cm_id);
                }


                //break;
            }
        }
    }

    public function transit_youtube($count = 10, $debug = false, $force = false) {
        $videos = $this->get_youtube_videos($count);
        /*
         * 
         * UPDATE youtube SET pid = 0
         * 
          [id] => 1
          [critic_name] => Daily Wire
          [channel_url] => https://www.youtube.com/channel/UCaeO5vkdj5xOQHp4UmIN6dw
          [title] => PragerU's Will Witt in the Hot Seat
          [video_url] => https://youtube.com/watch?v=5OK2IuPLpCY
          [transcriptions] => 1
          00:00:00,000 --> 00:00:03,280
          what's up guys what's up guys what's up
          [pid] => 0
         */
        if ($videos && sizeof($videos)) {

            //find data
            $data = array();
            $codes = array();
            foreach ($videos as $video) {
                $link = $video->video_url;
                $code = $this->find_youtube_code($link);
                if ($code) {
                    $data[$code] = $video;
                    $codes[] = $code;
                }
            }
            $codes_data = $this->find_youtube_data_api($codes, $debug);

            if ($debug) {
                print_r($codes_data);
            }

            foreach ($data as $code => $video) {

                $link = $video->video_url;

                // Get this post
                $link_hash = $this->cm->link_hash($link);

                //Check the post already in db
                $old_post = $this->cm->get_post_by_link_hash($link_hash);

                if ($old_post && $force == false) {
                    // Post exist, continue
                    $pid = -1;
                    $this->update_youtube_pid($video->id, $pid);
                    if ($debug) {
                        print "The post already exist: $link\n";
                    }
                } else {
                    //Add a new post
                    //Date 
                    // $date = $this->find_youtube_date($link, $debug);

                    $date = time();
                    $description = '';
                    if ($codes_data[$code]) {
                        $date = strtotime($codes_data[$code]->publishedAt);
                        $description = isset($codes_data[$code]->description) ? '<div class="description">' . str_replace("\n", '<br />', $codes_data[$code]->description) . '</div>' : '';
                    }

                    // print date('d.m.Y', $date) . '<br />';
                    //Get the content
                    $content = $this->youtube_content_filter($video->transcriptions);
                    // print $content . '<br /><br />';

                    $content = $description . $content;

                    $title = $video->title;
                    // Type - Transcript
                    $type = 4;
                    //Author
                    $author_name = trim($video->critic_name);

                    if ($old_post) {

                        $this->cm->update_post($old_post->id, $date, $old_post->status, $link, $title, $content, $type);
                        $cm_id = $old_post->id;

                        if ($debug) {
                            print "Update post: $cm_id, $date, $type, $link, $title\n";
                        }
                    } else {

                        $cm_id = $this->cm->add_post($date, $type, $link, $title, $content);

                        if ($debug) {
                            print "Add post:$cm_id, $date, $type, $link, $title\n";
                        }
                    }


                    if ($cm_id > 0) {

                        $this->update_youtube_pid($video->id, $cm_id);

                        //Add post author to new post (source)
                        if (!$old_post) {
                            // 1 - pro
                            $author_type = 1;
                            $author_id = $this->cm->get_or_create_author_by_name($author_name, $author_type);
                            if ($author_id) {
                                //Add post author
                                $this->cm->add_post_author($cm_id, $author_id);
                            }
                        }


                        $this->append_id($cm_id);
                    }
                }

                //break;
            }
        }
        //Get URL
        //Date
    }

    public function transit_youtube_ts($count = 10, $debug = false, $force = false) {
        $last_id = $this->get_option('last_transit_youtube_id', 0);
        if ($force) {
            $last_id = 0;
        }

        $sql = sprintf("SELECT * FROM {$this->db['youtube']} WHERE id>%d ORDER BY id ASC limit %d", (int) $last_id, (int) $count);
        $results = $this->db_results($sql);


        /*
         * 
         * UPDATE youtube SET pid = 0
         * 
          [id] => 1
          [critic_name] => Daily Wire
          [channel_url] => https://www.youtube.com/channel/UCaeO5vkdj5xOQHp4UmIN6dw
          [title] => PragerU's Will Witt in the Hot Seat
          [video_url] => https://youtube.com/watch?v=5OK2IuPLpCY
          [transcriptions] => 1
          00:00:00,000 --> 00:00:03,280
          what's up guys what's up guys what's up
          [pid] => 0
         */
        if ($results) {
            $last = end($results);
            $last_id = $last->id;
            foreach ($results as $video) {
                $pid = $video->pid;
                $transcriptions = $video->transcriptions;
                if ($debug) {
                    print_r(array($pid, $transcriptions));
                }

                if ($pid && $transcriptions) {

                    $ts_exist = $this->get_ts_by_pid($pid);
                    if (!$ts_exist) {
                        // Insert transcription
                        $date_add = $this->curr_time();
                        // Ts in post
                        $status = 2;
                        $data = array(
                            'pid' => $pid,
                            'date_add' => $date_add,
                            'status' => $status,
                            'content' => $transcriptions,
                        );

                        if ($debug) {
                            print_r($data);
                        }
                        
                        $this->cm->db_insert($data, $this->db['transcriptions']);                        
                    } else {
                        if ($debug){
                            print "ts for post $pid exists\n";
                        }
                    }
                }
            }

            $this->update_option('last_transit_youtube_id', $last_id);
        }
    }

    private function get_ts_by_pid($pid) {
        $sql = sprintf("SELECT * FROM {$this->db['transcriptions']} WHERE pid=%d", (int) $pid);
        $results = $this->cm->db_fetch_row($sql);
        return $results;
    }

    private function append_id($id) {
        // Append a new id to search queue
        $opt_key = 'feed_matic_search_ids';
        $ids_str = $this->get_option($opt_key, '');
        $ids = array();
        if ($ids_str) {
            $ids = unserialize($ids_str);
        }
        if (!in_array($id, $ids)) {
            $ids[] = $id;
            $ids_str = serialize($ids);
            $this->update_option($opt_key, $ids_str);
        }
    }

    public function find_youtube_date($url, $debug = false) {
        $cp = $this->get_cp();
        $code = $cp->get_proxy($url, '', $headers);
        if ($debug) {
            print_r($headers);
        }
        //<meta itemprop="datePublished" content="2021-07-28">
        $date = time();
        if (preg_match('/<meta itemprop="datePublished" content="([^"]+)">/', $code, $match)) {
            $date = strtotime($match[1]);
        }
        return $date;
    }

    public function find_bitchute_date($url, $debug = false) {
        $test_date = '<div class="video-publish-date">
First published at 09:37 UTC on September 6th, 2021.
</div>';
        $cp = $this->get_cp();
        $code = $cp->get_proxy($url, '', $headers);
        if ($debug) {
            print_r($headers);
        }
        //<meta itemprop="datePublished" content="2021-07-28">
        $date = 0;
        if (preg_match('/<div class="video-publish-date">[^<]*([0-9]{2}\:[0-9]{2}[^<]+[0-9]{4})\.[^<]*<\/div>/s', $code, $match)) {
            $date = strtotime($match[1]);
        }
        return $date;
    }

    private function find_youtube_code($link) {
        $code = '';
        if (preg_match('#//www\.youtube\.com/embed/([a-zA-Z0-9\-_]+)#', $link, $match) ||
                preg_match('#//(?:www\.|)youtube\.com/(?:v/|watch\?v=|watch\?.*v=|embed/)([a-zA-Z0-9\-_]+)#', $link, $match) ||
                preg_match('#//youtu\.be/([a-zA-Z0-9\-_]+)#', $link, $match)) {
            if (count($match) > 1) {
                $code = $match[1];
            }
        }
        return $code;
    }

    public function test_google() {
        $ids = array('TOAqMhKcP_Q');
        $this->find_youtube_data_api($ids, true);
        // $this->get_youtube_caption($ids);
    }

    public function find_youtube_data_api($ids, $debug = false) {
        if (!$ids) {
            return;
        }

        $service = $this->init_gs();

        $queryParams = [
            'id' => implode(',', $ids)
        ];

        $response = $service->videos->listVideos('snippet', $queryParams);
        if ($debug) {
            print_r($response);
        }

        /*
         * {
          "kind": "youtube#videoListResponse",
          "etag": "iXxxNChBvxHL3v7dFoPKtmOxvVg",
          "items": [
          {
          "kind": "youtube#video",
          "etag": "f926elR4ex_OgGjqHdTjk6hS0Zc",
          "id": "TOAqMhKcP_Q",
          "snippet": {
          "publishedAt": "2021-01-30T20:16:14Z",
          "channelId": "UCaeO5vkdj5xOQHp4UmIN6dw",
          "title": "RUN HIDE FIGHT Cast Interviews | Isabel May, Eli Brown, Thomas Jane",
          "description": "Isabel May reveals why she was hesitant to star in RUN HIDE FIGHT, Eli Brown details his experience working with director Kyle Rankin, and Thomas Jane weighs in on the gun safety debate.\n\nRUN HIDE FIGHT is available for streaming now at https://www.dailywire.com — exclusively for Daily Wire members. Become a member today and get 25% off your membership by using the code RHF. JOIN: https://utm.io/uc2Ui\n\nWatch the full trailer here — https://youtu.be/2Kh3jccZocc\n\nMass Shooting Safety Expert Breaks Down Scenes From ‘Run Hide Fight’ - https://youtu.be/dLLJlk6b9RA\n\nThe Making Of 'RUN HIDE FIGHT' | Cast Interviews, Auditions, Behind-The-Scenes Footage - https://youtu.be/Rr1bg2UUyW4",
          "thumbnails": {
          "default": {
          "url": "https://i.ytimg.com/vi/TOAqMhKcP_Q/default.jpg",
          "width": 120,
          "height": 90
          },
          "medium": {
          "url": "https://i.ytimg.com/vi/TOAqMhKcP_Q/mqdefault.jpg",
          "width": 320,
          "height": 180
          },
          "high": {
          "url": "https://i.ytimg.com/vi/TOAqMhKcP_Q/hqdefault.jpg",
          "width": 480,
          "height": 360
          },
          "standard": {
          "url": "https://i.ytimg.com/vi/TOAqMhKcP_Q/sddefault.jpg",
          "width": 640,
          "height": 480
          },
          "maxres": {
          "url": "https://i.ytimg.com/vi/TOAqMhKcP_Q/maxresdefault.jpg",
          "width": 1280,
          "height": 720
          }
          },
          "channelTitle": "The Daily Wire",
          "tags": [
          "Backstage",
          "Backstage live",
          "Ben Shapiro",
          "Jeremy Boreing",
          "Daily Wire",
          "Daily Wire movies",
          "Daily Wire entertainment",
          "Daily Wire Backstage",
          "Backstage Live",
          "Andrew Klavan",
          "Matt Walsh",
          "Michael Knowles",
          "Daily Wire film",
          "Daily Wire Run Hide Fight",
          "Run Hide Fight Daily Wire",
          "movie daily wire",
          "Movie",
          "movies",
          "new movies 2020",
          "full movie",
          "new movie",
          "new action movie",
          "Run Hide Fight",
          "Run Hide Fight movie",
          "Daily Wire movie",
          "Run Hide Fight official",
          "Run Hide Fight movie trailer",
          "Run",
          "Hide"
          ],
          "categoryId": "25",
          "liveBroadcastContent": "none",
          "localized": {
          "title": "RUN HIDE FIGHT Cast Interviews | Isabel May, Eli Brown, Thomas Jane",
          "description": "Isabel May reveals why she was hesitant to star in RUN HIDE FIGHT, Eli Brown details his experience working with director Kyle Rankin, and Thomas Jane weighs in on the gun safety debate.\n\nRUN HIDE FIGHT is available for streaming now at https://www.dailywire.com — exclusively for Daily Wire members. Become a member today and get 25% off your membership by using the code RHF. JOIN: https://utm.io/uc2Ui\n\nWatch the full trailer here — https://youtu.be/2Kh3jccZocc\n\nMass Shooting Safety Expert Breaks Down Scenes From ‘Run Hide Fight’ - https://youtu.be/dLLJlk6b9RA\n\nThe Making Of 'RUN HIDE FIGHT' | Cast Interviews, Auditions, Behind-The-Scenes Footage - https://youtu.be/Rr1bg2UUyW4"
          },
          "defaultAudioLanguage": "en"
          }
          },
          {
          "kind": "youtube#video",
          "etag": "q2Y9AnYwwAi5mM5A5b4T28FnYOI",
          "id": "cBEmK39XFYQ",
          "snippet": {
          "publishedAt": "2021-02-06T18:24:56Z",
          "channelId": "UCaeO5vkdj5xOQHp4UmIN6dw",
          "title": "Law Enforcement Expert Details the Six Steps to Survive a Mass Shooting",
          "description": "School safety and mass shooting survival expert John Matthews lays out the six steps you can take to increase your chances of surviving a mass shooting. \n\nRUN HIDE FIGHT is available for streaming now at https://www.dailywire.com\u200b\u200b — exclusively for Daily Wire members. This is the last weekend that you can get 25% off your membership by using the code RHF, so join today! CLICK HERE: https://utm.io/uc2Ui\u200b\u200b\n\nWatch the full trailer here — https://youtu.be/2Kh3jccZocc\u200b\n\nMass Shooting Safety Expert Breaks Down Scenes From ‘Run Hide Fight’ - https://youtu.be/dLLJlk6b9RA\u200b\n\nThe Making Of 'RUN HIDE FIGHT' | Cast Interviews, Auditions, Behind-The-Scenes Footage - https://youtu.be/Rr1bg2UUyW4",
          "thumbnails": {
          "default": {
          "url": "https://i.ytimg.com/vi/cBEmK39XFYQ/default.jpg",
          "width": 120,
          "height": 90
          },
          "medium": {
          "url": "https://i.ytimg.com/vi/cBEmK39XFYQ/mqdefault.jpg",
          "width": 320,
          "height": 180
          },
          "high": {
          "url": "https://i.ytimg.com/vi/cBEmK39XFYQ/hqdefault.jpg",
          "width": 480,
          "height": 360
          },
          "standard": {
          "url": "https://i.ytimg.com/vi/cBEmK39XFYQ/sddefault.jpg",
          "width": 640,
          "height": 480
          },
          "maxres": {
          "url": "https://i.ytimg.com/vi/cBEmK39XFYQ/maxresdefault.jpg",
          "width": 1280,
          "height": 720
          }
          },
          "channelTitle": "The Daily Wire",
          "tags": [
          "mass shooting",
          "shooting",
          "mass shootings",
          "new zealand mass shooting",
          "mass",
          "us mass shooting",
          "ut mass shooting",
          "school shooting",
          "gay mass shooting",
          "worst mass shooting",
          "texas mass shooting",
          "wwltv mass shooting",
          "gilroy mass shooting",
          "mass shooting canada",
          "mass shooting mosque",
          "canada mass shooting",
          "orlando mass shooting",
          "toronto mass shooting",
          "mass shootings 2016",
          "mass shooting in canada",
          "las vegas mass shooting",
          "mass shootings canada",
          "canada mass shootings",
          "las vegas shooting"
          ],
          "categoryId": "25",
          "liveBroadcastContent": "none",
          "localized": {
          "title": "Law Enforcement Expert Details the Six Steps to Survive a Mass Shooting",
          "description": "School safety and mass shooting survival expert John Matthews lays out the six steps you can take to increase your chances of surviving a mass shooting. \n\nRUN HIDE FIGHT is available for streaming now at https://www.dailywire.com\u200b\u200b — exclusively for Daily Wire members. This is the last weekend that you can get 25% off your membership by using the code RHF, so join today! CLICK HERE: https://utm.io/uc2Ui\u200b\u200b\n\nWatch the full trailer here — https://youtu.be/2Kh3jccZocc\u200b\n\nMass Shooting Safety Expert Breaks Down Scenes From ‘Run Hide Fight’ - https://youtu.be/dLLJlk6b9RA\u200b\n\nThe Making Of 'RUN HIDE FIGHT' | Cast Interviews, Auditions, Behind-The-Scenes Footage - https://youtu.be/Rr1bg2UUyW4"
          },
          "defaultAudioLanguage": "en"
          }
          }
          ],
          "pageInfo": {
          "totalResults": 2,
          "resultsPerPage": 2
          }
          }
         */

        $ret = array();
        if ($response && isset($response['items'])) {
            foreach ($response['items'] as $item) {
                $ret[$item->id] = $item['snippet'];
            }
        }

        return $ret;
    }

    private function youtube_content_filter($content) {
        $ret = '';
        if (preg_match_all('/([0-9]+\:[0-9]+\:[0-9\, ]+)-->[^\n]+\n([^\n]+)/s', $content, $match)) {
            for ($i = 0; $i < sizeof($match[1]); $i++) {
                $ret .= '<span data-time="' . $match[1][$i] . '">' . $match[2][$i] . '</span> ';
            }
            $ret = '<div class="transcriptions">' . $ret . '</div>';
        }
        return $ret;
    }

    private function bitchute_content_filter($content) {
        /*
         * [0:00:00 - 0:00:07]
          hello and welcome to Millennial 2017 number 36
         * 
         */
        $ret = '';
        if (preg_match_all('/([0-9]+\:[0-9]+\:[0-9]+)[^\n]+\n([^\n]+)/s', $content, $match)) {
            for ($i = 0; $i < sizeof($match[1]); $i++) {
                $ret .= '<span data-time="' . $match[1][$i] . '">' . $match[2][$i] . '</span> ';
            }
            $ret = '<div class="transcriptions">' . $ret . '</div>';
        }
        return $ret;
    }

    public function get_youtube_videos($count) {
        $sql = sprintf("SELECT * FROM {$this->db['youtube']} WHERE pid=0 limit %d", (int) $count);
        $results = $this->db_results($sql);
        return $results;
    }

    public function update_youtube_pid($id, $pid) {
        $sql = sprintf("UPDATE {$this->db['youtube']} SET pid='%d' WHERE id=%d", $pid, $id);
        $this->db_query($sql);
    }

    public function get_bitchute_videos($count) {
        $sql = sprintf("SELECT * FROM {$this->db['bitchute']} WHERE pid=0 limit %d", (int) $count);
        $results = $this->db_results($sql);
        return $results;
    }

    public function get_therightstuff_videos($count) {
        $sql = sprintf("SELECT * FROM {$this->db['therightstuff']} WHERE pid=0 limit %d", (int) $count);
        $results = $this->db_results($sql);
        return $results;
    }

    public function update_bitchute_pid($id, $pid) {
        $sql = sprintf("UPDATE {$this->db['bitchute']} SET pid='%d' WHERE id=%d", $pid, $id);
        $this->db_query($sql);
    }

    public function update_therightstuff_pid($id, $pid) {
        $sql = sprintf("UPDATE {$this->db['therightstuff']} SET pid='%d' WHERE id=%d", $pid, $id);
        $this->db_query($sql);
    }

    public function install() {
        $sql = "ALTER TABLE {$this->db['youtube']} ADD `pid` int(11) NOT NULL DEFAULT '0'";
        $this->db_query($sql);

        $sql = "ALTER TABLE {$this->db['therightstuff']} ADD `pid` int(11) NOT NULL DEFAULT '0'";
        $this->db_query($sql);

        $sql = "ALTER TABLE {$this->db['bitchute']} ADD `pid` int(11) NOT NULL DEFAULT '0'";
        $this->db_query($sql);
        print 'install';
    }

    public function get_youtube_caption($ids = array(), $lang = 'en') {
        //UNUSED
        $caption_list = $this->caption_list($ids);

        $cap_ids = array();
        if ($caption_list) {
            foreach ($caption_list as $key => $item) {
                if ($item->items) {
                    foreach ($item->items as $l) {
                        if ($l->snippet->language == $lang) {
                            $cap_ids[$key] = $l->id;
                        }
                    }
                }
            }
        }
        $cap_text = array();
        if (sizeof($cap_ids)) {
            foreach ($cap_ids as $key => $id) {
                $text = $this->caption_download($id);
                $cap_text[$key] = $text;
            }
        }
        print_r($cap_text);
    }

    public function caption_list($ids) {

        if (!$ids) {
            return;
        }

        // Define service object for making API requests.
        $service = $this->init_gs();
        $ret = array();

        /*
         * {
          "kind": "youtube#captionListResponse",
          "etag": "g-XyTYZqpWxiwzu7T3Z_ADjS3Kk",
          "items": [
          {
          "kind": "youtube#caption",
          "etag": "JfiTtwMJNpGIY7Znq6wt9wT8WpM",
          "id": "I1qOJ83pIR3F6hEuO2yo_-VokiNh7rH8HYMkrW0QaQw=",
          "snippet": {
          "videoId": "TOAqMhKcP_Q",
          "lastUpdated": "2021-01-31T01:12:13.911236Z",
          "trackKind": "asr",
          "language": "en",
          "name": "",
          "audioTrackType": "unknown",
          "isCC": false,
          "isLarge": false,
          "isEasyReader": false,
          "isDraft": false,
          "isAutoSynced": false,
          "status": "serving"
          }
          }
          ]
          }
         */
        foreach ($ids as $id) {
            $response = $service->captions->listCaptions('snippet', $id);
            $ret[$id] = $response;
        }
        return $ret;
    }

    public function caption_download($id) {

        /**
         * Sample PHP code for youtube.captions.download
         * See instructions for running these code samples locally:
         * https://developers.google.com/explorer-help/guides/code_samples#php
         *
         * Also note that this sample code downloads a file and can't be executed
         * via this interface. To test this sample, you must run it locally using your
         * own API credentials.
         */
        $client = $this->init_client();

        $client->setScopes([
            'https://www.googleapis.com/auth/youtube.force-ssl',
        ]);
        //$client->setRedirectUri('http://' . $_SERVER['HTTP_HOST'] . '/oauth2callback.php');
        // TODO: For this request to work, you must replace
        //       "YOUR_CLIENT_SECRET_FILE.json" with a pointer to your
        //       client_secret.json file. For more information, see
        //       https://cloud.google.com/iam/docs/creating-managing-service-account-keys
        $client->setAuthConfig(CRITIC_MATIC_PLUGIN_DIR . 'lib/rwt-yotube-6864346930e2.json');
        $client->setAccessType('offline');

        /*
          // Request authorization from the user.
          $authUrl = $client->createAuthUrl();
          printf("Open this link in your browser:\n%s\n", $authUrl);
          print('Enter verification code: ');
          $authCode = trim(fgets(STDIN));

          // Exchange authorization code for an access token.
          $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
          $client->setAccessToken($accessToken);
         */

        // Get the authorized Guzzle HTTP client.
        $http = $client->authorize();


        /**
         * The URL path for this request is:
         *     /youtube/v3/youtube/v3/captions/{id}
         * In the path, the string "{id}" is a placeholder
         * for the value of the corresponding request parameter.
         */
        $response = $http->request(
                'GET', '/youtube/v3/captions/' . $id
        );
        print_r($response);
        return $response->getBody()->getContents();
    }

}
