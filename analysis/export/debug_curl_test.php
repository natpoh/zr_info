<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');
include ABSPATH . 'analysis/export/import_db.php'; 

echo "<h1>Debug CURL Test</h1>";

// Mimic commit_info_request logic
$uid = $_GET['uid'] ?? 'test_uid'; 

echo "<h2>Testing with UID: $uid</h2>";

if (method_exists('Import', 'get_key')) {
    $key = Import::get_key();
    echo "Key: $key<br>";
} else {
     echo "Error: Import::get_key not found or not public.<br>";
     $key = '';
}

if (method_exists('Import', 'get_import_data')) {
    $options_data = Import::get_import_data();
    echo "Import Data retrieved.<br>";
} else {
    echo "Error: Import::get_import_data not found or not public.<br>";
    $options_data = [];
}

$link  = $options_data['link_request'] ?? '';
echo "Link: $link<br>";

if (!$link) {
    echo "Error: No link found in options_data.<br>";
    exit;
}

$limit=10;

$request = array(
    'uid'=>$uid,
    'action'=>'get_commit',
    'key'=>$key,
    'limit'=>$limit,
);

echo "<h3>Request Data:</h3>";
echo "<pre>";
print_r($request);
echo "</pre>";

echo "<h3>Sending CURL Request...</h3>";
$start = microtime(true);

$result =  GETCURL::getCurlCookie($link,'',$request);

$end = microtime(true);
$duration = $end - $start;
echo "Request took: " . number_format($duration, 4) . " seconds.<br>";

echo "<h3>Raw Result:</h3>";
var_dump($result);

echo "<h3>Decoded JSON:</h3>";
$json = json_decode($result, true);
if ($json) {
    echo "<pre>";
    print_r($json);
    echo "</pre>";
} else {
    echo "Result is not valid JSON.<br>";
    echo "Last JSON Error: " . json_last_error_msg() . "<br>";
}
?>
