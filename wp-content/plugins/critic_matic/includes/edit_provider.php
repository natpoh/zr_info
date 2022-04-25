<h2><a href="<?php print $url ?>"><?php print __('Critic matic') ?></a>. <?php print __('Edit provider') ?></h2>

<?php if ($pid) { ?>
    <h3><?php print __('Provider') ?>: [<?php print $pid ?>] <?php print $provider->name ?></h3>
    <?php
}

print $tabs;

if ($pid) {
    ?>
    <form accept-charset="UTF-8" method="post" id="provider">

        <div class="cm-edit inline-edit-row">
            <fieldset>
                <input type="hidden" name="id" class="id" value="<?php print $pid ?>">

                <label>
                    <span class="title"><?php print __('Name') ?></span>
                    <span class="input-text-wrap"><input type="text" name="name" value="<?php print $provider->name ?>"></span>
                </label>

                <label>
                    <span class="title"><?php print __('Slug') ?></span>
                    <span class="input-text-wrap"><input type="text" name="slug" value="<?php print $provider->slug ?>"></span>
                </label>

                <label>
                    <span class="title"><?php print __('Pid') ?></span>
                    <span class="input-text-wrap"><input type="text" name="pid" value="<?php print $provider->pid ?>"></span>
                </label>
                <label>
                    <span class="title"><?php print __('Weight') ?></span>
                    <span class="input-text-wrap"><input type="text" name="weight" value="<?php print $provider->weight ?>"></span>
                </label>

                <label>
                    <span class="title"><?php print __('Image') ?></span>
                    <span class="input-text-wrap"><input type="text" name="image" value="<?php print $provider->image ?>"></span>
                </label>

                <label class="inline-edit-status">                
                    <?php
                    $checked = '';
                    if ($provider->status == 1) {
                        $checked = 'checked="checked"';
                    }
                    ?>
                    <input type="checkbox" name="status" value="1" <?php print $checked ?> >
                    <span class="checkbox-title"><?php print __('Publish') ?></span>
                </label>

                <label class="inline-edit-free">                
                    <?php
                    $checked = '';
                    if ($provider->free == 1) {
                        $checked = 'checked="checked"';
                    }
                    ?>
                    <input type="checkbox" name="free" value="1" <?php print $checked ?> >
                    <span class="checkbox-title"><?php print __('Free') ?></span>
                </label>

                <?php wp_nonce_field('critic-feeds-options', 'critic-feeds-nonce'); ?>
                <br />
                <input type="submit" name="options" id="edit-submit" value="<?php echo __('Save') ?>" class="button-primary">  

            </fieldset>
        </div>
    </form>
<?php } ?>