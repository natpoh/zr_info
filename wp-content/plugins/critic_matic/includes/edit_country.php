<h2><a href="<?php print $url ?>"><?php print __('Critic matic') ?></a>. <?php print __('Edit country') ?></h2>

<?php if ($cid) { ?>
    <h3><?php print __('Country') ?>: [<?php print $cid ?>] <?php print $country->name ?></h3>
    <?php
}

print $tabs;

if ($cid) {
    ?>
    <form accept-charset="UTF-8" method="post" id="country">

        <div class="cm-edit inline-edit-row">
            <fieldset>
                <input type="hidden" name="id" class="id" value="<?php print $cid ?>">

                <label>
                    <span class="title"><?php print __('Name') ?></span>
                    <span class="input-text-wrap"><input type="text" name="name" value="<?php print $country->name ?>"></span>
                </label>

                <label>
                    <span class="title"><?php print __('Slug') ?></span>
                    <span class="input-text-wrap"><input type="text" name="slug" value="<?php print $country->slug ?>"></span>
                </label>

                <label>
                    <span class="title"><?php print __('Weight') ?></span>
                    <span class="input-text-wrap"><input type="text" name="weight" value="<?php print $country->weight ?>"></span>
                </label>

                <label class="inline-edit-status">                
                    <?php
                    $checked = '';
                    if ($country->status == 1) {
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