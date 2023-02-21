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
                    print_r(array('title_weight', $title_weight));
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

    public function forchan_tags_cloud_cron($count = 10, $force = false, $debug = false) {
        $cron_key = 'forchan_tags_cloud';
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

            $pop_words = $this->popular_words();

            $add_cloud = false;
            $cloud = array();
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
                    print_r(array('title_weight', $title_weight));
                }


                if ($title_weight >= $min_title_weight) {
                    $add_cloud = true;
                    $last_posts = $this->mp->get_fchan_posts_content($mid);

                    foreach ($last_posts as $post) {
                        $content = $post->content;
                        if (preg_match('/data-board="([^"]+)"/', $content, $match)) {
                            $board = $match[1];
                            if (!isset($cloud[$mid][$board])) {
                                $cloud[$mid][$board] = array();
                            }
                            # 1. Find text block
                            if (preg_match('/<div class="text">(.*)<\/div>/Us', $content, $match_text)) {
                                $text = $match_text[1];
                                # 2. Remove quotes greentext
                                $text = preg_replace('/<span class="greentext">.*<\/span>/Us', '', $text);
                                # 3. Remove links
                                $text = preg_replace('/<a.*<\/a>/Us', '', $text);
                                # 4. Remove tags
                                $text = strip_tags($text);
                                $text = strtolower($text);
                                $text = html_entity_decode($text);
                                $text = preg_replace('/[^a-z\ ]+/', '', $text);
                                $text = trim(preg_replace('/  /', ' ', $text));
                                if ($debug) {
                                    print_r(array($board, $text));
                                }
                                $text_arr = explode(' ', $text);
                                if ($text_arr) {
                                    foreach ($text_arr as $word) {
                                        if ($word) {
                                            if (!in_array($word, $pop_words)) {
                                                if (!isset($cloud[$mid][$board][$word])) {
                                                    $cloud[$mid][$board][$word] = 1;
                                                } else {
                                                    $cloud[$mid][$board][$word] += 1;
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    $cloud_sort = array();
                    $max_keys = 1000;
                    foreach ($cloud as $mid => $data) {
                        foreach ($data as $type => $words) {
                            arsort($words);

                            if (sizeof($words) > $max_keys) {
                                $keys = array_keys($words);
                                $first_keys = array_splice($keys, 0, $max_keys);
                                $cut_words = array_intersect_key($words, array_flip($first_keys));
                                $words = $cut_words;
                            }

                            $cloud_sort[$mid][$type] = $words;
                        }
                    }

                    if ($debug) {
                        print_r($cloud_sort);
                    }
                    # Save cloud to file
                    if ($cloud_sort) {
                        foreach ($cloud_sort as $mid => $data) {
                            $row = json_encode($data);
                            if ($debug){
                                print_r(array($mid, $row));
                            }
                            $this->save_arhive($mid, $row);
                        }
                    }
                }
            }



            $this->update_option($cron_key, $last_id);
        }
    }

    private function save_arhive($mid, $content) {



        $arhive_path = ABSPATH . 'temp/tag_cloud';
        $full_path = $arhive_path . '/' . $mid;
        
        $this->mp->check_and_create_dir($arhive_path);
        if (file_exists($full_path)) {
            unlink($full_path);
        }

        // Save code to arhive folder
        $gzdata = gzencode($content, 9);

        file_put_contents($full_path, $gzdata);
    }

    private function popular_words() {
        $words = array(
            "a",
            "about",
            "after",
            "again",
            "against",
            "all",
            "also",
            "always",
            "am",
            "an",
            "and",
            "any",
            "are",
            "as",
            "at",
            "be",
            "because",
            "been",
            "before",
            "being",
            "between",
            "both",
            "but",
            "by",
            "can",
            "cant",
            "could",
            "did",
            "didnt",
            "do",
            "dont",
            "does",
            "doesnt",
            "doing",
            "don",
            "dont",
            "down",
            "during",
            "each",
            "even",
            "ever",
            "every",
            "few",
            "first",
            "for",
            "from",
            "further",
            "get",
            "go",
            "had",
            "has",
            "have",
            "havent",
            "he",
            "her",
            "here",
            "him",
            "himself",
            "his",
            "how",
            "however",
            "i",
            "if",
            "ill",
            "in",
            "into",
            "is",
            "it",
            "its",
            "itself",
            "just",
            "know",
            "last",
            "let",
            "like",
            "likely",
            "made",
            "make",
            "many",
            "may",
            "me",
            "might",
            "more",
            "most",
            "much",
            "must",
            "my",
            "myself",
            "near",
            "nearly",
            "necessary",
            "need",
            "needs",
            "neither",
            "never",
            "new",
            "next",
            "no",
            "non",
            "none",
            "nor",
            "not",
            "now",
            "nowhere",
            "of",
            "off",
            "often",
            "on",
            "once",
            "only",
            "or",
            "other",
            "our",
            "ours",
            "ourselves",
            "out",
            "over",
            "own",
            "part",
            "perhaps",
            "please",
            "put",
            "quite",
            "rather",
            "really",
            "s",
            "said",
            "same",
            "say",
            "see",
            "seem",
            "seemed",
            "seeming",
            "seems",
            "seen",
            "several",
            "she",
            "should",
            "show",
            "side",
            "since",
            "so",
            "some",
            "someone",
            "something",
            "sometime",
            "sometimes",
            "somewhat",
            "somewhere",
            "still",
            "such",
            "sure",
            "t",
            "take",
            "than",
            "that",
            "thats",
            "the",
            "their",
            "theirs",
            "them",
            "themselves",
            "then",
            "there",
            "these",
            "they",
            "this",
            "those",
            "though",
            "through",
            "thus",
            "to",
            "too",
            "toward",
            "u",
            "under",
            "until",
            "up",
            "upon",
            "us",
            "use",
            "used",
            "using",
            "usually",
            "very",
            "want",
            "wants",
            "was",
            "we",
            "were",
            "well",
            "what",
            "when",
            "where",
            "which",
            "while",
            "who",
            "whom",
            "why",
            "with",
            "without",
            "you",
            "your",
        );
        return $words;
    }

}
