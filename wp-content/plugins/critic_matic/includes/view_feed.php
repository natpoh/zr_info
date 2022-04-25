<h2><a href="<?php print $url ?>"><?php print __('Critic feeds') ?></a>. <?php print __('View') ?></h2>

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
    $feed_invalid = isset($options['feed_invalid']) ? $options['feed_invalid'] : -1;
    if ($feed_invalid == 0) {
        $valid_data = array('text' => 'Valid', 'class' => 'green');
    } else if ($feed_invalid > 0) {
        $valid_data = array('text' => 'Invalid', 'class' => 'red');
    } else {
        $valid_data = array('text' => 'No info', 'class' => '');
    }
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
                <td><?php print __('Feed') ?></td>
                <td><?php print $campaign->feed ?></td>
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
                <td><?php print $feed_state[$campaign->status] ?></td>
            </tr>
            <tr>
                <td>
                    <?php
                    $checked = 'false';
                    $rss_date = isset($options['rss_date']) ? $options['rss_date'] : $def_options['options']['rss_date'];
                    if ($rss_date) {
                        $checked = 'true';
                    }
                    print __('Get the post date from RSS');
                    ?>
                </td>
                <td><?php print $checked ?></td>
            </tr>
            <tr>
                <td><?php print __('Update interval') ?></td>
                <td><?php print $update_interval[$campaign->update_interval] ?></td>
            </tr>
            <tr>
                <td><?php print __('Last update') ?></td>
                <td><?php print $this->cf->curr_date($feed->last_update) ?></td>
            </tr>
            <tr>
                <td><?php print __('Next update') ?></td>
                <td><?php print $this->cf->get_next_update($feed->last_update, $feed->update_interval) ?></td>
            </tr>
            <tr>
                <td><?php print __('Posts count') ?></td>
                <td><?php print $this->cm->get_feed_count($cid) ?></td>
            </tr>
            <tr>
                <td><?php print __('Valid') ?></td>
                <td><span class="<?php print $valid_data['class'] ?>"><?php print $valid_data['text'] ?></span>
                    <?php
                    if ($feed_invalid > 0) {
                        print ' count:&nbsp;' . $feed_invalid;
                    }
                    ?></td>
            </tr>
            <tr>
                <td><?php print __('Last log') ?></td>
                <td><?php print $this->cf->get_last_log($cid) ?></td>
            </tr>
        </tbody>        
    </table>
    
    <?php $this->cf->show_rules($options['rules'],false)?>
<?php } ?>