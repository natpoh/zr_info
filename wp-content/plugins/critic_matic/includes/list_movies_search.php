<h2><a href="<?php print $url ?>"><?php print __('Critic matic movies') ?></a>. <?php print __('Search') ?> <small>(<?php print $count ?>)</small></h2>
<form id="spform" method="get" action="<?php print $url ?>">
    <div class="sbar">
        <input type="search" name="s" id="sbar" size="15" value="<?php print $s ?>" placeholder="Search" autocomplete="off">
    </div>
    <input type="submit" id="submit" class="btn" value="find">        
    <input type="hidden" name="page" value="critic_matic_movies">
</form>
<?php
//print $filters;

if (sizeof($movies) > 0) {
    ?>
    <?php print $pager ?>
    <table id="movies" class="wp-list-table widefat striped table-view-list">
        <thead>
            <tr>
                <th class="mob-hide"><?php print __('id') ?></th>
                <th><?php print __('Release') ?></th>                 
                <th><?php print __('Title') ?></th>
                <th><?php print __('Type') ?></th>
                <th><?php print __('Add date') ?></th> 
                <th><?php print __('Meta') ?></th>
                <th><?php print __('Critics search: valid / other') ?></th>

            </tr>
        </thead>
        <tbody>
            <?php
            foreach ($movies as $search_post) {
                //id, rwt_id, title, release, type, year, weight() w 
                // Use user meta fields
                $post = $ma->get_post($search_post->id);
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
                        print sizeof((array)$critics_search['valid']).' / '.sizeof((array)$critics_search['other']);
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