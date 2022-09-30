<?php

return ['routes' => [
    ['name' => 'user#set_account_data', 'url' => '/api/set_account_data', 'verb' => 'POST'],
    ['name' => 'user#user_exists', 'url' => '/api/user_exists', 'verb' => 'POST'],
    ['name' => 'user#set_mail_quota_usage', 'url' => '/api/set_mail_quota_usage', 'verb' => 'POST'],
    ['name' => 'shop_account#set_shop_email_post_delete', 'url' => '/shop-accounts/set_shop_email_post_delete', 'verb' => 'POST' ],
    ['name' => 'shop_account#set_shop_delete_preference', 'url' => '/shop-accounts/set_shop_delete_preference', 'verb' => 'POST' ],
    ['name' => 'shop_account#get_order_info', 'url' => '/shop-accounts/order_info', 'verb' => 'GET'],
    [
        'name' => 'user#preflighted_cors', 'url' => '/api/{path}',
        'verb' => 'OPTIONS', 'requirements' => array('path' => '.+')
    ],
]];
