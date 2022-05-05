<?php
////////check count reviews


require('video_item_template_single.php');
require('section_home_template.php');

wp_enqueue_style('colums_template', get_template_directory_uri() . '/css/colums_template.css', array(), LASTVERSION);
wp_enqueue_script('spoiler-min', get_template_directory_uri() . '/js/spoiler.min.js', array('jquery'));
wp_enqueue_script('section_home', get_template_directory_uri() . '/js/section_home.js', array('jquery'), LASTVERSION);

$array_list = array(
    'Pro' => array('title' => 'Critic Reviews:', 'id' => 'review_scroll', 'class' => 'single pro_review'),
    'Staff' => array('title' => 'Staff Reviews:', 'id' => 'stuff_scroll', 'class' => 'single stuff_review widthed secton_gray'),
    'Audience' => array('title' => 'Audience Reviews:', 'id' => 'audience_scroll', 'class' => 'single audience_review'),
    //'4chan' => array('title' => '4chan:', 'id' => 'chan_scroll', 'class' => '4chan_review')
);

$video_items='';

for ($i = 1; $i <= 5; $i++) {
    $video_items.= str_replace('{id}', $i, $video_template);
}


$content = '';
foreach ($array_list as $value)
{
    $content_inner=$section;
    foreach ($value as $id=>$name)
    {
        $content_inner = str_replace('{'.$id.'}',$name,$content_inner);
    }
    $content_inner = str_replace('{content}',$video_items,$content_inner);
    $content_inner = str_replace('{post_id}',$post_id,$content_inner);

    $content_inner = preg_replace('/\{[a-z_]+\}/','',$content_inner);

    $content.=$content_inner;
}

echo $content;

?>

