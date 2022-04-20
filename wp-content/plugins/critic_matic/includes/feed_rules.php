<h2><a href="<?php print $url ?>"><?php print __('Critic feeds') ?></a>. <?php print __('Test feed rules') ?></h2>

<?php if ($cid) { ?>
    <h3><?php print __('Campaign') ?>: [<?php print $cid ?>] <?php print $campaign->title ?></h3>
    <?php
}

print $tabs;

if ($cid) {
    $options = unserialize($campaign->options);
    $rt = isset($options['rt']) ? $options['rt'] : array();
    ?>
    <form accept-charset="UTF-8" method="post" id="campaign">

        <h3><?php print __('Example feed content') ?></h3>
        <div class="cm-edit inline-edit-row">
            <fieldset>
                <input type="hidden" name="id" class="id" value="<?php print $campaign->id ?>">
                <label>
                    <span class="title"><?php print __('Title') ?></span>
                    <span class="input-text-wrap"><input type="text" name="t" class="title" value="<?php print isset($rt['t']) ? base64_decode($rt['t']) : ''  ?>" placeholder="Enter the test title"></span>
                </label>

                <label>
                    <span class="title"><?php print __('URL') ?></span>
                    <span class="input-text-wrap"><input type="text" name="u" value="<?php print isset($rt['u']) ? base64_decode($rt['u']) : ''  ?>" placeholder="Enter the test URL"></span>
                </label>

                <label>
                    <span class="title"><?php print __('Tags') ?></span>
                    <span class="input-text-wrap"><input type="text" name="c" value="<?php print isset($rt['c']) ? base64_decode($rt['c']) : ''  ?>" placeholder="Enter the test tags"></span>
                </label>

                <label>
                    <?php print __('Example content') ?>
                </label>
                <textarea name="d" style="width:100%" rows="10"><?php print isset($rt['d']) ? trim(stripslashes(base64_decode($rt['d']))) : ''  ?></textarea>
                <?php wp_nonce_field('critic-feeds-options', 'critic-feeds-nonce'); ?>
                <br />
                <input type="submit" name="options" id="edit-submit" value="<?php echo __('Submit') ?>" class="button-primary"> 
                <br />

                <h2><?php print __('Rules result') ?></h2>
                <?php
                if ($check) {
                    foreach ($check as $key => $value) {
                        print 'Result: <b>' . $this->cf->rules_actions[$value] . '</b>. Rule id: ' . $key;
                        break;
                    }
                } else {
                    print "<b>No changes</b>.";
                }

                $this->cf->show_rules($rules, false, $check);

                if ($use_global_rules) {
                    ?><h2><?php print __('Global rules result') ?></h2>
                    <?php
                    if ($global_check) {
                        foreach ($global_check as $key => $value) {
                            print 'Result: <b>' . $this->cf->rules_actions[$value] . '</b>. Rule id: ' . $key;
                            break;
                        }
                    } else {
                        print "<b>No changes</b>.";
                    }

                    $this->cf->show_rules($global_rules, false, $global_check, true);
                }
                ?> 
            </fieldset>

        </div>
    </form>
<?php } ?>