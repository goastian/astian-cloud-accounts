<template>
	<div id="registrationForm">
		<div class="grid">
			<select v-model="formData.selectedLanguage" class="padding-0 lang-select" @change="onLanguageChange">
				<option v-for="language in languages" :key="language.code" :value="language.code">
					{{ t(appName,language.name) }}
				</option>
			</select>
		</div>
		<form @submit.prevent="submitRegistrationForm">
			<div class="display-flex">
				<h1 id="registerHeading" class="has-text-centered subtitle is-3">
					{{ t(appName,'Create Murena Account') }}
				</h1>
			</div>
			<div id="fields-displayname" class="fields">
				<div class="field">
					<div class="control">
						<label>{{ t(appName,'Display name') }}<sup>*</sup></label>
						<input id="displayname"
							v-model="formData.displayname"
							name="displayname"
							type="text"
							class="form-input"
							:placeholder="t(appName,'Your name as shown to others')"
							@input="validateForm(['displayname'])">
						<p v-if="validation.isDisplaynameEmpty" class="validation-warning">
							{{ t(appName,'Display name is required.') }}
						</p>
						<p v-else-if="validation.isDisplaynameNotValid" class="validation-warning">
							{{ t(appName, displaynameValidationMessage) }}
						</p>
					</div>
				</div>
			</div>

			<div id="fields-username" class="fields">
				<div class="field">
					<div class="control">
						<label>{{ t(appName,'Username') }}<sup>*</sup></label>
						<div class="username-group">
							<input id="username"
								v-model="formData.username"
								name="username"
								class="form-input"
								:placeholder="t(appName,'Username')"
								type="text"
								@input="validateForm(['username'])">
							<div id="username-domain-div" class="pad-left-5">
								@{{ domain }}
							</div>
						</div>
						<p v-if="validation.isUsernameEmpty" class="validation-warning">
							{{ t(appName,'Username is required.') }}
						</p>
						<p v-else-if="validation.isUsernameNotValid" class="validation-warning">
							{{ t(appName,usernameValidationMessage) }}
						</p>
						<p v-else-if="isUsernameAvailable" class="validation-success">
							{{ t(appName,'Available!') }}
						</p>
					</div>
				</div>
			</div>

			<div id="fields-password" class="fields">
				<div class="field">
					<div class="control">
						<label>{{ t(appName,'Enter Password') }}<sup>*</sup></label>
						<div class="password-group">
							<Password id="password"
								v-model="formData.password"
								:secure-length="7"
								:toggle="false"
								:badge="false"
								type="password"
								name="password"
								:default-class="form - input"
								:placeholder="t(appName,'Password')"
								@input="validateForm(['password'])" />
							<input id="repassword"
								v-model="formData.repassword"
								type="password"
								name="repassword"
								class="form-input"
								:placeholder="t(appName,'Confirm')"
								@input="validateForm(['repassword'])">
						</div>
						<p v-if="validation.isPasswordEmpty" class="validation-warning">
							{{ t(appName,'Password is required.') }}
						</p>
						<p v-if="validation.isRepasswordEmpty" class="validation-warning">
							{{ t(appName,'Confirm password is required.') }}
						</p>
						<p v-for="(error, index) in passwordErrors"
							:key="index"
							class="validation-warning">
							{{ error }}
						</p>
						<p v-if="!validation.isPasswordEmpty && !validation.isRepasswordEmpty && validation.isRePasswordMatched"
							class="validation-warning">
							{{ t(appName,'The confirm password does not match the password.') }}
						</p>
					</div>
				</div>
			</div>

			<div id="fields-accepttns" class="fields">
				<div class="field">
					<div class="control">
						<span class="action-checkbox required">
							<input id="action-tns"
								v-model="formData.accepttns"
								type="checkbox"
								class="checkbox action-checkbox__checkbox focusable">
							<label for="action-tns" class="action-checkbox__label" v-html="t(appName,'I have read and accept the <a href=\'http://murena.io/apps/terms_of_service/en/termsandconditions\' target=\'_blank\'>Terms of Service</a>.')" />
						</span>

						<p v-if="validation.isAccepttnsEmpty" class="validation-warning">
							{{ t(appName,'You must read and accept the Terms of Service to create your account.') }}
						</p>
					</div>
				</div>
			</div>

			<div id="fields-newsletter_eos" class="fields">
				<div class="field">
					<div class="control">
						<span class="action-checkbox">
							<input id="action-newsletter_eos"
								v-model="formData.newsletterEos"
								type="checkbox"
								class="checkbox action-checkbox__checkbox focusable">
							<label for="action-newsletter_eos" class="action-checkbox__label">{{ t(appName,'I want to receive news about /e/OS.') }}</label>
						</span>
					</div>
				</div>
			</div>

			<div id="fields-newsletter_product" class="fields">
				<div class="field">
					<div class="control">
						<span class="action-checkbox">
							<input id="action-newsletter_product"
								v-model="formData.newsletterProduct"
								type="checkbox"
								class="checkbox action-checkbox__checkbox focusable">
							<label for="action-newsletter_product" class="action-checkbox__label">{{ t(appName,'I want to receive news about Murena products and promotions.') }}</label>
						</span>
					</div>
				</div>
			</div>
			<div id="groups" class="aliases-info">
				<button :wide="true"
					class="btn-primary"
					type="primary"
					:disabled="processing">
					<template v-if="!processing">
						{{ t(appName,'Create My Account') }}
					</template>
					<template v-else>
						...
					</template>
				</button>
			</div>
		</form>
	</div>
</template>

<script>
import Axios from '@nextcloud/axios'
import Password from 'vue-password-strength-meter'
import { generateUrl } from '@nextcloud/router'
import { loadState } from '@nextcloud/initial-state'
const APPLICATION_NAME = 'ecloud-accounts'

export default {
	components: {
		Password,
	},
	props: {
		value: Object,
	},
	data() {
		return {
			appName: APPLICATION_NAME,
			usernameValidationMessage: '',
			displaynameValidationMessage: '',
			domain: window.location.host,
			validation: {
				isDisplaynameEmpty: false,
				isUsernameEmpty: false,
				isUsernameNotValid: false,
				isPasswordEmpty: false,
				isPasswordNotValid: false,
				isRepasswordEmpty: false,
				isRePasswordMatched: false,
				isAccepttnsEmpty: false,
				isDisplaynameNotValid: false,
			},
			languages: [
				{ code: 'en', name: 'English' },
				{ code: 'de', name: 'German' },
				{ code: 'fr', name: 'French' },
				{ code: 'it', name: 'Italian' },
				{ code: 'es', name: 'Spanish' },
			],
			passwordErrors: [],
			passwordRules: [
				{ message: t(this.appName, 'Incorrect password length: Required length is 8 to 32'), regex: /.{8,32}/ },
				{ message: t(this.appName, 'Password has invalid characters.'), regex: /^(?!.*\\).*/ },
			],
			isUsernameAvailable: false,
			processing: false,
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
		this.formData.selectedLanguage = loadState(this.appName, 'lang')
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
			if (fieldsToValidate.includes('termsandservices')) {
				this.validation.isAccepttnsEmpty = !this.formData.accepttns
			}
			if (fieldsToValidate.includes('username')) {
				this.validateUsername()
			}
		},
		async passwordValidation() {
			this.passwordErrors = []
			this.validation.isPasswordNotValid = false

			if (this.formData.password) {
				let isValid = true
				for (const condition of this.passwordRules) {
					if (!condition.regex.test(this.formData.password)) {
						this.passwordErrors.push(condition.message)
						isValid = false // Set flag to false if the password fails any condition
					}
				}

				this.validation.isPasswordNotValid = !isValid
			}
		},
		async validateUsername() {
			this.validation.isUsernameNotValid = false
			this.usernameValidationMessage = ''
			this.isUsernameAvailable = false
			const usernamePattern = /^[a-zA-Z0-9._-]+$/
			const minCharacterCount = 3
			const isValidUsername = usernamePattern.test(this.formData.username)
			const isEnoughCharacters = this.formData.username.length >= minCharacterCount

			if (!isValidUsername) {
				this.usernameValidationMessage = t(this.appName, 'Username must consist of letters, numbers, hyphens, dots and underscores only.')
				this.validation.isUsernameNotValid = true
			} else if (!isEnoughCharacters) {
				this.usernameValidationMessage = t(this.appName, 'Username must be at least 3 characters long.')
				this.validation.isUsernameNotValid = true
			}
		},

		async validateFields() {
			this.validation.isUsernameNotValid = false
			this.validation.isDisplaynameNotValid = false
			const data = {
				username: this.formData.username,
				displayname: this.formData.displayname,
			}
			const url = generateUrl(`/apps/${this.appName}/accounts/validate_fields`)

			try {
				await Axios.post(url, data)
				this.isUsernameAvailable = true
			} catch (error) {
				if (error.response && error.response.status === 400) {
					if (error.response.data.field === 'username') {
						this.validation.isUsernameNotValid = true
						this.usernameValidationMessage = t(this.appName, error.response.data.message)
					}
					if (error.response.data.field === 'display name') {
						this.validation.isDisplaynameNotValid = true
						this.displaynameValidationMessage = t(this.appName, error.response.data.message)
					}
				} else {
					this.validation.isUsernameNotValid = true
					this.usernameValidationMessage = t(this.appName, 'Something went wrong.')
				}
			}
		},
		async submitRegistrationForm() {
			this.processing = true
			this.validateForm(['displayname', 'username', 'password', 'repassword', 'termsandservices'])
			await this.validateFields()
			const isFormValid = Object.values(this.validation).every(value => !value)

			if (isFormValid) {
				this.$emit('form-submitted', { isFormValid })
			}
			this.processing = false
		},
		onLanguageChange() {
			window.location.href = window.location.origin + '/apps/' + APPLICATION_NAME + '/accounts/' + this.formData.selectedLanguage + '/signup'
		},
	},
}
</script>

<style scoped>
#registrationForm{
	display: flex;
	max-width: 700px;
	width: 100%;
	margin: 0 auto;
}
.display-flex {
	display: flex;
	justify-content: space-between;
}
.padding-0 {
	padding: 0;
}
.lang-select {
	font-size: 14px;
	line-height:24px;
	color: rgba(0, 0, 0, 0.6);
	font-weight:400;
	background: url("../../img/expand_more.svg") no-repeat right 8px center;
	float: right;
	position: absolute;
	top: 67px;
	right: 5%;
}
.fields input[type='checkbox'].checkbox + label:before{
	height: 15px;
    width: 15px;
	margin-right: 10px;
}
.fields {
	margin: 10px 0;
}
.fields .control {
	text-align: left;
	margin: 10px 0;
}
.fields input#new-password,
.fields input#repassword {
	width: 45%;
}
.fields input#username {
	width: 50%;
}
.fields input{
	width: 100%;
}
#registrationForm .fields input,
#registrationForm .fields input[type="password"] {
	background-color: #ffffff;
	margin-bottom: 0;
	color: #000000;
	display: block;
	font-size: 16px;
	line-height: 1.3em;
	transition: all 0.5s linear;
	border: 1px solid #E6E8E9;
	border-radius: 8px;
	padding: 30px 20px;
	margin: 10px 0;
	box-sizing: border-box;
}
.username-group {
	display: flex;
}
#username-domain-div {
	display: flex;
	align-items: center;
}
.fields {
	background-color: white;
}
.fields .field {
	font-size: 1.3em;
}
.fields p {
	font-size: 15px;
}
#captcha_img {
	font-size: 12px;
	width: 100%;
}
.password-group {
    display: flex;
}
.password-group .Password{
	flex-grow: 1;
    flex-basis: 0;
	max-width: unset;
	margin-top: 5px;
    margin-bottom: 10px;
	margin-right: 10px;
}
.password-group > input
{
    width: 90%;
    border: 1px solid #E6E8E9;
	padding: 10px 20px;
}
#inviteHeader,
#registerHeading {
	margin-bottom: 12px;
	font-size: 24px;
	text-align: left !important;
	font-weight: 500;
}
input#password {
    height: 60px !important;
    margin-top: 0 !important;
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
.fields label {
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
.pad-left-5{
	padding-left: 5px;
}
span.action-checkbox.required:after {
    content: "*";
    font-size: large;
    color: red;
}
/** mobile font sizes **/
@media screen and (max-width: 650px) {
	.fields .field .control input {
		padding-left: 2%;
		padding-right: 2%;
	}
}
@media screen and (max-width: 768px) {
	.password-group {
		display: block;
	}
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
	.fields {
		background-color: white;
	}
	.fields .field {
		font-size: 1.0em;
	}
	.fields .field .control {
		text-align: left;
	}
	.fields .field .control input {
		font-size: 1.0em;
		line-height: 1.0em;
	}
	.fields p {
		font-size: 1.0em;
	}
	input {
		font-size: 1.0em;
		line-height: 1.0em;
	}
	.fields input#new-password,
	.fields input#repassword {
		width: 100%;
	}
	.password-group .Password{
		margin: 0;
	}
}
@media screen and (max-width: 500px) {
	#main {
		padding: 0 1.5rem;
	}
	#inviteHeader,
	#registerHeading {
		font-size: 18px;
	}
}
</style>
