<h2><a href="<?php print $url ?>"><?php print __('Tor Parser') ?></a>. <?php print __('Agents') ?></h2>
<?php
print $tabs;
if (isset($filters_tabs['filters'])) {
    print implode("\n", array_values($filters_tabs['filters']));
}
?>


<?php
if (sizeof($agents) > 0) {
    ?>
    <?php print $pager ?>

    <table id="services" class="wp-list-table widefat striped table-view-list">
        <thead>
            <?php $this->sorted_head('id', 'id', $orderby, $order, $page_url) ?>                                
        <th><?php print __('Agent') ?></th> 
        <?php $this->sorted_head('date', 'Date', $orderby, $order, $page_url) ?>        
        <th><?php print __('IP') ?></th>
    </thead>
    <tbody>
        <?php
        foreach ($agents as $service) {
            ?>
            <tr> 
                <td><?php print $service->id ?></td>
                <td><?php print $service->user_agent ?>               
                <td><?php
                    if ($service->date) {
                        print $this->tp->curr_date($service->date);
                    }
                    ?></td>
                <td><?php print $service->ip ?></td>
            </tr> 
        <?php } ?>
    </tbody>
    </table>  
    <?php print $pager ?>


    <?php
} else {
    ?>
    <p><?php print __('The agents not found') ?></p>
    <?php
}
?>
<br />
<h2>Add Agents list</h2>
<form accept-charset="UTF-8" method="post" id="add_urls">
    <div class="cm-edit inline-edit-row">
        <fieldset>                            
            <textarea name="add_agents" style="width:100%" rows="10"></textarea>
            <span class="inline-edit">Each name with a separate line.</span>
            <br /><br />
            <?php wp_nonce_field('ml-nonce', 'ml-nonce'); ?>
            <input type="submit" name="options" id="edit-submit" value="<?php echo __('Submit Agents') ?>" class="button-secondary">  
        </fieldset>
    </div>
</form>