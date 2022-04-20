<h2><a href="<?php print $url ?>"><?php print __('Critic matic') ?></a>. <?php print __('Settings parser') ?></h2>
<?php print $tabs; ?>

<form accept-charset="UTF-8" method="post" id="tag">
    <div class="cm-edit">
        <fieldset>    

            <h2><?php print __('Curl settings') ?></h2>

            <div class="label">
                <?php print __('User agent') ?>
            </div>
            <input type="text" name="parser_user_agent" class="title" value="<?php print $ss['parser_user_agent'] ?>" style="width:90%">
            <br /><br />
            <div class="label">
                <?php print __('Cookie file') ?>
            </div>
            <?php
            $cookie_text = '';
            if (file_exists($ss['parser_cookie_path'])) {
                $cookie_text = file_get_contents($ss['parser_cookie_path']);
            }
            ?>
            <textarea name="parser_cookie_text" style="width:90%" rows="10"><?php print $cookie_text ?></textarea>

            <h3>Google Service</h3>

            <div class="label">
                <?php print __('Goolge Developer Key') ?>
            </div>
            <input type="text" name="parser_gdk" class="title" value="<?php print $ss['parser_gdk'] ?>" style="width:90%">
            <br /><br />

            <div class="label">
                <?php print __('Goolge Application Name') ?>
            </div>
            <input type="text" name="parser_gapp" class="title" value="<?php print $ss['parser_gapp'] ?>" style="width:90%">
            <br />

            <?php wp_nonce_field('critic-feeds-options', 'critic-feeds-nonce'); ?>
            <br />
            <input type="submit" name="options" id="edit-submit" value="<?php echo __('Save') ?>" class="button-primary">  

        </fieldset>
    </div>
</form>