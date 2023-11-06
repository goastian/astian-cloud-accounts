<template>
	<div>
		<section id="main" class="register-page">
			<div id="registration">
				<RegistrationForm v-if="showRegistrationForm" v-model="formData" @form-submitted="submitRegistrationForm" />
				<CaptchaForm v-if="showCaptchaForm" v-model="formData" @form-submitted="submitCaptchaForm" />
				<RecoveryEmailForm v-if="showRecoverEmailForm" v-model="formData" @form-submitted="submitRecoveryEmailForm" />
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
				humanverification: '',
				email: '',
				accepttns: false,
				newsletter_eos: false,
				newsletter_product: false,
				selectedLanguage: 'en',
			},
			appName: APPLICATION_NAME,
			showRegistrationForm: true,
			showCaptchaForm: false,
			showRecoverEmailForm: false,
			showSuccessSection: false,
		}
	},
	methods: {
		submitRegistrationForm(data) {
			if (data.isFormValid) {
				this.showRegistrationForm = false
				this.showCaptchaForm = true
				this.showRecoverEmailForm = false
			}
		},
		submitCaptchaForm(data) {
			if (data.isFormValid) {
				this.showRegistrationForm = false
				this.showCaptchaForm = false
				this.showRecoverEmailForm = true
			}
		},
		submitRecoveryEmailForm(data) {
			if (data.isFormValid) {
				const data = {
					displayname: this.formData.displayname,
					username: this.formData.username,
					password: this.formData.password,
					email: this.formData.email,
					language: this.formData.selectedLanguage,
				}
				this.submitForm(data)
			}
		},
		async submitForm(data) {
			const url = generateUrl(`/apps/${this.appName}/accounts/create`)
			try {
				const response = await Axios.post(url, data)
				if (response.status === 200) {
					this.showRegistrationForm = false
					this.showCaptchaForm = false
					this.showRecoverEmailForm = false
					this.showSuccessSection = true
				} else if (response.status === 409) {
					this.showMessage(this.getLocalizedText(response.data.message), 'error')
				} else {
					this.showMessage(this.getLocalizedText('Something went wrong.'), 'error')
				}
				this.setAllFieldsBlank()
			} catch (error) {
				if (error.response && error.response.status === 409) {
					this.showMessage(this.getLocalizedText(error.response.data.message), 'error')
				} else {
					this.showMessage(this.getLocalizedText('Something went wrong.'), 'error')
				}
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
