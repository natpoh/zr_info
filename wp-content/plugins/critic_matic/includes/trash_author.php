<h2><a href="<?php print $url ?>"><?php print __('Critic matic') ?></a>. <?php print __('Trash author') ?></h2>


<?php if ($aid) { ?>
    <h3><?php print __('Author') ?>: [<?php print $aid ?>] <?php print $author->name ?></h3>
    <?php
}

print $tabs;

if ($aid) {
    $status = $author->status;

    // Move to trash
    if ($status != 2) {
        $title = __('Move the author to trush');
        $status = 2;
        $button = __('Move to trash');
    } else {
        //Restore
        $title = __('Restore author from trash');
        $status = 1;
        $button = __('Restore from trash');
    }
    ?>
    <p><?php print $title ?></p>
    <form accept-charset="UTF-8" method="post" id="author">
        <div class="cm-edit inline-edit-row">
            <fieldset>
                <input type="hidden" name="id" value="<?php print $aid ?>">
                <input type="hidden" name="trash" value="1" >
                <input type="hidden" name="status" value="<?php print $status ?>" >
                <?php wp_nonce_field('critic-feeds-options', 'critic-feeds-nonce'); ?>
                <br />
                <input type="submit" name="options" id="edit-submit" value="<?php print $button ?>" class="button">  
            </fieldset>
        </div>
    </form>


<?php } ?>