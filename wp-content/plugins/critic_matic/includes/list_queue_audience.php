<h2><a href="<?php print $url ?>"><?php print __('Critic matic') ?></a>. <?php print __('Audience') ?></h2>

<?php
print $tabs;
print $filters;


if (sizeof($posts) > 0) {
    ?>
    <?php print $pager ?>

    <table id="overview" class="wp-list-table widefat striped table-view-list">
        <thead>
            <tr>               
                <?php $this->sorted_head('id', 'id', $orderby, $order, $page_url) ?>                
                <?php $this->sorted_head('date', 'Date', $orderby, $order, $page_url) ?>                                         
                <th><?php print __('Title / Countent') ?></th>                
                <th><?php print __('Status') ?></th>
                <th><?php print __('Movie') ?></th>
                <th><?php print __('Rating') ?></th>
                <th><?php print __('Author') ?></th>  
                <th><?php print __('WP Uid') ?></th> 
                <th><?php print __('IP') ?></th>     
                <th><?php print __('IP list') ?></th>
                <th><?php print __('Unic id') ?></th> 
            </tr>
        </thead>
        <tbody>
            <?php
            foreach ($posts as $item) {
                //Post
                $author_name = $item->critic_name;
                // meta
                $rating_data = $this->cm->get_rating_array($item);
                //$email = isset($rating_data['em']) ? $rating_data['em'] : '';
                $ip = $item->ip;
                if ($ip) {
                    $ip_item = $this->cm->get_ip($ip);
                    $ip_type = 0;
                    if ($ip_item) {
                        $ip_type = $ip_item->type;
                    }
                    $ip_type_name = $this->cm->ip_status[$ip_type];
                }
                ?>
                <tr>                                   
                    <td class="mob-hide"><?php print $item->id ?></td>     
                    <td class="mob-hide"><?php print $this->cm->curr_date($item->date) ?></td>                                           
                    <td>
                        <b><?php print stripslashes($item->title) ?></b><br />        
                        <?php print $item->content ?>                       
                    </td> 
                    <td><?php
                        $status = $ca->get_queue_status($item->status);
                        if ($item->status == 0) {
                            $status = '<b class="red">' . $status . '</b>';
                        }
                        print $status;
                        ?>
                    </td>
                    <td><?php
                        if ($item->top_movie) {
                            $top_movie = $item->top_movie;
                            //$top_movie = $this->cm->get_top_movie($item->id);
                            print $this->theme_movie_link($top_movie, $this->get_movie_name_by_id($top_movie));
                        } else {
                            print __('None');
                        }
                        ?></td> 
                    <td><?php print $cfront->theme_rating($rating_data); ?></td>
                    <td><?php print $author_name ?></td>    
                    <td><?php print $item->wp_uid; ?></td> 
                    <td><?php print $ip ?></td> 
                    <td><?php print $ip_type_name ?></td>
                    <td><?php print $item->unic_id; ?></td>                     
                </tr> 
            <?php } ?>
        </tbody>
    </table>    

    <?php print $pager ?>
    <?php
} else {
    ?>
    <p><?php print __('Queue audience posts is clear') ?></p>
    <?php
}
?>