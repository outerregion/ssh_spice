<?php
// usersc/plugins/ssh_spice/functions.php

if (!function_exists('sshSpice')) {
    /**
     * Add SSH connection to database
     * @param array $data SSH connection data
     * @return bool|int Returns sshid on success, false on failure
     */
    function sshSpice($data)
    {
        global $db, $user;

        // Required fields
        $required = ['host', 'hostname', 'user', 'port'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                return false;
            }
        }

        // Set default values if not provided
        $data['createdby'] = $user->data()->id;
        $data['port'] = isset($data['port']) ? (int)$data['port'] : 22;

        // Default values for optional fields
        $optional_fields = [
            'ForwardAgent',
            'ForwardX11',
            'ForwardX11Trusted',
            'GSSAPIAuthentication',
            'LocalCommand',
            'LogLevel',
            'ProxyCommand',
            'ProxyJump',
            'proxyun',
            'identityfile',
            'forwardlocal',
            'forwardremote',
            'sshgroup'
        ];

        foreach ($optional_fields as $field) {
            if (!isset($data[$field])) {
                $data[$field] = null;
            }
        }

        // Insert the SSH connection
        $db->insert('ssh', $data);

        // Check for errors
        if ($db->error()) {
            error_log($user->data()->id . "SSH Spice - Failed to add SSH connection: " . $db->errorString());
            return false;
        }

        error_log($user->data()->id . "SSH Spice - Added SSH connection: " . $data['host']);
        return $db->lastId();
    }
}

if (!function_exists('sshAddKey')) {
    /**
     * Add SSH public key to database
     * @param array $data SSH key data
     * @return bool|int Returns authid on success, false on failure
     */
    function sshAddKey($data)
    {
        global $db, $user;

        // Required fields
        $required = ['name', 'pubkey', 'keytype'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                return false;
            }
        }

        // Set default values
        $data['user'] = $user->data()->id;
        $data['keytype'] = isset($data['keytype']) ? $data['keytype'] : 'rsa';

        // Insert the SSH key
        $db->insert('sshauth', $data);

        // Check for errors
        if ($db->error()) {
            error_log($user->data()->id . "SSH Spice - Failed to add SSH key: " . $db->errorString());
            return false;
        }

        //error_log($user->data()->id . "SSH Spice - Added SSH key: " . $data['name']);
        return $db->lastId();
    }
}

if (!function_exists('sshExportConfig')) {
    /**
     * Export SSH configuration
     * @param int $userid User ID
     * @return string Path to config file
     */
    function sshExportConfig($userid = null)
    {
        global $db, $user, $us_url_root;

        if ($userid === null) {
            $userid = $user->data()->id;
        }

        $userConfig = sshGetUserConfig($userid);
        $advancedDirectives = loadAdvancedInfo($userid);

        // Get SSH connections for user
        $sql = "SELECT * FROM ssh WHERE createdby = ? ORDER BY sshgroup ASC, host ASC";
        $query = $db->query($sql, [$userid]);
        $connections = $query->results();

        $sshwrite = "";
        $filepath = "output/$userid";

        if (!is_writable(dirname($filepath))) {
            error_log("Directory is not writable: $filepath");
            return false;
        }

        // Add general settings
        $sshwrite .= "### General settings ###\n";
        $sshwrite .= "Host *\n";
        if (!empty($userConfig->identityfile)) {
            $sshwrite .= "    IdentityFile " . $userConfig->identityfile . "\n";
        }
        if (!empty($userConfig->tcpkeepalive)) {
            $sshwrite .= "    TCPKeepAlive " . $userConfig->tcpkeepalive . "\n";
        }
        if (!empty($userConfig->forwardagent)) {
            $sshwrite .= "    ForwardAgent " . $userConfig->forwardagent . "\n";
        }
        if (!empty($userConfig->serveraliveinterval)) {
            $sshwrite .= "    ServerAliveInterval " . $userConfig->serveraliveinterval . "\n";
        }
        if (!empty($userConfig->compression)) {
            $sshwrite .= "    Compression " . $userConfig->compression . "\n";
        }
        if (!empty($userConfig->pubkeyauthentication)) {
            $sshwrite .= "    PubkeyAuthentication " . $userConfig->pubkeyauthentication . "\n";
        }
        if (!empty($userConfig->stricthostkeychecking)) {
            $sshwrite .= "    StrictHostKeyChecking " . $userConfig->stricthostkeychecking . "\n";
        }
        if (!empty($userConfig->loglevel)) {
            $sshwrite .= "    LogLevel " . $userConfig->loglevel . "\n";
        }
        if (!empty($userConfig->userknownhostsfile)) {
            $sshwrite .= "    UserKnownHostsFile " . $userConfig->userknownhostsfile . "\n";
        }
        if (!empty($userConfig->ciphers)) {
            $sshwrite .= "    Ciphers " . $userConfig->ciphers . "\n";
        }
        if (!empty($userConfig->hostkeyalgorithms)) {
            $sshwrite .= "    HostKeyAlgorithms " . $userConfig->hostkeyalgorithms . "\n";
        }
        if (!empty($userConfig->kexalgorithms)) {
            $sshwrite .= "    KexAlgorithms " . $userConfig->kexalgorithms . "\n";
        }
        if (!empty($userConfig->macs)) {
            $sshwrite .= "    MACs " . $userConfig->macs . "\n";
        }

        $sshwrite .= "\n";

        if (!empty($advancedDirectives)) {
            $sshwrite .= "# Custom SSH Directives\n" . $advancedDirectives . "\n";
        }

        // ✅ Group connections by sshgroup
        $grouped = [];
        foreach ($connections as $conn) {
            $group = $conn->sshgroup ?? 'Ungrouped';
            $grouped[$group][] = $conn;
        }

        ksort($grouped);

        foreach ($grouped as $groupName => $items) {
            $sshwrite .= "\n### $groupName ###\n";

            foreach ($items as $conn) {
                $host = $conn->host ?? 'unnamed';
                $hostname = $conn->hostname ?? '';
                $connUser = $conn->user ?? '';
                $port = $conn->port ?? '';
                $identityFile = $conn->identityfile ?? '';
                $proxyun = $conn->proxyun ?? '';
                $proxyjump = $conn->ProxyJump ?? '';
                $forwardlocal = $conn->forwardlocal ?? '';
                $forwardremote = $conn->forwardremote ?? '';

                if (!$host || !$hostname) continue;

                $sshwrite .= "Host $host\n";
                $sshwrite .= "    HostName $hostname\n";
                if ($proxyjump) {
                    $sshwrite .= "    ProxyJump $proxyun@$proxyjump\n";
                }
                if ($connUser) $sshwrite .= "    User $connUser\n";
                if ($port) $sshwrite .= "    Port $port\n";
                if ($identityFile) $sshwrite .= "    IdentityFile $identityFile\n";

                if ($forwardlocal && $forwardremote) {
                    $sshwrite .= "    LocalForward $forwardlocal localhost:$forwardremote\n";
                }
                if ($conn->ForwardX11) {
                    $sshwrite .= "    ForwardX11 yes\n";
                }
                if ($conn->ForwardX11Trusted) {
                    $sshwrite .= "    ForwardX11Trusted yes\n";
                }
                $sshwrite .= "\n";
            }
        }

        // Ensure output dir exists
        if (!file_exists($filepath)) {
            if (!mkdir($filepath, 0777, true)) {
                error_log("Failed to create directory: $filepath");
                return false;
            }
        }

        $fullPath = "$filepath/config";
        $file = fopen($fullPath, "w");
        if (!$file) {
            error_log("Unable to open file: $fullPath");
            return false;
        }

        fwrite($file, $sshwrite);
        fclose($file);

        return "/usersc/plugins/ssh_spice/output/$userid/config";
    }
}




if (!function_exists('sshUpdateConnection')) {
    /**
     * Update SSH connection in database
     * @param array $data SSH connection data
     * @return bool Returns true on success, false on failure
     */
    function sshUpdateConnection($data)
    {
        global $db, $user;

        // Required fields
        $required = ['sshid', 'host', 'hostname', 'user', 'port'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                return false;
            }
        }

        // Verify ownership
        $check = $db->query("SELECT * FROM ssh WHERE sshid = ? AND createdby = ?", [$data['sshid'], $user->data()->id]);
        if ($check->count() == 0) {
            return false;
        }

        // Remove sshid from data array before update
        $sshid = $data['sshid'];
        unset($data['sshid']);

        // Update the connection
        //$db->update('ssh', $sshid, $data, 'sshid');
        $fields_to_update = $data; // already has all the fields you want to update
        $db->update('ssh', ['sshid' => $sshid], $fields_to_update);

        var_dump($db->error(), $db->errorString());

        // Check for errors
        if ($db->error()) {
            error_log($user->data()->id . "SSH Spice - Failed to update SSH connection: " . $db->errorString());
            return false;
        }

        error_log($user->data()->id . "SSH Spice - Updated SSH connection: " . $data['host']);
        return true;
    }
}

if (!function_exists('sshGetGroups')) {
    /**
     * Get unique SSH groups from connections
     * @param int $userid User ID (optional)
     * @return array List of unique SSH groups
     */
    function sshGetGroups($userid = null)
    {
        global $db, $user;

        // Use current user if none specified
        if ($userid === null) {
            $userid = $user->data()->id;
        }

        // Check if sshgroups table exists and has entries
        $groupsTable = $db->query("SHOW TABLES LIKE 'sshgroups'");

        if ($groupsTable->count() > 0) {
            // Use the sshgroups table if it exists
            $query = $db->query("SELECT * FROM sshgroups WHERE userid = ? ORDER BY name ASC", [$userid]);

            if ($query->count()) {
                return $query->results();
            }
        } else {
            // Fall back to distinct groups from ssh table for backward compatibility
            $query = $db->query("SELECT DISTINCT sshgroup FROM ssh WHERE createdby = ? AND sshgroup IS NOT NULL ORDER BY sshgroup ASC", [$userid]);

            if ($query->count()) {
                $groups = [];
                foreach ($query->results() as $row) {
                    // Create objects to match expected structure
                    $group = new stdClass();
                    $group->sshgid = 0; // Placeholder
                    $group->sshgroup = $row->sshgroup;
                    $group->name = $row->sshgroup;
                    $group->userid = $userid;
                    $groups[] = $group;
                }
                return $groups;
            }
        }

        return [];
    }
}

if (!function_exists('sshAddGroup')) {
    /**
     * Add SSH group
     * @param array $data Group data
     * @return bool|int Returns group ID on success, false on failure
     */
    function sshAddGroup($data)
    {
        global $db, $user;

        // Required fields
        if (!isset($data['name']) || empty($data['name'])) {
            return false;
        }

        // Set default values
        $data['userid'] = $user->data()->id;
        $data['sshgroup'] = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $data['name']));

        // Insert the SSH group
        $db->insert('sshgroups', $data);

        // Check for errors
        if ($db->error()) {
            error_log($user->data()->id . "SSH Spice - Failed to add SSH group: " . $db->errorString());
            return false;
        }

        error_log($user->data()->id . "SSH Spice - Added SSH group: " . $data['name']);
        return $db->lastId();
    }
}


if (!function_exists('sshUpdateGroup')) {
    /**
     * Update SSH group
     * @param array $data Group data
     * @return bool Success/failure
     */
    function sshUpdateGroup($data)
    {
        global $db, $user;

        // Only proceed if formid is NOT 'advanced'
        if (isset($_POST['formid']) && $_POST['formid'] === 'advanced') {
            return false;
        }

        // Required fields
        if (!isset($data['sshgroup']) || empty($data['sshgroup']) || !isset($data['name']) || empty($data['name'])) {
            return false;
        }

        // Verify ownership
        $check = $db->query("SELECT * FROM sshgroups WHERE sshgid = ? AND userid = ?", [$data['sshgroup'], $user->data()->id]);
        if ($check->count() == 0) {
            return false;
        }

        // Update the group
        $db->update('sshgroups', $data['sshgroup'], ['name' => $data['name']], 'sshgid');

        // Check for errors
        if ($db->error()) {
            error_log($user->data()->id . "SSH Spice - Failed to update SSH group: " . $db->errorString());
            return false;
        }

        error_log($user->data()->id . "SSH Spice - Updated SSH group: " . $data['name']);
        return true;
    }
}


if (!function_exists('sshDeleteGroup')) {
    /**
     * Delete SSH group
     * @param int $sshgid Group ID
     * @return bool Success/failure
     */
    function sshDeleteGroup($sshgid)
    {
        global $db, $user;

        // Verify ownership
        $check = $db->query("SELECT * FROM sshgroups WHERE sshgid = ? AND userid = ?", [$sshgid, $user->data()->id]);
        if ($check->count() == 0) {
            return false;
        }

        // Check if group is in use
        $in_use = $db->query("SELECT COUNT(*) as count FROM ssh WHERE sshgroup = ? AND createdby = ?", [$check->first()->sshgroup, $user->data()->id]);
        if ($in_use->first()->count > 0) {
            return false; // Group is in use
        }

        // Delete the group
        $db->delete('sshgroups', ['sshgid', '=', $sshgid]);

        // Check for errors
        if ($db->error()) {
            error_log($user->data()->id . "SSH Spice - Failed to delete SSH group: " . $db->errorString());
            return false;
        }

        error_log($user->data()->id . "SSH Spice - Deleted SSH group #$sshgid");
        return true;
    }
}

if (!function_exists('sshGetGroups')) {
    /**
     * Get SSH groups for user
     * @param int $userid User ID (optional)
     * @return array List of SSH groups
     */
    function sshGetGroups($userid = null)
    {
        global $db, $user;

        // Use current user if none specified
        if ($userid === null) {
            $userid = $user->data()->id;
        }

        // Get groups
        $query = $db->query("SELECT * FROM sshgroups WHERE userid = ? ORDER BY name ASC", [$userid]);

        if ($query->count()) {
            return $query->results();
        }

        return [];
    }
}

if (!function_exists('sshGetConnections')) {
    /**
     * Get SSH connections for user
     * @param int $userid User ID (optional)
     * @param string $group Group filter (optional)
     * @return array List of SSH connections
     */
    function sshGetConnections($userid = null, $group = null)
    {
        global $db, $user;

        // Use current user if none specified
        if ($userid === null) {
            $userid = $user->data()->id;
        }

        // Build query
        $sql = "SELECT * FROM ssh WHERE createdby = ?";
        $params = [$userid];

        // Add group filter if specified
        if ($group !== null) {
            $sql .= " AND sshgroup = ?";
            $params[] = $group;
        }

        $sql .= " ORDER BY sshgroup ASC, host ASC";
        $query = $db->query($sql, $params);

        if ($query->count()) {
            return $query->results();
        }

        return [];
    }
}

if (!function_exists('sshGetKeys')) {
    /**
     * Get SSH keys for user
     * @param int $userid User ID (optional)
     * @return array List of SSH keys
     */
    function sshGetKeys($userid = null)
    {
        global $db, $user;

        // Use current user if none specified
        if ($userid === null) {
            $userid = $user->data()->id;
        }

        $query = $db->query("SELECT * FROM sshauth WHERE user = ? ORDER BY name ASC", [$userid]);
        if ($query->count()) {
            return $query->results();
        }

        return [];
    }
}

function sshExportAuthorizedKeys($userid = null)
{
    global $db, $user;
    if ($userid === null) {
        $userid = $user->data()->id;
    }
    $keys = $db->query("SELECT pubkey FROM sshauth WHERE user = ?", [$userid]);
    if (!$keys->count()) {
        return false;
    }
    $filepath = "usersc/plugins/ssh_spice/output/$userid/authorized_keys";
    if (!file_exists(dirname($filepath))) {
        mkdir(dirname($filepath), 0777, true);
    }
    $file = fopen($filepath, "w");
    foreach ($keys->results() as $row) {
        fwrite($file, trim($row->pubkey) . "\n");
    }
    fclose($file);
    return "/usersc/plugins/ssh_spice/output/$userid/authorized_keys";
}


if (!function_exists('sshDeleteConnection')) {
    /**
     * Delete SSH connection
     * @param int $sshid Connection ID
     * @return bool Success/failure
     */
    function sshDeleteConnection($sshid)
    {
        global $db, $user;

        // Verify ownership
        $check = $db->query("SELECT * FROM ssh WHERE sshid = ? AND createdby = ?", [$sshid, $user->data()->id]);
        if ($check->count() == 0) {
            return false;
        }

        $db->delete('ssh', ['sshid', '=', $sshid]);

        if ($db->error()) {
            error_log($user->data()->id . "SSH Spice - Failed to delete SSH connection: " . $db->errorString());
            return false;
        }

        error_log($user->data()->id . "SSH Spice - Deleted SSH connection #$sshid");
        return true;
    }
}

if (!function_exists('sshDeleteKey')) {
    /**
     * Delete SSH key
     * @param int $authid Key ID
     * @return bool Success/failure
     */
    function sshDeleteKey($authid)
    {
        global $db, $user;

        // Verify ownership
        $check = $db->query("SELECT * FROM sshauth WHERE authid = ? AND user = ?", [$authid, $user->data()->id]);
        if ($check->count() == 0) {
            return false;
        }

        $db->delete('sshauth', ['authid', '=', $authid]);

        if ($db->error()) {
            error_log($user->data()->id . "SSH Spice - Failed to delete SSH key: " . $db->errorString());
            return false;
        }

        error_log($user->data()->id . "SSH Spice - Deleted SSH key #$authid");
        return true;
    }
}

// Fetch general SSH settings for the current user, or return default values if no settings are found.
function getGeneralSettings()
{
    global $db, $userid;
    $sql = "SELECT * FROM sshconfig WHERE userid = ?";
    $query = $db->query($sql, [$userid]);

    if ($query->count() > 0) {
        return $query->first(); // Return the existing settings from the database
    }

    // If no settings exist, return default values (helpers).
    return (object)[
        'identityfile' => '',
        'tcpkeepalive' => '',
        'forwardagent' => 'no',
        'serveraliveinterval' => '',
        'compression' => '',
        'ciphers' => '',
        'hostkeyalgorithms' => '',
        'kexalgorithms' => '',
        'macs' => '',
        'pubkeyauthentication' => 'no',
        'stricthostkeychecking' => 'no',
        'userknownhostsfile' => '',
        'loglevel' => 'INFO'
    ];
}

// Save the general SSH settings for the current user.
function saveGeneralSettings($data)
{
    global $db, $user;

    try {
        // Ensure $db exists
        if (!isset($db) || $db === null) {
            $db = DB::getInstance();
        }

        // Ensure $user and $userid exist
        if (!isset($user) || !$user->isLoggedIn()) {
            throw new Exception("User not logged in or \$user object not available.");
        }

        $userid = $user->data()->id;

        // Now do the normal database logic
        $existing = $db->query("SELECT * FROM sshconfig WHERE userid = ?", [$userid]);
        if ($existing->count() > 0) {
            $row = $existing->first();

            // Log the update action
            error_log("Updating sshconfig for userid=$userid, id=" . $row->id . ", data=" . json_encode($data));

            $db->update('sshconfig', $row->id, $data);
        } else {
            $data['userid'] = $userid;

            // Log the insert action
            error_log("Inserting new sshconfig for userid=$userid, data=" . json_encode($data));

            $db->insert('sshconfig', $data);
        }
    } catch (Exception $e) {
        // Smarter error logging: include script and URL
        $error_message = "saveGeneralSettings error in " . basename(__FILE__) . " on " . ($_SERVER['REQUEST_URI'] ?? 'unknown') . ": " . $e->getMessage();
        error_log($error_message);

        // Optional: Show a safe user message
        sessionValMessages("Failed to save settings. Please try again or contact support.", "danger");

        // Redirect back
        redirect('yourpage.php');
        exit();
    }
}

function saveAdvancedInfo($advancedData)
{
    global $db, $user;

    try {
        // Get the current user ID
        $userid = $user->data()->id;
        //error_log("saveAdvancedInfo called with data: " . substr($advancedData, 0, 50));
        // Safely prepare the data
        $data = [
            'userid' => $userid,
            'advanced' => trim($advancedData)
        ];

        // Check if an entry already exists for this user
        $check = $db->query("SELECT * FROM ssh_advanced WHERE userid = ?", [$userid]);

        if ($check->count() > 0) {
            // Update existing record
            $db->update('ssh_advanced', ['userid' => $userid], ['advanced' => $data['advanced']]);
        } else {
            // Insert new record
            $db->insert('ssh_advanced', $data);
        }


        // Check for errors
        if ($db->error()) {
            error_log($userid . "SSH Spice"  . "Failed to save advanced SSH settings: " . $db->errorString());
            return false;
        }

        // error_log("UserID: $userid - SSH Spice: Saved advanced SSH settings");

        return true;
    } catch (Exception $e) {
        error_log("saveAdvancedInfo error: " . $e->getMessage());
        return false;
    }
}

function loadAdvancedInfo($userid)
{
    global $db, $user;

    // Use current user if none specified
    if ($userid === null) {
        $userid = $user->data()->id;
    }

    try {
        $query = $db->query("SELECT advanced FROM ssh_advanced WHERE userid = ?", [$userid]);

        if ($query->count() > 0) {
            return $query->first()->advanced;
        } else {
            return ''; // No directives found
        }
    } catch (Exception $e) {
        error_log("loadAdvancedInfo error: " . $e->getMessage());
        return '';
    }
}

if (!function_exists('sshGetUserConfig')) {
    /**
     * Get or create user SSH config settings
     * @param int $userid User ID
     * @return object User SSH config settings
     */
    function sshGetUserConfig($userid = null)
    {
        global $db, $user;

        // Use current user if none specified
        if ($userid === null) {
            $userid = $user->data()->id;
        }

        // Check if user config exists
        $query = $db->query("SELECT * FROM sshconfig WHERE userid = ?", [$userid]);

        if ($query->count()) {
            // Return existing config
            return $query->first();
        } else {
            // Create default config
            $defaults = [
                'userid' => $userid,
                'identityfile' => '~/.ssh/id_rsa',
                'tcpkeepalive' => 'yes',
                'forwardagent' => 'yes',
                'serveraliveinterval' => '10',
                'compression' => 'no',
                'pubkeyauthentication' => 'yes',
                'stricthostkeychecking' => 'ask',
                'loglevel' => 'INFO'
            ];

            $db->insert('sshconfig', $defaults);

            if ($db->error()) {
                error_log($userid, "SSH Spice - Failed to create default SSH config: " . $db->errorString());
                return (object)$defaults;
            }

            error_log($userid, "SSH Spice - Created default SSH config");
            return (object)$defaults;
        }
    }
}

if (!function_exists('sshUpdateUserConfig')) {
    /**
     * Update user SSH config settings
     * @param array $data Config data
     * @return bool Success/failure
     */
    function sshUpdateUserConfig($data)
    {
        global $db, $user;

        // Log for debugging
        error_log("sshUpdateUserConfig called with data: " . json_encode($data));

        // Only proceed if formid is NOT 'advanced'
        // Check if this is the advanced form submission through any means
        if ((isset($_POST['formid']) && $_POST['formid'] === 'advanced') ||
            (isset($data['formid']) && $data['formid'] === 'advanced')
        ) {
            return false;
        }
        // Check if all values are empty (which would indicate a problematic call)
        $allEmpty = true;
        foreach ($data as $key => $value) {
            if ($key !== 'userid' && !empty($value)) {
                $allEmpty = false;
                break;
            }
        }

        // If all values are empty (except userid), don't update
        if ($allEmpty) {
            error_log("Prevented empty update to sshconfig");
            return false;
        }

        // Original function code continues...
        // Required field
        if (!isset($data['userid'])) {
            $data['userid'] = $user->data()->id;
        }

        // Check if config exists
        $check = $db->query("SELECT * FROM sshconfig WHERE userid = ?", [$data['userid']]);

        if ($check->count() == 0) {
            // Insert new config
            $db->insert('sshconfig', $data);
        } else {
            // Update existing config
            $db->update('sshconfig', ['userid' => $data['userid']], $data);
        }

        // Check for errors
        if ($db->error()) {
            error_log($user->data()->id . "SSH Spice - Failed to update SSH config: " . $db->errorString());
            return false;
        }

        error_log($user->data()->id . "SSH Spice - Updated SSH config");
        return true;
    }
}
