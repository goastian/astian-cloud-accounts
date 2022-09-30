const webpackConfig = require('@nextcloud/webpack-vue-config')
const path = require('path')

module.exports = {
	...webpackConfig,
	entry: {
		'personal-settings': path.join(__dirname, 'src/personal.js'),
		'delete-account-listeners': path.join(__dirname, 'src/delete-account-listeners.js')
	},
}
