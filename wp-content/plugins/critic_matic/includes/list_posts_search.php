<h2><a href="<?php print $url ?>"><?php print __('Critic matic') ?></a>. <?php print __('Search critics') ?></h2>

<form id="spform" method="get" action="<?php print $url ?>">
    <div class="sbar">
        <input type="search" name="s" id="sbar" size="15" value="<?php print $s ?>" placeholder="Search" autocomplete="off">
    </div>
    <input type="submit" id="submit" class="btn" value="find">        
    <input type="hidden" name="page" value="critic_matic">
</form>
<?php
//print $filters_author_type;

?>
<?php
if (sizeof($posts) > 0) {
    ?>
    <?php print $pager ?>
    <table id="overview" class="wp-list-table widefat striped table-view-list">
        <thead>
            <tr>
                <?php $this->sorted_head('id', 'id', $orderby, $order, $page_url) ?>                
                <?php $this->sorted_head('date', 'Date', $orderby, $order, $page_url) ?>                                         
                <?php $this->sorted_head('title', 'Title / Link', $orderby, $order, $page_url) ?>                
                <th><?php print __('Content') ?></th>                 
                <th><?php print __('Author') ?></th>                                       
                <th><?php print __('From') ?></th>                
                <th><?php print __('Tags') ?></th>
                <th><?php print __('Top movie') ?></th>
                <th><?php print __('Movies meta') ?></th> 
                <th><?php print __('Status') ?></th>
                <th><?php print __('Type') ?></th>   
                <th><?php print __('In index') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php
            /*
              (
              [id] => 4566
              [date_add] => 1630308122
              [w] => 2700
              [t] => <b>Terminator</b> Genisys Needs More J.K. Simmons, Less Recycling
              [c] =>  ...  humans and the machines (<b>terminators</b>) tasked with killing Sarah  ...  (2009). That fourth <b>Terminator</b> film (following <b>Terminator</b> [1984], <b>Terminator</b> 2: Judgment Day [ ... 1991] and <b>Terminator</b> 3: Rise ...
              )
             */

            foreach ($posts as $search_item) {
                $item = $this->cm->get_post($search_item->id);
                //Post
                //$post_type = $this->cm->get_post_status($item->status);
                //Author
                $author = $this->cm->get_author($item->aid);
                $author_name = $author->name;
                $author_type = $this->cm->get_author_type($author->type);
                //Author link
                $author_link = $this->theme_author_link($item->aid, $author_name);

                //Tags
                $tags = $this->cm->get_author_tags($item->aid);
                $tag_arr = $this->theme_author_tags($tags);


                //Post links         
                $post_actions = $this->cm->post_actions();
                $post_url = $this->admin_page . $this->parrent_slug . '&pid=' . $item->id;
                $post_links = $this->get_filters($post_actions, $post_url, $curr_tab = 'none', $front_slug = 'home', $name = 'tab');

                //Movies
                $movies = $this->cm->get_movies_data($item->id);
                //$movies_search_arr = $this->cs->search_movies($item->title, $item->content);
                //$movies_search = $movies_search_arr['movies'];
                ?>
                <tr>
                    <td class="mob-hide"><?php print $item->id ?></td>     
                    <td class="mob-hide"><?php print $this->cm->curr_date($item->date) ?></td>                                           
                    <td>
                        <?php print $search_item->t ?><br />
                        <a href="<?php print $item->link ?>" target="_blank" title="<?php print $item->link ?>"><?php print substr($item->link, 0, 70) ?></a><br />
                        <?php print $post_links ?>
                    </td>
                    <td><?php print $search_item->c ?></td>                    
                    <td><?php print $author_link ?></td> 
                    <td><?php print $author_type ?></td>                    
                    <td><?php print implode(', ', $tag_arr) ?></td>  
                    <td><?php
                        if ($item->top_movie) {
                            $top_movie = $item->top_movie;
                            //$top_movie = $this->cm->get_top_movie($item->id);
                            print $this->theme_movie_link($top_movie, $this->get_movie_name_by_id($top_movie));
                        } else {
                            print __('None');
                        }
                        ?></td> 
                    <td><?php
                        print sizeof($movies);
                        ?>
                    </td> 
                    <td><?php print $this->cm->get_post_status($item->status) ?></td>
                    <td><?php
                        $item_type = $this->cm->get_post_type($item->type);

                        print $item_type;
                   
                        ?></td>
                    <td><?php print $this->cs->critic_in_index($item->id) ? 'Index' : 'Not'; ?></td>
                </tr> 
    <?php } ?>
        </tbody>
    </table>    
    <?php print $pager ?>
    <?php
} else {
    ?>
    <p><?php print __('The critic posts not found') ?></p>
    <?php
}
?>