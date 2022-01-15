<?php
error_reporting('E_ERROR');
set_time_limit(0);
ini_set('display_errors','On');

include 'db_config.php';
global $pdo;
pdoconnect();



function prepare_data($stroka1,$stroka2,$stroka3)
{
    $type='actors_jew';

global $pdo;

$array = array($stroka1,$stroka2,$stroka3);

/////searc id
///
///

    $sql = "SELECT actor_id FROM `data_actors_gender` where `Name` = '".$stroka1."' ";

   // echo $sql;

    $q = $pdo->prepare($sql);
    $q->execute();
    $r = $q->fetch();

  //  var_dump($r);

    if ($r['actor_id']) {

        $actor_id = $r['actor_id'];

    }
    else
    {
        $actor_id =0;
    }

if (is_array($array)) {


    $sql = "INSERT INTO `data_" . $type . "` VALUES (NULL, '".$actor_id."',?, ?, ?)";

        $q = $pdo->prepare($sql);
        $q->execute($array);
     /// echo ' inserted <br>';


}


}



function getlongdatacsv($url)
{


    global $pdo;
    $sql="TRUNCATE TABLE data_actors_jew";
    $q = $pdo->prepare($sql);
    $q->execute();

$i=0;

    $handle = @fopen($url, "r");
    if ($handle) {
        while (($buffer = fgets($handle,5000000)) !== false) {




            if (preg_match('#(.+);(.+);(.+);"#',$buffer,$mach)  )
            {

                prepare_data($mach[1],$mach[2],$mach[3]);
                $i++;

            }


         ///  echo $buffer.PHP_EOL;


          ///  if ($i>3)return;
        }
        if (!feof($handle)) {
            //  echo "Error: unexpected fgets() fail\n";
        }
        fclose($handle);
    }

}


$filename = $_SERVER['DOCUMENT_ROOT'] . '/database/sdata/jewornotjew.csv';


getlongdatacsv($filename);

