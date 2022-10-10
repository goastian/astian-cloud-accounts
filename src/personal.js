import Vue from 'vue'
import './common.js'
import PersonalSettings from './PersonalSettings.vue'

export default new Vue({
	el: '#ecloud-accounts-settings',
	render: h => h(PersonalSettings),
})
