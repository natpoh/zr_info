<h2><a href="<?php print $url ?>"><?php print __('Critic matic') ?></a>. <?php print __('Authors') ?></h2>

<?php print $tabs; ?>
<?php print $filters_author_type; ?>
<?php print $filters; ?>

<?php
if (sizeof($authors) > 0) {
    ?>
    <?php print $pager ?>
    <table id="authors" class="wp-list-table widefat striped table-view-list">
        <thead>
            <tr>
                <th ><?php print __('Img') ?></th> 
                <?php $this->sorted_head('id', 'id', $orderby, $order, $page_url) ?>                                
                <?php $this->sorted_head('wp_uid', 'WP Uid', $orderby, $order, $page_url) ?> 
                <?php $this->sorted_head('name', 'Author', $orderby, $order, $page_url) ?> 
                <?php $this->sorted_head('type', 'From', $orderby, $order, $page_url) ?>
                <?php $this->sorted_head('status', 'Status', $orderby, $order, $page_url) ?>
                <th><?php print __('Autoblur') ?></th> 
                <th><?php print __('Tags') ?></th>
                <th><?php print __('Posts') ?></th> 
                <th><?php print __('Feeds') ?></th> 
                <th><?php print __('Actions') ?></th> 
            </tr>
        </thead>
        <tbody>
            <?php
            $cav = $this->cm->get_cav();

            foreach ($authors as $author) {
                $author_name = $author->name;
                $author_type = $this->cm->get_author_type($author->type);
                $author_status = $this->cm->get_author_status($author->status);

                //Tags
                $tags = $this->cm->get_author_tags($author->id);
                $tag_arr = $this->theme_author_tags($tags);

                //Author posts
                $post_count = $this->cm->get_author_post_count($author->id);

                //Campaigns
                $campaigns = $this->cf->get_feeds_count(-1, $author->id);

                //Options
                $options = unserialize($author->options);

                //Author links
                $author_actions = $this->cm->authors_actions(array('home'));
                $author_url = $url . '&aid=' . $author->id;
                $action_links = $this->get_filters($author_actions, $author_url, $curr_tab = 'none', $front_slug = 'home', $name = 'tab', '', false);


                //image            
                $image = '';
                if ($options['image']) {
                    $image = '<img src="' . $options['image'] . '" width="50"  height="50">';
                }

                $wp_uid = $author->wp_uid;
                
          
                if (!$image && $author->type==2) {
                    if ($wp_uid) {
                        // User            
                        $image = $cav->get_or_create_user_avatar($wp_uid, 0, 64);
                    } else {
                        $image = $cav->get_or_create_user_avatar(0, $author->id, 64);
                    }
                }

                /*
                  //Critic posts (TEST ONLY. UNUSED)
                  global $wpdb;
                  $post_id = get_post();
                  $posttitle = $author_name;
                  $pid_sql = sprintf("SELECT p.ID FROM $wpdb->posts p WHERE p.post_title = '%s' and p.post_type = 'wprss_feed'", $posttitle);
                  $pid = $wpdb->get_var($pid_sql);

                  $critic_count = 0;
                  if ($pid){
                  $sql = sprintf("SELECT COUNT(id) FROM $wpdb->postmeta m "
                  . "WHERE m.meta_key = 'wprss_feed_id' AND m.meta_value=%d", $pid);
                  $critic_count = $wpdb->get_var($sql);
                  }
                  //$sql = sprintf("SELECT COUNT(id) FROM $wpdb->posts p INNER JOIN $wpdb->postmeta m "
                  //        . "WHERE p.post_title = '%s' AND m.meta_key = 'wprss_feed_id' AND m.meta_value=p.ID", $posttitle);

                 */
                ?>
                <tr>
                    <td ><?php print $image ?></td>  
                    <td><?php print $author->id ?></td>     
                    <td><?php print $wp_uid ?></td>
                    <td><a href="<?php print $author_url ?>"><?php print $author_name ?></a>
                        <?php
                        if ($author->type == 2 && isset($options['audience'])) {
                            print '<br />Key: ' . $options['audience'];
                        }
                        ?>
                    </td>
                    <td><?php print $author_type ?></td>
                    <td><?php print $author_status ?></td>
                    <td><?php print isset($options['autoblur']) && $options['autoblur'] == 1 ? 'True' : 'False'  ?></td>
                    <td><?php print implode(', ', $tag_arr) ?></td>  
                    <td><?php print $post_count ?></td>   
                    <td><?php print $campaigns ?></td> 
                    <td><?php print $action_links; ?>
                    </td>
                </tr> 
            <?php } ?>
        </tbody>
    </table>    
    <?php print $pager ?>
    <?php
} else {
    ?>
    <p><?php print __('The authors not found') ?></p>
    <?php
}
?>