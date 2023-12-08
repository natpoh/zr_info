<?php
////////check count reviews

function details_template($title,$data,$active='')
{
//        $result= '<details  class="dark actor_details" >
//   <summary>'.$title.'</summary>
//        <div>'.$data. '</div>
//        </details>';


    $result= '
        <div class="accordion-item'.$active.'">
            <div class="accordion-header">'.$title.'</div>
            <div class="accordion-content">'.$data. '</div>
        </div>
        ';

return $result;
}
function show_actors_template_single()
{

    global $section;

    !class_exists('Data_Loaded') ? include ABSPATH . "wp-content/themes/custom_twentysixteen/template/include/dataloaded.php" : '';
    require('section_home_template.php');
    global $post_id;

    if (!$post_id && isset($_GET['id']))
    {
        $post_id = $_GET['id'];
    }

    global $post_type;


///$post_id = get_the_ID();

    $array_list = array(
        'stars' => array('title' => 'Stars:', 'id' => 'actor_data?stars', 'class' => 'single section_gray section_actors loaded'),
        'main' => array('title' => 'Supporting cast:', 'id' => 'actor_data?main', 'class' => 'single section_gray section_actors loaded'),
        'extra' => array('title' => 'Other cast:', 'id' => 'actor_data?extra', 'class' => 'single section_gray section_actors loaded'),
        'directors' => array('title' => 'Production:', 'id' => 'actor_data?directors', 'class' => 'single section_gray section_actors loaded'),
    );

    $content_actors = '';
    $content_other='';

    foreach ($array_list as $array_type => $value) {
        $content_inner = $section;
        foreach ($value as $id => $name) {
            $content_inner = str_replace('{' . $id . '}', $name, $content_inner);
        }


        $content_inner = str_replace('not_load ', ' ', $content_inner);
        $content_inner = str_replace('{post_id}', $post_id, $content_inner);


        $video_items = Data_Loaded::get_data_content($post_id, $array_type, 'actor_data');

        $content_inner = str_replace('{content}', $video_items, $content_inner);

        $content_inner= preg_replace('/\{[a-z_]+\}/', '', $content_inner);

        if ($array_type == 'extra' || $array_type == 'directors') {

            $content_other.= $content_inner;

        }
        else
        {
            $content_actors .= $content_inner;
        }

    }

    if ($content_other)
    {

        $content_other =   details_template('Other Cast, Production',$content_other);

        $content_actors .= $content_other;

    }




    $content_rating='';
    $content = '';

    $allow_types = array("Movie" => 'Movies', "TVseries" => 'Shows', "VideoGame" => 'Games');

    $ptipe = $allow_types[$post_type];
    if (!$ptipe) $ptipe = 'Stuff';

    !class_exists('OptionData') ? include ABSPATH . "analysis/include/option.php" : '';
    $cast_demographic_note = OptionData::get_options('','cast_demographic_note');

    $content_rating= '<div class="movie_total_rating" id="movie_rating" data-value="' . $post_id . '"></div>';

    $ajax_data = Data_Loaded::get_movie_total_rating($post_id);
    if ($ajax_data) {
        $content_rating .= '<script type="text/javascript">var movie_rating_data =' . $ajax_data . ';
    jQuery(document).ready(function () {
add_movie_rating("movie_rating", movie_rating_data);
});
</script>';
    }


if ($post_type!='VideoGame') {

    $title='Cast';
    $data='<div class="desc"> <span data-value="cast_demographic_popup" class="nte_info nte_right nte_open_down"></span>' . $cast_demographic_note . '</div>' . $content_actors ;

    $content .= details_template($title,$data);


    $data='<div id="actor_representation"  data-value="' . $post_id . '" class="not_load"></div>';

    $content .= details_template('Representation',$data);



}
else
{

    $content .= details_template('Characters','<div class="dmg_content" id="actor_data_dop" ><div id="google_characters"  data-value="' . $post_id . '" class="page_custom_block not_load"></div></div>');



}





    $keyword_data = Data_Loaded::get_keywords($post_id);

    $content .= details_template('Tags / Keywords',$keyword_data);


    $content .=   details_template('Similar ' . $ptipe ,'<section class="dmg_content inner_content" id="actor_data_dop" ><div  id="similar_movies" data-name="' . $ptipe . '" data-value="' . $post_id . '" class="not_load"></div></section>');


    $content .= details_template('Parental Guide','<section class="dmg_content inner_content" id="actor_data_dop" ><div  id="family_friendly" data-value="' . $post_id . '" class="not_load"></div></section>');
$colum=7;
if ($post_type=='VideoGame') {

    $content .= details_template('Global Consensus','<section class="dmg_content inner_content" id="actor_data_dop" >
        <div  id="google_global_games" data-value="' . $post_id . '" class="page_custom_block not_load"></div></section>');

$colum=6;
}



else if ($post_type!='VideoGame') {

$content .= details_template('Global Consensus','<section class="dmg_content inner_content" id="actor_data_dop" >
        <div  id="global_zeitgeist" data-value="' . $post_id . '" class="page_custom_block not_load"></div></section>', ' active global_zr');





//    $content .= '<section class="inner_content no_pad global_zeitgeist_container"><div class="column_header">
//                    <h2>Global Zeitgest:</h2>
//                </div><div  id="global_zeitgeist" data-value="' . $post_id . '" class="not_load"></div></section>';

}

    $content =$content_rating.'<div class="accordion_section column-'.$colum.'">'.$content.'</div>';
    echo $content;



}


function show_actors_template_single_cache()
{

    global $post_id;

    if (!function_exists('wp_custom_cache')) {
        require(ABSPATH . 'wp-content/themes/custom_twentysixteen/template/include/custom_cahe.php');
    }

 $cache = wp_custom_cache('p-'.$post_id.'_show_actors_template_single_1', 'fastcache', 3600);
  echo $cache;
 // show_actors_template_single();
}



?>

