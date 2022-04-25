<h2><a href="<?php print $url ?>"><?php print __('Critic feeds') ?></a>. <?php print __('Add a new author') ?></h2>
<?php print $tabs; ?>
<?php ?>
<form accept-charset="UTF-8" method="post" id="campaign">
    <div class="cm-edit inline-edit-row">
        <fieldset>
            <label>
                <span class="title"><?php print __('Name') ?></span>
                <span class="input-text-wrap"><input type="text" name="name" class="name" value=""></span>
            </label>

            <label class="inline-edit-from">
                <span class="title"><?php print __('From') ?></span>
                <select name="type" class="type">
                    <?php
                    if (sizeof($author_type)) {
                        foreach ($author_type as $type => $title) {
                            ?>
                            <option value="<?php print $type ?>"><?php print $title ?></option>                                
                            <?php
                        }
                    }
                    ?>                       
                </select>
            </label>

            <span class="title inline-edit-categories-label"><?php print __('Tags') ?></span>
            <input type="hidden" name="post_category[]" value="0">
            <ul class="cat-checklist category-checklist">
                <?php
                if (sizeof($tags)) {
                    foreach ($tags as $tag) {
                        ?>
                        <li id="category-<?php print $tag->id ?>" class="popular-category">
                            <label class="selectit"><input value="<?php print $tag->id ?>" type="checkbox" name="post_category[]" id="in-category-<?php print $tag->id ?>"> <?php print $tag->name ?></label>
                        </li>
                        <?php
                    }
                }
                ?>
            </ul>

            <br />
            
            <label class="inline-edit-active">                
                <?php
                $checked = 'checked="checked"';
                ?>
                <input type="checkbox" name="status" value="1" <?php print $checked ?> >
                <span class="checkbox-title"><?php print __('Publish') ?></span>
            </label>

            <label class="inline-edit-active">  
                <input type="checkbox" name="autoblur" value="1" >
                <span class="checkbox-title"><?php print __('Autoblur') ?></span>
            </label>

            <?php wp_nonce_field('critic-feeds-options', 'critic-feeds-nonce'); ?>
            <br />
            <input type="submit" name="options" id="edit-submit" value="<?php echo __('Submit') ?>" class="button-primary">  
        </fieldset>
    </div>
</form>
