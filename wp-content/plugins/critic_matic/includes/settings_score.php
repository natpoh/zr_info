<h2><a href="<?php print $url ?>"><?php print __('Critic matic') ?></a>. <?php print __('Settings score') ?></h2>
<?php print $tabs; ?>

<form accept-charset="UTF-8" method="post" id="tag">
    <div class="cm-edit">
        <fieldset>    

            <h2><?php print __('Score limits') ?></h2>
            <p class="description"><?php print __('The minimum score to enable an action.') ?></p>
            <table class="form-table" role="presentation">
                <tbody>
                    <tr class="wrap">
                        <th><label for=""><?php print __('Upload avatar') ?></label></th>
                        <td><input type="text" name="score_avatar" value="<?php print $ss['score_avatar'] ?>">                             
                        </td>
                    </tr>                   
                    <tr class="wrap">
                        <th><label for=""><?php print __('Upload filter image') ?></label></th>
                        <td><input type="text" name="score_filter_image" value="<?php print $ss['score_filter_image'] ?>">                             
                        </td>
                    </tr>  
                </tbody>
            </table>

            <?php wp_nonce_field('critic-feeds-options', 'critic-feeds-nonce'); ?>
            <br />
            <input type="submit" name="options" id="edit-submit" value="<?php echo __('Save') ?>" class="button-primary">  

        </fieldset>
    </div>
</form>