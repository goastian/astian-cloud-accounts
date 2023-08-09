import Vue from 'vue'
import './common.js'
import Signup from './CreateAccount.vue'

export default new Vue({
	el: '#ecloud-accounts-create-account',
	render: h => h(Signup),
})
