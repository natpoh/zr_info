<h2><a href="<?php print $url ?>"><?php print __('Critic matic') ?></a>. <?php print __('Settings audience') ?></h2>
<?php print $tabs; ?>

<form accept-charset="UTF-8" method="post" id="tag">
    <div class="cm-edit inline-edit-row">
        <fieldset> 
            <input type="hidden" name="posts" value="1">
            <h3>Post type</h3>

            <?php
            /*
              1 => 'Proper Review',
              2 => 'Contains Mention',
              3 => 'Related Article'
             */
            foreach ($this->cm->post_category as $key => $value) {
                if ($key == 0) {
                    continue;
                }
                ?>
                <label class="inline-edit-status">                
                    <?php
                    $checked = '';
                    $type_name = 'posts_type_' . $key;
                    $type = isset($ss[$type_name]) ? $ss[$type_name] : 0;
                    if ($type == 1) {
                        $checked = 'checked="checked"';
                    }
                    ?>
                    <input type="checkbox" name="<?php print $type_name ?>" value="1" <?php print $checked ?> >
                    <span class="checkbox-title"><?php print $value ?></span>
                </label>

                <?php
            }
            ?>
            <div class="desc">Allowed post types for home page posting.</div>
            <br />


            <label>
                <span class="title"><?php print __('Min rating') ?></span>
                <span class="input-text-wrap">
                    <input type="text" name="posts_rating" value="<?php print $ss['posts_rating'] ?>">
                </span>
            </label>
            <div class="desc">Minimum rating for displaying posts.</div>

            <?php wp_nonce_field('critic-feeds-options', 'critic-feeds-nonce'); ?>
            <br />
            <input type="submit" name="options" id="edit-submit" value="<?php echo __('Save') ?>" class="button-primary">  

        </fieldset>
    </div>
</form>