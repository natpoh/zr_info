<?php
if (!defined('ABSPATH'))   
    define('ABSPATH', dirname(__FILE__, 3) . '/');

if (!defined('BAN_INFO_TEMP_FOLDER'))
    define('BAN_INFO_TEMP_FOLDER', ABSPATH . 'wp-content/uploads/baninfo');


class IpBanClient {

    var $folder = '';

    function __construct() {
        $this->folder = BAN_INFO_TEMP_FOLDER;
    }

    function in_blacklist_all($ip_local = "", $domain = "") {

        //check header
        if ($this->is_ban_bot_header()) {
            return true;
        }

        //check ip
        if ($this->in_blacklist($ip_local, $domain)) {
            return true;
        }

        return false;
    }

    function in_blacklist($ip_local = "", $domain = "") {

        if (!$ip_local) {
            $ip_local = $this->get_remote_ip();
        }
        if (!$domain) {
            $domain = $this->domian();
        }

        $ret = false;
        $list = $this->get_ips_from_domain($domain);
        if (sizeof($list) > 0) {
            foreach ($list as $ip) {
                if ($ip_local == $ip) {
                    $ret = true;
                    break;
                }
            }
        }
        return $ret;
    }

    function is_ban_bot_header() {
        $ret = false;
        $ban_agents = array('Sogou web spider');
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        foreach ($ban_agents as $agent) {
            if (strstr($user_agent, $agent)) {
                $ret = true;
                break;
            }
        }
        return $ret;
    }

    function get_ips_from_domain($domain) {
        $filename = $this->folder . '/' . $domain;
        if (file_exists($filename)) {
            $str = file_get_contents($filename);

            if ($str) {
                $list = explode("\n", $str);
                if (sizeof($list) > 0) {
                    return $list;
                }
            }
        }
        return array();
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

    function domian() {
        $name = $_SERVER['HTTP_HOST'];
        //Max db len 254
        if (strlen($name) > 254) {
            $name = substr($name, 0, 254);
        }
        return $name;
    }

    function blacklist_simple() {
        if ($this->in_blacklist_all()) {
            Header("HTTP/1.1 403 Forbidden");
            die("Access denied");
        }
    }

    function blacklist() {
        if ($this->in_blacklist_all()) {
            Header("HTTP/1.1 403 Forbidden");
            // Get RECAPCHA_PUBLIC
            !defined('DB_HOST_AN') ? include ABSPATH . 'an_config.php' : '';
            ?>

            <!DOCTYPE html>
            <html lang="en">
                <head>
                    <meta charset="UTF-8" />
                    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
                    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
                    <title>403 Access denied</title>

                    <!-- Google Recaptcha API CDN -->
                    <script src="https://www.google.com/recaptcha/api.js" async defer></script>                    
                    <script>
                        function onRecaptchaSuccess(responce) {
                            let searchParams = new URLSearchParams();
                            searchParams.set('resp', responce);
                            searchParams.set('host', window.location.hostname);
                            fetch('/service/ipban/recapcha_ajax.php', {
                                method: 'post',
                                body: searchParams,
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                            }).then(
                                    response => {
                                        return response.text();
                                    }
                            ).then(data => {
                                let result = JSON.parse(data);
                                if (result.remove == 1) {
                                    // console.log(data);
                                    location.reload();
                                }
                            });
                            ;
                        }
                    </script>
                    <style rel="stylesheet">
                        body {
                            font-size: 1rem;
                            display: flex;
                            flex-direction: column;
                            justify-content: center;
                            align-items: center;
                            text-align: center;
                            min-height: 100vh;
                        }
                        section {
                            max-width: 400px;
                            height: 400px;
                            text-align: left;
                        }

                        h1 {
                            font-size: 2.5rem;
                        }
                    </style>

                </head>
                <body>    
                    <section>
                        <h1>403 Access denied</h1>
                        <p>Access to your IP address is denied.</p>
                        <p>Reason for ban: Attack.</p>
                        <?php if (defined('RECAPCHA_PUBLIC')): ?>
                            <p>Confirm that you are not a robot.</p>                            
                            <div class="g-recaptcha" data-sitekey="<?php print RECAPCHA_PUBLIC ?>" data-callback="onRecaptchaSuccess"></div>                            
                        <?php endif ?>
                    </section>               
                </body>
            </html>
            <?php
            exit();
        }
    }

}
