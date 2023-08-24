<template>
	<div>
		<section id="main" class="register-page">
			<div id="registration">
				<h1 id="registerHeading" class="has-text-centered subtitle is-3">
					{{ getLocalizedText('Create Murena Account') }}
				</h1>
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
								<p v-if="validation.isDisplayNameEmpty" class="validation-error">
									{{ getLocalizedText('Display name is required.') }}
								</p>
							</div>
						</div>
					</div>

					<div id="fields">
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
										:secureLength="7"
										:toggle="false"
										:badge="false"
										type="password"
										name="password"
										:default-class="form-input"
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
								<p v-if="validation.isRePasswordEmpty" class="validation-error">
									{{ getLocalizedText('Confirm password is required.') }}
								</p>
								<p v-if="!validation.isPasswordEmpty && !validation.isRePasswordEmpty && validation.isRePasswordMatched"
									class="validation-error">
									{{ getLocalizedText('The confirm password does not match the password.') }}
								</p>
							</div>
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

const APPLICATION_NAME = 'ecloud-accounts'

export default {
	name: 'Signup',
	components: { Password },
	data() {
		return {
			appName: APPLICATION_NAME,
			domain: window.location.host,
			displayname: '',
			email: '',
			username: '',
			password: '',
			repassword: '',
			validation: {
				isEmailEmpty: false,
				isDisplayNameEmpty: false,
				isUsernameEmpty: false,
				isPasswordEmpty: false,
				isRePasswordEmpty: false,
				isRePasswordMatched: false,
			},
		}
	},
	methods: {
		validateForm() {
			const fieldsToValidate = ['email', 'displayname', 'username', 'password', 'repassword']
			fieldsToValidate.forEach(field => {
				this.validation[`is${field.charAt(0).toUpperCase() + field.slice(1)}Empty`] = this[field] === ''
			})
			this.validation.isRePasswordMatched = this.repassword !== this.password
		},
		async submitSignupForm() {
			this.validateForm()

			const isFormValid = Object.values(this.validation).every(value => !value)

			if (isFormValid) {
				try {
					const url = generateUrl(`/apps/${this.appName}/account/create`)
					const response = await Axios.post(url, {
						displayname: this.displayname,
						email: this.email,
						username: this.username,
						password: this.password,
						domain: this.domain,
					})

					if (response.data && response.data.message) {
						this.showMessage(response.data.message, 'success')
						this.setAllFieldsBlank()
					}
				} catch (error) {
					const errorMessage = error.response?.data?.message || this.getLocalizedText('Something went wrong.')
					this.showMessage(errorMessage, 'error')
				}
			}
		},
		showMessage(message, type) {
			if (type === 'success') {
				showSuccess(message)
			} else {
				showError(message)
			}
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
		},
	},
}
</script>
<style scoped>
section#main {
    overflow-x: hidden;
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

#fields input,#fields input[type="password"] {
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
#fields .Password{
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
	width: 20vw;
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
</style>
