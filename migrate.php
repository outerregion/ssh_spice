<?php
// usersc/plugins/ssh_spice/migrate.php
// This file handles migrations for plugin updates

require_once 'plugin_info.php';
$plugin_name = "ssh_spice";

$db = DB::getInstance();
//$plugin_name = plugin_name;

// Check which updates have already been run
$existing = [];
$db = DB::getInstance();
$query = $db->query("SELECT migration FROM us_migrations WHERE plugin = ?", [$plugin_name]);
if ($query->count() > 0) {
    $existing = $query->results();
    $existing = array_column($existing, 'migration');
}

// Count the number of migrations run during this update
$count = 0;

// Run migrations as needed
// Example of a migration:
/*
$update = '00001';
if (!in_array($update, $existing)) {
    logger($user->data()->id, "Migrations", "$update migration triggered for $plugin_name");
    
    // Your migration code here
    
    $existing[] = $update;
    $count++;
}
*/

// Example migration to add a description field to ssh table if needed in the future
$update = '00001';
if (!in_array($update, $existing)) {
    logger(1, "Migrations", "$update migration triggered for $plugin_name");
    
    // Add a description field to the ssh table
    $db->query("ALTER TABLE ssh ADD description TEXT NULL AFTER host;");
    
    $db->insert('us_migrations', [
        'plugin' => $plugin_name,
        'migration' => $update
    ]);
    $existing[] = $update;
    $count++;
}

if ($count == 1) {
    logger(1, "Migrations", "Completed $count $plugin_name migration");
} else {
    logger(1, "Migrations", "Completed $count $plugin_name migrations");
}
