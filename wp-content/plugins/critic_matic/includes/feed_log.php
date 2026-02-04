<h2><a href="<?php print $url ?>"><?php print __('Critic feeds') ?></a>. <?php print __('Log') ?> <small>(<?php print $count ?>)</small></h2>

<?php if ($cid){?>
<h3><?php print __('Campaign') ?>: [<?php print $cid ?>] <?php print stripslashes($campaign->title) ?></h3>
<?php }

print $tabs;

if (sizeof($log) > 0) {
    ?>
    <style>
        .tablenav-pages .pagination-links .button {
            margin-right: 5px;
        }
    </style>
    <?php print $pager ?>
    <table id="feeds" class="wp-list-table widefat striped table-view-list">
        <thead>
        <th class="mob-hide"><?php print __('id') ?></th>
        <th><?php print __('Date') ?></th> 
        <th><?php print __('Type') ?></th>
        <th><?php print __('Status') ?></th>
        <th><?php print __('Message') ?></th> 
        <th><?php print __('Campaign') ?></th>
    </thead>
    <tbody>
        <?php
        $log= array_reverse($log);
        foreach ($log as $item) {
            ?>
            <tr> 
                <td><?php print $item->id ?></td>
                <td><?php print $this->cf->curr_date($item->date) ?></td>
                <td><?php print $this->cf->get_log_type($item->type) ?></td>
                <td><?php print $this->cf->get_log_status($item->status) ?></td>
                <td><?php print $item->message ?></td>
                <td><?php print $this->theme_feed_link($item->cid,$item->cid) ?></td> 
            </tr> 
        <?php } ?>
    </tbody>
    </table>    
    <?php print $pager ?>
    <?php
} else {
    ?>
    <p><?php print __('The log is empty') ?></p>
    <?php
}
?>