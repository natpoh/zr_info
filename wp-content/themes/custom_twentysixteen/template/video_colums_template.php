<?php

require('video_item_template.php');
require('section_home_template.php');

wp_enqueue_style('colums_template', get_template_directory_uri() . '/css/colums_template.css', array(), LASTVERSION);
wp_enqueue_script('spoiler-min', get_template_directory_uri() . '/js/spoiler.min.js', array('jquery'));
wp_enqueue_script('section_home', get_template_directory_uri() . '/js/section_home.js', array('jquery'), LASTVERSION);

global $cfront;

$array_list = array(
    'Audience' => array('title' => 'Latest Audience Reviews:', 'id' => 'audience_scroll', 'class' => 'audience_review widthed ',
       'tabs' => array('p' => 'Positive', 'n' => 'Negative', 'a' => 'Latest')),
        'Pro' => array('title' => 'Latest Critic Reviews:', 'id' => 'review_scroll', 'class' => 'pro_review widthed secton_gray'),


        'Video' => array('title' => 'New Movies:', 'id' => 'video_scroll', 'class' => ''),
    'TV' => array('title' => 'Popular Shows Streaming:', 'id' => 'tv_scroll', 'class' => ''),
      //  'Staff' => array('title' => 'Latest Staff Reviews:', 'id' => 'stuff_scroll', 'class' => 'stuff_review widthed', 'title_desc' => '<a class="title_desc" href="https://zeitgeistreviews.com/writers-wanted/" target="_blank">Writers Wanted</a>'),

);
// add scripts
$scrpts = array();
gmi('scroll before');
$scrpts[] = '<script  type="text/javascript" >';
foreach ($array_list as $value) {
    $scoll_id = $value['id'];
    $data = $cfront->get_scroll($scoll_id, 0, 1, true);
    if ($data) {
        $data = '"' . addslashes($data) . '"';
    } else {
        $data = 'null';
    }
    $scrpts[] = 'var ' . $scoll_id . '_data = ' . $data . '; ';
    // Pro tags
    if ($scoll_id=='review_scroll'){
        $tags = json_encode($cfront->cm->get_tags(1));
        $scrpts[] = 'var ' . $scoll_id . '_tags = '.$tags.'; ';
    }
}
gmi('scroll after');
$scrpts[] = '</script>';
print (implode("\n", $scrpts));

$video_items = '';

for ($i = 1; $i <= 5; $i++) {
    $video_items .= str_replace('{id}', $i, $video_template);
}

$content = '';
foreach ($array_list as $value) {
    $content_inner = $section;
    foreach ($value as $id => $name) {
        if ($id == 'tabs') {
            // Tabs logic
            $name_arr = $name;
            $t = '<ul class="tab-wrapper home-tabs audience-tab">';
            $i = 0;
            foreach ($name_arr as $k => $v) {
                $active = '';
                if ($i == 0) {
                    $active = ' active';
                }
                $t .= '<li class="nav-tab' . $active . ' tab-' . $k . '"><a href="#tab-' . $k . '" data-id="tab-' . $k . '">' . $v . '</a></li>';
                $i++;
            }
            $t .= '</ul>';
            $name = $t;
        }
        $content_inner = str_replace('{' . $id . '}', $name, $content_inner);
    }
    $content_inner = str_replace('{content}', $video_items, $content_inner);
    $content_inner = str_replace('{post_id}', '', $content_inner);
    $content_inner = preg_replace('/\{[a-z_]+\}/', '', $content_inner);
    $content .= $content_inner;
}

echo $content;
?>

