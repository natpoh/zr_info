<h2><a href="<?php print $url ?>"><?php print __('Critic parsers') ?></a>. <?php print __('URLs') ?></h2>

<?php if ($cid) { ?>
    <h3><?php print __('Campaign') ?>: [<?php print $cid ?>] <?php print stripslashes($campaign->title) ?></h3>
    <?php
}

print $tabs;
print $filters;
print $filters_meta_type;
$queue_ids = $this->cs->get_search_ids();

/*
  `cid` int(11) NOT NULL DEFAULT '0',
  `pid` int(11) NOT NULL DEFAULT '0',
  `status` int(11) NOT NULL DEFAULT '0',
  `link_hash` varchar(255) NOT NULL default '',
  `link` text default NULL,
 */
if (sizeof($posts) > 0) {
    ?>
    <?php print $pager ?>  
    <form accept-charset="UTF-8" method="post" >
        <div class="bulk-actions-holder">
            <select name="bulkaction" class="bulk-actions">
                <option value=""><?php print __('Bulk actions') ?></option>
                <?php foreach ($this->cp->bulk_actions as $act_key => $act_name) { ?>                    
                    <option value="<?php print $act_key ?>">
                        <?php print $act_name ?>
                    </option>                                
                <?php } ?>                       
            </select>
            <input type="submit" id="edit-submit" value="<?php echo __('Submit') ?>" class="button-primary">  
        </div>

        <table id="parsers" class="wp-list-table widefat striped table-view-list">
            <thead>
            <td class="manage-column column-cb check-column" ><input type="checkbox" id="cb-select-all-1"></td>
            <?php $this->sorted_head('id', 'id', $orderby, $order, $page_url) ?>             
            <th><?php print __('Link') ?></th>                                
            <?php $this->sorted_head('status', 'Status', $orderby, $order, $page_url) ?>                                     
            <th><?php print __('Post') ?></th>                 
            <th><?php print __('Link') ?></th> 
            <th><?php print __('Post author') ?></th>  
            <th><?php print __('Post type') ?></th>
            <th><?php print __('Post status') ?></th>
            <th><?php print __('In index') ?></th>
            <th><?php print __('Campaign') ?></th> 
            <th><?php print __('Last log') ?></th> 
            <th><?php print __('Actions') ?></th> 
            </thead>
            <tbody>
                <?php
                foreach ($posts as $item) {

                    //Post links     
                    $post_url = '';
                    $author_link = '';
                    $post = '';


                    if ($item->pid) {
                        $post = $this->cm->get_post($item->pid);
                    } else {
                        // Get post by linkhash
                        $link_hash = $item->link_hash;
                        $post = $this->cm->get_post_by_link_hash($link_hash);
                    }
                    if ($post) {
                        $post_url = $this->admin_page . $this->parrent_slug . '&pid=' . $post->id;

                        //Author
                        $author = $this->cm->get_author($post->aid);
                        $author_name = $author->name;
                        //Author link
                        $author_link = $this->theme_author_link($post->aid, $author_name);
                    }
                    $preview_link = $url . '&tab=preview&cid=' . $item->cid . '&uid=' . $item->id;
                    ?>
                    <tr>           
                        <th  class="check-column" ><input type="checkbox" name="bulk-<?php print $item->id ?>"></th>
                        <td><?php print $item->id ?></td>                             
                        <td class="wrap">                            
                            <a href="<?php print $item->link ?>" target="_blank" title="<?php print $item->link ?>"><?php print $item->link ?></a>                    
                            <?php /*
                              if ($item->link):
                              //validate hash
                              if (!$this->cm->validate_link_hash($item->link, $item->link_hash)) {
                              $link_hash = $this->cp->url_update_link_hash($item->id, $item->link);
                              print $link_hash ? '<br />' . $link_hash : '';
                              }
                              ?>
                              <?php endif */ ?>
                        </td>
                        <td><?php print $this->cp->get_url_status($item->status) ?></td>
                        <td class="wrap"><?php if ($post_url) { ?>
                                <a href="<?php print $post_url ?>" ><?php print $post->title ?></a>                    
                            <?php } ?></td>                             
                        <td><?php
                            if ($post && $post->top_movie) {
                                $top_movie = $post->top_movie;
                                print $this->theme_movie_link($top_movie, $this->get_movie_name_by_id($top_movie));
                            } else {
                                print __('None');
                            }
                            ?></td>
                        <td><?php print $author_link; ?></td>
                        <td><?php print $post ? $this->cm->get_post_type($post->type) : ""; ?></td>                       
                        <td><?php print $post ? $this->cm->get_post_status($post->status) : ""  ?></td>
                        <td>
                            <?php
                            if ($post) {
                                print $this->cs->critic_in_index($post->id) ? 'Index' : 'Not';

                                if (in_array($post->id, $queue_ids)) {
                                    print '. <br >Waiting';
                                }
                            }
                            ?>
                        </td>
                        <td>
                            <a href="/wp-admin/admin.php?page=critic_matic_parser&cid=<?php print $item->cid ?>"><?php print $item->cid ?></a>
                        </td>
                        <td><?php print $this->cp->get_last_log($item->id); ?></td>
                        <td>
                            <a href="<?php print $preview_link ?>">Preview</a>
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
    <p><?php print __('The urls not found') ?></p>
    <?php
}
?>