<h2><a href="<?php print $url ?>"><?php print __('Critic matic') ?></a>. <?php print __('Audience') ?></h2>

<?php
print $tabs;
print $filters;


if (sizeof($posts) > 0) {
    ?>
    <?php print $pager ?>
    <form accept-charset="UTF-8" method="post" >
        <div class="bulk-actions-holder">
            <select name="bulkaction" class="bulk-actions">
                <option value=""><?php print __('Bulk actions') ?></option>
                <?php foreach ($this->bulk_actions_audience as $act_key => $act_name) { ?>                    
                    <option value="<?php print $act_key ?>">
                        <?php print $act_name ?>
                    </option>                                
                <?php } ?>                       
            </select>
            <input type="submit" id="edit-submit" value="<?php echo __('Submit') ?>" class="button-primary">  
        </div>
        <table id="overview" class="wp-list-table widefat striped table-view-list">
            <thead>
                <tr>
                    <td class="manage-column column-cb check-column" ><input type="checkbox" id="cb-select-all-1"></td>
                    <?php $this->sorted_head('id', 'id', $orderby, $order, $page_url) ?>                
                    <?php $this->sorted_head('date', 'Date', $orderby, $order, $page_url) ?>                                         
                    <?php $this->sorted_head('title', 'Title / Countent', $orderby, $order, $page_url) ?>                
                    <th><?php print __('Status') ?></th>
                    <th><?php print __('Movie') ?></th>
                    <th><?php print __('Rating') ?></th>
                    <th><?php print __('Author') ?></th>  
                    <th><?php print __('IP') ?></th>
                    <th><?php print __('IP list') ?></th>
                    <th><?php print __('In index') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ($posts as $item) {
                    //Post
                    //$post_type = $this->cm->get_post_status($item->status);
                    //Author
                    $author = $this->cm->get_author($item->aid);
                    $author_name = $author->name;
                    $author_type = $this->cm->get_author_type($author->type);
                    //Author link
                    $author_link = $this->theme_author_link($item->aid, $author_name);
                    //Post links         
                    $post_actions = $this->cm->post_actions();
                    $post_url = $this->admin_page . $this->audience_url . '&pid=' . $item->id;
                    $post_links = $this->get_filters($post_actions, $post_url, $curr_tab = 'none', $front_slug = 'home', $name = 'tab', '', false);

                    // meta
                    $rating_data = $this->cm->get_post_rating($item->id);
                    //$email = isset($rating_data['em']) ? $rating_data['em'] : '';
                    $ip = isset($rating_data['ip']) ? $rating_data['ip'] : '';
                    if ($ip) {
                        $ip_item = $this->cm->get_ip($ip);
                        $ip_type = 0;
                        if ($ip_item) {
                            $ip_type = $ip_item->type;
                        }
                        $ip_type_name = $this->cm->ip_status[$ip_type];
                    }
                    ?>
                    <tr>
                        <th  class="check-column" ><input type="checkbox" name="bulk-<?php print $item->id ?>"></th>                        
                        <td class="mob-hide"><?php print $item->id ?></td>     
                        <td class="mob-hide"><?php print $this->cm->curr_date($item->date) ?></td>                                           
                        <td>
                            <b><?php print $item->title ?></b><br />        
                            <?php print $item->content ?>
                            <a href="<?php print $item->link ?>" target="_blank" title="<?php print $item->link ?>"><?php print substr($item->link, 0, 70) ?></a><br />
                            <?php print $post_links ?>
                        </td> 
                        <td><?php
                            $status = $this->cm->get_post_status($item->status);
                            if ($item->status == 0) {
                                $status = '<b class="red">' . $status . '</b>';
                            }
                            print $status;
                            ?>
                        </td>
                        <td><?php
                            if ($item->top_movie) {
                                $top_movie = $item->top_movie;
                                //$top_movie = $this->cm->get_top_movie($item->id);
                                print $this->theme_movie_link($top_movie, $this->get_movie_name_by_id($top_movie));
                            } else {
                                print __('None');
                            }
                            ?></td> 
                        <td><?php print $cfront->theme_rating($rating_data); ?></td>
                        <td><?php print $author_link ?></td>    
                        <td><?php print $ip ?></td> 
                        <td><?php print $ip_type_name ?></td>
                        <td><?php print $this->cs->critic_in_index($item->id) ? 'Index' : 'Not'; ?></td>
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