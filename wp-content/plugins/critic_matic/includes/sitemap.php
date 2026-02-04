<h2>
    <?php echo __('Sitemap'); ?>
</h2>

<?php
if (!class_exists('CriticSitemap')) {
    require_once( CRITIC_MATIC_PLUGIN_DIR . 'CriticSitemap.php' );
}

$epvSitemap = new CriticSitemap($this->cm);

if ($_GET['delete_options'] == 1) {
    $epvSitemap->deleteOptions();
}

if (isset($_POST) && sizeof($_POST) > 0):
    $epvSitemap->options_submit($_POST);

endif;

/* TODO
 * Получаем список необходимых карт
 * проверяем их наличие по факту
 * оторажаем наличие в виде фомры, с возможностью пересоздания
 */

//Получаем данные карты
// Статус главной карты
$list_status = $epvSitemap->getMapListStatus();
$sitemapLink = $epvSitemap->getSitemapLink();
$mapdata = $epvSitemap->getMapData();
?>
<p><?php print $list_status ?>: <a target="_blank" href="<?php print $sitemapLink ?>"><?php print $sitemapLink ?></a></p>

<form accept-charset="UTF-8" method="post" id="epv-sitemap-options">  
    <table id="movies" class="wp-list-table widefat striped table-view-list">
        <thead>
            <tr>   
                <td class="manage-column column-cb check-column" ><input type="checkbox" id="cb-select-all-1"></td>                     
                <th><?php print __('Name') ?></th>
                <th><?php print __('Time') ?></th>
                <th><?php print __('Count') ?></th>
                <th><?php print __('Link') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php
// Список лет
            foreach ($epvSitemap->types as $type) {
                $epvSitemap->checkUpdateYear($type);
                // Список карт из опций
                if (isset($mapdata['maps'][$type]) && sizeof($mapdata['maps'][$type]) > 0) {
                    ?>
                    <tr><th colspan="5"><b><?php print ucfirst($type) ?></b></th></tr>
                    <?php
                    $maps = $mapdata['maps'][$type];

                    $hotmap = $maps[EPV_SITEMAP_HOTMAP];
                    unset($maps[EPV_SITEMAP_HOTMAP]);
                    ?>

                    <?php
                    krsort($maps);
                    $maplist = array();
                    $maplist[EPV_SITEMAP_HOTMAP] = $hotmap;
                    foreach ($maps as $key => $item) {
                        $maplist[$key] = $item;
                    }

                    foreach ($maplist as $key => $item) {
                        $time = $item['time'] ? date('d.m.Y H:i:s', $item['time']) : '';
                        $href = $epvSitemap->getMapLinkByYear($key, $type);
                        $link = $time ? '<a href="' . $href . '" target="_blank">' . $href . '</a>' : '';
                        ?>
                        <tr>
                            <th class="check-column" ><input type="checkbox" name="<?php print $type ?>-<?php print $key ?>" id="<?php print $key ?>" value="1"></th>
                            <td><?php print $key ?></td>
                            <td><?php print $time ?></td>                            
                            <td><?php print $item['count'] ?></td>
                            <td><?php print $link ?></td>
                        </tr> 
                    <?php } ?>

                    <?php
                }
            }
            ?>
        </tbody>
    </table> 
    <br />
    <input type="submit" name="send" id="edit-submit" value="<?php print __('Rebuild') ?>" class="button-primary">           
</form>