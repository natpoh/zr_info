<form method="get" action="/search" id="search-form" class="main-search">
    <div class="flex-page">
        <div id="primary" class="content-full with-sidebar-left">
            <main id="main" class="site-main" role="main">
                <header class="page-header">
                    <h1 id="search-title" class="page-title ajload"><?php print $search_title ?></h1>
                </header><!-- .page-header -->
                <div id="spform">
                    <div class="sbar">
                        <a class="clear<?php if ($keywords) print ' active' ?>" href="/search" title="Clear"></a>                        
                        <input type="search" name="s" id="sbar" size="15" value="<?php print $keywords ?>" placeholder="Search Movies, TV, Reviews" autocomplete="off">
                    </div>
                    <input type="submit" id="submit" class="btn" value="find">        
                </div>
                <div class="filters_btn">
                    <a id="fiters-btn" class="search-filters-btn" href="#filters" title="Advanced search filters"><span class="filters-icon"></span> Search filters</a>                    
                </div>
                <?php $search_front->theme_search_url($search_url, $search_text, $inc); ?>
                <?php
                if ($show_content):
                    //Search tabs
                    print $search_tabs;
                    print $fiters;
                    print $sort;
                    ?> 
                    <div id="page-content" class="ajload">
                        <?php
                        // Critic posts
                        if ($tab_key == 'critics'):
                            ?>
                            <div class="flex_content_block">
                                <?php
                                if (sizeof($results['critics']['list'])):
                                    foreach ($results['critics']['list'] as $post):

                                        $post_arr = $search_front->get_top_movie_critic($post->id, $post->date_add);
                                        if (!$post_arr) {
                                            continue;
                                        }

                                        $mid = $post_arr['m_id'];

                                        if ($mid) {
                                            $title = $post_arr['title'];

                                            $cast = $post_arr['cast'];
                                            if ($cast) {
                                                $cast = $search_front->cm->crop_text($cast, 50);
                                            }

                                            $release = $post_arr['release'];
                                            if ($release) {
                                                $release = strtotime($release);
                                                $release = date('Y', $release);
                                                if (strstr($title, $release)) {
                                                    $release = '';
                                                } else {
                                                    $release = ' (' . $release . ')';
                                                }
                                            }
                                        }
                                        $post_content = $post_arr['content_pro'];
                                        if ($keywords) {
                                            if (preg_match('/<div class="vote_content">(.*)<\/div>/Us', $post_content, $match) ||
                                                    preg_match('/<a[^>]* class="icntn"[^>]*>(.*)<\/a>/Us', $post_content, $match)) {


                                                $post_title = str_replace('<b>', '<u>', $post->t);
                                                $post_title = str_replace('</b>', '</u>', $post_title);

                                                $new_content = '<strong>' . $post_title . '</strong><p>' . $post->c . '</p>';
                                                $post_content = str_replace($match[1], $new_content, $post_content);
                                                $post_content = $search_front->check_spoiler($post_content);
                                            }

                                            if ($mid) {
                                                $movie_title = str_replace('<b>', '<u>', $post->mt);
                                                $movie_title = str_replace('</b>', '</u>', $movie_title);
                                                $title = $movie_title;
                                            }
                                        }
                                        ?><div class="card search_review">
                                        <?php if ($mid) { ?>
                                                <div class="full_review_movie">
                                                    <a href="<?php print $post_arr['link'] ?>" class="movie_link" >
                                                        <img src="<?php print $post_arr['poster_link_90'] ?>">
                                                        <div class="movie_link_desc">
                                                            <span class="itm_hdr"><?php print $title . $release ?></span>
                                                            <span><?php print $cast ?></span>
                                                        </div>
                                                    </a>
                                                </div>
                                            <?php } ?>
                                            <?php print $post_content ?>
                                        </div><?php endforeach; ?>

                                    <?php
                                    print $search_front->pagination($results['critics']['count']);
                                    ?>
                                </div>
                                <?php
                            endif;
                        else:
                            ?>
                            <div class="flex_movies_block">
                                <?php
                                // Show movies and tv tab
                                $total_list = $results[$tab_key]['list'];
                                $total_count = $results[$tab_key]['count'];

                                if (sizeof($total_list)):
                                    $ma = $search_front->get_ma();
                                    $array_result = array();
                                    ?>
                                    <?php
                                    foreach ($total_list as $movie):
                                        global $post_an, $video_api;

                                        $post_an = $ma->get_post($movie->id);

                                        $ids = $movie->id;
                                        $title = $movie->title;
                                        $name = $search_front->get_or_create_ma_post_name($ids);
                                        $post_type = strtolower($movie->type);

                                        if ($post_type == 'movie' || $post_type == 'tvseries' || $post_type == 'videogame') {
                                            if (function_exists('template_single_movie')) {
                                                template_single_movie($ids, $title, $name);
                                                $content_result[$ids] = $ids;
                                            }
                                        }
                                        ?>

                                    <?php endforeach; ?>
                                    <?php
                                    !class_exists('RWT_RATING') ? include ABSPATH . "wp-content/themes/custom_twentysixteen/template/include/movie_rating.php" : '';
                                    $RWT_RATING = new RWT_RATING;
                                    $content_result = $RWT_RATING->get_rating_data($content_result);
                                    ?>
                                    <script type="text/javascript">
                                        var rating = <?php echo($content_result) ?>;
                                    </script>

                                <?php endif; ?>
                                <?php
                                print $search_front->pagination($total_count);

                                if ($keywords) {

                                    $after_search = false;
                                    $exclude = array();
                                    if (!$total_count) {
                                        // Not found 
                                        $after_search = true;
                                    } else {
                                        $per_page = $search_front->search_limit;
                                        $page = $search_front->get_search_page();
                                        $max_page = (int) $total_count / $per_page;
                                        if ($page >= $max_page) {
                                            // Last page
                                            $after_search = true;
                                            if ($results) {
                                                foreach ($results[$tab_key]['list'] as $index => $data) {
                                                    $exclude[$data->id] = 1;
                                                }
                                            }
                                        }
                                    }


                                    if ($after_search) {
                                        ?><h3 style="margin: 20px">Still Can't find it?</h3><?php
                                        $types = $search_front->filters['type'];

                                        $key = 'all';
                                        if ($types) {

                                            if (in_array('tv', $types)) {
                                                if (in_array('movies', $types)) {
                                                    $key = 'all';
                                                } else {
                                                    $key = 'tv';
                                                }
                                            } else {
                                                $key = 'movies';
                                            }
                                        }

                                        $array_result = array($keywords, $key, $exclude);
                                        $keywords_data = serialize($array_result);
                                        $keywords_data = urlencode($keywords_data);

                                        echo '<div id="search_ajax" data-value="' . $keywords_data . '" class="not_load flex_movies_block"></div>';
                                    }
                                }
                                ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <footer class="entry-footer">
                    <?php // TODO Edit post link           ?>
                </footer><!-- .entry-footer -->
            </main><!-- .site-main --> 
        </div><!-- .content-area -->
        <div id="secondary" class="sidebar-left">
            <div class="sidebar-inner">
                <div class="mob-header"><span class="close"></span></div>
                <div id="search-facets">                    
                    <h2 class="title">Filters</h2>
                    <ul class="tab-wrapper sidebar-tabs">
                        <li class="nav-tab active"><a href="/search">Search</a></li>
                        <li class="nav-tab"><a href="/analytics">Analytics</a></li>                        
                    </ul>
                    <div id="facets">
                        <?php
                        if ($show_facets) {
                            $search_front->show_facets($facets, $tab_key);
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>