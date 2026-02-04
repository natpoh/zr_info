<?php
error_reporting('E_ERROR');
set_time_limit(0);
ini_set('display_errors','On');

include 'db_config.php';

function check_val($imdbid,$id)
{

global $pdo;


    $sql = "SELECT  id FROM data_movies_bechdeltest where imdbid =? LIMIT 1";

    $q = $pdo->prepare($sql);
    $q->execute([$imdbid]);
    $q->setFetchMode(PDO::FETCH_ASSOC);

    $r = $q->fetch();

    if  (!$r['id'])
    {
        $sql="INSERT INTO `data_movies_bechdeltest`  VALUES (NULL, ? , ? , NULL, NULL, NULL, NULL);";
        $q = $pdo->prepare($sql);
        $q->execute([$id,$imdbid]);
    }
    else
    {
        echo $imdbid.' exists ';
    }

}

function update_table($imbid)
{

    global $pdo;


    echo ' $imbid '.$imbid .'<br>';

    $url = 'http://bechdeltest.com/api/v1/getMovieByImdbId?imdbid='.$imbid;
    $content = file_get_contents($url);

    if ($content) {
        $o = json_decode($content);

    ////    var_dump($o);

        $updateParams = [

            ':rating' => $o->rating,
            ':title' => $o->title,
            ':year' => $o->year,
            ':dubious' => $o->dubious,
            ':imdbid' => $o->imdbid,


        ];


        $sql2 = "UPDATE `data_movies_bechdeltest` SET `rating` = :rating, `title` = :title, `year` = :year, `dubious` = :dubious WHERE `imdbid` = :imdbid";
        $q2 = $pdo->prepare($sql2);

        $q2->execute($updateParams);
    }

}


function download_bechdeltest()
{

    global $pdo;


    $sql = "SELECT  imdbid  FROM data_movies_bechdeltest where title IS NULL LIMIT 50";

    $q = $pdo->prepare($sql);
    $q->execute([]);
    $q->setFetchMode(PDO::FETCH_ASSOC);

    while ($r = $q->fetch())
    {
       $imbid=  $r['imdbid'];

        update_table($imbid);


    }


}


///// load data from the api server
function parse_bechdeltest()
{
 $url = 'http://bechdeltest.com/api/v1/getAllMovieIds';
 $content = file_get_contents($url);

 if ($content)
 {

  $object = json_decode($content);
 }
////var_dump($object);

/////add data to the table
    $i=0;

  foreach ($object as $val)
  {

$imdbid = $val->imdbid;
$id = $val->id;
///проверяем в базе данных
check_val($imdbid,$id);

$i++;
/*
if($i>5)
{
    return;
}
*/

echo $imdbid.'<br>';
  }



}


return;
pdoconnect();
//parse_bechdeltest();
download_bechdeltest();