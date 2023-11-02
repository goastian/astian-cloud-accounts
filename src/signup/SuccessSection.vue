<template>
	<div id="successSection">
		<section id="success" style="">
			<div id="successMessages" class="notification isa_success has-text-centered">
				<h3 class="success__title">
					{{ getLocalizedText('Success!') }}
				</h3>

				<p id="accountCreatedMsg" class="font-16">
					Your <b>{{ formData.username }}@{{ domain }}</b> account was successfully created.
				</p>

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
export default {
	props: {
		value: Object,
	},
	data() {
		return {
			domain: window.location.host,
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
