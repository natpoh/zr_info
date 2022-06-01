<h2><a href="<?php print $url ?>"><?php print __('Critic parsers') ?></a>. <?php print __('Preview parser') ?></h2>

<?php if ($cid) { ?>
    <h3><?php print __('Campaign') ?>: [<?php print $cid ?>] <?php print stripslashes($campaign->title) ?></h3>
    <?php
}

print $tabs;

if ($cid) {
    if (sizeof($preview)) {
        foreach ($preview as $id => $item) {
          
            ?>            
            <table class="wp-list-table widefat striped table-view-list">
                <thead>
                    <tr>
                        <th><?php print __('Name') ?></th>
                        <th><?php print __('Value') ?></th>                                                          
                    </tr>
                </thead>
                <tbody>                    
                    <tr>
                        <td><?php print __('Title') ?></td>
                        <td><?php print $item['title'] ?></td>                 
                    </tr> 
                    <tr>
                        <td><?php print __('URL') ?></td>
                        <td><?php print $item['link'] ?></td>                 
                    </tr> 
                    <tr>
                        <td><?php print __('Date') ?></td>
                        <td><?php
                            print "Date int: " . $item['date'] . '<br />';
                            print "Date: " . gmdate('d.m.Y H:i:s', $item['date']);
                            ?></td>                 
                    </tr> 
                    <tr>
                        <td><?php print __('Descripion') ?></td>
                        <td><?php print $item['desc'] ?></td>                 
                    </tr> 

                    <?php if ($options['use_rules']) { ?>
                        <tr>
                            <td><?php print __('Rules url') ?></td>
                            <td><?php
                                $check = $item['check'];
                                if ($check) {
                                    foreach ($check['data'] as $key => $value) {
                                        print 'Result: <b>' . $this->cp->rules_actions[$value] . '</b>. Rule id: ' . $key;
                                        break;
                                    }
                                } else {
                                    print "<b>No changes</b>. ";
                                    print $this->cp->rules_actions[$status] . ".";
                                }
                                ?>
                            </td>                 
                        </tr>                         
                    <?php } ?>
                </tbody>
            </table>  
            <br />

            <?php
        }
    }
}
?>