# Ecloud Accounts

- This app is used to integrate ecloud account creation with [welcome](https://gitlab.e.foundation/e/infra/docker-welcome)
- Options to be configured in your `config.php`:
```php
    'e_welcome_secret' => 'secret', // Secret to authenticate request to the welcome server
    'e_welcome_domain' => 'welcome.ecloud.global', // Domain of welcome server
    'user_folder_sharding' => false, // Whether or not user folder sharding has to be enabled
    'ecloud-accounts' => [
        'secret' => 'ecloud-accounts-secret', // Secret for incoming requests to authenticate against
        'realdatadirectory' => '/var/www/realdatadirectory' // Directory where folders for sharding are mounted
    ]
```

## User Account creation

- This plugin creates an endpoint `/apps/ecloud-accounts/api/set_account_data` that is to be used to set user's email, quota,recovery email and create the user's folder if necessary

## User folder sharding
- When user folder sharding is enabled, the user's folder is created in one of the folders in the specified "real" data directory and the folder is assigned to the user randomly
- Then a `symlink` is created linking the user's folder in the nextcloud data directory to the user's folder in the "real" data directory
- If the `user_folder_sharding` config key is set to `true`, ensure to set `realdatadirectory` config key in the `ecloud-accounts` configuration to the location where your folders are mounted 
- In case `user_folder_sharding` is not set in your `config.php`, it defaults to `false`

## Drop account

- The drop account functionality plugin works in conjunction with the drop_account plugin : https://apps.nextcloud.com/apps/drop_account
- The app listens for user deletion event to handle proper deletion of user account in /e/ ecosystem 
- This plugin calls the postDelete.php script in the /e/ docker-welcome container 
- The e_welcome_secret is loaded in nextcloud's config file during ecloud-selfhosting installation. 

## Support

Please open issues here : https://gitlab.e.foundation/e/management/issues

## Dependancies

This plugin works in cunjunction with the drop_account plugin : https://apps.nextcloud.com/apps/drop_account

This plugin uses SRDI's [Simple and lightweight curl class](https://github.com/srdi/php-curl-class), under the The Unlicense license : https://github.com/srdi/php-curl-class/blob/master/LICENSE