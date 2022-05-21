<h2><a href="<?php print $url ?>"><?php print __('Critic matic') ?></a>. <?php print __('Author posts') ?></h2>

<?php if ($aid) { ?>
    <h3><?php print __('Author') ?>: [<?php print $aid ?>] <?php print $author->name ?></h3>
    <?php
}


print $tabs;
if (isset($filters_tabs['filters'])){
    print implode("\n", array_values($filters_tabs['filters']));
}


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
            <th ><?php print __('Id') ?></th>                
            <th ><?php print __('Date') ?></th>                                
            <th><?php print __('Title') ?> / <?php print __('Link') ?></th>                 
            <th ><?php print __('Content') ?></th> 
            <th><?php print __('Status') ?></th>
            <th><?php print __('Type') ?></th>
            <th><?php print __('In parser') ?></th>
            <?php if ($author->type == 2) { ?>
                <th><?php print __('Rating') ?></th>
            <?php } ?>
            <th><?php print __('Actions') ?></th> 
            </thead>
            <tbody>
                <?php
                foreach ($posts as $item) {
                    //Post links         
                    $post_actions = $this->cm->post_actions();
                    $post_url = $this->admin_page . $this->parrent_slug . '&pid=' . $item->id;
                    $post_links = $this->get_filters($post_actions, $post_url, $curr_tab = 'none', $front_slug = 'home', $name = 'tab');
                    ?>
                    <tr>      
                        <th  class="check-column" ><input type="checkbox" name="bulk-<?php print $item->id ?>"></th>
                        <td ><?php print $item->id ?></td>     
                        <td ><?php print $this->cm->curr_date($item->date) ?></td>                                           
                        <td>
                            <?php print $item->title ?><br />
                            <a href="<?php print $item->link ?>" target="_blank" title="<?php print $item->link ?>"><?php print substr($item->link, 0, 70) ?></a>
                        </td>
                        <td><?php print $this->cm->crop_text(strip_tags($item->content), 100) ?></td>
                        <td><?php print $this->cm->get_post_status($item->status) ?></td>
                        <td><?php print $this->cm->get_post_type($item->type) ?></td>
                        <td><?php
                            $url_exist = $this->cp->get_url_by_hash($item->link_hash);
                            if ($url_exist) {
                                print $url_exist->cid;
                            }
                            ?></td>
                        <?php if ($author->type == 2) { ?>
                            <td><?php print_r($this->cm->get_post_rating($item->id)); ?></td>
                        <?php } ?>
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