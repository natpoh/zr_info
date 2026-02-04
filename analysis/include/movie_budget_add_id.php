<?php

///////////assign a movie id in the movie budget table

////$_GET['method']==2  approximate search


ini_set("auto_detect_line_endings", true);
error_reporting('E_ERROR');
set_time_limit(0);
ini_set('display_errors','On');




function getUniqueWords($words) {
    if (preg_match_all("#([\p{L}0-9]+)#uis", $words, $matchesarray)) {
        $wordsArr = array_unique($matchesarray[0]);
        return $wordsArr;
    }
}

function compareResults($m1, $m2) {
$wordsArr = getUniqueWords($m1);
$searchArr = getUniqueWords($m2);
    $count = sizeof($wordsArr);
    $find = 0;
    foreach ($wordsArr as $word) {
        if (in_array($word, $searchArr)) {
            $find++;
        } else {
            // echo " <b>$word</b>, ";
        }
    }
    $precent = ($find > 0) ? 100 * $find / $count : 0;
    return $precent;
}

include '../db_config.php';
global $pdo;
pdoconnect_db();


function checktable($movie_title,$date_need)
{
    global $pdo;



    if ($_GET['method']==2)
    {
        $movie_res='';

        $movie_array = explode(' ',$movie_title);

        $movie_res=$movie_array[0];
        if (strlen($movie_res)<5)
        {
            $movie_res=$movie_array[0].' '.$movie_array[0];
        }

        /*

        foreach ($movie_array as $movie)
        {
            if (strlen($movie)>=4)
            {
                $movie_res=$movie;
                break;
            }
        }
*/

        $sql ="SELECT MovieID, Title FROM `data_movie` where  	Title  LIKE '".$movie_res." %'  and (`Year` ='".$date_need."' or  `Details` LIKE '%".$date_need."%') limit 1";
//echo $sql;
        $q = $pdo->prepare($sql);
        $q->execute();
        $q->setFetchMode(PDO::FETCH_ASSOC);

    }
    else {

        $sql = "SELECT MovieID,Title FROM `data_movie` where  	Title  = ? and (`Year` ='" . $date_need . "' or  `Details` LIKE '%" . $date_need . "%') limit 1";
        $q = $pdo->prepare($sql);
        $q->execute([$movie_title]);
        $q->setFetchMode(PDO::FETCH_ASSOC);

    }
    $r = $q->fetch();
    if ($r['MovieID'])
    {

       if ( compareResults($r['Title'], $movie_title) >70 &&     $_GET['method']==2)
        {

            echo $r['MovieID'].' '.compareResults($r['Title'], $movie_title).' '.$r['Title'].' == '.$movie_title.'<br>';
            return $r['MovieID'];
        }
else if ($_GET['method']!=2)
{


       /// compareResults($r['Title'], $movie_title)


        return $r['MovieID'];
}
    }

}
function add_id_to_table($id, $result_id)
{
    global $pdo;
    $sql="UPDATE `data_movie_budget` SET `MovieID` = '".$result_id."' WHERE `data_movie_budget`.`id` = ".$id;
    $q = $pdo->prepare($sql);
    $q->execute();
    ///echo $id.' - '.$result_id.' addeded<br>'.PHP_EOL;

}


////////////////////////////


$sql ="SELECT * FROM `data_movie_budget` where MovieID = 0 limit 10000 ";
$q = $pdo->prepare($sql);
$q->execute();
$q->setFetchMode(PDO::FETCH_ASSOC);


while ($r = $q->fetch()) {

    $movie_date = $r['Release_Date'];
    if (preg_match('#\.([0-9]{4})#',$movie_date,$m))
    {
        $date_need = $m[1];

    }
    $movie_title= $r['Movie'];


    ////looking for this movie in the movie table

if ($movie_title && $date_need)
{
///echo  $movie_title.' '.$date_need;
$result_id = checktable($movie_title,$date_need);
//echo  $result_id;


    if ($result_id)
    {
     add_id_to_table($r['id'], $result_id);
    }
}



};
