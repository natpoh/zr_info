<?php

/**
 * Description of UserCarma
 *
 * @author brahman
 */
/*
 * 
 *  * 
 * TODO
 * Add modules

 * !guest-login - вход без пароля
 * !usercp (t) - профиль пользователя
 * !simple-activity(t) - входы пользователей
 * simple_edit_comment(t) - редактирование комментариев
 * unotify (t) - уведомления о комментариях
 * users_ban (t) - бан пользователей
 * 
 * Before activation modules need activate theme classes: cache, comments, etc.
 * * ?user-bl - взаимные блокировки пользователей (пока не надо)
 * 
 * 1. cm_activation
 * 2. Activate pluggins 
 * 3. Rewrite rules: /wp-admin/options-permalink.php
 * 4. Allow user registration: /wp-admin/options-general.php
 * 
 * TODO
 * +1. Страницы профиля пользователей
 * +2. Поля формы комментариев
 * +3. Логин пользователя, аякс
 * +4. Аватарки пользователя в профиле и ревью
 * +5. Рейтинг пользователя за эмоции
 * +6. Ссылка на профиль пользователя в админке
 * +8. Передача постов пользователю. Вывод только пользователей с постами
 * +9. Админ бар загружать только для администратора
 * +10. Ссылка на профиль пользователя по имени
 * +11. Отображение рейтинга и кармы пользователя
 * +12. Запись активности пользователя при голосовании за эмоции
 * +13. Галочка для анонимного ревью
 * +14. Иконки для анонимов
 * -15. Поставить правильные заголовки и шрифты
 * +16. Смена аватара
 * 
 * 
 * Ошибки:
 * +1. Проверить фильтры поиска ревью
 * +2. Проверить ссылку logout
 * +3. Проверить ночную тему
 * 
 * 
 */
class UserCarma extends AbstractDBWp {

    private $cm;
    private $db;

    public function __construct($cm = '') {
        $this->cm = $cm ? $cm : new CriticMatic();
        $table_prefix = DB_PREFIX_WP;
        $this->db = array(
            'posts' => DB_PREFIX_WP_AN . 'critic_matic_posts',
            // User carma
            'users' => $table_prefix . 'users',
            'carma' => $table_prefix . 'carma',
            'carma_trend' => $table_prefix . 'carma_trend',
            'carma_log' => $table_prefix . 'carma_log',
            'ips' => $table_prefix . 'ips',
            'user_names' => $table_prefix . 'user_names',
        );
    }

    function getCarma($id) {
        $sql = sprintf("SELECT rating, carma FROM {$this->db['carma']} WHERE uid = %d", (int) $id);
        $result = $this->db_fetch_row($sql);

        if ($result)
            return array($result->rating, $result->carma);
        else
            return array(0, 0);
    }
    
    
    function getCarmaTrend($id, $limit=100) {
        $sql = sprintf("SELECT id, rating, carma, date_added FROM {$this->db['carma_trend']} WHERE uid = %d ORDER BY date_added DESC LIMIT %d", (int) $id, $limit);
        $result = $this->db_results($sql);
        return $result;
    }

    public function getCarmaLog($id, $limit=100) {
                $sql = sprintf("SELECT * FROM {$this->db['carma_log']} WHERE uid = %d ORDER BY date_added DESC LIMIT %d", (int) $id, $limit);
        $result = $this->db_results($sql);
        return $result;
    }
    
    function emotions_rating($wp_uid = 0, $vote_value = 1, $post_id=0, $ratingback = false) {

        $data = $this->getUserById($wp_uid);
        $rating = $data->rating + abs($vote_value);
        $carma = $data->carma;
        
        
        $vote_log_value = abs($vote_value);
        if ($ratingback) {
            $rating = $data->rating - abs($vote_value);
            $vote_log_value = - abs($vote_value);
        }

        // Update rating
        $this->updateUserRating($wp_uid, $rating, $carma);

        // Add log
        if ($ratingback) {
            $vote_value = - abs($vote_value);
        }

        $this->add_rating_log($wp_uid, $vote_log_value, 0, 1, $post_id, $ratingback);
    }

    function updateUserRating($uid, $rating = 0, $carma = 0) {

        $sql = sprintf("SELECT id FROM {$this->db['carma']} carma WHERE uid = %d", (int) $uid);
        $row_id = $this->db_get_var($sql);

        if ($row_id) {
            // Data exist. Update
            $data = array(
                'rating' => (int) $rating,
                'carma' => (int) $carma,
            );
            $this->db_update($data, $this->db['carma'], $row_id);
        } else {
            // Add data
            $data = array(
                'rating' => (int) $rating,
                'carma' => (int) $carma,
                'uid' => (int) $uid
            );
            $this->db_insert($data, $this->db['carma']);
        }

        // Add trend
        $date_added = $this->curr_time();
        $data = array(
            'rating' => (int) $rating,
            'carma' => (int) $carma,
            'uid' => (int) $uid,
            'date_added' => (int) $date_added
        );
        $this->db_insert($data, $this->db['carma_trend']);
    }

    public function add_rating_log($uid, $rating = 0, $carma = 0, $type = 0, $post_id = 0, $ratingback = false) {
        // Dst user
        $user_id = 0;
        if (function_exists('wp_get_current_user')) {
            $user = wp_get_current_user();
            $user_id = $user->exists() ? $user->ID : 0;
        }

        // Dst ip
        $ip_id = 0;
        $ip = $this->cm->get_remote_ip();
        if ($ip) {
            $ip_item = $this->cm->get_or_create_ip($ip);
            if ($ip_item) {
                $ip_id = $ip_item->id;
            }
        }

        // Add to log
        $date_added = $this->curr_time();
        $data = array(
            'uid' => (int) $uid,
            'rating' => (int) $rating,
            'carma' => (int) $carma,
            'date_added' => (int) $date_added,
            'type' => (int) $type,
            'dst_uid' => (int) $user_id,
            'dst_ip' => (int) $ip_id,
            'rating_back' => $ratingback ? 1 : 0,
            'post_id' => (int) $post_id,
        );
        $this->db_insert($data, $this->db['carma_log']);
    }

    function getUsersByCookie($email) {
        // TODO refactor. Need from usercp. UNUSED
        return array();
        global $wpdb;
        $users = array();
        $sql = sprintf("SELECT DISTINCT cookie FROM wp_guests_meta WHERE email='%s' and cookie != 0", $email);
        $cookies = $wpdb->get_results($sql);
        $clist = array();
        $elist = array();
        foreach ($cookies as $cobj) {
            $clist[] = $cobj->cookie;
        }

        $sql = "SELECT DISTINCT email FROM wp_guests_meta WHERE cookie IN ('" . join("','", $clist) . "')";
        $emails = $wpdb->get_results($sql);
        if ($emails) {
            foreach ($emails as $cemail) {
                $elist[] = $cemail->email;

                if (strtolower($cemail->email) == strtolower($email))
                    continue;

                /* $guest = getGuestByEmail($cemail->email);
                  if ($guest)
                  $users[] = $guest; */

                $user = $this->getUserByEmail($cemail->email);
                if ($user)
                    $users[] = $user;
            }
            return array(users => $users, cookie => $clist, emails => $elist);
        }
    }

    // Guest functions
    function getUsersByIp($email, $uid) {
        // Need from usercp. TODO refactor. UNUSED
        global $wpdb, $table_prefix;

        $ipsource = array();
        $users = array();
        $locations = array();
        $iplist = array();
        $proxy = array();

        //Opera
        $proxyArr = array(
            '#^82\.#', '#^80\.239#', '#^217\.212#', '#^85\.26\.18#', '#^185\.26\.182#'
        );

        if (class_exists('SimpleActivity')) {
            $sim_act = new SimpleActivity();

            if ($uid > 0) {
                //Получаем IP адреса пользователя из его активности
                if (function_exists('simple_act_init')) {
                    $ips_act = $sim_act->get_user_ips($uid, 10);
                    if (sizeof($ips_act) > 0) {
                        foreach ($ips_act as $ipobj) {
                            $ip = $ipobj->ip;
                            $ipsource[$ip] = $ip;
                        }
                    }
                }
            }
        }

        if (sizeof($ipsource) > 0) {

            foreach ($ipsource as $ip) {
                $continue = false;

                //Пропуск соседей по прокси

                foreach ($proxyArr as $proxyItem) {
                    if (preg_match($proxyItem, $ip)) {
                        $pkey = 'Опера турбо';
                        $proxy[$pkey] = $pkey;
                        $continue = true;
                        break;
                    }
                }


                if ($continue == true) {
                    continue;
                }


                if (preg_match('#^46\.246\.76#', $ip)) {
                    $pkey = 'Proxy';
                    $proxy[$pkey] = $pkey;
                    continue;
                }


                $iplist[] = $ip;

                global $cfront;
                $ccode = '';
                if ($cfront) {
                    $geo2 = $cfront->cm->getGeoData($ip);
                    $ccode = isset($geo2['country_code']) ? $geo2['country_code'] : '';
                }
                if ($ccode) {
                    $res = new stdClass();
                    $res->country_code = $ccode;

                    $locations[$ccode] = array($res, $ip);
                }
            }

            //Получаем пользователей по IP
            if ($uid > 0) {
                $u_act = $sim_act->get_users_by_ips($iplist);

                if (count($u_act)) {
                    foreach ($u_act as $u) {
                        if ($uid == $u->user_id) {
                            continue;
                        }
                        $user = $this->getUserById($u->user_id);
                        if ($user) {
                            $users[] = $user;
                        }
                    }
                }
            }
        }

        return array('users' => $users, 'proxy' => $proxy, 'loc' => $locations, 'iplist' => $iplist);
    }

    function get_user_profile_link($link) {
        return '/author/' . $link;
    }

    function getUserById($id) {
        $sql = "SELECT u.ID AS id, u.display_name AS name, u.user_nicename AS url, c.rating AS rating, c.carma AS carma "
                . "FROM {$this->db['users']} u "
                . "LEFT JOIN {$this->db['carma']} c ON c.uid = u.ID "
                . "WHERE u.ID=" . (int) $id;

        $result = $this->db_fetch_row($sql);
        return $result;
    }

    function getUserByEmail($email) {
        $sql = sprintf("SELECT u.ID AS id, u.display_name AS name, u.user_nicename AS url, c.rating AS rating, c.carma AS carma "
                . "FROM {$this->db['users']} u "
                . "LEFT JOIN {$this->db['carma']} c ON c.uid = u.ID "
                . "WHERE u.user_email='%s'", $this->escape($email));

        $result = $this->db_fetch_row($sql);
        return $result;
    }

    public function wp_author_name_used($name = '') {
        if (!$name) {
            return '';
        }
        $name_hash = sha1($name);
        $sql = sprintf("SELECT id FROM {$this->db['user_names']} WHERE name_hash = %d", $name_hash);
        $result = $this->db_get_var($sql);
        return $result;
    }

    public function wp_author_add_name($name = '', $uid = 0) {
        if (!$name || !$uid) {
            return '';
        }
        if ($this->wp_author_name_used($name)) {
            return '';
        }

        $name_hash = sha1($name);
        $data = array(
            'uid' => $uid,
            'date' => $this->curr_time(),
            'name' => $name,
            'name_hash' => $name_hash,
        );
        $this->db_insert($data, $this->db['user_names']);
    }

    public function set_author_names() {
        // Get users and names. Set names
        $sql = "SELECT u.ID AS id, u.display_name AS name, u.user_nicename AS url, c.rating AS rating, c.carma AS carma "
                . "FROM {$this->db['users']} u "
                . "LEFT JOIN {$this->db['carma']} c ON c.uid = u.ID ";

        $result = $this->db_results($sql);
        if ($result) {
            foreach ($result as $user) {
                $this->wp_author_add_name($user->name, $user->id);
            }
        }
    }

}
