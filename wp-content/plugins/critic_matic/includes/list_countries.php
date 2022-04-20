<h2><a href="<?php print $url ?>"><?php print __('Critic matic') ?></a>. <?php print __('Countries') ?></h2>

<?php print $tabs; ?>
<?php print $filters; ?>

<?php
if (sizeof($countries) > 0) {
    ?>

    <?php print $pager ?>
    <table id="countries" class="wp-list-table widefat striped table-view-list">
        <thead>
            <tr>
                <?php $this->sorted_head('id', 'id', $orderby, $order, $page_url) ?>                
                <?php $this->sorted_head('name', 'Name', $orderby, $order, $page_url) ?>                                         
                <?php $this->sorted_head('slug', 'Slug', $orderby, $order, $page_url) ?> 
                <?php $this->sorted_head('status', 'Status', $orderby, $order, $page_url) ?> 
                <?php $this->sorted_head('weight', 'Weight', $orderby, $order, $page_url) ?> 
                <th><?php print __('Actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php
            foreach ($countries as $country) {
                //Action links
                $actions = $ma->country_actions(array('home'));
                $country_url = $url . '&cid=' . $country->id;
                $action_links = $this->get_filters($actions, $country_url, $curr_tab = 'none', $front_slug = 'home', 'tab', 'inline', false);                
                ?>
                <tr>
                    <td><?php print $country->id ?></td>                        
                    <td><a href="<?php print $country_url ?>"><?php print $country->name ?></a></td>
                    <td><?php print $country->slug ?></td>
                    <td><?php print $ma->get_country_status($country->status) ?></td>                    
                    <td><?php print $country->weight ?></td>
                    <td><?php print $action_links ?></td>                    
                </tr> 
            <?php } ?>
        </tbody>
    </table>    
    <?php print $pager ?>
    <?php
} else {
    ?>
    <p><?php print __('The countries not found') ?></p>
    <?php
}
?>