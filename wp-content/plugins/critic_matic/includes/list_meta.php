<h2><a href="<?php print $url ?>"><?php print __('Critic matic') ?></a>. <?php print __('Meta') ?></h2>

<?php print $tabs; ?>
<?php print $filters; ?>
<?php print $filters_type; ?>
<?php print $rating_filters; ?>

<?php if (sizeof($meta) > 0) { ?>
    <?php print $pager ?>
    <table id="meta" class="wp-list-table widefat striped table-view-list">
        <thead>
            <tr>                
                <th><?php print __('id') ?></th>
                <th><?php print __('Critic date') ?></th> 
                <th><?php print __('Critic title') ?></th> 
                <th><?php print __('Movie') ?></th>  
                <th><?php print __('Meta type') ?></th> 
                <th><?php print __('Meta State') ?></th>
                <th><?php print __('Rating') ?></th>
                <th><?php print __('Author') ?></th>
                <th><?php print __('Author type') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php
            foreach ($meta as $item) {
                ?>
                <tr>
                    <td><?php print $item->id ?></td>     
                    <td><?php print $this->cm->curr_date($item->post_date) ?></td>  
                    <td><?php print $this->theme_post_link($item->cid, $item->post_title) ?></td>     
                    <td><?php print $this->theme_movie_link($item->fid, $this->get_movie_name_by_id($item->fid)) ?></td>
                    <td><?php print $this->cm->get_post_category_name($item->type) ?></td>  
                    <td><?php print $this->cm->get_movie_state_name($item->state) ?></td>  
                    <td><?php print $item->rating ?></td>  
                    <td><?php print $this->theme_author_link($item->author_id, $item->author_name ); ?></td>     
                    <td><?php print $this->cm->get_author_type($item->author_type); ?></td>     
                </tr> 
            <?php } ?>
        </tbody>
    </table>    
    <?php print $pager ?>
    <?php
} else {
    ?>
    <p><?php print __('The meta not found') ?></p>
    <?php
}
?>