<h2><a href="<?php print $url ?>"><?php print __('Critic matic') ?></a>. <?php print __('Settings audience') ?></h2>
<?php print $tabs; ?>

<form accept-charset="UTF-8" method="post" id="tag">
    <div class="cm-edit inline-edit-row">

        <p><?php print __('Sync data') ?>: 
            <b><?php
                print DB_SYNC_DATA == 1 ? "True" : "False";
                ?></b></p>
        <p><?php print __('Sync status') ?>:

            <b><?php
                print $this->cm->sync_status_types[DB_SYNC_MODE];
                ?></b>                       
        </p>

    </div>
</form>