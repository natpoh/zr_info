<?php
set_time_limit(0);

include  ('get_data.php');
function set_option($id,$option)
{
    global $pdo;

    $sql = "DELETE FROM `options` WHERE `options`.`id` = ".$id;
    $q = $pdo->prepare($sql);
    $q->execute();



    $sql = "INSERT INTO `options`  VALUES ('".$id."',?,'')";


    $q = $pdo->prepare($sql);
    $q->execute(array($option));
//print_r($q->errorInfo());

}
global $pdo;
///////check and create cache
$options=array();
$sql = "SELECT * FROM `options` ";

$q = $pdo->prepare($sql);
$q->execute();
while ($r = $q->fetch())
{
    //  var_dump($r);
    $options[ $r['id']] =  $r['val'];
}
$last_id=0;
if ($options[2])
{
    $last_id = $options[2];

}


$sql = "Select MovieID FROM `data_movie_rank` where  (`Box Office Domestic` > 0 OR  `Box Office International` > 0 or `Box Office Worldwide`) and MovieID>".$last_id."  order by MovieID limit 100 ";


/// echo $sql.'<br>';

$q = $pdo->prepare($sql);
$q->execute();
$q->setFetchMode(PDO::FETCH_ASSOC);

$countmovies = 0;


while ($r = $q->fetch()) {

    if ($r['MovieID'] > 0) {


            $sql2 =" DELETE FROM `data_movie_ethnic` WHERE   movie_id = '" . $r['MovieID'] . "'";
            $qx = $pdo->prepare($sql2);
            $qx->execute();

        $sql2 = "DELETE FROM `data_movie_actor_cache` WHERE  movie_id = '" . $r['MovieID'] . "'";
        $qx = $pdo->prepare($sql2);
        $qx->execute();

        $array_movie_result = get_movie_data_from_db($r['MovieID'], '', 1);
        set_option(2,$r['MovieID']);
        echo $r['MovieID'].' add<br>'.PHP_EOL;
    }
}