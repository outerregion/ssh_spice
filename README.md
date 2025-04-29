# SSH Spice - UserSpice Plugin

**SSH Spice** is a UserSpice plugin that provides a secure, UI-based way to manage SSH connections and credentials within the UserSpice dashboard. It is especially useful in support or IT environments where managing access and history of remote logins is necessary.

---

## 📦 Features

- Stores your ssh connections, with the ability to proxy host
- Store your public keys
- Set your general settings
- Export both a config file and an authorized_keys
- Set custom settings under advanced, such as for VPN's or Wildcard networks
- Lightweight and easy to integrate with existing UserSpice installations.

---

## 🧰 Installation

1. Copy the `ssh_spice` plugin folder to the `userspice/plugins/` directory.
2. Log in to your UserSpice admin panel.
3. Navigate to **Admin Dashboard > Plugins**.
4. Click **Install** on the SSH Spice plugin.

---

## ⚙️ Configuration

After installation, visit:

/userspice/plugins/ssh_spice/index.php


From there, you can configure:

- **SSH Connections**: This is where you are able to add, edit, and delete your ssh connections
- **Public SSH Keys**: Store your ssh public keys and use them to generate an authorized_keys file
- **Groups**: Groups allow you to seperate different ssh connections for readability
- **General settings**: The default settings for your config file. This is Host *.
- **Advanced**: This is where you are able to set custom configurations that are not available in the other sections. 

🛡️ Permissions

To control which users can use SSH Spice:

    Go to Admin Dashboard > Manage Permissions.

    Assign the ssh_spice permission to roles/groups you want to allow access.

    Only users with this permission can access the plugin page.


🔧 Requirements

    A working ssh client must be available on the system running UserSpice.


❓ Help

To display help directly within the plugin interface, click on the help tab

🧼 Uninstallation

    Go to Admin Dashboard > Plugins.

    Click Uninstall next to the SSH Spice plugin.

    Delete the ssh_spice folder from your plugins/ directory.

🧑‍💻 Author

    Developed by: Mike Jackson, with AI assistance

    License: MIT or apache. Use it as you like.

    Compatible with: UserSpice 5.x+

💡 Notes

This plugin does not store passwords or private keys. SSH authentication is expected to be handled externally (e.g., via SSH key agents or password prompts in terminal sessions).

