# Ecloud Drop Account

Allow user to delete his account by himself


This plugin works in cunjunction with the drop_account plugin : https://apps.nextcloud.com/apps/drop_account

Triggers nextcloud's postDelete User Hook to handle proper deletion of user account in /e/ ecosystem

This plugin calls the postDelete.php script in the /e/ docker-welcome container

The e_welcome_secret is loaded in nextcloud's config file during ecloud-selfhosting installation. 

## Support

Please open issues here : https://gitlab.e.foundation/e/management/issues

## Dependancies

This plugin works in cunjunction with the drop_account plugin : https://apps.nextcloud.com/apps/drop_account

This plugin uses SRDI's [Simple and lightweight curl class](https://github.com/srdi/php-curl-class), under the The Unlicense license : https://github.com/srdi/php-curl-class/blob/master/LICENSE