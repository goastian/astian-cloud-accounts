const webpackConfig = require('@nextcloud/webpack-vue-config')
const path = require('path')

module.exports = {
	...webpackConfig,
	entry: {
		'admin-settings': path.join(__dirname, 'src/admin.js'),
		'personal-settings': path.join(__dirname, 'src/personal.js'),
	},
}
