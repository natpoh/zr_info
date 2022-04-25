<h2><a href="<?php print $url ?>"><?php print __('Critic matic') ?></a>. <?php print __('Genres') ?></h2>

<?php print $tabs; ?>
<?php print $filters; ?>

<?php
if (sizeof($genres) > 0) {
    ?>

    <?php print $pager ?>
    <table id="genres" class="wp-list-table widefat striped table-view-list">
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
            foreach ($genres as $genre) {
                //Action links
                $actions = $ma->genre_actions(array('home'));
                $genre_url = $url . '&gid=' . $genre->id;
                $action_links = $this->get_filters($actions, $genre_url, $curr_tab = 'none', $front_slug = 'home', 'tab', 'inline', false);                
                ?>
                <tr>
                    <td><?php print $genre->id ?></td>                        
                    <td><a href="<?php print $genre_url ?>"><?php print $genre->name ?></a></td>
                    <td><?php print $genre->slug ?></td>
                    <td><?php print $ma->get_genre_status($genre->status) ?></td>                    
                    <td><?php print $genre->weight ?></td>
                    <td><?php print $action_links ?></td>                    
                </tr> 
            <?php } ?>
        </tbody>
    </table>    
    <?php print $pager ?>
    <?php
} else {
    ?>
    <p><?php print __('The genres not found') ?></p>
    <?php
}
?>