<h2><a href="<?php print $url ?>"><?php print __('Critic matic') ?></a>. <?php print __('Edit author') ?></h2>

<?php if ($aid) { ?>
    <h3><?php print __('Author') ?>: [<?php print $aid ?>] <?php print stripslashes($author->name) ?></h3>
    <?php
}

print $tabs;

/*
  `id` int(11) unsigned NOT NULL auto_increment,
  `status` int(11) NOT NULL DEFAULT '1',
  `type` int(11) NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL default '',
  `options` text default NULL,
 */
if ($aid) {
    $options = unserialize($author->options);
    ?>
    <form accept-charset="UTF-8" method="post" id="author">

        <div class="cm-edit inline-edit-row">
            <fieldset>

                <input type="hidden" name="id" class="id" value="<?php print $aid ?>">

                <label>
                    <span class="title"><?php print __('Name') ?></span>
                    <span class="input-text-wrap"><input type="text" name="name" value="<?php print $author->name ?>"></span>
                </label>


                <?php if ($author->type == 1) { 
                    // Images field for pro critics only
                    ?>
                    <label>
                        <?php
                        $image = '';
                        if (isset($options['image'])) {
                            $image = $options['image'];
                        }
                        ?>
                        <span class="title"><?php print __('Image') ?></span>
                        <span class="input-text-wrap"><input type="text" name="image" value="<?php print $image ?>"></span>
                    </label>
                <?php } ?>


                <label>
                    <?php
                    $secret = '';
                    if (isset($options['secret'])) {
                        $secret = $options['secret'];
                    }
                    ?>
                    <span class="title"><?php print __('Secret key') ?></span>
                    <span class="input-text-wrap"><input type="text" name="secret" value="<?php print $secret ?>"></span>
                </label>

                <label class="inline-edit-interval">
                    <span class="title"><?php print __('From') ?></span>
                    <select name="type" class="type">
                        <?php
                        foreach ($author_type as $key => $name) {
                            $selected = ($key == $author->type) ? 'selected' : '';
                            ?>
                            <option value="<?php print $key ?>" <?php print $selected ?> ><?php print $name ?></option>                                
                            <?php
                        }
                        ?>                          
                    </select>                                  
                </label>

                <label class="inline-edit-interval">
                    <span class="title"><?php print __('Show type') ?></span>
                    <select name="show_type" class="show_type">
                        <?php
                        foreach ($this->cm->author_show_type as $key => $name) {
                            $selected = ($key == $author->show_type) ? 'selected' : '';
                            ?>
                            <option value="<?php print $key ?>" <?php print $selected ?> ><?php print $name ?></option>                                
                            <?php
                        }
                        ?>                          
                    </select>                                  
                </label>

                <span class="title inline-edit-categories-label"><?php print __('Tags') ?></span>
                <input type="hidden" name="post_category[]" value="0">
                <ul class="cat-checklist category-checklist">
                    <?php
                    $author_tags = $this->cm->get_author_tags($aid,-1,false);
                    $tag_arr = array();
                    if (sizeof($author_tags)) {
                        foreach ($author_tags as $tag) {
                            $tag_arr[] = $tag->id;
                        }
                    }

                    if (sizeof($tags)) {
                        foreach ($tags as $tag) {
                            $checked = '';
                            if (in_array($tag->id, $tag_arr)) {
                                $checked = 'checked="checked"';
                            }
                            ?>
                            <li id="category-<?php print $tag->id ?>" class="popular-category">
                                <label class="selectit"><input value="<?php print $tag->id ?>" <?php print $checked ?> type="checkbox" name="post_category[]" id="in-category-<?php print $tag->id ?>"> <?php print $tag->name ?></label>
                            </li>
                            <?php
                        }
                    }
                    ?>
                </ul>

                <br />
                <label class="inline-edit-status">                
                    <?php
                    $checked = '';
                    if ($author->status == 1) {
                        $checked = 'checked="checked"';
                    }
                    ?>
                    <input type="checkbox" name="status" value="1" <?php print $checked ?> >
                    <span class="checkbox-title"><?php print __('Publish') ?></span>
                </label>

                <label class="inline-edit-autoblur">  
                    <?php
                    $checked = '';
                    if (isset($options['autoblur']) && $options['autoblur'] == 1) {
                        $checked = 'checked="checked"';
                    }
                    ?>
                    <input type="checkbox" name="autoblur" value="1" <?php print $checked ?> >
                    <span class="checkbox-title"><?php print __('Autoblur') ?></span>
                </label>

                <?php wp_nonce_field('critic-feeds-options', 'critic-feeds-nonce'); ?>
                <br />
                <input type="submit" name="options" id="edit-submit" value="<?php echo __('Save') ?>" class="button-primary">  

            </fieldset>

        </div>
    </form>
<?php } ?>