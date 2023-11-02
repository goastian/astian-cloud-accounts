<template>
	<div>
		<section id="main" class="register-page">
			<div id="registration">
				<RegistrationForm v-if="showRegistrationForm" v-model="formData" @form-submitted="submitSignupForm" />
				<CaptchaForm v-if="showCaptchaForm" v-model="formData" @form-submitted="submitCaptchaForm" />
				<RecoveryEmailForm v-if="showRecoverEmailForm" v-model="formData" @form-submitted="submitRecoveryEmailForm" />
				<SuccessSection v-if="showSuccessSection" />
				<pre>{{ formData }}</pre>
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
			domain: window.location.host,
			showRegistrationForm: true,
			showCaptchaForm: false,
			showRecoverEmailForm: false,
			showSuccessSection: false,
			termsURL: 'http://murena.io/apps/terms_of_service/en/termsandconditions',
			supportURL: 'https://doc.e.foundation/support-topics/configure-email',
			validation: {
				isDisplaynameEmpty: false,
				isUsernameEmpty: false,
				isUsernameNotValid: false,
				isPasswordEmpty: false,
				isPasswordNotValid: false,
				isRepasswordEmpty: false,
				isRePasswordMatched: false,
				isHumanverificationEmpty: false,
				isHumanverificationNotMatched: false,
				isAccepttnsEmpty: false,
				isEmailEmpty: false,
			},
			passworderrors: [],
			passwordrules: [
				{ message: 'At least 6 characters.', regex: /.{6,}/ },
				{ message: 'Lowercase letters: a-z.', regex: /[a-z]+/ },
				{ message: 'Uppercase letters: a-z.', regex: /[A-Z]+/ },
				{ message: 'One number required.', regex: /[0-9]+/ },
			],
			isUsernameAvailable: false,
			usernameValidationMessage: '',
			captcha: [],
			num1: '',
			num2: '',
			operator: '',
			captchaResult: '',
			operators: ['+', '-'],
			languages: [
				{ code: 'en', name: 'English' },
				{ code: 'de', name: 'German' },
				{ code: 'fr', name: 'French' },
				{ code: 'it', name: 'Italian' },
				{ code: 'es', name: 'Spanish' },
			],
			titles: {
				createMurenaAccount: 'Create Murena Account',
				captchaVerification: 'Captcha Verification',
				recoveryEmailForm1: 'For security reasons you need to set a recovery address for your Murena Cloud account.',
				recoveryEmailForm2: 'As long as you don\'t, you\'ll have limited access to your account.',
				readAndAcceptTOS: 'I have read and accept the <a href=\'__termsURL__\' target=\'_blank\'>Terms of Service</a>.',
			},
			buttons: {
				createMyAccount: 'Create My Account',
				verify: 'Verify',
				later: 'Later',
				setRecoverEmail: 'Set my recovery email address',
				useMyAccountNow: 'Use My Account Now',
			},
			labels: {
				displayName: 'Display name',
				userName: 'Username',
				enterPassword: 'Enter Password',
				humanVefication: 'Human Verification',
				recoveryEmail: 'Recovery Email',
				newsletter_product: 'I want to receive news about Murena products and promotions',
				newsletter_eos: 'I want to receive news about /e/OS',
			},
			placeholders: {
				displayName: 'Your name as shown to others',
				userName: 'Username',
				enterPassword: 'Password',
				confirmPassword: 'Confirm',
				humanVefication: 'Human Verification',
				recoveryEmail: 'Recovery Email',
			},
			errors: {
				displayName: 'Display name is required.',
				userName: 'Username is required.',
				userNameInvalid: 'Username must consist of letters, numbers, hyphens, and underscores only.',
				userNameLength: 'Username must be at least 3 characters long.',
				userNameTaken: 'Username is already taken.',
				password: 'Password is required.',
				confirmPassword: 'Confirm password is required.',
				passwordNotMatched: 'The confirm password does not match the password.',
				humanVefication: 'Human Verification is required.',
				humanVeficationNotCorrect: 'Human Verification code is not correct.',
				recoveryEmail: 'Recovery Email is required.',
				recoveryEmailTaken: 'An account with the recovery address already exists.',
				acceptTOS: 'You must read and accept the Terms of Service to create your account.',
			},
			success: {
				usernameAvailable: 'Available!',
				successMessage: 'Success!',
				accountCreated: 'Your <b>__username__@__domain__</b> account was successfully created.',
				supportMessage: 'If you want to use your murena.io email in a mail app like Thunderbird, Outlook or another, please visit <a href=\'__supportURL__\'>this page</a>.',
			},
			others: {
				somethingWentWrong: 'Something went wrong.',
			},
		}
	},
	created() {
		this.createCaptcha()
		const readAndAcceptTOS = this.getLocalizedText(this.titles.readAndAcceptTOS)
		this.titles.readAndAcceptTOS = readAndAcceptTOS.replace('__termsURL__', this.termsURL)
		const currentURL = window.location.href
		const urlSegments = currentURL.split('/')
		this.selectedLanguage = urlSegments[urlSegments.length - 2]
	},
	methods: {
		validateForm(fieldsToValidate) {

			fieldsToValidate.forEach(field => {
				this.validation[`is${field.charAt(0).toUpperCase() + field.slice(1)}Empty`] = this.formData[field] === ''
			})
			if (fieldsToValidate.includes('password')) {
				this.passwordValidation()
			}
			if (fieldsToValidate.includes('repassword')) {
				this.validation.isRePasswordMatched = this.formData.repassword !== this.formData.password
			}
			if (fieldsToValidate.includes('humanverification')) {
				this.checkAnswer()
			}
			if (fieldsToValidate.includes('termsandservices')) {
				this.validation.isAccepttnsEmpty = !this.formData.accepttns
			}
			if (fieldsToValidate.includes('username')) {
				this.validateUsername()
			}
		},
		passwordValidation() {
			this.passworderrors = []
			this.validation.isPasswordNotValid = false
			if (!this.password) {
				for (const condition of this.passwordrules) {
					if (!condition.regex.test(this.formData.password)) {
						this.passworderrors.push(condition.message)
						this.validation.isPasswordNotValid = true
					}
				}
			}
		},
		submitFormTemp() {
			// Access the updated formData here from the parent component
			// console.log('Form data submitted:', this.formData)
			// Here you can perform any additional operations with the form data
			// For example, sending it to an API or performing client-side validations.
		},
		submitSignupForm() {
			this.validateForm(['displayname', 'username', 'password', 'repassword', 'termsandservices'])

			const isFormValid = Object.values(this.validation).every(value => !value)

			if (isFormValid) {
				this.showRegistrationForm = false
				this.showCaptchaForm = true
				this.showRecoverEmailForm = false
			}
		},
		submitCaptchaForm() {
			this.validateForm(['humanverification'])
			const isFormValid = Object.values(this.validation).every(value => !value)
			if (isFormValid) {
				this.showRegistrationForm = false
				this.showCaptchaForm = false
				this.showRecoverEmailForm = true
			}
		},
		submitRecoveryEmailForm(setrecoveryemail) {
			let isFormValid = true
			if (setrecoveryemail) {
				this.validateForm(['email'])
				isFormValid = Object.values(this.validation).every(value => !value)
			} else {
				this.email = ''
			}
			if (isFormValid) {
				const data = {
					displayname: this.formData.displayname,
					username: this.formData.username,
					password: this.formData.password,
					email: this.formData.email,
					language: this.formData.selectedLanguage,
					newsletterEOS: this.formData.newsletter_eos,
					newsletterProduct: this.formData.newsletter_product,
				}
				this.submitForm(data)
			}
		},
		validateUsername() {
			const usernamePattern = /^[a-zA-Z0-9_-]+$/
			const minCharacterCount = 3
			this.validation.isUsernameNotValid = false
			if (!usernamePattern.test(this.formData.username) || this.formData.username.length < minCharacterCount) {
				if (!usernamePattern.test(this.formData.username)) {
					this.usernameValidationMessage = this.errors.userNameInvalid
				} else {
					this.usernameValidationMessage = this.errors.userNameLength
				}
				this.validation.isUsernameNotValid = true
			} else {
				this.checkUsername()
			}
		},
		async checkUsername() {
			const data = {
				username: this.formData.username,
			}
			this.isUsernameAvailable = false
			const url = generateUrl(`/apps/${this.appName}/accounts/check_username_available`)
			try {
				const response = await Axios.post(url, data)
				if (response.status === 409) {
					this.validation.isUsernameNotValid = true
					this.usernameValidationMessage = this.errors.userNameTaken
				}
				if (response.status === 200) {
					this.isUsernameAvailable = true
				}
			} catch (error) {
				this.validation.isUsernameNotValid = true
				if (error.response && error.response.status === 409) {
					this.usernameValidationMessage = this.errors.userNameTaken
				} else {
					this.usernameValidationMessage = this.others.somethingWentWrong
				}
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

					let accountCreated = this.getLocalizedText(this.success.accountCreated)
					accountCreated = accountCreated.replace('__username__', this.formData.username)
					this.success.accountCreated = accountCreated.replace('__domain__', this.domain)

					const supportMessage = this.getLocalizedText(this.success.supportMessage)
					this.success.supportMessage = supportMessage.replace('__supportURL__', this.supportURL)

					this.showSuccessSection = true
				} else if (response.status === 409) {
					this.showMessage(response.data.message, 'error')
				} else {
					this.showMessage(this.others.somethingWentWrong, 'error')
				}
				this.setAllFieldsBlank()
			} catch (error) {
				if (error.response && error.response.status === 409) {
					this.showMessage(error.response.data.message, 'error')
				} else {
					this.showMessage(this.others.somethingWentWrong, 'error')
				}
			}
		},
		showMessage(message, type) {
			type === 'success' ? showSuccess(message) : showError(message)
		},
		getLocalizedText(text) {
			return t(this.appName, text)
		},
		setAllFieldsBlank() {
			this.formData.displayname = ''
			this.formData.email = ''
			this.formData.username = ''
			this.formData.password = ''
			this.formData.repassword = ''
			this.formData.humanverification = ''
		},
		createCaptcha() {
			this.num1 = this.getRandomCharacter()
			this.num2 = this.getRandomCharacter()
			const operators = this.operators
			this.operator = operators[Math.floor(Math.random() * operators.length)]
			this.captcha.push(this.num1)
			this.captcha.push(this.operator)
			this.captcha.push(this.num2)
		},
		getRandomCharacter() {
			const numbers = '123456789'
			const randomNumber = Math.floor(Math.random() * numbers.length)
			return numbers.charAt(randomNumber)
		},
		calculateResult() {
			const num1 = parseFloat(this.num1)
			const num2 = parseFloat(this.num2)

			switch (this.operator) {
			case '+':
				return num1 + num2
			case '-':
				return num1 - num2
			default:
				return NaN
			}
		},
		checkAnswer() {
			const result = this.calculateResult()
			this.captchaResult = parseInt(result, 10)
			if (parseInt(this.formData.humanverification, 10) !== this.captchaResult) {
				this.validation.isHumanverificationNotMatched = true
			} else {
				this.validation.isHumanverificationNotMatched = false
			}
		},
		getFontSize() {
			const fontVariations = [14, 16, 18, 20]
			return fontVariations[Math.floor(Math.random() * fontVariations.length)]
		},
		getRotationAngle() {
			const rotationVariations = [10, 5, -5, -10]
			return rotationVariations[Math.floor(Math.random() * rotationVariations.length)]
		},
		onLanguageChange() {
			window.location.href = window.location.origin + '/apps/' + APPLICATION_NAME + '/accounts/' + this.selectedLanguage + '/signup'
		},
		useMyAccount() {
			window.location.href = window.location.origin
		},
	},
}
</script>
<style scoped>
.display-flex {
	display: flex;
	justify-content: space-between;
}

.padding-0 {
	padding: 0;
}

section#main {
	overflow-x: hidden;
}

#fields li.action {
	display: block;
}
#fields input[type='checkbox'].checkbox + label:before{
	height: 15px;
    width: 15px;
}
/** mobile font sizes **/
@media screen and (max-width: 650px) {
	#fields .field .control input {
		padding-left: 2%;
		padding-right: 2%;
	}
}

@media screen and (max-width: 768px) {
	#successMessages {
		margin-left: 5%;
		margin-right: 5%;
	}

	#success h1 {
		font-size: 1.5em;
	}

	#inviteHeader,
	#registerHeading {
		font-size: 1.5em;
	}

	#fields {
		background-color: white;
	}

	#fields .field {
		font-size: 1.0em;
	}

	#fields .field .control {
		text-align: left;
	}

	#fields .field .control input {
		font-size: 1.0em;
		line-height: 1.0em;
	}

	#fields p {
		font-size: 1.0em;
	}

	input {
		font-size: 1.0em;
		line-height: 1.0em;
	}
}

#fields {
	margin: 10px;
}

#fields .control {
	text-align: left;
	margin-top: 10px;
	margin-bottom: 10px;
}

#fields input#username,
#fields input#new-password,
#fields input#repassword {
	width: 50%;
}

#fields input,
#fields input[type="password"] {
	background-color: var(--color-secondary-element);
	margin-bottom: 0;
	color: rgba(0, 0, 0, 0.8);
	display: block;
	width: 100%;
	font-size: 16px;
	line-height: 1.3em;
	transition: all 0.5s linear;
	border: 1px solid #E6E8E9;
	border-radius: 8px;
	padding: 10px 20px;
	margin-top: 10px;
	margin-bottom: 10px;
}

.username-group {
	display: flex;
}

#username-domain-div {
	display: flex;
	align-items: center;
}

#fields {
	background-color: white;
}

#fields .Password {
	max-width: unset;
	margin: unset;
}

#fields .field {
	font-size: 1.3em;
}

#fields p {
	font-size: 15px;
}

#captcha_img {
	font-size: 12px;
	width: 100%;
}

#inviteHeader,
#registerHeading {
	margin-bottom: 10%;
	font-size: 24px;
	text-align: left !important;
	font-weight: 500;
}

#currentLangImg {
	border-radius: 50%;
	margin: 0 auto;
	border: 0.1em transparent black;
	height: 24px;
	width: 24px;
	max-width: none;
}

#submitButton:hover {
	opacity: 0.9;
}

#fields label {
	color: #333333;
	font-size: 16px;
	font-weight: 900;
}

sup {
	color: #ff0000;
	font-weight: 500;
	font-size: 14px;
	padding-left: 3px;
}

#tos_div label {
	line-height: 1.5rem;
}

.validation-error {
	background: #ff0000;
    color: white;
    padding: 5px 10px;
    font-weight: 500;
    margin: 5px 0;
    border-radius: 5px;
    min-width: 70%;
    width: fit-content;
    display: flex;
    flex-direction: row;
}
p.validation-error:before {
    content: "\00d7";
    display: inline-block;
    font-size: 30px;
    margin: 0;
    padding-right: 5px;
}
.validation-warning{
	color: #ff0000;
    padding-left: 5px;
    font-weight: 500;
    display: flex;
    flex-direction: row;
}
p.validation-warning:before {
    content: "\00d7";
    display: inline-block;
    font-size: 30px;
    margin: 0;
    padding-right: 5px;
}

.validation-success{
	color: green;
    padding-left: 5px;
    font-weight: 500;
    display: flex;
    flex-direction: row;
}
p.validation-success:before {
    content: '\2713';
    display: inline-block;
    font-size: 20px;
    margin: 0;
    padding-right: 5px;
}

.btn-primary {
	width: 95%;
	background-color: var(--color-primary);
	color: white;
	border-color: var(--color-primary);
	font-size: large;
}

.btn-default{
	width: 95%;
	background-color: var(--color-warning);
	color: white;
	border-color: var(--color-warning);
	font-size: large;
}

@media screen and (max-width: 500px) {
	#main {
		padding: 0 1.5rem;
	}

	#inviteHeader,
	#registerHeading {
		font-size: 18px;
	}

	footer p {
		font-size: 10px;
	}
}

.np-captcha-section {
	display: flex;
	width: fit-content;
}

.np-captcha-container {
	background: #ffdada;
	width: max-content;
	height: 30px;
	margin: 0 auto;
	margin-bottom: 20px;
	padding: 10px;
}

.np-captcha {
	font-size: 24px;
	width: 200px;
	text-align: center;
}

.np-button {
	padding: 5px;
	background: #fff;
	border: 1px solid #eee;
	border-radius: 6px;
	font-size: 16px;
	margin: auto;
	min-width: 30px;
}

.np-captcha-character {
	display: inline-block;
	letter-spacing: 14px;
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
