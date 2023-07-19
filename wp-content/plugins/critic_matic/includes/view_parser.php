<h2><a href="<?php print $url ?>"><?php print __('Critic parsers') ?></a>. <?php print __('View') ?></h2>

<?php if ($campaign) { ?>
    <h3><?php print __('Campaign') ?>: [<?php print $cid ?>] <?php print stripslashes($campaign->title) ?></h3>
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
                <td><?php print stripslashes($campaign->title) ?></td>
            </tr>
            <tr>
                <td><?php print __('Site') ?></td>
                <td><?php print $campaign->site ?></td>
            </tr>
            <tr>
                <td><?php print __('Author') ?></td>
                <td><?php print stripslashes($author->name) ?></td>
            </tr>
            <tr>
                <td><?php print __('Status') ?></td>
                <td><?php print $parser_state[$campaign->status] ?></td>
            </tr>
            <tr>
                <td><?php print __('Update interval') ?></td>
                <td><?php print $update_interval[$campaign->update_interval] ?></td>
            </tr>
            <tr>
                <td><?php print __('Last update') ?></td>
                <td><?php print $this->cp->curr_date($parser->last_update) ?></td>
            </tr>
            <tr>
                <td><?php print __('Next update') ?></td>
                <td><?php print $this->cp->get_next_update($parser->last_update, $parser->update_interval) ?></td>
            </tr>
            <tr>
                <td><?php print __('URLs count') ?></td>
                <td><?php
                    $count = $this->cp->get_urls_count(-1, $cid);
                    print $count;
                    ?>. <?php if ($count > 0) { ?> 
                        <a target="_blank" href="<?php print $url ?>&cid=<?php print $cid ?>&export=1">Export</a>.
                    <?php } ?></td>
            </tr>
            <tr>
                <td><?php print __('Last log') ?></td>
                <td><?php print $this->cp->get_last_log(0, $cid) ?></td>
            </tr>
        </tbody>        
    </table>

    <?php
    $cprules = $this->cp->get_cprules();
    $cprules->show_rules($options['rules'], false);
    ?>

    <?php $cprules->show_parser_rules($options['parser_rules'], false, $campaign->type); ?>
    
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
           
            foreach ($this->cp->campaign_modules as $module=>$camp_types) {
         
                if (!in_array($campaign->type, $camp_types)){
                    continue;
                }
                
                if (isset($options[$module])) {
                    $item = $options[$module];
                    $log_name = $this->cp->option_names[$module]['log'];

                    $last_update = $item['last_update'];
                    if ($last_update) {
                        $last_update = $this->cp->curr_date($last_update);
                    }
                    ?>
                    <tr>
                        <td colspan="2"> <h3><?php print $this->cp->option_names[$module]['title']; ?></h3></td>
                    </tr>              

                    <tr>
                        <td><?php print __('Interval') ?></td>
                        <td><?php print $this->cp->update_interval[$item['interval']] ?></td>
                    </tr>
                    <tr>
                        <td><?php print __('Last update') ?></td>
                        <td><?php print $last_update ?></td>
                    </tr>
                    <tr>
                        <td><?php print __('Status') ?></td>
                        <td><i class="sticn st-<?php print $item['status'] ?>"></i><?php print $this->cp->camp_state[$item['status']]; ?></td>
                    </tr>
                    <tr>
                        <td><?php print __('Last log') ?></td>
                        <td><?php
                            print $this->cp->get_last_log(0, $cid, $log_name);
                        ?></td>
                    </tr>

                    <?php
                } else {
                    ?>
                    <tr>
                        <td><?php print $this->cp->option_names[$module]['title']; ?></td><td>Not configured</td>
                    </tr>  
                    <?php
                }
            }
            ?>
        </tbody>        
    </table>

<?php } ?>