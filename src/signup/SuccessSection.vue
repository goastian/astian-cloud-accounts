<template>
	<div id="successSection">
		<section id="success" style="">
			<div id="successMessages" class="notification isa_success has-text-centered">
				<h3 class="success__title">
					Success!
				</h3>

				<p id="accountCreatedMsg" class="font-16">
					Your <b>__username__@__domain__</b> account was successfully created.
				</p>

				<button :wide="true"
					class="btn-primary w-50"
					type="primary">
					{{ getLocalizedText('Use My Account Now') }}
				</button>
				<!-- @click="useMyAccount"> -->
				<p id="moreDetailMsg" class="font-16">
					If you want to use your murena.io email in a mail app like Thunderbird, Outlook or another, please visit <a href="__supportURL__">this page</a>.
				</p>
			</div>
		</section>
	</div>
</template>
<script>
export default {
	props: {
		value: Object,
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
	methods: {
		getLocalizedText(text) {
			return t('ecloud-accounts', text)
		},
	},
}
</script>
