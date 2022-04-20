<h2><a href="<?php print $url ?>"><?php print __('Critic matic movies') ?></a> <small>(<?php print $count ?>)</small></h2>
<form id="spform" method="get" action="<?php print $url ?>">
    <div class="sbar">
        <input type="search" name="s" id="sbar" size="15" value="<?php print $s ?>" placeholder="Search" autocomplete="off">
    </div>
    <input type="submit" id="submit" class="btn" value="find">        
    <input type="hidden" name="page" value="critic_matic_movies">
</form>
<?php
print $filters;

if (sizeof($movies) > 0) {
    ?>
    <?php print $pager ?>
    <table id="movies" class="wp-list-table widefat striped table-view-list">
        <thead>
            <tr>
                <?php $this->sorted_head('id', 'id', $orderby, $order, $page_url) ?>                                
                <?php $this->sorted_head('year', 'Release', $orderby, $order, $page_url) ?> 
                <?php $this->sorted_head('title', 'Title', $orderby, $order, $page_url) ?>
                <?php $this->sorted_head('type', 'Type', $orderby, $order, $page_url) ?>
                <?php $this->sorted_head('add_time', 'Add date', $orderby, $order, $page_url) ?>
                <th><?php print __('Meta') ?></th>
                <th><?php print __('Critics search: valid / other') ?></th>

            </tr>
        </thead>
        <tbody>
            <?php
            foreach ($movies as $post) {

                // Use user meta fields
                $type_user = array();
                $meta_ids = $this->cm->get_critics_meta_by_movie($post->id);
                $critics_search = $this->cs->search_critics($post);
                ?>
                <tr>
                    <td class="mob-hide"><?php print $post->id ?></td>     
                    <td><?php print $post->release ?></td>
                    <td><?php
                        $movie_link = $this->theme_movie_link($post->id, $post->title);
                        print $movie_link;
                        ?>
                    </td>                    
                    <td><?php print $post->type ?></td>
                    <td><?php print $this->cm->curr_date($post->add_time) ?></td>
                    <td><?php
                        print sizeof($meta_ids);
                        ?></td>
                    <td><?php
                        // Critic search        
                        print sizeof((array) $critics_search['valid']) . ' / ' . sizeof((array) $critics_search['other']);
                        ?>
                    </td>     
                </tr> 
            <?php } ?>
        </tbody>
    </table>    
    <?php print $pager ?>
    <?php
} else {
    ?>
    <p><?php print __('The movies not found') ?></p>
    <?php
}
?>