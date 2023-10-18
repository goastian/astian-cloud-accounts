<template>
	<div>
		<section id="main" class="register-page">
			<div id="registration">
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
				<div id="registrationForm">
					<div id="fields">
						<div class="field">
							<div class="control">
								<label>{{ getLocalizedText('Display name') }}<sup>*</sup></label>
								<input id="displayname"
									v-model="displayname"
									name="displayname"
									type="text"
									class="form-input"
									:placeholder="getLocalizedText('Your name as shown to others')">
								<p v-if="validation.isDisplaynameEmpty" class="validation-error">
									{{ getLocalizedText('Display name is required.') }}
								</p>
							</div>
						</div>
					</div>

					<!-- <div id="fields">
						<div class="field">
							<div class="control">
								<label>{{ getLocalizedText('Email') }}<sup>*</sup></label>
								<input id="email"
									v-model="email"
									name="email"
									type="email"
									class="form-input"
									:placeholder="getLocalizedText('Enter recovery email address')">
								<p v-if="validation.isEmailEmpty" class="validation-error">
									{{ getLocalizedText('Email is required.') }}
								</p>
							</div>
						</div>
					</div> -->

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
										type="text">
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
										:placeholder="getLocalizedText('Password')" />
									<!-- <input id="new-password" v-model="password" type="password" name="password" class="form-input" :placeholder="getLocalizedText('Password')"> -->
									<input id="repassword"
										v-model="repassword"
										type="password"
										name="repassword"
										class="form-input"
										:placeholder="getLocalizedText('Confirm')">
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
								<span data-v-1d3de86d="" class="action-checkbox">
									<input id="action-tns"
										v-model="accepttns"
										type="checkbox"
										class="checkbox action-checkbox__checkbox focusable"
										value="">
									<label data-v-1d3de86d="" for="action-tns" class="action-checkbox__label">
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
								<div class="newsletter_eos-group">
									<NcActionCheckbox v-model="newsletter_eos" value="newsletter_eos">
										I want to receive news about /e/OS
									</NcActionCheckbox>
								</div>
							</div>
						</div>
					</div>

					<div id="fields">
						<div class="field">
							<div class="control">
								<div class="newsletter_product-group">
									<NcActionCheckbox v-model="newsletter_product" value="newsletter_product">
										I want to receive news about Murena products and promotions
									</NcActionCheckbox>
								</div>
							</div>
						</div>
					</div>

					<div id="fields">
						<div class="field">
							<div class="control">
								<label>{{ getLocalizedText('Human verification') }}<sup>*</sup></label>
								<div class="humanverification-group">
									<input id="humanverification"
										v-model="humanverification"
										name="humanverification"
										class="form-input"
										:placeholder="getLocalizedText('Human verification')"
										type="text">
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
							@click="submitSignupForm">
							{{ getLocalizedText('Signup') }}
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
import NcActionCheckbox from '@nextcloud/vue/dist/Components/ActionCheckbox.js'

const APPLICATION_NAME = 'ecloud-accounts'

export default {
	name: 'Signup',
	components: { Password, NcActionCheckbox },
	data() {
		return {
			appName: APPLICATION_NAME,
			domain: window.location.host,
			displayname: '',
			username: '',
			password: '',
			repassword: '',
			humanverification: '',
			accepttns: '',
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
		validateForm() {
			const fieldsToValidate = ['displayname', 'username', 'password', 'repassword', 'humanverification', 'accepttns']
			fieldsToValidate.forEach(field => {
				this.validation[`is${field.charAt(0).toUpperCase() + field.slice(1)}Empty`] = this[field] === ''
			})
			this.validation.isRePasswordMatched = this.repassword !== this.password
			this.validation.isHumanverificationMatched = this.humanverification !== this.captchatext
		},
		async submitSignupForm() {
			this.validateForm()

			const isFormValid = Object.values(this.validation).every(value => !value)

			if (isFormValid) {
				const url = generateUrl(`/apps/${this.appName}/account/create`)
				try {
					const response = await Axios.post(url, {
						displayname: this.displayname,
						username: this.username,
						password: this.password,
					})

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
	color: #ff0000;
}

.btn-primary {
	width: 95%;
	background-color: var(--color-primary);
	color: white;
	border-color: var(--color-primary);
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
