<h2><a href="<?php print $url ?>"><?php print __('Tor Parser') ?></a>. <?php print __('Services') ?></h2>
<?php
print $tabs;
if (isset($filters_tabs['filters'])) {
    print implode("\n", array_values($filters_tabs['filters']));
}
?>


<?php
if (sizeof($services) > 0) {
    ?>
    <?php print $pager ?>
    <form accept-charset="UTF-8" method="post" >
        <div class="bulk-actions-holder">
            <select name="bulkaction" class="bulk-actions">
                <option value=""><?php print __('Bulk actions') ?></option>
                <?php foreach ($this->bulk_actions as $act_key => $act_name) { ?>                    
                    <option value="<?php print $act_key ?>">
                        <?php print $act_name ?>
                    </option>                                
                <?php } ?>                       
            </select>
            <input type="submit" id="edit-submit" value="<?php echo __('Submit') ?>" class="button-primary">  
        </div>
        <table id="services" class="wp-list-table widefat striped table-view-list">
            <thead>
            <td class="manage-column column-cb check-column" ><input type="checkbox" id="cb-select-all-1"></td>
            <?php $this->sorted_head('id', 'id', $orderby, $order, $page_url) ?>                                
            <?php $this->sorted_head('name', 'Name', $orderby, $order, $page_url) ?>  
            <?php $this->sorted_head('last_upd', 'Last update', $orderby, $order, $page_url) ?> 
            <?php $this->sorted_head('last_reboot', 'Last reboot', $orderby, $order, $page_url) ?>            
            <?php $this->sorted_head('status', 'State', $orderby, $order, $page_url) ?>                           
            <th><?php print __('IP') ?></th> 
            <th><?php print __('Agent') ?></th>         
            <th><?php print __('URL') ?></th> 
            </thead>
            <tbody>
                <?php
                foreach ($services as $service) {
                    ?>
                    <tr> 
                        <th  class="check-column" ><input type="checkbox" name="bulk-<?php print $service->id ?>"></th>
                        <td><?php print $service->id ?></td>
                        <td><?php print $service->name ?>
                            <?php
                            $actions = $this->service_actions();
                            $parser_url = $url . '&cid=' . $service->id;
                            $action_links = $this->get_filters($actions, $parser_url, $curr_tab = 'none', $front_slug = 'home', $name = 'tab', '', false);
                            print $action_links;
                            ?>
                        </td>
                        <td><?php print $this->ml->format_time($service->last_upd) ?></td>
                        <td><?php
                            if ($service->last_reboot) {
                                print $this->ml->format_time($service->last_reboot);
                            }
                            ?></td>                                
                        <td class="nowrap"><i class="sticn st-<?php print $service->status ?>"></i><?php print $this->tp->service_status[$service->status] ?></td>
                        <td><?php print $this->tp->get_ip_name_by_id($service->ip) ?></td>
                        <td><?php print $this->tp->get_agent_name_by_id($service->agent) ?></td>
                        <td><?php print $service->url ?></td>
                    </tr> 
                <?php } ?>
            </tbody>
        </table>  
    </form>
    <?php print $pager ?>
    <?php
} else {
    ?>
    <p><?php print __('The services not found') ?></p>
    <?php
}
?>