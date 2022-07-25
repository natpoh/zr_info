<h2><a href="<?php print $url ?>"><?php print __('Critic matic') ?></a>. <?php print __('Settings analytics') ?></h2>
<?php print $tabs; ?>

<form accept-charset="UTF-8" method="post" id="tag">
    <div class="cm-edit inline-edit-row">
        <fieldset> 
            <input type="hidden" name="posts" value="1">
            <h3>Verdict weight</h3>
            <label>
                <span class="title"><?php print __('Weight id') ?></span>
                <span class="input-text-wrap">
                    <input type="text" name="an_weightid" value="<?php print $ss['an_weightid'] ?>">
                </span>
            </label>
            <div class="desc">The weight id from analytics settings.</div>

            <?php
            $af = $this->cm->get_af();
            $priority = $af->race_weight_priority;
            if ($ss['an_weightid'] > 0) {
                $ma = $this->get_ma();
                $rule = $ma->get_race_rule_by_id($ss['an_weightid']);
                if ($rule) {
                    $priority = json_decode($rule->rule, true);
                }
            }


            $af->show_table_weight_priority($priority);
            ?>

            <?php wp_nonce_field('critic-feeds-options', 'critic-feeds-nonce'); ?>
            <br />
            <input type="submit" name="options" id="edit-submit" value="<?php echo __('Save') ?>" class="button-primary"> 

        </fieldset>
    </div>
</form>