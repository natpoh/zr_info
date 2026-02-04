<h2><a href="<?php print $url ?>"><?php print __('Movies Links') ?></a>. <?php print __('Custom results') ?></h2>

<?php if ($cid) { ?>
    <h3><?php print __('Campaign') ?>: [<?php print $cid ?>] <?php print $campaign->title ?></h3>
    <?php
}

print $tabs;
/* print $filters;
  print $filters_arhive_type;
  print $filters_parser_type;
  print $filters_links_type; */


if (sizeof($posts) > 0) {
    ?>
    <?php print $pager ?>  
    <form accept-charset="UTF-8" method="post" >
        <table id="parsers" class="wp-list-table widefat striped table-view-list">
            <thead>
                <?php $this->sorted_head('id', 'id', $orderby, $order, $page_url) ?>
                <?php $this->sorted_head('lastname', 'Lastname', $orderby, $order, $page_url) ?>                                     
            <th><?php print __('Countries') ?></th>   
            <th><?php print __('Rank races') ?></th>  
            <th><?php print __('Races') ?></th>  
            <th><?php print __('Races Simpson') ?></th>  
            <th><?php print __('Meta') ?></th>
            </thead>
            <tbody>
                <?php
                foreach ($posts as $item) {

                    $verdict_arr = $mlr->calculate_fs_verdict($item->id);


                    $country_names = implode('<br />', $verdict_arr['rows_total']);
                    $race_names = implode('<br />', $verdict_arr['rows_race']);
                    $verdict = $verdict_arr['verdict'];

                    // Simpson
                    $verdict_simpson_arr = $mlr->calculate_fs_verdict($item->id, true);
                    $country_simpson_names = implode('<br />', $verdict_simpson_arr['rows_total']);
                    $race_simpson_names = implode('<br />', $verdict_simpson_arr['rows_race']);
                    $verdict_simpson = $verdict_simpson_arr['verdict'];

                    $verdict_meta_item = $mlr->get_verdict_by_lastname($item->lastname);
                    $verdict_meta = '';
                    if ($verdict_meta_item) {
                        $verdict_meta = $mlr->get_verdict_name($verdict_meta_item->verdict);
                    }

                    // Verdict rating country
                    $top_verdict = $mlr->calculate_top_verdict($item);
                    $rank_names = implode('<br />', $top_verdict['rows_race']);
                    $verdict_rank = $top_verdict['verdict'];
                    $top_race_name = $top_verdict['top_race_name'];
                    ?>
                    <tr> 
                        <td><?php print $item->id ?></td>                             
                        <td><?php print $item->lastname ?></td>
                        <td>
                            <p>Rank: <?php print $item->topcountryrank ?></p>
                            <p>Top: <?php print $item->topcountryname ?></p>                            
                            <p><?php print $country_simpson_names ?></p>
                        </td>    
                        <td>
                            <p><?php print $rank_names ?></p>                            
                            <p><?php print "Verdict: <b>" . $verdict_rank . "</b>"; ?></p>
                            <?php if ($top_race_name): ?>
                            <p><?php print "Custom verdict: <b>" . $top_race_name . "</b>"; ?></p>
                            <?php endif; ?>
                        </td>  
                        
                        
                        <td>
                            <p><?php print $race_names ?></p>
                            <p><?php print "Verdict: <b>" . $verdict . "</b>"; ?></p>
                        </td>                          
                        <td>
                            <p><?php print $race_simpson_names; ?></p>
                            <p><?php print "Verdict: <b>" . $verdict_simpson . "</b>"; ?></p>
                        </td>  

                        <td><?php print $verdict_meta; ?></td>
                    </tr> 
                <?php } ?>
            </tbody>
        </table>  
    </form>
    <?php print $pager ?>
    <?php
    // Show invalid countries
    $all_countries = $mlr->get_all_countries();
    $population = $mlr->get_population();
    if ($all_countries) {
        $not_found = array();
        foreach ($all_countries as $name) {
            if (!isset($population[$name])) {
                if (!isset($mlr->country_names[$name])) {
                    $not_found[] = $name;
                }
            }
        }
        if ($not_found) {
            print '<h3>Not found countries</h3>';
            print implode('<br />', $not_found);
        }
        // Calculate Simpson
        if ($_GET['update_simpson']) {
            $simpson = $mlr->calculate_simpson($population);
            print_r($simpson);
        }
    }
} else {
    ?>
    <p><?php print __('The urls not found') ?></p>
    <?php
}
?>