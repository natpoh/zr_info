<h2><a href="<?php print $url ?>"><?php print __('Critic matic') ?></a>. <?php print __('View movie') ?></h2>


<?php if ($movie) { ?>    
    <h3><?php print __('Movie') ?>: [<?php print $mid ?>] <?php print $movie->title ?></h3>
    <p> Server: 
        <a href="https://info.filmdemographics.com/wp-admin/admin.php?page=critic_matic_movies&mid=<?php print $mid ?>">Info</a> | 
        <a href="https://zgreviews.com/wp-admin/admin.php?page=critic_matic_movies&mid=<?php print $mid ?>">Zr</a>
    </p>
    <?php
} else {
    ?>
    <h3><?php print __('Movie not found') ?>: [<?php print $mid ?>]</h3>
    <?php
    return;
}

print $tabs;

if ($mid) {

    $cfront = $this->get_cfront();
    $img = $cfront->get_thumb_path_full(220, 330, $mid);
    if ($img) {
        print '<img src="' . $img . '" /><br />';
    }
    $ma = $this->cm->get_ma();
    $kw_data = $ma->get_movie_keywords($mid);
    ?>
    <h3><?php print __('Meta') ?></h3>
    <table class="wp-list-table widefat striped table-view-list">
        <thead>
            <tr>
                <th><?php print __('Name') ?></th>                
                <th><?php print __('Value') ?></th>    
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><?php print __('Post link') ?></td>
                <td><?php
                    $link = $ma->get_post_link($movie);
                    print '<a href="' . $link . '">' . $link . '</a>';
                    ?></td>
            </tr>
            <?php foreach ($movie as $key => $value) { ?>
                <tr>
                    <td><?php print $key ?></td>
                    <td><?php print $value ?></td>
                </tr>
            <?php } ?>
            <tr>
                <td><?php print __('Add date') ?></td>
                <td><?php print $this->cm->curr_date($movie->add_time); ?></td>
            </tr>
            <tr>
                <td><?php print __('Last update') ?></td>
                <td><?php print date('d.m.Y H:i:s', $ma->get_movie_last_update($mid)); ?></td>
            </tr>
             <tr>
                <td><?php print __('Keywords') ?></td>
                <td><?php 
                if ($kw_data){
                    foreach ($kw_data as $kw_item) {
                        print "[{$kw_item->id}] {$kw_item->name}; ";
                    }
                }
                ?></td>
            </tr>
        </tbody>        
    </table>
    <h3>Genre</h3>
    <?php
    $ma = $this->cm->get_ma();
    $genre = $ma->get_movie_genres($mid);
    if (sizeof($genre)) {
        ?>
        <form accept-charset="UTF-8" method="post" >
            <div class="bulk-actions-holder">
                <select name="bulkaction" class="bulk-actions">
                    <option value=""><?php print __('Bulk actions') ?></option>
                    <?php foreach ($this->bulk_actions_genre as $act_key => $act_name) { ?>                    
                        <option value="<?php print $act_key ?>">
                            <?php print $act_name ?>
                        </option>                                
                    <?php } ?>                       
                </select>
                <input type="submit" id="edit-submit" value="<?php echo __('Submit') ?>" class="button-primary">  
            </div>
            <table id="movies" class="wp-list-table widefat striped table-view-list">
                <thead>
                    <tr>
                        <td class="manage-column column-cb check-column" ><input type="checkbox" id="cb-select-all-1"></td>
                        <th><?php print __('id') ?></th>
                        <th><?php print __('Name') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($genre as $item) {
                        ?>
                        <tr>
                            <th  class="check-column" ><input type="checkbox" name="bulk-<?php print $item->id ?>"></th>    
                            <td><?php print $item->name ?></td>          
                            <td><?php print $item->id ?></td>                                              
                        </tr> 
                    <?php } ?>
                </tbody>
            </table> 
        </form>   

        <?php
    }
    // Add genre
    $all_genres = $ma->get_all_genres();
    ?>
    <br />
    <form accept-charset="UTF-8" method="post" >
        <fieldset>
            <input type="hidden" name="edit_genre" value="1">
            <input type="hidden" name="mid" class="mid" value="<?php print $mid ?>">
            <label class="inline-edit-author">
                <span class="title"><?php print __('Add a genre') ?></span>
                <select name="genre" class="authors">
                    <?php
                    if ($all_genres) {
                        foreach ($all_genres as $genre) {
                            ?>
                            <option value="<?php print $genre->id ?>"><?php print stripslashes($genre->name) ?></option>                                
                            <?php
                        }
                    }
                    ?>                       
                </select>
            </label>
            <?php wp_nonce_field('critic-options', 'critic-nonce'); ?>
            <input type="submit" name="options" id="edit-submit" value="<?php echo __('Save') ?>" class="button-primary">  
        </fieldset>
    </form>

    <h3>ERating</h3>
    <?php
    $erating = $ma->get_movie_erating($mid);
    if ($erating) {
        $earr = (array) $erating;
        ksort($earr);
        ?>
        <form accept-charset="UTF-8" method="post" >
            <fieldset>
                <input type="hidden" name="edit_erating" value="1">
                <input type="hidden" name="mid" class="mid" value="<?php print $mid ?>">
                <input type="hidden" name="id" class="id" value="<?php print $earr['id'] ?>">
                <table id="movies" class="wp-list-table widefat striped table-view-list">
                    <thead>
                        <tr>
                            <th><?php print __('key') ?></th>
                            <th><?php print __('value') ?></th>                        
                            <th>Info</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach ($earr as $key => $value) {
                            if ($key == "id") {
                                continue;
                            }
                            ?>
                            <tr>                        
                                <td><?php print $key ?></td>
                                <td><input name="<?php print $key ?>" type="text" value="<?php print $value ?>"></td> 
                                <td> <?php
                                    if (strstr($key, 'date')) {
                                        print $value ? $ma->curr_date($value) : 0 ;
                                    }
                                    ?></td>
                            </tr> 
                        <?php } ?>
                    </tbody>
                </table> 
                <?php wp_nonce_field('critic-options', 'critic-nonce'); ?>
                <br />
                <input type="submit" name="options" id="edit-submit" value="<?php echo __('Update movie erating') ?>" class="button-primary">  
            </fieldset>
        </form>  
        <br />
        <?php
    }
    if (class_exists('MoviesLinks') && class_exists('MoviesLinksAdmin')) {
        $ml = new MoviesLinks();
        $mla = new MoviesLinksAdmin();
        $mpa = $mla->get_mpa();
        ?>
        <h1>Movies links</h1>
        <?php
        // Get links urls
        $mp = $ml->get_mp();
        $q_req = array('pid' => $mid);
        $urls = $mp->get_urls_query($q_req, 1, 0);

        // Get parsing posts
        $urls2 = array();
        $parsed_posts = $mp->get_posts_by_top_movie($mid);
        if ($parsed_posts) {
            $ids = array();
            foreach ($parsed_posts as $post) {
                $ids[] = $post->uid;
            }
            $q2_req = array(
                'ids' => $ids,
                'pid' => 0,
            );
            $urls2 = $mp->get_urls_query($q2_req, 1, 0);

            // Exclude camp
            $pcamp = $mp->get_campaigns(-1, 1);
            $pcamp_exlude = array();
            if ($pcamp) {
                foreach ($pcamp as $pcampitem) {
                    $pcamp_exlude[] = $pcampitem->id;
                }
            }

            if ($urls2) {
                $valid_urls_2 = array();
                foreach ($urls2 as $item) {
                    if (!in_array($item->cid, $pcamp_exlude)) {
                        $valid_urls_2[] = $item;
                    }
                }
            }
        }

        $total_urls = array(
            'Post id' => $urls,
            'Top movie' => $valid_urls_2,
        );

        foreach ($total_urls as $tkey => $urls) {

            if ($urls) {
                ?>
                <h3><?php print $tkey ?></h3>
                <form accept-charset="UTF-8" method="post" >
                    <input type="hidden" name="ml_posts" value="1">
                    <div class="bulk-actions-holder">
                        <select name="bulkaction" class="bulk-actions">
                            <option value=""><?php print __('Bulk actions') ?></option>
                            <?php foreach ($this->bulk_actions_ml as $act_key => $act_name) { ?>                    
                                <option value="<?php print $act_key ?>">
                                    <?php print $act_name ?>
                                </option>                                
                            <?php } ?>                       
                        </select>
                        <input type="submit" id="edit-submit" value="<?php echo __('Submit') ?>" class="button-primary">  
                    </div>
                    <table id="movies" class="wp-list-table widefat striped table-view-list">
                        <thead>
                            <tr>
                                <td class="manage-column column-cb check-column" ><input type="checkbox" id="cb-select-all-1"></td>
                                <th><?php print __('id') ?></th>
                                <th><?php print __('Date / Update') ?></th>
                                <th><?php print __('link') ?></th>
                                <th><?php print __('Movie ID') ?></th>
                                <th><?php print __('Status') ?></th>
                                <th><?php print __('Arhive') ?></th>
                                <th><?php print __('Post') ?></th>
                                <th><?php print __('Link') ?></th>
                                <th><?php print __('Camapaign') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            foreach ($urls as $item) {

                                $campaign = $mp->get_campaign($item->cid, true);
                                $camp_title = $item->cid;
                                if ($campaign) {
                                    $camp_title = $campaign->title;
                                }
                                $camp_title = $mla->theme_parser_campaign($item->cid, $camp_title);

                                $ml_url = '/wp-admin/admin.php?page=moveis_links_parser';
                                ?>
                                <tr>
                                    <th  class="check-column" ><input type="checkbox" name="bulk-<?php print $item->id ?>"></th>    
                                    <td>
                                        <a href="<?php print $ml_url . '&uid=' . $item->id ?>"><?php print $item->id ?></a>
                                    </td>
                                    <td>
                                        <?php print $item->date ? $mp->curr_date($item->date) : 0  ?><br />
                                        <?php print $item->last_upd ? $mp->curr_date($item->last_upd) : 0  ?>
                                    </td> 
                                    <td class="wrap">                            
                                        <a href="<?php print $item->link ?>" target="_blank" title="<?php print $item->link ?>"><?php print $item->link ?></a>                                               
                                    </td> 
                                    <td><a href="/wp-admin/admin.php?page=critic_matic_movies&mid=<?php print $item->pid ?>"><?php print $item->pid ?></a></td>
                                    <td><?php print $mpa->get_url_status($item->status) ?></td>
                                    <td>
                                        <?php
                                        if ($item->adate) {
                                            print $mp->curr_date($item->adate);
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        if ($item->ptitle) {
                                            $title = $item->postid . '<br /><b>' . $item->ptitle . '</b>';
                                            if ($item->pyear) {
                                                $title = $title . ' [' . $item->pyear . ']';
                                            }
                                            print $title . '<br />';
                                        }
                                        if ($item->pdate) {
                                            print 'Date: ' . $mp->curr_date($item->pdate) . '<br />';
                                        }
                                        if (isset($item->pstatus)) {
                                            print 'Status: ' . $mpa->post_parse_status[$item->pstatus];
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        if ($item->ptop_movie) {
                                            /* $m = $ma->get_movie_by_id($item->ptop_movie);
                                              $title = '<b>' . $m->title . '</b>';
                                              print $title . '  ['.$m->year.']<br />'; */
                                            print $item->ptop_movie . '<br />';
                                        }
                                        if ($item->prating) {
                                            print 'Rating: ' . $item->prating . '<br />';
                                        }
                                        if (isset($item->pstatus_links)) {
                                            print 'Status: ' . $mpa->post_link_status[$item->pstatus_links];
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php print $camp_title ?>
                                    </td>
                                </tr> 
                            <?php } ?>
                        </tbody>
                    </table> 
                </form>   

                <?php
            }
        }
    }
    ?>
    <br />

    <h1>Reviews</h1>
    <?php
    $critic_meta = $this->cm->get_critics_meta_by_movie($mid);
    $meta_exist = array();
    if (sizeof($critic_meta)) {
        ?>
        <form accept-charset="UTF-8" method="post" >
            <div class="bulk-actions-holder">
                <select name="bulkaction" class="bulk-actions">
                    <option value=""><?php print __('Bulk actions') ?></option>
                    <?php foreach ($this->bulk_actions_meta as $act_key => $act_name) { ?>                    
                        <option value="<?php print $act_key ?>">
                            <?php print $act_name ?>
                        </option>                                
                    <?php } ?>                       
                </select>
                <input type="submit" id="edit-submit" value="<?php echo __('Submit') ?>" class="button-primary">  
            </div>
            <h3><?php print __('Critics meta') ?></h3>
            <table id="movies" class="wp-list-table widefat striped table-view-list">
                <thead>
                    <tr>
                        <td class="manage-column column-cb check-column" ><input type="checkbox" id="cb-select-all-1"></td>
                        <th><?php print __('Meta id') ?></th>
                        <th><?php print __('Critic id') ?></th>
                        <th><?php print __('Critic title') ?></th>
                        <th><?php print __('Critic status') ?></th>
                        <th><?php print __('Type') ?></th>                 
                        <th><?php print __('State') ?></th>                    
                        <th><?php print __('Rating') ?></th> 
                        <th><?php print __('Author') ?></th>
                        <th><?php print __('Author type') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($critic_meta as $item) {
                        $state = $this->cm->get_movie_state_name($item->state);
                        $meta_exist[$item->cid] = $state;
                        $critic = $this->cm->get_post_and_author($item->cid);
                        $critic_link = $this->theme_post_link($item->cid, $critic->title);
                        ?>
                        <tr>
                            <th  class="check-column" ><input type="checkbox" name="bulk-<?php print $item->id ?>"></th>    
                            <td><?php print $item->id ?></td>
                            <td><?php print $item->cid ?></td>
                            <td><?php print $critic_link ?></td>
                            <td><?php print $this->cm->get_post_status($critic->status) ?></td>
                            <td><?php print $this->cm->get_post_category_name($item->type) ?></td>
                            <td><?php print $state ?></td>                          
                            <td><?php print $item->rating ?></td>
                            <td><?php print $this->theme_author_link($critic->aid, $critic->author_name) ?></td>
                            <td><?php print $this->cm->get_author_type($critic->author_type) ?></td>
                        </tr> 
                    <?php } ?>
                </tbody>
            </table> 
        </form>   

    <?php } ?>
    <?php
    // Meta log
    $log = $this->cs->get_log(1, $mid, 0, 100);
    if (sizeof($log)) {
        ?>
        <h3><?php print __('Log meta') ?></h3>
        <table id="feeds" class="wp-list-table widefat striped table-view-list">
            <thead>
            <th class="mob-hide"><?php print __('id') ?></th>
            <th><?php print __('Date') ?></th> 
            <th><?php print __('Type') ?></th>
            <th><?php print __('Status') ?></th>
            <th><?php print __('Message') ?></th> 
            <th><?php print __('Critic') ?></th>
            <th><?php print __('Movie') ?></th>
        </thead>
        <tbody>
            <?php
            $log = array_reverse($log);
            foreach ($log as $item) {
                ?>
                <tr> 
                    <td><?php print $item->id ?></td>
                    <td><?php print $this->cs->curr_date($item->date) ?></td>
                    <td><?php print $this->cs->get_log_type($item->type) ?></td>
                    <td><?php print $this->cs->get_log_status($item->status) ?></td>
                    <td><?php print $item->message ?></td>
                    <td><?php print $this->theme_post_link($item->cid, $this->cm->get_post_name_by_id($item->cid)) ?></td> 
                    <td><?php print $this->theme_movie_link($item->mid, $this->get_movie_name_by_id($item->mid)) ?></td>  
                </tr> 
            <?php } ?>
        </tbody>
        </table>    
    <?php } ?>
    <br />
    <h1><?php print __('Critics search') ?></h1>
    <h2><?php print __('Search fields') ?></h2>
    <?php if (isset($critics_search['debug'])) { ?>
        <table class="wp-list-table widefat striped table-view-list">
            <thead>
                <tr>
                    <th><?php print __('Name') ?></th>                
                    <th><?php print __('Value') ?></th>    
                </tr>
            </thead>
            <tbody>
                <?php foreach ($critics_search['debug'] as $key => $value) { ?>
                    <tr>
                        <td><?php print ucfirst($key) ?></td>
                        <td><?php print $value ?></td>
                    </tr>
                <?php } ?>
            </tbody>        
        </table><?php
    }
    ?>
    <form accept-charset="UTF-8" method="post" >
        <div class="bulk-actions-holder">
            <select name="bulkaction" class="bulk-actions">
                <option value=""><?php print __('Bulk actions') ?></option>
                <?php foreach ($this->bulk_actions_search as $act_key => $act_name) { ?>                    
                    <option value="<?php print $act_key ?>">
                        <?php print $act_name ?>
                    </option>                                
                <?php } ?>                       
            </select>
            <input type="submit" id="edit-submit" value="<?php echo __('Submit') ?>" class="button-primary">  
        </div>
        <?php
        $critic_search_types = array('valid', 'other');
        foreach ($critic_search_types as $search_type) {
            if ($critics_search[$search_type] && sizeof($critics_search[$search_type])) {
                ?>
                <h3><?php print __('Search') . ' ' . $search_type ?></h3>
                <table id="movies" class="wp-list-table widefat striped table-view-list">
                    <thead>
                        <tr>
                            <td class="manage-column column-cb check-column" ><input type="checkbox" id="cb-select-all-1"></td>
                            <th><?php print __('Critic id') ?></th>
                            <th><?php print __('Critic title') ?></th> 
                            <th><?php print __('Critic date') ?></th> 
                            <th><?php print __('Type') ?></th> 
                            <th><?php print __('In meta') ?></th>
                            <th><?php print __('Rating') ?></th> 
                            <th><?php print __('Score') ?></th>    
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach ($critics_search[$search_type] as $cid => $critic) {

                            $post = $this->cm->get_post_and_author($cid);
                            if ($post) {
                                // Post data                        
                                $title = $post->title;
                                $name = $post->name;
                                $link = $post->link;
                                // Date
                                $critic_date = gmdate('Y-m-d H:i:s', $post->date);
                            }
                            $critic_link = $this->theme_post_link($cid, $title);
                            $type = $this->cm->get_post_category_name($critic['type']);



                            // Rating
                            $rating = $critic['total'];
                            if ($critic['valid']) {
                                $rating = '<b class="green">' . $rating . '</b>';
                            } else {
                                $rating = '<b class="red">' . $rating . '</b>';
                            }
                            // Score
                            $score_str = '';
                            if ($critic['score']) {
                                foreach ($critic['score'] as $key => $value) {
                                    $score_str .= "$key => $value<br />";
                                }
                            }
                            //In meta
                            $in_meta = isset($meta_exist[$cid]) ? $meta_exist[$cid] : '<span class="red">Not</span>';
                            ?>
                            <tr>
                                <th  class="check-column" ><input type="checkbox" name="bulk-<?php print $cid ?>"></th>    
                                <td><?php print $cid ?></td>
                                <td><?php print $critic_link ?></td>                                                
                                <td><?php print $critic_date ?></td>
                                <td><?php print $type ?></td>
                                <td><?php print $in_meta ?></td>
                                <td><?php print $rating ?></td>
                                <td><?php print $score_str ?></td>                    
                            </tr> 
                            <?php if (isset($critic['debug'])) { ?>
                                <tr>
                                    <td></td>
                                    <td colspan="7">
                                        <table class="wp-list-table widefat striped table-view-list">     
                                            <tbody>
                                                <?php foreach ($critic['debug'] as $key => $value) { 
                                                    if (!$value){
                                                        continue;
                                                    }
                                                    ?>
                                                    <tr>                                                        
                                                        <td><?php print $key ?></td>
                                                        <td><?php
                                                            if (is_array($value)) {
                                                                print_r(implode('; ', $value));
                                                            } else {
                                                                print_r($value);
                                                            }
                                                            ?>
                                                        </td>
                                                    </tr>
                                                <?php } ?>
                                            </tbody>        
                                        </table>
                                    </td>                        
                                </tr> 
                                <?php
                            }
                        }
                        ?>
                    </tbody>
                </table>   
                <?php
            }
        }
        ?>
    </form>


<?php } ?>