<h2><a href="<?php print $url ?>"><?php print __('Critic matic') ?></a>. <?php print __('Add post') ?></h2>
<?php
print $tabs;
?>
<form accept-charset="UTF-8" method="post" id="author">

    <div class="cm-edit inline-edit-row">
        <fieldset>            
            <input type="hidden" name="add_post" class="add_post" value="1">
            <label>
                <span class="title"><?php print __('Title') ?></span>
                <span class="input-text-wrap"><input type="text" name="title" value=""></span>
            </label>
            <label>
                <span class="title"><?php print __('Date') ?></span>
                <span class="input-text-wrap"><input type="text" name="date" value="" placeholder="<?php print $this->cm->curr_date() ?>"></span>
            </label>

            <label>
                <span class="title"><?php print __('Link') ?></span>
                <span class="input-text-wrap"><input type="text" name="link" value=""></span>
            </label>

            <label class="inline-edit-status">                
                <?php
                $checked = 'checked="checked"';
                ?>
                <input type="checkbox" name="status" value="1" <?php print $checked ?> >
                <span class="checkbox-title"><?php print __('Publish') ?></span>
            </label>
            <label class="inline-edit-status">                
                <?php
                $checked = '';
                ?>
                <input type="checkbox" name="blur" value="1" <?php print $checked ?> >
                <span class="checkbox-title"><?php print __('Blur the content') ?></span>
            </label>
            <h3>
                <?php print __('Author') ?>
            </h3> 
            <div class="author-autocomplite autocomplite">
                <input type="text" placeholder="New author Name or Id" class="change_author autocomplite">
                <button class="clear button" disabled>Clear</button>
                <input type="hidden" name="author_id" class="author_id" value="">
                <div class="search_results"></div>
            </div>
            <h3>
                <?php print __('Content') ?>
            </h3>                
            <?php
            /*
              <textarea name="content" style="width:100%" rows="10"><?php print stripslashes($post->content) ?></textarea>
             */
            wp_editor('', 'content', array('textarea_name' => 'content', 'media_buttons' => false, 'tinymce' => true));
            ?>
            <?php wp_nonce_field('critic-feeds-options', 'critic-feeds-nonce'); ?>
            <br />
            <input type="submit" name="options" id="edit-submit" value="<?php echo __('Add post') ?>" class="button-primary">  

        </fieldset>

    </div>
</form>
