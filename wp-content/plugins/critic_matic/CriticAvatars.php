<?php

/*
 * TODO
 * parse avatar
 * get avatar sketch
 */

class CriticAvatars extends AbstractDB {

    private $cm;
    public $source_dir = "ca/source";
    public $cketch_dir = "ca/sketch";

    public function __construct($cm = '') {
        $this->cm = $cm ? $cm : new CriticMatic();
        $table_prefix = DB_PREFIX_WP;
        $this->db = array(
            'user_avatars' => 'data_user_avatars',
        );
    }

    public function run_cron($cron_type = 1, $force = false, $debug = false) {

        if ($cron_type == 1) {
            // Parse avatar
            $this->parse_avatar($force, $debug);
        } else {
            // Get sketch
            $this->get_sketch($force, $debug);
        }
    }

    public function parse_avatar($force = false, $debug = false) {
        $time = $this->curr_time();
        $url = 'https://thispersondoesnotexist.com/image';
        $ip_limit = array('h' => 20, 'd' => 200);
        $tor_mode = 2;
        $file_content = '';
        if ($this->cm->sync_server) {
            if (!class_exists('MoviesLinks')) {
                require_once( MOVIES_LINKS_PLUGIN_DIR . 'db/MoviesAbstractFunctions.php' );
                require_once( MOVIES_LINKS_PLUGIN_DIR . 'db/MoviesAbstractDB.php' );
                require_once( MOVIES_LINKS_PLUGIN_DIR . 'MoviesLinks.php' );
                require_once( MOVIES_LINKS_PLUGIN_DIR . 'TorParser.php' );
            }
            // 1. Get ml parser
            if ($debug) {
                print "ML parser\n";
            }
            $tp = new TorParser();
            $file_content = $tp->get_url_content($url, $headers, $ip_limit, true, $tor_mode, false, array(), array(), $debug);
        } else {
            // 2. Get cm parser
            $cp = $this->cm->get_cp();
            $file_content = $cp->get_proxy($url, '', $headers);
            if ($debug) {
                print "CM parser\n";
            }
        }

        //print_r($code);
        if ($debug) {
            p_r($headers);
        }

        if ($file_content) {
            // This is an image?
            $src_type = $this->isImage($file_content);
            if (!$src_type) {
                return;
            }

            $allowed_mime_types = [
                'image/jpeg' => '.jpg',
                'image/gif' => '.gif',
                'image/png' => '.png',
            ];
            if (!isset($allowed_mime_types[$src_type])) {
                return;
            }

            $filename = $time . $allowed_mime_types[$src_type];


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
                $filename = $item->date.'.png';
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
            if (sizeof($upd_names)>1){
                $ids = "&ids=" . implode(',', $upd_names);
            } else {
                $ids = "&id=" . $upd_names[0];
            }

            $url = "http://172.17.0.1:8331?p=ds1bfgFe_23_KJD".$ids;
            
            if ($debug) {
                p_r($url);
            }

            $cp = $this->cm->get_cp();
            $cp->send_curl_no_responce($url);
        }
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

    public function isImage($temp_file) {
        //Провереяем, картинка ли это
        @list($src_w, $src_h, $src_type_num) = array_values(getimagesizefromstring($temp_file));
        $src_type = image_type_to_mime_type($src_type_num);

        if (empty($src_w) || empty($src_h) || empty($src_type)) {
            return '';
        }
        return $src_type;
    }

}
