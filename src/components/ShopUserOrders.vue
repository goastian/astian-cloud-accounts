<template>
	<div>
		<p><span v-if="orderCount > 0" v-html="ordersDescription" /></p>
		<p><b v-if="hasActiveSubscription" v-html="subscriptionDescription" /></p>
	</div>
</template>

<script>
const APPLICATION_NAME = 'ecloud-accounts'

export default {
	name: 'ShopUserOrders',
	props: {
		hasActiveSubscription: {
			type: Boolean,
			required: true,
		},
		email: {
			type: String,
			required: true,
		},
		orderCount: {
			type: Number,
			required: true,
		},
		id: {
			type: Number,
			required: true,
		},
		myOrdersUrl: {
			type: String,
			required: true,
		},
	},
	data() {
		return {
			ordersDescription: this.t(APPLICATION_NAME, "For your information you have %d order(s) in <a class='text-color-active' href='%s' target='_blank'>your account</a>."),
			subscriptionDescription: this.t(APPLICATION_NAME, 'A subscription is active in this account. Please cancel it or let it expire before deleting your account.'),
		}
	},
	mounted() {
		this.ordersDescription = this.ordersDescription.replace('%d', this.orderCount).replace('%s', this.myOrdersUrl)

	},
}
</script>
