<h2><a href="<?php print $url ?>"><?php print __('Tor Parser') ?></a>. <?php print __('View') ?></h2>

<?php if ($service) { ?>
    <h3><?php print __('Service') ?>: [<?php print $cid ?>] <?php print $service->name ?></h3>
    <?php
} else {
    ?>
    <h3><?php print __('Service not found') ?>: [<?php print $cid ?>]</h3>
    <?php
    return;
}

print $tabs;

if ($cid) {
    ?>
    <br />
    <table class="wp-list-table widefat striped table-view-list">
        <thead>
            <tr>
                <th><?php print __('Name') ?></th>                
                <th><?php print __('Value') ?></th>    
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><?php print __('Name') ?></td>
                <td><?php print $service->name ?></td>
            </tr>
            <tr>
                <td><?php print __('URL') ?></td>
                <td><?php print $service->url ?></td>
            </tr>
            <tr>
                <td><?php print __('Status') ?></td>
                <td><?php
                    print $this->service_status[$service->status];
                    ?>
                </td>
            </tr>    
            <tr>
                <td><?php print __('Type') ?></td>
                <td><?php
                    print $this->service_type[$service->type];
                    ?>
                </td>
            </tr> 
            <tr>
                <td><?php print __('Last log') ?></td>
                <td><?php //print $this->get_last_log(0, $cid)  ?></td>
            </tr>
        </tbody>        
    </table>
<?php } ?>