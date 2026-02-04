<h2><a href="<?php print $url ?>"><?php print __('Critic matic') ?></a>. <?php print __('Tags') ?></h2>

<?php print $tabs; ?>
<?php print $filters; ?>

<?php
if (sizeof($tags) > 0) {
    ?>

    <?php print $pager ?>
    <table id="tags" class="wp-list-table widefat striped table-view-list">
        <thead>
            <tr>
                <?php $this->sorted_head('id', 'id', $orderby, $order, $page_url) ?>                                
                <?php $this->sorted_head('name', 'Name', $orderby, $order, $page_url) ?>                                         
                <?php $this->sorted_head('slug', 'Slug', $orderby, $order, $page_url) ?> 
                <?php $this->sorted_head('status', 'Status', $orderby, $order, $page_url) ?>        
                <th><?php print __('Authors') ?></th>
                <th><?php print __('Actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php
            foreach ($tags as $tag) {

                //Action links
                $actions = $this->cm->tag_actions(array('home'));
                $tag_url = $url . '&tid=' . $tag->id;
                $action_links = $this->get_filters($actions, $tag_url, $curr_tab = 'none', $front_slug = 'home', 'tab', 'inline');
                ?>
                <tr>
                    <td><?php print $tag->id ?></td>                        
                    <td><a href="<?php print $tag_url ?>"><?php print $tag->name ?></a></td>
                    <td><?php print $tag->slug ?></td>
                    <td><?php print $this->cm->get_tag_status($tag->status) ?></td>                    
                    <td><?php print $this->cm->get_authors_count(-1, $tag->id) ?></td>
                    <td><?php print $action_links ?></td>                    
                </tr> 
            <?php } ?>
        </tbody>
    </table>    
    <?php print $pager ?>
    <?php
} else {
    ?>
    <p><?php print __('The tags not found') ?></p>
    <?php
}
?>