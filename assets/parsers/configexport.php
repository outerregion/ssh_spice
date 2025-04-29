<?php
// usersc/plugins/ssh_spice/assets/parsers/configexport.php
require_once '../../../../../users/init.php';
if (!isset($user) || !$user->isLoggedIn()) {
    die("Unauthorized");
}

// Check for CSRF token
if (!Token::check(Input::get('csrf'))) {
    die("Invalid token");
}

$userid = $user->data()->id;

// Call the export function
$configPath = sshExportConfig($userid);

if ($configPath) {
    sessionValMessages([], "Config generated.");
    Redirect::to($configPath);
} else {
    sessionValMessages(["No SSH connections found"], "");
    Redirect::to($us_url_root . 'users/account.php?plugin=ssh_spice');
}
