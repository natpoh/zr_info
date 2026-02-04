<h2><a href="<?php print $url ?>"><?php print __('Critic matic') ?></a>. <?php print __('Author feeds') ?></h2>

<?php if ($aid) { ?>
    <h3><?php print __('Author') ?>: [<?php print $aid ?>] <?php print stripslashes($author->name) ?></h3>
    <?php
}

print $tabs;
print $filters;


if (sizeof($feeds) > 0) {
    ?>
    <?php print $pager ?>

    <table id="feeds" class="wp-list-table widefat striped table-view-list">
        <thead>
        <th class="mob-hide"><?php print __('id') ?></th>
            <?php $this->sorted_head('id', 'id', $orderby, $order, $page_url) ?>                                
            <?php $this->sorted_head('date', 'Date', $orderby, $order, $page_url) ?> 
            <?php $this->sorted_head('title', 'Title', $orderby, $order, $page_url) ?>            
            <?php $this->sorted_head('status', 'State', $orderby, $order, $page_url) ?>
            <?php $this->sorted_head('update_interval', 'Update interval', $orderby, $order, $page_url) ?>
            <?php $this->sorted_head('last_update', 'Last update', $orderby, $order, $page_url) ?>           
        <th><?php print __('Next update') ?></th>        
        <th><?php print __('Feed') ?></th> 
        <th><?php print __('Posts count') ?></th> 
        <th><?php print __('Valid') ?></th> 
        <th><?php print __('Last log') ?></th> 

    </thead>
    <tbody>
        <?php
        foreach ($feeds as $feed) {
            $options = unserialize($feed->options);
            $feed_invalid = isset($options['feed_invalid']) ? $options['feed_invalid'] : -1;
            if ($feed_invalid == 0) {
                $valid_data = array('text' => 'Valid', 'class' => 'green');
            } else if ($feed_invalid > 0) {
                $valid_data = array('text' => 'Invalid', 'class' => 'red');
            } else {
                $valid_data = array('text' => 'No info', 'class' => '');
            }
            ?>
            <tr> 
                <td><?php print $feed->id ?></td>
                <td><?php print $this->cf->curr_date($feed->date) ?></td>
                <td>
                    <?php print stripslashes($feed->title) ?><br />                    
                    <?php
                    $feed_actions = $this->cf->feed_actions();
                    $feed_url = $this->admin_page . $this->feeds_url . '&cid=' . $feed->id;
                    $action_links = $this->get_filters($feed_actions, $feed_url, $curr_tab = 'none', $front_slug = 'home', $name = 'tab');
                    print $action_links;
                    ?>
                </td>
                <td><?php print $this->cf->feed_state[$feed->status] ?></td>
                <td><?php print $this->cf->update_interval[$feed->update_interval] ?></td>
                <td><?php print $this->cf->curr_date($feed->last_update) ?></td>
                <td><?php print $this->cf->get_next_update($feed->last_update, $feed->update_interval) ?></td>
                <td>
                    <?php print 'RSS: ' . $feed->feed ?>
                    <?php if ($feed->site) print '<br />URL: ' . $feed->site ?>
                </td>                  
                <td><?php print $this->cm->get_feed_count($feed->id) ?></td>
                <td>
                    <span class="<?php print $valid_data['class'] ?>"><?php print $valid_data['text'] ?></span>
                    <?php
                    if ($feed_invalid > 0) {
                        print '<br /> count:&nbsp;' . $feed_invalid;
                    }
                    ?>
                </td>
                <td><?php print $this->cf->get_last_log($feed->id) ?></td>


            </tr> 
        <?php } ?>
    </tbody>
    </table>    
    <?php print $pager ?>
    <?php
} else {
    ?>
    <p><?php print __('The feeds not found') ?></p>
    <?php
}
?>