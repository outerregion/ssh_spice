<?php
// usersc/plugins/ssh_spice/menu_hooks/menu_hook.php
// This adds a link to the SSH Manager in the menu

if (pluginActive('ssh_spice')) {
    $dropdownString = '<li class="nav-item"><a class="nav-link" href="'.$us_url_root.'users/account.php?plugin=ssh_spice"><i class="fa fa-key"></i> SSH Manager</a></li>';
    $position = stripos($menu, '<li class="divider">');
    if ($position !== false) {
        $menu = substr_replace($menu, $dropdownString, $position, 0);
    }
}
