<h2><a href="<?php print $url ?>"><?php print __('Tor Parser') ?></a>. <?php print __('Logs') ?></h2>
<?php
print $tabs;
if (isset($filters_tabs['filters'])) {
    print implode("\n", array_values($filters_tabs['filters']));
}
?>


<?php
if (sizeof($logs) > 0) {
    krsort($logs);
    ?>
    <?php print $pager ?>

    <table id="services" class="wp-list-table widefat striped table-view-list">
        <thead>
            <?php $this->sorted_head('id', 'id', $orderby, $order, $page_url) ?>                                
            <?php $this->sorted_head('date', 'Date', $orderby, $order, $page_url) ?>
            <?php $this->sorted_head('type', 'Type', $orderby, $order, $page_url) ?>
            <?php $this->sorted_head('status', 'Status', $orderby, $order, $page_url) ?>
        <th><?php print __('Service') ?></th>
        <th><?php print __('IP') ?></th>
        <th><?php print __('Agent') ?></th>
        <th><?php print __('Site') ?></th>
        <th><?php print __('Code') ?></th>        
    </thead>
    <tbody>
        <?php

        foreach ($logs as $service) {
            ?>
            <tr> 
                <td><?php print $service->id ?></td>
                <td><?php print $this->tp->curr_date($service->date); ?></td>                
                <td><?php print $this->log_type[$service->type] ?></td>
                <td><?php print $this->log_status[$service->status] ?></td>
                <td><?php print $this->tp->get_service_name_by_id($service->driver) ?></td>
                <td><?php print $this->tp->get_ip_name_by_id($service->ip) ?></td>
                <td><?php print $this->tp->get_agent_name_by_id($service->agent) ?></td>
                <td><?php print $this->tp->get_site_name_by_id($service->url) ?></td>
                <td><?php print $service->resp_code ?></td>
            </tr> 
        <?php } ?>
    </tbody>
    </table>  
    <?php print $pager ?>


    <?php
} else {
    ?>
    <p><?php print __('The logs not found') ?></p>
    <?php
}
?>