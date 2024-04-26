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
        global $post_name;
        global $actor_meta;
        $array_verdict =["gender"=>"Gender","ethnic"=> "Ethnicelebs", "jew"=>"JewOrNotJew", "kairos"=>"Facial Recognition by Kairos" ,"bettaface"=> "Facial Recognition by Betaface",
         "surname"=> "Surname Analysis",  "familysearch"=>  "FamilySearch" ,"forebears_rank" =>  "ForeBears" , "crowdsource"=>"Crowdsource" , "verdict"=>  "Verdict" ];



        ?>



<article id="post-actor-<?php echo $post_name; ?>" >

	<div class="entry-content">

        <div id="<?php echo $post_name ?>" class="movie_container actor_container single_post " >
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
                        <span data-value="actor_popup" class="nte_info nte_right_top nte_open_down"></span>
                        <?php

                        foreach ($actor_meta['verdict'] as $i=>$v)
                        {

                            if ($i =='crowdsource')
                            {
                                 echo '<div class="single_flex"><div class="block"><span data-value="'.$post_name.'" class="actor_crowdsource_container">'.$array_verdict[ $i].'
<span data-value="custom_actor_crowdsource_'.$post_name.'" class="nte_info nte_open_down"></span> 
</span></div>
<div class="block">'.strtoupper( $v).'</div></div>';
                            }

                            else
                            {
                                $class='';
                                if ($i =='verdict')
                                {
                                    $class =' yellow ';
                                }

                                echo '<div class="single_flex'.$class.'"><div class="block"><span>'.$array_verdict[ $i].'</span></div><div class="block">'.strtoupper( $v).'</div></div>';
                            }



                        }

                            ?>
                    </div>
                </div>
            </div>
        </div>
		<?php
        include ABSPATH . 'wp-content/themes/custom_twentysixteen/template/actors_template_single.php';

        $content_inner = details_template('Star','<div class="dmg_content" id="actor_data_dop" ><div id="custom_search"  data-value="' . $post_name . '" class="page_custom_block not_load"></div></div>');
        $content_inner.= details_template('Main','<div class="dmg_content" id="actor_data_dop" ><div id="custom_search"  data-value="' . $post_name . '" class="page_custom_block not_load"></div></div>');

        $content ='<div class="accordion_section column-2">'.$content_inner.'</div>';
        echo $content;

        global $post_name;

       /// Actor_Data::actor_data_template($post_name);




		?>
	</div><!-- .entry-content -->



</article>
