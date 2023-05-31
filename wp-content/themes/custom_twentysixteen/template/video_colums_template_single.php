<?php

////////check count reviews


require('video_item_template_single.php');
require('section_home_template.php');

wp_enqueue_style('colums_template', get_template_directory_uri() . '/css/colums_template.css', array(), LASTVERSION);
wp_enqueue_script('spoiler-min', get_template_directory_uri() . '/js/spoiler.min.js', array('jquery'));
wp_enqueue_script('awesomeCloud', get_template_directory_uri() . '/js/jquery.awesomeCloud-0.2.js', array('jquery'), LASTVERSION);
wp_enqueue_script('section_home', get_template_directory_uri() . '/js/section_home.js', array('jquery'), LASTVERSION);

global $post_id;
$array_list = array(
    'Audience' => array('title' => 'Audience Reviews:', 'id' => 'audience_scroll', 'class' => 'single audience_review',
    'tabs' => array('p' => 'Positive', 'n' => 'Negative', 'a' => 'All')),
    'Pro' => array('title' => 'Critic Reviews: <a href="#" id-data="'.$post_id.'" class="add_critic" >+add</a>', 'id' => 'review_scroll', 'class' => 'single pro_review'),
   // 'Staff' => array('title' => 'Staff Reviews:', 'id' => 'stuff_scroll', 'class' => 'single stuff_review widthed secton_gray'),

   '4chan' => array( 'title_desc'=> '<p class="content_warning"><span class="content_red_warning">CONTENT WARNING:</span> Foul language, offensive images, & possible spoilers.</p>',  'title' => 'Internet Zeitgest:', 'id' => 'twitter_scroll', 'class' => '4chan_review')
);

$video_items = '';

for ($i = 1; $i <= 5; $i++) {
    $video_items .= str_replace('{id}', $i, $video_template);
}

global $cfront;

$movie_id = $post_id;

// Audience post count
$post_count = $cfront->get_audience_post_count($movie_id);
$active_key = 'a';
$vote_scroll = 0;
if ($post_count['p']) {
  //  $active_key = 'p';
   // $vote_scroll = 1;
}

// add scripts
$scrpts = array();
gmi('scroll before');
$scrpts[] = '<script  type="text/javascript" >';
foreach ($array_list as $value) {
    $scoll_id = $value['id'];

        $data = $cfront->get_scroll($scoll_id, $movie_id, $vote_scroll);
        if ($data) {
            $data = '"' . addslashes($data) . '"';
        } else {


               $data = 'null';

        }
        $scrpts[] = 'var ' . $scoll_id . '_data = ' . $data . '; ';

}
gmi('scroll after');
$scrpts[] = '</script>';
print (implode("\n", $scrpts));

$content = '';
foreach ($array_list as $value) {
    $content_inner = $section;

    foreach ($value as $id => $name) {
        if ($id == 'tabs') {
            if ($post_count['a']) {
                // Tabs logic
                $name_arr = $name;
                $t = '<ul class="tab-wrapper home-tabs">';
                foreach ($name_arr as $k => $v) {
                    if (!$post_count[$k]) {
                        continue;
                    }
                    $active = '';
                    if ($k == $active_key) {
                        $active = ' active';
                    }
                    $t .= '<li class="nav-tab' . $active . '"><a href="#tab-' . $k . '" data-id="tab-' . $k . '">' . $v . ' (' . $post_count[$k] . ')</a></li>';
                }
                $t .= '</ul>';
                $name = $t;
            } else {
                $name = '';
            }
        }
        $content_inner = str_replace('{' . $id . '}', $name, $content_inner);
    }
    $content_inner = str_replace('{content}', $video_items, $content_inner);
    $content_inner = str_replace('{post_id}', $post_id, $content_inner);

    $content_inner = preg_replace('/\{[a-z_]+\}/', '', $content_inner);

    $content .= $content_inner;
}

echo $content;
?>

