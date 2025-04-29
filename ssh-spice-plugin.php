<?php
// usersc/plugins/ssh_spice/install.php

require_once 'plugin_info.php';

$db = DB::getInstance();
//$plugin_name = plugin_name;


//Only run the following code if the plugin is being installed
if (!pluginActive($plugin_name)) {
    // Create the ssh table if it doesn't exist
    $db->query("
CREATE TABLE IF NOT EXISTS `ssh` (
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
  `sshgroup` text DEFAULT NULL,
  `createdby` int(1) DEFAULT NULL,
  PRIMARY KEY (`sshid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
");

$db->query("
CREATE TABLE IF NOT EXISTS `sshauth` (
  `authid` int(11) NOT NULL AUTO_INCREMENT,
  `name` text NOT NULL,
  `pubkey` text NOT NULL,
  `user` int(11) NOT NULL,
  `keytype` enum('dsa','rsa','ed25519','ecdsa') NOT NULL DEFAULT 'rsa',
  PRIMARY KEY (`authid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
");

$db->query("
CREATE TABLE IF NOT EXISTS `sshconfig` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `identityfile` varchar(255) DEFAULT NULL,
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
  `advanced` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
");

$db->query("
CREATE TABLE IF NOT EXISTS `sshgroups` (
  `sshgid` int(11) NOT NULL AUTO_INCREMENT,
  `sshgroup` text DEFAULT NULL,
  `name` text DEFAULT NULL,
  `userid` int(11) NOT NULL,
  PRIMARY KEY (`sshgid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
");

$db->query("
CREATE TABLE IF NOT EXISTS `ssh_advanced` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `userid` int(10) UNSIGNED NOT NULL,
  `advanced` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `userid` (`userid`)
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
