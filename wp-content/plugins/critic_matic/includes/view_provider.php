<h2><a href="<?php print $url ?>"><?php print __('Critic matic') ?></a>. <?php print __('View provider') ?></h2>

<?php if ($provider) { ?>
    <h3><?php print __('Provider') ?>: [<?php print $pid ?>] <?php print $provider->name ?></h3>
    <?php
} else {
    ?>
    <h3><?php print __('Provider not found') ?>: [<?php print $pid ?>]</h3>
    <?php
    return;
}

print $tabs;

if ($pid) {
    if ($provider->image) {
        ?>
        <img src="/wp-content/uploads/thumbs/providers_img/100x100/<?php print $provider->pid ?>.jpg">
    <?php } ?>
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
                <td><?php print $provider->name ?></td>
            </tr>  
            <tr>
                <td><?php print __('Slug') ?></td>
                <td><?php print $provider->slug ?></td>
            </tr>
            <tr>
                <td><?php print __('Pid') ?></td>
                <td><?php print $provider->pid ?></td>
            </tr>
            <tr>
                <td><?php print __('Weight') ?></td>
                <td><?php print $provider->weight ?></td>
            </tr>
            <tr>
                <td><?php print __('Image') ?></td>
                <td><?php print $provider->image ?></td>
            </tr>
            <tr>
                <td><?php print __('Status') ?></td>
                <td><?php print $ma->get_provider_status($provider->status) ?></td>
            </tr>
        </tbody>        
    </table>

<?php } ?>