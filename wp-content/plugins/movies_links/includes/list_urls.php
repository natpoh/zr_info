<h2><a href="<?php print $url ?>"><?php print __('Movies Links') ?></a>. <?php print __('URLs') ?></h2>

<?php if ($cid) { ?>
    <h3><?php print __('Campaign') ?>: [<?php print $cid ?>] <?php print $campaign->title ?></h3>
    <?php
}

print $tabs;

if (isset($filters_tabs['filters'])) {
    print implode("\n", array_values($filters_tabs['filters']));
}

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
                <?php foreach ($this->bulk_actions as $act_key => $act_name) { ?>                    
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
            <?php $this->sorted_head('date', 'date', $orderby, $order, $page_url) ?>            
            <?php $this->sorted_head('last_upd', 'last_upd', $orderby, $order, $page_url) ?>
            <?php $this->sorted_head('pid', 'pid', $orderby, $order, $page_url) ?>
            <th><?php print __('Link') ?></th>                                
            <?php if ($campaign->type != 1) { ?>
                <?php $this->sorted_head('pid', 'Movie ID', $orderby, $order, $page_url) ?>
            <?php } ?>
            <?php $this->sorted_head('parent_url', 'Parent URL', $orderby, $order, $page_url) ?>
            <?php $this->sorted_head('status', 'Status', $orderby, $order, $page_url) ?>                                     
            <?php $this->sorted_head('adate', 'Arhive', $orderby, $order, $page_url) ?>            
            <?php $this->sorted_head('pdate', 'Post', $orderby, $order, $page_url) ?>                    
            <th><?php print __('Link') ?></th>
            <?php if ($update_status) { ?>
                <?php $this->sorted_head('exp_status', 'Expire', $orderby, $order, $page_url) ?>
                <?php $this->sorted_head('upd_rating', 'Update rating', $orderby, $order, $page_url) ?>
            <?php } ?>
            <th><?php print __('Campaign') ?></th> 
            <?php /* ?>
              <th><?php print __('Last log') ?></th>
              <?php */ ?>



            </thead>
            <tbody>
                <?php
                $ma = $this->ml->get_ma();
                foreach ($posts as $item) {

                    $campaign = $this->mp->get_campaign($item->cid, true);
                    $camp_title = $item->cid;
                    if ($campaign) {
                        $camp_title = $campaign->title;
                    }
                    $camp_title = $this->mla->theme_parser_campaign($item->cid, $camp_title);
                    ?>
                    <tr>           
                        <th  class="check-column" ><input type="checkbox" name="bulk-<?php print $item->id ?>"></th>
                        <td>
                            <a href="<?php print $url . '&uid=' . $item->id ?>"><?php print $item->id ?></a>
                        </td>                             
                        <td><?php print $item->date ? $this->mp->curr_date($item->date) : 0  ?></td> 
                        <td><?php print $item->last_upd ? $this->mp->curr_date($item->last_upd) : 0  ?></td> 
                        <td>
                            <?php print $item->pid ?>
                        </td>  
                        <td class="wrap">                            
                            <a href="<?php print $item->link ?>" target="_blank" title="<?php print $item->link ?>"><?php print $item->link ?></a>                                               
                        </td>
                        <?php if ($campaign->type != 1) { ?>
                            <th><a href="/wp-admin/admin.php?page=critic_matic_movies&mid=<?php print $item->pid ?>"><?php print $item->pid ?></a></th>
                        <?php } ?>
                        <td><?php if ($item->parent_url) { ?>
                                <a href="<?php print $url . '&uid=' . $item->parent_url ?>"><?php print $item->parent_url ?></a>
                            <?php }
                            ?></td>
                        <td><?php print $this->get_url_status($item->status) ?></td>
                        <td>
                            <?php
                            if ($item->adate) {
                                print $this->mp->curr_date($item->adate);
                            }
                            ?>
                        </td>
                        <td>
                            <?php
                            if ($item->ptitle) {
                                $title = '<b>' . $item->ptitle . '</b>';
                                if ($item->pyear) {
                                    $title = $title . ' [' . $item->pyear . ']';
                                }
                                print $title . '<br />';
                            }
                            if ($item->pdate) {
                                print 'Date: ' . $this->mp->curr_date($item->pdate) . '<br />';
                            }
                            if (isset($item->pstatus)) {
                                print 'Status: ' . $this->post_parse_status[$item->pstatus];
                            }
                            ?>
                        </td>
                        <td>
                            <?php
                            if ($item->ptop_movie) {
                                /* $m = $ma->get_movie_by_id($item->ptop_movie);
                                  $title = '<b>' . $m->title . '</b>';
                                  print $title . '  ['.$m->year.']<br />'; */
                                print $item->ptop_movie . '<br />';
                            }
                            if ($item->prating) {
                                print 'Rating: ' . $item->prating . '<br />';
                            }
                            if (isset($item->pstatus_links)) {
                                print 'Status: ' . $this->post_link_status[$item->pstatus_links];
                            }
                            ?>
                        </td>
                        <?php if ($update_status) { ?>
                            <td><?php print $this->exp_status[$item->exp_status]; ?></td>
                            <td><?php print $item->upd_rating; ?></td>
                        <?php } ?>
                        <td>
                            <?php print $camp_title ?>
                        </td>
                        <?php /* ?>
                          <td><?php print $this->get_last_log($item->id); ?></td>
                          <?php */ ?>

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