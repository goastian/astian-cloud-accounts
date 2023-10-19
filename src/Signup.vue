<template>
	<div>
		<section id="main" class="register-page">
			<div id="registration">
				<div v-if="showRegistrationForm" id="registrationForm">
					<div class="display-flex">
						<h1 id="registerHeading" class="has-text-centered subtitle is-3">
							{{ getLocalizedText('Create Murena Account') }}
						</h1>
						<div class="grid">
							<select v-model="selectedLanguage" class="padding-0" @change="onLanguageChange">
								<option v-for="language in languages" :key="language.code" :value="language.code">
									{{ language.name }}
								</option>
							</select>
						</div>
					</div>
					<div id="fields">
						<div class="field">
							<div class="control">
								<label>{{ getLocalizedText('Display name') }}<sup>*</sup></label>
								<input id="displayname"
									v-model="displayname"
									name="displayname"
									type="text"
									class="form-input"
									:placeholder="getLocalizedText('Your name as shown to others')"
									@input="validateForm(['displayname'])">
								<p v-if="validation.isDisplaynameEmpty" class="validation-error">
									{{ getLocalizedText('Display name is required.') }}
								</p>
							</div>
						</div>
					</div>

					<div id="fields">
						<div class="field">
							<div class="control">
								<label>{{ getLocalizedText('Username') }}<sup>*</sup></label>
								<div class="username-group">
									<input id="username"
										v-model="username"
										name="username"
										class="form-input"
										:placeholder="getLocalizedText('Username')"
										type="text"
										@input="validateForm(['username'])">
									<div id="username-domain-div" class="pad-left-5">
										@{{ domain }}
									</div>
								</div>
								<p v-if="validation.isUsernameEmpty" class="validation-error">
									{{ getLocalizedText('Username is required.') }}
								</p>
							</div>
						</div>
					</div>

					<div id="fields">
						<div class="field">
							<div class="control">
								<label>{{ getLocalizedText('Enter Password') }}<sup>*</sup></label>
								<div class="username-group">
									<Password v-model="password"
										:secure-length="7"
										:toggle="false"
										:badge="false"
										type="password"
										name="password"
										:default-class="form - input"
										:placeholder="getLocalizedText('Password')"
										@input="validateForm(['password'])" />
									<!-- <input id="new-password" v-model="password" type="password" name="password" class="form-input" :placeholder="getLocalizedText('Password')"> -->
									<input id="repassword"
										v-model="repassword"
										type="password"
										name="repassword"
										class="form-input"
										:placeholder="getLocalizedText('Confirm')"
										@input="validateForm(['repassword'])">
								</div>
								<p v-if="validation.isPasswordEmpty" class="validation-error">
									{{ getLocalizedText('Password is required.') }}
								</p>
								<p v-if="validation.isRepasswordEmpty" class="validation-error">
									{{ getLocalizedText('Confirm password is required.') }}
								</p>
								<p v-if="!validation.isPasswordEmpty && !validation.isRepasswordEmpty && validation.isRePasswordMatched"
									class="validation-error">
									{{ getLocalizedText('The confirm password does not match the password.') }}
								</p>
							</div>
						</div>
					</div>

					<div id="fields">
						<div class="field">
							<div class="control">
								<span class="action-checkbox">
									<input id="action-tns"
										v-model="accepttns"
										type="checkbox"
										class="checkbox action-checkbox__checkbox focusable"
										@input="validateForm(['termsandservices'])">
									<label for="action-tns" class="action-checkbox__label">
										I have read and accept the&nbsp;<a :href="termsURL" target="_blank">Terms of Service</a>.<sup>*</sup></label>
								</span>

								<p v-if="validation.isAccepttnsEmpty" class="validation-error">
									{{ getLocalizedText('You must read and accept the Terms of Service to create your account.') }}
								</p>
							</div>
						</div>
					</div>

					<div id="fields">
						<div class="field">
							<div class="control">
								<span class="action-checkbox">
									<input id="action-newsletter_eos"
										v-model="newsletter_eos"
										type="checkbox"
										class="checkbox action-checkbox__checkbox focusable">
									<label for="action-newsletter_eos" class="action-checkbox__label">
										I want to receive news about /e/OS
									</label>
								</span>
							</div>
						</div>
					</div>

					<div id="fields">
						<div class="field">
							<div class="control">
								<span class="action-checkbox">
									<input id="action-newsletter_product"
										v-model="newsletter_product"
										type="checkbox"
										class="checkbox action-checkbox__checkbox focusable">
									<label for="action-newsletter_product" class="action-checkbox__label">
										I want to receive news about Murena products and promotions
									</label>
								</span>
							</div>
						</div>
					</div>

					<div id="groups" class="aliases-info">
						<button :wide="true"
							class="btn-primary"
							type="primary"
							@click="submitSignupForm">
							{{ getLocalizedText('Create My Account') }}
						</button>
					</div>
				</div>

				<div v-if="showCaptchaForm" id="captchaForm">
					<div id="fields">
						<div class="display-flex">
							<h1 id="registerHeading" class="has-text-centered subtitle is-3">
								{{ getLocalizedText('Captcha Verification') }}
							</h1>
						</div>

						<div class="field">
							<div class="control">
								<label>{{ getLocalizedText('Human verification') }}<sup>*</sup></label>
								<div class="humanverification-group">
									<input id="humanverification"
										v-model="humanverification"
										name="humanverification"
										class="form-input"
										:placeholder="getLocalizedText('Human verification')"
										type="text"
										@input="validateForm(['humanverification'])">
								</div>
								<p v-if="validation.isHumanverificationEmpty" class="validation-error">
									{{ getLocalizedText('Human Verification is required.') }}
								</p>
								<p v-if="!validation.isHumanverificationEmpty && validation.isHumanverificationMatched"
									class="validation-error">
									{{ getLocalizedText('Human Verification code is not correct.') }}
								</p>
							</div>
						</div>
					</div>

					<div id="fields">
						<div class="field np-captcha-section">
							<div class="control np-captcha-container">
								<div v-if="captcha && captcha.length" v-once class="np-captcha">
									<div v-for="(c, i) in captcha"
										:key="i"
										:style="{
											fontSize: getFontSize() + 'px',
											fontWeight: 800,
											transform: 'rotate(' + getRotationAngle() + 'deg)',
										}"
										class="np-captcha-character">
										{{ c }}
									</div>
								</div>
							</div>
							<!-- <button class="np-button" @click="createCaptcha">
								&#x21bb;
							</button> -->
						</div>
					</div>

					<div id="groups" class="aliases-info">
						<button :wide="true"
							class="btn-primary"
							type="primary"
							@click="submitCaptchaForm">
							{{ getLocalizedText('Verify') }}
						</button>
					</div>
				</div>

				<div v-if="showRecoverEmailForm" id="recoveryEmailForm">
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
									:placeholder="getLocalizedText('Enter recovery email address')"
									@input="validateForm(['email'])">
								<p v-if="validation.isEmailEmpty" class="validation-error">
									{{ getLocalizedText('Recovery Email is required.') }}
								</p>
							</div>
						</div>
					</div>

					<div id="groups" class="aliases-info display-flex">
						<button :wide="true"
							class="btn-default w-50"
							type="primary"
							@click="submitLaterForm">
							{{ getLocalizedText('Later') }}
						</button>
						<button :wide="true"
							class="btn-primary w-50"
							type="primary"
							@click="submitRecoveryEmailForm">
							{{ getLocalizedText('Set my recovery email address') }}
						</button>
					</div>
				</div>
			</div>
		</section>
	</div>
</template>

<script>
import Axios from '@nextcloud/axios'
import { showSuccess, showError } from '@nextcloud/dialogs'
import { generateUrl } from '@nextcloud/router'
import Password from 'vue-password-strength-meter'

const APPLICATION_NAME = 'ecloud-accounts'

export default {
	name: 'Signup',
	components: { Password },
	data() {
		return {
			appName: APPLICATION_NAME,
			domain: window.location.host,
			showRegistrationForm: true,
			showCaptchaForm: false,
			showRecoverEmailForm: false,
			displayname: '',
			username: '',
			password: '',
			repassword: '',
			humanverification: '',
			accepttns: false,
			newsletter_eos: false,
			newsletter_product: false,
			termsURL: 'http://murena.io/apps/terms_of_service/en/termsandconditions',
			validation: {
				isDisplaynameEmpty: false,
				isUsernameEmpty: false,
				isPasswordEmpty: false,
				isRepasswordEmpty: false,
				isRePasswordMatched: false,
				isHumanverificationEmpty: false,
				isHumanverificationMatched: false,
				isAccepttnsEmpty: false,
				isEmailEmpty: false,
			},
			captchaLength: 5,
			captcha: [],
			captchatext: '',
			selectedLanguage: 'en',
			languages: [
				{ code: 'en', name: 'English' },
				{ code: 'de', name: 'German' },
				{ code: 'fr', name: 'French' },
				{ code: 'it', name: 'Italian' },
				{ code: 'es', name: 'Spanish' },
			],
		}
	},
	created() {
		this.createCaptcha()
	},
	methods: {
		validateForm(fieldsToValidate) {

			fieldsToValidate.forEach(field => {
				this.validation[`is${field.charAt(0).toUpperCase() + field.slice(1)}Empty`] = this[field] === ''
			})
			if (fieldsToValidate.includes('repassword')) {
				this.validation.isRePasswordMatched = this.repassword !== this.password
			}
			if (fieldsToValidate.includes('humanverification')) {
				this.validation.isHumanverificationMatched = this.humanverification !== this.captchatext
			}
			if (fieldsToValidate.includes('termsandservices')) {
				this.validation.isAccepttnsEmpty = !this.accepttns
			}
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
		submitLaterForm() {
			const data = {
				displayname: this.displayname,
				username: this.username,
				password: this.password,
				email: '',
			}
			this.submitForm(data)
		},
		submitRecoveryEmailForm() {
			this.validateForm(['email'])
			const isFormValid = Object.values(this.validation).every(value => !value)
			if (isFormValid) {
				const data = {
					displayname: this.displayname,
					username: this.username,
					password: this.password,
					email: this.email,
				}
				this.submitForm(data)
			}
		},
		async submitForm(data) {
			const url = generateUrl(`/apps/${this.appName}/account/create`)
			try {
				const response = await Axios.post(url, data)
				if (response.status === 200) {
					this.showMessage(this.getLocalizedText("Congratulations! You've successfully created a Murena account."), 'success')
				} else {
					this.showMessage(this.getLocalizedText('Something went wrong.'), 'error')
				}
				this.setAllFieldsBlank()
			} catch (error) {
				if (error.response && error.response.status === 409) {
					this.showMessage(this.getLocalizedText('Username already exists.'), 'error')
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
		setAllFieldsBlank() {
			this.displayname = ''
			this.email = ''
			this.username = ''
			this.password = ''
			this.repassword = ''
			this.humanverification = ''
		},
		createCaptcha() {
			this.captchatext = Array.from({ length: this.captchaLength }, () => this.getRandomCharacter()).join('')
			this.captcha = this.captchatext.split('')
		},
		getRandomCharacter() {
			const symbols = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'
			const randomNumber = Math.floor(Math.random() * symbols.length)
			return symbols.charAt(randomNumber)
		},
		getFontSize() {
			const fontVariations = [14, 20, 30, 36, 40]
			return fontVariations[Math.floor(Math.random() * fontVariations.length)]
		},
		getRotationAngle() {
			const rotationVariations = [5, 10, 20, 25, -5, -10, -20, -25]
			return rotationVariations[Math.floor(Math.random() * rotationVariations.length)]
		},
		onLanguageChange() {
			this.$i18n.locale = this.selectedLanguage
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
    color: wheat;
    padding: 10px;
    font-weight: 500;
    margin: 5px 0;
    border-radius: 5px;
    min-width: 70%;
    width: fit-content;
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
</style>
