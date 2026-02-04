<?php

if (!defined('ABSPATH'))
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');


!class_exists('GETCOINS') ? include ABSPATH . "analysis/include/get_coin_info.php" : '';

$content = GETCOINS::front();


echo '<a href="https://cointr.ee/rightwingtomato#messages" target="_blank"><div class="donations_inner_content">'.$content.'</div></a>';
