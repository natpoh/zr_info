<?php

class CriticEmotions extends AbstractDB {

    private $reactions = array(
        1 => 'like',
        2 => 'love',
        3 => 'haha',
        4 => 'wow',
        5 => 'sad',
        6 => 'angry',
        7 => 'hmm',
        8 => 'dislike',
    );
    private $cm;
    public $top_results = true;

    public function __construct($cm) {
        $this->cm = $cm;
        $table_prefix = DB_PREFIX_WP_AN;
        $this->db = array(
            // CE
            'emotions' => $table_prefix . 'critic_emotions',
            'emotions_authors' => $table_prefix . 'critic_emotions_authors',
            // CM
            'posts' => $table_prefix . 'critic_matic_posts',
        );
    }

    public function get_user_reactions($post_id, $post_type = 0, $allow_cmt = true) {
        $user_class = '';

        if ($allow_cmt) {
            $disquss_count_array = $this->get_comments_count([$post_id]);
            $disquss_count = $disquss_count_array[$post_id];
            $disquss_title = '';
        }

        if ($this->top_results) {
            // Show top reaction
            $top_reactions = $this->get_top_reaction($post_id, $post_type);

            if ($top_reactions['key']) {
                $user_class = ' emotions_custom user-reaction-' . $top_reactions['key'];
                $reaction_count = $top_reactions['count'];
            }
        } else {
            // Show user reaction. Need ajax refactor
            //Reaction count
            $reaction_count = $this->get_posts_vote_count($post_id, $post_type);

            //User vote. TODO ajax request
            $user_reaction = $this->get_current_user_post_reaction($post_id, $post_type);

            if ($user_reaction) {
                $user_class = ' emotions_custom user-reaction-' . $user_reaction;
            }
        }

        if (!$reaction_count) {
            $reaction_count = '';
        }

        $disquss_count_text = ' ';
        $disquss_class = '';
        $disquss_str = '';
        if ($allow_cmt) {
            if ($disquss_count) {
                $disquss_class = ' comment_count';
                $disquss_count_text = '<span  class="disquss_coment_count">' . $disquss_count . '</span>';
            }
            $disquss_str = '<a  href="#" data_title="' . $disquss_title . '" class="disquss_coment' . $disquss_class . '">' . $disquss_count_text . '</a>';
        }
        $reaction_data = '<div class="review_comment_data" data-id="' . $post_id . '" data-ptype="' . $post_type . '">'
                . $disquss_str .
                '<a href="#"   class="emotions  ' . $user_class . '  "><span class="emotions_count">' . $reaction_count . '</span></a></div>';

        return $reaction_data;
    }

    private function get_top_reaction($post_id, $post_type = 0) {
        $top_key = '';
        $top_count = 0;
        $post_reactions = $this->get_post_reactions($post_id, $post_type);

        if ($post_reactions) {
            foreach ($post_reactions as $key => $count) {
                if ($count > $top_count) {
                    $top_key = $this->get_reaction_name($key);
                    $top_count = $count;
                }
            }
        }
        return array('key' => $top_key, 'count' => $top_count);
    }

    public function get_emotions($post_id = 0, $post_type = 0, $single = '') {
        // Get wp user        
        $user = $this->cm->get_current_user();
        $wp_uid = isset($user->ID) ? (int) $user->ID : 0;

        $user_vote = $this->get_current_user_post_reaction($post_id, $post_type, $wp_uid);

        $single_class = '';
        if ($single) {
            $single_class = ' user-reactions-single';
        }

        $nonce = $this->unic_id();
        ob_start();
        ?>
        <div class="user-reactions user-reactions-post-<?php echo $post_id . $single_class ?>" data-nonce="<?php echo $nonce ?>" data-post="<?php echo $post_id ?>" data-ptype="<?php echo $post_type ?>">
            <div class="user-reactions-button reaction-show">
                <div class="user-reactions-box">
                    <?php echo $this->count_like_layout($post_id, $post_type, $user_vote); ?>
                </div>
            </div>
        </div>
        <?php
        $content = ob_get_contents();
        ob_get_clean();

        return $content;
    }

    public function get_comments_count($post_ids) {
        $result = [];

        foreach ($post_ids as $post_id) {
            ///get comment count
            $sql = "SELECT `count` FROM `cache_disqus_treheads` WHERE `type`='critics' and `post_id` ='" . $post_id . "' limit 1";
            $r1 = Pdo_an::db_fetch_row($sql);
            if ($r1) {
                $count = $r1->count;

                if ($count) {
                    $result[$post_id] = $count;
                }
            }
        }
        return $result;
    }

    public function get_emotions_counts_all($post_ids = array(), $post_type = 0) {
        $array_like = array();
        $user_like = array();
        $result = array();

        if ($this->top_results) {
            foreach ($post_ids as $post_id) {
                $reaction = $this->get_top_reaction($post_id, $post_type);
                if ($reaction['count'] > 0) {
                    $array_like[$post_id] = $reaction['count'];
                    $user_like[$post_id] = 'user-reaction-' . $reaction['key'];
                } else {
                    $array_like[$post_id] = '';
                }
            }
        } else {

            // Get wp user
            $user = $this->cm->get_current_user();
            $wp_uid = isset($user->ID) ? (int) $user->ID : 0;
            $aid = 0;
            if (!$wp_uid) {
                $aid = $this->get_current_user_unic();
            }
            if (sizeof($post_ids)) {
                foreach ($post_ids as $post_id) {
                    $total = $this->get_posts_vote_count($post_id, $post_type);
                    $array_like[$post_id] = $total ? $total : '';
                    // Get user vote
                    if ($wp_uid) {
                        $vote = $this->get_post_vote_by_author_wp($post_id, $post_type, $wp_uid);
                    } else if ($aid) {
                        $vote = $this->get_post_vote_by_author($post_id, $post_type, $aid);
                    }

                    if ($vote) {
                        $reaction = $this->get_reaction_name($vote);
                        $user_like[$post_id] = 'user-reaction-' . $reaction;
                    }
                }
            }
        }

        $comments = $this->get_comments_count($post_ids);
        if ($comments) {
            $result['comments'] = $comments;
        }

        $result['total'] = $array_like;
        if ($user_like) {
            $result['user'] = $user_like;
        }

        return $result;
    }

    private function count_like_layout($post_id = false, $post_type = 0, $user_vote = false) {

        $post_reactions = $this->get_post_reactions($post_id, $post_type);

        $output = '';
        foreach ($this->reactions as $key => $reaction) {
            $voted = '';
            if ($user_vote && $reaction == $user_vote) {
                $voted = 'voted';
            }
            $count = '';
            if (isset($post_reactions[$key])) {
                $count = $post_reactions[$key];
            }
            $output .= '<span class="user-reaction user-reaction-' . ($reaction) . ' ' . $voted . '"><strong>' . ucfirst($reaction) . '</strong><span class="count">' . $count . '</span></span>' . PHP_EOL;
        }
        return $output;
    }

    public function get_ajax() {

        $debug =1;
        $update_vote = true;

        // Get wp user
        $user = $this->cm->get_current_user();
        $wp_uid = isset($user->ID) ? (int) $user->ID : 0;

        $unic_id = $this->unic_id();
        if ($unic_id != $_POST['nonce']) {
            $update_vote = false;
        }

        $post_type = intval($_POST['ptype']);

        if ($update_vote) {
            $post_id = intval($_POST['post']);
            $vote_type = $_POST['vote_type'];
            $vote_num = $this->get_reaction_id($_POST['type']);
            if (!$vote_num || !$post_id) {
                $update_vote = false;
            }
        }

        if ($update_vote) {
            // Get user id
            $aid = 0;
            if (!$wp_uid) {
                $aid = $this->get_or_create_author_by_name($unic_id);
            }
            if ('unvote' == $vote_type) {
                // Remove vote if need
                $this->remove_vote($post_id, $post_type, $aid, $wp_uid);
            } else {
                // Add or update vote
              $upd_data =   $this->add_or_update_vote($post_id, $post_type, $aid, $wp_uid, $vote_num);
            }
        }

        if ($this->top_results) {
            $top_reaction = $this->get_top_reaction($post_id, $post_type);
            $top_reaction['debug']=['uv'=>$update_vote,'aid'=>$aid,'vt'=>$vote_type,'vn'=>$vote_num,'pt'=>$post_type,'upd'=>$upd_data];
            print json_encode($top_reaction);
        }
        else
        {
            echo json_encode(['error'=>'error, top_results']);
        }
    }

    public function get_reaction_name($id) {
        $name = isset($this->reactions[$id]) ? $this->reactions[$id] : '';
        return $name;
    }

    public function get_reaction_id($name) {
        if (!$name) {
            return false;
        }
        $id = array_search($name, $this->reactions);
        return $id;
    }

    public function get_current_user_unic() {
        $unic_id = $this->unic_id();
        if (!$unic_id) {
            return '';
        }

        $aid = $this->get_author_by_name($unic_id);
        return $aid;
    }

    public function get_current_user_post_reaction($post_id, $post_type, $wp_uid = 0) {

        if ($wp_uid) {
            $vote = $this->get_post_vote_by_author_wp($post_id, $post_type, $wp_uid);
        } else {
            $aid = $this->get_current_user_unic();
            if (!$aid) {
                return '';
            }
            $vote = $this->get_post_vote_by_author($post_id, $post_type, $aid);
        }

        $reaction = '';
        if ($vote) {
            $reaction = $this->get_reaction_name($vote);
        }
        return $reaction;
    }

    private function get_or_create_author_by_name($name = '') {
        //Get author id
        $id = $this->get_author_by_name($name);

        if (!$id) {
            // Create the author
            $sql = sprintf("INSERT INTO {$this->db['emotions_authors']} (name) VALUES ('%s')", $this->escape($name));
            $this->db_query($sql);
            //Get the id
            $id = $this->getInsertId('id', $this->db['emotions_authors']);
        }

        return $id;
    }

    private function get_author_by_name($name) {
        $sql = sprintf("SELECT id FROM {$this->db['emotions_authors']} WHERE name='%s' ", $this->escape($name));
        $id = $this->db_get_var($sql);
        return $id;
    }

    private function add_or_update_vote($post_id = 0, $post_type = 0, $aid = 0, $wp_uid = 0, $vote_num = 0) {
        //Get vote id        
        if ($aid) {
            $vote = $this->get_post_vote_by_author($post_id, $post_type, $aid);
        } else if ($wp_uid) {
            $vote = $this->get_post_vote_by_author_wp($post_id, $post_type, $wp_uid);
        }
        $date = $this->curr_time();
        $update_vote = false;

        if ($vote) {
            // Vote exists
            if ($vote != $vote_num) {
                // Update vote
                $and = sprintf(" AND aid=%d", $aid);
                if ($wp_uid) {
                    $and = sprintf(" AND wp_uid=%d", $wp_uid);
                }

                $sql = sprintf("UPDATE {$this->db['emotions']} SET date=%d, vote=%d WHERE pid=%d AND type=%d" . $and, $date, $vote_num, $post_id, $post_type);
                $this->db_query($sql);
                $update_vote = true;
            }
        } else {
            // Add vote  
            $data = array(
                'date' => $date,
                'pid' => $post_id,
                'aid' => $aid,
                'wp_uid' => $wp_uid,
                'vote' => $vote_num,
                'type' => $post_type,
            );
            $insert_id = $this->db_insert($data, $this->db['emotions']);

            $update_vote = true;

            if ($post_type == 0) {
                // Add critic rating
                $post_wp_author = $this->cm->get_post_wp_author($post_id);
                if ($post_wp_author) {
                    if ($wp_uid != $post_wp_author) {
                        $uc = $this->cm->get_uc();
                        $uc->emotions_rating($post_wp_author, $post_type, 1, $post_id);
                    }
                }
            } else if ($post_type == 1) {
                // Add filter rating
                $uf = $this->cm->get_uf();
                $post_wp_author = $uf->get_post_wp_author($post_id);
                if ($wp_uid != $post_wp_author) {
                    $uc = $this->cm->get_uc();
                    $uc->emotions_rating($post_wp_author, $post_type, 1, $post_id);
                }
            } else if ($post_type == 2) {
                // Add watchilst rating
                $wl = $this->cm->get_wl();
                $list = $wl->get_list($post_id);
                if ($list) {
                    $post_wp_author = $list->wp_uid;
                    if ($wp_uid != $post_wp_author) {
                        $uc = $this->cm->get_uc();
                        $uc->emotions_rating($post_wp_author, $post_type, 1, $post_id);
                    }
                }
            }
        }

        if ($update_vote) {
            // Update critic post
            if ($post_type == 0) {
                $this->update_critic_post($post_id);
            } else if ($post_type == 1) {
                // Filter
                $uf = $this->cm->get_uf();
                $vote_count = $this->get_posts_vote_count($post_id, $post_type);
                $uf->update_filter($post_id, $vote_count);
            } else if ($post_type == 2) {
                // Watchlist
                $wl = $this->cm->get_wl();
                $vote_count = $this->get_posts_vote_count($post_id, $post_type);
                $data = array(
                    'rating' => $vote_count,
                );
                $wl->update_list($data, $post_id);
            }
        }
        return ['ins_id'=>$insert_id,'data'=>$data];
    }

    private function remove_vote($post_id = 0, $post_type = 0, $aid = 0, $wp_uid = 0) {
        $sql = '';
        if ($aid > 0) {
            $sql = sprintf("DELETE FROM {$this->db['emotions']} WHERE pid=%d AND type=%d AND aid=%d", (int) $post_id, (int) $post_type, (int) $aid);
        } else if ($wp_uid > 0) {
            $sql = sprintf("DELETE FROM {$this->db['emotions']} WHERE pid=%d AND type=%d AND wp_uid=%d", (int) $post_id, (int) $post_type, (int) $wp_uid);
        }
        if ($sql) {
            $this->db_query($sql);
        }
        //Remove author if he not posts
        if ($aid) {
            if (!$this->get_author_posts_count($aid)) {
                $this->remove_author($aid);
            }
        }
        if ($post_type == 0) {
            // Remove critic author rating
            $post_wp_author = $this->cm->get_post_wp_author($post_id);
            if ($post_wp_author) {
                if ($wp_uid != $post_wp_author) {
                    $uc = $this->cm->get_uc();
                    $uc->emotions_rating($post_wp_author, $post_type, 1, $post_id, true);
                }
            }

            // Update critic post
            $this->update_critic_post($post_id);
        } else if ($post_type == 1) {
            // Add filter rating
            $uf = $this->cm->get_uf();
            $post_wp_author = $uf->get_post_wp_author($post_id);
            if ($wp_uid != $post_wp_author) {
                $uc = $this->cm->get_uc();
                $uc->emotions_rating($post_wp_author, $post_type, 1, $post_id, true);
            }
            $vote_count = $this->get_posts_vote_count($post_id, $post_type);
            $uf->update_filter($post_id, $vote_count);
        }
    }

    private function update_critic_post($post_id) {
        // Update critic post date. Need form cache
        if ($post_id) {
            // Post exist?
            $sql = sprintf("SELECT id FROM {$this->db['posts']} WHERE id=%d", (int) $post_id);
            $result = $this->db_get_var($sql);

            //Update post
            if ($result) {
                $date_add = $this->curr_time();
                $sql = sprintf("UPDATE {$this->db['posts']} SET date_add=%d WHERE id = %d", (int) $date_add, (int) $post_id);
                $this->db_query($sql);
            }
        }
    }

    private function remove_author($aid = 0) {
        $sql = sprintf("DELETE FROM {$this->db['emotions_authors']} WHERE aid=%d", (int) $aid);
        $this->db_query($sql);
    }

    private function get_author_posts_count($aid = 0, $post_type = -1) {
        $post_type_and = '';
        if ($post_type != -1) {
            $post_type_and = " AND type=" . (int) $post_type;
        }
        $aid = (int) $aid;
        $sql = "SELECT COUNT(id) FROM {$this->db['emotions']} WHERE aid={$aid}" . $post_type_and;
        return $this->db_get_var($sql);
    }

    private function get_posts_vote_count($pid, $post_type = 0) {
        $sql = sprintf("SELECT COUNT(id) FROM {$this->db['emotions']} WHERE pid=%d AND type=%d", (int) $pid, (int) $post_type);
        return $this->db_get_var($sql);
    }

    private function get_post_vote_by_author($post_id, $post_type, $aid) {
        $sql = sprintf("SELECT vote FROM {$this->db['emotions']} WHERE pid=%d AND type=%d AND aid=%d", (int) $post_id, (int) $post_type, (int) $aid);
        return $this->db_get_var($sql);
    }

    private function get_post_vote_by_author_wp($post_id, $post_type, $wp_uid) {
        $sql = sprintf("SELECT vote FROM {$this->db['emotions']} WHERE pid=%d AND type=%d AND wp_uid=%d", (int) $post_id, (int) $post_type, (int) $wp_uid);
        return $this->db_get_var($sql);
    }

    public function unic_id() {
        $ip = $this->cm->get_remote_ip();
        $unic_id = md5($_SERVER["HTTP_USER_AGENT"] . $ip);
        return $unic_id;
    }

    private function get_post_reactions($post_id = 0, $post_type = 0) {
        $sql = sprintf("SELECT vote FROM {$this->db['emotions']} WHERE pid=%d AND type=%d", (int) $post_id, (int) $post_type);
        $reactions = $this->db_results($sql);

        $result = array();
        if (sizeof($reactions)) {
            foreach ($reactions as $reaction) {
                if (!isset($result[$reaction->vote])) {
                    $result[$reaction->vote] = 1;
                } else {
                    $result[$reaction->vote] += 1;
                }
            }
        }
        return $result;
    }
}
