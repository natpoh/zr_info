<?php

set_time_limit(0);

include  ('get_data.php');
function set_option($id,$option)
{
    global $pdo;

    $sql = "DELETE FROM `options` WHERE `options`.`id` = ".$id;
    $q = $pdo->prepare($sql);
    $q->execute();



    $sql = "INSERT INTO `options`  VALUES ('".$id."',?)";


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
if ($options[6])
{
    $last_id = $options[6];
}



$sql = "Select * FROM `data_crew` where ((Role ='Producer' and Detail like 'executive producer%') OR Role ='Casting')  and id >".$last_id."  order by ud limit 10";


/// echo $sql.'<br>';

$q = $pdo->prepare($sql);
$q->execute();
$q->setFetchMode(PDO::FETCH_ASSOC);

$countmovies = 0;


while ($r = $q->fetch()) {

    if ($r['crew_id'] > 0) {



    }}