<?php
if (function_exists('get_template_directory')) {
    if (!function_exists('get_poster_tsumb')) {
        require(get_template_directory() . '/template/include/create_tsumb.php');
    }


    require(get_template_directory() . "/template/include/pccf_filter.php");
    require(get_template_directory() . '/template/include/lib_feed_pro.php');
    require(get_template_directory() . '/template/plugins/spoiler_plugin.php');
    require (get_template_directory() . '/template/include/get_full_staff.php');


} else {
    if (!function_exists('get_poster_tsumb')) {
        require('create_tsumb.php');
    }

    require("pccf_filter.php");
    require('lib_feed_pro.php');
    require('../plugins/spoiler_plugin.php');
    require ('get_full_staff.php');

}




if (!function_exists('get_template_critics_custom'))
{
    function get_template_critics_custom($pid_array,$movie_id)
    {
        global $enable_reactions;
        $enable_reactions=true;

            foreach ($pid_array as $pid=>$data) {

               // var_dump($data['wprss_feed_from']);

                    if ($data['wprss_feed_from']==2)///audience
                    {

                        if (function_exists('get_post_data'))
                        {
                            $review_title = get_post_data($pid, 'post_name', 'ID', 'posts');
                        }
                        else if (function_exists('get_post')) {

                                $post = get_post($pid);
                            $review_title = $post->post_name;

                            }

                        $link_data = WP_SITEURL.'/audience/'.$review_title.'/';


                        $inner_content = get_audience_templ('', $link_data, $pid, '', 0);
                    }
                    else if ($data['wprss_feed_from']==0)///staf
                    {
                        $inner_content = get_feed_pro_templ($pid, '', '', $movie_id, 1, 0);
                    }
                    else if ($data['wprss_feed_from']==1){
                        ///pro
                        $inner_content = get_feed_pro_templ($pid, '', '', $movie_id, '', 0);

                    }
                    if ($content && $inner_content)
                    {
                        $content.='<div class="after_post"></div>'.$inner_content;
                    }
                    else
                    {
                        $content.=$inner_content;
                    }


            }

        return $content;
    }
}