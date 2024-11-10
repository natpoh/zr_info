<?php
/*
 * TODO need:
 * 
 * Admin 
 * + add_comment wp filters and actions 
 * block comment form for banned users
 * user ban options in user profile
 * +flag comment text in admin panel 
 * 
 * + wp_filter to content after edit comment 
 * + view comment on moderation for author 
 * + add class "user" to body after user login form commentform 
 * + Show comments list in popover Review
 * + check status after WP comments filter an send message to user - 301
 * + css styles for: comments
 * + translate pluggins: flagreport, usersban
 * + night theme comments 
 * 
 * Author
 * + ajax load more user comments from author
 * + move to comment for user page/get comment full path or move to full path
 * + show answers in user panel
 * 
 * Search 
 * + update init comments after ajax load page
 * + index comment after change
 * + bulk actions: delete and approve comments
 *
 * Bug
 * +move to trash menu not reindex
 * +user comments count wrong
 * +after send message <br> is removed
 * +after login add respond another ajax request
 * 
 * Votes
 * + update comment date_upd after vote (for reindex)
 * 
 * // LOW
 * + add tomato icon to Anon comments
 * + add sjs logic to add comment
 * change comments counter after spam or delete comment
 * flag send message email
 * anon cant vote for his comments
 * similar comments filter
 * cancel vote / revote
 * edit comments page
 * ?view comment on moderation for Anon
 */

class CriticComments extends AbstractDB {

    private $cm;
    private $db;
    private $cc;
    // TODO set to 20
    public $comments_per_page = 20;
    public $max_num_comment_pages;
    public $comments = '';
    private $curr_user = '';
    private $post_author = 0;
    private $user = 0;
    private $post_author_class = ' pau';
    private $comm_author_class = ' cau';
    private $is_recent = false;
    private $is_search = false;
    private $show_avatar = true;
    public $post_type = array(
        'critic' => 0,
        'page' => 1,
        'movies' => 2,
        'games' => 3,
        'actors' => 4,
    );
    public $bulk_actions = array(
        'approve' => 'Approve',
        'hold' => 'Pending',
        'spam' => 'Spam',
        'trash' => 'Trash',
    );

    public function __construct($cm = '') {
        $this->cm = $cm ? $cm : new CriticMatic();
        $table_prefix = DB_PREFIX_WP_AN;
        $this->db = array(
            // CM
            'posts' => $table_prefix . 'critic_matic_posts',
            'meta' => $table_prefix . 'critic_matic_posts_meta',
            'rating' => $table_prefix . 'critic_matic_rating',
            'authors' => $table_prefix . 'critic_matic_authors',
            'authors_meta' => $table_prefix . 'critic_matic_authors_meta',
            'movies_meta' => $table_prefix . 'critic_movies_meta',
            'ip' => $table_prefix . 'critic_matic_ip',
            'author_key' => $table_prefix . 'meta_critic_author_key',
            // Comments
            'comments' => 'data_comments',
            'comments_num' => 'meta_comments_num',
        );
    }

    private function get_cc(){
        if (!$this->cc){
            $this->cc = $this->cm->get_cc();
        }
        return $this->cc;        
    }
    
    private function reset_user(){
        $this->curr_user = '';
    }


    private function get_user() {
        if ($this->curr_user == '') {
            if (function_exists('wp_get_current_user')) {
                $this->curr_user = wp_get_current_user();
            } else {
                $this->curr_user = $this->cm->get_current_user();
            }
            $this->curr_uid = $this->curr_user->ID;
        }
        return $this->curr_user;
    }

    private function get_user_id() {
        if (!$this->curr_user) {
            $this->get_user();
        }
        return $this->curr_uid;
    }

    public function ajax_respond($form) {
        /*
          comment_post_ID: 292911
          post_type: 0
          comment_nonce: efe8f5bc90
          comment_parent: 0
         */

        $rtn = new stdClass();
        $rtn->err = array();
        $rtn->success = false;
        $rtn->needlogin = false;

        $user_sjs = (int) $form['sjs'];

        // Check nonce
        $nonce = $form['comment_nonce'];
        if (!$this->cm->wp_verify_nonce($nonce, 'comments')) {
            $rtn->err[] = 'Error CSRF: ' . $nonce;
        }

        $comment_content = trim($form['comment_content']);
        $min_content_len = 1;
        if (!$comment_content) {
            $rtn->err[] = 'Comment Text is required.';
        } else {
            $clear_text = trim(preg_replace("/[^A-Za-z0-9 ]/", '', strip_tags($comment_content)));
            if (strlen($clear_text) < $min_content_len) {
                $rtn->err[] = 'Comment Text is too small.';
            }
        }

        if (count($rtn->err)) {
            // die here if we failed any spambot checks
            die(json_encode($rtn));
        }

        // User
        $user = $this->get_user();
        $uid = $user->ID;
        $user_login = $user->ID > 0 ? true : false;
        $email = '';

        // Anon review            
        $comment_user_name = $form['comment_user_name'];
        $anon_review = $comment_user_name ? false : true;
        if ($user_login) {
            $email = $user->user_email;
            $anon_review = false;
        }

        $author_name = '';

        if ($uid) {
            $author_name = $user->display_name;
        }


        if (!$author_name && $comment_user_name) {
            $author_name = trim(preg_replace("/[^A-Za-z0-9 ]/", '', $comment_user_name));
            if (strlen($author_name) > 50) {
                $author_name = substr($author_name, 0, 50);
            }
        }

        if (!$author_name) {
            if ($anon_review) {
                $author_name = 'Anon';
            } else {
                $rtn->err[] = 'User Name is required.';
            }
        }

        $pass = '';

        if (!$anon_review && !$uid) {
            // Login or create user

            $comment_user_pass = $form['comment_user_pass'];

            if (!$comment_user_pass) {
                $rtn->err[] = 'Password is required.';
            }

            if ($comment_user_pass) {
                $pass = trim($comment_user_pass);
            }

            // Allow login user
            if ($author_name && $pass) {

                if (class_exists('GuestLogin')) {
                    // Need WP core

                    $gl = new GuestLogin();

                    $login_exist = $gl->loginExist($author_name);
                    if ($login_exist) {
                        // Check pass
                        if ($gl->getCheckUserPass($pass, $login_exist->user_pass, $login_exist->ID)) {
                            // Password correct
                            $uid = $login_exist->ID;
                        } else {
                            // Password incorrect
                            $rtn->err[] = 'Password incorrect. This name "' . $author_name . '" already used. Enter another name or type correct password.';
                        }
                    } else {
                        // Create new user
                        $uid = $gl->create_new_user($author_name, $pass);
                    }

                    if ($uid) {
                        // Login         
                        // Set user data to comment cookeis
                        $comment = new stdClass();
                        $comment->comment_author = $author_name;
                        $comment->comment_author_pass = base64_encode($pass);
                        wp_set_comment_cookies($comment, $user);
                        $gl->wp_login_user($uid);
                        $rtn->needlogin = true;
                        $this->reset_user();
                    }
                }
            }
        }

        // Check post id
        $curr_time = $this->curr_time();
        $comment_parent = (int) $form['comment_parent'];
        $comment_post_ID = (int) $form['comment_post_ID'];
        $post_type = (int) $form['comment_post_type'];
        if ($comment_parent) {
            // Get post type from parent
            $parent = $this->get_comment($comment_parent);
            if ($parent){
                $comment_post_ID = $parent->comment_post_ID;
                $post_type = $parent->post_type;
            } else {
                $rtn->err[] = 'Parent comment not vaild';
            }
            
        }

        if (!$comment_post_ID) {
            $rtn->err[] = 'Parent publication not vaild';
        } else {
            // Check pulication exists
            $post_data = $this->get_comment_post_data($comment_post_ID, $post_type);
            if (!$post_data->link){
                $rtn->err[] = 'Parent publication not found';
            }
        }

        //Comment to disable spambot check
        if (count($rtn->err)) {
            // die here if we failed any spambot checks
            die(json_encode($rtn));
        }

        // Create Author acc for new users

        $aid = 0;
        $author_type = 2;
        $new_author = false;
        $unic_id = $this->cm->unic_id();
        if ($author_name) {

            // Audience
            if ($author_name == 'Anon') {
                $first_author = $this->cm->get_author_by_name($author_name, true, $author_type);
                if ($first_author) {
                    $aid = $first_author->id;
                }
            } else {
                $author = $this->cm->get_author_by_wp_uid($uid, true);
                if ($author) {
                    $aid = $author->id;
                }
            }
            if (!$aid) {
                // Get remote aid for a new author
                $new_author = true;
                $author_status = 1;
                $options = array('audience' => $unic_id);
                $aid = $this->cm->create_author_by_name($author_name, $author_type, $author_status, $options, $uid);
            }
        }

        if ($aid) {
            // Local, not sync
            $this->add_author_key($aid);
        }

        $ip = $this->cm->get_remote_ip();

        if (function_exists('sanitize_text_field')) {
            $comment_content = str_replace("<div", "\n<div", $comment_content);
            $comment_content = _sanitize_text_fields($comment_content, true);
        }




        $data = array(
            'comment_post_ID' => $comment_post_ID,
            'comment_author' => $author_name,
            'comment_author_email' => $email,
            'comment_date' => date('Y-m-d H:i:s', $curr_time),
            'comment_date_gmt' => gmdate('Y-m-d H:i:s', $curr_time),
            'comment_content' => $comment_content,
            'comment_approved' => 1,
            'comment_agent' => $_SERVER["HTTP_USER_AGENT"],
            'comment_author_IP' => $ip,
            'comment_parent' => $comment_parent,
            'user_id' => $uid,
            'post_type' => $post_type,
            'aid' => $aid,
            'user_sjs' => $user_sjs,
        );

        $cid = $this->wp_new_comment($data);
        // TODO check status after filter an send message to user
        if (!$cid) {
            $rtn->err[] = 'Error: Cannot add comment';
            die(json_encode($rtn));
        }

        //$cid = $this->wp_insert_comment($data);


        $rtn->msg = 'Your comment has been sent successfully';
        $rtn->theme = 'status';
        // Check comment after all 
        $comment = $this->get_comment($cid, false);
        if (!$comment->comment_approved == 1) {
            $rtn->msg = 'Your comment is awaiting moderation';
            $rtn->theme = 'warning';
        }

        $rtn->comment = $this->theme_one_comment($comment);       

        $rtn->parent = $comment_parent;
        $rtn->cid = $cid;
        $rtn->user_name = $author_name;
        $rtn->success = true;
        die(json_encode($rtn));
    }

    public function ajax_get_childs($form) {
        // Page num
        $page_num = isset($form['page']) ? (int) $form['page'] : 1;
        // Author page
        $author_id = isset($form['author_id']) ? (int) $form['author_id'] : 0;
        $post_type = isset($form['post_type']) ? (int) $form['post_type'] : -1;
        $post_id = isset($form['post_id']) ? (int) $form['post_id'] : 0;
        if ($author_id > 0) {
            $post_type = -1;
        }

        $comment_parent = 0;
        if ($form['cid']) {
            $comment_parent = (int) str_replace('comment-', '', $form['cid']);
        }

        $uid = $this->get_user_id();

        $comments = $this->get_post_comments_data($post_id, $post_type, $comment_parent, $page_num, $uid, $author_id);

        $comments_count = $this->get_comments_count($post_id, $post_type, $comment_parent, $uid, $author_id);

        // Childs level
        $level = 1;
        if ($comment_parent) {
            $level = 2;
        }
        ob_start();
        ?>
        <?php
        if ($comments) {
            if ($page_num > 1 || $page_num == 0) {
                // Next page and all pages
                print $this->theme_list_comments($comments, $comments_count, $page_num, $level);
            } else {
                ?>
                <ul class="cld" id="comment-<?php print $comment_parent ?>-childs">
                    <?php print $this->theme_list_comments($comments, $comments_count, $page_num, $level); ?>
                </ul>
                <?php
            }
        }
        ?>
        <?php
        $comments_template_data = ob_get_contents();
        ob_end_clean();
        die($comments_template_data);
    }

    public function ajax_simple_edit_comment($form) {

        $query_type = $form['q'];
        $cid = (int) $form['cid'];

        $ret_info = array('type' => 'error', 'msg' => 'Ошибка', 'theme' => "error");

        if ($cid && $query_type) {

            // Get comment by id
            if ($query_type == 'getdata') {
                $comment = $this->get_comment($cid);
                if ($comment) {
                    // Check if current user is the author of comment
                    $user = wp_get_current_user();
                    if ($user->ID && $user->ID == $comment->user_id) {
                        $ret_info = array('type' => 'ok', 'data' => $comment->comment_content);
                    }
                }
            } else if ($query_type == 'save') {

                $comment = $this->get_comment($cid);
                $uid = $comment->user_id;

                $is_block = false;
                $is_comment_block = false;

                if ($uid > 0) {
                    if (class_exists('UsersBan')) {
                        $usersBan = new UsersBan();

                        $isBan = $usersBan->isBan($uid);
                        if ($isBan) {
                            $banInfo = $usersBan->banInfo($uid);
                            $ubt = $banInfo->ubt;
                            if ($ubt == 1) {
                                $is_block = true;
                                $ret_info = array('type' => 'error', 'msg' => 'Error, your account is blocked', 'theme' => "warning");
                                // Ban comments 
                            } else if ($ubt == 2) {
                                $current_time = strtotime(gmdate('Y-m-d H:i:s', ( time() + ( get_option('gmt_offset') * HOUR_IN_SECONDS ))));
                                $unblock_date = $banInfo->date_added + ($banInfo->time) * 86400;
                                if ($current_time < $unblock_date) {
                                    $is_comment_block = true;
                                    $ret_info = array('type' => 'error', 'msg' => 'Error, commenting is temporarily blocked', 'theme' => "warning");
                                } else {
                                    $usersBan->unBanUser($uid);
                                }
                            }
                        }
                    }
                }

                if (!$is_block && !$is_comment_block) {

                    // TODO обработка комментария перед записью
                    $content = trim($_REQUEST['data']);
                    $comment = $this->get_comment($cid);

                    if ($content) {
                        // Проверяем, является ли текущий пользователь автором комментария
                        $user = wp_get_current_user();
                        if ($user->ID && $user->ID == $comment->user_id) {
                            if (md5($comment->comment_content) == md5($content)) {
                                $ret_info = array('type' => 'ok');
                                $comment_text = apply_filters('comment_text', $comment->comment_content, $comment, array());
                                $ret_info = array('type' => 'ok', 'data' => $comment_text);
                            } else {
                                $comment->comment_content = $content;
                                $comment->comment_content = apply_filters('pre_comment_content', $comment->comment_content);

                                $result = $this->wp_update_comment((array) $comment);
                                if ($result) {
                                    $comment = $this->get_comment($cid, false);
                                    $comment_text = $comment->comment_content;
                                    $comment_text = apply_filters('comment_text', $comment_text, $comment);
                                    $ret_info = array('type' => 'ok', 'data' => $comment_text, 'msg' => 'Comment updated successfully', 'theme' => "status");
                                } else {
                                    $ret_info = array('type' => 'error', 'msg' => 'Error, comment may not have been changed', 'theme' => "warning");
                                }
                            }
                        }
                    }
                }
            }
        }


        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            print json_encode($ret_info);
        } else {
            header("Location: " . $_SERVER["HTTP_REFERER"]);
        }
        die();
    }

    public function ajax_get_ban_info($form) {

        $cid = (int) $form['cid'];
        $ret = array();

        if ($cid > 0) {
            if (class_exists('UsersBan')) {
                $comment = $this->get_comment($cid);
                $uid = $comment->user_id;
                if ($uid > 0) {
                    $usersBan = new UsersBan();
                    $ret_info = $usersBan->banInfoHistory($uid);
                    if ($ret_info) {
                        $curr_time = strtotime(gmdate('Y-m-d H:i:s', ( time() + ( get_option('gmt_offset') * HOUR_IN_SECONDS ))));

                        // Время последнего бана
                        $ban_time = (int) $ret_info[0]->time;

                        // Дата последнего бана
                        $add_time = $ret_info[0]->date_added;
                        $last_ban_days = round(($curr_time - $add_time) / 86400, 2);

                        // Период бана
                        $ban_period = array(1 => 1, 2 => 2, 3 => 3, 7 => 4, 14 => 5, 21 => 6, 30 => 7, 365 => 8);

                        $ban_index = isset($ban_period[$ban_time]) ? $ban_period[$ban_time] : 0;
                        $ban_index += 1;

                        $is_ban = false;
                        if ($curr_time < $add_time + ($ban_time * 86400)) {
                            $is_ban = true;
                        }

                        $next_ban = 1;

                        if ($ban_index > 0) {
                            //Уменьшаем время бана, если период истёк

                            $add_time_next = $add_time + 30 * 86400;
                            while ($curr_time > $add_time_next) {
                                $add_time_next += 30 * 86400;
                                $ban_index -= 1;

                                if ($ban_index == 0) {
                                    break;
                                }
                            }
                            if ($ban_index > 0) {

                                foreach ($ban_period as $key => $value) {
                                    if ($ban_index == $value) {
                                        $next_ban = $key;
                                        break;
                                    }
                                }
                            }
                        }

                        $ret = array('next_ban' => $next_ban, 'last_ban_days' => $last_ban_days, 'isban' => $is_ban);
                    }
                }
            }
        }


        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            print json_encode($ret);
        }
        die();
    }

    function ajax_flag_cmt($form) {

        $ret_info = array('type' => 'ok', 'msg' => 'Message sent', 'theme' => "status");

        $cid = (int) $form['cid'];
        $args = $form['args'];
        $type = $form['type'];

        $categories = array(
            "Z" => 'Spam',
            "T" => 'Material does not correspond to the subject of the site',
            "P" => 'Materials of a sexual nature',
            "G" => 'Cruel or disgusting content',
            "R" => 'Expressions of hatred, insults',
            "X" => 'Harmful and dangerous actions',
            "A" => 'Violation of my rights',
        );

        $categories_comm = array(
            "R" => 'Hate speech, insults',
            "Z" => 'Spam advertising',
            "A" => 'Other',
        );

        $current_user = wp_get_current_user();
        $uid = $current_user->ID;
        if ($uid) {
            $from = $current_user->data->display_name . " | https://zgreviews.com//author/" . $current_user->data->user_nicename . " | <" . $current_user->data->user_email . ">";
        } else {
            $from = $args['usr_name'] . " <" . $args['usr_mail'] . ">";
        }
        if ($type == "cmt") {
            $this->notify_flag($cid, $from, $args['usr_cmt'], $categories_comm[$args['type']]);
        } else if ($type == "post") {
            $this->post_flag($cid, $from, $args['usr_cmt'], $categories[$args['type']]);
        } else if ($type == "ban") {
            $usersBan = new UsersBan();
            $usersBan->commentBan($cid, $uid, $args['usr_cmt'], $args['type'], $args['time']);
            $ret_info['msg'] = 'The user is blocked';
        }


        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            print json_encode($ret_info);
        } else {
            header("Location: " . $_SERVER["HTTP_REFERER"]);
        }
        die();
    }

    function ajax_spam_cmt($form) {
        $query_type = $form['q'];
        $type = $form['type'];
        $cid = (int) $form['cid'];
        $ret_info = array('type' => 'error', 'msg' => 'Ошибка', 'theme' => "error");
        if ($cid && $query_type) {

            //Проверка на права и наличие данных
            $comment = $this->get_comment($cid);
            if ($comment) {
                //$post = get_post($comment->comment_post_ID);

                if (current_user_can(8)) {
                    //Только для редакторов и админа
                    $update = true;
                } /* else if (current_user_can('author')) {

                  //Автор материала со статусом "автор".
                  $current_user = wp_get_current_user();

                  if ($post->post_author == $current_user->ID) {
                  $update = true;
                  }
                  } */
            }

            // Checks passed
            if ($update) {

                $childs = $comment->comment_childs;

                // Get a comment by id
                if ($query_type == 'getdata') {

                    $rettext = '<p>Are you sure you want to send the comment of user <b>' . $comment->comment_author . '</b> to spam?</p>';
                    $spam_text = "spam";
                    if ($type == "trash") {
                        $rettext = '<p>Are you sure you want to delete the comment of user <b>' . $comment->comment_author . '</b></p>';
                        $spam_text = "trash";
                    }

                    // Get the number of comment children
                    if ($childs) {
                        $rettext .= '<p><b>All replies</b> to the comment (<b>' . $childs . ' pcs</b>.) will also <b>be sent to ' . $spam_text . '</b>.</p>';
                    }
                    $rettext .= '<p>Only comments that violate the Community Guidelines can be sent to ' . $spam_text . '. '
                            . 'Abusing this feature is also a violation.</p>';

                    $ret_info = array('type' => 'ok', 'data' => $rettext);
                } else if ($query_type == 'do') {

                    $success = false;

                    $msg = "Comment successfully sent to ";
                    if ($childs) {
                        $msg = "Comments successfully sent to ";
                    }

                    if ($type == "trash") {
                        if (current_user_can(8)) {
                            // For editors and admins only
                            $this->wp_set_comment_status($cid, 'trash');
                            $msg .= "trash";
                            $success = true;
                        } else {
                            $ret_info = array('type' => 'error', 'msg' => 'You do not have permission to do this', 'theme' => "error");
                        }
                    } else {
                        $this->wp_set_comment_status($cid, 'spam');
                        $msg .= "spam";
                        $success = true;
                    }

                    if ($success) {
                        $ret_info = array('type' => 'ok', 'cid' => $cid, 'msg' => $msg, 'theme' => "status");
                    }
                }
                $this->comments_delta();
            }
        }



        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            print json_encode($ret_info);
        } else {
            header("Location: " . $_SERVER["HTTP_REFERER"]);
        }
        die();
    }

    public function ajax_change_cmt($form) {
        $cid = (int) $form['cid'];
        $hide = (int) $form['hide'];
        $ret = 'error';
        $msg = 'Error';
        $theme = 'error';
        if ($cid && $cid > 0) {
            if (current_user_can(8)) {
                $data = array(
                    'comment_hide' => $hide,
                    'last_upd' => $this->curr_time(),
                );
                $this->db_update($data, $this->db['comments'], (int) $cid, 'comment_ID');
                $this->comments_delta();
                $msg = 'The comment revealed';
                if ($hide == 1) {
                    $msg = 'The comment hidden';
                }
                $ret = 'ok';
                $theme = 'status';
            }
        }

        $ret_info = array('type' => $ret, 'cid' => $cid, 'msg' => $msg, 'theme' => $theme);

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            print json_encode($ret_info);
        } else {
            header("Location: " . $_SERVER["HTTP_REFERER"]);
        }
        die();
    }

    public function ajax_get_comments_page($form = array()) {

        $post_type = $form['post_type'] ? (int) $form['post_type'] : 0;
        $post_id = $form['post_id'] ? (int) $form['post_id'] : 0;
        if (!$post_id) {
            return;
        }
         
        $form_id = "ch". time();
      
        ?>
        <div id="<?php print $form_id ?>" class="comments_holder" data-pid="<?php print $post_id ?>" data-type="<?php print $post_type ?>">                                   
            <?php
            $this->commentForm($post_id, $post_type);
            print $this->comments_list($post_id, $post_type);
            ?>       
        </div>  
        <?php
        die();
    }
    
    public function ajax_respond_form($form = array()) {
            $cid = $form['cid'];
            $comment = $this->get_comment($cid);
            // Load form
            print '<div>';
            
            if ($comment){
                $this->commentForm($comment->comment_post_ID, $comment->post_type);            
            }
            if (function_exists('get_user_bar')){
                get_user_bar();
            }
            print '</div>';
            die();
    }

    public function ajax_get_three($form = array()) {
        $cid = (int) $form['cid'];
        if ($cid && $cid > 0) {
            $this->get_comments_three($cid);
        }
        die();
    }

    private function get_comments_three($cid) {
        $comment = $this->get_comment($cid);

        $current_comment = $comment;

        $parents = array();
        while ($current_comment->comment_parent) {
            $parents[$current_comment->comment_parent] = $current_comment->comment_ID;
            // Get parent
            $current_comment = $this->get_comment($current_comment->comment_parent);
        }
        $parents[0] = $current_comment->comment_ID;

        $this->theme_comments_three(0, $parents, 1);
    }

    private function theme_comments_three($parent_id, $parents, $level = 1) {
        $cid = isset($parents[$parent_id]) ? $parents[$parent_id] : 0;
        if ($cid == 0) {
            return;
        }

        $comment = $this->get_comment($cid);

        $comments_count = 1;
        if ($parent_id !== 0) {
            $level += 1;
            $comments_count = $comment->comment_childs;
        }

        print $this->theme_list_comments(array($comment), $comments_count, 1, $level);
        if ($comments_count > 0) {
            ?>
            <ul class="cld show" id="comment-<?php print $cid ?>-childs">
                <?php print $this->theme_comments_three($cid, $parents, $level) ?>
                <?php
                $top_number_childs = $this->get_comments_count(0, -1, $cid);
                if ($top_number_childs > 1) {
                    ?>
                    <li data-page="0" class="next_page cmt lvl-<?php print $level ?>"><a class="zrbtn" href="#all">Load all comments three</a></li>
                <?php } ?>
            </ul>
            <?php
        }
        if ($level == 1) {
            $top_number = $this->get_comments_count($comment->comment_post_ID, $comment->post_type, 0);
            if ($top_number > 1) {
                // Load more
                ?>
                <li data-page="0" class="next_page cmt lvl-1"><a class="zrbtn" href="#all">Load all comments</a></li>
                <?php
            }
        }
    }

    public function bulk_action($action, $ids = array()) {
        if (!$this->bulk_actions[$action]) {
            return false;
        }
        if (!is_array($ids)) {
            $ids = array($ids);
        }

        if (!$this->cm->is_admin()) {
            return false;
        }

        foreach ($ids as $id) {
            $comment_id = (int) $id;
            if ($comment_id > 0) {

                $this->wp_set_comment_status($comment_id, $action);
            }
        }

        $this->comments_delta();
    }

    /*
     * pid - post id
     * type - post type
     */
    
    public function comments($pid=0, $type=-1){
        $form_id = "ch". time();
      
        ?>
        <div id="<?php print $form_id ?>" class="comments_holder" data-pid="<?php print $pid ?>" data-type="<?php print $type ?>">
            <?php
            $this->commentForm($pid, $type);
            print $this->comments_list($pid, $type);
            ?>       
        </div><?php
    }

    public function commentForm($post_id = 0, $post_type = 0, $post_parent = 0) {

        // User
        $user = $this->get_user();
        $user_login = $user->ID > 0 ? true : false;

        // Is Author?
        $author_class = '';
        $nonce = $this->cm->wp_create_nonce('comments');

        $is_block = false;
        $is_comment_block = false;
        // Проверка блокировки пользователя
        if (class_exists('UsersBan')) {
            $usersBan = new UsersBan();
            if ($user->ID > 0) {
                $isBan = $usersBan->isBan($user->ID);
                if ($isBan) {
                    $banInfo = $usersBan->banInfo($user->ID);
                    $ubt = $banInfo->ubt;
                    if ($ubt == 1) {
                        $is_block = true;
                        // Comments ban
                    } else if ($ubt == 2) {
                        $current_time = strtotime(gmdate('Y-m-d H:i:s', ( time() + ( get_option('gmt_offset') * HOUR_IN_SECONDS ))));
                        $unblock_date = $banInfo->date_added + ($banInfo->time) * 86400;
                        if ($current_time < $unblock_date) {
                            $is_comment_block = true;
                            $ban_date = $this->curr_date($unblock_date);
                            if (class_exists('CtgHumanDate')) {
                                $ban_date = CtgHumanDate::humanDate($current_time, $unblock_date);
                            }
                            $autorlink = '/author/' . $user->user_nicename;
                            $comment_block_text = "Commenting is blocked until {$ban_date} due to violation of site rules. <a href=\"{$autorlink}/baninfo\">Violation details</a>.";
                        } else {
                            $usersBan->unBanUser($user->ID);
                        }
                    }
                }
            }
        }

        $author_role = '';
        $editor_class = '';
        if (function_exists('user_can')) {

            if (current_user_can("editor") || current_user_can("administrator")) {
                $editor_class = " editor";
            }
        }
        $usebl_class = '';
        if (class_exists('UserBl')) {
            # Black lists
            $usebl_class = ' userbl';
        }
        $author_link = '';
        $anon_class = ' anon';
        $avatar = '';
        $avSize = 64;
        $cav = $this->cm->get_cav();
        if ($user_login) {
            $anon_class = '';

            # Author link
            $author_link = '/author/' . $user->user_nicename;

            // WP avatar                
            $avatar = $cav->get_user_avatar($user->ID, $avSize);
        } else {
            $avatar = $cav->get_or_create_user_avatar(0, 0, $avSize, 'anon');
        }
        ?>
        <div id="rspnd-<?php print $post_id ?>-<?php print $post_type ?>" class="rspnd<?php print $author_class . $editor_class . $author_role . $usebl_class . $anon_class ?>">            
            <?php if ($is_comment_block && !$user_login) : ?>
                <div class="msg warning">You must be logged into post comments.</div>
            <?php elseif ($user && $is_block): ?>  
                <div class="msg error">You cannot leave comments because your account is blocked.</div>
            <?php elseif ($user && $is_comment_block): ?>  
                <div class="msg warning"><?php print $comment_block_text; ?></div>
            <?php else : ?> 
                <div id="commentform">     


                    <div class="comment-av-holder">

                        <div class="av">
                            <?php if ($user_login): ?>
                                <a title="You are logged in as <?php print $user->display_name ?>" href="<?php print $author_link ?>"><?php print $avatar ?></a>
                            <?php else: ?> 
                                <?php print $avatar ?>
                            <?php endif; ?> 
                        </div>


                        <div id="comment-container">
                            <?php
                            if (!$user_login) :
                                ?> 
                                <div class="row comment_user_name">
                                    <label class="title" for="comment_user_name"><span class="title">Your Name:</span></label>                               
                                    <input id="comment_user_name" name="comment_user_name" type="text" value="" placeholder="Anon">
                                </div>
                                <div class="row comment_user_pass">
                                    <label for="comment_user_pass"><span class="title">Password:</span></label>                               
                                    <input id="comment_user_pass" name="comment_user_pass" type="text" value="">
                                </div>
                            <?php endif; ?> 
                            <div id="comment-box" contenteditable="true" data-placeholder="Add a comment..."></div>
                            <div id="comment-buttons-holder">
                                <div id="comment-buttons">        
                                    <a href="#" id="comment-cancel-btn">Cancel</a>
                                    <a href="#" id="comment-submit-btn" class="disabled">Comment</a>
                                </div>
                            </div>
                        </div>                    
                    </div>

                    <div class="form-submit">                        
                        <input type="hidden" id="sjs" name="sjs" value="no" />
                        <input type="hidden" name="comment_nonce" value="<?php print $nonce ?>" id="comment_nonce">
                        <input type="hidden" name="comment_parent" id="comment_parent" value="<?php print $post_parent ?>">
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    public function comments_list($post_id = 0, $post_type = 0) {
        ob_start();
        $uid = $this->get_user_id();
        $comments = $this->get_post_comments_data($post_id, $post_type, 0, 1, $uid);
        $number = $this->get_comments_number($post_id, $post_type);
        ?>
        <ul class="commentlist">
            <?php
            if ($comments) {
                print $this->theme_list_comments($comments, $number);
            }
            ?>
        </ul>  
        <?php
        $comments_template_data = ob_get_contents();
        ob_end_clean();

        return $comments_template_data;
    }

    public function theme_one_comment($comment) {
        $level = 1;
        if ($comment->comment_parent) {
            $level = 2;
        }
        return $this->theme_list_comments(array($comment), 0, 1, $level);
    }

    public function theme_list_comments($comments = array(), $comments_count = 0, $page_num = 1, $level = 1) {
        $ret = '';
        if (!$comments) {
            return $ret;
        }

        // Get user
        $user = $this->get_user();
        $uid = $user->ID;

        $is_editor = false;
        if (current_user_can("editor") || current_user_can("administrator")) {
            $is_editor = true;
        }

        if ($page_num == 0) {
            $page_num = 1;
        }

        // TODO
        // post_author

        $per_page = $this->comments_per_page;
        $current_count = $page_num * $per_page;

        $next_page = 0;
        if ($current_count < $comments_count) {
            $next_page = $page_num + 1;
        }

        //User black list
        if ($uid && $uid > 0) {
            if (function_exists('userbl_init')) {
                $user_bl = new UserBl();
                $bl = $user_bl->getBlackList($uid);
                $tbl = $user_bl->getBlForTarget($uid);
            }
        }

        $usersBan = '';
        if (class_exists('UsersBan')) {
            $usersBan = new UsersBan();
        }

        foreach ($comments as $comment) {

            $is_del = false;
            $is_del_class = '';

            if ($usersBan) {
                $banType = $usersBan->banType($comments->user_id);
                if ($banType && $banType == 3) {
                    $is_del = true;
                    $is_del_class = ' is-del';
                }
            }

            $is_hidecm = false;
            $is_hidecm_class = '';
            if ($comment->comment_hide) {

                if ($is_editor) {
                    $is_hidecm_class = ' is-hide hideadm';
                } else {
                    $is_hidecm_class = ' hideall';
                    $is_hidecm = true;
                }
            }

            // Post author
            $post_a = '';
            if ($this->post_author && $this->post_author == $comment->user_id) {
                $post_a = $this->post_author_class;
            }

            // Comment author
            $com_a = '';
            if ($this->curr_uid && $this->curr_uid == $comment->user_id) {
                $com_a = $this->comm_author_class;
            }

            // Hide comment Black list
            $hide_bl_class = '';
            if (isset($bl[$comment->user_id])) {
                $hide_bl_class = ' is-hide inbl';
            }


            $ourbl_class = '';
            if (isset($tbl[$comment->user_id])) {
                $ourbl_class = ' ourbl';
            }

            $ret .= '<li id="comment-' . $comment->comment_ID . '" class="cmt lvl-' . $level . $post_a . $com_a . $hide_bl_class . $is_hidecm_class . $is_del_class . $ourbl_class . '" data-pid="' . $comment->comment_post_ID . '" data-type="' . $comment->post_type . '"  data-uid="' . $comment->user_id . '">';
            if (!$is_del && !$is_hidecm) {
                $ret .= $this->theme_comment($comment, $level);
            }
            $ret .= "</li>\n";
        }

        if ($next_page) {
            $ret .= '<li data-page="' . $next_page . '" class="next_page cmt lvl-' . $level . '">';
            $ret .= '<a class="zrbtn" href="#more">Load more comments</a>';
            $ret .= "</li>\n";
        }

        return $ret;
    }

    public function theme_comment($comment, $level = 1) {

        # Comment id
        $cid = (int) $comment->comment_ID;

        # User
        $uid = (int) $comment->user_id;

        $wpu = $this->cm->get_wpu();
        $user = $wpu->get_user_by_id($uid);

        $curr_user = $this->get_user();
        $curr_user_id = $curr_user->ID;
        $comment_author = false;
        if ($curr_user_id > 0 && $curr_user_id == $uid) {
            $comment_author = true;
        }

        $author_link = '';
        if ($user) {
            # Author link
            $author_link = '/author/' . $user->user_nicename;
        }
        // Author data
        $author = $this->cm->get_author($comment->aid);

        $avatar = '';
        if ($this->show_avatar) {
            # Avatar            
            $avSize = 64;
            if ($level != 1) {
                $avSize = 40;
            }
            $cav = $this->cm->get_cav();
            if ($uid) {
                // WP avatar                
                $avatar = $cav->get_author_avatar($author, $avSize);
            } else {
                $avatar = $cav->get_anon_avatar($comment, $avSize);
            }
        }
        # Флажок страны

        $comment_ip = $comment->comment_author_IP;
        // Country
        $country_img = '';
        $country_data = $this->cm->get_geo_flag_by_ip($comment_ip);
        if ($country_data['path']) {
            $country_name = $country_data['name'];
            $country_img = '<div class="cntr nte cflag" title="' . $country_name . '">
                                                    <div class="btn"><img src="' . $country_data['path'] . '" /></div> 
                                                    <div class="nte_show">
                                                        <div class="nte_in">
                                                            <div class="nte_cnt">
                                                                This review was posted from ' . $country_name . ' or from a VPN in ' . $country_name . '.                                                                
                                                            </div>
                                                        </div>
                                                    </div>
                             </div>';
        }

        $comment_votes = $this->cm->get_comment_votes();
        $comment_data = $comment_votes->vote_count($cid);

        $votes = $comment_data['plus'] + $comment_data['minus'];
        $score = $comment_data['vote_result'];
        $votes_plus = $comment_data['plus'];
        $votes_minus = $comment_data['minus'];

        $score_class = "sz";

        if ($score > 0) {
            $score_class = "sp";
        } else if ($score < 0) {
            $score_class = "sm";
        }

        if ($votes > 0) {
            $score_class = "ne " . $score_class;
        }
        $respond = '#respond-' . $cid;
        $answer_link = $respond;
        $author_comment_link = '#comment-' . $cid;

        if ($comment->comment_parent) {
            $parent = $this->get_comment($comment->comment_parent);
            $parent_link = '#comment-' . $comment->comment_parent;
        }

        $comment_text = $comment->comment_content;
        if (function_exists('apply_filters')) {
            // WP filters
            $comment_text = apply_filters('comment_text', $comment->comment_content, $comment);
        }
        
        $cc = $this->get_cc();        
        $clear_data = $cc->validate_content($comment_text);
        $comment_text = $clear_data['content'];

        $curr_time = $this->curr_time();
        $comment_time = $this->humanDate($curr_time, strtotime($comment->comment_date));

        // Get post data
        $post_title = '';
        $post_link = '';
        $is_recent = $this->is_recent;
        $is_search = $this->is_search;

        if ($is_recent || $is_search) {
            // TODO get post title and link
            $post_data = $this->get_comment_post_data($comment->comment_post_ID, $comment->post_type);
            $post_title = $post_data->title;
            $post_link = $post_data->link;
            $parent_link = $post_link . $parent_link;
            $author_comment_link = $post_link . $author_comment_link;
            // $answer_link = $post_link . $answer_link;
        }

        // Answers
        $answers = '';
        if ($comment->comment_childs > 0) {
            $answers = $comment->comment_childs . " Answer";
            if ($comment->comment_childs > 1) {
                $answers .= "s";
            }
        }

        $editor_check = '';
        if ($is_search && $this->cm->is_admin()) {
            $editor_check = '<span class="check-column"><input type="checkbox" name="bulk-' . $cid . '"></span> ';
        }

        ob_start();
        if ($this->show_avatar) {
            ?>
            <div class="av">
                <?php if ($author_link): ?>
                    <a href="<?php print $author_link ?>"><?php print $avatar ?></a>
                <?php else: ?>
                    <?php print $avatar ?>
                <?php endif ?>
            </div>
        <?php } ?>
        <div class="ch">
            <div class="tp">                                                 
                <?php print $editor_check ?>
                <?php
                // Flag
                if ($country_img) {
                    print $country_img;
                }
                // Author
                ?> 
                <span class="usrs">
                    <?php if ($author_link): ?>
                        <a class="athr" href="<?php print $author_link ?>"><?php print $comment->comment_author ?></a>
                    <?php else: ?>
                        <span class="athr"><?php print $comment->comment_author ?></span>
                    <?php endif ?>
                    <?php
                    // Parent
                    if ($comment->comment_parent) {
                        ?>
                        <?php if ($is_recent || $is_search) { ?>
                            &rarr; <a class="prnt ext" href="<?php print $parent_link ?>"><?php print $parent->comment_author ?> <i class="icon icon-link-ext"></i></a>
                        <?php } else { ?>
                            &rarr; <a class="prnt" href="<?php print $parent_link ?>"><?php print $parent->comment_author ?></a>
                        <?php } ?>
                    <?php } ?>
                </span> <?php
                // Date
                ?>
                <a class="dt" href="<?php print $author_comment_link ?>"><?php print $comment_time ?></a>
                <?php if ($is_recent || $is_search) { ?>
                    <span class="pl">to post <a href="<?php print $post_link ?>"><?php print $post_title ?></a></span> 
                <?php } ?>
                <?php
                // Menu            
                ?>

                <div class="nte cmtm">                    
                    <div class="btn"><i class="icon icon-ellipsis-vert"></i></div> 
                    <div class="nte_show dwn">
                        <div class="nte_in"><div class="nte_cnt"></div></div>
                    </div>
                </div>

            </div>  
            <?php if ($comment->comment_approved == '0') : ?>
                <em class="noapprvd">
                    <?php
                    $mod_text = 'This comment is awaiting moderation.';
                    if ($comment_author) {
                        $mod_text = 'Thank you! Your comment has been sent for moderation.';
                    }
                    print $mod_text;
                    ?>                
                </em> 
            <?php elseif ($comment->comment_approved == 'spam') : ?>
                <em class="spamcmt">This comment has been marked as spam.</em> 
            <?php elseif ($comment->comment_approved == 'trash') : ?>
                <em class="spamcmt">This comment is in the trash.</em> 
            <?php endif; ?> 
            <div class="t">
                <?php print $comment_text; ?>
            </div>             
            <div class="bnm">                
                <div class="vtb">
                    <div class="vt">
                        <div>
                            <a class="up" href="#" title="Agree"><b class="upi icon-thumbs-up"></b><span class="cnt"><i><?php if ($votes_plus) print $votes_plus ?></i></span></a> 
                            <a class="dw" href="#" title="Disagree"><b class="dwi icon-thumbs-down"></b><span class="cnt"><i><?php if ($votes_minus) print $votes_minus ?></i></span></a>
                        </div>
                        <div class="vc"></div>
                    </div>
                    <div class="ttl <?php print $score_class ?>"><?php
                        if ($votes > 0) {
                            /* if ($score > 0) {
                              print "+";
                              } */
                            print $score;
                        }
                        ?>
                    </div>
                </div>
                <div id="respond-<?php print $cid ?>" class="rsd">                    
                    <?php /*
                      if ($is_recent || $is_search) { ?>
                      <?php if ($comment->comment_childs > 0) { ?>
                      <?php print $comment->comment_childs ?> <a class="ext" href="<?php print $answer_link ?>">
                      <?php print $answers ?> <i class="icon icon-link-ext"></i></a>
                      <?php } else { ?>
                      <a class="ext" href="<?php print $answer_link ?>">Reply <i class="icon icon-link-ext"></i></a>
                      <?php } ?>
                      <?php } else {
                     */ ?>                    
                    <a href="<?php print $answer_link ?>">Reply</a>
                    <?php /* }
                     */
                    ?>
                </div>
            </div>
            <?php if ($comment->comment_childs > 0): ?>
                <div class="bansw">
                    <a href="#"><i class="icon-down-open"></i><i class="icon-up-open"></i> <?php print $answers ?></a>
                </div>
            <?php endif ?>
        </div> 
        <?php
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }

    /*
     * DB functions
     */

    public function get_post_comments_data($post_id = 0, $post_type = -1, $comment_parent = -1, $page = 1, $uid = 0, $author_uid = 0) {


        $start = 0;
        $size = $this->comments_per_page;
        if ($page > 1) {
            $start = ($page - 1) * $size;
        }


        $user_sql = '';
        if ($author_uid > 0) {
            // User comments
            $user_sql = " AND user_id = " . (int) $author_uid;
        }

        $and_parent = '';
        if ($comment_parent != -1) {
            $and_parent = ' AND comment_parent = ' . (int) $comment_parent;
        }

        $and_post_id = '';
        if ($post_id > 0) {
            $and_post_id = " AND comment_post_ID = " . (int) $post_id;
        }

        $and_post_type = '';
        if ($post_type != -1) {
            $and_post_type = " AND post_type = " . (int) $post_type;
        }

        $comment_approved = "comment_approved = '1'";
        if ($uid > 0) {
            $comment_approved = " (comment_approved = '1' OR (user_id = " . (int) $uid . " AND comment_approved = '0'))";
        }
        // Select comments on database.
        $sql = "SELECT * FROM {$this->db['comments']} "
                . " WHERE " . $comment_approved
                . $and_post_id
                . $and_post_type
                . $and_parent
                . $user_sql
                . " ORDER BY comment_ID DESC "
                . " LIMIT " . $start . "," . $size;

        $comments = $this->db_results($sql);

        return $comments;
    }

    public function get_comment($id, $cache = true) {
        if ($cache) {
            static $dict;
            if (is_null($dict)) {
                $dict = array();
            }

            if (isset($dict[$id])) {
                return $dict[$id];
            }
        }
        $sql = "SELECT * FROM {$this->db['comments']} WHERE comment_ID=" . (int) $id;
        $result = $this->db_fetch_row($sql);

        if ($cache) {
            $dict[$id] = $result;
        }
        return $result;
    }

    public function get_comments_count($post_id = 0, $post_type = -1, $comment_parent = -1, $uid = 0, $author_uid = 0) {
        $user_sql = '';
        if ($author_uid > 0) {
            // User comments
            $user_sql = sprintf(" AND user_id = %d ", $author_uid);
        }
        $and_parent = '';
        if ($comment_parent != -1) {
            $and_parent = ' AND comment_parent=' . (int) $comment_parent;
        }

        $and_post_id = '';
        if ($post_id > 0) {
            $and_post_id = " AND comment_post_ID = " . (int) $post_id;
        }

        $and_post_type = '';
        if ($post_type != -1) {
            $and_post_type = " AND post_type = " . (int) $post_type;
        }

        $comment_approved = "comment_approved = '1'";
        if ($uid > 0) {
            $comment_approved = " (comment_approved = '1' OR (user_id = " . (int) $uid . " AND comment_approved = '0'))";
        }

        $sql = "SELECT COUNT(*) FROM {$this->db['comments']} "
                . " WHERE " . $comment_approved
                . $and_post_id
                . $and_post_type
                . $and_parent
                . $user_sql;
        $result = $this->db_get_var($sql);

        return $result;
    }

    /*
     * Comment meta
     */

    public function get_comments_number($post_id = 0, $post_type = 0, $cache = true) {
        $id = $post_id . "-" . $post_type;
        if ($cache) {
            static $dict;
            if (is_null($dict)) {
                $dict = array();
            }

            if (isset($dict[$id])) {
                return $dict[$id];
            }
        }

        $sql = sprintf("SELECT comments_count FROM {$this->db['comments_num']} WHERE comment_post_ID=%d AND post_type=%d", $post_id, $post_type);

        $result = $this->db_get_var($sql);

        if ($cache) {
            $dict[$id] = $result;
        }
        return $result;
    }

    public function update_comment_count($comment_post_ID = 0, $post_type = 0) {
        $count = $this->get_comments_count($comment_post_ID, $post_type);
        $sql = sprintf("SELECT id FROM {$this->db['comments_num']} WHERE comment_post_ID=%d AND post_type=%d", $comment_post_ID, $post_type);

        $id_exist = $this->db_get_var($sql);

        $data = array(
            'comments_count' => $count,
            'last_upd' => $this->curr_time(),
        );
        if ($id_exist) {
            // Update

            $this->db_update($data, $this->db['comments_num'], $id_exist);
        } else {
            // Insert
            $data['comment_post_ID'] = $comment_post_ID;
            $data['post_type'] = $post_type;

            $id_exist = $this->db_insert($data, $this->db['comments_num']);
        }
        return $count;
    }

    public function change_all_parrents_counter($comment_parent = 0, $change = 1) {
        if ($comment_parent > 0) {
            $sql = "SELECT comment_parent, comment_childs FROM {$this->db['comments']} WHERE comment_ID =" . (int) $comment_parent;
            $result = $this->db_fetch_row($sql);

            $count = $result->comment_childs + $change;
            if ($count < 0) {
                $count = 0;
            }
            $data = array(
                'comment_childs' => $count,
                'last_upd' => $this->curr_time()
            );

            $this->db_update($data, $this->db['comments'], (int) $comment_parent, 'comment_ID');
            if ($result->comment_parent) {
                // Update all parents recursive
                $this->change_all_parrents_counter($result->comment_parent, $change);
            }
        }
    }

    public function update_parent_count($comment_parent = 0) {
        // Unused
        $sql = "SELECT COUNT(*) FROM {$this->db['comments']} "
                . " WHERE comment_approved = '1' "
                . " AND comment_parent = " . (int) $comment_parent;
        $count = (int) $this->db_get_var($sql);

        $data = array(
            'comment_childs' => $count,
            'last_upd' => $this->curr_time(),
        );

        $this->db_update($data, $this->db['comments'], (int) $comment_parent, 'comment_ID');
    }

    public function add_author_key($aid) {
        $unic_id = $this->cm->unic_id();
        $aid_db = $this->get_author_by_key($unic_id);
        if ($aid != $aid_db) {
            //new key
            $data = array(
                'aid' => $aid,
                'name' => $unic_id
            );

            $this->db_insert($data, $this->db['author_key']);
        }
    }

    public function get_author_by_key($unic_id) {
        $sql = sprintf("SELECT aid FROM {$this->db['author_key']} WHERE name = '%s'", $this->escape($unic_id));
        $result = $this->db_get_var($sql);
        return $result;
    }

    public function change_comments_childs($comment_ID, $status = '') {
        $sql = sprintf("SELECT comment_ID FROM {$this->db['comments']} WHERE comment_parent =%d AND comment_approved!='%s'", $comment_ID, $status);
        $results = $this->db_results($sql);
        if ($results) {
            foreach ($results as $comment) {
                $cid = $comment->comment_ID;
                $data = array(
                    'comment_approved' => $status,
                    'last_upd' => $this->curr_time(),
                );
                $this->db_update($data, $this->db['comments'], (int) $cid, 'comment_ID');
                $this->change_comments_childs($cid, $status);
            }
        }
    }
    
    public function comments_home(){
        $this->comments_per_page = 10;
        $data = $this->get_post_comments_data(0, -1);
        $this->is_recent = true;
        $comments_count = 0;
        if ($data){
            $this->renderComments($data, $comments_count);
        }
    }

    /*
     * User CP Widgets
     */

    function commentsWidget($uid, $load_more = false) {
        # Виджет комментариев на странице пользователя

        if (!$load_more) {
            // no avatar for recent
            $this->show_avatar = false;
            $this->comments_per_page = 5;
        }

        $data = $this->get_post_comments_data(0, -1, -1, 1, $uid, $uid);
        $this->is_recent = true;

        $content = '';
        if ($data) {
            $comments_count = 0;
            if ($load_more) {
                $comments_count = $this->get_comments_count(0, -1, -1, $uid, $uid);
            }
            ob_start();
            $this->renderComments($data, $comments_count);
            $content = ob_get_contents();
            ob_end_clean();
        }
        return $content;
    }

    function renderComments($data, $comments_count = 0) {
        if ($data) {
            $form_id = "ch". time();
            ?>
            <div id="<?php print $form_id ?>" class="comments_holder" data-pid="0" data-type="-1">       
                <?php $this->commentForm(); ?>
                <ul class="commentlist">
                    <?php print $this->theme_list_comments($data, $comments_count); ?>
                </ul>               
            </div>
            <?php
        }
    }

    public function get_comment_post_data($post_ID = 0, $post_type = 0) {

        $ret = new stdClass();
        if ($post_type == 0) {
            // Critics
            $critic = $this->cm->get_post_and_author($post_ID);
            $permalink = $critic->link;
            if (!$permalink) {
                // Create local permalink
                $permalink = $this->cm->get_critic_url($critic);
            }

            $ret->title = $critic->title;
            $ret->link = $permalink;
        } else if ($post_type == 1) {
            // Wp page
            $post = get_post($post_ID);
            $ret->title = get_the_title($post);
            $ret->link = get_permalink($post);
        } else if ($post_type == 2 || $post_type == 3) {
            // Movies and Games
            $ma = $this->cm->get_ma();
            $movie = $ma->get_post($post_ID);
            $ret->title = $movie->title;
            $ret->link = $ma->get_movie_link($movie);
        }else if ($post_type == 4) {
            // TODO Actors data
            $ma = $this->cm->get_ma();
            $actor = $ma->get_actor_by_id($post_ID);           
                       
            $ret->title = $actor->name;
            $ret->link = '/actor/'.$post_ID;
        }

        return $ret;
    }

    /*
     * Search
     */

    public function theme_seach_comments($data) {
        $comments_list = $data['comments']['list'];
        $comments = array();
        if ($comments_list) {
            foreach ($comments_list as $comment) {

                $vc = $comment;
                $vc->comment_date = $this->wp_date_from_int($comment->comment_date);
                $vc->comment_date_gmt = $this->wp_date_from_int($comment->comment_date);
                $vc->comment_ID = $comment->comment_id;
                $vc->comment_post_ID = $comment->comment_post_id;
                $vc->comment_author_IP = $comment->comment_author_ip;

                $comments[] = $vc;
            }
        }

        $this->is_search = true;

        ob_start();
        $this->renderComments($comments);
        $content = ob_get_contents();
        ob_end_clean();

        return $content;
    }

    public function wp_date_from_int($date) {
        return date('Y-m-d H:i:s', $date);
    }

    public function comments_delta() {
        $data = array(
            'cmd' => 'comments_delta',
        );

        if (!defined('SYNC_HOST')) {
            return false;
        }
        $host = SYNC_HOST;
        return $this->cm->post($data, $host);
    }

    public function get_comments_notify_data($comment_ids) {
        $ret = array();
        if (sizeof($comment_ids) > 0) {
            $ids = implode(', ', $comment_ids);
            $sql = sprintf("SELECT * FROM {$this->db['comments']} WHERE comment_ID IN (%s)", $ids);

            $results = $this->db_results($sql);
            if ($results) {
                foreach ($results as $item) {
                    $post_data = $this->get_comment_post_data($item->comment_post_ID, $item->post_type);
                    $item->post_title = $post_data->title;
                    $item->post_link = $post_data->link;
                    $ret[$item->comment_ID] = $item;
                }
            }
        }
        return $ret;
    }

    /*
     * Wp functions
     */

    /**
     * Sets the status of a comment.
     *
     * The {@see 'wp_set_comment_status'} action is called after the comment is handled.
     * If the comment status is not in the list, then false is returned.
     *
     * @since 1.0.0
     *
     * @global wpdb $wpdb WordPress database abstraction object.
     *
     * @param int|WP_Comment $comment_id     Comment ID or WP_Comment object.
     * @param string         $comment_status New comment status, either 'hold', 'approve', 'spam', or 'trash'.
     * @param bool           $wp_error       Whether to return a WP_Error object if there is a failure. Default false.
     * @return bool|WP_Error True on success, false or WP_Error on failure.
     */
    function wp_set_comment_status($comment_id, $comment_status, $wp_error = false) {


        switch ($comment_status) {
            case 'hold':
            case '0':
                $status = '0';
                break;
            case 'approve':
            case '1':
                $status = '1';
                add_action('wp_set_comment_status', 'wp_new_comment_notify_postauthor');
                break;
            case 'spam':
                $status = 'spam';
                break;
            case 'trash':
                $status = 'trash';
                break;
            default:
                return false;
        }

        $comment_old = clone $this->get_comment($comment_id);

        $data = array(
            'comment_approved' => $status,
            'last_upd' => $this->curr_time(),
        );
        $this->db_update($data, $this->db['comments'], (int) $comment_old->comment_ID, 'comment_ID');

        $comment = $this->get_comment($comment_old->comment_ID, false);

        /**
         * Fires immediately after transitioning a comment's status from one to another in the database
         * and removing the comment from the object cache, but prior to all status transition hooks.
         *
         * @since 1.5.0
         *
         * @param string $comment_id     Comment ID as a numeric string.
         * @param string $comment_status Current comment status. Possible values include
         *                               'hold', '0', 'approve', '1', 'spam', and 'trash'.
         */
        do_action('wp_set_comment_status', $comment->comment_ID, $comment_status);

        wp_transition_comment_status($comment_status, $comment_old->comment_approved, $comment);

        $comment = $this->get_comment($comment->comment_ID, false);

        // Change childs status
        $this->change_comments_childs($comment->comment_ID, $status);

        $old_status = $comment_old->comment_approved;
        $new_status = $comment->comment_approved;

        if ($comment->comment_parent > 0) {
            // Update comment count
            if ($new_status != $old_status) {
                if ($new_status == 1) {
                    // Publish
                    $change = 1;
                    $this->change_all_parrents_counter($comment->comment_parent, $change);
                } else if ($old_status == 1) {
                    // No publish
                    $change = -1;
                    $this->change_all_parrents_counter($comment->comment_parent, $change);
                }
            }
        }

        // Update comment count
        $this->update_comment_count($comment->comment_post_ID, $comment->post_type);

        return true;
    }

    /**
     * Adds a new comment to the database.
     *
     * Filters new comment to ensure that the fields are sanitized and valid before
     * inserting comment into database. Calls {@see 'comment_post'} action with comment ID
     * and whether comment is approved by WordPress. Also has {@see 'preprocess_comment'}
     * filter for processing the comment data before the function handles it.
     *
     * We use `REMOTE_ADDR` here directly. If you are behind a proxy, you should ensure
     * that it is properly set, such as in wp-config.php, for your environment.
     *
     * See {@link https://core.trac.wordpress.org/ticket/9235}
     *
     * @since 1.5.0
     * @since 4.3.0 Introduced the `comment_agent` and `comment_author_IP` arguments.
     * @since 4.7.0 The `$avoid_die` parameter was added, allowing the function
     *              to return a WP_Error object instead of dying.
     * @since 5.5.0 The `$avoid_die` parameter was renamed to `$wp_error`.
     * @since 5.5.0 Introduced the `comment_type` argument.
     *
     * @see wp_insert_comment()
     * @global wpdb $wpdb WordPress database abstraction object.
     *
     * @param array $commentdata {
     *     Comment data.
     *
     *     @type string $comment_author       The name of the comment author.
     *     @type string $comment_author_email The comment author email address.
     *     @type string $comment_author_url   The comment author URL.
     *     @type string $comment_content      The content of the comment.
     *     @type string $comment_date         The date the comment was submitted. Default is the current time.
     *     @type string $comment_date_gmt     The date the comment was submitted in the GMT timezone.
     *                                        Default is `$comment_date` in the GMT timezone.
     *     @type string $comment_type         Comment type. Default 'comment'.
     *     @type int    $comment_parent       The ID of this comment's parent, if any. Default 0.
     *     @type int    $comment_post_ID      The ID of the post that relates to the comment.
     *     @type int    $user_id              The ID of the user who submitted the comment. Default 0.
     *     @type int    $user_ID              Kept for backward-compatibility. Use `$user_id` instead.
     *     @type string $comment_agent        Comment author user agent. Default is the value of 'HTTP_USER_AGENT'
     *                                        in the `$_SERVER` superglobal sent in the original request.
     *     @type string $comment_author_IP    Comment author IP address in IPv4 format. Default is the value of
     *                                        'REMOTE_ADDR' in the `$_SERVER` superglobal sent in the original request.
     * }
     * @param bool  $wp_error Should errors be returned as WP_Error objects instead of
     *                        executing wp_die()? Default false.
     * @return int|false|WP_Error The ID of the comment on success, false or WP_Error on failure.
     */
    function wp_new_comment($commentdata, $wp_error = false) {
        global $wpdb;

        /*
         * Normalize `user_ID` to `user_id`, but pass the old key
         * to the `preprocess_comment` filter for backward compatibility.
         */
        if (isset($commentdata['user_ID'])) {
            $commentdata['user_ID'] = (int) $commentdata['user_ID'];
            $commentdata['user_id'] = $commentdata['user_ID'];
        } elseif (isset($commentdata['user_id'])) {
            $commentdata['user_id'] = (int) $commentdata['user_id'];
            $commentdata['user_ID'] = $commentdata['user_id'];
        }

        $prefiltered_user_id = ( isset($commentdata['user_id']) ) ? (int) $commentdata['user_id'] : 0;

        if (!isset($commentdata['comment_author_IP'])) {
            $commentdata['comment_author_IP'] = $_SERVER['REMOTE_ADDR'];
        }

        if (!isset($commentdata['comment_agent'])) {
            $commentdata['comment_agent'] = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        }

        /**
         * Filters a comment's data before it is sanitized and inserted into the database.
         *
         * @since 1.5.0
         * @since 5.6.0 Comment data includes the `comment_agent` and `comment_author_IP` values.
         *
         * @param array $commentdata Comment data.
         */
        $commentdata = apply_filters('preprocess_comment', $commentdata);

        $commentdata['comment_post_ID'] = (int) $commentdata['comment_post_ID'];

        // Normalize `user_ID` to `user_id` again, after the filter.
        if (isset($commentdata['user_ID']) && $prefiltered_user_id !== (int) $commentdata['user_ID']) {
            $commentdata['user_ID'] = (int) $commentdata['user_ID'];
            $commentdata['user_id'] = $commentdata['user_ID'];
        } elseif (isset($commentdata['user_id'])) {
            $commentdata['user_id'] = (int) $commentdata['user_id'];
            $commentdata['user_ID'] = $commentdata['user_id'];
        }

        $commentdata['comment_parent'] = isset($commentdata['comment_parent']) ? absint($commentdata['comment_parent']) : 0;

        $parent_status = ( $commentdata['comment_parent'] > 0 ) ? $this->wp_get_comment_status($commentdata['comment_parent']) : '';

        $commentdata['comment_parent'] = ( 'approved' === $parent_status || 'unapproved' === $parent_status ) ? $commentdata['comment_parent'] : 0;

        $commentdata['comment_author_IP'] = preg_replace('/[^0-9a-fA-F:., ]/', '', $commentdata['comment_author_IP']);

        $commentdata['comment_agent'] = substr($commentdata['comment_agent'], 0, 254);

        if (empty($commentdata['comment_date'])) {
            $commentdata['comment_date'] = current_time('mysql');
        }

        if (empty($commentdata['comment_date_gmt'])) {
            $commentdata['comment_date_gmt'] = current_time('mysql', 1);
        }

        if (empty($commentdata['comment_type'])) {
            $commentdata['comment_type'] = 'comment';
        }

        $commentdata = wp_filter_comment($commentdata);

        $commentdata['comment_approved'] = wp_allow_comment($commentdata, $wp_error);

        if (is_wp_error($commentdata['comment_approved'])) {
            return $commentdata['comment_approved'];
        }

        $comment_id = $this->wp_insert_comment($commentdata);

        if (!$comment_id) {
            $fields = array('comment_author', 'comment_author_email', 'comment_author_url', 'comment_content');

            foreach ($fields as $field) {
                if (isset($commentdata[$field])) {
                    $commentdata[$field] = $wpdb->strip_invalid_text_for_column($wpdb->comments, $field, $commentdata[$field]);
                }
            }

            $commentdata = wp_filter_comment($commentdata);

            $commentdata['comment_approved'] = wp_allow_comment($commentdata, $wp_error);
            if (is_wp_error($commentdata['comment_approved'])) {
                return $commentdata['comment_approved'];
            }

            $comment_id = $this->wp_insert_comment($commentdata);
            if (!$comment_id) {
                return false;
            }
        }

        /**
         * Fires immediately after a comment is inserted into the database.
         *
         * @since 1.2.0
         * @since 4.5.0 The `$commentdata` parameter was added.
         *
         * @param int        $comment_id       The comment ID.
         * @param int|string $comment_approved 1 if the comment is approved, 0 if not, 'spam' if spam.
         * @param array      $commentdata      Comment data.
         */
        do_action('comment_post', $comment_id, $commentdata['comment_approved'], $commentdata);

        return $comment_id;
    }

    /**
     * Retrieves the status of a comment by comment ID.
     *
     * @since 1.0.0
     *
     * @param int|WP_Comment $comment_id Comment ID or WP_Comment object
     * @return string|false Status might be 'trash', 'approved', 'unapproved', 'spam'. False on failure.
     */
    function wp_get_comment_status($comment_id) {
        $comment = $this->get_comment($comment_id);
        if (!$comment) {
            return false;
        }

        $approved = $comment->comment_approved;

        if (null == $approved) {
            return false;
        } elseif ('1' == $approved) {
            return 'approved';
        } elseif ('0' == $approved) {
            return 'unapproved';
        } elseif ('spam' === $approved) {
            return 'spam';
        } elseif ('trash' === $approved) {
            return 'trash';
        } else {
            return false;
        }
    }

    /**
     * Inserts a comment into the database.
     *
     * @since 2.0.0
     * @since 4.4.0 Introduced the `$comment_meta` argument.
     * @since 5.5.0 Default value for `$comment_type` argument changed to `comment`.
     *
     * @global wpdb $wpdb WordPress database abstraction object.
     *
     * @param array $commentdata {
     *     Array of arguments for inserting a new comment.
     *
     *     @type string     $comment_agent        The HTTP user agent of the `$comment_author` when
     *                                            the comment was submitted. Default empty.
     *     @type int|string $comment_approved     Whether the comment has been approved. Default 1.
     *     @type string     $comment_author       The name of the author of the comment. Default empty.
     *     @type string     $comment_author_email The email address of the `$comment_author`. Default empty.
     *     @type string     $comment_author_IP    The IP address of the `$comment_author`. Default empty.
     *     @type string     $comment_author_url   The URL address of the `$comment_author`. Default empty.
     *     @type string     $comment_content      The content of the comment. Default empty.
     *     @type string     $comment_date         The date the comment was submitted. To set the date
     *                                            manually, `$comment_date_gmt` must also be specified.
     *                                            Default is the current time.
     *     @type string     $comment_date_gmt     The date the comment was submitted in the GMT timezone.
     *                                            Default is `$comment_date` in the site's GMT timezone.
     *     @type int        $comment_karma        The karma of the comment. Default 0.
     *     @type int        $comment_parent       ID of this comment's parent, if any. Default 0.
     *     @type int        $comment_post_ID      ID of the post that relates to the comment, if any.
     *                                            Default 0.
     *     @type string     $comment_type         Comment type. Default 'comment'.
     *     @type array      $comment_meta         Optional. Array of key/value pairs to be stored in commentmeta for the
     *                                            new comment.
     *     @type int        $user_id              ID of the user who submitted the comment. Default 0.
     * }
     * @return int|false The new comment's ID on success, false on failure.
     */
    function wp_insert_comment($commentdata) {

        $data = wp_unslash($commentdata);

        $comment_author = !isset($data['comment_author']) ? '' : $data['comment_author'];
        $comment_author_email = !isset($data['comment_author_email']) ? '' : $data['comment_author_email'];
        $comment_author_url = !isset($data['comment_author_url']) ? '' : $data['comment_author_url'];
        $comment_author_ip = !isset($data['comment_author_IP']) ? '' : $data['comment_author_IP'];

        $comment_date = !isset($data['comment_date']) ? current_time('mysql') : $data['comment_date'];
        $comment_date_gmt = !isset($data['comment_date_gmt']) ? get_gmt_from_date($comment_date) : $data['comment_date_gmt'];

        $comment_post_id = !isset($data['comment_post_ID']) ? 0 : $data['comment_post_ID'];
        $comment_content = !isset($data['comment_content']) ? '' : $data['comment_content'];
        $comment_karma = !isset($data['comment_karma']) ? 0 : $data['comment_karma'];
        $comment_approved = !isset($data['comment_approved']) ? 1 : $data['comment_approved'];
        $comment_agent = !isset($data['comment_agent']) ? '' : $data['comment_agent'];
        $comment_type = empty($data['comment_type']) ? 'comment' : $data['comment_type'];
        $comment_parent = !isset($data['comment_parent']) ? 0 : $data['comment_parent'];

        $user_id = !isset($data['user_id']) ? 0 : $data['user_id'];

        // Custom fields
        $user_sjs = !isset($data['user_sjs']) ? 0 : $data['user_sjs'];
        $post_type = !isset($data['post_type']) ? 0 : $data['post_type'];
        $aid = !isset($data['aid']) ? 0 : $data['aid'];

        $compacted = array(
            'comment_post_ID' => $comment_post_id,
            'comment_author_IP' => $comment_author_ip,
        );

        $compacted += compact(
                'comment_author',
                'comment_author_email',
                'comment_author_url',
                'comment_date',
                'comment_date_gmt',
                'comment_content',
                'comment_karma',
                'comment_approved',
                'comment_agent',
                'comment_type',
                'comment_parent',
                'user_id',
                'user_sjs',
                'post_type',
                'aid',
        );

        $compacted['last_upd'] = $this->curr_time();

        $id = $this->db_insert($compacted, $this->db['comments']);
        if (!$id) {
            return 0;
        }

        $comment = $this->get_comment($id);

        /**
         * Fires immediately after a comment is inserted into the database.
         *
         * @since 2.8.0
         *
         * @param int        $id      The comment ID.
         * @param WP_Comment $comment Comment object.
         */
        do_action('wp_insert_comment', $id, $comment);

        // Get comment after hooks
        $comment = $this->get_comment($id, false);

        if ($comment->comment_parent > 0) {

            // Update parent count
            if ($comment->comment_approved == 1) {
                $change = 1;
                $this->change_all_parrents_counter($comment->comment_parent, $change);
            }
        }

        // Update comment count
        $this->update_comment_count($comment->comment_post_ID, $comment->post_type);

        $this->comments_delta();

        return $id;
    }

    /**
     * Updates an existing comment in the database.
     *
     * Filters the comment and makes sure certain fields are valid before updating.
     *
     * @since 2.0.0
     * @since 4.9.0 Add updating comment meta during comment update.
     * @since 5.5.0 The `$wp_error` parameter was added.
     * @since 5.5.0 The return values for an invalid comment or post ID
     *              were changed to false instead of 0.
     *
     * @global wpdb $wpdb WordPress database abstraction object.
     *
     * @param array $commentarr Contains information on the comment.
     * @param bool  $wp_error   Optional. Whether to return a WP_Error on failure. Default false.
     * @return int|false|WP_Error The value 1 if the comment was updated, 0 if not updated.
     *                            False or a WP_Error object on failure.
     */
    function wp_update_comment($commentarr, $wp_error = false) {

        // First, get all of the original fields.
        $comment = (array) $this->get_comment($commentarr['comment_ID']);

        if (empty($comment)) {
            if ($wp_error) {
                return new WP_Error('invalid_comment_id', __('Invalid comment ID.'));
            } else {
                return false;
            }
        }

        // Make sure that the comment post ID is valid (if specified).
        /* if (!empty($commentarr['comment_post_ID']) && !get_post($commentarr['comment_post_ID'])) {
          if ($wp_error) {
          return new WP_Error('invalid_post_id', __('Invalid post ID.'));
          } else {
          return false;
          }
          } */

        $filter_comment = false;
        if (!has_filter('pre_comment_content', 'wp_filter_kses')) {
            $filter_comment = !user_can(isset($comment['user_id']) ? $comment['user_id'] : 0, 'unfiltered_html');
        }

        if ($filter_comment) {
            add_filter('pre_comment_content', 'wp_filter_kses');
        }

        // Escape data pulled from DB.
        $comment = wp_slash($comment);

        $old_status = $comment['comment_approved'];

        // Merge old and new fields with new fields overwriting old ones.
        $commentarr = array_merge($comment, $commentarr);

        $commentarr = wp_filter_comment($commentarr);

        if ($filter_comment) {
            remove_filter('pre_comment_content', 'wp_filter_kses');
        }

        // Now extract the merged array.
        $data = wp_unslash($commentarr);

        /**
         * Filters the comment content before it is updated in the database.
         *
         * @since 1.5.0
         *
         * @param string $comment_content The comment data.
         */
        $data['comment_content'] = apply_filters('comment_save_pre', $data['comment_content']);

        $data['comment_date_gmt'] = get_gmt_from_date($data['comment_date']);

        if (!isset($data['comment_approved'])) {
            $data['comment_approved'] = 1;
        } elseif ('hold' === $data['comment_approved']) {
            $data['comment_approved'] = 0;
        } elseif ('approve' === $data['comment_approved']) {
            $data['comment_approved'] = 1;
        }

        $comment_id = $data['comment_ID'];
        $comment_post_id = $data['comment_post_ID'];

        /**
         * Filters the comment data immediately before it is updated in the database.
         *
         * Note: data being passed to the filter is already unslashed.
         *
         * @since 4.7.0
         * @since 5.5.0 Returning a WP_Error value from the filter will short-circuit comment update
         *              and allow skipping further processing.
         *
         * @param array|WP_Error $data       The new, processed comment data, or WP_Error.
         * @param array          $comment    The old, unslashed comment data.
         * @param array          $commentarr The new, raw comment data.
         */
        $data = apply_filters('wp_update_comment_data', $data, $comment, $commentarr);

        // Do not carry on on failure.
        if (is_wp_error($data)) {
            if ($wp_error) {
                return $data;
            } else {
                return false;
            }
        }

        $keys = array(
            'comment_post_ID',
            'comment_author',
            'comment_author_email',
            'comment_author_url',
            'comment_author_IP',
            'comment_date',
            'comment_date_gmt',
            'comment_content',
            'comment_karma',
            'comment_approved',
            'comment_agent',
            'comment_type',
            'comment_parent',
            'user_id',
            'post_type',
            'aid',
            'comment_childs',
            'comment_hide',
            'last_upd',
        );

        $data = wp_array_slice_assoc($data, $keys);
        $data['last_upd'] = $this->curr_time();

        $this->db_update($data, $this->db['comments'], (int) $comment_id, 'comment_ID');

        /*
          // If metadata is provided, store it.
          if (isset($commentarr['comment_meta']) && is_array($commentarr['comment_meta'])) {
          foreach ($commentarr['comment_meta'] as $meta_key => $meta_value) {
          update_comment_meta($comment_id, $meta_key, $meta_value);
          }
          } */


        /**
         * Fires immediately after a comment is updated in the database.
         *
         * The hook also fires immediately before comment status transition hooks are fired.
         *
         * @since 1.2.0
         * @since 4.6.0 Added the `$data` parameter.
         *
         * @param int   $comment_id The comment ID.
         * @param array $data       Comment data.
         */
        do_action('edit_comment', $comment_id, $data);

        $comment = $this->get_comment($comment_id, false);

        wp_transition_comment_status($comment->comment_approved, $old_status, $comment);

        // Get comment after hooks
        $comment = $this->get_comment($comment_id, false);

        $new_status = $comment->comment_approved;

        if ($comment->comment_parent > 0) {
            // Update comment count
            if ($new_status != $old_status) {
                if ($new_status == 1) {
                    // Publish
                    $change = 1;
                    $this->change_all_parrents_counter($comment->comment_parent, $change);
                } else if ($old_status == 1) {
                    // No publish
                    $change = -1;
                    $this->change_all_parrents_counter($comment->comment_parent, $change);
                }
                $this->update_comment_count($comment->comment_post_ID, $comment->post_type);
            }
        }

        $this->comments_delta();

        return $comment;
    }

    /**
     * Notifies the moderator of the blog about a new comment that is awaiting approval.
     *
     * @since 1.0
     * @uses $wpdb
     *
     * @param int $comment_id Comment ID
     * @return bool Always returns true
     */
    function notify_flag($comment_id, $fromUser, $commUser, $type = '') {

        // TODO send email if need

        $comment = $this->get_comment($comment_id);

        global $flagReport;
        if ($flagReport) {
            $flagReport->flag_comment($comment, $fromUser, $commUser, $type);
        }

        return true;
    }

    function post_flag($pid, $fromUser, $commUser, $type) {
        // UNUSED
        // TODO get post if need
        $post = $pid;
        //Отправка уведомления в базу
        global $flagReport;
        if ($flagReport) {
            $flagReport->flag_post($post, $fromUser, $commUser, $type);
        }

        return true;
    }
}
