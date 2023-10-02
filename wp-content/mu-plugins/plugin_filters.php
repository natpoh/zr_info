<?php

try {
    // IP ban
    require_once( ABSPATH . 'service/ipban/ipban_client.php' );
    $ipBanClient = new IpBanClient();
    $ipBanClient->blacklist();
} catch (Exception $exc) {
    
}
