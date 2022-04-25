<h2><a href="<?php print $url ?>"><?php print __('Critic matic') ?></a>. <?php print __('Edit tag') ?></h2>

<?php if ($tid) { ?>
    <h3><?php print __('Tag') ?>: [<?php print $tid ?>] <?php print $tag->name ?></h3>
    <?php
}

print $tabs;

if ($tid) {
    $options = unserialize($tag->options);
    ?>
    <form accept-charset="UTF-8" method="post" id="tag">

        <div class="cm-edit inline-edit-row">
            <fieldset>
                <input type="hidden" name="id" class="id" value="<?php print $tid ?>">

                <label>
                    <span class="title"><?php print __('Name') ?></span>
                    <span class="input-text-wrap"><input type="text" name="name" value="<?php print $tag->name ?>"></span>
                </label>

                <label>
                    <span class="title"><?php print __('Slug') ?></span>
                    <span class="input-text-wrap"><input type="text" name="slug" value="<?php print $tag->slug ?>"></span>
                </label>

                <label class="inline-edit-status">                
                    <?php
                    $checked = '';
                    if ($tag->status == 1) {
                        $checked = 'checked="checked"';
                    }
                    ?>
                    <input type="checkbox" name="status" value="1" <?php print $checked ?> >
                    <span class="checkbox-title"><?php print __('Publish') ?></span>
                </label>

                <?php wp_nonce_field('critic-feeds-options', 'critic-feeds-nonce'); ?>
                <br />
                <input type="submit" name="options" id="edit-submit" value="<?php echo __('Save') ?>" class="button-primary">  

            </fieldset>
        </div>
    </form>
<?php } ?>