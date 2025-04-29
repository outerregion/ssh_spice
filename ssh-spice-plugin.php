<?php
// usersc/plugins/ssh_spice/install.php

require_once 'plugin_info.php';

$db = DB::getInstance();
$plugin_name = plugin_name;

//Only run the following code if the plugin is being installed
if (!pluginActive($plugin_name)) {
    // Create the ssh table if it doesn't exist
    $db->query("CREATE TABLE IF NOT EXISTS `ssh` (
        `sshid` int(11) NOT NULL AUTO_INCREMENT,
        `host` text NOT NULL,
        `ForwardAgent` varchar(10) DEFAULT NULL,
        `ForwardX11` varchar(10) DEFAULT NULL,
        `ForwardX11Trusted` varchar(10) DEFAULT NULL,
        `GSSAPIAuthentication` varchar(10) DEFAULT NULL,
        `hostname` text NOT NULL,
        `user` text NOT NULL,
        `port` int(11) NOT NULL,
        `identityfile` text DEFAULT NULL,
        `LocalCommand` varchar(10) DEFAULT NULL,
        `forwardlocal` text DEFAULT NULL,
        `forwardremote` int(11) DEFAULT NULL,
        `LogLevel` varchar(10) DEFAULT NULL,
        `ProxyCommand` varchar(10) DEFAULT NULL,
        `ProxyJump` text DEFAULT NULL,
        `proxyun` text DEFAULT NULL,
        `sshgroup` enum('aws-work','aws-personal','git','personal','web','webdev','internal-userful','work','external','infrastructure','internal-home') DEFAULT NULL,
        `createdby` int(1) DEFAULT NULL,
        PRIMARY KEY (`sshid`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;");

    // Create the sshauth table if it doesn't exist
    $db->query("CREATE TABLE IF NOT EXISTS `sshauth` (
        `authid` int(11) NOT NULL AUTO_INCREMENT,
        `name` text NOT NULL,
        `pubkey` text NOT NULL,
        `user` int(11) NOT NULL,
        `keytype` enum('dsa','rsa','ed25519','ecdsa') NOT NULL DEFAULT 'rsa',
        PRIMARY KEY (`authid`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;");

    // Create the sshgroups table if it doesn't exist
    $db->query("CREATE TABLE IF NOT EXISTS `sshgroups` (
        `sshgid` int(11) NOT NULL AUTO_INCREMENT,
        `group_name` varchar(255) NOT NULL,
        `description` text DEFAULT NULL,
        `createdby` int(11) DEFAULT NULL,
        PRIMARY KEY (`sshgid`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;");

    // Add default groups
    $default_groups = [
        'aws-work' => 'AWS Work Servers',
        'aws-personal' => 'AWS Personal Servers',
        'git' => 'Git Repositories',
        'personal' => 'Personal Servers',
        'web' => 'Web Servers',
        'webdev' => 'Web Development Servers',
        'internal-userful' => 'Internal Userful Servers',
        'work' => 'Work Servers',
        'external' => 'External Servers',
        'infrastructure' => 'Infrastructure Servers',
        'internal-home' => 'Internal Home Servers'
    ];

    foreach ($default_groups as $group_name => $description) {
        $check = $db->query("SELECT * FROM sshgroups WHERE group_name = ?", [$group_name])->count();
        if ($check == 0) {
            $db->insert('sshgroups', [
                'group_name' => $group_name,
                'description' => $description,
                'createdby' => 1
            ]);
        }
    }


    // Create the sshconfig table if it doesn't exist
    $db->query("CREATE TABLE IF NOT EXISTS `sshconfig` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `identityfile` varchar(255) DEFAULT '~/.ssh/id_rsa',
  `tcpkeepalive` varchar(10) DEFAULT 'yes',
  `forwardagent` varchar(10) DEFAULT 'yes',
  `serveraliveinterval` varchar(10) DEFAULT '10',
  `compression` varchar(10) DEFAULT 'no',
  `ciphers` varchar(255) DEFAULT NULL,
  `hostkeyalgorithms` varchar(255) DEFAULT NULL,
  `kexalgorithms` varchar(255) DEFAULT NULL,
  `macs` varchar(255) DEFAULT NULL,
  `pubkeyauthentication` varchar(10) DEFAULT 'yes',
  `stricthostkeychecking` varchar(10) DEFAULT 'ask',
  `userknownhostsfile` varchar(255) DEFAULT NULL,
  `loglevel` varchar(50) DEFAULT 'INFO',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `userid` (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
");

    // Create the ssh_advanced table if it doesn't exist
    $db->query("CREATE TABLE ssh_advanced (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `userid` INT UNSIGNED NOT NULL,
    `advanced` TEXT,
    PRIMARY KEY (id),
    UNIQUE KEY (userid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");


    // Register plugin hooks
    $hooks = [];
    $hooks['account.php']['bottom'] = 'hooks/accountbottom.php';

    // Register menu_hooks
    registerHooks($hooks, $plugin_name);

    // Menu hooks
    $menu_hooks['menu_hook.php'] = 'SSH Manager';

    // Insert plugin settings
    $db->insert('us_plugins', [
        'plugin' => $plugin_name,
        'status' => 'active',
    ]);

    if (!$db->error()) {
        logger(1, "Plugins", "Successfully installed $plugin_name plugin");
    } else {
        logger(1, "Plugins", "Failed to install $plugin_name plugin, Error: " . $db->errorString());
    }
}
