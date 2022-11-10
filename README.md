# Ecloud Accounts

- This app is used to integrate ecloud account creation with [welcome](https://gitlab.e.foundation/e/infra/docker-welcome)
- Options to be configured in your `config.php`:
```php
    'e_welcome_secret' => 'secret', // Secret to authenticate request to the welcome server
    'e_welcome_domain' => 'welcome.ecloud.global', // Domain of welcome server
    'ecloud-accounts' => [
        'secret' => 'ecloud-accounts-secret', // Secret for incoming requests to authenticate against
    ]
```

## User Account creation

- This plugin creates an endpoint `/apps/ecloud-accounts/api/set_account_data` that is to be used to set user's email, quota,recovery email and create the user's folder if necessary

## Drop account

- The drop account functionality plugin works in conjunction with the drop_account plugin : https://apps.nextcloud.com/apps/drop_account
- The app listens for user deletion event to handle proper deletion of user account in /e/ ecosystem 
- This plugin calls the postDelete.php script in the /e/ docker-welcome container 
- The e_welcome_secret is loaded in nextcloud's config file during ecloud-selfhosting installation. 

## Support

Please open issues here : https://gitlab.e.foundation/e/backlog/issues

## Dependancies

This plugin works in cunjunction with the drop_account plugin : https://apps.nextcloud.com/apps/drop_account

This plugin uses SRDI's [Simple and lightweight curl class](https://github.com/srdi/php-curl-class), under the The Unlicense license : https://github.com/srdi/php-curl-class/blob/master/LICENSE

## Beta User

- The app is using system values which are configured in `/config/config.php`. 
- Below keys to add:
1. beta_group_name
2. beta_gitlab_email_id

The values are:
```
'beta_group_name' => 'beta',
'beta_gitlab_email_id' => 'gitlab+e-infra-ecloud-beta-feedback-1361-issue-@e.email',
```
