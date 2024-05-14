import Vue from 'vue'
import VueRouter from 'vue-router'
import Signup from './Signup.vue'

Vue.use(VueRouter)

const routes = [
	{
		path: '/signup',
		name: 'Signup',
		component: () => Signup,
	},
]

const router = new VueRouter({
	mode: 'history',
	routes,
})

export default router
