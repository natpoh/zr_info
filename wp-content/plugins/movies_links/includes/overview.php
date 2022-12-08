<h2><a href="<?php print $url ?>"><?php print __('Movies Links') ?></a>. <?php print __('Overview') ?></h2>
<?php print $tabs; ?>
<?php print $filters ?>
<?php print $type_filters ?>
<?php
if (sizeof($campaigns) > 0) {
    ?>
    <?php print $pager ?>

    <form accept-charset="UTF-8" method="post" >
        <div class="bulk-actions-holder">
            <select name="bulkaction" class="bulk-actions">
                <option value=""><?php print __('Bulk actions') ?></option>
                <?php foreach ($this->bulk_actions_parser as $act_key => $act_name) { ?>                    
                    <option value="<?php print $act_key ?>">
                        <?php print $act_name ?>
                    </option>                                
                <?php } ?>                       
            </select>
            <input type="submit" id="edit-submit" value="<?php echo __('Submit') ?>" class="button-primary">  
        </div>
        <table id="feeds" class="wp-list-table widefat striped table-view-list">
            <thead>
            <td class="manage-column column-cb check-column" ><input type="checkbox" id="cb-select-all-1"></td>
            <?php $this->sorted_head('id', 'id', $orderby, $order, $page_url) ?>                                
            <?php $this->sorted_head('date', 'Date', $orderby, $order, $page_url) ?> 
            <?php $this->sorted_head('title', 'Title', $orderby, $order, $page_url) ?>
            <?php $this->sorted_head('type', 'Type', $orderby, $order, $page_url) ?>  
            <?php $this->sorted_head('status', 'State', $orderby, $order, $page_url) ?>  
            <?php foreach ($this->mp->campaign_modules as $module) { ?>
                <th><?php print $mtitle = $this->option_names[$module]['title']; ?></th>
            <?php } ?>
            <?php /* ?><th><?php print __('Last log') ?></th> <?php */ ?>
            </thead>
            <tbody>
                <?php
                foreach ($campaigns as $parser) {
                    $options = unserialize($parser->options);
                    ?>
                    <tr> 
                        <th  class="check-column" ><input type="checkbox" name="bulk-<?php print $parser->id ?>"></th>
                        <td><?php print $parser->id ?></td>
                        <td><?php print $this->mp->curr_date($parser->date) ?></td>
                        <td>
                            <?php print $parser->title ?><br />                    
                            <?php
                            $parser_actions = $this->parser_actions($parser);
                            $parser_url = $parser_url . '&cid=' . $parser->id;
                            $action_links = $this->get_filters($parser_actions, $parser_url, $curr_tab = 'none', $front_slug = 'home', $name = 'tab', '', false);
                            print $action_links;
                            ?>
                        </td>
                        <td><?php print $this->parser_types[$parser->type] ?></td>
                        <td class="nowrap"><i class="sticn st-<?php print $parser->status ?>"></i><?php print $this->camp_state[$parser->status]['title'] ?></td>

                        <?php
                        foreach ($this->mp->campaign_modules as $module) {
                            ?><td class="nowrap"><?php
                            $item = isset($options[$module]) ? $options[$module] : array();
                            if (isset($item['status'])) {
                                ?>
                                    <i class="sticn st-<?php print $item['status'] ?>"></i><?php print $this->camp_state[$item['status']]['title']; ?>
                                    <?php
                                } else {
                                    print '-';
                                }

                                if ($module == 'arhive' || $module == 'cron_urls') {
                                    $interval = $this->update_interval[$item['interval']];
                                    if ($module == 'cron_urls') {
                                        $item = $options['service_urls'];
                                    }
                                    // Get parser type
                                    print '<br /> — ' . $this->parse_mode[$item['webdrivers']];
                                    
                                    if ($module == 'arhive') {
                                        $count = $item['num'];
                                        
                                        print '<br /> — ' . $count . ' / ' . $interval;
                                    } else {
                                        print '<br /> — ' . $interval;
                                    }
                                }
                                ?></td><?php
                        }
                        ?>

                        <?php /* ?><td><?php print $this->get_last_log(0, $parser->id) ?></td><?php */ ?>


                    </tr> 
                <?php } ?>
            </tbody>
        </table>    
        <?php print $pager ?>
        <?php
    } else {
        ?>
        <p><?php print __('The parsers not found') ?></p>
        <?php
    }
    ?>