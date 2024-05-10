<?php
/**
 * The template used for displaying actor content
 *
 * @package WordPress
 * @subpackage Twenty_Sixteen
 * @since Twenty Sixteen 1.0
 */
?>
		<?php

        global $actor_meta;
        $array_verdict =["gender"=>"Gender","ethnic"=> "Ethnicelebs", "jew"=>"JewOrNotJew", "kairos"=>"Facial Recognition by Kairos" ,"bettaface"=> "Facial Recognition by Betaface",
         "surname"=> "Surname Analysis",  "familysearch"=>  "FamilySearch" ,"forebears_rank" =>  "ForeBears" , "crowdsource"=>"Crowdsource" , "verdict"=>  "Verdict" ];






        ?>



<article id="post-actor-<?php echo $actor_meta['id']; ?>" >


        <div id="<?php echo $actor_meta['id'] ?>" class="movie_container actor_container single_post " >
            <div class="movie_poster">
                 <div class="image">
                    <div class="wrapper" style="min-width: 270px;min-height: 338px;">

                        <img loading="lazy" class="actor_poster" src="<?php echo $actor_meta['image'] ?>"
                            <?php if ($actor_meta['image_big']) { ?> srcset="<?php echo $actor_meta['image']; ?> 1x, <?php echo $actor_meta['image_big']; ?> 2x"<?php } ?> >
                    </div>
                </div>
            </div>
            <div class="movie_description">
                <div class="header_title entry-header">
                    <h1 class="entry-title"><?php echo $actor_meta['name']; ?></h1>
                </div>
                <div class="movie_description_container">
                    <div class="movie_summary" >

                        <?php



                        $formattedVerdicts = Actor_Data::formatVerdicts($actor_meta['id'], $actor_meta['verdict'], $actor_meta['name']);
                        echo $formattedVerdicts;


                        echo '<div class="small_desc"><div class="block"><span data-value="' . $actor_meta['id'] . '" class="actor_crowdsource_container">Please help improve ZR by correcting & adding data.<span data-value="custom_actor_crowdsource_' . $actor_meta['id'] . '" class="nte_info nte_open_down"></span> <a href="#" data-id="'.$actor_meta['id'].'" class="actors_link" > (Methodology)</a></span></div>';

                        ?>

                    </div>
                </div>
            </div>
        </div>
		<?php
//!class_exists('TMDB') ? include ABSPATH . "analysis/include/tmdb.php" : '';
//echo TMDB::getslug($actor_meta['name']);

// include ABSPATH . 'wp-content/themes/custom_twentysixteen/template/actors_template_single.php';

//        $content_inner = details_template('Actor (Star)','<div class="dmg_content" id="actor_data_dop" ><div id="custom_search?type_s"  data-value="actorstar_' . $actor_meta['id'] . '/sort_rrwt-desc" class="flex_content page_custom_block not_load"></div></div>',' active ');
//        $content_inner.= details_template('Actor (Main)','<div class="dmg_content" id="actor_data_dop" ><div id="custom_search?type_m"  data-value="actormain_' . $actor_meta['id'] . '/sort_rrwt-desc" class="flex_content page_custom_block not_load"></div></div>');
//        $content_inner.= details_template('Actor (All)','<div class="dmg_content" id="actor_data_dop" ><div id="custom_search?type_a"  data-value="actor_' . $actor_meta['id'] . '/sort_rrwt-desc" class="flex_content page_custom_block not_load"></div></div>');
//
//        $content_inner.= details_template('Director','<div class="dmg_content" id="actor_data_dop" ><div id="custom_search?type_d"  data-value="dir_' . $actor_meta['id'] . '/sort_rrwt-desc" class="flex_content page_custom_block not_load"></div></div>');
//        $content_inner.= details_template('Writer','<div class="dmg_content" id="actor_data_dop" ><div id="custom_search?type_w"  data-value="dirwrite_' . $actor_meta['id'] . '/sort_rrwt-desc" class="flex_content page_custom_block not_load"></div></div>');
//        $content_inner.= details_template('Casting Director','<div class="dmg_content" id="actor_data_dop" ><div id="custom_search?type_cd"  data-value="dircast_' . $actor_meta['id'] . '/sort_rrwt-desc" class="flex_content page_custom_block not_load"></div></div>');
//        $content_inner.= details_template('Producer','<div class="dmg_content" id="actor_data_dop" ><div id="custom_search?type_p"  data-value="dirprod_' . $actor_meta['id'] . '/sort_rrwt-desc" class="flex_content page_custom_block not_load"></div></div>');
//
//
//
//        $content ='<div class="accordion_section column-8">'.$content_inner.'</div>';


        $content ='<section class="no_pad single w100">
        <div class="column_header"><h2>Filmography:</h2></div>
        <div class="dmg_content" id="actor_data_dop" ><div id="custom_search?type_a"  data-value="sort_cast-desc/actor_' . $actor_meta['id'] . '" class="flex_content page_custom_block not_load"></div></div>
        
        <div class="column_header"><h2>Director:</h2></div>
        <div class="dmg_content" id="actor_data_dop" ><div id="custom_search?type_d"  data-value="sort_rrwt-desc/dirall_' . $actor_meta['id'] . '" class="flex_content page_custom_block not_load"></div></div>
    
        </section>
        
        
        ';


        echo $content;



       /// Actor_Data::actor_data_template($actor_meta['id']);




		?>




</article>
