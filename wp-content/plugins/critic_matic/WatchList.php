<?php

/**
 * @author brahman
 * 
 * TODO
 * lists widget 
 * lists page
 * list page
 * lists in search
 * 
 */
class WatchList extends AbstractDB {

    private $cm;
    private $db;
    public $publish = array(
        0 => array('title' => 'For you', 'icon' => 'eye-off'),
        1 => array('title' => 'For everyone', 'icon' => 'eye'),
    );
    public $type = array(
        0 => 'User list',
        1 => 'Watch Later',
        2 => 'Favorites',
    );

    public function __construct($cm = '') {
        $this->cm = $cm ? $cm : new CriticMatic();
        $this->db = array(
            'list' => 'watch_list',
            'item' => 'watch_item',
        );
    }

    /*
     * Ajax
     */

    public function ajax_select($mid = 0, $activate = 0, $type = 0) {
        # Fast select list by type
        $wp_uid = $this->get_current_user_id();
        $result = 0;
        $msg = 'Please login';
        $theme = 'warn';
        if ($wp_uid) {
            $list = $this->get_user_list_by_type($wp_uid, $type);
            if (!$list) {
                $this->create_def_lists($wp_uid);
                $list = $this->get_user_list_by_type($wp_uid, $type);
            }
            if ($activate == 1) {
                // Add
                $msg = 'Added to ' . $this->type[$type];
                $this->add_to_list($wp_uid, $list->id, $mid);
                $theme = 'status';
            } else {
                // Remove
                $this->remove_from_list($wp_uid, $list->id, $mid);
                $msg = 'Removed from ' . $this->type[$type];
                $theme = 'status';
            }
            $result = 1;
        }
        $ret = array('wp_uid' => $wp_uid, 'result' => $result, 'msg' => $msg, 'theme' => $theme);
        print json_encode($ret);
        exit();
    }

    public function ajax_select_list($lid = 0, $mid = 0, $act = '') {
        # Select list from menu
        $result = array();
        $wp_uid = $this->get_current_user_id();
        $msg = 'Please login';
        $theme = 'warning';
        if ($wp_uid && $mid) {
            $list = $this->get_user_list($wp_uid, $lid);
            if (!$list) {
                $this->create_def_lists($wp_uid);
                $list = $this->get_user_list($wp_uid, $lid);
            }

            if ($act == 'add') {
                # Add to list
                $result['ret'] = $this->add_to_list($wp_uid, $lid, $mid);
                $msg = 'Added to list: ' . stripslashes($list->title);
                $theme = 'status';
            } else {
                # Remove from list
                $result['ret'] = $this->remove_from_list($wp_uid, $lid, $mid);
                $msg = 'Removed from list: ' . stripslashes($list->title);
                ;
                $theme = 'status';
            }
            $result['type'] = $list->type;
        }

        $ret = array('wp_uid' => $wp_uid, 'result' => $result, 'msg' => $msg, 'theme' => $theme);
        print json_encode($ret);
        exit();
    }

    public function ajax_list_menu($id = 0, $parent = 0, $act = '') {
        // actions:
        // delwl,
        // makeicon,
        // delitem
        # Get current user
        $wp_uid = $this->get_current_user_id();
        $result = 0;
        if ($wp_uid) {
            if ($act == 'makeicon') {
                # UNUSED DEPRECATED
                # Check current user lists                
                $list_exsist = $this->get_user_list($wp_uid, $parent);
                if ($list_exsist) {
                    $data = array(
                        'top_mid' => $id
                    );
                    $this->update_list($data, $parent);
                    $result = 1;
                }
            } else if ($act == 'delwl') {
                $list_exsist = $this->get_user_list($wp_uid, $id);
                if ($list_exsist) {
                    $this->remove_items($id);
                    $this->remove_list($id);
                    $result = 1;
                }
            } else if ($act == 'delitem') {
                # Remove item from list
                $result = $this->remove_from_list($wp_uid, $parent, $id);
            }
        }
        $ret = array('wp_uid' => $wp_uid, 'result' => $result);
        print json_encode($ret);
        exit();
    }

    public function ajax_add_new_list($title = '', $content = '', $publish = 0, $mid = 0) {
        /*
          $privacy
          0 - For everyone
          1 - By link
          2 - For you
         */
        # Get current user
        $wp_uid = $this->get_current_user_id();
        $msg = 'Please login';
        $theme = 'warning';

        if ($wp_uid) {
            if (!$title) {
                $title = 'Watch list ' . $this->curr_date();
            }

            $aid = $this->get_aid($wp_uid);
            $data = array(
                'wp_uid' => (int) $wp_uid,
                'aid' => (int) $aid,
                'date' => $this->curr_time(),
                'title' => $title,
                'content' => $content,
                'publish' => (int) $publish,
            );
            $this->db_insert($data, $this->db['list']);
            $msg = 'List added';
            $theme = 'status';

            $this->watchlists_delta();
            $lists = $this->get_user_lists_mid($wp_uid, $mid);
        }

        $ret = array('wp_uid' => $wp_uid, 'lists' => $lists, 'msg' => $msg, 'theme' => $theme);
        print json_encode($ret);
        exit();
    }

    public function ajax_update_list($title = '', $content = '', $publish = 0, $id = 0) {
        # Get current user
        $wp_uid = $this->get_current_user_id();
        if ($wp_uid) {
            $list_exsist = $this->get_user_list($wp_uid, $id);
            if ($list_exsist) {
                if (!$title) {
                    $title = 'Watch list ' . $this->curr_date();
                }
                $data = array(
                    'title' => $title,
                    'publish' => $publish,
                    'content' => $content,
                );
                $this->update_list($data, $id);
            }
        }

        $ret = array('wp_uid' => $wp_uid);
        print json_encode($ret);
        exit();
    }

    public function ajax_get_user_lists($mid = 0) {
        # Get current user
        $wp_uid = $this->get_current_user_id();
        $lists = array();
        if ($wp_uid) {
            # Get user lists
            $lists = $this->get_user_lists_mid($wp_uid, $mid);
        }
        $ret = array('wp_uid' => $wp_uid, 'lists' => $lists);
        print json_encode($ret);
        exit();
    }

    /*
     * Lists
     */

    public function get_user_lists_mid($wp_uid = 0, $mid = 0) {
        # Get lists
        $sql = sprintf("SELECT * FROM {$this->db['list']} WHERE wp_uid=%d ORDER BY id DESC", (int) $wp_uid);
        $lists = $this->db_results($sql);
        # Get movie lists        
        $list_valid = array();
        $def_lists_exist = 0;
        $lids = array();
        if ($lists) {
            foreach ($lists as $list) {
                $lids[] = $list->id;
                if ($list->type != 0) {
                    $def_lists_exist += 1;
                }
            }
        }

        if ($def_lists_exist != 2) {
            $this->create_def_lists($wp_uid);
            return $this->get_user_lists_mid($wp_uid, $mid);
        }

        if ($lids) {
            $valid = array();
            $sql = sprintf("SELECT lid FROM {$this->db['item']} WHERE mid=%d AND lid IN(" . implode(',', $lids) . ")", (int) $mid);
            $items = $this->db_results($sql);
            if ($items) {
                foreach ($items as $item) {
                    $valid[$item->lid] = 1;
                }
            }

            $def_lists = array();
            foreach ($lists as $list) {
                $list->mid = isset($valid[$list->id]) ? 1 : 0;
                //$list->icon = $this->publish[$list->publish]['icon'];
                if ($list->type == 0) {
                    $list_valid[] = $list;
                } else {
                    $def_lists[$list->type] = $list;
                }
            }
            if ($def_lists) {
                $list_valid = array_merge(array_values($def_lists), $list_valid);
            }
        }


        return $list_valid;
    }

    public function get_user_lists($wp_uid = 0, $owner = 0, $count = 0, $page = 1) {
        $and_publish = '';
        if ($owner != 1) {
            $and_publish = ' AND publish=1';
        }

        $and_limit = '';
        if ($count != 0) {
            $from = 0;
            if ($page != 1) {
                $from = ($page - 1) * $count;
            }
            $and_limit = sprintf(' LIMIT %d,%d', $from, $count);
        }

        $sql = sprintf("SELECT * FROM {$this->db['list']} WHERE wp_uid=%d" . $and_publish . " ORDER BY id DESC" . $and_limit, (int) $wp_uid);
        $results = $this->db_results($sql);
        $lists = array();
        $def_lists = array();
        if ($results) {
            // global $cfront;
            foreach ($results as $list) {
                /* $poster = '';
                  if ($list->top_mid) {
                  $poster = $cfront->get_thumb_path_full(90, 120, $list->top_mid);
                  }
                  $list->poster = $poster; */
                if ($list->type == 0) {
                    $lists[] = $list;
                } else {
                    $def_lists[$list->type] = $list;
                }
            }
            if ($def_lists) {
                $lists = array_merge(array_values($def_lists), $lists);
            }
        }

        return $lists;
    }

    public function in_def_lists($wp_uid = 0, $mids = array()) {
        $def_lists = $this->get_def_lists($wp_uid);
        $mids_ret = array();
        if ($def_lists) {
            foreach ($mids as $mid) {
                foreach ($def_lists as $type => $items) {
                    if (in_array($mid, $items)) {
                        $mids_ret[$mid][$type] = 1;
                    }
                }
            }
        }
        return $mids_ret;
    }

    public function get_def_lists($wp_uid = 0) {
        $sql = sprintf("SELECT * FROM {$this->db['list']} WHERE wp_uid=%d AND type!=0", (int) $wp_uid);
        $results = $this->db_results($sql);
        $ret = array();
        if ($results) {
            foreach ($results as $list) {
                $items = $this->get_list_items($list->id);
                if ($items) {
                    foreach ($items as $item) {
                        $ret[$list->type][] = $item->mid;
                    }
                }
            }
        }
        return $ret;
    }

    public function get_list_movies($wp_uid = 0, $lid = 0) {
        $sql = sprintf("SELECT * FROM {$this->db['list']} WHERE id=%d", (int) $lid);
        $list = $this->db_fetch_row($sql);
        $ids = array();
        if ($list) {
            if ($list->publish == 1 || ($list->publish == 0 && $wp_uid == $list->wp_uid)) {
                $items = $this->get_list_items($lid);
                if ($items) {
                    foreach ($items as $item) {
                        $ids[] = $item->mid;
                    }
                }
            }
        }
        return $ids;
    }

    public function get_list($lid = 0) {
        $sql = sprintf("SELECT * FROM {$this->db['list']} WHERE id=%d", (int) $lid);
        $results = $this->db_fetch_row($sql);
        return $results;
    }

    public function get_user_list($wp_uid = 0, $lid = 0) {
        $sql = sprintf("SELECT * FROM {$this->db['list']} WHERE wp_uid=%d AND id=%d", (int) $wp_uid, (int) $lid);
        $results = $this->db_fetch_row($sql);
        return $results;
    }

    public function get_user_list_by_type($wp_uid = 0, $type = 0) {
        $sql = sprintf("SELECT * FROM {$this->db['list']} WHERE wp_uid=%d AND type=%d", (int) $wp_uid, (int) $type);
        $results = $this->db_fetch_row($sql);
        return $results;
    }

    public function get_user_lists_count($wp_uid = 0, $owner = 0) {
        // UNUSED
        $and_publish = '';
        if ($owner != 1) {
            $and_publish = ' AND publish=1';
        }
        $sql = sprintf("SELECT COUNT(*) FROM {$this->db['list']} WHERE wp_uid=%d" . $and_publish, $wp_uid);
        $ret = $this->db_get_var($sql);

        return $ret;
    }

    public function update_list($data = array(), $id) {
        $data['last_upd'] = $this->curr_time();
        $this->db_update($data, $this->db['list'], $id);
        $this->watchlists_delta();
    }

    public function remove_list($lid = 0) {
        $sql = sprintf("DELETE FROM {$this->db['list']} WHERE id=%d", (int) $lid);
        $this->db_query($sql);
        $this->watchlists_delta();
    }

    public function create_def_lists($wp_uid = 0) {
        /*
          public $type
          0 => 'User list',
          1 => 'Watch Later',
          2 => 'Favorites',
         */
        foreach ($this->type as $type => $title) {
            if ($type==0){
                continue;
            }
            $exist = $this->get_user_list_by_type($wp_uid, $type);
            if (!$exist) {
                $aid = $this->get_aid($wp_uid);
                $data = array(
                    'wp_uid' => (int) $wp_uid,
                    'aid' => (int) $aid,
                    'date' => $this->curr_time(),
                    'title' => $title,
                    'type' => $type,
                    'content' => '',
                    'publish' => 0,
                );
                $this->db_insert($data, $this->db['list']);
            }
        }
        $this->watchlists_delta();
    }

    /*
     * Items
     */

    public function get_list_items($lid = 0, $limit = 0) {
        $and_limit = '';
        if ($limit > 0) {
            $and_limit = " LIMIT " . (int) $limit;
        }
        $sql = sprintf("SELECT * FROM {$this->db['item']} WHERE lid=%d" . $and_limit, $lid);
        $ret = $this->db_results($sql);
        return $ret;
    }

    public function get_list_items_count($lid = 0) {
        $sql = sprintf("SELECT COUNT(*) FROM {$this->db['item']} WHERE lid=%d", $lid);
        $ret = $this->db_get_var($sql);
        return $ret;
    }

    public function item_exist($lid = 0, $mid = 0) {
        $sql = sprintf("SELECT id FROM {$this->db['item']} WHERE lid=%d AND mid=%d", $lid, $mid);
        $ret = $this->db_get_var($sql);
        return $ret;
    }

    public function add_item($mid = 0, $lid = 0, $weight = 0) {
        $data = array(
            'mid' => $mid,
            'lid' => $lid,
            'date' => $this->curr_time(),
            'weight' => $weight,
        );

        $id = $this->db_insert($data, $this->db['item']);
        return $id;
    }

    public function remove_from_list($wp_uid, $lid, $mid) {
        $result = 0;
        $list_exsist = $this->get_user_list($wp_uid, $lid);
        if ($list_exsist) {
            $new_count = $list_exsist->items;
            $data = array();

            # Remove from list
            $this->remove_item($mid, $lid);
            //if ($list_exsist->top_mid != 0) {
            //    $list_count = $this->get_list_items_count($lid);
            //    if ($list_count == 0 || $list_exsist->top_mid == $mid) {
            //        $data['top_mid'] = 0;
            //    }
            //}
            $new_count -= 1;
            if ($new_count < 0) {
                $new_count = 0;
            }
            $data['items'] = $new_count;
            $this->update_list($data, $lid);
            $result = 1;
        }
        return $result;
    }

    public function add_to_list($wp_uid, $lid, $mid) {
        # Check current user lists
        $result = 0;
        $list_exsist = $this->get_user_list($wp_uid, $lid);
        if ($list_exsist) {
            if (!$this->item_exist($list_exsist->id, $mid)) {
                $new_count = $list_exsist->items;
                $data = array();

                # Add to list
                $this->add_item($mid, $lid);

                //if ($list_exsist->top_mid == 0) {
                //    $data['top_mid'] = $mid;
                //}
                $new_count += 1;
                $data['items'] = $new_count;
                $this->update_list($data, $lid);

                $result = 1;
            }
        }
        return $result;
    }

    public function remove_item($mid = 0, $lid = 0) {
        $sql = sprintf("DELETE FROM {$this->db['item']} WHERE mid=%d AND lid=%d", (int) $mid, (int) $lid);
        $this->db_query($sql);
    }

    public function remove_items($lid = 0) {
        $sql = sprintf("DELETE FROM {$this->db['item']} WHERE lid=%d", (int) $lid);
        $this->db_query($sql);
    }

    public function update_item($data = array(), $id) {
        // UNUSED
        $this->db_update($data, $this->db['item'], $id);
    }

    public function get_lists_widget_by_wpuid($wp_uid = 0, $owner = 0, $url = '/') {
        $perpage = 10;
        $posts = $this->get_user_lists($wp_uid, $owner, $perpage);

        $content = '';
        if ($posts) {
            ob_start();
            ?>
            <div class="simple">
                <div class="items">
            <?php
            foreach ($posts as $post) {

                # Link to filter
                $link = $this->get_post_link($url, $post->id);

                # Time
                $ptime = $post->date;
                $addtime = date('M', $ptime) . ' ' . date('jS Y', $ptime);

                # Title
                $title = stripslashes($post->title);

                $publish = $post->publish;
                $pub_icon = '<i class="icon-' . $this->publish[$publish]['icon'] . '"></i>';

                $count = $post->items;

                $poster = $this->get_list_collage($post->id, false);
                ?>
                        <div class="item">
                            <a href="<?php print $link ?>" title="<?php print $title ?>" >                           
                <?php
                if ($poster) {
                    print $poster;
                }
                ?>
                                <div class="desc">
                                    <h5><?php print $title ?></h5>
                                    <p><?php print $addtime ?> <?php print $pub_icon ?> Items: <?php print $count ?></p>
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

    public function get_lists_page_by_wpuid($wp_uid = 0, $owner = 0, $url, $perpage = 10, $page = 1) {
        $posts = $this->get_user_lists($wp_uid, $owner, $perpage, $page);

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

                         # Link to filter
                         $link = $this->get_post_link($url, $post->id);

                         # Time
                         $ptime = $post->date;
                         $addtime = date('M', $ptime) . ' ' . date('jS Y', $ptime);

                         # Title
                         $title = stripslashes($post->title);

                         $publish = $post->publish;
                         $pub_icon = '';
                         if ($owner) {
                             $pub_icon = ' <i class="icon-' . $this->publish[$publish]['icon'] . '"></i>';
                         }
                         $count = $post->items;

                         $poster = $this->get_list_collage($post->id, false);
                         ?>
                        <div class="item" data-id="<?php print $post->id ?>">
                            <a href="<?php print $link ?>" title="<?php print $title ?>" >   
                <?php
                if ($poster) {
                    print $poster;
                }
                ?>
                                <div class="desc">
                                    <h5><?php print $title ?></h5>
                                    <p><?php print $addtime ?>.<?php print $pub_icon ?> Items: <?php print $count ?></p>
                                </div>
                            </a>                                       
                <?php
                if ($owner):

                    $list_json = array(
                        'id' => $post->id,
                        'publish' => $post->publish,
                        'title' => stripslashes($post->title),
                        'content' => stripslashes($post->content),
                    );
                    $str_json = json_encode($list_json);
                    ?>                                            
                                <div class="menu nte">
                                    <div class="btn">
                                        <i class="icon icon-ellipsis-vert"></i>
                                    </div>
                                    <div class="nte_show dwn">
                                        <div class="nte_in">
                                            <div class="nte_cnt">
                                                <ul class="sort-wrapper more listmenu">                      
                                                    <li class="nav-tab" data-act="show" data-href="/search/wl_<?php print $post->id ?>">Show in Search</li>
                                                    <li class="nav-tab" data-act="show" data-href="/analytics/tab_ethnicity/wl_<?php print $post->id ?>">Show in Analytics</li>
                    <?php if ($post->type == 0): ?>
                                                        <li class="nav-tab" data-act="editwl" data-json="<?php print htmlspecialchars($str_json) ?>">Edit List</li>
                                                        <li class="nav-tab" data-act="delwl">Delete List</li>
                    <?php endif ?>
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

    public function get_list_page($curr_list = 0, $list = array(), $owner = 0) {
        $content = '';
        if ($list) {
            $count = $list->items;
            $posts = $this->get_list_items($curr_list);
            global $cfront;
            if ($cfront) {
                $ma = $cfront->get_ma();
                ob_start();
                ?>
                <div class="flexrow">    
                    <div class="flexcol first250">
                <?php
                $ptime = $list->last_upd;
                $updtime = '';
                if ($ptime) {
                    $updtime = date('M', $ptime) . ' ' . date('jS Y', $ptime);
                }
                $publish = $list->publish;
                $pub_icon = '<i class="icon-' . $this->publish[$publish]['icon'] . '"></i>';
                $pub_title = $this->publish[$publish]['title'];
                $list_json = array(
                    'id' => $list->id,
                    'publish' => $list->publish,
                    'title' => stripslashes($list->title),
                    'content' => stripslashes($list->content),
                );
                $str_json = json_encode($list_json);

                $poster = $this->get_list_collage($list->id);
                if ($poster) {
                    print $poster;
                }
                ?>
                        <?php if ($ptime) { ?>
                            <div class="row">Updated: <?php print $updtime ?></div>
                        <?php } ?>
                        <?php if ($owner) { ?>
                            <div class="row">Access: <?php print $pub_icon . ' ' . $pub_title ?></div>
                        <?php } ?>
                        <div class="row">Items: <?php print $count ?></div>

                <?php
                # Content
                $content = stripslashes($list->content);
                if ($content) {
                    ?>
                            <p><?php print $content ?><p>
                        <?php } ?>

                        <div class="row"><a class="uw-btn" href="/search/wl_<?php print $list->id ?>">Show in Search</a></div>
                        <div class="row"><a class="uw-btn" href="/analytics/tab_ethnicity/wl_<?php print $list->id ?>">Show in Analytics</a></div>


                <?php if ($owner && $list->type == 0) { ?>
                            <br /><div class="row"><button id="user_edit_watchlist" class="btn-small" data-json="<?php print htmlspecialchars($str_json) ?>" data-id="<?php print $curr_list ?>" data-publish="<?php print $publish ?>" data-title="<?php print $list->title ?>" data-content="<?php print $list->content ?>">Edit list</button></div>
                        <?php } ?>

                    </div>
                    <div class="flexcol second simple">
                        <div class="items<?php
                if ($owner) {
                    print " owner";
                }
                        ?>" data-id="<?php print $list->id ?>">
                        <?php
                             if ($posts) {
                                 foreach ($posts as $post) {

                                     # Time
                                     $ptime = $post->date;
                                     $addtime = date('M', $ptime) . ' ' . date('jS Y', $ptime);

                                     $movie = $ma->get_post($post->mid);

                                     # Title                                
                                     $title = stripslashes($movie->title);

                                     # Link 
                                     $link = $ma->get_post_link($movie);

                                     // release
                                     $release = $movie->release;
                                     if ($release) {
                                         $release = strtotime($release);
                                         $release = date('Y', $release);
                                         if (strstr($title, $release)) {
                                             $release = '';
                                         } else {
                                             $release = ' (' . $release . ')';
                                         }
                                     }
                                     $poster_link_90 = $cfront->get_thumb_path_full(90, 120, $post->mid);
                                     ?>
                                    <div class="item" data-id="<?php print $movie->id ?>">
                                        <a href="<?php print $link ?>" title="<?php print $title ?>" >   
                                            <img srcset="<?php print $poster_link_90 ?>" alt="<?php print $title ?>">
                                            <div class="desc">
                                                <h5><?php print $title . $release ?></h5>
                                                <p><?php print $addtime ?></p>
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
                                                                <li class="nav-tab" data-act="delitem">Remove from Wachlist</li>                                                                
                                                            </ul>
                                                        </div>                                                          
                                                    </div>                                                    
                                                </div>                                                
                                            </div>
                        <?php endif; ?>
                                    </div>
                                        <?php
                                    }
                                }
                                ?>
                        </div>
                    </div>
                </div>
                <?php
                $content = ob_get_contents();
                ob_end_clean();
            }
        }
        return $content;
    }

    public function get_list_collage($lid, $big = true) {
        $limit = 5;
        $items = $this->get_list_items($lid, $limit);
        $w = 90;
        $h = 120;
        $big_class = '';
        if ($big) {
            $big_class = ' big';
            $w = 220;
            $h = 330;
        }
        $ret = '';
        if ($items) {
            $ret = '<div class="collage' . $big_class . '">';
            foreach ($items as $item) {
                $poster = $this->get_thumb_path_full(220, 330, $item->mid);
                $ret .= '<div class="item"><img srcset="' . $poster . '"></div>';
            }
            $ret .= '</div>';
        }
        return $ret;
    }

    public function get_watch_blocks($ids = array()) {
        if (!$ids) {
            return array();
        }
        # Get current user
        $wp_uid = $this->get_current_user_id();
        if (!$wp_uid) {
            return array();
        }
        $in_list = $this->in_def_lists($wp_uid, $ids);
        $ret = array();
        foreach ($ids as $id) {
            /*
              1 => 'Watch Later',
              2 => 'Favorites',
             */
            $add_watch_list_active = isset($in_list[$id][1]) ? ' active' : '';
            $add_favorite_list_active = isset($in_list[$id][2]) ? ' active' : '';
            ob_start();
            ?>
            <div id="watch_block_<?php print $id ?>" class="watch_block">
                <a href="#watch_later" title="Watch later" class="add_watch_list<?php print $add_watch_list_active ?>" data-mid="<?php print $id ?>" data-type="1"></a>
                <a href="#favorites" title="Favorites" class="add_favorite_list<?php print $add_favorite_list_active ?>" data-mid="<?php print $id ?>" data-type="2"></a>
                <a href="#" title="Add to Watch List" class="browse_watch_lists" data-mid="<?php print $id ?>">...</a>
            </div>            
            <?php
            $content = ob_get_contents();
            ob_end_clean();
            $ret[$id] = $content;
        }
        return $ret;
    }

    public function get_thumb_path_full($w, $h, $id) {
        !class_exists('RWTimages') ? include ABSPATH . "analysis/include/rwt_images.php" : '';
        $time = RWTimages::get_last_time($id);
        return RWTimages::get_image_link('m_' . $id, $w . 'x' . $h, '', $time);
    }

    public function get_post_link($url, $pid) {
        $link = $url . $pid . '/';
        return $link;
    }

    public function get_current_user() {
        return $this->cm->get_current_user();
    }

    public function get_current_user_id() {
        $user = $this->cm->get_current_user();
        return $user->ID;
    }

    private function get_aid($wp_uid) {
        $author = $this->cm->get_author_by_wp_uid($wp_uid, true);
        $aid = 0;
        if ($author) {
            $aid = $author->id;
        } else {
            // Get remote aid for a new author                
            $author_status = 1;
            $unic_id = $this->cm->unic_id();
            $options = array('audience' => $unic_id);
            $author_type = 2;
            $user = $this->get_current_user();
            $author_name = $user->display_name;
            $aid = $this->cm->create_author_by_name($author_name, $author_type, $author_status, $options, $wp_uid);
        }
        return $aid;
    }

    public function watchlists_delta() {
        $data = array(
            'cmd' => 'watchlists_delta',
        );

        if (!defined('SYNC_HOST')) {
            return false;
        }
        $host = SYNC_HOST;
        return $this->cm->post($data, $host);
    }
}
