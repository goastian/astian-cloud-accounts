// SPDX-FileCopyrightText: {{ app.author_name }} <{{ app.author_mail }}>
// SPDX-License-Identifier: {{ app.license }}
const webpackConfig = require('@nextcloud/webpack-vue-config')
const path = require('path')

module.exports = {
	...webpackConfig,
	entry: {
		'delete-shop-account-setting': path.join(__dirname, 'src/delete-shop-account-setting.js'),
		'delete-account-listeners': path.join(__dirname, 'src/delete-account-listeners.js'),
		'beta-user-setting': path.join(__dirname, 'src/beta-user-setting.js'),
		'signup': path.join(__dirname, 'src/signup.js'),
		'snappymail': path.join(__dirname, 'src/emailrecovery.js')
	},
}
