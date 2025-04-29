<?php
// usersc/plugins/ssh_spice/hooks/accountbottom.php
// This adds a link to the SSH Manager in the account page

if (pluginActive('ssh_spice')) {
    ?>
    <div class="card mt-2">
        <div class="card-body">
            <h5 class="card-title">SSH Manager</h5>
            <p class="card-text">Manage your SSH connections and keys for easy access to your servers.</p>
            <a href="<?=$us_url_root?>users/account.php?plugin=ssh_spice" class="btn btn-primary">SSH Spice Manager</a>
        </div>
    </div>
    <?php
}
