<h2><a href="<?php print $url ?>"><?php print __('Critic matic') ?></a>. <?php print __('View genre') ?></h2>

<?php if ($genre) { ?>
    <h3><?php print __('Genre') ?>: [<?php print $gid ?>] <?php print $genre->name ?></h3>
    <?php
} else {
    ?>
    <h3><?php print __('Genre not found') ?>: [<?php print $gid ?>]</h3>
    <?php
    return;
}

print $tabs;

if ($gid) {    
    ?>
    <br />
    <table class="wp-list-table widefat striped table-view-list">
        <thead>
            <tr>
                <th><?php print __('Name') ?></th>                
                <th><?php print __('Value') ?></th>    
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><?php print __('Name') ?></td>
                <td><?php print $genre->name ?></td>
            </tr>  
            <tr>
                <td><?php print __('Slug') ?></td>
                <td><?php print $genre->slug ?></td>
            </tr>
            <tr>
                <td><?php print __('Weight') ?></td>
                <td><?php print $genre->weight ?></td>
            </tr>
            <tr>
                <td><?php print __('Status') ?></td>
                <td><?php print $ma->get_genre_status($genre->status) ?></td>
            </tr>
        </tbody>        
    </table>

<?php } ?>