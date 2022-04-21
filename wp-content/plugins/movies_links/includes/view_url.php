<h2><a href="<?php print $url ?>"><?php print __('Movies Links URL') ?></a>. <?php print __('View') ?></h2>

<h3><?php print __('URL') ?>: [<?php print $uid ?>] <?php print $url_data->link ?></h3>
<?php
if ($uid) {
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
                <td><?php print __('Campaign') ?></td>
                <td><?php
                    $campaign = $this->mp->get_campaign($url_data->cid);
                    if ($campaign) {
                        print $campaign->title;
                    }
                    ?>
                </td>
            </tr>
            <tr>
                <td><?php print __('Link') ?></td>
                <td><a target="_blank" href="<?php print $url_data->link ?>"><?php print $url_data->link ?></a></td>
            </tr>
            <tr>
                <td><?php print __('Link hash') ?></td>
                <td><?php print $url_data->link_hash ?></td>
            </tr>           
            <tr>
                <td><?php print __('Last log') ?></td>
                <td><?php print $this->get_last_log($uid) ?></td>
            </tr>
        </tbody>        
    </table>
    <?php
    $arhive = $this->mp->get_arhive_by_url_id($uid);

    if ($arhive) {
        $content = $this->mp->get_arhive_file($url_data->cid, $arhive->arhive_hash);
        ?>
        <h2>Arhive</h2>
        <p>Date: <?php print $this->mp->curr_date($arhive->date) ?></p>
        <textarea style="width: 90%; height: 300px;"><?php print htmlspecialchars($content) ?></textarea>      
        <?php
    }

    $post = $this->mp->get_post_by_uid($uid);
    if ($post) {
        ?>
        <h2>Post</h2>  
        <p>Post fields</p>
        <table class="wp-list-table widefat striped table-view-list">
            <thead>
                <tr>
                    <th><?php print __('Name') ?></th>                
                    <th><?php print __('Value') ?></th>    
                </tr>
            </thead>

            <tbody>  
                <tr>
                    <td><?php print __('Title') ?></td>
                    <td><?php print $post->title ?></td>
                </tr>
                <tr>
                    <td><?php print __('Year') ?></td>
                    <td><?php print $post->year ?></td>
                </tr>
                <tr>
                    <td><?php print __('Release') ?></td>
                    <td><?php print $post->rel ?></td>
                </tr>
                <?php
                $po = $this->mp->get_post_options($post);

                if ($po) {
                    ?>
                    <?php foreach ($po as $key => $value) { ?>
                        <tr>
                            <td><?php print $key ?></td>
                            <td><?php print $value ?></td>
                        </tr>
                    <?php } ?>
                    <?php
                }
                ?>     
            </tbody>        
        </table>
        <p>Meta fields</p>
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
                    <td><?php print $this->mp->curr_date($post->date) ?></td>
                </tr>
                <tr>
                    <td><?php print __('Last update') ?></td>
                    <td><?php print $this->mp->curr_date($post->last_upd) ?></td>
                </tr>
                <tr>
                    <td><?php print __('Status') ?></td>
                    <td><?php print $this->post_parse_status[$post->status] ?></td>
                </tr>             
            </tbody>        
        </table>

        <h2>Post links</h2>  
        <table class="wp-list-table widefat striped table-view-list">
            <thead>
                <tr>
                    <th><?php print __('Name') ?></th>                
                    <th><?php print __('Value') ?></th>    
                </tr>
            </thead>
            <tbody> 
                <tr>
                    <td><?php print __('Status links') ?></td>
                    <td><?php print $this->post_link_status[$post->status_links] ?></td>
                </tr>               
                <tr>
                    <td><?php
                        $top_title = 'Top movie';
                        if ($campaign->type == 1) {
                            $top_title = 'Top actor';
                        }

                        print $top_title
                        ?></td>
                    <td><?php
                        if ($post->top_movie) {
                            if ($campaign->type == 1) {
                                print $post->top_movie;
                            } else {
                                $ma = $this->ml->get_ma();
                                $m = $ma->get_movie_by_id($post->top_movie);
                                $title = '<b>' . $m->title . '</b>';
                                print 'Id:' . $post->top_movie . '; Title: ' . $title . '  [' . $m->year . ']<br />';
                            }
                        }
                        ?></td>
                </tr>
                <?php if ($campaign->type == 1) { ?>
                    <tr>
                        <td><?php print __('Actors meta') ?></td>
                        <td><?php
                            $meta = $this->mp->get_post_actor_meta(0, $post->id, $campaign->id);
                            if ($meta){
                                $actors=array();
                                foreach ($meta as $item) {
                                    $actors[]=$item->aid;
                                }
                                print implode(',', $actors);
                            }
                            ?>
                        </td>
                    </tr>
                <?php } ?>
                <tr>
                    <td><?php print __('Rating') ?></td>
                    <td><?php print $post->rating ?></td>
                </tr>
            </tbody>        
        </table>
        <?php
        $options = $this->mp->get_options($campaign);
        $o = $options['links'];
        $preivew_data = $this->mp->find_posts_links(array($post), $o, $campaign->type == 1);
        $this->preview_links_search($preivew_data);
        ?>

    <?php } ?>

    <?php
    $log = $this->mp->get_log(1, 0, $uid, -1, -1, 300);
    if ($log) {
        ?>
        <h2>Log</h2>  
        <table id="parsers" class="wp-list-table widefat striped table-view-list">
            <thead>
            <th class="mob-hide"><?php print __('id') ?></th>
            <th><?php print __('Date') ?></th> 
            <th><?php print __('Type') ?></th>
            <th><?php print __('Status') ?></th>
            <th><?php print __('Message') ?></th> 
            <th><?php print __('Campaign') ?></th>
            <th><?php print __('URL') ?></th>
        </thead>
        <tbody>
            <?php
            $log = array_reverse($log);
            foreach ($log as $item) {
                $campaign = $this->mp->get_campaign($item->cid, true);
                $camp_title = $item->cid;
                if ($campaign) {
                    $camp_title = $campaign->title;
                }
                $camp_title = $this->mla->theme_parser_campaign($item->cid, $camp_title);
                ?>
                <tr> 
                    <td><?php print $item->id ?></td>
                    <td><?php print $this->mp->curr_date($item->date) ?></td>
                    <td><?php print $this->get_log_type($item->type) ?></td>
                    <td><?php print $this->get_log_status($item->status) ?></td>
                    <td><?php print $item->message ?></td>
                    <td><?php print $camp_title ?></td> 
                    <td><?php print $this->mla->theme_parser_url_link($item->uid, $item->uid) ?></td>
                </tr> 
            <?php } ?>
        </tbody>
        </table>    
    <?php }
    ?>

<?php } ?>