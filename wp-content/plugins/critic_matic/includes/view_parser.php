<h2><a href="<?php print $url ?>"><?php print __('Critic parsers') ?></a>. <?php print __('View') ?></h2>

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
                <td><?php print __('Author') ?></td>
                <td><?php print $author->name ?></td>
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
                    $count = $this->cp->get_urls_count(-1,$cid);
                    print $count;
                    ?>. <?php if ($count > 0) { ?> 
                    <a target="_blank" href="<?php print $url ?>&cid=<?php print $cid ?>&export=1">Export</a>.
                    <?php } ?></td>
            </tr>
            <tr>
                <td><?php print __('Last log') ?></td>
                <td><?php print $this->cp->get_last_log(0,$cid) ?></td>
            </tr>
        </tbody>        
    </table>

    <?php $this->cp->show_rules($options['rules'], false) ?>

    <?php $this->cp->show_parser_rules($options['parser_rules'], false, $campaign->type); ?>
<?php } ?>