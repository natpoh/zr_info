<h2><a href="<?php print $url ?>"><?php print __('Movies Links Parser') ?></a>. <?php print __('Log') ?> <small>(<?php print $count ?>)</small></h2>

<?php if ($cid) { 
    $campaign = $this->mp->get_campaign($cid, true);
    ?>
    <h3><?php print __('Campaign') ?>: [<?php print $cid ?>] <?php print $campaign->title ?></h3>
    <?php
}

print $tabs;

if (sizeof($log) > 0) {
    ?>
    <style>
        .tablenav-pages .pagination-links .button {
            margin-right: 5px;
        }
    </style>
    <?php print $filters_log_status ?>
    <?php print $filters_type ?>    
    <?php print $pager ?>
    <table id="parsers" class="wp-list-table widefat striped table-view-list">
        <thead>
        <th class="mob-hide"><?php print __('id') ?></th>
        <th><?php print __('Date') ?></th> 
        <th><?php print __('Type') ?></th>
        <th><?php print __('Status') ?></th>
        <th><?php print __('Message') ?></th> 
        <th><?php print __('Campaign') ?></th>
        <th><?php print __('URL') ?></th>
    </thead>
    <tbody>
        <?php
        $log = array_reverse($log);
        foreach ($log as $item) {
            $campaign = $this->mp->get_campaign($item->cid, true);
            $camp_title = $item->cid;
            if ($campaign){
                $camp_title = $campaign->title;
            }
            $camp_title = $this->mla->theme_parser_campaign($item->cid, $camp_title);
            ?>
            <tr> 
                <td><?php print $item->id ?></td>
                <td><?php print $this->mp->curr_date($item->date) ?></td>
                <td><?php print $this->get_log_type($item->type) ?></td>
                <td><?php print $this->get_log_status($item->status) ?></td>
                <td><?php print $item->message ?></td>
                <td><?php print $camp_title ?></td> 
                <td><?php print $this->mla->theme_parser_url_link($item->uid, $item->uid) ?></td>
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