<?php

class CriticEmotions extends AbstractDB {

    private $reactions = array(
        1 => 'like',
        2 => 'love',
        3 => 'haha',
        4 => 'wow',
        5 => 'sad',
        6 => 'angry'
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

    public function get_user_reactions($post_id) {
        $user_class = '';
        $disquss_count = '';
        $disquss_title = '';

        if ($this->top_results) {
            // Show top reaction
            $top_reactions = $this->get_top_reaction($post_id);

            if ($top_reactions['key']) {
                $user_class = ' emotions_custom user-reaction-' . $top_reactions['key'];
                $reaction_count = $top_reactions['count'];
            }
        } else {
            // Show user reaction. Need ajax refactor
            //Reaction count
            $reaction_count = $this->get_posts_vote_count($post_id);

            //User vote. TODO ajax request
            $user_reaction = $this->get_current_user_post_reaction($post_id);

            if ($user_reaction) {
                $user_class = ' emotions_custom user-reaction-' . $user_reaction;
            }
        }


        if (!$reaction_count) {
            $reaction_count = '';
        }

        $reaction_data = '<div class="review_comment_data" id="' . $post_id . '"><a  href="#" data_title="' . $disquss_title . '" class="disquss_coment"><span  class="disquss_coment_count">' . $disquss_count . '</span></a>
                <a href="#"   class="emotions  ' . $user_class . '  "><span class="emotions_count">' . $reaction_count . '</span></a></div>';

        return $reaction_data;
    }

    private function get_top_reaction($post_id) {
        $top_key = '';
        $top_count = 0;
        $post_reactions = $this->get_post_reactions($post_id);

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

    public function get_emotions($post_id, $single = '') {

        $user_vote = $this->get_current_user_post_reaction($post_id);

        $type = 'unvote';
        if ($user_vote) {
            $type = 'vote';
        }

        $single_class = '';
        if ($single) {
            $single_class = ' user-reactions-single';
        }

        $nonce = $this->unic_id();
        ob_start();
        ?>
        <div class="user-reactions user-reactions-post-<?php echo $post_id . $single_class ?>" data-nonce="<?php echo $nonce ?>" data-post="<?php echo $post_id ?>">
            <div class="user-reactions-button reaction-show">
                <div class="user-reactions-box">
                    <?php echo $this->count_like_layout($post_id, $user_vote); ?>
                </div>
            </div>
        </div>
        <?php
        $content = ob_get_contents();
        ob_get_clean();

        return $content;
    }

    public function get_emotions_counts_all($post_ids = array()) {
        $array_like = array();
        $user_like = array();
        $result = array();

        if ($this->top_results) {
            foreach ($post_ids as $post_id) {
                $reaction = $this->get_top_reaction($post_id);
                if ($reaction['count'] > 0) {
                    $array_like[$post_id] = $reaction['count'];
                    $user_like[$post_id] = 'user-reaction-' . $reaction['key'];
                } else {
                    $array_like[$post_id] = '';
                }
            }
        } else {

            $aid = $this->get_current_user();

            if (sizeof($post_ids)) {
                foreach ($post_ids as $post_id) {
                    $total = $this->get_posts_vote_count($post_id);
                    $array_like[$post_id] = $total ? $total : '';
                    if ($aid) {
                        // Get user vote
                        $vote = $this->get_post_vote_by_author($post_id, $aid);
                        if ($vote) {
                            $reaction = $this->get_reaction_name($vote);
                            $user_like[$post_id] = 'user-reaction-' . $reaction;
                        }
                    }
                }
            }
        }
        $result['total'] = $array_like;
        if ($user_like) {
            $result['user'] = $user_like;
        }

        return $result;
    }

    private function count_like_layout($post_id = false, $user_vote = false) {

        $post_reactions = $this->get_post_reactions($post_id);

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

        $update_vote = true;

        $unic_id = $this->unic_id();
        if ($unic_id != $_POST['nonce']) {
            $update_vote = false;
        }

        if ($update_vote) {
            $post_id = intval($_POST['post']);
            $vote_type = $_POST['vote_type'];
            $type = $this->get_reaction_id($_POST['type']);
            if (!$type || !$post_id) {
                $update_vote = false;
            }
        }

        if ($update_vote) {
            // Get user id
            $aid = $this->get_or_create_author_by_name($unic_id);
            if ('unvote' == $vote_type) {
                // Remove vote if need
                $this->remove_vote($post_id, $aid);
            } else {
                // Add or update vote
                $this->add_or_update_vote($post_id, $aid, $type);
            }
        }

        if ($this->top_results) {
            $top_reaction = $this->get_top_reaction($post_id);
            print json_encode($top_reaction);
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

    public function get_current_user() {
        $unic_id = $this->unic_id();
        if (!$unic_id) {
            return '';
        }

        $aid = $this->get_author_by_name($unic_id);
        return $aid;
    }

    public function get_current_user_post_reaction($post_id) {

        $aid = $this->get_current_user();
        if (!$aid) {
            return '';
        }

        $vote = $this->get_post_vote_by_author($post_id, $aid);
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

    private function add_or_update_vote($post_id = 0, $aid = 0, $type = 0) {
        //Get vote id        
        $vote = $this->get_post_vote_by_author($post_id, $aid);
        $date = $this->curr_time();
        $update_vote = false;

        if ($vote) {
            // Vote exists
            if ($vote != $type) {
                // Update vote
                $sql = sprintf("UPDATE {$this->db['emotions']} SET date=%d, vote=%d WHERE pid=%d AND aid=%d", $date, $type, $post_id, $aid);
                $this->db_query($sql);
                $update_vote = true;
            }
        } else {
            // Add vote            
            $sql = sprintf("INSERT INTO {$this->db['emotions']} (date,pid,aid,vote) VALUES (%d,%d,%d,%d)", $date, $post_id, $aid, $type);
            $this->db_query($sql);
            $update_vote = true;
        }

        if ($update_vote) {
            // Update critic post
            $this->update_critic_post($post_id);
        }
    }

    private function remove_vote($post_id = 0, $aid = 0) {
        $sql = sprintf("DELETE FROM {$this->db['emotions']} WHERE pid=%d AND aid=%d", (int) $post_id, (int) $aid);
        $this->db_query($sql);

        //Remove author if he not posts
        if (!$this->get_author_posts_count($aid)) {
            $this->remove_author($aid);
        }

        // Update critic post
        $this->update_critic_post($post_id);
    }

    private function update_critic_post($post_id) {
        // Update critic post date. Need form cache
        if ($post_id) {
            // Post exist?
            $sql = sprintf("SELECT id FROM {$this->db['posts']} WHERE id=%d", (int) $post_id);
            $result = $this->db_get_var($sql);

            //Update post
            if ($result) {
                $date_add = time();
                $sql = sprintf("UPDATE {$this->db['posts']} SET date_add=%d WHERE id = %d", (int) $date_add, (int) $post_id);
                $this->db_query($sql);
            }
        }
    }

    private function remove_author($aid = 0) {
        $sql = sprintf("DELETE FROM {$this->db['emotions_authors']} WHERE aid=%d", (int) $aid);
        $this->db_query($sql);
    }

    private function get_author_posts_count($aid) {
        $sql = sprintf("SELECT COUNT(id) FROM {$this->db['emotions']} WHERE aid='%d' ", (int) $aid);
        return $this->db_get_var($sql);
    }

    private function get_posts_vote_count($pid) {
        $sql = sprintf("SELECT COUNT(id) FROM {$this->db['emotions']} WHERE pid='%d' ", (int) $pid);
        return $this->db_get_var($sql);
    }

    private function get_post_vote_by_author($post_id, $aid) {
        $sql = sprintf("SELECT vote FROM {$this->db['emotions']} WHERE pid=%d AND aid=%d", (int) $post_id, (int) $aid);
        return $this->db_get_var($sql);
    }

    public function unic_id() {
        $ip = $this->cm->get_remote_ip();
        $unic_id = md5($_SERVER["HTTP_USER_AGENT"] . $ip);
        return $unic_id;
    }

    private function get_post_reactions($post_id) {
        $sql = sprintf("SELECT vote FROM {$this->db['emotions']} WHERE pid=%d", (int) $post_id);
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
