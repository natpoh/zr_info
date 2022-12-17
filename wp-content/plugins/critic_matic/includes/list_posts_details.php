<h2><a href="<?php print $url ?>"><?php print __('Critic matic') ?></a>. <?php print __('Posts') ?></h2>

<form id="spform" method="get" action="<?php print $url ?>">
    <div class="sbar">
        <input type="search" name="s" id="sbar" size="15" value="<?php print $s ?>" placeholder="Search" autocomplete="off">
    </div>
    <input type="submit" id="submit" class="btn" value="find">        
    <input type="hidden" name="page" value="critic_matic">
</form>
<?php

print $tabs;

if (isset($filters_tabs['filters'])){
    print implode("\n", array_values($filters_tabs['filters']));
}


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
        <table id="overview" class="wp-list-table widefat striped table-view-list">
            <thead>
                <tr>
                    <td class="manage-column column-cb check-column" ><input type="checkbox" id="cb-select-all-1"></td>
                    <?php $this->sorted_head('id', 'id', $orderby, $order, $page_url) ?>                
                    <?php $this->sorted_head('date', 'Date', $orderby, $order, $page_url) ?>      
                    <?php $this->sorted_head('date_add', 'Last update', $orderby, $order, $page_url) ?>  
                    <?php $this->sorted_head('title', 'Title / Link', $orderby, $order, $page_url) ?>                
                    <th><?php print __('Content') ?></th>                 
                    <th><?php print __('Author') ?><br /><input type="text" placeholder="filter" value="" id="filter_author"></th>                                       
                    <th><?php print __('WP uid') ?></th>
                    <th><?php print __('From') ?></th>   
                    <?php if ($author_type == 0 || $author_type == 2) { ?>
                        <th><?php print __('Rating') ?></th> 
                    <?php } ?>
                    <th><?php print __('Tags') ?></th>
                    <th><?php print __('Top movie') ?></th>
                    <th><?php print __('Movies meta') ?></th> 
                    <th><?php print __('Status') ?></th>
                    <th><?php print __('Type') ?></th>   
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
                    $wp_uid = $author->wp_uid;
                    $author_name = $author->name;
                    $a_type = $this->cm->get_author_type($author->type);
                    //Author link
                    $author_link = $this->theme_author_link($item->aid, $author_name. ' ['.$item->aid.']');

                    //Tags
                    $tags = $this->cm->get_author_tags($item->aid);
                    $tag_arr = $this->theme_author_tags($tags);

                    //Post links         
                    $post_actions = $this->cm->post_actions();
                    $post_url = $this->admin_page . $this->parrent_slug . '&pid=' . $item->id;
                    $post_links = $this->get_filters($post_actions, $post_url, $curr_tab = 'none', $front_slug = 'home', $name = 'tab', '', false);

                    //Movies
                    $movies = $this->cm->get_movies_data($item->id);
                    //$movies_search_arr = $this->cs->search_movies($item->title, $item->content);
                    // $movies_search = $movies_search_arr['movies'];
                    ?>
                <tr class="row" data-author="<?php print $author_name ?>">
                        <th  class="check-column" ><input type="checkbox" name="bulk-<?php print $item->id ?>"></th>
                        <td><?php print $item->id ?></td>     
                        <td><?php print $this->cm->curr_date($item->date) ?></td> 
                        <td><?php print $this->cm->curr_date($item->date_add) ?></td> 
                        <td>
                            <?php print stripslashes($item->title); ?><br />
                            <?php if ($item->link): ?>
                                <a href="<?php print $item->link ?>" target="_blank" title="<?php print $item->link ?>"><?php print substr($item->link, 0, 70) ?></a>
                            <?php endif ?>
                            <?php print $post_links ?>
                        </td>
                        <td><?php print $this->cm->crop_text(strip_tags($item->content), 100) ?></td>                    
                        <td><?php print $author_link ?></td> 
                        <td><?php print $wp_uid ?></td> 
                        <td><?php print $a_type ?></td> 
                        <?php if ($author_type == 0 || $author_type == 2) { ?>
                            <td><?php
                                if ($author_type == 0) {
                                    if ($_GET['transit_rating']) {
                                        //one time transit rating
                                        $update_content = false;
                                        if ($_GET['update_content']) {
                                            $update_content = true;
                                        }
                                        $this->cm->transit_post_rating($item->id, $item->content, $update_content);
                                    }
                                }
                                $rating_data = $this->cm->get_post_rating($item->id);
                                if ($rating_data) {
                                    print_r($rating_data);
                                }
                                ?>
                            </td> 
                        <?php } ?>
                        <td><?php print implode(', ', $tag_arr) ?></td>  
                        <td><?php
                            if ($item->top_movie) {
                                $top_movie = $item->top_movie;
                                //$top_movie = $this->cm->get_top_movie($item->id);
                                print $this->theme_movie_link($top_movie, $this->get_movie_name_by_id($top_movie));
                            } else {
                                print __('None');
                            }
                            ?></td> 
                        <td><?php
                            print sizeof($movies);
                            ?>
                        </td> 

                        <td><?php print $this->cm->get_post_status($item->status) ?></td>
                        <td><?php
                            $item_type = $this->cm->get_post_type($item->type);
                            print $item_type;
                         
                            ?></td>
                        <td><?php
                            print $this->cs->critic_in_index($item->id) ? 'Index' : 'Not';
                            if (in_array($item->id, $queue_ids)) {
                                print '.<br >Waiting';
                            }
                            ?></td>
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