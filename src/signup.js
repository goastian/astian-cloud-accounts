import Vue from 'vue'
import './common.js'
import Signup from './Signup.vue'

const urlParams = new URLSearchParams(window.location.search)
const recoveryEmail = urlParams.get('recoveryEmail') ?? ''

export default new Vue({
	el: '#ecloud-accounts-signup',
	render: h => h(Signup, {
		props: {
		  recoveryEmail,
		},
	  }),
})
