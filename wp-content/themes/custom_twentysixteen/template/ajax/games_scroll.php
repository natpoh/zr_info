<?php
$_GET['type']='games';

if (!class_exists('TV_Scroll'))  {
    require(ABSPATH . 'wp-content/themes/custom_twentysixteen/template/ajax/tv_scroll.php');
}
else
{
    $cache = tv_scroll('VideoGame');
    echo $cache;
}

