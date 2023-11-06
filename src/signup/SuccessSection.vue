<template>
	<div id="successSection">
		<section id="success">
			<div id="successMessages" class="notification isa_success has-text-centered">
				<img :src="SuccessIcon">
				<h3 class="success__title">
					{{ getLocalizedText('Success!') }}
				</h3>
				<p id="accountCreatedMsg" class="font-16" v-html="accountCreatedMsg" />
				<button :wide="true"
					class="btn-primary w-50"
					type="primary"
					@click="useMyAccount">
					{{ getLocalizedText('Use My Account Now') }}
				</button>
				<p id="moreDetailMsg" class="font-16" v-html="getLocalizedText('If you want to use your murena.io email in a mail app like Thunderbird, Outlook or another, please visit <a href=\'https://doc.e.foundation/support-topics/configure-email\'>this page</a>.')" />
			</div>
		</section>
	</div>
</template>
<script>
const APPLICATION_NAME = 'ecloud-accounts'
export default {
	props: {
		value: Object,
	},
	data() {
		return {
			accountCreatedMsg: '',
			SuccessIcon: OC.generateUrl('/custom_apps/' + APPLICATION_NAME + '/img/success.svg'),
		}
	},
	computed: {
		formData: {
			get() {
				return this.value
			},
			set(formData) {
				this.$emit('input', formData)
			},
		},
	},
	created() {
		const domain = window.location.host
		const accountCreatedMsg = this.getLocalizedText('Your <b>__username__@__domain__</b> account was successfully created.')
		this.accountCreatedMsg = accountCreatedMsg.replace('__username__', this.formData.username).replace('__domain__', domain)
	},
	methods: {
		getLocalizedText(text) {
			return t(this.appName, text)
		},
		useMyAccount() {
			window.location.href = window.location.origin
		},

	},
}
</script>

<style scoped>

@media screen and (max-width: 768px) {
	#successMessages {
		margin-left: 5%;
		margin-right: 5%;
	}

	#success h1 {
		font-size: 1.5em;
	}
}
.btn-primary {
	width: 95%;
	background-color: var(--color-primary);
	color: white;
	border-color: var(--color-primary);
	font-size: large;
}

.success__title {
    font-family: 'Montserrat';
    font-style: normal;
    font-weight: 700;
    font-size: 24px;
    line-height: 150%;
    margin: 1em 0 0.5em 0;
    text-align: center;
}
.font-16{
	font-size: 16px;
}
</style>
