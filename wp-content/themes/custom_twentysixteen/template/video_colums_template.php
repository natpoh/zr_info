<?php

require('video_item_template.php');
require('section_home_template.php');

wp_enqueue_style('colums_template', get_template_directory_uri() . '/css/colums_template.css', array(), LASTVERSION);
wp_enqueue_script('spoiler-min', get_template_directory_uri() . '/js/spoiler.min.js', array('jquery'));
wp_enqueue_script('section_home', get_template_directory_uri() . '/js/section_home.js', array('jquery'), LASTVERSION);

global $cfront;

include ABSPATH . 'wp-content/themes/custom_twentysixteen/template/compilation.php';
$array_list = Compilation_link::get_home_blocks();

// add scripts
$scrpts = array();
//gmi('scroll before');
$scrpts[] = '<script  type="text/javascript" >';
$mids = array();
$user = wp_get_current_user();

foreach ($array_list as $value) {
    $scoll_id = $value['id'];
    $data = $cfront->get_scroll($scoll_id, 0, 1, true);

    if ($user->ID) {
        // Try to get movies ids
        try {
            $json_data = json_decode($data);

            $mids_loc = $json_data->mids;
            if ($mids_loc) {
                $mids = array_merge($mids, $mids_loc);
            }
        } catch (Exception $exc) {
            
        }
    }
    if ($data) {
        $data = '"' . addslashes($data) . '"';
    } else {
        $data = 'null';
    }
    $scrpts[] = 'var ' . $scoll_id . '_data = ' . $data . '; ';
    // Pro tags
    if ($scoll_id == 'review_scroll') {
        $tags = json_encode($cfront->cm->get_tags(1));
        $scrpts[] = 'var ' . $scoll_id . '_tags = ' . $tags . '; ';
    }
}

$in_list=array();
if ($mids) {
    arsort($mids);
    if($user->ID){
        // Get watchlists
        $wl = $cfront->cm->get_wl();
        $in_list = $wl->in_def_lists($user->ID, $mids);
        $scrpts[] = 'var watch_lists_data = ' . json_encode($in_list) . '; ';
    }
}
$scrpts[] = 'var watch_lists_data = ' . json_encode($in_list) . '; ';

//gmi('scroll after');
$scrpts[] = '</script>';
print (implode("\n", $scrpts));

$video_items = '';
global $video_template;
global $section;

for ($i = 1; $i <= 5; $i++) {
    $video_items .= str_replace('{id}', $i, $video_template);
}
$pid = $value['pid'];
if (!$pid)
    $pid = '';
$content = '';

foreach ($array_list as $index => $value) {
    $content_inner = $section;
    foreach ($value as $id => $name) {
        if ($id == 'tabs') {
            // Tabs logic
            $name_arr = $name;
            $t = '<ul class="tab-wrapper home-tabs tabs-btn audience-tab">';
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


    if (strstr($index, 'compilation_')) {


        $content_inner = str_replace('{post_id}', $pid, $content_inner);
    }

    $content_inner = str_replace('{content}', $video_items, $content_inner);

    $content_inner = preg_replace('/\{[a-z_]+\}/', '', $content_inner);
    $content .= $content_inner;
}

echo $content;
?>

