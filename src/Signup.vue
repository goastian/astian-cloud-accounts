<template>
	<div>
		<section id="main" class="register-page">
			<div id="registration">
				<RegistrationForm v-if="showRegistrationForm" v-model="formData" @form-submitted="submitRegistrationForm" />
				<CaptchaForm v-if="showCaptchaForm" v-model="formData" @form-submitted="submitCaptchaForm" />
				<RecoveryEmailForm v-if="showRecoveryEmailForm" v-model="formData" @form-submitted="submitRecoveryEmailForm" />
				<SuccessSection v-if="showSuccessSection" v-model="formData" />
			</div>
		</section>
	</div>
</template>

<script>
import Axios from '@nextcloud/axios'
import { showSuccess, showError } from '@nextcloud/dialogs'
import { generateUrl } from '@nextcloud/router'
import RegistrationForm from './signup/RegistrationForm.vue'
import CaptchaForm from './signup/CaptchaForm.vue'
import RecoveryEmailForm from './signup/RecoveryEmailForm.vue'
import SuccessSection from './signup/SuccessSection.vue'

const APPLICATION_NAME = 'ecloud-accounts'

export default {
	name: 'Signup',
	components: {
		RegistrationForm,
		CaptchaForm,
		RecoveryEmailForm,
		SuccessSection,
	},
	data() {
		return {
			formData: {
				displayname: '',
				username: '',
				password: '',
				repassword: '',
				captchaInput: '',
				email: '',
				accepttns: false,
				newsletterEos: false,
				newsletterProduct: false,
				selectedLanguage: 'en',
			},
			appName: APPLICATION_NAME,
			showRegistrationForm: true,
			showCaptchaForm: false,
			showRecoveryEmailForm: false,
			showSuccessSection: false,
		}
	},
	mounted() {
		// Extracting the recovery email from the URL when the component is mounted
		const urlParams = new URLSearchParams(window.location.search)
		const recoveryEmail = urlParams.get('recoveryEmail')

		// Set formData.email directly to recoveryEmail
		this.formData.email = recoveryEmail || ''
	},
	methods: {
		submitRegistrationForm(data) {
			if (data.isFormValid) {
				this.showRegistrationForm = false
				this.showCaptchaForm = true
				this.showRecoveryEmailForm = false
			}
		},
		submitCaptchaForm(data) {
			if (data.isFormValid) {
				this.showRegistrationForm = false
				this.showCaptchaForm = false
				this.showRecoveryEmailForm = true
			}
		},
		submitRecoveryEmailForm(data) {
			if (data.isFormValid) {
				const data = {
					displayname: this.formData.displayname,
					username: this.formData.username,
					password: this.formData.password,
					recoveryEmail: this.formData.email,
					language: this.formData.selectedLanguage,
					newsletterEos: this.formData.newsletterEos,
					newsletterProduct: this.formData.newsletterProduct,
				}
				this.submitForm(data)
			}
		},
		async submitForm(data) {
			try {
				const url = generateUrl(`/apps/${this.appName}/accounts/create`)
				await Axios.post(url, data)

				// If the execution reaches here, the response status is in the 2xx range
				this.showRegistrationForm = false
				this.showCaptchaForm = false
				this.showRecoveryEmailForm = false
				this.showSuccessSection = true
			} catch (error) {
				// Handle network errors and unexpected response structures here
				// const errorMessage = error.response ? error.response.data.message : error.message
				this.showMessage(t(this.appName, 'Recovery email address cannot have murena domains.'), 'error')
			}
		},
		showMessage(message, type) {
			type === 'success' ? showSuccess(message) : showError(message)
		},
		getLocalizedText(text) {
			return t(this.appName, text)
		},
	},
}
</script>
<style scoped>
section#main {
	overflow-x: hidden;
}
@media screen and (max-width: 500px) {
	#main {
		padding: 0 1.5rem;
	}
}
</style>
