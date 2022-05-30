<h2><a href="<?php print $url ?>"><?php print __('Critic feeds') ?></a>. <?php print __('Campaigns') ?></h2>
<?php print $tabs; ?>
<?php print $filters ?>

<?php
if (sizeof($feeds) > 0) {
    ?>
    <?php print $pager ?>
    <form accept-charset="UTF-8" method="post" >
        <div class="bulk-actions-holder">
            <select name="bulkaction" class="bulk-actions">
                <option value=""><?php print __('Bulk actions') ?></option>
                <?php foreach ($this->bulk_actions_feeds as $act_key => $act_name) { ?>                    
                    <option value="<?php print $act_key ?>">
                        <?php print $act_name ?>
                    </option>                                
                <?php } ?>                       
            </select>
            <input type="submit" id="edit-submit" value="<?php echo __('Submit') ?>" class="button-primary">  
        </div>

        <table id="feeds" class="wp-list-table widefat striped table-view-list">
            <thead>
            <td class="manage-column column-cb check-column" ><input type="checkbox" id="cb-select-all-1"></td>
            <?php $this->sorted_head('id', 'id', $orderby, $order, $page_url) ?>                                
            <?php $this->sorted_head('date', 'Date', $orderby, $order, $page_url) ?> 
            <?php $this->sorted_head('title', 'Title', $orderby, $order, $page_url) ?>
            <?php $this->sorted_head('author_name', 'Author', $orderby, $order, $page_url) ?>
            <?php $this->sorted_head('status', 'State', $orderby, $order, $page_url) ?>
            <?php $this->sorted_head('update_interval', 'Update interval', $orderby, $order, $page_url) ?>
            <?php $this->sorted_head('last_update', 'Last update', $orderby, $order, $page_url) ?>            
            <th><?php print __('Next update') ?></th> 
            <th><?php print __('Feed') ?></th> 
            <th><?php print __('Posts count') ?></th> 
            <th><?php print __('Valid') ?></th> 
            <th><?php print __('Post status') ?></th> 
            <th><?php print __('Rules') ?></th> 
            <th><?php print __('Last log') ?></th> 

            </thead>
            <tbody>
                <?php
                $def_options = $this->cf->def_options;
                foreach ($feeds as $feed) {
                    $options = unserialize($feed->options);
                    $feed_invalid = isset($options['feed_invalid']) ? $options['feed_invalid'] : -1;
                    if ($feed_invalid == 0) {
                        $valid_data = array('text' => 'Valid', 'class' => 'green');
                    } else if ($feed_invalid > 0) {
                        $valid_data = array('text' => 'Invalid', 'class' => 'red');
                    } else {
                        $valid_data = array('text' => 'No info', 'class' => '');
                    }

                    //Author link
                    $author = $this->cm->get_author($feed->author);
                    $author_link = $this->theme_author_link($feed->author, $author->name);
                    $status_int = $feed->status;
                    ?>
                    <tr> 
                        <th  class="check-column" ><input type="checkbox" name="bulk-<?php print $feed->id ?>"></th>
                        <td><?php print $feed->id ?></td>
                        <td><?php print $this->cf->curr_date($feed->date) ?></td>
                        <td>
                            <?php print stripslashes($feed->title) ?><br />                    
                            <?php
                            $feed_actions = $this->cf->feed_actions();
                            $feed_url = $url . '&cid=' . $feed->id;
                            $action_links = $this->get_filters($feed_actions, $feed_url, $curr_tab = 'none', $front_slug = 'home', $name = 'tab');
                            print $action_links;
                            ?>
                        </td>
                        <td><?php print $author_link ?></td>              
                        <td class="nowrap"><i class="sticn st-<?php print $status_int ?>"></i><?php print $this->cf->feed_state[$feed->status] ?></td>
                        <td><?php print $this->cf->update_interval[$feed->update_interval] ?></td>
                        <td><?php print $this->cf->curr_date($feed->last_update) ?></td>
                        <td><?php print $this->cf->get_next_update($feed->last_update, $feed->update_interval) ?></td>
                        <td>
                            <?php print 'RSS: ' . $feed->feed ?>
                            <?php if ($feed->site) print '<br />URL: ' . $feed->site ?>
                        </td>                  
                        <td><?php print $this->cm->get_feed_count($feed->id) ?></td>
                        <td>
                            <span class="<?php print $valid_data['class'] ?>"><?php print $valid_data['text'] ?></span>
                            <?php
                            if ($feed_invalid > 0) {
                                print '<br /> count:&nbsp;' . $feed_invalid;
                            }
                            ?>
                        </td>
                        <td><?php
                            $post_status = isset($options['post_status']) ? $options['post_status'] : $def_options['options']['post_status'];
                            print ($this->cm->post_status[$post_status]);
                            ?></td>
                        <td><?php
                            $rules = isset($options['rules']) ? $options['rules'] : array();
                            print count($rules);
                            ?></td>
                        <td><?php print $this->cf->get_last_log($feed->id) ?></td>


                    </tr> 
                <?php } ?>
            </tbody>
        </table>    
    </form>
    <?php print $pager ?>
    <?php
} else {
    ?>
    <p><?php print __('The feeds not found') ?></p>
    <?php
}
?>