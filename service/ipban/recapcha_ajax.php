<?php

$resp = $_POST['resp'];
$host = $_POST['host'];

$ret = array(
    'remove' => 0,   
);

if ($resp && $host) {

    try {
        require_once('ipban.php');
        $ipBanService = new IpBanService();
        /*
         * reCapcha
         * https://www.google.com/recaptcha/admin/
         * https://developers.google.com/recaptcha/docs/verify?hl=en
         */ 
        $google_url = 'https://www.google.com/recaptcha/api/siteverify';
        $data = array(
            'secret' => RECAPCHA_SECRET,
            'response' => $resp,
        );
        $post_json = $ipBanService->post($data, $google_url);        
        $post_data = json_decode($post_json);
        if ($post_data->success == true) {
            $remote_ip = $ipBanService->get_remote_ip();
            $ipBanService->remove_ip($remote_ip, $host);
            $ret['remove'] = 1;
        }
    } catch (Exception $exc) {
        
    }
}

print json_encode($ret);
exit();
