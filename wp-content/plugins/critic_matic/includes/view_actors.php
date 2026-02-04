<h2><a href="<?php print $url ?>"><?php print __('Critic matic') ?></a>. <?php print __('View actors') ?></h2>


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
    $mac = $this->cm->get_mac();

    // Проверка наличия данных
    ?>
    <h3>Actors meta (meta_movie_actor, data_actors_meta)</h3>
    <?php
    $actors = $mac->get_movie_actors($mid);
    print $this->cm->theme_table($actors);
    ?>
    <h3>Actors cache (cache_movie_actor_meta)</h3>
    Update actors <a target="_blank" href="/wp-content/plugins/critic_matic/cron/movie_actor_cache_cron.php?p=8ggD_23sdf_DSF&debug=1&mid=<?php print $mid ?>">cache</a>.
    <?php
    $cache_actors = $mac->get_cache_actors($mid);
     print $this->cm->theme_table($cache_actors);
     ?>
    <h3>Directors meta (meta_movie_director, data_actors_meta)</h3>
    <?php
    $mdirs = $this->cm->get_mdirs();
    $actors = $mdirs->get_movie_actors($mid);
    print $this->cm->theme_table($actors);
    ?>
    <h3>Directors cache (cache_movie_director_meta)</h3>
    Update directors <a target="_blank" href="/wp-content/plugins/critic_matic/cron/movie_actor_cache_cron.php?p=8ggD_23sdf_DSF&debug=1&mid=<?php print $mid ?>&type=1">cache</a>.
    <?php
    $cache_actors = $mdirs->get_cache_actors($mid);
    
    print $this->cm->theme_table($cache_actors);
    
    ?> 
<?php } ?>