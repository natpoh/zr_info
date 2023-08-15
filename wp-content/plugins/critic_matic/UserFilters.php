<?php

/**
 * Description of UserFilters
 *
 * @author brahman
 * 
 * TODO
 * 1. Check user login
 * 2. Edit link
 * 3. 
 * 
 */
class UserFilters extends AbstractDB {

    private $cm;
    private $db;

    public function __construct($cm = '') {
        $this->cm = $cm ? $cm : new CriticMatic();
        $this->db = array(
            'user_filters' => 'data_user_filters',
            'link_filters' => 'data_link_filters',
        );
    }

    public function submit($data) {
        $link = $data['link'];
        $title = $data['title'];
        $content = $data['content'];
        $publish = (int) $data['publish'];
        $curr_time = $this->curr_time();

        $user = wp_get_current_user();
        $wp_uid = $user->exists() ? $user->ID : 0;
        $msg = 'Need login';
        $ret_class = 'error';


        if ($wp_uid) {
            $link_hash = $this->link_hash($link);
            $link_data = $this->get_link_by_hash($link_hash);
            $msg = "Error. can't add filter";
            if ($link_data) {
                // Exist filter

                $user_add_data = array(
                    'publish' => $publish,
                    'uid' => $wp_uid,
                    'fid' => $link_data->id,
                    'last_upd' => $curr_time,
                    'title' => $title,
                    'content' => $content,
                );

                // Check user data
                $user_data = $this->get_user_data($link_data->id, $wp_uid);
                if ($user_data) {
                    // Update user data
                    $user_id = $user_data->id;
                    $this->db_update($user_add_data, $this->db['user_filters'], $user_id);
                    $msg = "The filter updated successfully";
                    $ret_class = 'success';
                } else {
                    // Add user data
                    $user_add_data['date'] = $curr_time;
                    $user_id = $this->db_insert($user_add_data, $this->db['user_filters']);
                    if ($user_id) {
                        $msg = "The filter added successfully";
                        $ret_class = 'success';
                    }
                }
            } else {
                // New filter
                $type = 0;

                // Add link data
                $link_add_data = array(
                    'date' => $curr_time,
                    'type' => $type,
                    'link_hash' => $link_hash,
                    'link' => $link,
                );
                $link_id = $this->db_insert($link_add_data, $this->db['link_filters']);
                if ($link_id) {
                    // Add user data
                    $user_add_data = array(
                        'publish' => $publish,
                        'uid' => $wp_uid,
                        'fid' => $link_id,
                        'date' => $curr_time,
                        'last_upd' => $curr_time,
                        'title' => $title,
                        'content' => $content,
                    );

                    $user_id = $this->db_insert($user_add_data, $this->db['user_filters']);
                    if ($user_id) {
                        $msg = "The filter added successfully";
                        $ret_class = 'success';
                    }
                }
            }
        }
        ?>            
        <form class="row-form">
            <div class="row">
                <div class="col_title <?php print $ret_class ?>"><?php print $msg ?></div>                    
            </div>
            <div class="submit_data">
                <button class="button btn-second close">Close</button>
            </div>
        </form>
        <?php
    }

    public function link_form($link) {
        $link_hash = $this->link_hash($link);

        // Get user
        $user = wp_get_current_user();
        $wp_uid = $user->exists() ? $user->ID : 0;

        // Check user login
        $user_identity = $user->exists() ? $user->display_name : '';

        if (!$user_identity) {
            print 'Need login';
            return;
        }

        $publish = 1;
        $filter_id = 0;
        $title = '';
        $content = '';

        // Get exist link        
        $link_data = $this->get_link_by_hash($link_hash);
        if ($link_data) {
            $filter_id = $link_data->id;
            $publish = 0;
            // Check user data
            $user_data = $this->get_user_data($link_data->id, $wp_uid);
            if ($user_data) {
                $publish = $user_data->publish;
                $title = $user_data->title;
                $content = $user_data->content;
            }
        }

        $commenter = $this->wp_get_current_commenter();
        ?>
        <form class="row-form">
            <div class="row">
                <div class="col_title">
        <?php
        $user_profile = get_author_posts_url($user->ID, $user->user_nicename);
        $logged_in_as = '<p class="logged-in-as">' .
                sprintf(__('You are logged in as <a href="%1$s">%2$s</a>. <a href="%3$s" title="Sign out of this account">Sign out?</a>'), $user_profile, $user_identity, wp_logout_url($post_link)) . '</p>';
        print apply_filters('comment_form_logged_in', $logged_in_as, $commenter, $user_identity);
        do_action('comment_form_logged_in_after', $commenter, $user_identity);
        ?>               
                </div>
            </div>
            <div class="row">
                <div class="col_title">Filter link:</div>
                <div class="col_input">                    
                    <input name="link" class="link" value="<?php print $link ?>" disabled="disabled">
                    <div class="col_content"></div>
                </div>                
            </div>
            <div class="row">
                <div class="col_title">Title:</div>
                <div class="col_input">
                    <input name="title" class="title" value="<?php print stripslashes($title) ?>" placeholder="">
                    <div class="col_content"></div>
                </div>                
            </div>
            <div class="row">
                <div class="col_title">Description:</div>
                <div class="col_input">
                    <textarea name="content" data-id="content" class="content"><?php print stripslashes($content) ?></textarea>
                    <div class="col_content"></div>
                </div>                
            </div>
            <div class="row">                
                <div class="col_title form-check">                     
                    <input type="checkbox" name="publish" value="1" id="publish" <?php
            if ($publish) {
                print "checked";
            }
            ?> >
                    <label for="publish">
                        Publish to the public filter list.
                    </label>
                </div>                  
            </div>
            <div class="submit_data">
                <button id="submit-filter" class="button">Submit</button>
                <button class="button btn-second close">Close</button>
            </div>
        </form>
        <?php
    }

    public function get_user_filter($uid = 0, $link = '') {
        $ret = 0;
        if ($uid > 0 && $link) {
            $link_hash = $this->link_hash($link);
            $sql = sprintf("SELECT id FROM {$this->db['link_filters']} WHERE link_hash='%s'", $link_hash);
            $fid = $this->db_get_var($sql);

            if ($fid) {
                $sql = sprintf("SELECT id FROM {$this->db['user_filters']} WHERE fid=%d AND uid=%d", $fid, $uid);
                $ret = $this->db_get_var($sql);
            }
        }
        return $ret;
    }

    private function wp_get_current_commenter() {
        // Cookies should already be sanitized.

        $comment_author = '';
        if (isset($_COOKIE['comment_author_' . COOKIEHASH])) {
            $comment_author = $_COOKIE['comment_author_' . COOKIEHASH];
        }

        $comment_author_pass = '';
        if (isset($_COOKIE['comment_author_pass_' . COOKIEHASH])) {
            $comment_author_pass = $_COOKIE['comment_author_pass_' . COOKIEHASH];
        }
        return compact('comment_author', 'comment_author_pass');
    }

    private function get_link_by_hash($link_hash = '') {
        $sql = sprintf("SELECT * FROM {$this->db['link_filters']} WHERE link_hash='%s'", $link_hash);
        $result = $this->db_fetch_row($sql);
        return $result;
    }

    private function get_user_data($fid = 0, $uid = 0) {
        $sql = sprintf("SELECT * FROM {$this->db['user_filters']} WHERE fid=%d AND uid=%d", $fid, $uid);
        $result = $this->db_fetch_row($sql);
        return $result;
    }

}
