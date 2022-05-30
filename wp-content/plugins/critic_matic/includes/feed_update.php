<h2><a href="<?php print $url ?>"><?php print __('Critic feeds') ?></a>. <?php print __('Update') ?></h2>

<?php if ($cid) { ?>
    <h3><?php print __('Campaign') ?>: [<?php print $cid ?>] <?php print stripslashes($campaign->title) ?></h3>
<?php } ?>


<?php print $tabs; ?>

<?php
if ($count == 0) {
    print '<p>' . __('No new posts') . '</p>';
} else {
    print '<p>' . $count . __(' added new posts') . '</p>';
}
?>
