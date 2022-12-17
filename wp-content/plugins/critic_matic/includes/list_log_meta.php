<h2><a href="<?php print $url ?>"><?php print __('Critic matic') ?></a>. <?php print __('Log') ?> <small>(<?php print $count ?>)</small></h2>

<?php
print $tabs;
print $filters_log_status;
print $filters_type;

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
        <th><?php print __('Critic') ?></th>
        <th><?php print __('Movie') ?></th>
    </thead>
    <tbody>
        <?php
        $log = array_reverse($log);
        foreach ($log as $item) {
            ?>
            <tr> 
                <td><?php print $item->id ?></td>
                <td><?php print $this->cs->curr_date($item->date) ?></td>
                <td><?php print $this->cs->get_log_type($item->type) ?></td>
                <td><?php print $this->cs->get_log_status($item->status) ?></td>
                <td><?php print $item->message ?></td>
                <td><?php print $this->theme_post_link($item->cid, $this->cm->get_post_name_by_id($item->cid)) ?></td> 
                <td><?php print $this->theme_movie_link($item->mid, $this->get_movie_name_by_id($item->mid)) ?></td>  
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