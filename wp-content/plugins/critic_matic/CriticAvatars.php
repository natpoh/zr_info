<?php

/*
 * TODO
 * parse avatar
 * get avatar sketch
 */

class CriticAvatars extends AbstractDB {

    private $cm;
    private $mp;
    // Audience
    public $source_dir = "ca/source";
    public $cketch_dir = "ca/sketch";
    public $tomato_dir = "ca/tomato";
    public $img_service = 'https://info.antiwoketomatoes.com/';
    public $thumb_service = 'https://img.zeitgeistreviews.com/';
    // Pro critic
    public $pro_source_dir = "cp/source";
    public $allowed_mime_types = [
        'image/jpeg' => '.jpg',
        'image/gif' => '.gif',
        'image/png' => '.png',
    ];

    public function __construct($cm = '') {
        $this->cm = $cm ? $cm : new CriticMatic();
        $table_prefix = DB_PREFIX_WP_AN;
        $this->db = array(
            'user_avatars' => 'data_user_avatars',
            'authors' => $table_prefix . 'critic_matic_authors',
        );
    }

    public function get_mp() {
        // Get movies parser
        if (!$this->mp) {
            if (!class_exists('MoviesLinks')) {
                !defined('MOVIES_LINKS_PLUGIN_DIR') ? define('MOVIES_LINKS_PLUGIN_DIR', ABSPATH . 'wp-content/plugins/movies_links/') : '';
                require_once( MOVIES_LINKS_PLUGIN_DIR . 'db/MoviesAbstractFunctions.php' );
                require_once( MOVIES_LINKS_PLUGIN_DIR . 'db/MoviesAbstractDB.php' );
                require_once( MOVIES_LINKS_PLUGIN_DIR . 'MoviesLinks.php' );
            }

            $ml = new MoviesLinks();
            // get parser
            $this->mp = $ml->get_mp();
        }
        return $this->mp;
    }

    function add_actions() {
        if (function_exists('add_filter')) {
            add_filter('get_avatar', array($this, 'get_avatar'), 9, 4);
        }
    }

    function add_default_avatar_option($avatars) {
        $avatars['neuro'] = __('Neuro');
        return $avatars;
    }

    function get_avatar($avatar, $user, $size, $default) {

        $email = '';
        $user_id = 0;
        if ($default == "neuro") {
            if (is_int($user)) {
                // User id
                $user_id = $user;
            } else if (is_string($user)) {
                // User email
                $email = $user;
            } else if (is_object($user)) {
                // Comment object
                if (isset($user->comment_author_email)) {
                    $email = $user->comment_author_email;
                }
            }
            if (!$user_id && $email) {
                // Get user
                if (function_exists('getUserByEmail')) {
                    $user_obj = getUserByEmail($email);
                    $user_id = $user_obj->id ? $user_obj->id : 0;
                }
            }

            // Check avatar type
            $author = $this->cm->get_author_by_wp_uid($user_id, true);

            if ($author->avatar_type == 1 && $author->avatar_name) {
                // Get upload avatar
                $avatar = $this->get_upload_user_avatar($author->id, $size, $author->avatar_name);
            } else {
                // Get avatar by code     
                $avatar = $this->get_or_create_user_avatar($user_id, 0, $size);
            }
        }
        return $avatar;
    }

    public function get_or_create_user_avatar($user_id = 0, $aid = 0, $size = 64, $type = 'tomato') {
        $img_path = '/wp-content/themes/custom_twentysixteen/images/antomface-150.jpg';

        if ($user_id) {
            $avatar_data = $this->get_avatar_by_uid($user_id);
        } else {
            $avatar_data = $this->get_avatar_by_aid($aid);
        }

        // Tomato avatars
        $tomato = 1;

        if (!$avatar_data) {
            // Create avatar link
            $avatar_data = $this->set_avatar_by_uid($user_id, $aid, $tomato);
        }

        if ($avatar_data) {
            $av_dir = $this->cketch_dir;
            $tomato_class = ' sketch';
            $img = $avatar_data->date . '.png';

            if ($type == 'tomato') {
                $av_dir = $this->tomato_dir;
                $tomato_class = ' tomato';
            } else if ($type == 'photo') {
                $av_dir = $this->source_dir;
                $tomato_class = ' photo';
                $img = $avatar_data->date . '.jpg';
            }

            $img_path = $this->img_service . 'wp-content/uploads/' . $av_dir . '/' . $img;
            $img_path = $this->get_avatar_thumb($img_path, $size);
        }


        $avatar = '<img class="neuro avatar' . $tomato_class . '" srcset="' . $img_path . '" width="' . $size . '" height="' . $size . '" />';
        return $avatar;
    }

    public function get_upload_user_avatar($aid = 0, $size = 64, $filename = '') {
        $img_path = $this->img_service . 'wp-content/uploads/' . $this->pro_source_dir . '/' . $filename;
        if ($size < 200) {
            $img_path = $this->get_avatar_thumb($img_path, $size);
        }
        $avatar = '<img class="neuro avatar upload" srcset="' . $img_path . '" width="' . $size . '" height="' . $size . '" />';
        return $avatar;
    }

    public function get_author_avatar($author, $av_size = 200) {
        // User   
        if ($author->avatar_type == 1 && $author->avatar_name) {
            $image = $this->get_upload_user_avatar($author->id, $av_size, $author->avatar_name);
        } else {
            $wp_uid = $author->wp_uid;
            if ($wp_uid) {
                $image = $this->get_or_create_user_avatar($wp_uid, 0, $av_size);
            } else {
                $image = $this->get_or_create_user_avatar(0, $author->id, $av_size);
            }
        }

        return $image;
    }

    public function get_avatar_thumb($img_path = '', $w = 200) {
        $result = $this->thumb_service . 'webp/' . $w . '/' . $img_path . '.webp';
        return $result;
    }

    function md5_hex_to_dec($hex_str) {
        $arr = str_split($hex_str, 4);
        foreach ($arr as $grp) {
            $dec[] = str_pad(hexdec($grp), 5, '0', STR_PAD_LEFT);
        }
        return implode('', $dec);
    }

    function smallHashCode($s) {
        $md = md5($s);

        $dec = $this->md5_hex_to_dec($md);
        $str = "" . $dec;
        $dec_arr = array();

        while ($str) {
            $first = substr($str . "", 0, 12);
            $dec_arr[] = $first;
            $str = str_replace($first, '', $str);
        }
        $ret = 0;
        if (count($dec_arr)) {
            foreach ($dec_arr as $value) {
                $ret += $value;
            }
        }

        if (strlen("" . $ret) > 12) {
            $ret = (int) substr("" . $ret . "", 0, 12);
        }

        return $ret;
    }

    public function ajax_random_avatar() {
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
        header('Content-type: application/json');
        header('Access-Control-Allow-Origin: *');

        $rtn = new stdClass();
        $rtn->success = false;

        $user = wp_get_current_user();
        $user_id = $user->exists() ? $user->ID : 0;
        $avatar = '';
        if ($user_id) {
            $size = 200;
            $img_path = '/wp-content/themes/custom_twentysixteen/images/antomface-150.jpg';

            // Get last data
            $last_avatar_data = $this->get_avatar_by_uid($user_id);

            $tomato = 1;
            $and_type = ' AND sketch=1';
            if ($tomato) {
                $and_type = ' AND tomato=1';
            }

            // Get random avatar
            $sql = "SELECT id, date FROM {$this->db['user_avatars']} WHERE uid=0 AND aid=0" . $and_type . " ORDER BY id ASC limit 1000";
            $results = $this->db_results($sql);
            if ($results) {
                shuffle($results);
                $avatar_data = array_pop($results);
                if ($avatar_data) {
                    // Create avatar link
                    $data = array('uid' => $user_id);
                    $this->sync_update_data($data, $avatar_data->id, $this->db['user_avatars'], true);
                }

                // Clear last data
                $this->clear_avatar_uid($last_avatar_data->id);
            }

            if ($avatar_data) {

                $av_dir = $this->cketch_dir;
                if ($tomato) {
                    $av_dir = $this->tomato_dir;
                }

                $img = $avatar_data->date . '.png';
                $img_path = $this->img_service . 'wp-content/uploads/' . $av_dir . '/' . $img;
                $img_path = $this->get_avatar_thumb($img_path, $size);
            }

            $avatar = $img_path;
            $rtn->success = true;
        }

        $rtn->avatar = $avatar;
        die(json_encode($rtn));
    }

    /*
     * Cron 
     */

    public function run_cron($cron_type = 1, $force = false, $debug = false, $count = 10) {
        if ($cron_type == 1) {
            // Parse avatar
            $this->parse_avatar($force, $debug);
        } else if ($cron_type == 2) {
            // Get sketch
            $this->get_sketch($force, $debug);
        } else if ($cron_type == 3) {
            // Get tomatoe
            $this->get_tomato($force, $debug, $count);
        }
    }

    public function parse_avatar($force = false, $debug = false) {
        $time = $this->curr_time();
        $url = 'https://thispersondoesnotexist.com/image';

        $file_content = $this->get_file_content($url, $debug);

        if ($file_content) {
            // This is an image?
            $src_type = $this->isImage($file_content);
            if (!$src_type) {
                return;
            }

            if (!isset($this->allowed_mime_types[$src_type])) {
                return;
            }

            $filename = $time . $this->allowed_mime_types[$src_type];


            // Check md5 hash
            $img_hash = md5($file_content);
            if ($debug) {
                p_r(array('md5' => $img_hash));
            }
            if ($this->get_avatar_by_hash($img_hash)) {
                if ($debug) {
                    print "The image hash already exist\n";
                }
                return;
            }

            // Save image           
            $source_dir = WP_CONTENT_DIR . '/uploads/' . $this->source_dir;
            if (class_exists('ThemeCache')) {
                ThemeCache::check_and_create_dir($source_dir);
            }

            $img_path = $source_dir . "/" . $filename;

            if (!file_exists($img_path)) {
                // Save file
                $fp = fopen($img_path, "w");
                fwrite($fp, $file_content);
                fclose($fp);
                /*
                 *  `date` int(11) NOT NULL DEFAULT '0',                                 
                  `img` int(11) NOT NULL DEFAULT '0',
                  `uid` int(11) NOT NULL DEFAULT '0',
                  `sketch` int(11) NOT NULL DEFAULT '0',
                  `img_hash` varchar(255) NOT NULL default '',
                 */
                // Add avatar to db
                $data = array(
                    'date' => $time,
                    'uid' => 0,
                    'sketch' => 0,
                    'img' => $filename,
                    'img_hash' => $img_hash,
                );

                $id = $this->sync_insert_data($data, $this->db['user_avatars'], $this->cm->sync_client, true, 20);
                if ($debug) {
                    p_r($data);
                    p_r($id);
                }
            }
        }
    }

    public function get_file_content($url, $debug) {
        $ip_limit = array('h' => 20, 'd' => 200);
        $tor_mode = 2;
        $file_content = '';
        if ($this->cm->sync_server) {

            $mp = $this->get_mp();
            $tp = $mp->get_tp();
            $max_errors=10;

            $file_content = $tp->get_url_content($url, $headers, $ip_limit, true, $tor_mode, false, array(), array(), $max_errors, $debug);
        } else {
            // 2. Get cm parser
            $cp = $this->cm->get_cp();
            $file_content = $cp->get_proxy($url, '', $headers);
            if ($debug) {
                print "CM parser\n";
            }
        }

        if ($debug) {
            p_r($headers);
        }

        return $file_content;
    }

    public function get_sketch($force = false, $debug = false) {
        // 1. Get last sketchs
        $progress_type = 2;
        $lasts_sk = $this->get_avatars_by_sketch($progress_type);
        $sketch_dir = WP_CONTENT_DIR . '/uploads/' . $this->cketch_dir;
        if ($debug) {
            p_r($lasts_sk);
        }
        if ($lasts_sk) {

            // Check sketch content
            foreach ($lasts_sk as $item) {
                $filename = $item->date . '.png';
                $img_path = $sketch_dir . "/" . $filename;
                if (file_exists($img_path)) {
                    // Add sketch exist type
                    if ($debug) {
                        p_r(array('exist' => $img_path));
                    }
                    $sketch_type = 1;
                } else {
                    // Reset sketch type
                    if ($debug) {
                        p_r(array('not exist' => $img_path));
                    }
                    $sketch_type = 0;
                }
                $data = array(
                    'sketch' => $sketch_type
                );
                $this->sync_update_data($data, $item->id, $this->db['user_avatars'], true, 20);
            }
        }

        // 2. Update next sketchs
        $sk_count = 5;
        $progress_type = 0;
        $sk_to_update = $this->get_avatars_by_sketch($progress_type, $sk_count);

        if ($debug) {
            p_r($sk_to_update);
        }

        if ($sk_to_update) {
            $upd_names = array();
            foreach ($sk_to_update as $item) {
                $upd_names[] = $item->date;
                $data = array(
                    'sketch' => 2
                );
                $this->db_update($data, $this->db['user_avatars'], $item->id);
            }

            $ids = '';
            if (sizeof($upd_names) > 1) {
                $ids = "&ids=" . implode(',', $upd_names);
            } else {
                $ids = "&id=" . $upd_names[0];
            }

            $url = "http://172.17.0.1:8331?p=ds1bfgFe_23_KJD" . $ids;

            if ($debug) {
                p_r($url);
            }

            $cp = $this->cm->get_cp();
            $cp->send_curl_no_responce($url);
        }
    }

    public function get_random_tomato($debug = false) {
        $dir = ABSPATH . 'analysis/imgtotomato/tomato_source/';
        $file_array = [];
        if ($handle = opendir($dir)) {
            while (false !== ($file = readdir($handle))) {
                if ($file != "." && $file != "..") {
                    $file_array[] = $file;
                }
            }
        }

        $count = count($file_array);
        $num = random_int(1, $count);
        $tomato = $dir . $file_array[$num];
        if ($debug) {
            p_r($tomato);
        }
        return $tomato;
    }

    public function create_tomato_image($img_path, $dst_path, $debug = false) {

        $watermark = ABSPATH . "analysis/imgtotomato/mask.png";
        $tomato = $this->get_random_tomato($debug);

        $img = imagecreatefrompng($img_path); //создаем исходное изображение

        $water_img = imagecreatefrompng($watermark); //создаем водный знак
        $tomato_img = imagecreatefrompng($tomato);

        imagecopy($img, $water_img, 0, 0, 0, 0, 1024, 1024); //накладываем водный знак на изображение по заданным координатам.
        imagetruecolortopalette($img, false, 2);
        $rgb = imagecolorat($img, 10, 10);
        // Получаем массив значений RGB
        $colors = imagecolorsforindex($img, $rgb);
        imagealphablending($img, true);
        imagesavealpha($img, false);

        $white = imagecolorexact($img, $colors["red"], $colors ["green"], $colors["blue"]);
        imagecolortransparent($img, $white);

        /// to tomato
        imagealphablending($tomato_img, true);
        imagesavealpha($tomato_img, true);
        imagecopy($tomato_img, $img, 0, 0, 0, 0, 1024, 1024); //накладываем водный знак на изображение по заданным координатам.
        imagesavealpha($img, true);

        // imagepng($tomato_img, 'result.png');
        // Set data
        imagepng($tomato_img, $dst_path);
        return 1;
    }

    public function get_tomato($force = false, $debug = false, $count = 10) {

        $sql = "SELECT * FROM {$this->db['user_avatars']} WHERE sketch = 1 AND tomato < 2 ORDER BY id ASC limit " . (int) $count;
        $results = $this->db_results($sql);
        if ($debug) {
            p_r($results);
        }
        if ($results) {
            foreach ($results as $item) {
                $img_name = $item->date . '.png';

                // Source path

                $img_path = WP_CONTENT_DIR . '/uploads/' . $this->cketch_dir . '/' . $img_name;
                if (!file_exists($img_path)) {
                    $img_dst = $this->img_service . 'wp-content/uploads/' . $this->cketch_dir . '/' . $img_name;
                    if ($debug) {
                        p_r(array('Get dst file', $img_dst));
                    }
                    $content = file_get_contents($img_dst);
                    if ($content) {
                        file_put_contents($img_path, $content);
                    }
                    if (!file_exists($img_path)) {
                        if ($debug) {
                            p_r(array('File not found', $img_path));
                        }
                        continue;
                    }
                }

                // Dst path
                $tomato_dir = WP_CONTENT_DIR . '/uploads/' . $this->tomato_dir;
                if (class_exists('ThemeCache')) {
                    ThemeCache::check_and_create_dir($tomato_dir);
                }
                $dst_path = $tomato_dir . '/' . $img_name;

                if ($debug) {
                    p_r(array($img_path, $dst_path));
                }

                $this->create_tomato_image($img_path, $dst_path, $debug);

                if (file_exists($dst_path)) {
                    if ($debug) {
                        p_r('Success');
                    }
                    // Update DB
                    $data = array(
                        'tomato' => 2
                    );
                    $this->db_update($data, $this->db['user_avatars'], $item->id);
                } else {
                    if ($debug) {
                        p_r('Error');
                    }
                }
            }
        }
    }

    public function get_avatar_by_uid($uid) {
        $sql = sprintf("SELECT * FROM {$this->db['user_avatars']} WHERE uid = %d", $uid);
        $result = $this->db_fetch_row($sql);
        return $result;
    }

    public function get_avatar_by_aid($aid) {
        $sql = sprintf("SELECT * FROM {$this->db['user_avatars']} WHERE aid = %d", $aid);
        $result = $this->db_fetch_row($sql);
        return $result;
    }

    public function set_avatar_by_uid($uid = 0, $aid = 0, $tomato = 0) {
        if (!$uid && !$aid) {
            return array();
        }
        $and_type = ' AND sketch = 1';
        if ($tomato) {
            $and_type = ' AND tomato = 1';
        }

        $sql = "SELECT * FROM {$this->db['user_avatars']} WHERE uid=0 AND aid=0" . $and_type;
        $result = $this->db_fetch_row($sql);
        if ($result) {
            $data = array();
            if ($uid) {
                $data['uid'] = $uid;
            } else {
                $data['aid'] = $aid;
            }
            $this->sync_update_data($data, $result->id, $this->db['user_avatars'], true);
        }
        return $result;
    }

    public function clear_avatar_uid($data_id = 0) {
        $data = array('uid' => 0);
        $this->sync_update_data($data, $data_id, $this->db['user_avatars'], true);
    }

    public function get_avatars_by_sketch($sketch = 2, $count = 0) {
        $and_count = '';
        if ($count > 0) {
            $and_count = sprintf(" limit %d", $count);
        }
        $sql = sprintf("SELECT * FROM {$this->db['user_avatars']} WHERE sketch = %d ORDER BY id ASC" . $and_count, $sketch);

        $result = $this->db_results($sql);
        return $result;
    }

    public function get_avatar_by_hash($img_hash) {
        $sql = sprintf("SELECT * FROM {$this->db['user_avatars']} WHERE img_hash = '%s'", $img_hash);
        $result = $this->db_fetch_row($sql);
        return $result;
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

    /*
     * Pro critic avatars    
     *  
     * 1. Change current paths to local avatars
     *  Add one time transit task
     *  Change show pro avatars to new api
     * 2. Upload module for critic avatar
     *  In single author page and list of authors
     * 3. Search avatars by critic channels: youtube, bichdue, odysee
     */

    public function transit_pro_avatars($count = 10, $debug = false, $force = false) {
        # 1. Get pro authors without images data.

        $option_name = 'transit_pro_avatars_id';
        $last_id = (int) $this->get_option($option_name, 0);
        if ($force) {
            $last_id = 0;
        }

        if ($debug) {
            p_r(array('last_id', $last_id));
        }

        $sql = sprintf("SELECT id FROM {$this->db['authors']} WHERE id>%d AND avatar=0 AND type=1 ORDER BY id ASC LIMIT %d", $last_id, $count);
        $authors = $this->db_results($sql);
        if ($debug) {
            print_r($authors);
        }
        if ($authors) {
            $last = end($authors);
            if ($debug) {
                print 'last id: ' . $last->id . "\n";
            }
            if ($last) {
                $this->update_option($option_name, $last->id);
            }
            # 1. Find image
            $ids = array();
            foreach ($authors as $author) {
                $ids[] = $author->id;
            }
            $this->find_pro_avatars($ids, $debug);

            # 2. Get image.
            $sql = "SELECT * FROM {$this->db['authors']} WHERE avatar=0 AND type=1 AND id IN(" . implode(',', $ids) . ")";
            $upd_authors = $this->db_results($sql);
            $this->pro_url_to_image($upd_authors, $debug);
        }
    }

    public function bulk_transit_pro_avatars($ids = array(), $debug = false) {
        # 1. Get pro authors without images data.
        $sql = "SELECT * FROM {$this->db['authors']} WHERE avatar=0 AND type=1 AND id IN(" . implode(',', $ids) . ")";
        $authors = $this->db_results($sql);
        if ($debug) {
            print_r($authors);
        }
        # 2. Get image.
        $this->pro_url_to_image($authors, $debug);
    }

    public function pro_url_to_image($authors, $debug = false) {
        $ret = array();
        foreach ($authors as $author) {
            $ret[$author->id] = 1;
            $options = unserialize($author->options);
            if (isset($options['image']) && $options['image']) {
                $img = $options['image'];

                # 3. Try to get image
                $file_content = $this->get_file_content($img, $debug);

                // This is an image?
                $src_type = $this->isImage($file_content, $debug);
                if (!$src_type) {
                    $ret[$author->id] = 0;
                    continue;
                }


                if (!isset($this->allowed_mime_types[$src_type])) {
                    $ret[$author->id] = 0;
                    continue;
                }

                $time = $this->curr_time();

                $filename = $author->id . '-' . $time . $this->allowed_mime_types[$src_type];


                // Save image           
                $source_dir = WP_CONTENT_DIR . '/uploads/' . $this->pro_source_dir;
                if (class_exists('ThemeCache')) {
                    ThemeCache::check_and_create_dir($source_dir);
                }

                $img_path = $source_dir . "/" . $filename;

                if (!file_exists($img_path)) {
                    // Save file
                    $fp = fopen($img_path, "w");
                    fwrite($fp, $file_content);
                    fclose($fp);

                    // Add avatar to db
                    $data = array(
                        'avatar' => 1,
                        'avatar_name' => $filename,
                        'last_upd'=>$this->curr_time(),
                    );

                    $id = $this->sync_update_data($data, $author->id, $this->db['authors']);
                    if ($debug) {
                        p_r($data);
                        p_r($id);
                    }
                }
            }
        }
        return $ret;
    }

    public function get_pro_avatar($filename = '') {
        $img_path = '';
        if ($filename) {
            $source_dir = $this->img_service . 'wp-content/uploads/' . $this->pro_source_dir;
            $img_path = $source_dir . "/" . $filename;
        }
        return $img_path;
    }

    public function get_pro_thumb($w = 100, $h = 100, $filename = '') {
        $ret = '';

        if ($filename) {
            $source_dir = $this->img_service . 'wp-content/uploads/' . $this->pro_source_dir;
            $img_path = $source_dir . "/" . $filename;
            //$service = 'https://rwt.4aoc.ru';
            $ret = $this->thumb_service . 'webp/' . $w . 'x' . $w . '/' . $img_path . '.webp';
        }
        return $ret;
    }

    public function ajax_pro_img() {
        $croped_image = isset($_POST['image']) ? $_POST['image'] : '';
        $author_id = (int) $_POST['author_id'];
        $no_upd = isset($_POST['no_upd']) ? true : false;
        $filename = isset($_POST['filename']) ? $_POST['filename'] : '';
        if ($filename) {
            return $this->update_author_file($author_id, $filename);
        }

        if (isset($_POST['change_type'])) {
            $av_type = (int) $_POST['av_type'];
            $av_size = (int) $_POST['av_size'];
            return $this->change_author_type($author_id, $av_type, $av_size = 200);
        }



        list($type, $croped_image) = explode(';', $croped_image);
        list(, $croped_image) = explode(',', $croped_image);
        $file_content = base64_decode($croped_image);

        $ret = array();

        // This is an image?
        $src_type = $this->isImage($file_content);
        if (!$src_type) {
            $ret['error'] = 1;
            $ret['reason'] = 'This is no image';
            return json_encode($ret);
        }

        if (!isset($this->allowed_mime_types[$src_type])) {
            $ret['error'] = 1;
            $ret['reason'] = 'This image type is not allowed';
            return json_encode($ret);
        }

        $time = $this->curr_time();

        $filename = $author_id . '-' . $time . $this->allowed_mime_types[$src_type];

        // Save image           
        $source_dir = WP_CONTENT_DIR . '/uploads/' . $this->pro_source_dir;
        if (class_exists('ThemeCache')) {
            ThemeCache::check_and_create_dir($source_dir);
        }

        $img_path = $source_dir . "/" . $filename;

        if (file_exists($img_path)) {
            unlink($img_path);
        }

        // Remove old avatar
        $this->remove_old_pro_avatar($author_id);

        // Save file
        $fp = fopen($img_path, "w");
        fwrite($fp, $file_content);
        fclose($fp);

        $ret['filename'] = $filename;

        if ($no_upd) {
            // No update. Only return filename.
            return json_encode($ret);
        }
        // Add avatar to db
        $data = array(
            'avatar' => 1,
            'avatar_type' => 1,
            'avatar_name' => $filename,
            'last_upd'=>$this->curr_time(),
        );

        $this->sync_update_data($data, $author_id, $this->db['authors']);

        return json_encode($ret);
    }

    private function update_author_file($author_id, $filename) {
        // Add avatar to db
        $data = array(
            'avatar' => 1,
            'avatar_type' => 1,
            'avatar_name' => $filename,
            'last_upd'=>$this->curr_time(),
        );

        $this->sync_update_data($data, $author_id, $this->db['authors']);
        $ret = array(
            'success' => 1,
        );

        return json_encode($ret);
    }

    private function change_author_type($author_id, $av_type, $size) {
        // Add avatar to db
        $data = array(
            'avatar_type' => $av_type,
            'last_upd'=>$this->curr_time(),
        );

        $this->sync_update_data($data, $author_id, $this->db['authors']);

        // Check avatar type
        $author = $this->cm->get_author($author_id);

        if ($author->avatar_type == 1 && $author->avatar_name) {
            // Get upload avatar
            $avatar = $this->get_upload_user_avatar($author->id, $size, $author->avatar_name);
        } else {
            // Get avatar by code     
            $avatar = $this->get_or_create_user_avatar($author->wp_uid, 0, $size);
        }

        return $avatar;
    }

    public function remove_old_pro_avatar($aid) {
        $author = $this->cm->get_author($aid);
        if ($author && $author->avatar_name) {
            $source_dir = WP_CONTENT_DIR . '/uploads/' . $this->pro_source_dir;
            $img_path = $source_dir . "/" . $author->avatar_name;
            if (file_exists($img_path)) {
                unlink($img_path);
                return true;
            }
        }
        return false;
    }

    public function find_pro_avatars($ids, $debug = false) {
        // Find and add avatars for pro critic campaings
        if ($ids) {
            # 1. Get sites ids
            $sites = array('youtube.com', 'bitchute.com', 'odysee.com');
            $sites_ids = $this->cm->get_post_links_by_names($sites);
            if ($debug) {
                print_r(array('Sites_ids:', $sites_ids));
            }
            foreach ($ids as $aid) {
                # 1. Load author
                $author = $this->cm->get_author($aid);
                if ($author->type == 1) {
                    # Only pro critic
                    if ($author->avatar == 0) {
                        # Only empty avatar
                        $avatar_url = '';
                        foreach ($sites as $site) {
                            # 2. Find avatar in sites
                            $site_key = isset($sites_ids[$site]) ? $sites_ids[$site] : 0;
                            if ($site_key) {
                                if ($debug) {
                                    print_r(array('Site:', $site, $site_key));
                                }
                                # Get avatar by site api
                                $post = $this->cm->get_author_post_link_by_site($aid, $site_key);


                                if ($post) {
                                    if ($debug) {
                                        print_r(array('Post:', $post->link));
                                    }
                                    if ($site == 'youtube.com') {
                                        # Get by youtube
                                        $avatar_url = $this->get_avatar_from_youtube($post->link);
                                    } else if ($site == 'bitchute.com') {
                                        # Get by bitchute
                                        $avatar_url = $this->get_avatar_from_bitchute($post->link);
                                    }

                                    if ($avatar_url) {
                                        break;
                                    }
                                }
                            }
                        }

                        if ($debug) {
                            print_r(array('Avatar URL:', $avatar_url));
                        }

                        if ($avatar_url) {
                            # 3. Update author
                            $author_opt = unserialize($author->options);
                            $author_opt['image'] = $avatar_url;
                            $author->options = $author_opt;
                            $this->cm->update_author($author);

                            # 4. Upload avatar
                            $author_upd = $this->cm->get_author($aid);
                            $authors = array($author_upd);
                            $this->pro_url_to_image($authors, $debug);
                        }
                    }
                }
            }
        }
    }

    private function get_avatar_from_youtube($url = '') {
        if (!$url) {
            return '';
        }

        $avatar_url = '';
        $code = $this->get_by_webdriver($url);
        if ($code) {
            # "channelAvatar":{"thumbnails":[{"url":"https://yt3.ggpht.com/ytc/AGIKgqMpMWaZ54cB3hH8RuKkKK2uP4DZHjpwzgzfV602MA=s88-c-k-c0x00ffffff-no-rj"}]}
            if (preg_match('/"channelAvatar":{"thumbnails":\[{"url":"([^"]+)"}\]/', $code, $match)) {
                $avatar_url = $match[1];
            }
        }
        return $avatar_url;
    }

    private function get_avatar_from_bitchute($url = '') {
        if (!$url) {
            return '';
        }

        $avatar_url = '';
        $code = $this->get_by_webdriver($url);
        if ($code) {
            # <img class="image lazyload" src="/static/v141/images/loading_small.png" data-src="https://static-3.bitchute.com/live/channel_images/ZMv79MtHJ9al/qOAV1UTaMWe66EQEI3YhwtUf_small.jpg" onerror="this.src='/static/v141/images/blank_small.png';this.onerror='';" alt="channel image">
            if (preg_match('/<img [^>]+data-src="([^"]+)"[^>]+ alt="channel image">/', $code, $match)) {
                $avatar_url = $match[1];
            }
        }
        return $avatar_url;
    }

    private function get_by_webdriver($url, $parse_mode = 3, $tor_mode = 2) {
        // $parse_mode=3 - Tor curl
        // $tor_mode=2 - Proxy
        $mp = $this->get_mp();
        $mp_settings = $mp->get_settings();
        $service_urls = array(
            'webdrivers' => $parse_mode,
            'del_pea' => 0,
            'del_pea_cnt' => 10,
            'tor_h' => 20,
            'tor_d' => 100,
            'tor_mode' => $tor_mode,
            'progress' => 0,
            'weight' => 0,
        );
        $code = $mp->get_code_by_current_driver($url, $headers, $mp_settings, $service_urls);
        return $code;
    }

}
