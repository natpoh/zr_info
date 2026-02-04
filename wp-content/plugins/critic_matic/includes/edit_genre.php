<h2><a href="<?php print $url ?>"><?php print __('Critic matic') ?></a>. <?php print __('Edit genre') ?></h2>

<?php if ($gid) { ?>
    <h3><?php print __('Genre') ?>: [<?php print $gid ?>] <?php print $genre->name ?></h3>
    <?php
}

print $tabs;

if ($gid) {
    ?>
    <form accept-charset="UTF-8" method="post" id="genre">

        <div class="cm-edit inline-edit-row">
            <fieldset>
                <input type="hidden" name="id" class="id" value="<?php print $gid ?>">

                <label>
                    <span class="title"><?php print __('Name') ?></span>
                    <span class="input-text-wrap"><input type="text" name="name" value="<?php print $genre->name ?>"></span>
                </label>

                <label>
                    <span class="title"><?php print __('Slug') ?></span>
                    <span class="input-text-wrap"><input type="text" name="slug" value="<?php print $genre->slug ?>"></span>
                </label>

                <label>
                    <span class="title"><?php print __('Weight') ?></span>
                    <span class="input-text-wrap"><input type="text" name="weight" value="<?php print $genre->weight ?>"></span>
                </label>

                <label class="inline-edit-status">                
                    <?php
                    $checked = '';
                    if ($genre->status == 1) {
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