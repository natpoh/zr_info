<?php

// cron: find /var/www/just.stfuhollywood.com/htdocs/wp-content/uploads/cache -name "*.html" -mmin +60 -exec rm -f -R {} \; > /dev/null
$p = 'sdf23_ds-f23DS';
$pass = $_GET['p'];

if ($pass !== $p) {
    die();
}

if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');

if (!defined('CRITIC_MATIC_PLUGIN_DIR')) {
    define('CRITIC_MATIC_PLUGIN_DIR', ABSPATH . 'wp-content/plugins/critic_matic/');
}

require_once( CRITIC_MATIC_PLUGIN_DIR . 'ThemeCache.php' );

$mode = ($_GET['mode']);
$type = ($_GET['type']);

if (!class_exists('ThemeCache')) {
    return;
}

if (isset($mode) && $mode == 'all') {
    ThemeCache::clearCacheAll($type);
} else {
    ThemeCache::clearCache($type);
}