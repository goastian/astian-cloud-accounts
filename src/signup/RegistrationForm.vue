<template>
	<div id="registrationForm">
		<form @submit.prevent="submitRegistrationForm">
			<div class="display-flex">
				<h1 id="registerHeading" class="has-text-centered subtitle is-3">
					{{ getLocalizedText('Create Murena Account') }}
				</h1>
				<div class="grid">
					<select v-model="formData.selectedLanguage" class="padding-0" @change="onLanguageChange">
						<option v-for="language in languages" :key="language.code" :value="language.code">
							{{ getLocalizedText(language.name) }}
						</option>
					</select>
				</div>
			</div>
			<div id="fields">
				<div class="field">
					<div class="control">
						<label>{{ getLocalizedText('Display name') }}<sup>*</sup></label>
						<input id="displayname"
							v-model="formData.displayname"
							name="displayname"
							type="text"
							class="form-input"
							:placeholder="getLocalizedText('Your name as shown to others')"
							@input="validateForm(['displayname'])">
						<p v-if="validation.isDisplaynameEmpty" class="validation-warning">
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
								v-model="formData.username"
								name="username"
								class="form-input"
								:placeholder="getLocalizedText('Username')"
								type="text"
								@input="validateForm(['username'])">
							<div id="username-domain-div" class="pad-left-5">
								@{{ domain }}
							</div>
						</div>
						<p v-if="validation.isUsernameEmpty" class="validation-warning">
							{{ getLocalizedText('Username is required.') }}
						</p>
						<p v-else-if="validation.isUsernameNotValid" class="validation-warning">
							{{ getLocalizedText(usernameValidationMessage) }}
						</p>
						<p v-else-if="isUsernameAvailable" class="validation-success">
							{{ getLocalizedText('Available!') }}
						</p>
					</div>
				</div>
			</div>

			<div id="fields">
				<div class="field">
					<div class="control">
						<label>{{ getLocalizedText('Enter Password') }}<sup>*</sup></label>
						<div class="username-group">
							<Password v-model="formData.password"
								:secure-length="7"
								:toggle="false"
								:badge="false"
								type="password"
								name="password"
								:default-class="form - input"
								:placeholder="getLocalizedText('Password')"
								@input="validateForm(['password'])" />
							<input id="repassword"
								v-model="formData.repassword"
								type="password"
								name="repassword"
								class="form-input"
								:placeholder="getLocalizedText('Confirm')"
								@input="validateForm(['repassword'])">
						</div>
						<p v-if="validation.isPasswordEmpty" class="validation-warning">
							{{ getLocalizedText('Password is required.') }}
						</p>
						<p v-if="validation.isRepasswordEmpty" class="validation-warning">
							{{ getLocalizedText('Confirm password is required.') }}
						</p>
						<p v-for="(error, index) in passworderrors" :key="index" class="validation-warning">
							{{ error }}
						</p>
						<p v-if="!validation.isPasswordEmpty && !validation.isRepasswordEmpty && validation.isRePasswordMatched"
							class="validation-warning">
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
								v-model="formData.accepttns"
								type="checkbox"
								class="checkbox action-checkbox__checkbox focusable">
							<label for="action-tns" class="action-checkbox__label">
								I have read and accept the <a href="__termsURL__" target="_blank">Terms of Service</a>.
							</label>
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
								v-model="formData.newsletter_eos"
								type="checkbox"
								class="checkbox action-checkbox__checkbox focusable">
							<label for="action-newsletter_eos" class="action-checkbox__label">
								{{ getLocalizedText('labels.I want to receive news about /e/OS') }}
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
								v-model="formData.newsletter_product"
								type="checkbox"
								class="checkbox action-checkbox__checkbox focusable">
							<label for="action-newsletter_product" class="action-checkbox__label">
								{{ getLocalizedText('I want to receive news about Murena products and promotions') }}
							</label>
						</span>
					</div>
				</div>
			</div>

			<div id="groups" class="aliases-info">
				<button :wide="true"
					class="btn-primary"
					type="primary">
					<!-- @click="submitSignupForm" -->
					{{ getLocalizedText('Create My Account') }}
				</button>
			</div>
		</form>
	</div>
</template>

<script>
import Password from 'vue-password-strength-meter'
export default {
	components: {
		Password,
	},
	props: {
		value: Object,
	},
	data() {
		return {
			username: '',
			email: '',
			password: '',
			usernameValidationMessage: '',
			validation: {
				isDisplaynameEmpty: false,
				isUsernameEmpty: false,
				isUsernameNotValid: false,
				isPasswordEmpty: false,
				isPasswordNotValid: false,
				isRepasswordEmpty: false,
				isRePasswordMatched: false,
				isAccepttnsEmpty: false,
			},
			languages: [
				{ code: 'en', name: 'English' },
				{ code: 'de', name: 'German' },
				{ code: 'fr', name: 'French' },
				{ code: 'it', name: 'Italian' },
				{ code: 'es', name: 'Spanish' },
			],
			passworderrors: [],
			passwordrules: [
				{ message: 'At least 6 characters.', regex: /.{6,}/ },
				{ message: 'Lowercase letters: a-z.', regex: /[a-z]+/ },
				{ message: 'Uppercase letters: a-z.', regex: /[A-Z]+/ },
				{ message: 'One number required.', regex: /[0-9]+/ },
			],
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
		validateForm(fieldsToValidate) {
			fieldsToValidate.forEach(field => {
				this.validation[`is${field.charAt(0).toUpperCase() + field.slice(1)}Empty`] = this[field] === ''
			})
			if (fieldsToValidate.includes('password')) {
				this.passwordValidation()
			}
			if (fieldsToValidate.includes('repassword')) {
				this.validation.isRePasswordMatched = this.repassword !== this.password
			}
			if (fieldsToValidate.includes('termsandservices')) {
				this.validation.isAccepttnsEmpty = !this.accepttns
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
					if (!condition.regex.test(this.password)) {
						this.passworderrors.push(condition.message)
						this.validation.isPasswordNotValid = true
					}
				}
			}
		},
		validateUsername() {
			const usernamePattern = /^[a-zA-Z0-9_-]+$/
			const minCharacterCount = 3
			this.validation.isUsernameNotValid = false
			if (!usernamePattern.test(this.username) || this.username.length < minCharacterCount) {
				if (!usernamePattern.test(this.username)) {
					this.usernameValidationMessage = this.errors.userNameInvalid
				} else {
					this.usernameValidationMessage = this.errors.userNameLength
				}
				this.validation.isUsernameNotValid = true
			} else {
				this.checkUsername()
			}
		},
		submitRegistrationForm() {
			this.validateForm(['displayname', 'username', 'password', 'repassword', 'termsandservices'])

			const isFormValid = Object.values(this.validation).every(value => !value)

			if (isFormValid) {
				this.$emit('form-submitted')
				// this.showRegistrationForm = false
				// this.showCaptchaForm = true
				// this.showRecoverEmailForm = false
			}
		},
		getLocalizedText(text) {
			return t('ecloud-accounts', text)
		},
	},
}
</script>
