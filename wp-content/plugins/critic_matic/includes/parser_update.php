<h2><a href="<?php print $url ?>"><?php print __('Critic parser') ?></a>. <?php print __('Force update') ?></h2>

<?php if ($cid) { ?>
    <h3><?php print __('Campaign') ?>: [<?php print $cid ?>] <?php print $campaign->title ?></h3>
<?php } ?>


<?php print $tabs; ?>

<?php
if ($count == 0) {
    print '<p>' . __('No new Posts') . '</p>';
} else if ($count == -1) {
    print '<p>' . __('Parser status is not Active') . '</p>';
} else {    
    print '<p>' . $count . __(' added new Posts') . '</p>';
}

if ($count_urls == 0) {
    print '<p>' . __('No new URLs') . '</p>';
}else if ($count_urls == -1) {
    print '<p>' . __('Find urls status is not Active') . '</p>';
} else {
    print '<p>' . $count_urls . __(' added new URLs') . '</p>';
}
?>
