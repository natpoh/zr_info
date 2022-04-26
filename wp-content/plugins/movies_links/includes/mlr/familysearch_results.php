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
                <?php $this->sorted_head('topcountryname', 'Top country', $orderby, $order, $page_url) ?>                                    
            <th><?php print __('Countries') ?></th>             
            <th><?php print __('Races') ?></th>             
            <th><?php print __('Verdict') ?></th>
            </thead>
            <tbody>
                <?php
                foreach ($posts as $item) {

                    $verdict_arr = $mlr->calculate_fs_verdict($item->id);
                   

                    $country_names = implode('<br />', $verdict_arr['rows_total']);
                    $race_names = implode('<br />', $verdict_arr['rows_race']);
                    $verdict = $verdict_arr['verdict'];
                    ?>
                    <tr> 
                        <td><?php print $item->id ?></td>                             
                        <td><?php print $item->lastname ?></td>
                        <td><?php print $item->topcountryname ?></td>
                        <td><?php print $country_names ?></td>    
                        <td><?php print $race_names ?></td>  
                        <td><?php print $verdict;  ?></td>  
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
    }
} else {
    ?>
    <p><?php print __('The urls not found') ?></p>
    <?php
}
?>