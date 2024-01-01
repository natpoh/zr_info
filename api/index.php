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
    require_once( CRITIC_MATIC_PLUGIN_DIR . 'ThemeCache.php' );
    require_once( CRITIC_MATIC_PLUGIN_DIR . 'CriticMatic.php' );
    require_once( CRITIC_MATIC_PLUGIN_DIR . 'CriticSearch.php' );
    require_once( CRITIC_MATIC_PLUGIN_DIR . 'SearchFacets.php' );
}

require_once __DIR__ . '/vendor/autoload.php';

// Get current URL
$parsedUrl = parse_url($_SERVER['REQUEST_URI']);
$path = $parsedUrl['path'];

if ($path=='/'){
    // Load documentation    
    $index = file_get_contents('doc/index.html');
    $index = str_replace("swagger_url_path", SWAGGER_API_URL, $index);    
    print $index;
    exit;
}
$path_arr = explode('/', $path);

$version = isset($path_arr[1]) ? $path_arr[1] : 'v1';
$command = isset($path_arr[2]) ? $path_arr[2] : '';
$command2 = isset($path_arr[3]) ? $path_arr[3] : '';


// Check api key
$api_valid = false;

$query_args = array();
if ($parsedUrl['query']) {
    parse_str($parsedUrl['query'], $query_args);
}

if (isset($query_args['api_key'])) {
    $api_key = $query_args['api_key'];
    // TODO check api limits
    $api_valid = true;
}

if (!$api_valid) {
    // TODO Check api IP or Domain

	if ($_SERVER['REMOTE_ADDR']=='148.251.54.53')
	{
	$api_valid = true;
	}


}

if (!$api_valid) {
    http_response_code(401);
    echo 'Unauthorized ';
    exit;
}

if ($version == 'v1') {
    if ($command == 'search') {
        $controller = new OpenApi\Fd\Controllers\SearchController();
        $controller->runPath($command2, $query_args);
    } elseif ($command == 'string_uri') {
        $controller = new OpenApi\Fd\Controllers\StrUriController();
        $controller->runPath($command2, $query_args);
    } elseif ($command == 'media') {
        $controller = new OpenApi\Fd\Controllers\MediaController();
        $query_args['media_id']=$command2;
        $command3 = isset($path_arr[4]) ? $path_arr[4] : '';
        $controller->runPath($command3, $query_args);
    }
} else {
    http_response_code(404);
    echo '404 Not Found';
}
