<?php

return ['routes' => [
	['name' => 'user#set_account_data', 'url' => '/api/set_account_data', 'verb' => 'POST'],
	['name' => 'user#user_exists', 'url' => '/api/user_exists', 'verb' => 'POST'],
	['name' => 'user#set_mail_quota_usage', 'url' => '/api/set_mail_quota_usage', 'verb' => 'POST'],
	['name' => 'shop_account#set_shop_email_post_delete', 'url' => '/shop-accounts/set_shop_email_post_delete', 'verb' => 'POST' ],
	['name' => 'shop_account#set_shop_delete_preference', 'url' => '/shop-accounts/set_shop_delete_preference', 'verb' => 'POST' ],
	['name' => 'shop_account#get_shop_users', 'url' => '/shop-accounts/users', 'verb' => 'GET'],
	['name' => 'shop_account#check_shop_email_post_delete', 'url' => '/shop-accounts/check_shop_email_post_delete', 'verb' => 'GET'],
	[
		'name' => 'user#preflighted_cors', 'url' => '/api/{path}',
		'verb' => 'OPTIONS', 'requirements' => array('path' => '.+')
	],
	[
		'name' => 'beta_user#remove_user_in_group',
		'url' => '/beta/remove', 'verb' => 'DELETE'
	],
	[
		'name' => 'beta_user#add_user_in_group',
		'url' => '/beta/add', 'verb' => 'POST'
	],
	[
		'name' => 'beta_user#submit_issue',
		'url' => '/issue/submit', 'verb' => 'POST'
	],
	
	['name' => 'account#index', 'url' => '/accounts/{lang}/signup', 'verb' => 'GET'],
	['name' => 'account#create', 'url' => '/accounts/create', 'verb' => 'POST'],
	['name' => 'account#captcha', 'url' => '/accounts/captcha', 'verb' => 'GET'],
	['name' => 'account#verify_captcha', 'url' => '/accounts/verify_captcha', 'verb' => 'POST'],
	['name' => 'account#check_username_available', 'url' => '/accounts/check_username_available', 'verb' => 'POST'],

]];
