<?php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');

if (!defined('ADD_DOMAIN')) {
    include ABSPATH . 'an_config.php';
}


// Start a session if it's not already started
if (!session_id()) {
    session_start();
}
class AUTH
{
    static $debug =0;

    public  static  function auth_login_user($wp_user_id,$redirect_url)
    {

        $user = get_user_by('id', $wp_user_id);
        wp_set_current_user($wp_user_id);
        clean_user_cache($user->ID);
        wp_clear_auth_cookie();
        wp_set_current_user($wp_user_id, $user->user_login);
        wp_set_auth_cookie($wp_user_id, true);
        update_user_caches($user);

        do_action('wp_login', $user->user_login, $user);
        wp_redirect('https://'.ADD_DOMAIN.'/');
        exit();
    }

    public  static function check_key($data)
    {
        $data = urldecode($data);

        !class_exists('JWT') ? include ABSPATH . "service/php-jwt-main/src/JWT.php" : '';
        !class_exists('Key') ? include ABSPATH . "service/php-jwt-main/src/Key.php" : '';

        $key = 'fdslRdfS'.(intval(time()/100000)).'sqmO';

        $decoded = JWT::decode($data, new Key($key, 'HS256'));
        //var_dump($decoded);
        if (($decoded)) {
            if ($decoded->t) {
                $time = $decoded->t;

                if ($time < time() - 10) {
                    $result = (time() - 10 - $time);
                    if (self::$debug)   echo json_encode(['e' => 1, 't' => $result]);
                    return;
                }
            }
            if ($decoded->id) {
                if (!defined('WP_HOME')) {
                    require(ABSPATH . 'wp-load.php');
                }

                $u_id = intval($decoded->id);
                $redirect_url = $decoded->r;

                $user = wp_get_current_user();


                if ($user->exists()) {


                    wp_redirect('https://'.ADD_DOMAIN.'/');
                    exit();


                }
                $user = get_user_by('ID', $u_id);
                if ($user) {
                    if ($decoded->l == $user->user_login && $decoded->e == $user->user_email) {
                        self::auth_login_user($u_id,$redirect_url);
                    } else {
                        if (self::$debug) echo json_encode(['e' => 1, 'u_data' => 'false']);
                        return;
                    }
                } else {
                    if (self::$debug) echo json_encode(['e' => 1, 'u' => 'false']);
                    return;
                }

            }

        }


    }

    public  static  function encode($payload)
    {

        !class_exists('JWT') ? include ABSPATH . "service/php-jwt-main/src/JWT.php" : '';
        !class_exists('Key') ? include ABSPATH . "service/php-jwt-main/src/Key.php" : '';


        $key = 'fdslRdfS'.(intval(time()/100000)).'sqmO';

        $payload['key'] = $key;

        $jwt = JWT::encode($payload, $key, 'HS256');
        return $jwt;

    }
    public function ext_redirect($key)
    {

        $url = 'https://'.ADD_DOMAIN.'/?remote_key='.$key;

        wp_redirect($url);

    }


    public static function set_auth()
    {
        $loggedIn = self::check_login();
        if ($loggedIn) {

            $server = $_SERVER['HTTP_HOST'];

            if ($server == MAIN_DOMAIN) {
                $data = self::get_user_data();

                if ($data) {
                    self:: ext_redirect($data);

                }


                if (self::$debug) {

                    echo json_encode(['l' => 1, 'key' => $data]);
                }


            }
        }

    }

    public  static function get_user_data()
    {
        if (!defined('WP_HOME')) {
            require(ABSPATH . 'wp-load.php');
        }



        $current_user = wp_get_current_user();
        $userId = $current_user->ID;

        // Now you can retrieve additional user data from the database if needed

        $user_data = get_userdata($userId);
        $r = $_SERVER['REQUEST_URI'];
        $user_array = array('t' => time(), 'id' => $user_data->ID, 'l' => $user_data->user_login, 'e' => $user_data->user_email, 'r' => $r);

        $data = self::encode($user_array);
        if ($data)
        {
            $data = urlencode($data);
        }

        return $data;
    }

    public  static function check_login()
    {
        $loggedIn = false; // Initialize a flag to check if the user is logged in

// Iterate through all cookies
        foreach ($_COOKIE as $cookieName => $cookieValue) {


            // Check if the cookie name contains "wordpress_logged_in_"
            if (strpos($cookieName, 'wordpress_logged_in_') !== false && $cookieValue) {
                // Found a matching cookie, set the flag to true
                $loggedIn = true;

                break; // No need to continue checking
            }
        }

    return $loggedIn;
    }

    public  static function login()
    {

        if (defined('ADD_DOMAIN')) {

            $server = $_SERVER['HTTP_HOST'];

            if ($server == ADD_DOMAIN) {

                if (isset($_GET['remote_key'])) {

                    self::check_key($_GET['remote_key']);

                }

            }
        }
}
}




?>