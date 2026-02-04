<?php

$keycom = "XF3-ds3_24dfs";

$key = $_GET['key'];
if ($keycom == $key) {
    try {
        require_once('../../wp-config.php');
        if (function_exists('simple_act_init')) {
            $sim_act = new SimpleActivity();
            $sim_act->remove_old_blacklist();
            $sim_act->remove_old_logins();
        }
    } catch (Exception $exc) {
        
    }
    try {
        require_once('ipban.php');
        $ipBanService = new IpBanService();
        $ipBanService->remove_old_blacklist();
    } catch (Exception $exc) {
        
    }
}
