<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/EmptyPHP.php to edit this template
 */

class WpUser extends AbstractDBWp {

    public $user = '';

    public function __construct() {
        $table_prefix = DB_PREFIX_WP;
        $this->db = array(
            'users' => $table_prefix . 'users',
        );
    }

    public function get_current_user() {
        // Define
        $this->define_config();

        // Cookie
        if (defined('WP_SITEURL')) {
            $siteurl = WP_SITEURL;
        } else {
            $siteurl = $this->get_option('siteurl', '', false, false);
        }

        if ($siteurl) {
            define('COOKIEHASH', md5($siteurl));
        } else {
            define('COOKIEHASH', '');
        }
        define('LOGGED_IN_COOKIE', 'wordpress_logged_in_' . COOKIEHASH);

        $cookie = $_COOKIE[LOGGED_IN_COOKIE];
        $scheme = 'logged_in';

        if (empty($cookie)) {
            return 0;
        }
        $cookie_elements = explode('|', $cookie);
        if (count($cookie_elements) !== 4) {
            return false;
        }

        // print_r($cookie_elements);

        list( $username, $expiration, $token, $hmac ) = $cookie_elements;

        $expired = $expiration;

        // Allow a grace period for POST and Ajax requests.
        if ('POST' === $_SERVER['REQUEST_METHOD']) {
            $expired += 3600;
        }

        // 1. Quick check to see if an honest cookie has expired.
        if ($expired < time()) {
            return 0;
        }

        // 2. Username auth_cookie_bad_username
        $user = $this->get_user_by_login($username);
        if (!$user) {
            return 0;
        }
        // print_r($user);
        $this->user = $user;

        // 3. Hash

        $pass_frag = substr($user->user_pass, 8, 4);
        $key = $this->wp_hash($username . '|' . $pass_frag . '|' . $expiration . '|' . $token, $scheme);

        // If ext/hash is not present, compat.php's hash_hmac() does not support sha256.
        $algo = function_exists('hash') ? 'sha256' : 'sha1';
        if (function_exists('hash_hmac')) {
            $hash = hash_hmac($algo, $username . '|' . $expiration . '|' . $token, $key);
        } else {
            $hash = $this->hash_hmac($algo, $username . '|' . $expiration . '|' . $token, $key);
        }

        if (!hash_equals($hash, $hmac)) {
            return 0;
        }

        // Token. Fires if a bad session token is encountered.
        if ($this->wp_session_verify($token)) {
            return 0;
        }

        return $user->ID;
    }

    public function define_config() {
        $config_keys = array(
            'AUTH_KEY',
            'SECURE_AUTH_KEY',
            'LOGGED_IN_KEY',
            'NONCE_KEY',
            'AUTH_SALT',
            'SECURE_AUTH_SALT',
            'LOGGED_IN_SALT',
            'NONCE_SALT',
            'ADD_DOMAIN',
            'MAIN_DOMAIN',
        );
        $wp_config = file_get_contents(ABSPATH . "wp-config.php");

        if (preg_match_all("/define\('([^']+)',[^']*'([^']+)'\);/s", $wp_config, $match)) {
            foreach ($match[1] as $key => $value) {
                if (in_array($value, $config_keys)) {
                    if (!defined($value)) {
                        define($value, $match[2][$key]);
                    }
                }
                // print "$value:{$match[2][$key]}\n";
            }
        }

        // Site URL
        if (!defined('WP_SITEURL')) {
            if ($_SERVER['HTTP_HOST'] == MAIN_DOMAIN) {
                define('WP_SITEURL', 'https://' . MAIN_DOMAIN);
            } else {
                define('WP_SITEURL', 'https://' . ADD_DOMAIN);
            }
        }
    }

    public function get_user_by_login($user_login) {
        $sql = sprintf("SELECT * FROM {$this->db['users']} WHERE user_login='%s'", $user_login);
        $result = $this->db_fetch_row($sql);
        return $result;
    }

    public function wp_session_verify($token) {
        $verifier = $this->wp_session_hash_token($token);
        return (bool) $this->wp_session_get_session($verifier);
    }

    /**
     * Retrieves a session based on its verifier (token hash).
     *
     * @since 4.0.0
     *
     * @param string $verifier Verifier for the session to retrieve.
     * @return array|null The session, or null if it does not exist
     */
    protected function wp_session_get_session($verifier) {
        $sessions = $this->wp_session_get_sessions();

        if (isset($sessions[$verifier])) {
            return $sessions[$verifier];
        }

        return null;
    }

    /**
     * Retrieves all sessions of the user.
     *
     * @since 4.0.0
     *
     * @return array Sessions of the user.
     */
    protected function wp_session_get_sessions() {
        $sessions = $this->get_user_meta($this->user->ID, 'session_tokens', true);

        if (!is_array($sessions)) {
            return array();
        }

        $sessions = array_map(array($this, 'prepare_session'), $sessions);
        return array_filter($sessions, array($this, 'wp_session_is_still_valid'));
    }

    /**
     * Determines whether a session is still valid, based on its expiration timestamp.
     *
     * @since 4.0.0
     *
     * @param array $session Session to check.
     * @return bool Whether session is valid.
     */
    final protected function wp_session_is_still_valid($session) {
        return $session['expiration'] >= time();
    }

    /**
     * Hashes the given session token for storage.
     *
     * @since 4.0.0
     *
     * @param string $token Session token to hash.
     * @return string A hash of the session token (a verifier).
     */
    private function wp_session_hash_token($token) {
        // If ext/hash is not present, use sha1() instead.
        if (function_exists('hash')) {
            return hash('sha256', $token);
        } else {
            return sha1($token);
        }
    }

    /**
     * Gets hash of given string.
     *
     * @since 2.0.3
     *
     * @param string $data   Plain text to hash.
     * @param string $scheme Authentication scheme (auth, secure_auth, logged_in, nonce).
     * @return string Hash of $data.
     */
    function wp_hash($data, $scheme = 'auth') {
        $salt = $this->wp_salt($scheme);
        return $this->hash_hmac('md5', $data, $salt);
    }

    /**
     * Returns a salt to add to hashes.
     *
     * Salts are created using secret keys. Secret keys are located in two places:
     * in the database and in the wp-config.php file. The secret key in the database
     * is randomly generated and will be appended to the secret keys in wp-config.php.
     *
     * The secret keys in wp-config.php should be updated to strong, random keys to maximize
     * security. Below is an example of how the secret key constants are defined.
     * Do not paste this example directly into wp-config.php. Instead, have a
     * {@link https://api.wordpress.org/secret-key/1.1/salt/ secret key created} just
     * for you.
     *
     *     define('AUTH_KEY',         ' Xakm<o xQy rw4EMsLKM-?!T+,PFF})H4lzcW57AF0U@N@< >M%G4Yt>f`z]MON');
     *     define('SECURE_AUTH_KEY',  'LzJ}op]mr|6+![P}Ak:uNdJCJZd>(Hx.-Mh#Tz)pCIU#uGEnfFz|f ;;eU%/U^O~');
     *     define('LOGGED_IN_KEY',    '|i|Ux`9<p-h$aFf(qnT:sDO:D1P^wZ$$/Ra@miTJi9G;ddp_<q}6H1)o|a +&JCM');
     *     define('NONCE_KEY',        '%:R{[P|,s.KuMltH5}cI;/k<Gx~j!f0I)m_sIyu+&NJZ)-iO>z7X>QYR0Z_XnZ@|');
     *     define('AUTH_SALT',        'eZyT)-Naw]F8CwA*VaW#q*|.)g@o}||wf~@C-YSt}(dh_r6EbI#A,y|nU2{B#JBW');
     *     define('SECURE_AUTH_SALT', '!=oLUTXh,QW=H `}`L|9/^4-3 STz},T(w}W<I`.JjPi)<Bmf1v,HpGe}T1:Xt7n');
     *     define('LOGGED_IN_SALT',   '+XSqHc;@Q*K_b|Z?NC[3H!!EONbh.n<+=uKR:>*c(u`g~EJBf#8u#R{mUEZrozmm');
     *     define('NONCE_SALT',       'h`GXHhD>SLWVfg1(1(N{;.V!MoE(SfbA_ksP@&`+AycHcAV$+?@3q+rxV{%^VyKT');
     *
     * Salting passwords helps against tools which has stored hashed values of
     * common dictionary strings. The added values makes it harder to crack.
     *
     * @since 2.5.0
     *
     * @link https://api.wordpress.org/secret-key/1.1/salt/ Create secrets for wp-config.php
     *
     * @param string $scheme Authentication scheme (auth, secure_auth, logged_in, nonce).
     * @return string Salt value
     */
    function wp_salt($scheme = 'auth') {
        static $cached_salts = array();
        if (isset($cached_salts[$scheme])) {
            /**
             * Filters the WordPress salt.
             *
             * @since 2.5.0
             *
             * @param string $cached_salt Cached salt for the given scheme.
             * @param string $scheme      Authentication scheme. Values include 'auth',
             *                            'secure_auth', 'logged_in', and 'nonce'.
             */
            return $cached_salts[$scheme];
        }

        static $duplicated_keys;
        if (null === $duplicated_keys) {
            $duplicated_keys = array(
                'put your unique phrase here' => true,
            );

            /*
             * translators: This string should only be translated if wp-config-sample.php is localized.
             * You can check the localized release package or
             * https://i18n.svn.wordpress.org/<locale code>/branches/<wp version>/dist/wp-config-sample.php
             */
            $duplicated_keys['put your unique phrase here'] = true;

            foreach (array('AUTH', 'SECURE_AUTH', 'LOGGED_IN', 'NONCE', 'SECRET') as $first) {
                foreach (array('KEY', 'SALT') as $second) {
                    if (!defined("{$first}_{$second}")) {
                        continue;
                    }
                    $value = constant("{$first}_{$second}");
                    $duplicated_keys[$value] = isset($duplicated_keys[$value]);
                }
            }
        }

        $values = array(
            'key' => '',
            'salt' => '',
        );
        if (defined('SECRET_KEY') && SECRET_KEY && empty($duplicated_keys[SECRET_KEY])) {
            $values['key'] = SECRET_KEY;
        }
        if ('auth' === $scheme && defined('SECRET_SALT') && SECRET_SALT && empty($duplicated_keys[SECRET_SALT])) {
            $values['salt'] = SECRET_SALT;
        }

        if (in_array($scheme, array('auth', 'secure_auth', 'logged_in', 'nonce'), true)) {
            foreach (array('key', 'salt') as $type) {
                $const = strtoupper("{$scheme}_{$type}");
                if (defined($const) && constant($const) && empty($duplicated_keys[constant($const)])) {
                    $values[$type] = constant($const);
                } elseif (!$values[$type]) {
                    $values[$type] = $this->get_option("{$scheme}_{$type}", '', false, false);
                    /* if (!$values[$type]) {
                      $values[$type] = wp_generate_password(64, true, true);
                      update_site_option("{$scheme}_{$type}", $values[$type]);
                      } */
                }
            }
        } else {
            if (!$values['key']) {
                $values['key'] = $this->get_option('secret_key', '', false, false);
                /* if (!$values['key']) {
                  $values['key'] = wp_generate_password(64, true, true);
                  update_site_option('secret_key', $values['key']);
                  } */
            }
            $values['salt'] = $this->hash_hmac('md5', $scheme, $values['key']);
        }

        $cached_salts[$scheme] = $values['key'] . $values['salt'];

        /** This filter is documented in wp-includes/pluggable.php */
        return $cached_salts[$scheme];
    }

    /**
     * Internal compat function to mimic hash_hmac().
     *
     * @ignore
     * @since 3.2.0
     *
     * @param string $algo   Hash algorithm. Accepts 'md5' or 'sha1'.
     * @param string $data   Data to be hashed.
     * @param string $key    Secret key to use for generating the hash.
     * @param bool   $binary Optional. Whether to output raw binary data (true),
     *                       or lowercase hexits (false). Default false.
     * @return string|false The hash in output determined by `$binary`.
     *                      False if `$algo` is unknown or invalid.
     */
    function hash_hmac($algo, $data, $key, $binary = false) {

        $packs = array(
            'md5' => 'H32',
            'sha1' => 'H40',
        );

        if (!isset($packs[$algo])) {
            return false;
        }

        $pack = $packs[$algo];

        if (strlen($key) > 64) {
            $key = pack($pack, $algo($key));
        }

        $key = str_pad($key, 64, chr(0));

        $ipad = ( substr($key, 0, 64) ^ str_repeat(chr(0x36), 64) );
        $opad = ( substr($key, 0, 64) ^ str_repeat(chr(0x5C), 64) );

        $hmac = $algo($opad . pack($pack, $algo($ipad . $data)));

        if ($binary) {
            return pack($pack, $hmac);
        }

        return $hmac;
    }
}
