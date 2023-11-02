<template>
	<div id="recoveryEmailForm">
		<div class="">
			<h1 class="has-text-centered subtitle is-3">
				{{ getLocalizedText('For security reasons you need to set a recovery address for your Murena Cloud account.') }}
			</h1>
			<h1 class="has-text-centered subtitle is-3">
				{{ getLocalizedText('As long as you don\'t, you\'ll have limited access to your account.') }}
			</h1>
		</div>

		<div id="fields">
			<div class="field">
				<div class="control">
					<label>{{ getLocalizedText('Recovery Email') }}</label>
					<input id="email"
						v-model="email"
						name="email"
						type="email"
						class="form-input"
						:placeholder="getLocalizedText('Recovery Email')"
						@input="validateForm(['email'])">
					<p v-if="validation.isEmailEmpty" class="validation-warning">
						{{ getLocalizedText('Recovery Email is required.') }}
					</p>
				</div>
			</div>
		</div>

		<div id="groups" class="aliases-info display-flex">
			<button :wide="true"
				class="btn-default w-50"
				type="primary"
				@click="submitRecoveryEmailForm(false)">
				{{ getLocalizedText('Later') }}
			</button>
			<button :wide="true"
				class="btn-primary w-50"
				type="primary"
				@click="submitRecoveryEmailForm(true)">
				{{ getLocalizedText('Set my recovery email address') }}
			</button>
		</div>
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
