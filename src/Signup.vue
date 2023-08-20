<template>
	<div>
		<section id="main" class="register-page">
			<div id="registration">
				<h1 id="registerHeading" class="has-text-centered subtitle is-3">
					{{ getLocalizedText('Create Murena Account') }}
				</h1>
				<form id="registrationForm">

					<div id="fields">
						<div class="field">
							<div class="control">
								<label>{{ getLocalizedText('Display name') }}<sup>*</sup></label>
								<input id="displayname" v-model="displayname" name="displayname" type="displayname"
									class="form-input" :placeholder="getLocalizedText('Your name as shown to others')" />
								<p v-if="isDisplayNameEmpty" class="validation-error">
									{{ getLocalizedText('Display name is required.') }}
								</p>
							</div>
						</div>
					</div>

					<div id="fields">
						<div class="field">
							<div class="control">
								<label>{{ getLocalizedText('Email') }}<sup>*</sup></label>
								<input id="email" v-model="email" name="email" type="email" class="form-input"
									:placeholder="getLocalizedText('Enter recovery email address')">
								<p v-if="isEmailEmpty" class="validation-error">
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
									<input id="username" name="username" v-model="username" class="form-input"
										:placeholder="getLocalizedText('Username')" type="text">
									<div id="username-domain-div" class="pad-left-5">@{{ domain }}</div>
								</div>
								<p v-if="isUsernameEmpty" class="validation-error">
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
									<input type="password" name="password" id="new-password" v-model="password"
										class="form-input" :placeholder="getLocalizedText('Password')">
									<input type="password" id="repassword" name="repassword" v-model="repassword"
										class="form-input" :placeholder="getLocalizedText('Confirm')">
								</div>
								<p v-if="isPasswordEmpty" class="validation-error">
									{{ getLocalizedText('Password is required.') }}
								</p>
								<p v-if="isRePasswordEmpty" class="validation-error">
									{{ getLocalizedText('Confirm password is required.') }}
								</p>
							</div>

							<meter style="display: none;" max="4" id="password-strength-meter" value="0"></meter>
							<p class="hint has-text-centered" id="password-strength-text" hidden="" style="display: none;">
								Strength:<strong class="pw-score"> Good </strong>
								<span class="pw-feedback"></span>
							</p>
						</div>
					</div>
					<div id="groups" class="aliases-info">
						<NcButton :wide="true" type="primary" @click="submitSignupForm">
							{{ getLocalizedText('Signup') }}
						</NcButton>
					</div>
				</form>
			</div>
		</section>
	</div>
</template>

<script>
import NcButton from '@nextcloud/vue/dist/Components/Button'
const APPLICATION_NAME = 'ecloud-accounts'

export default {
	name: 'Signup',
	components: {
		NcButton,
	},
	data() {
		return {
			appName: APPLICATION_NAME,
			domain: window.location.host,
			displayname: '',
			email: '',
			username: '',
			password: '',
			repassword: '',
			isEmailEmpty: false,
			isDisplayNameEmpty: false,
			isUsernameEmpty: false,
			isPasswordEmpty: false,
			isRePasswordEmpty: false,
		}
	},
	methods: {
		async submitSignupForm() {
			try {
				this.isEmailEmpty = this.email === '';
				this.isDisplayNameEmpty = this.displayname === '';
				this.isUsernameEmpty = this.username === '';
				this.isPasswordEmpty = this.password === '';
				this.isRePasswordEmpty = this.repassword === '';

				if (!this.isEmailEmpty && !this.isDisplayNameEmpty && !this.isUsernameEmpty) {
					// submit form
				}
			} catch (error) {
				this.showError(this.getLocalizedText('Something went wrong.'));
			}
		},
		getLocalizedText(text) {
			return t(this.appName, text)
		},
		showError(message) {
			// Implement your showError function here
		},
	},
}
</script>
<style scoped>
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

#fields .form-input {
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
