const webpackConfig = require('@nextcloud/webpack-vue-config')
const path = require('path')

module.exports = {
	...webpackConfig,
	entry: {
		'personal-settings': path.join(__dirname, 'src/personal.js'),
	},
}
