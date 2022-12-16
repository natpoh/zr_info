<h2><a href="<?php print $url ?>"><?php print __('Movies Links Parsers') ?></a>. <?php print __('View') ?></h2>

<?php if ($campaign) { ?>
    <h3><?php print __('Campaign') ?>: [<?php print $cid ?>] <?php print $campaign->title ?></h3>
    <?php
} else {
    ?>
    <h3><?php print __('Campaign not found') ?>: [<?php print $cid ?>]</h3>
    <?php
    return;
}

print $tabs;

if ($cid) {
    $options = unserialize($campaign->options);
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
                <td><?php print __('Title') ?></td>
                <td><?php print $campaign->title ?></td>
            </tr>
            <tr>
                <td><?php print __('Site') ?></td>
                <td><?php print $campaign->site ?></td>
            </tr>
            <tr>
                <td><?php print __('Type') ?></td>
                <td><?php
                    print $this->parser_types[$campaign->type];
                    ?>
                </td>
            </tr>
            <tr>
                <td><?php print __('Status') ?></td>
                <td><?php
                    print $this->camp_state[$campaign->status]['title'];
                    ?>
                </td>
            </tr>

            <tr>
                <td><?php print __('URLs count') ?></td>
                <td><?php
                    $count = $this->mp->get_urls_count(-1, $cid);
                    print $count;
                    ?>. <?php if ($count > 0) { ?> 
                        <a target="_blank" href="<?php print $url ?>&cid=<?php print $cid ?>&export=1">Export</a>.
                    <?php } ?></td>
            </tr>
            <tr>
                <td><?php print __('Posts count') ?></td>
                <td><?php
                    $count = $this->mp->get_posts_count($cid);
                    print $count;
                    ?>. <?php if ($count > 0) { ?> 
                        <a target="_blank" href="<?php print $url ?>&cid=<?php print $cid ?>&export_posts=1">Export posts</a>.
                        <?php
                        $mp = $this->ml->get_mp();
                        $export_path = $mp->get_full_export_path($cid);
                        if (file_exists($export_path)) {
                            $get_path = str_replace(ABSPATH, '/', $export_path);
                            ?> 
                            <a target="_blank" href="<?php print $get_path ?>">Downoad file</a>.
                            <?php
                        }
                        ?>
                    <?php } ?></td>
            </tr>
            <tr>
                <td><?php print __('Last log') ?></td>
                <td><?php print $this->get_last_log(0, $cid) ?></td>
            </tr>
        </tbody>        
    </table>

    <h2>Parsers status</h2>
    <table class="wp-list-table widefat striped table-view-list">
        <thead>
            <tr>
                <th><?php print __('Name') ?></th>                
                <th><?php print __('Value') ?></th>    
            </tr>
        </thead>
        <tbody>
            <?php
            foreach ($this->mp->campaign_modules as $module) {
                if (isset($options[$module])) {
                    $item = $options[$module];
                    $log_status = $this->option_names[$module]['log'];

                    $last_update = $item['last_update'];
                    if ($last_update) {
                        $last_update = $this->mp->curr_date($last_update);
                    }
                    ?>
                    <tr>
                        <td colspan="2"> <h3><?php print $this->option_names[$module]['title']; ?></h3></td>
                    </tr>              

                    <tr>
                        <td><?php print __('Interval') ?></td>
                        <td><?php print $this->update_interval[$item['interval']] ?></td>
                    </tr>
                    <tr>
                        <td><?php print __('Last update') ?></td>
                        <td><?php print $last_update ?></td>
                    </tr>
                    <tr>
                        <td><?php print __('Status') ?></td>
                        <td><i class="sticn st-<?php print $item['status'] ?>"></i><?php print $this->camp_state[$item['status']]['title']; ?></td>
                    </tr>
                    <tr>
                        <td><?php print __('Last log') ?></td>
                        <td><?php print $this->get_last_log(0, $cid, $log_status) ?></td>
                    </tr>

                    <?php
                } else {
                    ?>
                    <tr>
                        <td><?php print $this->option_names[$module]['title']; ?></td><td>Not configured</td>
                    </tr>  
                    <?php
                }
            }
            ?>
        </tbody>        
    </table>

<?php } ?>