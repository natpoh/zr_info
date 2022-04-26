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
                    $country_names = '';
                    $countryes = $mlr->get_countries_by_lasnameid($item->id);

                    $race_total = array();
                    $rows_total = array();
                    $rows_race = array();
                    $total = 0;

                    if ($countryes) {
                        foreach ($countryes as $country => $value) {
                            $rows_total[] = $country . ': ' . $value;
                            $total += $value;
                            $races = $mlr->get_country_races($country, $value);
                            $race_str = array();
                            foreach ($races as $race => $count) {
                                $race_str[] = $race . ": " . $count;
                                $race_total[$race] += $count;
                            }
                            $rows_race[] = $country . ': ' . implode(', ', $race_str);
                        }
                        arsort($race_total);

                        $verdict = array_keys($race_total)[0];
                        $total_str = array();
                        foreach ($race_total as $race => $cnt) {
                            $total_str[] = $race . ': ' . $cnt;
                        }
                        $rows_total[] = 'Total: ' . $total;
                        $rows_race[] = 'Total: ' . implode(', ', $total_str);
                    }


                    $country_names = implode('<br />', $rows_total);
                    $race_names = implode('<br />', $rows_race);
                    ?>
                    <tr> 
                        <td><?php print $item->id ?></td>                             
                        <td><?php print $item->lastname ?></td>
                        <td><?php print $item->topcountryname ?></td>
                        <td><?php print $country_names; ?></td>    
                        <td><?php print $race_names; ?></td>  
                        <td><?php print $verdict; ?></td>  
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
    if ($all_countries){
        $not_found = array();
        foreach ($all_countries as $name) {
            if (!isset($population[$name])){
                $not_found[]= $name;
            }
        }
        if ($not_found){
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