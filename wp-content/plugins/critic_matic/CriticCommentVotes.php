<?php

/**
 * Description of CriticCommentVotes
 *
 * @author brahman
 */
class CriticCommentVotes extends AbstractDB {

    private $cm;
    private $db;

    public function __construct($cm = '') {
        $this->cm = $cm ? $cm : new CriticMatic();
        $table_prefix = DB_PREFIX_WP_AN;
        $this->db = array(
            // Comments
            'comments' => 'data_comments',
            'comments_num' => 'meta_comments_num',
            // Votes
            'votes' => 'data_comment_votes',
            'votes_log' => 'data_comment_votes_log',
        );
    }

    public function ajax_thumb($form) {

        $id = (int) $form['cid'];
        $vote = $form['thumb'];
        $sjs = (int) $form['sjs'] != 'no' ? $form['sjs'] : 0;

        $userdata = wp_get_current_user();
        $ip = $this->cm->get_remote_ip();
        $user = (int) $userdata->ID ? $userdata->ID : 0;

        // ctg votes begin
        $allow_vote = $vote == "up" || $vote == "dw";
        $status = "error";
        $msg = "";
        $theme = "status";
        $vote_value = $vote == "up" ? 1 : -1;

        if ($allow_vote && $user) {

            // User ban check
            if (class_exists('UsersBan')) {
                $usersBan = new UsersBan();
                $ban_type = $usersBan->banType($user);

                if ($ban_type == USER_BAN_BLOCK) {
                    $allow_vote = false;
                    $msg = "You cannot vote because your profile is blocked";
                    $theme = "warning";
                } else if ($ban_type == USER_BAN_VOTE && $usersBan->banTimeActive($user)) {
                    $allow_vote = false;
                    $msg = "Voting is restricted for you due to violations of site rules";
                    $theme = "warning";
                }
            }
        }
        $comment = '';
        if ($allow_vote && $user) {
            // Blacklist
            if (function_exists('userbl_init')) {
                $comments = $this->cm->get_comments();
                $comment = $comments->get_comment($id);
                $userbl = new UserBl();
                // The user you are voting for has added you to their blacklist
                $ban_type = $userbl->inBl($user, $comment->user_id);
                if ($ban_type) {
                    $allow_vote = false;
                    $msg = "You cannot vote for comments from a user who has blacklisted you";
                    $theme = "warning";
                }

                if ($allow_vote) {
                    // You have blacklisted the user
                    $ban_type = $userbl->inBl($comment->user_id, $user);
                    if ($ban_type) {
                        $allow_vote = false;
                        $msg = "You cannot vote for comments from a user whom you have blacklisted";
                        $theme = "warning";
                    }
                }
            }
        }

        // SJS
        if ($sjs == 0) {
            $allow_vote = false;
            $msg = "You cannot vote for this comment.";
            $theme = "warning";
        }

        // Unic emotions id
        $unic_emoid = 0;
        if (!$user) {
            $ce = $this->cm->get_ce();
            $unic_id = $ce->unic_id();
            $unic_emoid = $ce->get_or_create_author_by_name($unic_id);
        }

        // Voting for your own comments
        if ($allow_vote) {
            if ($user > 0) {
                if (!$comment) {
                    $comments = $this->cm->get_comments();
                    $comment = $comments->get_comment($id);
                }
                $comment_author = $comment->user_id;
                if ($user == $comment_author) {
                    $allow_vote = false;
                }
                if (!$allow_vote) {
                    $msg = "You cannot vote for your own comments.";
                    $theme = "warning";
                }
            }
        }

        // Already voted
        if ($allow_vote) {
            if ($user > 0) {
                $allow_vote = $this->check_alredy($id, $user, $vote_value);
                if (!$allow_vote) {
                    $msg = "You have already voted for this comment.";
                    $theme = "warning";
                }
            } else {
                $allow_vote = $this->check_self_guest($id, $sjs);
                if (!$allow_vote) {
                    $msg = "You have already voted for this comment.";
                    $theme = "warning";
                }
            }
        }

        // Anonymous users cannot downvote - April 3, 2018
        if ($allow_vote) {
            if (!$user) {
                // Only for anonymous users
                if ($vote == "dw") {
                    $allow_vote = false;
                    $msg = "Downvoting is restricted for anonymous users. Please log in to remove restrictions.";
                    $theme = "warning";
                }
            }
        }

        $vote_count = array();
        $data = array();
        if ($allow_vote) {
            $status = "ok";
            $msg = "Your vote has been counted.";

            // Insert vote log
            $data = array(
                'date' => $this->curr_time(),
                'cid' => $id,
                'wp_uid' => $user,
                'user_sjs' => $sjs,
                'user_vote' => $vote_value,
                'user_ip' => $ip,
                'unic_emoid' => $unic_emoid,
            );
            $this->db_insert($data, $this->db['votes_log']);

            // Update comment vote
            $vote_count = $this->update_comment_vote($id);

            // Update comment last_upd
            $this->update_comment_date($id);
        }

        $rtn = new stdClass();
        $rtn->type = $status;
        $rtn->msg = $msg;
        $rtn->data = $vote_count;
        die(json_encode($rtn));
    }

    public function ajax_thumbs_vote_data($form) {
        /*
         * Get a list of comment ids and issue a verdict on them
         * For a registered user, search by user id
         * For a guest by sjs
         *
         * Accept a list of comment ids
         * Return a list of ids that cannot be voted for.
         * You need to specify how the user voted
         */

        $comments = $form['comments'];
        $sjs = (int) $form['sjs'];

        $ret_info = array('type' => 'empty', 'data' => array());
        if (isset($sjs) && is_array($comments) && count($comments) > 0) {

            $clear_cid = array();
            foreach ($comments as $cid) {
                $clear_cid[] = (int) $cid;
            }
            $in_sql = implode(',', $clear_cid);

            // Проверяем пользователя
            $current_user = $this->cm->get_current_user();

            $user_sql = "user_sjs = " . $sjs;
            if ($current_user && $current_user->ID > 0) {
                $user_sql = "wp_uid = " . $current_user->ID;
            }

            $sql = sprintf("SELECT cid, user_vote FROM {$this->db['votes_log']} WHERE " . $user_sql . " AND cid IN(%s)", $in_sql);
            $results = $this->db_results($sql);

            $ret = array();
            if ($results) {
                $ret_info['type'] = 'ok';
                foreach ($results as $item) {
                    $ret[] = array($item->cid, $item->user_vote);
                }
                $ret_info['data'] = $ret;
            }
        }


        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            print json_encode($ret_info);
        } else {
            header("Location: " . $_SERVER["HTTP_REFERER"]);
        }
        die();
    }

    public function ajax_vote_info_users($form) {
        $cid = 0;
        $vote = true;

        if (strstr($form['id'], "comment-")) {
            $cid = (int) str_replace('comment-', '', $form['id']);
        }

        if (!is_integer($cid) || $cid == 0) {
            exit("Error comment id");
        }

        // Get info
        $sql = sprintf("SELECT id, wp_uid, user_vote FROM {$this->db['votes_log']} WHERE cid = %d", $cid);
        $result = $this->db_results($sql);

        if (sizeof($result) == 0) {
            $vote = false;
        }
        $plusUsersNum = 0;
        $minusUsersNum = 0;

        $wpu = $this->cm->get_wpu();
        $cav = $this->cm->get_cav();

        $vote_data = array();
        foreach ($result as $item) {
            if ($item->wp_uid > 0) {
                $uid = $item->wp_uid;
                //get user
                $user_data = $wpu->get_user_by_id($uid);
                if (!$user_data) {
                    continue;
                }

                $user = new stdClass();
                $user->name = $user_data->display_name;
                $user->avatar = $cav->get_user_avatar($uid, 40);
                $user->url = '/author/' . $user_data->user_nicename;
                $user->vote = $item->user_vote;

                if (isset($vote_data['user'][$uid])) {
                    if ($item->user_vote == '1') {
                        $plusGuestNum++;
                    } else {
                        $minusGuestNum++;
                    }
                    continue;
                }

                $vote_data['user'][$uid] = (array) $user;

                if ($item->user_vote == '1') {
                    $plusUsersNum++;
                } else {
                    $minusUsersNum++;
                }
            } else {
                $vote_data['guest'][$item->user_vote] += 1;

                if ($item->user_vote == '1') {
                    $plusGuestNum++;
                } else {
                    $minusGuestNum++;
                }
            }
        }

        $totalVotes = sizeof($result);
        $guestCountPlusText = $this->getGuestInfo($plusGuestNum);
        $plusSep = ($plusUsersNum > 0 && $plusGuestNum > 0) ? ", " : '';
        $plusText = $this->getUserInfo($plusUsersNum) . $plusSep . $guestCountPlusText;

        $guestCountMinusText = $this->getGuestInfo($minusGuestNum);
        $minusSep = ($minusUsersNum > 0 && $minusGuestNum > 0) ? ", " : '';
        $minusText = $this->getUserInfo($minusUsersNum) . $minusSep . $guestCountMinusText;

        $vote_data['data'] = array(
            'plusText' => $plusText,
            'plusGuestText' => $guestCountPlusText,
            'plusCount' => $plusGuestNum,
            'minusText' => $minusText,
            'minusGuestText' => $guestCountMinusText,
            'minusCount' => $minusGuestNum,
            'totalVotes' => "$totalVotes vote" . $this->ctg_okonchanie($totalVotes),
        );

        if (isset($vote_data['user'])) {
            ksort($vote_data['user']);
        }

        if ($vote === false) {
            $result['type'] = "error";
            $result['vote_data'] = 'error';
        } else {
            $result['type'] = "success";
            $result['vote_data'] = $vote_data;
        }

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            $result = json_encode($result);
            echo $result;
        } else {
            header("Location: " . $_SERVER["HTTP_REFERER"]);
        }

        die();
    }

    private function update_comment_vote($cid = 0) {
        $vote_count = $this->vote_count($cid);
        // Vote exists
        $sql = sprintf("SELECT id FROM {$this->db['votes']} WHERE cid=%d", $cid);
        $id_exist = $this->db_get_var($sql);

        $vote_count['last_upd'] = $this->curr_time();

        if ($id_exist) {
            // Update
            $this->db_update($vote_count, $this->db['votes'], $id_exist);
        } else {
            // Insert
            $vote_count['cid'] = $cid;
            $id_exist = $this->db_insert($vote_count, $this->db['votes']);
        }
        return $vote_count;
    }

    public function vote_count($cid) {
        $sql = sprintf("SELECT user_vote FROM {$this->db['votes_log']} WHERE cid=%d", $cid);
        $results = $this->db_results($sql);
        $ret = array(
            'plus' => 0,
            'minus' => 0,
            'vote_result' => 0,
        );
        if ($results) {
            foreach ($results as $item) {
                $val = $item->user_vote;
                if ($val > 0) {
                    $ret['plus'] += 1;
                } else {
                    $ret['minus'] += 1;
                }
                $ret['vote_result'] += $val;
            }
        }
        return $ret;
    }

    public function update_comment_date($comment_id) {
        $data = array();
        $data['last_upd'] = $this->curr_time();
        $this->db_update($data, $this->db['comments'], (int) $comment_id, 'comment_ID');
    }

    private function check_alredy($cid, $wp_uid, $vote_value) {
        $sql = sprintf("SELECT id FROM {$this->db['votes_log']} WHERE cid = %d AND wp_uid = %d AND user_vote=%d", (int) $cid, (int) $wp_uid, (int) $vote_value);

        $result = $this->db_get_var($sql);
        if ($result) {
            return false;
        }
        return true;
    }

    private function check_self($cid, $wp_uid) {
        $sql = sprintf("SELECT id FROM {$this->db['votes_log']} WHERE cid = %d AND wp_uid = %d", (int) $cid, (int) $wp_uid);

        $result = $this->db_get_var($sql);
        if ($result) {
            return false;
        }
        return true;
    }

    private function check_self_guest($cid, $sjs) {
        $sql = sprintf("SELECT id FROM {$this->db['votes_log']} WHERE cid = %d AND wp_uid = 0 AND user_sjs=%d", (int) $cid, (int) $sjs);

        $result = $this->db_get_var($sql);
        if ($result) {
            return false;
        }
        return true;
    }

    private function getGuestInfo($guest = 0) {
        $ret = '';

        if ($guest > 0) {

            $ret .= $guest . ' anon' . $this->ctg_okonchanie($guest);
        }

        return $ret;
    }

    private function getUserInfo($user = 0) {
        $ret = '';
        if ($user > 0) {
            $ret .= $user . ' user' . $this->ctg_okonchanie($user);
        }

        return $ret;
    }

    function ctg_okonchanie($num = 1, $one = '', $two = 's', $five = 's') {
        $ret = $one;
        if ($num > 100) {
            $sNum = ' ' . $num;
            $num = substr($sNum, strlen($sNum) - 2);
        }

        if ($num >= 20) {
            $sNum = ' ' . $num;
            $num = $sNum[strlen($sNum) - 1];
        }
        //p_r($num);
        switch ($num) {
            case 1:
                $num = $one;
                break;
            case 2:
                $num = $two;
                break;
            case 3:
                $num = $two;
                break;
            case 4:
                $num = $two;
                break;
            default:
                $num = $five;
                break;
        }
        return $num;
    }
}
