<?php

$url = 'https://zeitgeistreviews.com/critics/126655-Staff-Neo-Feudalist-How_Am_I_Not_Myself/';
if ($_GET['url']) {
    $url = $_GET['url'];
}

$json = false;
if ($_GET['json']) {
    $json = true;
}

$p = '8ggD_23_2D0DSF-F';

if ($_GET['p'] != $p) {
    return;
}

$content = file_get_contents($url);

//$content = "<body>Look at this cat: <img src='./cat.jpg'> 123 <img src=x onerror=alert(1)//></body>";
$pass = 'sdDclSPMF_32sd-s';

$data = array('p' => $pass, 'u' => $url, 'c' => $content);

// use key 'http' even if you send the request to https://...
$options = array(
    'http' => array(
        'header' => "Content-type: application/x-www-form-urlencoded\r\n",
        'method' => 'POST',
        'content' => http_build_query($data)
    )
);
$context = stream_context_create($options);
$service = 'http://148.251.54.53:8980/';
$result = file_get_contents($service, false, $context);

$data = array();
if ($result === FALSE) {
    /* Handle error */
} else {
    $data = json_decode($result);
}

if ($data) {
    $result = array('title' => $data->title, 'autor' => $data->author, 'content' => $data->content);
    if ($json) {
        $result_string = json_encode($result);

        echo $result_string;
    } else {
        print '<h1>'.$data->title.'</h1>';
        print '<h3>'.$data->author.'</h3>';
        print $data->content;
    }
}

