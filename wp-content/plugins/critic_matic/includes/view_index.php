<h2><a href="<?php print $url ?>"><?php print __('Critic matic') ?></a>. <?php print __('View index') ?></h2>


<?php if ($movie) { ?>    
    <h3><?php print __('Movie') ?>: [<?php print $mid ?>] <?php print $movie->title ?></h3>
    <p> Server: 
        <a href="https://info.zgreviews.com/wp-admin/admin.php?page=critic_matic_movies&mid=<?php print $mid ?>">Info</a> | 
        <a href="https://zgreviews.com/wp-admin/admin.php?page=critic_matic_movies&mid=<?php print $mid ?>">Zr</a>
    </p>
    <?php
} else {
    ?>
    <h3><?php print __('Movie not found') ?>: [<?php print $mid ?>]</h3>
    <?php
    return;
}

print $tabs;

if ($mid) {

    $cfront = $this->get_cfront();
    $data = $this->cs->get_movie_by_id($mid);
    ?>
    <h3>Movie index data</h3>
    <table class="wp-list-table widefat striped table-view-list">
        <thead>
            <tr>
                <th><?php print __('Name') ?></th>                
                <th><?php print __('Value') ?></th>    
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data as $key => $value) { ?>
                <tr>
                    <td><?php print $key ?></td>
                    <td><?php print $value ?></td>
                </tr>
            <?php } ?>           
        </tbody>        
    </table>

<?php } ?>