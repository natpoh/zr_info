<?php


function last_compilation_scroll()
{
    $li='';

$q = "SELECT * FROM `meta_compilation_links` WHERE `enable` = 1   ORDER BY CAST(`weight` AS UNSIGNED) DESC ";
$ru = Pdo_an::db_results_array($q);
foreach ($ru as $row)
{
    $title = $row['title'];
    $id = $row['id'];
    $li.='<li data-value="'.$id.'">'.$title.'</li>';

}

    $randomKey = array_rand($ru);
    $randomValue = $ru[$randomKey];
    $title = $randomValue['title'];
    $id = $randomValue['id'];

$content ='<div class="dropdown">
  <span class="dropdown_button"></span>
  <ul class="dropdown-content" id="myList" >
'.$li.'
  </ul>
</div>';


    $title='<span class="block_title">'.$title.'</span>:'.$content;

return [$id,$title];


}
