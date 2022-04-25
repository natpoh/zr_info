<h2><a href="<?php print $url ?>"><?php print __('Critic feeds') ?></a>. <?php print __('Posts') ?></h2>

<?php if ($cid) { ?>
    <h3><?php print __('Campaign') ?>: [<?php print $cid ?>] <?php print $campaign->title ?></h3>
    <?php
}

print $tabs;
print $filters_type;
print $filters_meta_type;
print $filters;

$queue_ids = $this->cs->get_search_ids();

if (sizeof($posts) > 0) {
    ?>
    <?php print $pager ?>  
    <form accept-charset="UTF-8" method="post" >
        <div class="bulk-actions-holder">
            <select name="bulkaction" class="bulk-actions">
                <option value=""><?php print __('Bulk actions') ?></option>
                <?php foreach ($this->bulk_actions as $act_key => $act_name) { ?>                    
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
            <?php $this->sorted_head('title', 'Title / Link', $orderby, $order, $page_url) ?>              
            <th><?php print __('Content') ?></th>                 
            <th><?php print __('Author') ?></th> 
            <th><?php print __('Status') ?></th>
            <th><?php print __('Type') ?></th> 
            <th><?php print __('In index') ?></th>
            <th><?php print __('Actions') ?></th> 
            </thead>
            <tbody>
                <?php
                foreach ($posts as $item) {
                    //Author
                    $author = $this->cm->get_author($item->aid);
                    $author_name = $author->name;
                    $author_link = $this->theme_author_link($item->aid, $author_name);

                    //Post links         
                    $post_actions = $this->cm->post_actions();
                    $post_url = $this->admin_page . $this->parrent_slug . '&pid=' . $item->id;
                    $post_links = $this->get_filters($post_actions, $post_url, $curr_tab = 'none', $front_slug = 'home', $name = 'tab');
                    ?>
                    <tr>           
                        <th  class="check-column" ><input type="checkbox" name="bulk-<?php print $item->id ?>"></th>
                        <td><?php print $item->id ?></td>     
                        <td><?php print $this->cm->curr_date($item->date) ?></td>                                           
                        <td>
                            <?php print $item->title ?>
                            <?php 
                            if ($item->link):
                                //validate hash
                                /*if (!$this->cm->validate_link_hash($item->link, $item->link_hash)) {
                                    $link_hash = $this->cm->update_link_hash($item->id, $item->link);
                                    print $link_hash?'<br />'.$link_hash:'';
                                }*/
                                ?>
                                <br />
                                <a href="<?php print $item->link ?>" target="_blank" title="<?php print $item->link ?>"><?php print substr($item->link, 0, 70) ?></a>                    
                            <?php endif ?>
                        </td>
                        <td><?php print $this->cm->crop_text(strip_tags($item->content), 100) ?></td>
                        <td><?php print $author_link ?></td>
                        <td><?php print $this->cm->get_post_status($item->status) ?></td>
                        <td><?php print $this->cm->get_post_type($item->type) ?></td>
                        <td><?php
                            print $this->cs->critic_in_index($item->id) ? 'Index' : 'Not';
                            if (in_array($item->id, $queue_ids)) {
                                print '.<br >Waiting';
                            }
                            ?></td>
                        <td><?php print $post_links ?></td>

                    </tr> 
                <?php } ?>
            </tbody>
        </table>  
    </form>
    <?php print $pager ?>
    <?php
} else {
    ?>
    <p><?php print __('The critic posts not found') ?></p>
    <?php
}
?>