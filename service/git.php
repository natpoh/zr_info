<?php
/* try {
    ob_start();
    print '<pre>';
    print_r($_SERVER);
    print_r($_REQUEST);
    print_r($_GET);
    print_r($_POST);
    print_r(json_decode(file_get_contents('php://input'))); 
    print '</pre>';
    $str = ob_get_contents();
    ob_end_clean();

    $handle = fopen('../wp-content/uploads/git.log', 'a');
    fwrite($handle, sprintf("%s|%s\n", date('c'), $str));
    fclose($handle);
} catch (Exception $exc) {    
}*/

require_once('../an_config.php');


function curl_post($data = array(), $host = '') {

    $time = isset($_GET['t']) ? (int) $_GET['t'] : 1;
    $fields_string = http_build_query($data);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $host);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $time);
    curl_setopt($ch, CURLOPT_TIMEOUT, $time);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

    $result = curl_exec($ch);

    return $result;
}

$commands = array(
    'pull_zr',
    'pull_info',
    'pull_filmdemographics',
);

$cmd = isset($_GET['cmd']) ? $_GET['cmd'] : '';

if (in_array($cmd, $commands)) {
    $data = array(
        'cmd' => $cmd,
    );
    $host = SYNC_HOST;    
    print curl_post($data, $host);
}