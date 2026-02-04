<?php

set_time_limit(0);
error_reporting(E_ERROR);
ini_set('display_errors', TRUE);
//ini_set('display_startup_errors', TRUE);

include 'db_config.php';
global $pdo;
pdoconnect();


//require_once ('forceutf8-master/src/ForceUTF8/Encoding.php');
//use \ForceUTF8\Encoding;






function prepare_data($array,$type)
{

global $pdo;
    $array = array_slice($array, 0, 11);
 ///   var_dump($array) ;


    //      var_dump($row) ;
    /*
        $title = $row[0];

        $stroka =iconv(mb_detect_encoding($buffer),'UTF-8',$buffer);

       $stroka = '['.$stroka.']';
       $stroka = str_replace(',NA',',"NA"',$stroka);
        $stroka  =str_replace(PHP_EOL,'\r\n',$stroka);
        $stroka  =str_replace('"\r\n','"',$stroka);
      ///  $stroka  =str_replace('”,"','","',$stroka);



        //echo $stroka.'<br><br>'
       // $stroka =  Encoding::fixUTF8($stroka);
        /*
            $array = json_decode($stroka);
            if (!is_array($array)) {

                $array =explode('","',$stroka);
               // var_dump($array);
                $array0='';

                foreach ($array as $index=> $val)
                {
                    $val=str_replace(array('"','[',']'),'',$val);

                    $array0[$index]=$val;
                }
                $array=$array0;

             // var_dump($array);

            }

                if (is_array($array)) {

        /*
           $sql = "SELECT  id  FROM data_" . $type . " where `Name`  =? LIMIT 1";

            $q = $pdo->prepare($sql);
            $q->execute([$array[0]]);
            $q->setFetchMode(PDO::FETCH_ASSOC);
            $r = $q->fetch();
            if (!$r['id'] && $array[0] != 'ID'  && $array[0] != 'Name') {

             */
    $actor_id =0;
    $actor_ids='';

if ($array[0]) {
    $sql = "SELECT actor_id FROM `data_actors_gender` where `Name` = '" . $array[0] . "' ";

   // echo $sql;


    $q = $pdo->prepare($sql);
    $q->execute();
   while ($r = $q->fetch())
   {
    //  var_dump($r);

    if ($r['actor_id']) {

        $actor_id = $r['actor_id'];

    }
       $actor_ids.=','.$r['actor_id'];
   }
   if ($actor_ids)
   {
       $actor_ids = substr($actor_ids,1);
       if ($actor_ids==$actor_id)
       {
           $actor_ids='';
       }
   }
}



    $sql = "INSERT INTO `data_" . $type . "` VALUES (NULL, '".$actor_id."',?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,'".$actor_ids."')";

        $q = $pdo->prepare($sql);
        $q->execute($array);
     /// echo ' inserted <br>';



}


function getlongdatacsv($url,$type)
{
    global $pdo;
    $sql="TRUNCATE TABLE data_actors_ethnic";
    $q = $pdo->prepare($sql);
    $q->execute();

///echo 'try include phpexcel';

    require_once('PHPExcel/Classes/PHPExcel.php');

    $array_result=array();

    $excel = PHPExcel_IOFactory::load($url);
//var_dump($excel);

    $lists=[];

    Foreach ($excel->getWorksheetIterator() as $worksheet) {
        $lists[] = $worksheet->toArray();
    }

    if (is_array($lists)) {

        foreach ($lists as $list) {

            // Перебор строк
            foreach ($list as $row) {

                $str = $row[0].$row[1];
               if (!$array_result[$str] && $row[0] != 'Name')
                {



                    prepare_data($row,$type);
                    $array_result[$str]=1;
                  //  return;
                }





            }
        }
    }



/*
    $i=0;
    $handle = @fopen($url, "r");
    if ($handle) {
        while (($buffer = fgets($handle,5000000)) !== false) {

            $reg ='"';

$Teststring = substr($buffer,0,1);


            if ($Teststring=='"')
            {
                prepare_data($content,$type);
                $content='';
                $content.=$buffer;


            }
            else
            {
                if (!strstr($buffer,'"Name",'))
                {
                $content.=$buffer;
            }
            }

            //echo $buffer.PHP_EOL;
$i++;

        //  if ($i>100)return;

        }
        if (!feof($handle)) {
            //  echo "Error: unexpected fgets() fail\n";
        }
        fclose($handle);
    }
*/



}

///$filename = $_SERVER['DOCUMENT_ROOT'] . '/database/sdata/final_db.csv';
$filename = $_SERVER['DOCUMENT_ROOT'] . '/database/sdata/final_final_ETHNICELEBS_DB.csv';


$type = 'actors_ethnic';

getlongdatacsv($filename,$type);

