# Ecloud Drop Account

Allow user to delete his account by himself


This plugin works in cunjunction with the drop_account plugin by tcit : https://framagit.org/tcit/drop_user

Triggers nextcloud's postDelete User Hook to handle proper deletion of user account in /e/ ecosystem

This plugin calls the postDelete.php script in the /e/ docker-welcome container

The e_welcome_secret is loaded in nextcloud's config file during ecloud-selfhosting installation. 