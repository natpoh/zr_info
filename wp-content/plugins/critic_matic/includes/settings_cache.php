<h2><a href="<?php print $url ?>"><?php print __('Critic matic') ?></a>. <?php print __('Settings cache') ?></h2>
<?php print $tabs; ?>

<div class="cm-edit">
    <?php
    if (class_exists('ThemeCache')) {
        $path = ThemeCache::$path;
        $pass = 'sdf23_ds-f23DS';
        ?>
        <h3><?php print $key ?></h3>
        <table id="overview" class="wp-list-table widefat striped table-view-list">
            <thead>
                <tr>                      
                    <th><?php print __('Name') ?></th>
                    <th><?php print __('Functions (name:time)') ?></th>
                    <th><?php print __('Count') ?></th>
                    <th><?php print __('Action') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ($path as $key => $value) {
                    $names = array();
                    foreach ($value['cache'] as $name => $time) {
                        $names[] = $name . ':' . $time;
                    }
                        ?>
                        <tr>                      
                            <td><b><?php print $key ?></b></td>
                            <td><?php print implode('; ', $names) ?></td>
                            <td><?php print ThemeCache::count_dir($value['folder']) ?></td>
                            <td><a href="/wp-content/plugins/critic_matic/cron/clear_cache.php?p=<?php print $pass ?>&mode=all&type=<?php print $key ?>" target="_blank">Clear cache</a></td>
                        </tr> 
                        <?php
 
                }
                ?>
            </tbody>
        </table>    
        <?php
    }
    ?>

</div>

