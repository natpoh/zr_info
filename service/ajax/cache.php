<?php

if (isset($_GET['action']))
{
    require_once('../../wp-config.php');

    if ($_GET['action'] =='clear_all_cache')
    {

        wpclearpostcache();

        exit();
    }


}

