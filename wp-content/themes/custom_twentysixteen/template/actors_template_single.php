<?php
////////check count reviews
function show_actors_template_single()
{



    !class_exists('Data_Loaded') ? include ABSPATH . "wp-content/themes/custom_twentysixteen/template/include/dataloaded.php" : '';
    require('section_home_template.php');
    global $post_id;

    if (!$post_id && isset($_GET['id']))
    {
        $post_id = $_GET['id'];
    }
    global $section;
    global $post_type;


///$post_id = get_the_ID();

    $array_list = array(
        'stars' => array('title' => 'Stars:', 'id' => 'actor_data?stars', 'class' => 'single section_gray section_actors loaded'),
        'main' => array('title' => 'Supporting cast:', 'id' => 'actor_data?main', 'class' => 'single section_gray section_actors loaded'),
        'extra' => array('title' => 'Other cast:', 'id' => 'actor_data?extra', 'class' => 'single section_gray section_actors loaded'),
        'directors' => array('title' => 'Production:', 'id' => 'actor_data?directors', 'class' => 'single section_gray section_actors loaded'),
    );

    $content_actors = '';

    foreach ($array_list as $array_type => $value) {
        $content_inner = $section;
        foreach ($value as $id => $name) {
            $content_inner = str_replace('{' . $id . '}', $name, $content_inner);
        }


        $content_inner = str_replace('not_load ', ' ', $content_inner);
        $content_inner = str_replace('{post_id}', $post_id, $content_inner);


        $video_items = Data_Loaded::get_data_content($post_id, $array_type, 'actor_data');
        $content_inner = str_replace('{content}', $video_items, $content_inner);
        if ($array_type == 'extra') {
            $content_actors .= '<details  class="dark actor_details" >
   <summary>Other Cast, Production</summary>
        <div>';

        }

        $content_inner = preg_replace('/\{[a-z_]+\}/', '', $content_inner);

        $content_actors .= $content_inner;


    }
    $content_actors .= '</div></details>';

    $content = '';

    $allow_types = array("Movie" => 'Movies', "TVseries" => 'Shows', "VideoGame" => 'Games');

    $ptipe = $allow_types[$post_type];
    if (!$ptipe) $ptipe = 'Stuff';

    !class_exists('OptionData') ? include ABSPATH . "analysis/include/option.php" : '';
    $cast_demographic_note = OptionData::get_options('','cast_demographic_note');

    $content .= '<div class="movie_total_rating" id="movie_rating" data-value="' . $post_id . '"></div>';

    $ajax_data = Data_Loaded::get_movie_total_rating($post_id);
    if ($ajax_data) {
        $content .= '<script type="text/javascript">var movie_rating_data =' . $ajax_data . ';
    jQuery(document).ready(function () {
add_movie_rating("movie_rating", movie_rating_data);
});
</script>';
    }


    $content .= '<div class="detail_container" >
<details  class="dark actor_details" >
   <summary>Cast Demographics</summary>
<div>

<div class="desc"> <span data-value="cast_demographic_popup" class="nte_info nte_right nte_open_down"></span>
'.$cast_demographic_note.'
</div>

' . $content_actors . '</div>
</details>';


    $content .= '<details class="dark actor_details" >
   <summary>Representation</summary>
<div class="dmg_content" id="actor_data_dop" ><div id="actor_representation"  data-value="' . $post_id . '" class="not_load"></div></div>
</details>


</details>';

    $keyword_data = Data_Loaded::get_keywords($post_id);

    $content .= '<details class="dark actor_details" >
   <summary>Tags / Keywords</summary>
<div class="dmg_content" id="actor_data_dop" ><div id="tags_keywords"  data-value="' . $post_id . '" class="loaded">' . $keyword_data . '</div></div>
</details>';


    $content .= '<details class="dark actor_details" >
   <summary>Similar ' . $ptipe . '</summary>
<section class="dmg_content inner_content" id="actor_data_dop" >
        <div  id="similar_movies" data-name="' . $ptipe . '" data-value="' . $post_id . '" class="not_load"></div>
</section>
</details>
</details>


<details class="dark actor_details" >
   <summary>Family Friendly Breakdown</summary>
<section class="dmg_content inner_content" id="actor_data_dop" >
        <div  id="family_friendly" data-value="' . $post_id . '" class="not_load"></div>
</section>
</details>

</div>
';
    $content.='<section class="inner_content no_pad"><div class="column_header">
                    <h2>Global Zeitgest:</h2>
                </div><div  id="global_zeitgeist" data-value="' . $post_id . '" class="not_load"></div></section>';
    echo $content;
}
//<details class="dark actor_details" >
//   <summary>Global Consensus</summary>
//<section class="dmg_content inner_content" id="actor_data_dop" >
//        <div  id="global_zeitgeist" data-value="' . $post_id . '" class="not_load"></div>
//</section>
//</details>

function show_actors_template_single_cache()
{

    global $post_id;

    if (!function_exists('wp_custom_cache')) {
        require(ABSPATH . 'wp-content/themes/custom_twentysixteen/template/include/custom_cahe.php');
    }

   $cache = wp_custom_cache('p-'.$post_id.'_show_actors_template_single_1', 'fastcache', 3600);
   //echo $cache;
   show_actors_template_single();
}



?>

