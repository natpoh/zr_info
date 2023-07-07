<?php

if (!defined('ABSPATH')) {
    define('ABSPATH', $_SERVER['DOCUMENT_ROOT'] . '/');
}

require_once(ABSPATH . 'wp-load.php');


//DB config
!defined('DB_HOST_AN') ? include ABSPATH . 'analysis/db_config.php' : '';
//Abstract DB
!class_exists('Pdoa') ? include ABSPATH . "analysis/include/Pdoa.php" : '';


function get_chan_data($id, $title)
{
    $tok = explode(" ", $title);

    $title_array = [];
    foreach ($tok as $val) {
        $title_array[trim(strtolower($val))] = 1;
    }

    $file = ABSPATH . 'temp/tag_cloud/' . $id;
    $display = '';
    $gzcontent='';
    if (file_exists($file)){
        $gzcontent = file_get_contents($file);    
    }
    if ($gzcontent) {
        $content = gzdecode($gzcontent);
    } else {
        return;
    }
    if ($content) {
        $content_array = json_decode($content, 1);
    }
    $content_data = '';
    $k = 10;
    $array_all = [];
    $count = count($content_array);
    $array_genre = [];

    !class_exists('OptionData') ? include ABSPATH . "analysis/include/option.php" : '';
    $words_exclude =  OptionData::get_options('','words_exclude');
    if ($words_exclude)
    {
        $words_exclude =  str_replace('\\','',$words_exclude);
        $words_exclude_array = explode(',',$words_exclude);
    }


    foreach ($content_array as $block => $data) {


        $count_data = count($data);
        //echo '$count_data='.$count_data;
        $array_genre[] = $block;
        $i = 0;
        $link = 'https://archive.4plebs.org/_/search/boards/'.$block.'/text/%22' . urlencode($title) . '%22/';
        $content_data .= '<a id="' . $block . '" class="wordcloud" ' . $display . ' href="'.$link.'" target="_blank">';

        if (!$display) {
            $display = ' style="display:none" ';
        }
        foreach ($data as $name => $weight) {


            if (!$title_array[$name] && !in_array($name,$words_exclude_array)) {
                $array_all[$name] += $weight / $count;

               $weight = $weight * $k;

                if ($weight > 200) {
                    $weight = 200 + ($weight-200) / 10;
                }
              $weight = round($weight, 0);
                if ($weight<20)
                {
                    $weight=20;
                }

                $i++;
                $content_data .= '<span data-weight="' . $weight . '">' . $name . '</span>' . PHP_EOL;
            }
            if ($i >= 200) {
                break;
            }
        }

        $content_data .= '</a>';
    }


    if ($array_all) {
        $link_data = implode('.',$array_genre);

        $link = 'https://archive.4plebs.org/_/search/boards/'.$link_data.'/text/%22' . urlencode($title) . '%22/';
        $i = 0;
        arsort($array_all);
        $content_data .= '<a id="'.$link_data.'" class="wordcloud" ' . $display . ' href="'.$link.'" target="_blank">';
        foreach ($array_all as $name => $weight) {
            $i++;
            $weight = $weight * $k;
            if ($weight > 200) {
                $weight = 200 + ($weight-200)  / 10;
            }
            $weight = round($weight, 0);

            if ($weight<20)
            {
                $weight=20;
            }
            $content_data .= '<span data-weight="' . $weight . '">' . $name . '</span>' . PHP_EOL;

            if ($i >= 200) {
                break;
            }
        }
        $content_data .= '</a>';


    }
    if ($content_data) {
        $content_data = '<a class="open_popup gl_zr_extlink" target="_blank" href="'.$link.'"></a><div class="s_container forchan">' . $content_data . '</div>';
    }

    $genre = '';
    $selected = ' selected ';
    if ($array_genre) {
        foreach ($array_genre as $g) {

            $genre .= '<input class="blue_btn' . $selected . '" type="button" dataid="' . $g . '" value="/' . $g . '/" >';
            if ($selected) {
                $selected = '';
            }
        }
        $all = implode('.',$array_genre);
        $genre .= '<input class="blue_btn" type="button" dataid="'.$all.'"  value="All" >';

    }


    return ['content' => $content_data, 'genre' => $genre];
}


if (isset($_GET['id'])) {
    $movie_id = (int)$_GET['id'];


    $sql = "SELECT * FROM `data_movie_imdb` where `id` ='" . $movie_id . "' limit 1 ";
    $r = Pdo_an::db_fetch_row($sql);

    $movie_title = $r->title;
    $year = $r->year;
    $type=  $r->type;

    $prefix = 'tv';

    $array_btn = ['pol','tv'];

    $link = 'https://archive.4plebs.org/_/search/boards/'.$prefix.'/text/%22' . urlencode($movie_title) . '%22/';
    if ($type=='VideoGame')
    {
        $prefix = 'v';

        $array_btn = ['v','vg','vm'];
        $link = 'https://arch.b4k.co/_/search/boards/'.$prefix.'/text/%22' . urlencode($movie_title) . '%22/';

    }
//filter:verified


    $genre = '';
    $selected = ' selected ';

        foreach ($array_btn as $g) {

            $genre .= '<input class="blue_btn' . $selected . '" type="button" dataid="' . $g . '" value="/' . $g . '/" >';
            if ($selected) {
                $selected = '';
            }
        }
        $all = implode('.',$array_btn);
        $genre .= '<input class="blue_btn" type="button" dataid="'.$all.'"  value="All" >';


    ///4chan
    $clud = get_chan_data($movie_id, $movie_title);
    if (!$clud) {
        $clud['content'] =
            '<a class="open_popup gl_zr_extlink" target="_blank" href="'.$link.'"></a><div class="s_container forchan smoched"><a id="no_cloud" class="wordcloud"  href="'.$link.'" target="_blank"></a></div>';

        $clud['genre'] = $genre;
    }


    $content = '<div class="column_inner_content 4chan_review"  > <h3 class="column_header">4Chan:</h3>
                       <div class="chan_big_block"></div>
                        
           ' . $clud['content'] . '
                       
                    <div class="column_inner_bottom fchan_btn" data_title = "%22' . urlencode($movie_title) . '%22">
                   ' . $clud['genre'] . '
                    </div>
                        </div>';


    $atts = array('search' => '"' . $movie_title . '"  lang:en min_retweets:1000');
    $tw_content = ctf_init($atts);

    if (strstr($tw_content, 'Unable to load Tweets')) {

    }
    else {

        $content .= '<div class="column_inner_content twitter_content">
<div class="twitter_content_main">
<div class="popup-close"></div>
            <h3 class="column_header">Twitter:</h3>

            <div class="s_container smoched">
                <div class="column_inner_content_data">
                    <div  id="twitter_scroll?unverified" class="s_container_inner">' . $tw_content . '</div>
                    <div class="s_container_load"></div>
                </div>
            </div>
            <div class="column_inner_bottom">
            
             <a class="twiiter_link" target="_blank" href="https://twitter.com/search?q=' . urlencode($movie_title) . '&src=typed_query&pf=on">Mentioned by people you follow ></a>
</div>
    </div>
    <div class="calobl"></div>       
            
        </div>';
    }
//    $link = 'https://camas.unddit.com/#{%22resultSize%22:100,%22query%22:%22'.urlencode($movie_title).'%22}';
//    $content.= '<div class="column_inner_content 4chan_review"  > <h3 class="column_header">Reddit:</h3>
//                        <div class="s_container smoched">
//                            <div ><iframe src="' . $link. '"></iframe></div>
//                            <div class="s_container_smoth">
//
//                            </div>
//                        </div></div>';


    echo $content;

}


?>