<?php

/**
 * Description of UserFilters
 *
 * @author brahman
 * 
 * TODO
 * 1. Check user login
 * 2. Edit link
 * 
 */
class UserFilters extends AbstractDB {

    private $cm;
    private $db;
    public $tab_names = array(
        'movies' => 1,
        'games' => 2,
        'critics' => 3,
        'international' => 4,
        'ethnicity' => 5,
    );
    public $filters_img_dir = "filters_img";
    public $allowed_mime_types = [
        'image/jpeg' => '.jpg',
        'image/gif' => '.gif',
        'image/png' => '.png',
    ];
    public $an_tabs = array(4, 5);

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
        $croped_image = $data['img'];
        $remove_img = (int) $data['remove_img'];
        $publish = (int) $data['publish'];
        $curr_time = $this->curr_time();
        $filename = '';
        $source_dir = WP_CONTENT_DIR . '/uploads/' . $this->filters_img_dir;

        if (!$title) {
           $title = 'Filter ' . $this->curr_date();
        }
        
        $user = $this->get_current_user();
        $wp_uid = isset($user->ID) ? (int) $user->ID : 0;

        $msg = 'Need login';
        $ret_class = 'error';

        if ($wp_uid) {
            $link_hash = $this->link_hash($link);
            $link_data = $this->get_link_by_hash($link_hash);
            $msg = "Error. can't add filter";

            $author = $this->cm->get_author_by_wp_uid($wp_uid, true);
            if ($author) {
                $aid = $author->id;
            } else {
                // Get remote aid for a new author                
                $author_status = 1;
                $unic_id = $this->cm->unic_id();
                $options = array('audience' => $unic_id);
                $author_type = 2;
                $author_name = $user->display_name;
                $aid = $this->cm->create_author_by_name($author_name, $author_type, $author_status, $options, $wp_uid);
            }

            // Upload image
            $img_error = 0;
            $uscore_filter_image = $this->score_filter_image($wp_uid);

            if ($croped_image && $uscore_filter_image) {

                // This is an image?
                list($type, $croped_image) = explode(';', $croped_image);
                list(, $croped_image) = explode(',', $croped_image);
                $file_content = base64_decode($croped_image);

                $src_type = $this->isImage($file_content);
                if (!$src_type) {
                    $img_error = 1;
                    $msg = 'Upload image error';
                }

                if ($img_error == 0 && !isset($this->allowed_mime_types[$src_type])) {
                    $img_error = 1;
                    $msg = 'Upload image type is not allowed';
                }

                if ($img_error == 0) {
                    $filename = $aid . '-' . $curr_time . $this->allowed_mime_types[$src_type];

                    // Save image           

                    if (class_exists('ThemeCache')) {
                        ThemeCache::check_and_create_dir($source_dir);
                    }

                    $img_path = $source_dir . "/" . $filename;

                    if (file_exists($img_path)) {
                        unlink($img_path);
                    }

                    // Save file
                    $fp = fopen($img_path, "w");
                    fwrite($fp, $file_content);
                    fclose($fp);
                }
            }

            if ($img_error == 0) {
                if ($link_data) {
                    // Exist filter
                    $user_add_data = array(
                        'publish' => $publish,
                        'aid' => $aid,
                        'wp_uid' => $wp_uid,
                        'fid' => $link_data->id,
                        'last_upd' => $curr_time,
                        'title' => $title,
                        'content' => $content,
                    );
                    if ($uscore_filter_image && $filename) {
                        $user_add_data['img'] = $filename;
                    }
                    // Check user data
                    $user_data = $this->get_user_data($link_data->id, $wp_uid);
                    if ($user_data) {
                        // Check old image
                        if ($uscore_filter_image) {
                            if (($filename && $user_data->img !== $filename) || $remove_img == 1) {
                                $img_path = $source_dir . "/" . $user_data->img;
                                if (file_exists($img_path)) {
                                    unlink($img_path);
                                }
                            }
                            if ($remove_img == 1) {
                                $user_add_data['img'] = '';
                            }
                        }
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

                    $filters = $this->get_filters_by_url($link);
                    $tab_name = isset($filters['filters']['tab']) ? $filters['filters']['tab'] : '';

                    $def_tab = 1;

                    $tab = isset($this->tab_names[$tab_name]) ? $this->tab_names[$tab_name] : $this->get_def_tab($link);

                    // Add link data
                    $link_add_data = array(
                        'date' => $curr_time,
                        'tab' => $tab,
                        'link_hash' => $link_hash,
                        'link' => $link,
                    );
                    $link_id = $this->db_insert($link_add_data, $this->db['link_filters']);
                    if ($link_id) {
                        // Add user data
                        $user_add_data = array(
                            'publish' => $publish,
                            'aid' => $aid,
                            'wp_uid' => $wp_uid,
                            'fid' => $link_id,
                            'date' => $curr_time,
                            'last_upd' => $curr_time,
                            'title' => $title,
                            'content' => $content,
                            'img' => $filename,
                        );

                        $user_id = $this->db_insert($user_add_data, $this->db['user_filters']);
                        if ($user_id) {
                            $msg = "The filter added successfully";
                            $ret_class = 'success';
                        }
                    }
                }
            }
        }
        $this->filters_delta();
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

    public function get_img_path($img) {
        return '/wp-content/uploads/' . $this->filters_img_dir . '/' . $img;
    }

    public function get_def_tab($link) {
        $tab = 1;
        if (preg_match('#^/analytics/#', $link)) {
            // Def an tab
            $tab = 4;
        }
        return $tab;
    }

    public function link_form($link) {

        if (preg_match('#/f/([0-9]+)#', $link, $match)) {
            $filter = $this->load_filter_by_url($link);
            $link = $filter->link;
        }

        $link_hash = $this->link_hash($link);

        // Get user

        $user = $this->get_current_user();
        $wp_uid = isset($user->ID) ? (int) $user->ID : 0;

        // Check user login
        $user_identity = $user ? $user->display_name : '';

        if (!$user_identity) {
            print 'Need login';
            return;
        }

        $publish = 1;
        $filter_id = 0;
        $title = '';
        $content = '';
        $img = '';
        $already_publish = '';

        // Get exist link        
        $link_data = $this->get_link_by_hash($link_hash);
        if ($link_data) {
            $filter_id = $link_data->id;

            // Check user data
            $user_data = $this->get_user_data($link_data->id, $wp_uid);
            if ($user_data) {
                $publish = $user_data->publish;
                $title = $user_data->title;
                $content = $user_data->content;
                $img = $user_data->img;
            } else {
                $already_publish = $this->already_publish($link_data->id, $wp_uid);
                if ($already_publish) {
                    $publish = 0;
                }
            }
        }

        $commenter = $this->wp_get_current_commenter();
        ?>
        <form class="row-form">
            <div class="row">
                <div class="col_title">
                    <?php
                    $user_profile = '/author/'.$user->user_nicename.'/';
                    $logged_in_as = '<p class="logged-in-as">' .
                            sprintf('You are logged in as <a href="%1$s">%2$s</a>.', $user_profile, $user_identity). '</p>';   
                    print $logged_in_as;
                    ?>               
                </div>
            </div>
            <?php if ($this->score_filter_image($wp_uid)): ?>
                <div class="row">
                    <div class="col_input"> 
                        <div id="filter_image"><?php if ($img) { ?>
                                <img src="<?php print $this->get_img_path($img); ?>">
                            <?php } ?></div>                
                    </div>                
                    <div class="col_input">  
                        <button id="upl_filter_image" class="btn-small">Upload image</button>                     
                        <button id="remove_filter_image" class="btn-small btn-second<?php
                            if (!$img) {
                                print " ishide";
                            }
                            ?>">Remove image</button>
                        <input type="file" id="upl_filter_file" style="display: none;" >                                        
                        <input type="hidden" id="upl_filter_thumb" >                    
                        <input type="hidden" id="remove_filter_thumb" val="0"> 
                    </div>                
                </div>
            <?php endif ?>
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
                <?php if ($already_publish): ?>
                    <div class="desc col_title">Default: not published. Reason: the same filter has already been published by another user.</div>
                <?php endif; ?>
            </div>
            <div class="submit_data">
                <button id="submit-filter" class="button">Submit</button>
                <button class="button btn-second close">Close</button>
            </div>
        </form>
        <?php
    }

    private function score_filter_image($wp_uid = 0) {
        $ss = $this->cm->get_settings();
        $score_filter_image = $ss['score_filter_image'];
        $user_rating = 0;

        if ($wp_uid) {
            $uc = $this->cm->get_uc();
            $carma = $uc->getCarma($wp_uid);
            $user_rating = $carma[0];
        }

        if ($user_rating >= $score_filter_image) {
            return true;
        }

        return false;
    }

    public function ajax_list_menu($id = '', $act = '') {
        $user = $this->get_current_user();
        $wp_uid = isset($user->ID) ? (int) $user->ID : 0;
        $result = 0;
        if ($act == 'delfilter') {
            $exist_id = $this->get_filter_id_by_user_and_id($wp_uid, $id);
            if ($exist_id) {
                $this->remove_filter($exist_id);
                // Reindex rating
                $this->filters_delta();
                $result = 1;
            }
        }
        $ret = array('uid' => $wp_uid, 'result' => $result);
        print json_encode($ret);
        exit();
    }

    public function get_filters_by_url($url = '', $tags = false) {
        $ret = array();
        // Init url
        $last_req = $_SERVER['REQUEST_URI'];

        $_SERVER['REQUEST_URI'] = $url;
        $search_front = new CriticFront();
        $search_front->init_search_filters();
        $ret['filters'] = $search_front->filters;
        if ($tags) {
            $curr_tab = isset($ret['filters']['tab']) ? $ret['filters']['tab'] : '';
            $tab_name = isset($this->tab_names[$curr_tab]) ? $this->tab_names[$curr_tab] : $this->get_def_tab($url);
            if (in_array($tab_name, $this->an_tabs)) {
                if (!class_exists('AnalyticsFront')) {
                    require_once( CRITIC_MATIC_PLUGIN_DIR . 'AnalyticsFront.php' );
                    require_once( CRITIC_MATIC_PLUGIN_DIR . 'AnalyticsSearch.php' );
                }
                $search_front = new AnalyticsFront();
                $search_front->init_search_filters();
                $ret['filters'] = $search_front->filters;
            }

            $search_front->cs->get_filters_query($ret['filters']);
            $uid = 0;
            $ret['tags'] = $search_front->search_filters($curr_tab, $uid, true);
        }
        // Deinit url
        $_SERVER['REQUEST_URI'] = $last_req;

        return $ret;
    }

    public function get_filter_link($fid) {
        $link = '/f/' . $fid;
        return $link;
    }

    public function get_filters_count_by_wpuser($wp_uid = 0, $owner = 0) {

        $and_publish = '';
        if ($owner != 1) {
            $and_publish = ' AND publish=1';
        }
        $sql = sprintf("SELECT COUNT(*) FROM {$this->db['user_filters']} WHERE wp_uid=%d" . $and_publish, $wp_uid);
        $ret = $this->db_get_var($sql);

        return $ret;
    }

    public function get_filters_by_wpuser($wp_uid = 0, $owner = 0, $count = 0, $page = 1) {
        $and_publish = '';
        if ($owner != 1) {
            $and_publish = ' AND f.publish=1';
        }

        $and_limit = '';
        if ($count != 0) {
            $from = 0;
            if ($page != 1) {
                $from = ($page - 1) * $count;
            }
            $and_limit = sprintf(' LIMIT %d,%d', $from, $count);
        }

        $sql = sprintf("SELECT f.id, f.date, f.title, f.content, f.publish, f.aid, f.wp_uid, f.img, l.link, l.tab "
                . "FROM {$this->db['user_filters']} f "
                . "INNER JOIN {$this->db['link_filters']} l ON l.id=f.fid "
                . "WHERE f.wp_uid=%d" . $and_publish . " ORDER BY id DESC" . $and_limit, $wp_uid);
        $ret = $this->db_results($sql);

        return $ret;
    }

    public function get_user_filter($wp_uid = 0, $link = '') {
        $ret = 0;
        if ($link) {
            $link_hash = $this->link_hash($link);
            $sql = sprintf("SELECT id FROM {$this->db['link_filters']} WHERE link_hash='%s'", $link_hash);
            $fid = $this->db_get_var($sql);

            if ($fid) {
                $ret = $this->get_filter_id_by_user_and_id($wp_uid, $fid);
            }
        }
        return $ret;
    }

    public function get_filter_id_by_user_and_id($wp_uid = 0, $fid = '') {
        $sql = sprintf("SELECT id FROM {$this->db['user_filters']} WHERE fid=%d AND wp_uid=%d", $fid, $wp_uid);
        $ret = $this->db_get_var($sql);
        return $ret;
    }

    public function remove_filter($id = 0) {
        $sql = sprintf("DELETE FROM {$this->db['user_filters']} WHERE id=%d", (int) $id);
        $this->db_query($sql);
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

    public function load_filter_by_url($url = '') {
        $fid = 0;
        if (preg_match('#/f/([0-9]+)#', $url, $match)) {
            $fid = $match[1];
        }
        $filter = array();
        if ($fid) {
            $filter = $this->get_user_filter_by_id($fid);
        }
        return $filter;
    }

    public function get_post_wp_author($id = 0) {
        $sql = sprintf("SELECT wp_uid FROM {$this->db['user_filters']} WHERE id=%d", $id);
        $result = $this->db_get_var($sql);
        return $result;
    }

    public function update_filter($post_id = 0, $vote_count = 0) {
        // Update critic post date. Need form cache
        if ($post_id) {
            // Post exist?
            $sql = sprintf("SELECT id FROM {$this->db['user_filters']} WHERE id=%d", (int) $post_id);
            $result = $this->db_get_var($sql);

            //Update post
            if ($result) {
                $data = array(
                    'last_upd' => $this->curr_time(),
                    'rating' => $vote_count,
                );
                $this->db_update($data, $this->db['user_filters'], $post_id);
            }

            // Reindex rating
            $this->filters_delta();
        }
    }

    public function get_user_filter_by_id($id = 0) {
        $sql = sprintf("SELECT f.id, f.title, f.content, f.aid, f.wp_uid, f.img, l.link, l.tab "
                . "FROM {$this->db['user_filters']} f "
                . "INNER JOIN {$this->db['link_filters']} l ON l.id=f.fid "
                . "WHERE f.id=%d", $id);

        $result = $this->db_fetch_row($sql);
        return $result;
    }

    private function get_link_by_hash($link_hash = '') {
        $sql = sprintf("SELECT * FROM {$this->db['link_filters']} WHERE link_hash='%s'", $link_hash);
        $result = $this->db_fetch_row($sql);
        return $result;
    }

    private function get_user_data($fid = 0, $wp_uid = 0) {
        $sql = sprintf("SELECT * FROM {$this->db['user_filters']} WHERE fid=%d AND wp_uid=%d", $fid, $wp_uid);
        $result = $this->db_fetch_row($sql);
        return $result;
    }

    private function already_publish($fid = 0, $wp_uid = 0) {
        $sql = sprintf("SELECT * FROM {$this->db['user_filters']} WHERE fid=%d AND publish=1 AND wp_uid!=%d ORDER BY id ASC LIMIT 1", $fid, $wp_uid);
        $result = $this->db_fetch_row($sql);
        return $result;
    }

    public function filters_delta() {
        $data = array(
            'cmd' => 'filters_delta',
        );

        if (!defined('SYNC_HOST')) {
            return false;
        }
        $host = SYNC_HOST;
        return $this->cm->post($data, $host);
    }

    public function isImage($temp_file, $debug = false) {
        //Провереяем, картинка ли это
        @list($src_w, $src_h, $src_type_num) = array_values(getimagesizefromstring($temp_file));
        $src_type = image_type_to_mime_type($src_type_num);
        if ($debug) {
            print_r($src_type);
        }

        if (empty($src_w) || empty($src_h) || empty($src_type)) {
            return '';
        }
        return $src_type;
    }

    public function get_filters_page_by_wpuid($wp_uid = 0, $owner = 0, $url, $perpage = 10, $page = 1) {
        $posts = $this->get_filters_by_wpuser($wp_uid, $owner, $perpage, $page);
        $content = '';
        if ($posts) {
            ob_start();
            ?>
            <div class="simple">
                <div class="items<?php
            if ($owner) {
                print " owner";
            }
            ?>" data-id="0">
                     <?php
                         foreach ($posts as $post) {

                             // Link to filter
                             $link = $this->get_filter_link($post->id);

                             // Time
                             $ptime = $post->date;
                             $addtime = date('M', $ptime) . ' ' . date('jS Y', $ptime);

                             // Title
                             $title = strip_tags($post->title);

                             $publish = $post->publish;
                             $pub_icon = '';
                             if ($owner) {
                                 if ($publish == 0) {
                                     $pub_icon = '<i class="icon-eye-off"></i>';
                                 } else {
                                     $pub_icon = '<i class="icon-eye"></i>';
                                 }
                             }
                             $img = '';
                             if ($post->img) {
                                 $img = $this->get_img_path($post->img);
                             }
                             ?>
                        <div class="item" data-id="<?php print $post->id ?>">
                            <a href="<?php print $link ?>" title="<?php print $title ?>" >   
                                <?php if ($img): ?>
                                    <img srcset="<?php print $img; ?>" alt="<?php print $title ?>">                                             
                                <?php endif ?>
                                <div class="desc">
                                    <h5><?php print $title ?></h5>
                                    <p><?php print $addtime ?>.<?php print $pub_icon ?></p>
                                </div>
                            </a>                                       
                            <?php if ($owner): ?>                                            
                                <div class="menu nte">
                                    <div class="btn">
                                        <i class="icon icon-ellipsis-vert"></i>
                                    </div>
                                    <div class="nte_show dwn">
                                        <div class="nte_in">
                                            <div class="nte_cnt">
                                                <ul class="sort-wrapper more listmenu">                                                                                                               
                                                    <li class="nav-tab" data-act="editfilter" data-link="<?php print $link ?>">Edit Filter</li>
                                                    <li class="nav-tab" data-act="delfilter">Delete Filter</li>                                                                
                                                </ul>
                                            </div>                                                          
                                        </div>                                                    
                                    </div>                                                
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php } ?>
                </div>
            </div>
            <?php
            $content = ob_get_contents();
            ob_end_clean();
        }
        return $content;
    }

    public function get_filters_widget_by_wpuid($wp_uid = 0, $owner = 0, $perpage = 10) {
        $posts = $this->get_filters_by_wpuser($wp_uid, $owner, $perpage);

        $content = '';
        if ($posts) {
            ob_start();
            ?>
            <div class="simple">
                <div class="items">
                    <?php
                    foreach ($posts as $post) {

                        // Link to filter
                        $link = $this->get_filter_link($post->id);

                        // Time
                        $ptime = $post->date;
                        $addtime = date('M', $ptime) . ' ' . date('jS Y', $ptime);

                        // Title
                        $title = strip_tags($post->title);

                        $publish = $post->publish;
                        $pub_icon = '';
                        if ($owner) {
                            if ($publish == 0) {
                                $pub_icon = '<i class="icon-eye-off"></i>';
                            } else {
                                $pub_icon = '<i class="icon-eye"></i>';
                            }
                        }
                        $img = '';
                        if ($post->img) {
                            $img = $this->get_img_path($post->img);
                        }
                        ?>
                        <div class="item">
                            <a href="<?php print $link ?>" title="<?php print $title ?>" >    
                                <?php if ($img): ?>
                                    <img srcset="<?php print $img; ?>" alt="<?php print $title ?>">                                             
                                <?php endif ?>
                                <div class="desc">
                                    <h5><?php print $title ?></h5>
                                    <p><?php print $addtime ?></p>
                                </div>
                            </a>
                        </div>
                    <?php } ?>
                </div>
            </div>
            <?php
            $content = ob_get_contents();
            ob_end_clean();
        }
        return $content;
    }

    private function get_current_user() {
        $wpu = $this->cm->get_wpu();
        $user_id = $wpu->get_current_user();
        if ($user_id) {
            $user = $wpu->user;
            return $user;
        }
        return new stdClass();
    }
}
