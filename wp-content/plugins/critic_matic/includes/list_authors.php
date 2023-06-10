<h2><a href="<?php print $url ?>"><?php print __('Critic matic') ?></a>. <?php print __('Critic authors') ?></h2>

<?php print $tabs; ?>
<?php
if (isset($filters_tabs['filters'])) {
    print implode("\n", array_values($filters_tabs['filters']));
}
?>

<?php
if (sizeof($authors) > 0) {

    $author_status = $this->cm->author_status;
    ?>
    <?php print $pager ?>
    <form accept-charset="UTF-8" method="post" >
        <div class="bulk-actions-holder">
            <select name="bulkaction" class="bulk-actions">
                <option value=""><?php print __('Bulk actions') ?></option>
                <?php foreach ($this->bulk_actions_authors as $act_key => $act_name) { ?>                    
                    <option value="<?php print $act_key ?>">
                        <?php print $act_name ?>
                    </option>                                
                <?php } ?>                       
            </select>
            <input type="submit" id="edit-submit" value="<?php echo __('Submit') ?>" class="button-primary">  
        </div>
        <table id="authors" class="wp-list-table widefat striped table-view-list">
            <thead>
                <tr>
                    <td class="manage-column column-cb check-column" ><input type="checkbox" id="cb-select-all-1"></td>
                    <th ><?php print __('Img') ?></th> 
                    <?php $this->sorted_head('id', 'id', $orderby, $order, $page_url) ?>                                

                    <?php $this->sorted_head('name', 'Author', $orderby, $order, $page_url) ?> 

                    <?php $this->sorted_head('status', 'Status', $orderby, $order, $page_url) ?>
                    <?php $this->sorted_head('show_type', 'Show type', $orderby, $order, $page_url) ?>
                    <th><?php print __('Avatar') ?></th> 
                    <th><?php print __('Autoblur') ?></th> 
                    <th><?php print __('Tags') ?></th>
                    <th><?php print __('Posts') ?></th> 
                    <th><?php print __('Feeds') ?></th> 
                    <th><?php print __('Parsers') ?></th> 
                    <th><?php print __('Actions') ?></th> 
                </tr>
            </thead>
            <tbody>
                <?php
                $cav = $this->cm->get_cav();
                $cp = $this->cm->get_cp();

                foreach ($authors as $author) {
                    $author_name = $author->name;
                    $author_type = $this->cm->get_author_type($author->type);
                    $author_status = $this->cm->get_author_status($author->status);

                    //Tags
                    $tags = $this->cm->get_author_tags($author->id);
                    $tag_arr = $this->theme_author_tags($tags);

                    //Author posts
                    $post_count = $this->cm->get_author_post_count($author->id);

                    // Feeds
                    $campaigns = $this->cf->get_feeds_count(-1, $author->id);

                    // Parsers
                    $parsers = $cp->get_parser_count($author->id);

                    //Options
                    $options = unserialize($author->options);

                    //Author links
                    $author_actions = $this->cm->authors_actions(array('home'));
                    $author_url = $url . '&aid=' . $author->id;
                    $action_links = $this->get_filters($author_actions, $author_url, $curr_tab = 'none', $front_slug = 'home', $name = 'tab', '', false);

                    //image   
                    $image='';
                    $avatar_url = $cav->get_pro_avatar($author->avatar_name);
                    if ($avatar_url) {
                        $image = '<img src="' . $avatar_url . '" width="50"  height="50">';
                    }
                    ?>
                    <tr>
                        <th  class="check-column" ><input type="checkbox" name="bulk-<?php print $author->id ?>"></th>
                        <td ><?php print $image ?></td>  
                        <td><?php print $author->id ?></td>     

                        <td>
                            <a href="<?php print $author_url ?>"><?php print $author_name ?></a>
                        </td>

                        <td><?php print $author_status ?></td>
                        <td><?php print $this->cm->author_show_type[$author->show_type] ?></td>
                        <td><?php
                            if ($author->avatar_name) {
                                print $author->avatar_name;
                            }
                            if ($options['image']) {
                                print '<br />URL exist';
                            }
                            ?></td>
                        <td><?php print isset($options['autoblur']) && $options['autoblur'] == 1 ? 'True' : 'False'  ?></td>
                        <td><?php print implode(', ', $tag_arr) ?></td>  
                        <td><?php print $post_count ?></td>   
                        <td><?php print $campaigns ?></td> 
                        <td><?php print $parsers ?></td> 
                        <td><?php print $action_links; ?>
                        </td>
                    </tr> 
                <?php } ?>
            </tbody>
        </table>    
    </form>
    <?php print $pager ?>
    <?php
} else {
    ?>
    <p><?php print __('The authors not found') ?></p>
    <?php
}
?>