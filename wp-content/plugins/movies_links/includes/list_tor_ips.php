<h2><a href="<?php print $url ?>"><?php print __('Tor Parser') ?></a>. <?php print __('IPs') ?></h2>
<?php
print $tabs;
if (isset($filters_tabs['filters'])) {
    print implode("\n", array_values($filters_tabs['filters']));
}
?>


<?php
if (sizeof($ips) > 0) {
    ?>
    <?php print $pager ?>

    <table id="services" class="wp-list-table widefat striped table-view-list">
        <thead>
            <?php $this->sorted_head('id', 'id', $orderby, $order, $page_url) ?>                                
        <th><?php print __('IP') ?></th>        
        <?php $this->sorted_head('date', 'Date', $orderby, $order, $page_url) ?>
        <th><?php print __('Agent') ?></th> 

    </thead>
    <tbody>
        <?php
        foreach ($ips as $service) {
            ?>
            <tr> 
                <td><?php print $service->id ?></td>
                <td><?php print $service->ip ?></td>                
                <td><?php
                    if ($service->date) {
                        print $this->tp->curr_date($service->date);
                    }
                    ?></td>
                <td><?php print $service->user_agent ?>               
            </tr> 
        <?php } ?>
    </tbody>
    </table>  
    <?php print $pager ?>


    <?php
} else {
    ?>
    <p><?php print __('The ips not found') ?></p>
    <?php
}
?>