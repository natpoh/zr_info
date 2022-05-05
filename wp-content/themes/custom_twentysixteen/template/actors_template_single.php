<?php
////////check count reviews


require('video_item_template_single.php');
require('section_home_template.php');
global $post_id;
///$post_id = get_the_ID();

$array_list = array(
    'stars' => array('title' => 'Stars:', 'id' => 'actor_data?stars', 'class' => 'single section_gray section_actors'),
    'main' => array('title' => 'Supporting cast:', 'id' => 'actor_data?main', 'class' => 'single section_gray section_actors'),
    'extra' => array('title' => 'Other cast:', 'id' => 'actor_data?extra', 'class' => 'single section_gray section_actors'),
    'directors ' => array('title' => 'Production:', 'id' => 'actor_data?directors', 'class' => 'single section_gray section_actors'),
);

$video_items='';

for ($i = 1; $i <= 5; $i++) {
    $video_items.= str_replace('{id}', $i, $video_template);
}


$content = '';
foreach ($array_list as $array_type=> $value)
{
    $content_inner=$section;
    foreach ($value as $id=>$name)
    {
        $content_inner = str_replace('{'.$id.'}',$name,$content_inner);
    }

    $content_inner = str_replace('{content}',$video_items,$content_inner);
    $content_inner = str_replace('{post_id}',$post_id,$content_inner);

    if ($array_type=='extra')
    {
        $content.=   '<details  class="dark actor_details" >
   <summary>Other Cast, Production</summary>
<div>';

    }

    $content_inner = preg_replace('/\{[a-z_]+\}/','',$content_inner);

    $content.=$content_inner;


}
$content.=  '</div></details>';

$content = '<div class="movie_total_rating not_load" id="movie_rating" data-value="'.$post_id.'"></div>
<details  class="dark actor_details" >
   <summary>Cast Demographics</summary>
<div><div class="desc"> <b>NOTE:</b> For the largest &amp; most objective database possible, these verdicts are automated. To check our sources and/or submit a manual correction, click the image of the cast member. 
<br>
<br>
We are constantly expanding and improving our algorithms. But it is time consuming &amp; expensive, so please consider <u><a href="https://cointr.ee/rightwingtomato" target="_blank">donating</a></u> or helping us crowdsource. For suggestions, check <u><a href="https://rightwingtomatoes.com/feedback" target="_blank">our feedback page</a></u> to see if we are already planning on it.
<br>
<br>
Thank you!</div>'.$content.'</div>
</details>
<details class="dark actor_details" >
   <summary>Representation</summary>
<div class="dmg_content" id="actor_data_dop" ><div id="actor_representation"  data-value="'.$post_id.'" class="not_load"></div></div>
</details>';

echo $content;

?>

