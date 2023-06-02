<h2><a href="<?php print $url ?>"><?php print __('Critic matic') ?></a>. <?php print __('Edit post') ?></h2>


<?php if ($pid) { ?>
    <h3><?php print __('Post') ?>: [<?php print $pid ?>] <?php print $post->title ?></h3>
    <?php
}

print $tabs;

if ($pid) {
    $author = $this->cm->get_author($post->aid);
    $autor_type = $author->type;
    ?>
    <form accept-charset="UTF-8" method="post" id="author">

        <div class="cm-edit inline-edit-row">
            <fieldset>
                <input type="hidden" name="edit_parser" value="1">
                <input type="hidden" name="id" class="id" value="<?php print $pid ?>">

                <label>
                    <span class="title"><?php print __('Title') ?></span>
                    <span class="input-text-wrap"><input type="text" name="title" value="<?php print stripslashes(htmlspecialchars($post->title)) ?>"></span>
                </label>

                <label>
                    <span class="title"><?php print __('Date') ?></span>
                    <span class="input-text-wrap"><input type="text" name="date" value="<?php print $this->cf->curr_date($post->date) ?>"></span>
                </label>
                <?php if ($autor_type != 2): ?>

                    <label>
                        <span class="title"><?php print __('Link') ?></span>
                        <span class="input-text-wrap"><input type="text" name="link" value="<?php print $post->link ?>"></span>
                    </label>

                    <label class="inline-edit-author">
                        <span class="title"><?php print __('Author') ?></span>
                        <select name="author" class="authors">
                            <?php
                            if (sizeof($authors)) {
                                foreach ($authors as $author) {
                                    $selected = ($author->id == $post->aid) ? 'selected' : '';
                                    ?>

                                    <option value="<?php print $author->id ?>" <?php print $selected ?> >
                                        <?php print stripslashes($author->name) ?> (<?php print $this->cm->get_author_type($author->type) ?>)
                                    </option>                                
                                    <?php
                                }
                            }
                            ?>                       
                        </select>
                    </label>

                <?php endif; ?>
                <label class="inline-edit-status">                
                    <?php
                    $checked = '';
                    if ($post->status == 1) {
                        $checked = 'checked="checked"';
                    }
                    ?>
                    <input type="checkbox" name="status" value="1" <?php print $checked ?> >
                    <span class="checkbox-title"><?php print __('Publish') ?></span>
                </label>

                <label class="inline-edit-status">                
                    <?php
                    $checked = '';
                    if ($post->blur == 1) {
                        $checked = 'checked="checked"';
                    }
                    ?>
                    <input type="checkbox" name="blur" value="1" <?php print $checked ?> >
                    <span class="checkbox-title"><?php print __('Blur the content') ?></span>
                </label>
                <h3>
                    <?php print __('Content') ?>
                </h3>                
                <?php
                /*
                  <textarea name="content" style="width:100%" rows="10"><?php print stripslashes($post->content) ?></textarea>
                 */
                wp_editor(stripslashes($post->content), 'content', array('textarea_name' => 'content', 'media_buttons' => false, 'tinymce' => true));
                ?>
                <?php wp_nonce_field('critic-feeds-options', 'critic-feeds-nonce'); ?>
                <br />

                <?php
                $critic_meta = $this->cm->get_movies_data($pid);
                if (sizeof($critic_meta)) {
                    ?>
                    <h2><?php print __('Movies meta') ?></h2>

                    <label>
                        <span class="title"><?php print __('Top movie') ?></span>
                        <select name="top_movie" class="meta" disabled>
                            <?php
                            foreach ($critic_meta as $item) {
                                $selected = ($post->top_movie == $item->fid) ? 'selected' : '';
                                //$name = $post->top_movie;
                                //$name = $this->cm->get_top_movie($post->id);
                                $name = $this->get_movie_name_by_id($item->fid);
                                ?>
                                <option value="<?php print $item->fid ?>" <?php print $selected ?> >[<?php print $item->fid ?>] <?php print $name ?></option>                                
                            <?php } ?>                       
                        </select>
                    </label>                    

                    <?php
                    $state_items = array();
                    foreach ($critic_meta as $item) {
                        $state_items[$item->state][] = $item;
                    }

                    $states_oder = array(1, 2, 0);

                    foreach ($states_oder as $order) {
                        if (isset($state_items[$order])) {
                            ?>
                            <h3><?php print $this->cm->get_movie_state_name($order) ?> <?php print __('meta') ?></h3>                              


                            <table id="movies" class="wp-list-table widefat striped table-view-list">
                                <thead>
                                    <tr>
                                        <th><?php print __('Movie id') ?></th>
                                        <th><?php print __('Movie Name') ?></th>
                                        <th><?php print __('Type') ?></th>                 
                                        <th><?php print __('State') ?></th>  
                                        <th><?php print __('Rating') ?></th> 
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    foreach ($state_items[$order] as $item) {
                                        ?>
                                        <tr>
                                            <td><?php print $item->fid ?></td>
                                            <td><?php print $this->theme_movie_link($item->fid, $this->get_movie_name_by_id($item->fid)); ?></td>
                                            <td>
                                                <select name="meta_type_<?php print $item->fid ?>" class="meta">
                                                    <?php
                                                    foreach ($this->cm->post_category as $type => $name) {
                                                        $selected = ($item->type == $type) ? 'selected' : '';
                                                        ?>
                                                        <option value="<?php print $type ?>" <?php print $selected ?> ><?php print $name ?></option>                                
                                                    <?php } ?>                       
                                                </select>
                                            </td>
                                            <td>
                                                <select name="meta_state_<?php print $item->fid ?>" class="meta">
                                                    <?php
                                                    foreach ($this->cm->movie_state as $type => $name) {
                                                        $selected = ($item->state == $type) ? 'selected' : '';
                                                        ?>
                                                        <option value="<?php print $type ?>" <?php print $selected ?> ><?php print $name ?></option>                                
                                                    <?php } ?>  
                                                </select>
                                            </td>   
                                            <td><input type="text" name="meta_rating_<?php print $item->fid ?>" value="<?php print $item->rating ?>"></td>
                                        </tr> 
                                        <?php
                                    }
                                }
                                ?>
                            </tbody>
                        </table>  
                        <br />
                        <?php
                    }
                }
                ?>                        

                <h3><?php print __('Add a movie meta') ?></h3>

                <table id="movies" class="wp-list-table widefat striped table-view-list">
                    <thead>
                        <tr>
                            <th><?php print __('Movie Name') ?></th>
                            <th><?php print __('Type') ?></th>                 
                            <th><?php print __('State') ?></th>  
                            <th><?php print __('Rating') ?></th> 
                        </tr>
                    </thead>
                    <tbody>                      
                        <tr>    
                            <td>
                                <div class="autocomplite">
                                    <input type="text" name="meta_name_new" value="" class="search_text"  autocomplete="off" >
                                    <button class="clear button" disabled="">Clear</button>
                                    <input type="hidden" name="meta_id_new" value="" class="search_id">
                                    <div class="search_results"></div>
                                </div>
                            </td>
                            <td>
                                <select name="meta_type_new" class="meta">
                                    <?php
                                    // Defalult select - 1
                                    foreach ($this->cm->post_category as $type => $name) {
                                        $selected = (1 == $type) ? 'selected' : '';
                                        ?>
                                        <option value="<?php print $type ?>" <?php print $selected ?> ><?php print $name ?></option>                                
                                    <?php } ?>                       
                                </select>
                            </td>
                            <td>
                                <select name="meta_state_new" class="meta">
                                    <?php
                                    // Defalult select - 1
                                    foreach ($this->cm->movie_state as $type => $name) {
                                        $selected = (1 == $type) ? 'selected' : '';
                                        if ($type == 2) {
                                            continue;
                                        }
                                        ?>
                                        <option value="<?php print $type ?>" <?php print $selected ?> ><?php print $name ?></option>                                
                                    <?php } ?>  
                                </select>
                            </td>   
                            <td><input type="text" name="meta_rating_new" value="0"></td>
                        </tr>                 
                    </tbody>
                </table>  
                <br />                               
                <?php
                if ($autor_type == 0 || $autor_type == 2) {
                    $rating = $this->cm->get_post_rating($post->id);

                    if ($rating) {
                        ?>
                        <h3><?php print __('Edit rating') ?></h3>

                        <label>
                            <span class="title"><?php print __('Rating') ?></span>
                            <span class="input-text-wrap"><input type="text" name="rating_r" value="<?php print $rating['r'] ?>"></span>
                        </label>
                        <br />
                        <?php
                        $ca = $this->get_ca();
                        if ($ca) {
                            $ca->edit_post_rating($rating);
                        }
                    }
                }
                ?>

                <label>
                    <span class="title"><?php print __('Ip') ?></span>
                    <span class="input-text-wrap"><input type="text" name="rating_ip" value="<?php print $rating['ip'] ?>"></span>
                </label>
                <br />

                <input type="submit" name="options" id="edit-submit" value="<?php echo __('Save') ?>" class="button-primary">  

            </fieldset>

        </div>
    </form>
<?php } ?>