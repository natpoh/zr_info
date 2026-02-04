<?php
ini_set("auto_detect_line_endings", true);
error_reporting('E_ERROR');
set_time_limit(0);
ini_set('display_errors','On');




include 'db_config.php';
global $pdo;
pdoconnect();

///echo  $_SERVER['DOCUMENT_ROOT'];


require_once('forceutf8-master/src/ForceUTF8/Encoding.php');

use \ForceUTF8\Encoding;




function preparestring($string)
{
   $string = preg_replace('#"#','',$string);
//echo mb_detect_encoding($string);

 /// $string =mb_convert_encoding($string, 'UTF-8','ASCII');

    return $string;
}
function addto_db($array,$type='actor')
{
 ///var_dump($array);
    global $pdo;

    if ($type=='actor') {
        $sql = "SELECT  * FROM data_" . $type . " where " . $type . "_id =? and MovieID =?  and Name =? and Role =? and  Category =? and Year =? LIMIT 1";
    }
   else if ($type=='crew') {
        $sql = "SELECT  * FROM data_" . $type . " where " . $type . "_id =? and MovieID =?  and Name =? and Role =? and  Detail =? and Year =? LIMIT 1";
    }

   else if ($type=='movie') {
       $sql = "SELECT  * FROM data_" . $type . " where MovieID =? LIMIT 1";
   }




////echo $sql;
/*
    $q = $pdo->prepare($sql);
    $q->execute([$array[0]]);
    $q->setFetchMode(PDO::FETCH_ASSOC);




    $r = $q->fetch();

    */
    if  (//!$r['id'] &&
        $array[0]!='ID')
    {

        $sql="INSERT INTO `data_".$type."` VALUES (NULL, ?, ?, ?, ?, ?, ?, ?)";
        $q = $pdo->prepare($sql);
        $q->execute($array);
       /// echo $id.' inserted <br>';
    }
    else
    {

       if ($array[0]!='ID') {
           echo '<br> exists  <br>';
           var_dump($r);
           echo '<br>';
           var_dump($array);
           echo '<br><br>';
       }


    }
}
function getfile($url)
{

    $handle = @fopen($url, "r");
    if ($handle) {
        while (($buffer = fgets($handle,5000000)) !== false) {
            $array[] =  $buffer;

            //echo $buffer.PHP_EOL;
        }
        if (!feof($handle)) {
            //  echo "Error: unexpected fgets() fail\n";
        }
        fclose($handle);
    }

    if (is_array($array))
    {
        $array= array_reverse($array) ;
        return $array;
    }
}
function getcsvdata($type='actors')
{




    global $pdo;
    $sql="TRUNCATE TABLE data_".$type;
    $q = $pdo->prepare($sql);
    $q->execute();




    $i = 0;

    for ($time = 1960; $time <= 2018; $time += 1) {

        $filename = $_SERVER['DOCUMENT_ROOT'] . '/database/sdata/data_final/'.$type.'_' . $time . '.csv';

echo '$filename:'.$filename.'<br>'.PHP_EOL;

       /// echo $filename;

        $array = getfile($filename);



        if (is_array($array)) {
//var_dump($array);
            foreach ($array as $stroka) {
                if ($stroka) {
                    $stroka =mb_convert_encoding($stroka, 'UTF-8','ASCII');
                    $stroka = '['.$stroka.']';


                  ///  echo $stroka;
                    $ob = json_decode($stroka);





                    if (is_array($ob)) {


                if ($type!='movie'){$ob[6]=$time;}

    addto_db($ob,$type);



                    }
                }
            }
        }

   /// return;
        $i++;
    }


}




function prepare_data($stroka)
{
    $type = 'actors_race';
global $pdo;


   //
   /// $stroka =mb_convert_encoding($stroka, 'UTF-8','ASCII');
// $stroka=  convert_cp1252_to_utf8($stroka);


    $stroka =  Encoding::fixUTF8($stroka);

 ///   echo $stroka.PHP_EOL;



  /// $stroka =mb_convert_encoding($stroka, 'UTF-8','ASCII');
   /// $stroka =iconv(mb_detect_encoding($stroka),'UTF-8',$stroka);
/*
    if (preg_match('#("(\w+,)+(\w+)*")#',$stroka,$match))
    {
        $res = str_replace(',',';',$match[0]);
        $res = str_replace('"','',$res);
        $stroka = preg_replace('#("(\w+,)+(\w+)*")#',$res,$stroka)  ;
 }
*/

// echo $stroka;
   // echo '<br>';
   // $array = explode(',',$stroka);

   $stroka = '['.$stroka.']';
   $stroka = str_replace('NA','"NA"',$stroka);
   $array = json_decode($stroka);



if (is_array($array)) {

   //var_dump($array);
/*
    $sql = "SELECT  id  FROM data_".$type." where actor_id =? LIMIT 1";

    $q = $pdo->prepare($sql);
    $q->execute([$array[0]]);
    $q->setFetchMode(PDO::FETCH_ASSOC);

    $r = $q->fetch();
    if (!$r['id'] && $array[0] != 'ID') {

     */



    if ( $array[0] != 'ID') {

        $sql = "INSERT INTO `data_" . $type . "` VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ? , ?, ?, ?, ? , ?)";

        ///echo $sql;

        $q = $pdo->prepare($sql);
        $q->execute($array);
        /// echo $id.' inserted <br>';


    } else {

        if ($array[0] != 'ID') {
            /*
            echo '<br> exists  <br>';
            var_dump($r);
            echo '<br>';
            var_dump($array);
            echo '<br><br>';

            */
        }
    }
}
else
{
    echo '<br>' .$array.' not array<br>';
}

}



function getlongdatacsv($url)
{
    global $pdo;
   $sql="TRUNCATE TABLE data_actors_race";
    $q = $pdo->prepare($sql);
    $q->execute();


$i=0;

    $handle = @fopen($url, "r");
    if ($handle) {
        while (($buffer = fgets($handle,5000000)) !== false) {



           prepare_data($buffer);

         //   echo $buffer.PHP_EOL;
            $i++;

       //   if ($i>2)return;
        }
        if (!feof($handle)) {
            //  echo "Error: unexpected fgets() fail\n";
        }
        fclose($handle);
    }
}


if ($_GET['type'])
{
    $type=$_GET['type'];

    getcsvdata($type);
}




else {
   // $filename = $_SERVER['DOCUMENT_ROOT'] . '/database/sdata/test.csv';
 $filename = $_SERVER['DOCUMENT_ROOT'] . '/database/sdata/final_db.csv';
   // $filename = $_SERVER['DOCUMENT_ROOT'] . '/database/sdata/final_db-fixed.csv';

    getlongdatacsv($filename);
}
