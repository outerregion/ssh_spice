<?php
// usersc/plugins/ssh_spice/configure.php
// This is the configuration page for the SSH Spice plugin
$root = $_SERVER['DOCUMENT_ROOT'];
require_once "$root/users/init.php";
require_once $abs_us_root . $us_url_root . 'users/includes/template/prep.php';
if (!securePage($_SERVER['PHP_SELF'])) {
  die();
}

// Ensure the user is logged in
if (!isset($user) || !$user->isLoggedIn()) {
  Redirect::to($us_url_root . 'users/login.php');
  exit();
}

// Include necessary files
require_once 'plugin_info.php';

// Check if we're being included from the account page or accessed directly
$from_account = false;
if (!isset($plugin_name)) {
  require_once '../../../users/init.php';
  require_once $abs_us_root . $us_url_root . 'users/includes/template/prep.php';
  $plugin_name = 'ssh_spice';
} else {
  $from_account = true;
}

// Process form submissions
if (!empty($_POST)) {
  // Verify CSRF token
  if (!Token::check(Input::get('csrf'))) {
    include($abs_us_root . $us_url_root . 'usersc/scripts/token_error.php');
    exit;
  }

  // Handle SSH connection form
  if (isset($_POST['add_connection'])) {
    $connection_data = [
      'host' => Input::get('host'),
      'hostname' => Input::get('hostname'),
      'user' => Input::get('user'),
      'port' => Input::get('port'),
      'identityfile' => Input::get('identityfile'),
      'sshgroup' => Input::get('sshgroup'),
      'ForwardAgent' => Input::get('ForwardAgent'),
      'ForwardX11' => Input::get('ForwardX11'),
      'ForwardX11Trusted' => Input::get('ForwardX11Trusted'),
      'GSSAPIAuthentication' => Input::get('GSSAPIAuthentication'),
      'ProxyJump' => Input::get('ProxyJump'),
      'proxyun' => Input::get('proxyun'),
      'forwardlocal' => Input::get('forwardlocal'),
      'forwardremote' => Input::get('forwardremote'),
    ];

    if (sshSpice($connection_data)) {
      $successes[] = "SSH connection added successfully.";
    } else {
      $errors[] = "Failed to add SSH connection.";
    }
  }

  // Handle SSH connection update
  if (isset($_POST['update_connection'])) {
    $connection_data = [
      'sshid' => Input::get('sshid'),
      'host' => Input::get('host'),
      'hostname' => Input::get('hostname'),
      'user' => Input::get('user'),
      'port' => Input::get('port'),
      'identityfile' => Input::get('identityfile'),
      'sshgroup' => Input::get('sshgroup'),
      'ForwardAgent' => Input::get('ForwardAgent'),
      'ForwardX11' => Input::get('ForwardX11'),
      'ForwardX11Trusted' => Input::get('ForwardX11Trusted'),
      'GSSAPIAuthentication' => Input::get('GSSAPIAuthentication'),
      'ProxyJump' => Input::get('ProxyJump'),
      'proxyun' => Input::get('proxyun'),
      'forwardlocal' => Input::get('forwardlocal'),
      'forwardremote' => Input::get('forwardremote'),
    ];

    if (sshUpdateConnection($connection_data)) {
      $successes[] = "SSH connection updated successfully.";
    } else {
      $errors[] = "Failed to update SSH connection.";
    }
  }

  // Handle SSH key form
  if (isset($_POST['add_key'])) {
    $key_data = [
      'name' => Input::get('name'),
      'pubkey' => Input::get('pubkey'),
      'keytype' => Input::get('keytype'),
    ];

    if (sshAddKey($key_data)) {
      $successes[] = "SSH key added successfully.";
    } else {
      $errors[] = "Failed to add SSH key.";
    }
  }

  // Handle delete connection
  if (isset($_POST['delete_connection'])) {
    $sshid = Input::get('sshid');
    if (sshDeleteConnection($sshid)) {
      $successes[] = "SSH connection deleted successfully.";
    } else {
      $errors[] = "Failed to delete SSH connection.";
    }
  }

  // Handle delete key
  if (isset($_POST['delete_key'])) {
    $authid = Input::get('authid');
    if (sshDeleteKey($authid)) {
      $successes[] = "SSH key deleted successfully.";
    } else {
      $errors[] = "Failed to delete SSH key.";
    }
  }

  // Handle export config
  if (isset($_POST['export_config'])) {
    $configPath = sshExportConfig();
    if ($configPath) {
      $successes[] = "SSH config exported successfully. <a href='$configPath' target='_blank'>Download</a>";
    } else {
      $errors[] = "Failed to export SSH config.";
    }
  }

  // Handle add group
  if (isset($_POST['add_group'])) {
    $group_data = [
      'name' => Input::get('group_name'),
    ];

    if (sshAddGroup($group_data)) {
      $successes[] = "SSH group added successfully.";
    } else {
      $errors[] = "Failed to add SSH group.";
    }
  }

  // Handle update group
  if (isset($_POST['update_group'])) {
    $group_data = [
      'sshgroup' => Input::get('sshgroup'),
      'name' => Input::get('group_name'),
    ];

    if (sshUpdateGroup($group_data)) {
      $successes[] = "SSH group updated successfully.";
    } else {
      $errors[] = "Failed to update SSH group.";
    }
  }

  // Handle delete group
  if (isset($_POST['delete_group'])) {
    $sshgroup = Input::get('sshgroup');
    if (sshDeleteGroup($sshgroup)) {
      $successes[] = "SSH group deleted successfully.";
    } else {
      $errors[] = "Failed to delete SSH group. Make sure no connections are using this group.";
    }
  }
}

// Get SSH connections
$ssh_connections = sshGetConnections();

// Get SSH keys
$ssh_keys = sshGetKeys();

// Get SSH groups
$ssh_groups = sshGetGroups();

// Create group options for select
$group_options = '';
foreach ($ssh_groups as $group) {
  $group_value = is_object($group) ? $group->sshgroup : $group;
  $group_name = is_object($group) ? $group->name : $group;
  $group_options .= "<option value='$group_value'>" . ucwords($group_name) . "</option>";
}

// Get connection to edit (if provided)
$edit_connection = null;
if (isset($_GET['edit_connection']) && is_numeric($_GET['edit_connection'])) {
  foreach ($ssh_connections as $conn) {
    if ($conn->sshid == $_GET['edit_connection']) {
      $edit_connection = $conn;
      break;
    }
  }
}

// Get group to edit (if provided)
$edit_group = null;
if (isset($_GET['edit_group']) && is_numeric($_GET['edit_group'])) {
  foreach ($ssh_groups as $group) {
    if ($group->sshgroup == $_GET['edit_group']) {
      $edit_group = $group;
      break;
    }
  }
}

// Process form submission
if (isset($_GET['general']) || (isset($_POST['formid']) && $_POST['formid'] === 'general')) {
  $data = [
    'userid' => $user->data()->id,
    'identityfile' => Input::get('identityfile'),
    'tcpkeepalive' => Input::get('tcpkeepalive'),
    'forwardagent' => Input::get('forwardagent'),
    'serveraliveinterval' => Input::get('serveraliveinterval'),
    'compression' => Input::get('compression'),
    'pubkeyauthentication' => Input::get('pubkeyauthentication'),
    'stricthostkeychecking' => Input::get('stricthostkeychecking'),
    'userknownhostsfile' => Input::get('userknownhostsfile'),
    'loglevel' => Input::get('loglevel'),
    'ciphers' => Input::get('ciphers'),
    'hostkeyalgorithms' => Input::get('hostkeyalgorithms'),
    'kexalgorithms' => Input::get('kexalgorithms'),
    'macs' => Input::get('macs')
  ];

  $result = sshUpdateUserConfig($data);

  if ($result) {
    $successes[] = "SSH configuration updated successfully";
  } else {
    $errors[] = "Failed to update SSH configuration";
  }
}

// Handle advanced directives form submission
if (isset($_GET['advanced']) || (isset($_POST['formid']) && $_POST['formid'] === 'advanced')) {
  // Show the advanced form
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['formid']) && $_POST['formid'] === 'advanced') {
    // Process advanced form submission
    $advancedData = Input::get('advanced'); // Adjust field name as needed
    if (saveAdvancedInfo($advancedData)) {
      sessionValMessages("Advanced SSH settings updated successfully.", "success");
     // error_log("Advanced SSH settings updated successfully. $advancedData");
    } else {
      sessionValMessages("Failed to update advanced SSH settings.", "danger");
    }
    redirect('index.php'); // Redirect to avoid form resubmission
  }
}

// Get current settings
$userConfig = sshGetUserConfig();


// Start the page for direct access
if (!$from_account) {
  include($abs_us_root . $us_url_root . 'users/includes/html_header.php');
  include($abs_us_root . $us_url_root . 'users/includes/navbar.php');
?>
  <div id="page-wrapper">
    <div class="container">
    <?php
  }
    ?>

    <div class="row">
      <div class="col-md-12">
        <h1>SSH Spice Configuration</h1>
        <p>Manage your SSH connections and public keys with ease.</p>

        <!-- Display messages -->
        <?php if (isset($errors) && !empty($errors)) { ?>
          <div class="alert alert-danger">
            <?php foreach ($errors as $error) { ?>
              <p><?php echo $error ?></p>
            <?php } ?>
          </div>
        <?php } ?>
        <?php if (isset($successes) && !empty($successes)) { ?>
          <div class="alert alert-success">
            <?php foreach ($successes as $success) { ?>
              <p><?php echo $success ?></p>
            <?php } ?>
          </div>
        <?php } ?>
      </div>
    </div>

    <div class="row">
      <div class="col-md-12">
        <div class="card">
          <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs" id="ssh-tabs" role="tablist">
              <li class="nav-item">
                <a class="nav-link <?php echo (isset($_GET['edit_connection']) || !isset($_GET['tab']) || (isset($_GET['tab']) && $_GET['tab'] == 'connections')) ? 'active' : ''; ?>" id="connections-tab" data-bs-toggle="tab" href="#connections" role="tab">Connections</a>
              </li>
              <li class="nav-item">
                <a class="nav-link <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'keys') ? 'active' : ''; ?>" id="keys-tab" data-bs-toggle="tab" href="#keys" role="tab">Public Keys</a>
              </li>
              <li class="nav-item">
                <a class="nav-link <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'groups' || isset($_GET['edit_group'])) ? 'active' : ''; ?>" id="groups-tab" data-bs-toggle="tab" href="#groups" role="tab">Groups</a>
              </li>
              <li class="nav-item">
                <a class="nav-link <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'general' || isset($_GET['general_group'])) ? 'active' : ''; ?>" id="general-tab" data-bs-toggle="tab" href="#general" role="tab">General</a>
              </li>
              <li class="nav-item">
                <a class="nav-link <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'advanced') ? 'active' : ''; ?>" id="advanced-tab" data-bs-toggle="tab" href="#advanced" role="tab">Advanced</a>
              </li>
              <li class="nav-item">
                <a class="nav-link <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'export') ? 'active' : ''; ?>" id="export-tab" data-bs-toggle="tab" href="#export" role="tab">Export</a>
              </li>
              <li class="nav-item">
                <a class="nav-link <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'help') ? 'active' : ''; ?>" id="help-tab" data-bs-toggle="tab" href="#help" role="tab">Help</a>
              </li>
            </ul>
          </div>
          <div class="card-body">
            <div class="tab-content" id="ssh-tabContent">
              <!-- Connections Tab -->
              <div class="tab-pane fade <?php echo (isset($_GET['edit_connection']) || !isset($_GET['tab']) || (isset($_GET['tab']) && $_GET['tab'] == 'connections')) ? 'show active' : ''; ?>" id="connections" role="tabpanel">
                <h3><?php echo $edit_connection ? 'Edit SSH Connection' : 'SSH Connections'; ?></h3>

                <!-- Add/Edit Connection Form -->
                <div class="card mb-4">
                  <div class="card-header">
                    <h5><?php echo $edit_connection ? 'Edit Connection' : 'Add New Connection'; ?></h5>
                  </div>
                  <div class="card-body">
                    <form action="" method="post">
                      <?php echo tokenHere(); ?>
                      <?php if ($edit_connection): ?>
                        <input type="hidden" name="sshid" value="<?php echo $edit_connection->sshid; ?>">
                      <?php endif; ?>
                      <div class="row">
                        <div class="col-md-6">
                          <div class="mb-3">
                            <label for="host">Host Alias</label>
                            <input type="text" class="form-control" id="host" name="host" value="<?php echo $edit_connection ? $edit_connection->host : ''; ?>" required>
                            <small class="form-text text-muted">Shorthand name for this connection</small>
                          </div>
                          <div class="mb-3">
                            <label for="hostname">Hostname</label>
                            <input type="text" class="form-control" id="hostname" name="hostname" value="<?php echo $edit_connection ? $edit_connection->hostname : ''; ?>" required>
                            <small class="form-text text-muted">Server hostname or IP address</small>
                          </div>
                          <div class="mb-3">
                            <label for="user">Username</label>
                            <input type="text" class="form-control" id="user" name="user" value="<?php echo $edit_connection ? $edit_connection->user : ''; ?>" required>
                          </div>
                          <div class="mb-3">
                            <label for="port">Port</label>
                            <input type="number" class="form-control" id="port" name="port" value="<?php echo $edit_connection ? $edit_connection->port : '22'; ?>" required>
                          </div>
                          <div class="mb-3">
                            <label for="sshgroup">Group</label>
                            <select class="form-control" id="sshgroup" name="sshgroup">
                              <option value="">-- Select Group --</option>
                              <?php foreach ($ssh_groups as $group): ?>
                                <option value="<?php echo $group->sshgroup; ?>" <?php echo ($edit_connection && $edit_connection->sshgroup == $group->sshgroup) ? 'selected' : ''; ?>>
                                  <?php echo ucwords($group->sshgroup); ?>
                                </option>
                              <?php endforeach; ?>
                            </select>
                          </div>
                        </div>
                        <div class="col-md-6">
                          <div class="mb-3">
                            <label for="identityfile">Identity File</label>
                            <input type="text" class="form-control" id="identityfile" name="identityfile" value="<?php echo $edit_connection ? $edit_connection->identityfile : ''; ?>">
                            <small class="form-text text-muted">Optional: Path to identity file (relative to ~/.ssh/)</small>
                          </div>
                          <div class="mb-3">
                            <label for="ProxyJump">Proxy Jump Host</label>
                            <input type="text" class="form-control" id="ProxyJump" name="ProxyJump" value="<?php echo $edit_connection ? $edit_connection->ProxyJump : ''; ?>">
                            <small class="form-text text-muted">Optional: Hostname of proxy jump server</small>
                          </div>
                          <div class="mb-3">
                            <label for="proxyun">Proxy Username</label>
                            <input type="text" class="form-control" id="proxyun" name="proxyun" value="<?php echo $edit_connection ? $edit_connection->proxyun : ''; ?>">
                            <small class="form-text text-muted">Optional: Username for proxy server</small>
                          </div>
                          <div class="row">
                            <div class="col-md-6">
                              <div class="mb-3">
                                <label for="forwardlocal">Local Forward Port</label>
                                <input type="text" class="form-control" id="forwardlocal" name="forwardlocal" value="<?php echo $edit_connection ? $edit_connection->forwardlocal : ''; ?>">
                              </div>
                            </div>
                            <div class="col-md-6">
                              <div class="mb-3">
                                <label for="forwardremote">Remote Forward Port</label>
                                <input type="number" class="form-control" id="forwardremote" name="forwardremote" value="<?php echo $edit_connection ? $edit_connection->forwardremote : ''; ?>">
                              </div>
                            </div>
                          </div>
                          <div class="mb-3">
                            <div class="form-check">
                              <input class="form-check-input" type="checkbox" id="ForwardAgent" name="ForwardAgent" value="yes" <?php echo ($edit_connection && $edit_connection->ForwardAgent == 'yes') ? 'checked' : ''; ?>>
                              <label class="form-check-label" for="ForwardAgent">
                                Forward Agent
                              </label>
                            </div>
                            <div class="form-check">
                              <input class="form-check-input" type="checkbox" id="ForwardX11" name="ForwardX11" value="yes" <?php echo ($edit_connection && $edit_connection->ForwardX11 == 'yes') ? 'checked' : ''; ?>>
                              <label class="form-check-label" for="ForwardX11">
                                Forward X11
                              </label>
                            </div>
                          </div>
                        </div>
                      </div>
                      <button type="submit" name="<?php echo $edit_connection ? 'update_connection' : 'add_connection'; ?>" class="btn btn-primary">
                        <?php echo $edit_connection ? 'Update Connection' : 'Add Connection'; ?>
                      </button>
                      <?php if ($edit_connection): ?>
                        <a href="./" class="btn btn-secondary">Cancel</a>
                      <?php endif; ?>
                    </form>
                  </div>
                </div>

                <!-- Connection List -->
                <div class="card">
                  <div class="card-header">
                    <h5>Your SSH Connections</h5>
                  </div>
                  <div class="card-body">
                    <?php if (count($ssh_connections) > 0): ?>
                      <div class="table-responsive">
                        <table class="table table-striped">
                          <thead>
                            <tr>
                              <th>Host</th>
                              <th>Hostname</th>
                              <th>Username</th>
                              <th>Port</th>
                              <th>Group</th>
                              <th>Actions</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php foreach ($ssh_connections as $conn): ?>
                              <tr>
                                <td><?php echo $conn->host ?></td>
                                <td><?php echo $conn->hostname ?></td>
                                <td><?php echo $conn->user ?></td>
                                <td><?php echo $conn->port ?></td>
                                <td>
                                  <?php
                                  $group_name = '';
                                  foreach ($ssh_groups as $group) {
                                    if ($group->sshgroup == $conn->sshgroup) {
                                      $group_name = ucwords($group->sshgroup);
                                      break;
                                    }
                                  }
                                  echo $group_name ? $group_name : 'None';
                                  ?>
                                </td>
                                <td>
                                  <a href="./?edit_connection=<?php echo $conn->sshid ?>" class="btn btn-sm btn-primary">Edit</a>
                                  <form action="" method="post" class="d-inline">
                                    <?php echo tokenHere(); ?>
                                    <input type="hidden" name="sshid" value="<?php echo $conn->sshid ?>">
                                    <button type="submit" name="delete_connection" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                                  </form>
                                </td>
                              </tr>
                            <?php endforeach; ?>
                          </tbody>
                        </table>
                      </div>
                    <?php else: ?>
                      <p>No SSH connections found. Add one using the form above.</p>
                    <?php endif; ?>
                  </div>
                </div>
              </div>

              <!-- Keys Tab -->
              <div class="tab-pane fade <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'keys') ? 'show active' : ''; ?>" id="keys" role="tabpanel">
                <h3>SSH Public Keys</h3>

                <!-- Add Key Form -->
                <div class="card mb-4">
                  <div class="card-header">
                    <h5>Add New Public Key</h5>
                  </div>
                  <div class="card-body">
                    <form action="" method="post">
                      <?php echo tokenHere(); ?>
                      <div class="mb-3">
                        <label for="name">Key Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                        <small class="form-text text-muted">A descriptive name for this key</small>
                      </div>
                      <div class="mb-3">
                        <label for="keytype">Key Type</label>
                        <select class="form-control" id="keytype" name="keytype">
                          <option value="rsa">RSA</option>
                          <option value="dsa">DSA</option>
                          <option value="ed25519">ED25519</option>
                          <option value="ecdsa">ECDSA</option>
                        </select>
                      </div>
                      <div class="mb-3">
                        <label for="pubkey">Public Key</label>
                        <textarea class="form-control" id="pubkey" name="pubkey" rows="4" required></textarea>
                        <small class="form-text text-muted">The contents of your public key file (e.g., id_rsa.pub)</small>
                      </div>
                      <button type="submit" name="add_key" class="btn btn-primary">Add Key</button>
                    </form>
                  </div>
                </div>

                <!-- Key List -->
                <div class="card">
                  <div class="card-header">
                    <h5>Your SSH Public Keys</h5>
                  </div>
                  <div class="card-body">
                    <?php if (count($ssh_keys) > 0): ?>
                      <div class="table-responsive">
                        <table class="table table-striped">
                          <thead>
                            <tr>
                              <th>Name</th>
                              <th>Type</th>
                              <th>Public Key</th>
                              <th>Actions</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php foreach ($ssh_keys as $key): ?>
                              <tr>
                                <td><?php echo $key->name ?></td>
                                <td><?php echo strtoupper($key->keytype) ?></td>
                                <td>
                                  <div class="text-truncate" style="max-width: 350px;">
                                    <?php echo $key->pubkey ?>
                                  </div>
                                  <button type="button" class="btn btn-sm btn-info mt-1" onclick="copyToClipboard('<?php echo htmlspecialchars($key->pubkey, ENT_QUOTES) ?>')">Copy</button>
                                </td>
                                <td>
                                  <form action="" method="post">
                                    <?php echo tokenHere(); ?>
                                    <input type="hidden" name="authid" value="<?php echo $key->authid ?>">
                                    <button type="submit" name="delete_key" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                                  </form>
                                </td>
                              </tr>
                            <?php endforeach; ?>
                          </tbody>
                        </table>
                      </div>
                    <?php else: ?>
                      <p>No SSH keys found. Add one using the form above.</p>
                    <?php endif; ?>
                  </div>
                </div>
              </div>

              <!-- Groups Tab -->
              <div class="tab-pane fade <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'groups' || isset($_GET['edit_group'])) ? 'show active' : ''; ?>" id="groups" role="tabpanel">
                <h3><?php echo $edit_group ? 'Edit SSH Group' : 'SSH Groups'; ?></h3>

                <!-- Add/Edit Group Form -->
                <div class="card mb-4">
                  <div class="card-header">
                    <h5><?php echo $edit_group ? 'Edit Group' : 'Add New Group'; ?></h5>
                  </div>
                  <div class="card-body">
                    <form action="" method="post">
                      <?php echo tokenHere(); ?>
                      <?php if ($edit_group): ?>
                        <input type="hidden" name="sshgroup" value="<?php echo $edit_group->sshgroup; ?>">
                      <?php endif; ?>
                      <div class="mb-3">
                        <label for="group_name">Group Name</label>
                        <input type="text" class="form-control" id="group_name" name="group_name" value="<?php echo $edit_group ? $edit_group->name : ''; ?>" required>
                        <small class="form-text text-muted">A descriptive name for this group</small>
                      </div>
                      <button type="submit" name="<?php echo $edit_group ? 'update_group' : 'add_group'; ?>" class="btn btn-primary">
                        <?php echo $edit_group ? 'Update Group' : 'Add Group'; ?>
                      </button>
                      <?php if ($edit_group): ?>
                        <a href="./?tab=groups" class="btn btn-secondary">Cancel</a>
                      <?php endif; ?>
                    </form>
                  </div>
                </div>

                <!-- Group List -->
                <div class="card">
                  <div class="card-header">
                    <h5>Your SSH Groups</h5>
                  </div>
                  <div class="card-body">
                    <?php if (count($ssh_groups) > 0): ?>
                      <div class="table-responsive">
                        <table class="table table-striped">
                          <thead>
                            <tr>
                              <th>Group Name</th>
                              <th>Connections</th>
                              <th>Actions</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php foreach ($ssh_groups as $group): ?>
                              <tr>
                                <td><?php echo is_object($group) ? ucwords($group->name) : ucwords($group); ?></td>
                                <td>
                                  <?php
                                  // Count connections in this group
                                  $connection_count = 0;
                                  foreach ($ssh_connections as $conn) {
                                    if ($conn->sshgroup == $group->sshgroup) {
                                      $connection_count++;
                                    }
                                  }
                                  echo $connection_count;
                                  ?>
                                </td>
                                <td>
                                  <a href="./?edit_group=<?php echo $group->sshgroup ?>" class="btn btn-sm btn-primary">Edit</a>
                                  <form action="" method="post" class="d-inline">
                                    <?php echo tokenHere(); ?>
                                    <input type="hidden" name="sshgroup" value="<?php echo $group->sshgroup ?>">
                                    <button type="submit" name="delete_group" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure? This will not delete connections in this group.')">Delete</button>
                                  </form>
                                </td>
                              </tr>
                            <?php endforeach; ?>
                          </tbody>
                        </table>
                      </div>
                    <?php else: ?>
                      <p>No SSH groups found. Add one using the form above.</p>
                    <?php endif; ?>
                  </div>
                </div>
              </div>

              <!-- Export Tab -->
              <div class="tab-pane fade <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'export') ? 'show active' : ''; ?>" id="export" role="tabpanel">
                <h3>Export SSH Config</h3>

                <div class="card">
                  <div class="card-body">
                    <p>Generate and download your SSH config file based on your stored connections.</p>
                    <form action="" method="post">
                      <?php echo tokenHere(); ?>
                      <button type="submit" name="export_config" class="btn btn-primary">Generate SSH Config</button>
                    </form>

                    <div class="mt-4">
                      <h5>Usage Instructions</h5>
                      <ol>
                        <li>Generate your SSH config file using the button above.</li>
                        <li>Download the generated file.</li>
                        <li>Place the file at <code>~/.ssh/config</code> on your system.</li>
                        <li>If you already have a config file, you may want to back it up first or append the content.</li>
                        <li>You can now connect to your servers using just the aliases: <code>ssh server-alias</code></li>
                      </ol>
                    </div>
                  </div>
                </div>
                <div class="row">
                  <div class="col-sm-6">

                  </div>
                </div>
              </div>

              <!-- General Tab -->
              <div class="tab-pane fade <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'general') ? 'show active' : ''; ?>" id="general" role="tabpanel">
                <h3>General SSH Settings</h3>

                <div class="card">
                  <div class="card-header">
                    <h5>Default SSH Configuration</h5>
                  </div>
                  <div class="card-body">
                    <form action="" method="post">
                      <?php echo tokenHere(); ?>
                      <div class="row">
                        <div class="col-sm-6">
                          <div class="form-group mb-3">
                            <label for="compression">Compression:</label>
                            <select class="form-control" id="compression" name="compression">
                              <option value="yes" <?= $userConfig->compression == 'yes' ? 'selected' : '' ?>>Yes</option>
                              <option value="no" <?= $userConfig->compression == 'no' ? 'selected' : '' ?>>No</option>
                            </select>
                          </div>
                        </div>
                        <div class="col-sm-6">
                          <div class="form-group mb-3">
                            <label for="pubkeyauthentication">Pubkey Authentication:</label>
                            <select class="form-control" id="pubkeyauthentication" name="pubkeyauthentication">
                              <option value="yes" <?= $userConfig->pubkeyauthentication == 'yes' ? 'selected' : '' ?>>Yes</option>
                              <option value="no" <?= $userConfig->pubkeyauthentication == 'no' ? 'selected' : '' ?>>No</option>
                            </select>
                          </div>
                        </div>
                      </div>
                      <div class="row">
                        <div class="col-sm-6">
                          <div class="form-group mb-3">
                            <label for="stricthostkeychecking">Strict Host Key Checking:</label>
                            <select class="form-control" id="stricthostkeychecking" name="stricthostkeychecking">
                              <option value="yes" <?= $userConfig->stricthostkeychecking == 'yes' ? 'selected' : '' ?>>Yes</option>
                              <option value="no" <?= $userConfig->stricthostkeychecking == 'no' ? 'selected' : '' ?>>No</option>
                              <option value="ask" <?= $userConfig->stricthostkeychecking == 'ask' ? 'selected' : '' ?>>Ask</option>
                            </select>
                          </div>
                        </div>
                        <div class="col-sm-6">
                          <div class="form-group mb-3">
                            <label for="loglevel">Log Level:</label>
                            <select class="form-control" id="loglevel" name="loglevel">
                              <option value="QUIET" <?= $userConfig->loglevel == 'QUIET' ? 'selected' : '' ?>>QUIET</option>
                              <option value="FATAL" <?= $userConfig->loglevel == 'FATAL' ? 'selected' : '' ?>>FATAL</option>
                              <option value="ERROR" <?= $userConfig->loglevel == 'ERROR' ? 'selected' : '' ?>>ERROR</option>
                              <option value="INFO" <?= $userConfig->loglevel == 'INFO' ? 'selected' : '' ?>>INFO</option>
                              <option value="VERBOSE" <?= $userConfig->loglevel == 'VERBOSE' ? 'selected' : '' ?>>VERBOSE</option>
                              <option value="DEBUG" <?= $userConfig->loglevel == 'DEBUG' ? 'selected' : '' ?>>DEBUG</option>
                            </select>
                          </div>
                        </div>
                      </div>
                      <div class="row">
                        <div class="col-sm-6">
                          <div class="form-group mb-3">
                            <label for="userknownhostsfile">User Known Hosts File:</label>
                            <input type="text" class="form-control" id="userknownhostsfile" name="userknownhostsfile" value="<?= $userConfig->userknownhostsfile ?>">
                            <small class="form-text text-muted">Default: ~/.ssh/known_hosts</small>
                          </div>
                        </div>
                      </div>

                      <h4>Cryptographic Settings</h4>
                      <div class="row">
                        <div class="col-sm-6">
                          <div class="form-group mb-3">
                            <label for="ciphers">Ciphers:</label>
                            <input type="text" class="form-control" id="ciphers" name="ciphers" value="<?= $userConfig->ciphers ?>">
                            <small class="form-text text-muted">Example: aes128-ctr,aes192-ctr,aes256-ctr</small>
                          </div>
                        </div>
                        <div class="col-sm-6">
                          <div class="form-group mb-3">
                            <label for="macs">MACs:</label>
                            <input type="text" class="form-control" id="macs" name="macs" value="<?= $userConfig->macs ?>">
                            <small class="form-text text-muted">Example: hmac-sha2-512,hmac-sha2-256</small>
                          </div>
                        </div>
                      </div>
                      <div class="row">
                        <div class="col-sm-6">
                          <div class="form-group mb-3">
                            <label for="hostkeyalgorithms">Host Key Algorithms:</label>
                            <input type="text" class="form-control" id="hostkeyalgorithms" name="hostkeyalgorithms" value="<?= $userConfig->hostkeyalgorithms ?>">
                            <small class="form-text text-muted">Example: ssh-ed25519,rsa-sha2-512,rsa-sha2-256</small>
                          </div>
                        </div>
                        <div class="col-sm-6">
                          <div class="form-group mb-3">
                            <label for="kexalgorithms">Key Exchange Algorithms:</label>
                            <input type="text" class="form-control" id="kexalgorithms" name="kexalgorithms" value="<?= $userConfig->kexalgorithms ?>">
                            <small class="form-text text-muted">Example: curve25519-sha256,diffie-hellman-group-exchange-sha256</small>
                          </div>
                        </div>
                      </div>
                      <div class="row">
                        <div class="col-sm-6">
                          <div class="form-group mb-3">
                            <label for="identityfile">Default Identity File:</label>
                            <input type="text" class="form-control" id="identityfile" name="identityfile" value="<?= $userConfig->identityfile ?>">
                            <small class="form-text text-muted">Default: ~/.ssh/id_rsa</small>
                          </div>
                        </div>
                        <div class="col-sm-6">
                          <div class="form-group mb-3">
                            <label for="tcpkeepalive">TCP KeepAlive:</label>
                            <select class="form-control" id="tcpkeepalive" name="tcpkeepalive">
                              <option value="yes" <?= $userConfig->tcpkeepalive == 'yes' ? 'selected' : '' ?>>Yes</option>
                              <option value="no" <?= $userConfig->tcpkeepalive == 'no' ? 'selected' : '' ?>>No</option>
                            </select>
                          </div>
                        </div>
                      </div>
                      <div class="row">
                        <div class="col-sm-6">
                          <div class="form-group mb-3">
                            <label for="forwardagent">Forward Agent:</label>
                            <select class="form-control" id="forwardagent" name="forwardagent">
                              <option value="yes" <?= $userConfig->forwardagent == 'yes' ? 'selected' : '' ?>>Yes</option>
                              <option value="no" <?= $userConfig->forwardagent == 'no' ? 'selected' : '' ?>>No</option>
                            </select>
                          </div>
                        </div>
                        <div class="col-sm-6">
                          <div class="form-group mb-3">
                            <label for="serveraliveinterval">Server Alive Interval:</label>
                            <input type="number" class="form-control" id="serveraliveinterval" name="serveraliveinterval" value="<?= $userConfig->serveraliveinterval ?>">
                            <small class="form-text text-muted">Default: 10</small>
                          </div>
                        </div>
                      </div>
                      <div class="row">
                        <div class="col-12">
                          <div class="form-group">
                            <button type="submit" name="update_general" class="btn btn-primary">Save General Configuration</button>
                          </div>
                        </div>
                      </div>
                    </form>
                  </div>
                </div>
              </div>

              <!-- Advanced Tab -->
              <div class="tab-pane fade <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'advanced') ? 'show active' : ''; ?>" id="advanced" role="tabpanel">
                <h3>Advanced SSH Configuration</h3>

                <div class="card">
                  <div class="card-header">
                    <h5>Custom SSH Directives</h5>
                  </div>
                  <div class="card-body">
                    <form action="" method="post">
                      <input type="hidden" name="formid" value="advanced">
                      <?php echo tokenHere(); ?>
                      <div class="form-group mb-3">
                        <label for="advanced">Custom SSH Directives:</label>
                        <?php
                        $existingAdvanced = loadAdvancedInfo($userid);
                        ?>
                        <textarea class="form-control" id="advanced" name="advanced" rows="10"><?= htmlspecialchars($existingAdvanced, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></textarea>
                        <small class="form-text text-muted">
                          Enter custom SSH directives here. These will be included in your SSH config file.
                          <br>Example: <code><br>Host 172.16.5.* <br> &nbsp;&nbsp;&nbsp;ForwardX11 yes</code> <br> or <code>ServerAliveCountMax 5</code>
                        </small>
                      </div>
                      <div class="form-group">
                        <button type="submit" name="update_advanced" class="btn btn-primary">Save Advanced Configuration</button>
                      </div>
                    </form>
                  </div>
                </div>

                <div class="card mt-4">
                  <div class="card-header">
                    <h5>Advanced SSH Configuration Help</h5>
                  </div>
                  <div class="card-body">
                    <p>The advanced configuration section allows you to add custom SSH directives that aren't covered by the standard options.</p>
                    <p>Some examples of advanced directives you might want to use:</p>
                    <ul>
                      <li><code>AddKeysToAgent yes</code> - Automatically adds keys to the SSH agent when they're used</li>
                      <li><code>ControlMaster auto</code> - Enables SSH connection sharing</li>
                      <li><code>ControlPath ~/.ssh/control-%h-%p-%r</code> - Path for control sockets</li>
                      <li><code>ControlPersist 10m</code> - Keep master connection open for 10 minutes</li>
                      <li><code>VisualHostKey yes</code> - Shows ASCII art representing the host key</li>
                      <li><code>HashKnownHosts yes</code> - Hash host names in known_hosts file</li>
                    </ul>
                    <p>These directives can significantly improve your SSH experience by enabling advanced features.</p>
                  </div>
                </div>
              </div>

              <!-- Help Tab would go here -->
              <div class="tab-pane fade <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'help') ? 'show active' : ''; ?>" id="help" role="tabpanel">
                <h3>SSH Spice Help</h3>

                <div class="card mb-4">
                  <div class="card-header">
                    <h5>Instructions for Adding SSH Configuration Entries</h5>
                  </div>
                  <div class="card-body">
                    <ol>
                      <li><strong>Host:</strong> Provide a short, descriptive name for the server.</li>
                      <li><strong>Hostname:</strong> Enter the IP address or full DNS name of the server.</li>
                      <li><strong>Port:</strong> Specify the port number if different from the default (22).</li>
                      <li><strong>Username:</strong> Provide the username to use for SSH login.</li>
                      <li><strong>Identity File:</strong> (Optional) Path to your private key file, e.g., <code>~/.ssh/id_rsa</code>.</li>
                      <li><strong>Proxy Jump:</strong> (Optional) Specify a bastion host if needed (format: <code>user@bastionhost</code>).</li>
                      <li><strong>Notes:</strong> Add any notes that might be useful, like server purpose, special credentials, etc.</li>
                    </ol>
                    <p>Use the "Export" tab to generate a formatted SSH config file from your entries.</p>
                  </div>
                </div>

                <div class="card mb-4">
                  <div class="card-header">
                    <h5>Common SSH Options</h5>
                  </div>
                  <div class="card-body">
                    <div class="table-responsive">
                      <table class="table table-striped">
                        <thead>
                          <tr>
                            <th>Option</th>
                            <th>Description</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr>
                            <td>IdentityFile</td>
                            <td>Specifies a file from which the user's private key is read</td>
                          </tr>
                          <tr>
                            <td>ForwardAgent</td>
                            <td>Specifies whether the connection to the authentication agent will be forwarded to the remote machine</td>
                          </tr>
                          <tr>
                            <td>ForwardX11</td>
                            <td>Specifies whether X11 connections will be automatically redirected over the secure channel</td>
                          </tr>
                          <tr>
                            <td>ProxyJump</td>
                            <td>Specifies one or more jump proxies as hostname[:port]</td>
                          </tr>
                          <tr>
                            <td>LocalForward</td>
                            <td>Specifies that a TCP port on the local machine be forwarded over the secure channel to the specified host and port</td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>

                <div class="card">
                  <div class="card-header">
                    <h5>Using the Plugin API</h5>
                  </div>
                  <div class="card-body">
                    <p>You can programmatically add SSH connections using the <code>sshSpice()</code> function:</p>
                    <pre><code>// Example usage
$connection = [
    'host' => 'web-server',
    'hostname' => 'example.com',
    'user' => 'admin',
    'port' => 22,
    'sshgroup' => 'web',
    'identityfile' => 'id_rsa_web'
];

$result = sshSpice($connection);</code></pre>

                    <p>You can also add SSH keys programmatically:</p>
                    <pre><code>// Example usage
$key = [
    'name' => 'Web Server Key',
    'keytype' => 'ed25519',
    'pubkey' => 'ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIJhvJ5dVw...'
];

$result = sshAddKey($key);</code></pre>

                    <p>To manage SSH groups:</p>
                    <pre><code>// Add a new group
$group = [
    'name' => 'Development Servers'
];
$result = sshAddGroup($group);

// Update a group
$group = [
    'sshgroup' => 1, // Group ID
    'name' => 'Production Servers'
];
$result = sshUpdateGroup($group);

// Delete a group
$result = sshDeleteGroup(1); // Group ID</code></pre>

                    <p>To export the SSH config file:</p>
                    <pre><code>$configPath = sshExportConfig();</code></pre>
                  </div>
                </div>
              </div>

              <script>
                function copyToClipboard(text) {
                  const textarea = document.createElement('textarea');
                  textarea.value = text;
                  document.body.appendChild(textarea);
                  textarea.select();
                  document.execCommand('copy');
                  document.body.removeChild(textarea);
                  alert('Public key copied to clipboard!');
                }

                // Initialize Bootstrap tabs
                document.addEventListener('DOMContentLoaded', function() {
                  // Check if Bootstrap is available
                  if (typeof bootstrap !== 'undefined') {
                    // Create tab instances
                    const triggerTabList = [].slice.call(document.querySelectorAll('#ssh-tabs a'))
                    triggerTabList.forEach(function(triggerEl) {
                      const tabTrigger = new bootstrap.Tab(triggerEl)

                      triggerEl.addEventListener('click', function(event) {
                        event.preventDefault()
                        tabTrigger.show()
                      })
                    })

                    // Show the first tab by default
                    const activeTab = document.querySelector('#ssh-tabs a.active')
                    if (activeTab) {
                      const tab = new bootstrap.Tab(activeTab)
                      tab.show()
                    }
                  } else {
                    console.error('Bootstrap JavaScript is not loaded. Tabs may not function properly.');
                  }
                });
              </script>

              <?php
              // Close the page for direct access
              if (!$from_account) {
              ?>
            </div> <!-- /.container -->
          </div> <!-- /#page-wrapper -->
        <?php
                include($abs_us_root . $us_url_root . 'users/includes/html_footer.php');
              }
        ?>

        <!-- Place any per-page javascript here -->
        <?php require_once $abs_us_root . $us_url_root . 'users/includes/html_footer.php'; ?>