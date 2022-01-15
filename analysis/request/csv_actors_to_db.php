<?php
ini_set('memory_limit', '4096M');
set_time_limit(0);
error_reporting(E_ERROR);
ini_set('display_errors', TRUE);
//ini_set('display_startup_errors', TRUE);

include '../db_config.php';
global $pdo;
pdoconnect_db();



function getlongdatacsv($url)
{
    global $pdo;
    $sql="TRUNCATE TABLE data_actors_gender";
    $q = $pdo->prepare($sql);
    $q->execute();

///echo 'try include phpexcel';


    $i=0;
    $handle = @fopen($url, "r");
    if ($handle) {
        while (($buffer = fgets($handle,4194967296 )) !== false) {



            if ($i>0)
            {
                prepare_data($buffer);
            }

            $i++;

            //   if ($i>2)return;
        }
        if (!feof($handle)) {
            //  echo "Error: unexpected fgets() fail\n";
        }
        fclose($handle);
    }




}
function prepare_data($array)
{

    $array = explode('	',$array);

    global $pdo;

    $gender=null;

    $array[0] =intval( substr($array[0],2));


    if (strstr($array[4],'actress'))
    {
        $gender ='f';
    }
    else     if (strstr($array[4],'actor'))
    {
        $gender ='m';
    }
    $array[6]=$gender;

 ///   var_dump($array);

    $array_requst = array($array[0],$gender);
    if ($gender)
    {
        $sql = "INSERT INTO `data_actors_gender`(`id`, `actor_id`, `Gender`) VALUES (NULL,?,?)";
        $q = $pdo->prepare($sql);
        $q->execute($array_requst);
    }
    /// echo ' inserted <br>';

}
//return;

////////https://datasets.imdbws.com/ link to data

$filename = $_SERVER['DOCUMENT_ROOT'] . '/analysis/sdata/actrosnames.tsv';
//$filename = $_SERVER['DOCUMENT_ROOT'] . '/analysis/sdata/test.tsv';
getlongdatacsv($filename);