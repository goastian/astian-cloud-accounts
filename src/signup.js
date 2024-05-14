import Vue from 'vue'
import './common.js'
import Signup from './Signup.vue'
import router from './routes.js'

export default new Vue({
	el: '#ecloud-accounts-signup',
	router,
	render: h => h(Signup),
})
