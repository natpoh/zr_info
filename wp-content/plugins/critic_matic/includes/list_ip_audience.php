<h2><a href="<?php print $url ?>"><?php print __('Critic matic') ?></a>. <?php print __('Audience IP') ?></h2>

<?php
print $tabs;
print $filters;


if (sizeof($posts) > 0) {
    ?>
    <?php print $pager ?>
    <form accept-charset="UTF-8" method="post" >
        <input type="hidden" name="isips" value="1">
        <div class="bulk-actions-holder">
            <select name="bulkaction" class="bulk-actions">
                <option value=""><?php print __('Bulk actions') ?></option>
                <?php foreach ($this->bulk_actions_audience_ip as $act_key => $act_name) { ?>                    
                    <option value="<?php print $act_key ?>">
                        <?php print $act_name ?>
                    </option>                                
                <?php } ?>                       
            </select>
            <input type="submit" id="edit-submit" value="<?php echo __('Submit') ?>" class="button-primary">  
        </div>
        <table id="overview" class="wp-list-table widefat striped table-view-list">
            <thead>
                <tr>                    
                    <th></th>
                    <?php $this->sorted_head('id', 'id', $orderby, $order, $page_url) ?>                
                    <?php $this->sorted_head('ip', 'IP', $orderby, $order, $page_url) ?>                                         
                    <?php $this->sorted_head('type', 'Type', $orderby, $order, $page_url) ?>                                    
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ($posts as $item) {

                    $ip_type_name = $this->cm->ip_status[$item->type];
                    ?>
                    <tr>
                        <td><input type="checkbox" name="bulk-<?php print $item->id ?>"></td> 
                        <td><?php print $item->id ?></td>     
                        <td><?php print $item->ip ?></td>                                           
                        <td><?php print $ip_type_name ?></td>
                    </tr> 
                <?php } ?>
            </tbody>
        </table>    
    </form>
    <?php print $pager ?>
    <?php
} else {
    ?>
    <p><?php print __('The IPs not found') ?></p>
    <?php
}
?>