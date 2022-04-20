<h2><a href="<?php print $url ?>"><?php print __('Critic matic') ?></a>. <?php print __('Providers') ?></h2>

<?php print $tabs; ?>
<?php print $filters; ?>
<?php print $filters_free; ?>

<?php
if (sizeof($providers) > 0) {
    ?>

    <?php print $pager ?>
    <table id="providers" class="wp-list-table widefat striped table-view-list">
        <thead>
            <tr>                
                <th><?php print __('Img') ?></th>
                <?php $this->sorted_head('id', 'id', $orderby, $order, $page_url) ?>                                
                <?php $this->sorted_head('pid', 'Pid', $orderby, $order, $page_url) ?>      
                <?php $this->sorted_head('name', 'Name', $orderby, $order, $page_url) ?>                                         
                <?php $this->sorted_head('slug', 'Slug', $orderby, $order, $page_url) ?> 
                <?php $this->sorted_head('status', 'Status', $orderby, $order, $page_url) ?> 
                <?php $this->sorted_head('weight', 'Weight', $orderby, $order, $page_url) ?> 
                <?php $this->sorted_head('free', 'Free', $orderby, $order, $page_url) ?> 
                <th><?php print __('Image') ?></th>
                <th><?php print __('Actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php
            foreach ($providers as $provider) {
                //Action links
                $actions = $ma->provider_actions(array('home'));
                $provider_url = $url . '&pid=' . $provider->id;
                $action_links = $this->get_filters($actions, $provider_url, $curr_tab = 'none', $front_slug = 'home', 'tab', 'inline', false);                
                ?>
                <tr>
                    
                    <td><img src="/wp-content/uploads/thumbs/providers_img/50x50/<?php print $provider->pid ?>.jpg" width="25" height="25"></td>                                            
                    <td><?php print $provider->id ?></td>                                            
                    <td><?php print $provider->pid ?></td>  
                    <td><a href="<?php print $provider_url ?>"><?php print $provider->name ?></a></td>
                    <td><?php print $provider->slug ?></td>
                    <td><?php print $ma->get_provider_status($provider->status) ?></td>                                        
                    <td><?php print $provider->weight ?></td>
                    <td><?php print $provider->free?'Free':'Pay' ?></td>   
                    <td><?php print $provider->image ?></td>
                    <td><?php print $action_links ?></td>                    
                </tr> 
            <?php } ?>
        </tbody>
    </table>    
    <?php print $pager ?>
    <?php
} else {
    ?>
    <p><?php print __('The providers not found') ?></p>
    <?php
}
?>