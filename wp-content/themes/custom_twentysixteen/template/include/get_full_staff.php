<?php

if (strstr(WP_SITEURL,'just.test1'))
{
    define('DB_NAME2', 'please');
    define('DB_USER2', 'root');
    define('DB_PASSWORD2', '');
    define('DB_HOST2', 'localhost');
    define('DB_CHARSET2', 'utf8');

}
else

{
    define('DB_NAME2', 'review_dake_stfuhollywoo');
    define('DB_USER2', 'reviewdakestfuho');
    define('DB_PASSWORD2', 'NkqVAGxd');
    define('DB_HOST2', '127.0.0.1:3306');
    define('DB_CHARSET2', 'utf8');



}





global $table_prefix2;
$table_prefix2  = 'wp_pxh68b_';

global $pdo_r;

try {

    $pdo_r = new PDO("mysql:host=".DB_HOST2.";dbname=".DB_NAME2, DB_USER2, DB_PASSWORD2 );

    $pdo_r->exec("SET NAMES '" .DB_CHARSET . "' ");
    $pdo_r->exec("SET character_set_client = '" .DB_CHARSET . "' ");
    $pdo_r->exec("SET character_set_results = '" .DB_CHARSET . "' ");
    $pdo_r->exec("SET CHARACTER SET '" .DB_CHARSET . "' ");
    $pdo_r->exec("SET NAMES utf8 COLLATE utf8_unicode_ci");


}
catch (PDOException $e) {
    //print "Error!: " . $e->getMessage() . "<br/>";
 //   die();
}
if ($pdo_r)
{
  //  $pdo_r->query( 'use review_dake_stfuhollywoo' );
  //  $pdo_r->exec("SET NAMES '" .DB_CHARSET . "' ");
}
