<?php
if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');


if (!function_exists('pccf_filter')) {
    function pccf_filter($text)
    {
        if (!defined('CRITIC_MATIC_PLUGIN_DIR')) {
            define('CRITIC_MATIC_PLUGIN_DIR', ABSPATH . 'wp-content/plugins/critic_matic/');
        }

        if (!class_exists('CriticFront')) {
            require_once(CRITIC_MATIC_PLUGIN_DIR . 'critic_matic_ajax_inc.php');
        }
        global $cfront;
        $cfront = new CriticFront();

        $result = $cfront->pccf_filter($text);

        return $result;
    }
}
