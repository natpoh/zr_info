<h2><a href="<?php print $url ?>"><?php print __('Critic matic') ?></a>. <?php print __('View country') ?></h2>

<?php if ($country) { ?>
    <h3><?php print __('Country') ?>: [<?php print $cid ?>] <?php print $country->name ?></h3>
    <?php
} else {
    ?>
    <h3><?php print __('Country not found') ?>: [<?php print $cid ?>]</h3>
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
                <td><?php print $country->name ?></td>
            </tr>  
            <tr>
                <td><?php print __('Slug') ?></td>
                <td><?php print $country->slug ?></td>
            </tr>
            <tr>
                <td><?php print __('Weight') ?></td>
                <td><?php print $country->weight ?></td>
            </tr>
            <tr>
                <td><?php print __('Status') ?></td>
                <td><?php print $ma->get_country_status($country->status) ?></td>
            </tr>
        </tbody>        
    </table>

<?php } ?>