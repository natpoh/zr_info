<?php
if (!defined('ABSPATH'))  define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');

if (isset($_GET['upload']))
{

    include (ABSPATH.'wp-content/plugins/avatar-project/inc/upload-file.php');
}
else
{
    include (ABSPATH.'wp-content/plugins/avatar-project/get.php');


}




