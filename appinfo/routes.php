<?php

return ['routes' => [
    ['name' => 'user#set_account_data', 'url' => '/api/set_account_data', 'verb' => 'POST'],
    ['name' => 'user#user_exists', 'url' => '/api/user_exists', 'verb' => 'POST'],
    ['name' => 'user#set_mail_quota_usage', 'url' => '/api/set_mail_quota_usage', 'verb' => 'POST'],
    [
        'name' => 'user#preflighted_cors', 'url' => '/api/{path}',
        'verb' => 'OPTIONS', 'requirements' => array('path' => '.+')
    ],
]];
