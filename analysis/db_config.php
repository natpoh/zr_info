<?php


include  $_SERVER['DOCUMENT_ROOT'].'/an_config.php';

if (!function_exists('pdoconnect_db')) {

function pdoconnect_db()
{

    global $pdo;

    try {

        $pdo = new PDO("mysql:host=".DB_HOST_AN.";dbname=".DB_NAME_AN, DB_USER_AN, DB_PASSWORD_AN );

    }
    catch (PDOException $e) {
        print "Error!: " . $e->getMessage() . "<br/>";
        die();
    }

    $pdo->exec("SET NAMES '" .DB_CHARSET_AN . "' ");

}
}