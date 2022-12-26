<h2><a href="<?php print $url ?>"><?php print __('Critic matic') ?></a>. <?php print __('View post') ?></h2>


<?php if ($post) { ?>
    <h3><?php print __('Post') ?>: [<?php print $pid ?>] <?php print $post->title ?></h3>
    <?php
} else {
    ?>
    <h3><?php print __('Post not found') ?>: [<?php print $pid ?>]</h3>
    <?php
    return;
}

print $tabs;

if ($pid) {
    /*
      `id` int(11) unsigned NOT NULL auto_increment,
      `date` int(11) NOT NULL DEFAULT '0',
      `date_add` int(11) NOT NULL DEFAULT '0',
      `status` int(11) NOT NULL DEFAULT '1',
      `type` int(11) NOT NULL DEFAULT '0',
      `link_hash` varchar(255) NOT NULL default '',
      `link` text default NULL,
      `title` text default NULL,
      `content` text default NULL,
     */

    //Author
    $autor_type = 0;
    if ($post->aid) {
        $author = $this->cm->get_author($post->aid);
        $autor_type = $author->type;

        $author_name = $author->name;

        //Author url
        $author_url = $this->admin_page . $this->authors_url . '&aid=' . $post->aid;
    }
    ?>
    <br />
    <table class="wp-list-table widefat striped table-view-list">
        <thead>
            <tr>
                <th><?php print __('Name') ?></th>                
                <th><?php print __('Value') ?></th>    
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><?php print __('Post date') ?></td>
                <td><?php print $this->cm->curr_date($post->date) ?></td>
            </tr>
            <tr>
                <td><?php print __('Add date') ?></td>
                <td><?php print $this->cm->curr_date($post->date_add) ?></td>
            </tr>
            <tr>
                <td><?php print __('Status') ?></td>
                <td><?php print $this->cm->get_post_status($post->status) ?></td>
            </tr>
            <tr>
                <td><?php print __('Blur') ?></td>
                <td><?php print $post->blur ? 'Blur the content' : 'No'  ?></td>
            </tr>
            <?php
            // For all, except Audience
            if ($autor_type != 2):
                ?>
                <tr>
                    <td><?php print __('Type') ?></td>
                    <td><?php print $this->cm->get_post_type($post->type) ?></td>
                </tr> 
                <tr>
                    <td><?php print __('Link') ?></td>
                    <td><?php print $post->link ?></td>
                </tr>                
                <tr>
                    <td><?php print __('Link hash') ?></td>
                    <td><?php print $post->link_hash ?></td>
                </tr>
                <tr>
                    <td><?php print __('ZR URL') ?></td>
                    <td><a href="/critics/<?php print $pid ?>">/critics/<?php print $pid ?></td>
                </tr>  
                <?php
                $verdict = $this->cm->get_critic_verdict($pid);

                if ($verdict) {
                    ?>
                    <tr>
                        <td><?php print __('Verdict') ?></td>
                        <td>Percent: <?php print $verdict->percent ?>%. Rating: <?php print $verdict->result ?></td>
                    </tr>  
                <?php }
                ?>
    <?php endif; ?>
            <tr>
                <td><?php print __('Author') ?></td>
                <td>
                    <?php if ($post->aid) { ?>
                        <a href="<?php print $author_url ?>"><?php print $author_name ?></a>
    <?php } ?>

                </td>
            </tr>  
            <tr>
                <td><?php print __('Top movie') ?></td>
                <td><?php
                    if ($post->top_movie) {
                        $name = $post->top_movie;
                        //$name = $this->cm->get_top_movie($post->id);

                        print $this->theme_movie_link($name, $this->get_movie_name_by_id($name));
                    } else {
                        print __('None');
                    }
                    ?></td>
            </tr>
            <tr>
                <td><?php print __('In index') ?></td>
                <td><?php print $this->cs->critic_in_index($pid) ? 'Index' : 'Not'; ?></td>
            </tr>

            <?php
            if ($autor_type == 0 || $autor_type == 2) {
                $rating = $this->cm->get_post_rating($post->id);

                if (isset($rating['em'])) {
                    ?>
                    <tr>
                        <td><?php print __('Email') ?></td>
                        <td><?php print $rating['em'] ?></td>
                    </tr>
                    <?php
                }
                if (isset($rating['ip'])) {
                    ?>
                    <tr>
                        <td><?php print __('IP') ?></td>
                        <td><?php print $rating['ip'] ?></td>
                    </tr>
                    <?php
                }
                ?>
                <tr>
                    <td><?php print __('Rating') ?></td>
                    <td><?php
                        if ($rating) {
                            if (!$cfront) {
                                $cfront = new CriticFront();
                            }
                            print $cfront->theme_rating($rating);
                        }
                        ?></td>
                </tr>
                <?php
            }
            ?>

        </tbody>       
    </table>


    <?php
    $critic_meta = $this->cm->get_movies_data($post->id);
    if (sizeof($critic_meta)) {
        ?>
        <h2><?php print __('Movies meta') ?></h2>
        <?php
        $state_items = array();
        foreach ($critic_meta as $item) {
            $state_items[$item->state][] = $item;
        }

        $states_oder = array(1, 2, 0);

        foreach ($states_oder as $order) {
            if (isset($state_items[$order])) {
                ?>
                <h3><?php print $this->cm->get_movie_state_name($order) ?> <?php print __('meta') ?></h3>  

                <table id="movies" class="wp-list-table widefat striped table-view-list">
                    <thead>
                        <tr>
                            <th><?php print __('Movie id') ?></th>
                            <th><?php print __('Movie name') ?></th>
                            <th><?php print __('Meta type') ?></th>                 
                            <th><?php print __('State') ?></th>                    
                            <th><?php print __('Rating') ?></th> 
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach ($state_items[$order] as $item) {
                            $movie = $this->theme_movie_link($item->fid, $this->get_movie_name_by_id($item->fid));
                            ?>
                            <tr>
                                <td><?php print $item->fid ?></td>
                                <td><?php print $movie ?></td>
                                <td><?php print $this->cm->get_post_category_name($item->type) ?></td>
                                <td><?php print $this->cm->get_movie_state_name($item->state) ?></td>                          
                                <td><?php print $item->rating ?></td>
                            </tr> 
                <?php } ?>
                    </tbody>
                </table>    

                <?php
            }
        }
    }
    ?>


    <?php
    // Meta log
    $log = $this->cs->get_log(1, 0, $post->id, 100);
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

    <?php
    if ($autor_type != 2) {
        $movies_search_arr = $this->cs->search_movies($post->title, $post->content);
        $movies_search = $movies_search_arr['movies'];
        $keyword = $movies_search_arr['keywords'];
        if (sizeof($movies_search)) {
            foreach ($movies_search as $type => $movie_type) {
                ?>
                <h3><?php print 'Search [' . $type . ']: ' . $keyword[$type] ?></h3>
                <table id="movies" class="wp-list-table widefat striped table-view-list">
                    <thead>
                        <tr>
                            <th><?php print __('Movie id') ?></th>
                            <th><?php print __('Movie name') ?></th>
                            <th><?php print __('Movie type') ?></th>
                            <th><?php print __('Release') ?></th>                                     
                            <th><?php print __('Last update') ?></th> 
                            <th><?php print __('Search weight') ?></th> 
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $ma = $this->get_ma();
                        foreach ($movie_type as $item) {
                            $movie = $this->theme_movie_link($item->id, $item->title);
                            ?>
                            <tr>
                                <td><?php print $item->id ?></td>
                                <td><?php print $movie ?></td>
                                <td><?php print $item->type ?></td>
                                <td><?php print $item->release ?></td>                        
                                <td><?php
                                    $date = $ma->get_movie_last_update($item->id);
                                    if ($date) {
                                        print date('d.m.Y H:i:s', $date);
                                    }
                                    ?></td>                        
                                <td><?php print $item->w ?></td>                        
                            </tr> 
                <?php } ?>
                    </tbody>
                </table>    

                <?php
            }
        }
    }
    ?>

    <h2><?php print __('Title') . ': ' . $post->title ?></h2>
    <table class="wp-list-table widefat fixed striped table-view-list">
        <thead>
            <tr>
                <th><?php print __('Content html') ?></th>                
                <th><?php print __('Content view') ?></th>    
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><?php print htmlspecialchars(stripslashes($post->content)) ?></td>
                <td><?php print $post->content ?></td>
            </tr>            

        </tbody>        
    </table>

    <?php
    // Transcriptions
    if ($post->tstatus == 1 || $post->tstatus == 2) {
        ?>
        <h2><?php print __('Transcriptions') ?></h2>
        <textarea style="width: 90%; height: 300px;"><?php print $post->tcontent ?></textarea>    
        <?php
    }
    if ($autor_type == 1) {
        ?>
        <h2>Find dublicates</h2>
        <textarea style="width: 90%; height: 600px;">
            <?php
            // Find dublicates
            $povtors = $this->cs->find_post_povtor($post->title, $post->id, $post->aid, true);
            ?>
        </textarea>
        <?php
        if ($povtors) {
            print '<h3>Dublicates found:</h3>';
            p_r($povtors);
        }
        ?>
    <?php } ?>

<?php } ?>