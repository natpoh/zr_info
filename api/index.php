<?php

declare(strict_types=1);
header('Access-Control-Allow-Origin: *');
# header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
# header('Access-Control-Allow-Headers: Content-Type, Authorization');

/*
 * Php compiler
 * https://github.com/zircote/swagger-php
 * User interface
 * https://github.com/swagger-api/swagger-ui
 * Php doc
 * https://zircote.github.io/swagger-php/reference/attributes.html
 *  
 * docker run --rm -v /var/www/inforwt/api:/app -it pathmotion/swagger-php openapi -e vendor --format yaml -o ./output/v1.yaml ./src/v1 --debug
 * cd /var/www/inforwt/api && composer 
 *
 * TODO
 * 
 * 1. install api viewer 
 * 2. Add simple class
 * Functions:
 * search - find movies by search
 * itemInfo - get item info: movie, tv, game
 * 3. Add authorization: key, domain, ip
 * 4. Add doc compilation
 * 5. Add tests
 */

if (!defined('ABSPATH'))
    define('ABSPATH', dirname(__FILE__, 2) . '/');

if (!defined('CRITIC_MATIC_PLUGIN_DIR')) {
    define('CRITIC_MATIC_PLUGIN_DIR', ABSPATH . 'wp-content/plugins/critic_matic/');

    // DB config
    !defined('DB_HOST_AN') ? include ABSPATH . 'analysis/db_config.php' : '';
    // bstract DB
    !class_exists('Pdoa') ? include ABSPATH . "analysis/include/Pdoa.php" : '';

    require_once( CRITIC_MATIC_PLUGIN_DIR . 'db/AbstractFunctions.php' );
    require_once( CRITIC_MATIC_PLUGIN_DIR . 'db/AbstractDBFront.php' );
    require_once( CRITIC_MATIC_PLUGIN_DIR . 'db/AbstractDBAn.php' );
    require_once( CRITIC_MATIC_PLUGIN_DIR . 'db/AbstractDBFda.php' );
    require_once( CRITIC_MATIC_PLUGIN_DIR . 'ThemeCache.php' );
    require_once( CRITIC_MATIC_PLUGIN_DIR . 'CriticMatic.php' );
    require_once( CRITIC_MATIC_PLUGIN_DIR . 'CriticSearch.php' );
    require_once( CRITIC_MATIC_PLUGIN_DIR . 'SearchFacets.php' );
}

require_once __DIR__ . '/vendor/autoload.php';

// Get current URL
$parsedUrl = parse_url($_SERVER['REQUEST_URI']);



///only develop server
if (strstr($parsedUrl["path"],'index.php') && $_SERVER['HTTP_HOST'] =='info.test1.ru')
{

	$parsedUrl["path"] = str_replace( '/api/index.php','',$parsedUrl["path"] );
}


$path = $parsedUrl['path'];


if ($path == '/') {
    // Load documentation    
    $index = file_get_contents('doc/index.html');
    $indexr = str_replace("swagger_url_path", SWAGGER_API_URL, $index);
    print $indexr;
    exit;
} else if (preg_match('#^/poster/([0-9]+)#', $path, $match)) {
    // Generate poster    	   
    $_GET['id'] = 'm_' . $match[1];
    include ('../analysis/create_image.php');
    exit();
}
else if (preg_match('#^/image/([0-9]+)#', $path, $match)) {
	// Generate poster
	$_GET['id'] =  $match[1].'_o2';

	include ('../analysis/create_image.php');
	exit();
}

$path_arr = explode('/', $path);

$version = isset($path_arr[1]) ? $path_arr[1] : 'v1';

$query_args = array();
if ($parsedUrl['query']) {
    parse_str($parsedUrl['query'], $query_args);
}


if ($version == 'v1') {
    $bs = new OpenApi\Fd\Bootstrap();
    $bs->run($path_arr, $query_args);
}

