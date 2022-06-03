<?php

function get_user_reactions($rpid) {

    $array_r = [];
    $array_r[0]->pid = $rpid;


    if (function_exists('get_emotions_counts_all')) {
        global $reactions;
        if (!$reactions) {
            $reactions = new User_Reactions_Custom();
        }
        $reactiondata = $reactions->get_emotions_counts_all($array_r);
    }

    $reaction_count = $reactiondata['total'][$rpid];
    if (!$reaction_count)
        $reaction_count = '';
    $user = $reactiondata ["user"];
    $disquss_count = '';
    $disquss_title = '';
    if ($user) {
        $user_class = ' emotions_custom ' . $user;
    } else {
        $user_class = '';
    }

    $sql = "SELECT `count` FROM `cache_disqus_treheads` WHERE `type`='critics' and `post_id` ='" . $rpid . "' limit 1";
    $r1 = Pdo_an::db_fetch_row($sql);
    if ($r1) {
        $disquss_count = $r1->count;
    }

    $disquss_count_text=' ';
    $disquss_class='';
    if ($disquss_count)
    {
        $disquss_class  =' comment_count';
        $disquss_count_text = '<span  class="disquss_coment_count">' . $disquss_count . '</span>';
    }




    $reaction_data = '<div class="review_comment_data" id="' . $rpid . '"><a  href="#" data_title="' . $disquss_title . '" class="disquss_coment'.$disquss_class.'">'.$disquss_count_text.'</a>
                <a href="#"   class="emotions  ' . $user_class . '  "><span class="emotions_count">' . $reaction_count . '</span></a></div>';

    return $reaction_data;
}

function get_emotions($id, $single = '') {


    $reactions = new User_Reactions_Custom();
    return $reactions->custom_layout($id, $single);
}

function get_ajax() {


    $reactions = new User_Reactions_Custom();

    return $reactions->ajax();
}

function get_emotions_counts_all($array) {
    $reactions = new User_Reactions_Custom();

    return $reactions->get_emotions_counts_all($array);
}

class User_Reactions_Custom {

    public function get_emotions_counts_all($array) {
        $array_like = [];
        $result = [];
        $where = '';

        if ($array) {
            foreach ($array as $i => $val) {
                if ($val->pid) {
                    $where .= "OR post_id = '{$val->pid}' ";
                }
            }
            if ($where) {
                $where = substr($where, 2);
                global $table_prefix;
                global $pdo;
                global $wpdb;
                $sql = "SELECT * FROM {$table_prefix}user_reactoin_meta WHERE meta_key='user_reaction_total_liked' AND({$where})";

                if ($wpdb) {
                    $q = $wpdb->get_results($sql, ARRAY_A);

                    foreach ($q as $i => $r) {
                        $array_like[$r['post_id']] = $r['meta_value'];
                    }
                } else if ($pdo) {

                    ///echo $sql;
                    $q = $pdo->prepare($sql);
                    $q->execute();
                    $q->setFetchMode(PDO::FETCH_ASSOC);
                    while ($r = $q->fetch()) {
                        $array_like[$r['post_id']] = $r['meta_value'];
                    }
                }

                /// var_dump($array_like);
                //  var_dump($array_like);

                $result['total'] = $array_like;


                ////check user

                $is_liked = $this->is_liked($this->unic_id(), '', $where);

                ///var_dump($is_liked);
                if ($is_liked) {
                    $is_liked = str_replace('_', '-', $is_liked);

                    $result['user'] = $is_liked;
                }
            }
        }

        return $result;
    }

    public function delete_post_meta_reactions($post_id, $meta_key = '', $meta_value = '') {

        if ($meta_key) {
            $where = "meta_key ='{$meta_key}' AND ";
        }
        if ($meta_value) {
            $where .= "meta_value ='{$meta_value}' AND ";
        }

        global $table_prefix;
        global $pdo;
        if ($post_id) {
            $sql = "DELETE FROM {$table_prefix}user_reactoin_meta WHERE " . $where . " post_id = '{$post_id}' ";
            $q = $pdo->prepare($sql);
            $q->execute();

            $this->update_critic_post($post_id);
        }
    }

    public function insert_post_meta_reactions($post_id, $meta_key, $meta_value) {
        global $table_prefix;
        global $pdo;
        if ($post_id && $meta_key) {

            $sql = "INSERT INTO {$table_prefix}user_reactoin_meta  VALUES (NULL, '{$post_id}', '{$meta_key}', '{$meta_value}') ";
            $q = $pdo->prepare($sql);
            $q->execute();

            $this->update_critic_post($post_id);
        }
    }

    private function update_critic_post($post_id) {
        // Update critic post date. Need form cache
        global $table_prefix;
        global $pdo;
        if ($post_id) {
            // Post exist?
            $sql = sprintf("SELECT id FROM {$table_prefix}critic_matic_posts WHERE id=%d", (int) $post_id);
            $q = $pdo->prepare($sql);
            $q->execute();
            $q->setFetchMode(PDO::FETCH_ASSOC);
            $result = $q->fetch();
        
            //Update post
            if ($result) {
                $date_add = time();
                $sql = sprintf("UPDATE {$table_prefix}critic_matic_posts SET date_add=%d WHERE id = %d", (int) $date_add, (int) $post_id);
                $q = $pdo->prepare($sql);
                $q->execute();
            }
        }
    }

    public function ajax() {

        if ($this->unic_id() != $_POST['nonce']) {
            return;
        }

        $post_id = intval($_POST['post']);
        $type = ($_POST['type']);
        $vote_type = $_POST['vote_type'];

        $is_liked = $this->is_liked($this->unic_id(), $post_id);



        if ($is_liked) {
            // delete_post_meta_reactions($post_id, $is_liked, $this->unic_id());


            if ('unvote' == $vote_type) {

                /// echo $vote_type;

                $total = $this->get_post_meta_reactions($post_id, 'user_reaction_total_liked', true);

                if ($total) {
                    $total = $total["meta_value"];
                } else {
                    $total = 0;
                }

                ///echo '$total=' . $total;

                if ($total >= 0) {
                    $total = (int) $total - 1;
                    $this->delete_post_meta_reactions($post_id, 'user_reaction_total_liked');
                    $this->insert_post_meta_reactions($post_id, 'user_reaction_total_liked', $total);
                }

                $user_id = $this->unic_id();
                $this->delete_post_meta_reactions($post_id, '', $user_id);

                return;
            }
        }

        if (!$is_liked) {

            ///    echo '  not liked  +1 total post ';

            $total = $this->get_post_meta_reactions($post_id, 'user_reaction_total_liked', true);
            if ($total) {
                $total = $total["meta_value"];
            } else {
                $total = 0;
            }

            $total = (int) $total + 1;

            $this->delete_post_meta_reactions($post_id, 'user_reaction_total_liked');
            $this->insert_post_meta_reactions($post_id, 'user_reaction_total_liked', $total);
        }

        $user_id = $this->unic_id();

        ////   echo '  add new data  ';
        // update to database
        $this->delete_post_meta_reactions($post_id, '', $user_id);
        $this->insert_post_meta_reactions($post_id, 'user_reaction_' . $type, $user_id);
    }

    public function is_liked($user_id, $post_id = false, $where = '') {
        global $wpdb;
        global $pdo;
        global $table_prefix;

        $query = "SELECT * FROM {$table_prefix}user_reactoin_meta WHERE meta_key IN ( 'user_reaction_love', 'user_reaction_like', 'user_reaction_haha', 'user_reaction_wow', 'user_reaction_sad', 'user_reaction_angry' ) AND meta_value = '{$user_id}'";

        if ($post_id) {
            $query .= " AND post_id = {$post_id}";
        }
        if ($where) {
            $query .= " AND ( {$where} )";
        }
        //echo $query;
        $result = [];


        if ($wpdb) {

            $result = $wpdb->get_row($query);
            $result = $result->meta_key;
        } else {
            $q = $pdo->prepare($query);
            $q->execute();
            $q->setFetchMode(PDO::FETCH_ASSOC);

            if ($post_id) {
                $r = $q->fetch();
                $result = $r['meta_key'];
            } else {


                while ($r = $q->fetch()) {
                    $result[$r['post_id']] = $r['meta_key'];
                }
            }
        }



        return !empty($result) ? $result : false;
    }

    public function unic_id() {
        $unic_id = md5($_SERVER["HTTP_USER_AGENT"] . $_SERVER['REMOTE_ADDR']);


        return $unic_id;
    }

    public function get_post_meta_reactions($post_id, $meta_key, $single = 0) {
        global $wpdb;
        global $pdo;
        global $table_prefix;

        $query = "SELECT meta_value FROM {$table_prefix}user_reactoin_meta WHERE meta_key ='{$meta_key}' AND post_id = '{$post_id}'";


        if ($wpdb) {
            if ($single) {
                $result = $wpdb->get_var($query);
            } else {
                $result = $wpdb->get_col($query);
            }
        } else {


            $q = $pdo->prepare($query);
            $q->execute();
            $q->setFetchMode(PDO::FETCH_ASSOC);


            if ($single) {
                $result = $q->fetch();
            } else {
                $result = $q->fetch();
            }
        }

        return !empty($result) ? $result : false;
    }

    public function count_like_layout($post_id = false, $user_vote = false) {

        $reactions = array('like', 'love', 'haha', 'wow', 'sad', 'angry');

        $output = '';
        foreach ($reactions as $reaction) {
            $count = $this->get_post_meta_reactions($post_id, 'user_reaction_' . $reaction);

            $voted = '';

            if ($user_vote && $reaction == $user_vote) {
                $voted = 'voted';
            }

            if ($count) {
                $count = count($count);
            } else {
                $count = '';
            }

            $output .= '<span class="user-reaction user-reaction-' . ($reaction) . ' ' . $voted . '"><strong>' . ucfirst($reaction) . '</strong><span class="count">' . $count . '</span></span>' . PHP_EOL;
        }
        return $output;
    }

    public function custom_layout($post_id, $single = '') {


        $is_liked = $this->is_liked($this->unic_id(), $post_id);

        ///var_dump($is_liked);

        $type = 'none';
        $nonce = '';
        $syngle_class = '';



        if ($is_liked) {
            $type = 'vote';
            $user_vote = substr($is_liked, 14);
        } else {
            $type = 'unvote';
            $user_vote = '';
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

}
