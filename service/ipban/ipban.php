<?php
// Обычная атака 90 диапазонов по 20 - 65 адресов.
// Возьмём 10 диапазонов по 15 адресов.
define('SHOW_IP_MASK', 0);
define('DDOS_MAX_IP_MASKS', 10);
define('DDOS_MAX_IP_MASK_ADDRESS', 15);
define('DDOS_MAX_GET', 180);
define('DDOS_MAX_POST', 60);
define('DDOS_POST_BAN', 600);
define('ACCESS_LOG_PATH', '/var/log/nginx/access.log');

if (!defined('ABSPATH'))
    define('ABSPATH', dirname(__FILE__, 3) . '/');


if (!defined('BAN_INFO_TEMP_FOLDER'))
    define('BAN_INFO_TEMP_FOLDER', ABSPATH . 'wp-content/uploads/baninfo');
!defined('DB_HOST_AN') ? include ABSPATH . 'an_config.php' : '';

//Abstract DB
if (!class_exists('Pdoi')) {
    include ABSPATH . "service/ipban/db/Pdoi.php";
    include ABSPATH . "service/ipban/db/AbstractFunctionsi.php";
    include ABSPATH . "service/ipban/db/AbstractDBi.php";
}

/* TODO
 * Создаём базу ип адресов
 * Добавляем туда адреса с датой и доменом
 * Добавляем сборщик мусора
 * Сохраняем адреса в отдельную папку для доменов в виде списка 
 */

class IpBanService extends AbstractDBi {

    private $db;
    private $allowhosts = array('yandex', 'google.com', 'miralinks.ru');
    private $white_ip_list = array('198.27.70.142', '198.27.68.101', '148.251.54.53', '127.0.0.1', '172.17.0.1');
    private $post_ban_list = array('/p-login.php');
    private $ban_time_sec = 86400;
    private $allow_get_path = array('/album/');
    private $allow_get_domains = array('img.');
    private $mail_notifi = 0;
    private $nginx_rules = 0;

    function __construct() {
        $this->db = array(
            'blacklist' => 'ipban_blacklist',
        );
        $this->mail_notifi = defined('EMAIL_NOTIFI_ENABLE') ? EMAIL_NOTIFI_ENABLE : 0;
        $this->mail_from = defined('EMAIL_NOTIFI_FROM') ? EMAIL_NOTIFI_FROM : '';
        $this->mail_to = defined('EMAIL_NOTIFI_TO') ? EMAIL_NOTIFI_TO : array();
    }

    public function run_cron($debug = false) {
        $dataServer = $this->getLogData();
        if (sizeof($dataServer) > 0) {
            // Get config
            ob_start();
            foreach ($dataServer as $domainhost => $data) {
                print "<h1>$domainhost - " . sizeof($data) . "</h1>";
                $this->detectAttack($data, $domainhost);
                $this->renderLogData($data);
            }
            $content = ob_get_contents();
            ob_end_clean();
            if ($debug) {
                $this->renderLog($content);
            }
        }
    }

    private function getLogData() {
        $f = fopen(ACCESS_LOG_PATH, "r");
        $hosts = array();
        $result = array();
        if ($f) {

            if (fseek($f, -1, SEEK_END) == 0) {//в конец файла -1 символ перевода строки
                $len = ftell($f);

                $offset = -2;
                $max_len = 5000000;
                $time_offset = 60 * 1; //1 мин.
                $time_log_first = '';

                //Ищим начало строки
                $stroka = '';
                for ($i = $len; $i > ($len - $max_len); $i--) {//5000 - предполагаемая макс. длина строки
                    $seec = fseek($f, $offset, SEEK_CUR);
                    if ($seec == -1) {
                        break;
                    }

                    $read = fread($f, 1);

                    if ($read == "\n") {//если встретился признак конца строки
                        $item = explode("|", $stroka);

                        $time_log = $item[1];
                        $time = strtotime($time_log);

                        if (!$time_log_first) {
                            $time_log_first = $time;
                        } else {
                            //print "$time_log_first $time<br />";
                            if (($time_log_first - $time_offset) > $time) {
                                break;
                            }
                        }

                        /* Log data
                          '$proxy_add_x_forwarded_for|$time_local|'
                          '$status|$request_length|$bytes_sent|$request_time|'
                          '$request|$http_referer|$http_user_agent';
                         */

                        $http_host = isset($item[9]) ? $item[9] : 'local';
                        $ip_raw = $item[0];
                        $ip = $ip_raw;
                        if (strstr($ip_raw, ',')) {
                            $ip_raw_arr = explode(',', $ip_raw);
                            $ip = trim($ip_raw_arr[0]);
                        }

                        $item_names = array(
                            'ip' => $ip,
                            'ip_raw' => $ip_raw,
                            'time_local' => $item[1],
                            'status' => $item[2],
                            'request_length' => $item[3],
                            'bytes_sent' => $item[4],
                            'request_time' => $item[5],
                            'request' => $item[6],
                            'http_referer' => $item[7],
                            'http_user_agent' => $item[8],
                            'http_host' => $http_host,
                        );

                        $hosts[$http_host] += 1;
                        $result[$http_host][] = $item_names;


                        $stroka = '';
                    } else {
                        $stroka = $read . $stroka;
                    }
                }
            }

            fclose($f);
        }
        arsort($hosts);
        $ret = array();
        foreach ($hosts as $key => $value) {
            if ($value > 1) {
                $ret[$key] = $result[$key];
            }
        }

        return $ret;
    }

    private function detectAttack($data, $http_host) {
        $max_post = 0;
        $max_get = 0;
        $attack = false;
        $mask_attack = false;

        if (sizeof($data)) {
            //Получаем недостающие данные
            $u_ip = array();
            $u_ip_req = array();
            $masks = array();
            $mask_ip = array();

            foreach ($data as $item) {

                //Ip count
                $ip = $item['ip'];
                $u_ip_req[$ip][] = $item['http_referer'] . ' -> ' . $item['request'];
                if (!$u_ip[$ip]) {
                    $u_ip[$ip] = $item;
                }
                $u_ip[$ip]['count'] += 1;
            }

            $bots = $this->useronline_get_bots_custom();
            $hostarr = array();

            if ($u_ip) {
                foreach ($u_ip as $ip => $item) {
                    if (in_array($ip, $this->white_ip_list)) {
                        continue;
                    }

                    //Docker local
                    if (strstr($ip, '172.17.')) {
                        continue;
                    }

                    //Already in bl
                    if (strstr($ips_in_bl, $ip)) {
                        continue;
                    }

                    //Атака POST (Информируем)
                    if (strstr($item['request'], 'POST')) {
                        if ($item['count'] > $max_post) {
                            $max_post = $item['count'];
                        }

                        if ($item['count'] > DDOS_MAX_POST) {
                            // Check For Bot
                            $bot_found = false;
                            foreach ($bots as $name => $lookfor) {
                                if (stristr($item['http_user_agent'], $lookfor) !== false) {
                                    $bot_found = $name;
                                    break;
                                }
                            }


                            $bot_name = '';

                            if ($bot_found) {
                                $bot_name = "Представился как бот: $bot_found\n";
                                $ban = false;
                                //continue;
                            }

                            //Host
                            if (isset($hostarr[$ip])) {
                                $host = $hostarr[$ip];
                            } else {
                                $host = $this->gethostbyaddrCache($ip);
                                if ($host) {
                                    $host = $ip;
                                }
                                $hostarr[$ip] = $host;
                            }

                            foreach ($this->allowhosts as $ahost) {
                                if (strstr($host, $ahost)) {
                                    $ban = false;
                                    break;
                                }
                            }

                            $ban = false;

                            if ($item['count'] > DDOS_POST_BAN) {
                                // Больше 600 запросов в минуту, 10 запросов в секунду.
                                $ban = true;
                            }

                            foreach ($this->post_ban_list as $value) {
                                if (strstr($item['request'], $value)) {
                                    $ban = true;
                                    break;
                                }
                            }

                            $add = true;
                            $bantext = '';

                            if ($ban) {
                                $add = $this->add_to_blacklist($ip, $http_host, 1, $this->ban_time_sec);
                                $bantext = 'ЗАБАНЕН';
                            }

                            //post spam
                            print '<h3>Атака POST ' . $ip . '. Req/min ' . $item['count'] . '</h3>';

                            $title = 'Атака POST';
                            if ($ban) {
                                $title = 'Атака POST -> Бан';
                            }

                            $title .= ' IP: ' . $ip . '. Req/min: ' . $item['count'] . '. ' . $item['http_host'];

                            $item_string = '';
                            foreach ($item as $key => $value) {
                                $item_string .= "$key => $value\n";
                            }

                            $body = 'Много запросов POST с ' . $ip . '. Req/min ' . $item['count'] . " $bantext\nХост:$host\n$bot_name" . $item_string;

                            if ($add && $item['status'] != 403 && $this->mail_notifi) {
                                $this->send_mail_attack($title, $body, $http_host);
                            }

                            $attack = true;
                        }
                    } else {
                        //Атака GET Баним
                        $bantext = '';

                        if ($item['count'] > $max_get) {
                            $max_get = $item['count'];
                        }

                        if ($item['count'] > DDOS_MAX_GET) {

                            // Check For Bot
                            $bot_found = false;
                            foreach ($bots as $name => $lookfor) {
                                if (stristr($item['http_user_agent'], $lookfor) !== false) {
                                    $bot_found = $name;
                                    break;
                                }
                            }

                            $bot_name = '';

                            if ($bot_found) {
                                $bot_name = "Представился как бот: $bot_found\n";
                                //continue;
                            }

                            $host = $this->gethostbyaddrCache($ip);

                            $add = true;
                            $ban = true;

                            if ($bot_found) {
                                $ban = false;
                                $bantext = 'бот';
                            }
                            $requests = "Requests:\n" . implode("\n", $u_ip_req[$ip]);

                            //Белый список адресов запроса
                            foreach ($this->allow_get_path as $path) {
                                if (strstr($requests, $path)) {
                                    $ban = false;
                                    $bantext = $path;
                                    break;
                                }
                            }

                            //Баним
                            if ($ban) {
                                foreach ($this->allowhosts as $ahost) {
                                    if (strstr($host, $ahost)) {
                                        $ban = false;
                                        break;
                                    }
                                }
                            }

                            //Белый список доменов                            
                            if ($ban) {
                                foreach ($this->allow_get_domains as $ahost) {
                                    if (strstr($http_host, $ahost)) {
                                        $ban = false;
                                        $add = false;
                                        break;
                                    }
                                }
                            }

                            if ($ban) {
                                $add = $this->add_to_blacklist($ip, $http_host, 1, $this->ban_time_sec);
                                $bantext = 'ЗАБАНЕН';
                            }

                            //много обращений
                            print '<h3>Атака "Много обращений" ' . $ip . '. Req/min ' . $item['count'] . '</h3>';


                            $title = 'Атака GET " ';
                            if ($ban) {
                                $title = 'Атака GET -> Бан ';
                            }
                            if ($bot_found) {
                                $title = 'Атака Бота GET ';
                            }

                            $title .= ' IP: ' . $ip . '. Req/min: ' . $item['count'] . '. ' . $item['http_host'];

                            $item_string = '';
                            foreach ($item as $key => $value) {
                                $item_string .= "$key => $value\n";
                            }

                            $body = 'Много обращений с ' . $ip . '. Req/min ' . $item['count'] . " $bantext\nХост:$host\n$bot_name" . $item_string . $requests;

                            if ($add && $item['status'] != 403 && $this->mail_notifi) {
                                $this->send_mail_attack($title, $body, $http_host);
                            }
                        }
                    }
                }

                //Masks attack
                foreach ($u_ip as $ip => $value) {
                    //Ip mask
                    if (strstr(',', $ip)) {
                        $ips = explode(', ', $ip);
                        $ip = $ips[1];
                    }
                    $ip_arr = explode('.', $ip);
                    $ip_mask = $ip_arr[0] . "." . $ip_arr[1] . '.' . $ip_arr[2];
                    $item['mask'] = $ip_mask;
                    $masks[$ip_mask] += 1;
                    $mask_ip[$ip_mask] = $ip;
                }
                arsort($masks);

                if (sizeof($masks) > DDOS_MAX_IP_MASKS) {
                    $masks_attack = array();

                    foreach ($masks as $key => $value) {
                        if ($value > DDOS_MAX_IP_MASK_ADDRESS) {
                            $ban = true;
                            //validate mask host
                            $curr_ip = $mask_ip[$key];
                            $host = $this->gethostbyaddrCache($curr_ip);
                            foreach ($this->allowhosts as $ahost) {
                                if (strstr($host, $ahost)) {
                                    $ban = false;
                                    break;
                                }
                            }
                            if ($ban) {
                                $masks_attack[$key] = $value;
                            }
                        }
                    }

                    //Attack
                    if (sizeof($masks_attack) > DDOS_MAX_IP_MASKS) {
                        $this->upadte_mask_attack_ips($masks_attack, sizeof($u_ip), $http_host);
                        $mask_attack = true;
                    }
                }
            }

            if ($attack) {
                print '<p>Attack <b>detected</b></p>';
            } else {
                print '<h3>Time attack not detected</h3>';
            }
            print '<p>Max POST ' . $max_post . ' (' . DDOS_MAX_POST . ') req/min, Max GET ' . $max_get . ' (' . DDOS_MAX_GET . ') req/min</p>';

            if ($mask_attack) {
                print '<h3>Mask attack <b>detected</b></h3>';
            } else {
                print '<h3>Mask attack not detected</h3>';
            }
            //Возьмём 10 диапазонов по 15 адресов.
            print "<p>Total masks: " . sizeof($masks_attack) . "(" . DDOS_MAX_IP_MASKS . ") in IP address > " . DDOS_MAX_IP_MASK_ADDRESS . "</p>";
        }
    }

    private function gethostbyaddrCache($ip) {
        static $cahe_ip;
        if (!$cahe_ip) {
            $cahe_ip = array();
        }
        if (isset($cahe_ip[$ip])) {
            return $cahe_ip[$ip];
        } else {
            $cahe_ip[$ip] = gethostbyaddr($ip);
            return $cahe_ip[$ip];
        }
    }

    private function useronline_get_bots_custom() {
        $bots = array(
            'Googlebot' => 'Googlebot',
            'Google Bot' => 'Google Bot',
            'Googlebot-News' => 'Googlebot-News',
            'Googlebot-Image' => 'Googlebot-Image',
            'Googlebot-Video' => 'Googlebot-Video',
            'Googlebot-Mobile' => 'Googlebot-Mobile',
            'Mediapartners-Google' => 'Mediapartners-Google',
            'AdsBot-Google' => 'AdsBot-Google',
            'google' => 'Google',
            'MSN' => 'msnbot',
            'BingBot' => 'bingbot',
            'Alex' => 'ia_archiver',
            'Lycos' => 'lycos',
            'Ask Jeeves' => 'jeeves',
            'Altavista' => 'scooter',
            'AllTheWeb' => 'fast-webcrawler',
            'Inktomi' => 'slurp@inktomi',
            'Turnitin.com' => 'turnitinbot',
            'Technorati' => 'technorati',
            'Yahoo' => 'yahoo',
            'Findexa' => 'findexa',
            'NextLinks' => 'findlinks',
            'Gais' => 'gaisbo',
            'WiseNut' => 'zyborg',
            'WhoisSource' => 'surveybot',
            'Bloglines' => 'bloglines',
            'BlogSearch' => 'blogsearch',
            'PubSub' => 'pubsub',
            'Syndic8' => 'syndic8',
            'RadioUserland' => 'userland',
            'Gigabot' => 'gigabot',
            'Become.com' => 'become.com',
            'Baidu' => 'baidu',
            'Yandex' => 'yandex',
            'Rambler' => 'Rambler',
            'Mail.Ru' => 'Mail.Ru',
            'Webalta' => 'Webalta',
            'Quintura' => 'Quintura-Crw',
            'Turtle' => 'TurtleScanner',
            'Webfind' => 'webfind',
            'Aport' => 'Aport',
            'Amazon' => 'amazonaws.com',
            'Twitterbot' => 'Twitterbot',
            'applebot' => 'applebot',
            'AhrefsBot' => 'AhrefsBot',
            'Miralinks' => 'Miralinks',
            'Sogou' => 'Sogou web spider',
            'SemrushBot' => 'SemrushBot',
            'DotBot' => 'DotBot',
            'BLEXBot' => 'BLEXBot',
            'Amazonbot' => 'Amazonbot',
        );

        return $bots;
    }

    private function send_mail_attack($title, $notify_message, $blogname = '') {
        $subject = $title . ' ' . $blogname;
        $message_headers = 'From:' . $this->mail_from;

        if (function_exists('wp_mail')) {
            if (!$blogname && function_exists('get_option')) {
                $blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
            }

            foreach ($this->mail_to as $email) {
                @wp_mail($email, $subject, $notify_message, $message_headers);
            }
        } else {
            foreach ($this->mail_to as $email) {
                mail($email, $subject, $notify_message, $message_headers);
            }
        }
        return $notify_message;
    }

    private function mail_attack($new_ips, $masks_attack_size, $user_online, $blogname = '') {

        if (is_array($new_ips)) {
            $n_ips = implode(", ", $new_ips);
        } else {
            $n_ips = $new_ips;
        }

        $notify_message = "Атака на сайт!\n
Пользователей онлайн: $user_online \n
Маски ботов: $masks_attack_size \n
Добавлены маски: \n
$n_ips ";

        $subject = sprintf('Атака на сайт %1$s', $blogname);

        $message_headers = 'From:' . $this->mail_from;
        if (function_exists('wp_mail')) {
            if (!$blogname && function_exists('get_option')) {
                $blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
            }
            foreach ($this->mail_to as $email) {
                @wp_mail($email, $subject, $notify_message, $message_headers);
            }
        } else {
            foreach ($this->mail_to as $email) {
                mail($email, $subject, $notify_message, $message_headers);
            }
        }
        return $notify_message;
    }

    private function upadte_mask_attack_ips($masks_attack, $user_online, $http_host) {

        $new_ips = array();

        foreach ($masks_attack as $mask => $ip) {
            if (in_array($mask, $this->white_ip_list)) {
                continue;
            }
            if ($this->in_blacklist($ip, $http_host)) {
                continue;
            }
            $new_ips[] = $ip;
        }
        $add = false;

        if (sizeof($new_ips) > 0) {
            $add = true;
            foreach ($new_ips as $ip) {
                $add = $this->add_to_blacklist($ip, $http_host, 2, $this->ban_time_sec);
            }
        }

        if ($add && $this->mail_notifi) {
            print $this->mail_attack($new_ips, sizeof($masks_attack), $user_online, $http_host);
        }
    }

    private function add_to_blacklist($ip = "", $domain = "", $ban_type = 1, $time = 86400) {
        /*
         * Ban type:
         * 1 - Атака
         * 2 - Массовая атака
         * 3 - Подбор паролей
         */
        if (!$this->in_blacklist($ip, $domain)) {
            //Add

            $date_ban = time();
            $ban_time = $date_ban + $time;

            if ($domain && strlen($domain) > 254) {
                $domain = substr($domain, 0, 254);
            }
            $data = array(
                'date_ban' => $date_ban,
                'ban_time' => $ban_time,
                'ban_type' => $ban_type,
                'ip' => $ip,
                'domain' => $domain,
            );

            $this->db_insert($data, $this->db['blacklist']);

            // add text to domain folder
            $this->update_text_info($domain);

            // add hook to nginx reload
            $this->nginx_reload_hook();

            return true;
        }
        return false;
    }

    public function in_blacklist($ip = "", $domain = "") {

        if ($ip) {
            //Max db len 32
            if (strlen($ip) > 31) {
                $ip = substr($ip, 0, 31);
            }
        } else {
            $ip = $this->get_remote_ip();
        }

        $domainsql = "";
        if ($domain) {
            if (strlen($domain) > 254) {
                $domain = substr($domain, 0, 254);
            }
            $domainsql = sprintf("AND domain='%s'", $domain);
        }

        if ($ip) {
            //get IP from db
            $sql = sprintf("SELECT id FROM {$this->db['blacklist']} WHERE ip='%s' $domainsql limit 1", $ip);
            $exist_id = $this->db_get_var($sql);
            if ($exist_id) {
                return true;
            }
        }
        return false;
    }

    private function in_blacklist_info($ip = "", $domain = "") {

        if ($ip) {
            //Max db len 32
            if (strlen($ip) > 31) {
                $ip = substr($ip, 0, 31);
            }
        } else {
            $ip = $this->get_remote_ip();
        }

        $domainsql = "";
        if ($domain) {
            if (strlen($domain) > 254) {
                $domain = substr($domain, 0, 254);
            }
            $domainsql = sprintf("AND domain='%s'", $domain);
        }

        if ($ip) {
            //get IP from db
            $sql = sprintf("SELECT * FROM {$this->db['blacklist']} WHERE ip='%s' $domainsql limit 1", $ip);
            $row = $this->db_fetch_row($sql);
            return $row;
        }
        return '';
    }

    public function get_last_blacklist_ips($limit = 100) {
        $sql = sprintf("SELECT * FROM {$this->db['blacklist']} ORDER BY date_ban DESC limit %d", $limit);
        $data = $this->db_results($sql);
        return $data;
    }

    private function get_blacklist_ips($domain = "", $limit = 0) {
        $domainsql = "";
        if ($domain) {
            if (strlen($domain) > 254) {
                $domain = substr($domain, 0, 254);
            }
            $domainsql = sprintf("WHERE domain='%s'", $domain);
        }
        $limitsql = "";
        if ($limit > 0) {
            $limitsql = sprintf("limit %d", $limit);
        }

        $sql = "SELECT * FROM {$this->db['blacklist']} $domainsql ORDER BY date_ban DESC $limitsql";
        $data = $this->db_results($sql);
        return $data;
    }

    /**
     * функция определяет ip адрес по глобальному массиву $_SERVER
     * ip адреса проверяются начиная с приоритетного, для определения возможного использования прокси
     * @return ip-адрес
     */
    function get_remote_ip() {
        $ip = false;
        if (isset($_SERVER['HTTP_CF_CONNECTING_IP']))
            $ipa[] = trim($_SERVER['HTTP_CF_CONNECTING_IP']);

        if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
            $ipa[] = trim(strtok($_SERVER['HTTP_X_FORWARDED_FOR'], ','));

        if (isset($_SERVER['HTTP_CLIENT_IP']))
            $ipa[] = $_SERVER['HTTP_CLIENT_IP'];

        if (isset($_SERVER['REMOTE_ADDR']))
            $ipa[] = $_SERVER['REMOTE_ADDR'];

        if (isset($_SERVER['HTTP_X_REAL_IP']))
            $ipa[] = $_SERVER['HTTP_X_REAL_IP'];

        // проверяем ip-адреса на валидность начиная с приоритетного.
        foreach ($ipa as $ips) {
            //  если ip валидный обрываем цикл, назначаем ip адрес и возвращаем его
            if ($this->is_valid_ip($ips)) {
                $ip = $ips;
                break;
            }
        }

        return $ip;
    }

    /**
     * функция для проверки валидности ip адреса
     * @param ip адрес в формате 1.2.3.4
     * @return bolean : true - если ip валидный, иначе false
     */
    private function is_valid_ip($ip = null) {
        if (preg_match("#^([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})$#", $ip))
            return true; // если ip-адрес попадает под регулярное выражение, возвращаем true

        return false; // иначе возвращаем false
    }

    //Причина бана
    private function get_reason($ban_type) {
        $reason = "";
        switch ($ban_type) {
            case 1:
                $reason = "Атака";
                break;
            case 2:
                $reason = "Массовая атака";
                break;
            case 3:
                $reason = "Подбор паролей";
                break;

            default:
                $reason = "Атака";
                break;
        }
        return $reason;
    }

    private function update_text_info($domain = "") {

        if (!$domain) {
            return;
        }

        //Save str to domain folder
        $this->check_and_create_dir(BAN_INFO_TEMP_FOLDER);

        $file_name = BAN_INFO_TEMP_FOLDER . '/' . $domain;
        $file_name_nginx = BAN_INFO_TEMP_FOLDER . '/nginx_' . $domain . '.conf';

        $ips = $this->get_blacklist_ips($domain);

        /*
          [0] => stdClass Object
          (
          [id] => 8
          [date_ban] => 1551785563
          [ban_time] => 1551871963
          [ban_type] => 1
          [ip] => 207.46.13.153
          [domain] => pandoraopen.ru
          )

         */

        // Reload nginx
        if (sizeof($ips) > 0) {
            $ip_list = array();
            $ip_list_nginx = array();
            foreach ($ips as $value) {
                $ip = $value->ip;
                $ip_list[] = $ip;

                // Nginx
                $ipsn = array($ip);
                if (strstr($ip, ',')) {
                    $ipsn = explode(',', $ip);
                }
                foreach ($ipsn as $ipr) {
                    if (preg_match('/[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}/', $ipr, $match)) {
                        $ip_list_nginx[] = "deny " . $match[0] . ';';
                    }
                }
            }
            $string = implode("\n", $ip_list);

            //Пишим файл
            $fp = fopen($file_name, "w");
            fwrite($fp, $string);
            fclose($fp);

            //Пишим файл nginx
            if ($ip_list_nginx) {
                $string_nginx = implode("\n", $ip_list_nginx);
                $fp = fopen($file_name_nginx, "w");
                fwrite($fp, $string_nginx);
                fclose($fp);
            } else {
                unlink($file_name_nginx);
            }
        } else {
            //remove domain text
            unlink($file_name);
            unlink($file_name_nginx);
        }
    }

    private function nginx_reload_hook() {
        if (!$this->nginx_rules) {
            return false;
        }
        $this->check_and_create_dir(BAN_INFO_TEMP_FOLDER);
        $file_name = BAN_INFO_TEMP_FOLDER . "/reload_nginx.txt";
        $string = time();
        if (!file_exists($file_name)) {
            $fp = fopen($file_name, "w");
            fwrite($fp, $string);
            fclose($fp);
        }
    }

    private function renderLogData($data) {
        if (sizeof($data)) {

            $longtext = 50;
            $bots = $this->useronline_get_bots_custom();

            $unique_ip = array();
            foreach ($data as &$item) {
                $ip = $item['ip'];
                if (!$unique_ip[$ip]) {
                    $unique_ip[$ip] = $item;
                    $unique_ip[$ip]['http_referer'] = $this->replace_long_text($item['http_referer'], $longtext);
                    $unique_ip[$ip]['time_local'] = date('H:i:s', strtotime($item['time_local']));
                }
                $unique_ip[$ip]['count'] += 1;
                $unique_ip[$ip]['bytes_sent'] += $item['bytes_sent'];
                $unique_ip[$ip]['request_time'] += $item['request_time'];
                $unique_ip[$ip]['request'] .= "<br />" . $item['request'];
                $unique_ip[$ip]['http_referer'] .= "<br />" . $this->replace_long_text($item['http_referer'], $longtext);
                $unique_ip[$ip]['time_local'] .= "<br />" . date('H:i:s', strtotime($item['time_local']));
            }

            $data = $unique_ip;

            $masks = array();
            $bots_count = 0;
            $user_count = 0;
            foreach ($data as &$item) {

                //ip mask
                $ip = $item['ip'];
                if (strstr(',', $ip)) {
                    $ips = explode(', ', $ip);
                    $ip = $ips[1];
                }
                $ip_arr = explode('.', $ip);
                $ip_mask = $ip_arr[0] . "." . $ip_arr[1] . '.' . $ip_arr[2];
                $item['mask'] = $ip_mask;
                $masks[$ip_mask] += 1;


                // Check For Bot
                $bot_found = 'none';
                $type = 'guest';

                foreach ($bots as $name => $lookfor) {
                    if (stristr($item['http_user_agent'], $lookfor) !== false) {
                        $bot_found = $name;
                        $type = 'bot';
                        $bots_count += 1;
                        break;
                    }
                }

                $item['name'] = $bot_found;
                $item['type'] = $type;


                //get users
                if ($bot_found == 'none') {
                    $user = '';
                    if (function_exists('getUsersListByIp')) {
                        $user = getUsersListByIp($item['ip']);
                    }
                    if ($user) {
                        $user_count += 1;
                        $item['type'] = 'user';
                        if (is_string($user)) {
                            $item['name'] = $user;
                        } else {
                            $item['name'] = $user->name;
                        }
                    }
                }
            }
            ?>

            <h2>Total online <?php print sizeof($unique_ip) ?>. Bots <?php print $bots_count ?>. Users <?php print $user_count ?></h2>
            <table id="usersonline" class="bordered tablesorter">
                <thead>
                    <tr>


                        <th><?php print 'ip' ?></th>
                        <th><?php print 'ip raw' ?></th>
                        <th><?php print 'ips count' ?></th>                   
                        <th><?php print 'mask count' ?></th>
                        <th><?php print 'name' ?></th>                    
                        <th><?php print 'type' ?></th>  
                        <th><?php print 'status' ?></th>
                        <th><?php print 'request time' ?></th>
                        <th><?php print 'http user agent' ?></th>    
                        <th><?php print 'http referer' ?></th>                    
                        <th><?php print 'request' ?></th>
                        <th><?php print 'request length' ?></th>
                        <th><?php print 'bytes sent' ?></th>
                        <th><?php print 'ip mask' ?></th>
                        <th><?php print 'time' ?></th>  

                    </tr>
                </thead>
                <tbody>
                    <?php
                    /*
                      [ip] => 68.180.228.125
                      [time_local] => 12/Oct/2015:19:55:55 +0300
                      [status] => 200
                      [request_length] => 195
                      [bytes_sent] => 41435
                      [request_time] => 0.633
                      [request] => GET /category/army/page/26/ HTTP/1.1
                      [http_referer] => -
                      [http_user_agent] => Mozilla/5.0 (compatible; Yahoo! Slurp; http://help.yahoo.com/help/us/ysearch/slurp
                     */

                    foreach ($data as $ip) {
                        ?>
                        <tr>


                            <td><?php print $ip['ip']; ?></td>  
                            <td><?php print $ip['ip_raw']; ?></td>
                            <td><?php print $ip['count']; ?></td>                        
                            <td><?php print $masks[$ip['mask']]; ?> (<?php
                                $ip_curr_arr = explode('.', $ip['mask']);
                                print $ip_curr_arr[0]
                                ?>)</td>                        
                            <td><?php print $ip['name']; ?></td>
                            <td><?php print $ip['type']; ?></td>
                            <td><?php print $ip['status']; ?></td>
                            <td><?php print $ip['request_time']; ?></td>                       
                            <td><?php print $ip['http_user_agent'] ?></td>
                            <td><p class="word"><?php print $ip['http_referer'] ?></p></td>                        
                            <td><p class="word"><?php print $ip['request'] ?></p></td>
                            <td><?php print $ip['request_length']; ?></td>
                            <td><?php print $ip['bytes_sent']; ?></td>
                            <td><?php print $ip['mask']; ?></td>
                            <td><p class="word"><?php print $ip['time_local'] ?></p></td>

                        </tr>
                        <?php
                    }
                    ?>
                </tbody>
            </table>
            <?php
        }
    }

    private function renderLog($content = '') {
        ?>
        <!DOCTYPE HTML>
        <html>
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">

                <link rel="stylesheet" href="tablesorter/jq.css" type="text/css" media="print, projection, screen" />
                <link rel="stylesheet" href="tablesorter/blue/style.css" type="text/css" id="" media="print, projection, screen" />

                <script type="text/javascript" src="tablesorter/jquery-latest.js"></script> 
                <script type="text/javascript" src="tablesorter/jquery.tablesorter.min.js"></script>
                <script type="text/javascript" id="js">$(document).ready(function () {
                        // call the tablesorter plugin
                        $("table").tablesorter();
                    });</script>

                <style>
                    body {
                        display: block;
                        margin: 8px;
                    }
                    .word {
                        white-space: nowrap;
                    }
                </style>
                <title>Nginx access time details</title>
            </head>
            <body>
                <?php print $content ?>
            </body>
        </html><?php
    }

    private function replace_long_text($text, $len) {
        if (strlen($text) > $len) {
            $text = "<span title=\"$text\">" . substr($text, 0, $len) . "</span>";
        }
        return $text;
    }

    // run from cron
    public function remove_old_blacklist() {
        $time = time();
        $sql = sprintf("SELECT DISTINCT domain FROM {$this->db['blacklist']} WHERE ban_time < %d", $time);
        //Получаем список доменов
        $data = $this->db_results($sql);

        if ($data) {
            //Удаляем данные из таблицы
            $sql = sprintf("DELETE FROM {$this->db['blacklist']} WHERE ban_time < %d", $time);
            $this->db_query($sql);

            //Обновляем данные для доменов       
            foreach ($data as $value) {
                $domain = $value->domain;
                $this->update_text_info($domain);
            }
            $this->nginx_reload_hook();
        }
    }

    public function remove_id($id, $domain) {

        if (!$id) {
            return false;
        }

        $domainsql = "";
        if ($domain) {
            $domainsql = sprintf("AND domain='%s'", $domain);
        }

        //Удаляем данные из таблицы
        $sql = sprintf("DELETE FROM {$this->db['blacklist']} WHERE id = %d $domainsql", $id);
        $this->db_query($sql);

        $this->update_text_info($domain);
        $this->nginx_reload_hook();

        return true;
    }

    public function remove_ip($ip, $domain = '') {

        if (!$ip) {
            return false;
        }

        $domainsql = "";
        if ($domain) {
            $domainsql = sprintf("AND domain='%s'", $domain);
        }

        //Удаляем данные из таблицы
        $sql = sprintf("DELETE FROM {$this->db['blacklist']} WHERE ip = '%s' $domainsql", $ip);
        $this->db_query($sql);

        $this->update_text_info($domain);
        $this->nginx_reload_hook();

        return true;
    }

    public function blacklist() {
        if ($this->in_blacklist()) {
            Header("HTTP/1.1 403 Forbidden");
            //TODO
            //Причина бана и срок блокировки
            $info = $this->in_blacklist_info();

            $reason = $this->get_reason($info->ban_type);
            $offset = get_option('gmt_offset') * HOUR_IN_SECONDS;
            print ("<h2>403 Доступ запрещён</h2>");
            print ("<p>Доступ запрещён для IP адреса: " . $info->ip . ".</p>");
            print ("<p>Причина бана: " . $reason . ".</p>");
            print ("<p>Дата бана: " . date("d.m.Y H:i:s", ($info->date_ban + $offset)) . ".</p>");
            print ("<p>Дата разблокировки: " . date("d.m.Y H:i:s", ($info->ban_time + $offset)) . ".</p>");
            print ("<p>По вопросам досрочной разблокировки пишите на почту info@ctg66.ru.");
            die();
        }
    }

    public function ip() {
        $ip = $_SERVER['REMOTE_ADDR'];
        //Max db len 32
        if (strlen($ip) > 31) {
            $ip = substr($ip, 0, 31);
        }
        return $ip;
    }

    public function domian() {
        $name = $_SERVER['HTTP_HOST'];
        //Max db len 254
        if (strlen($name) > 254) {
            $name = substr($name, 0, 254);
        }
        return $name;
    }

    public function install_info() {
        $sql = "CREATE TABLE IF NOT EXISTS  `ipban_blacklist`(
				`id` int(11) unsigned NOT NULL auto_increment,	
                                `date_ban` int(11) NOT NULL DEFAULT '0', 
                                `ban_time` int(11) NOT NULL DEFAULT '0',        
                                `ban_type` int(11) NOT NULL DEFAULT '1', 
				`ip` varchar(32) NOT NULL default '',				
                                `domain` varchar(255) NOT NULL default '',				
				PRIMARY KEY  (`id`)				
				) DEFAULT COLLATE latin1_swedish_ci;";

        $this->db_query($sql);
    }

    public function check_and_create_dir($dst_path) {
        $path = '';
        if (ABSPATH) {
            $path = ABSPATH;
        }
        $dst_path = str_replace($path, '', $dst_path);

        # Создать дирикторию
        $arr = explode("/", $dst_path);

        foreach ($arr as $a) {
            if (isset($a)) {
                $path = $path . $a . '/';
                $this->fileman($path);
            }
        }
        return null;
    }

    public function fileman($way) {
        //Проверка наличия и создание директории
        // string $way - путь к дириктории
        $ret = true;
        if (!file_exists($way)) {
            if (!mkdir("$way", 0777)) {
                $ret = false;
                throw new Exception('Can not create dir: ' . $way . ', check cmod');
            }
        }
        return $ret;
    }

    public function post($data = array(), $host = '') {

        $fields_string = http_build_query($data);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $host);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);

        return $result;
    }

}
