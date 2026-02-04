<h2><a href="<?php print $url ?>"><?php print __('Critic matic') ?></a>. <?php print __('Settings search') ?></h2>
<?php print $tabs; ?>

<form accept-charset="UTF-8" method="post" id="tag">
    <div class="cm-edit">
        <fieldset>    

            <h2><?php print __('Main limits') ?></h2>
            <table class="form-table" role="presentation">
                <tbody>
                    <tr class="wrap">
                        <th><label for=""><?php print __('Search limit') ?></label></th>
                        <td><input type="text" name="limit" value="<?php print $ss['limit'] ?>"> 
                            <?php
                            $range = $this->cs->get_settings_range('limit');
                            print 'Min: ' . $range['min'] . '; Default: ' . $range['def'] . '; Max: ' . $range['max']
                            ?>                        
                            <p class="description"><?php print __('Maximum number of posts to search.') ?></p></td>
                    </tr>

                    <tr class="wrap">
                        <th><label for=""><?php print __('Min valid score') ?></label></th>
                        <td><input type="text" name="min_valid_point" value="<?php print $ss['min_valid_point'] ?>"> 
                            <?php
                            $range = $this->cs->get_settings_range('min_valid_point');
                            print 'Min: ' . $range['min'] . '; Default: ' . $range['def'] . '; Max: ' . $range['max']
                            ?> 
                            <p class="description"><?php print __('The minimum number of points for a review to be considered valid.') ?></p></td>                
                    </tr>

                    <tr class="wrap">
                        <th><label for=""><?php print __('Name multipler') ?></label></th>
                        <td><input type="text" name="name_words_multipler" value="<?php print $ss['name_words_multipler'] ?>">
                            <?php
                            $range = $this->cs->get_settings_range('name_words_multipler');
                            print 'Min: ' . $range['min'] . '; Default: ' . $range['def'] . '; Max: ' . $range['max']
                            ?> 
                            <p class="description"><?php print __('Rating increase factor based on the number of words in the title.') ?></p></td>                
                    </tr>

                </tbody>
            </table>

            <h2><?php print __('Title score') ?></h2>
            <p class="description"><?php print __('Rating assigned if the keyword is present in the post title.') ?></p>
            <table class="form-table" role="presentation">
                <tbody>
                    <tr class="wrap">
                        <th><label for=""><?php print __('Name') ?></label></th>
                        <td><input type="text" name="name_point_title" value="<?php print $ss['name_point_title'] ?>">
                            <?php
                            $range = $this->cs->get_settings_range('name_point_title');
                            print 'Min: ' . $range['min'] . '; Default: ' . $range['def'] . '; Max: ' . $range['max']
                            ?> 
                        </td>
                    </tr>

                    <tr class="wrap">
                        <th><label for=""><?php print __('Name equals') ?></label></th>
                        <td><input type="text" name="name_equals" value="<?php print $ss['name_equals'] ?>">
                            <?php
                            $range = $this->cs->get_settings_range('name_equals');
                            print 'Min: ' . $range['min'] . '; Default: ' . $range['def'] . '; Max: ' . $range['max']
                            ?> 
                        </td>
                    </tr>

                    <tr class="wrap">
                        <th><label for=""><?php print __('Release') ?></label></th>
                        <td><input type="text" name="release_point_title" value="<?php print $ss['release_point_title'] ?>">
                            <?php
                            $range = $this->cs->get_settings_range('release_point_title');
                            print 'Min: ' . $range['min'] . '; Default: ' . $range['def'] . '; Max: ' . $range['max']
                            ?> 
                        </td>
                    </tr>
                    <tr class="wrap">
                        <th><label for=""><?php print __('Quote title') ?></label></th>
                        <td><input type="text" name="quote_title" value="<?php print $ss['quote_title'] ?>">
                            <?php
                            $range = $this->cs->get_settings_range('quote_title');
                            print 'Min: ' . $range['min'] . '; Default: ' . $range['def'] . '; Max: ' . $range['max']
                            ?> 
                        </td>
                    </tr>
                    <tr class="wrap">
                        <th><label for=""><?php print __('Need Release before') ?></label></th>
                        <td><input type="text" name="need_release" value="<?php print $ss['need_release'] ?>">
                            <?php
                            $range = $this->cs->get_settings_range('need_release');
                            print 'Min: ' . $range['min'] . '; Default: ' . $range['def'] . '; Max: ' . $range['max']
                            ?> 
                        </td>
                    </tr>

                </tbody>
            </table>

            <h2><?php print __('Content score') ?></h2>
            <p class="description"><?php print __('Rating assigned if the keyword is present in the post content.') ?></p>
            <table class="form-table" role="presentation">
                <tbody>

                    <tr class="wrap">
                        <th><label for=""><?php print __('Name') ?></label></th>
                        <td><input type="text" name="name_point" value="<?php print $ss['name_point'] ?>">
                            <?php
                            $range = $this->cs->get_settings_range('name_point');
                            print 'Min: ' . $range['min'] . '; Default: ' . $range['def'] . '; Max: ' . $range['max']
                            ?> 
                        </td>
                    </tr>

                    <tr class="wrap">
                        <th><label for=""><?php print __('Quote content') ?></label></th>
                        <td><input type="text" name="quote_content" value="<?php print $ss['quote_content'] ?>">
                            <?php
                            $range = $this->cs->get_settings_range('quote_content');
                            print 'Min: ' . $range['min'] . '; Default: ' . $range['def'] . '; Max: ' . $range['max']
                            ?> 
                        </td>
                    </tr>

                    <tr class="wrap">
                        <th><label for=""><?php print __('Release') ?></label></th>
                        <td><input type="text" name="release_point" value="<?php print $ss['release_point'] ?>">
                            <?php
                            $range = $this->cs->get_settings_range('release_point');
                            print 'Min: ' . $range['min'] . '; Default: ' . $range['def'] . '; Max: ' . $range['max']
                            ?> 
                        </td>
                    </tr>

                    <tr class="wrap">
                        <th><label for=""><?php print __('Runtime') ?></label></th>
                        <td><input type="text" name="runtime_point" value="<?php print $ss['runtime_point'] ?>">
                            <?php
                            $range = $this->cs->get_settings_range('runtime_point');
                            print 'Min: ' . $range['min'] . '; Default: ' . $range['def'] . '; Max: ' . $range['max']
                            ?> 
                        </td>
                    </tr>                    

                    <tr class="wrap">
                        <th><label for=""><?php print __('Director') ?></label></th>
                        <td><input type="text" name="director_point" value="<?php print $ss['director_point'] ?>">
                            <?php
                            $range = $this->cs->get_settings_range('director_point');
                            print 'Min: ' . $range['min'] . '; Default: ' . $range['def'] . '; Max: ' . $range['max']
                            ?> 
                        </td>
                    </tr>

                    <tr class="wrap">
                        <th><label for=""><?php print __('Cast') ?></label></th>
                        <td><input type="text" name="cast_point" value="<?php print $ss['cast_point'] ?>">
                            <?php
                            $range = $this->cs->get_settings_range('cast_point');
                            print 'Min: ' . $range['min'] . '; Default: ' . $range['def'] . '; Max: ' . $range['max']
                            ?> 
                        </td>
                    </tr>

                </tbody>
            </table>
            <h3>Game tags</h3>
            <table class="form-table" role="presentation">
                <tbody>

                    <tr class="wrap">
                        <th><label for=""><?php print __('Game tag point') ?></label></th>
                        <td><input type="text" name="game_tag_point" value="<?php print $ss['game_tag_point'] ?>">
                            <?php
                            $range = $this->cs->get_settings_range('game_tag_point');
                            print 'Min: ' . $range['min'] . '; Default: ' . $range['def'] . '; Max: ' . $range['max']
                            ?> 
                        </td>
                    </tr>
                </tbody>
            </table>
            <textarea name="games_tags" style="width: 90%;" rows="5"><?php print stripslashes($ss['games_tags']) ?></textarea>
            <div class="desc">Tags separated by a comma.</div>                        
            <?php wp_nonce_field('critic-feeds-options', 'critic-feeds-nonce'); ?>
            <br />
            <input type="submit" name="options" id="edit-submit" value="<?php echo __('Save') ?>" class="button-primary">  

        </fieldset>
    </div>
</form>