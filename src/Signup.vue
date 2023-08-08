<template>
	<div>
		<section id="main" class="register-page">
			<div id="registration">
				<h1 class="has-text-centered subtitle is-3" id="registerHeading">
					{{ getLocalizedText('Request Murena Account') }}
				</h1>
				<form id="registrationForm">
					<div id="fields">
						<div class="field">
							<div class="control">
								<label>{{ getLocalizedText('Email') }}<sup>*</sup></label>
								<input id="email" name="email" type="email" class="form-input" v-model="email"
									:placeholder="getLocalizedText('Enter email to receive invitation')" />
								<p id="displayEmailError" v-if="isEmailEmpty" class="validation-error">
									{{ getLocalizedText('Email is required.') }}
								</p>
							</div>
						</div>

						<div class="field">
							<div class="control">
								<label>{{ getLocalizedText('Confirm email') }}<sup>*</sup></label>
								<div class="confirm-email">
									<input id="confirm-email" name="confirm-email" type="text" class="form-input"
										v-model="confirmEmail"
										:placeholder="getLocalizedText('Verify your email address')" />
								</div>
								<p id="displayConfirmEmailError" v-if="isConfirmEmailEmpty" class="validation-error">
									{{ getLocalizedText('Confirm email is required.') }}
								</p>
							</div>
						</div>
					</div>

					<div id="groups" class="aliases-info">
						<NcButton :wide="true" type="primary" @click="submitSignupForm">
							{{ getLocalizedText('Request Invitation') }}
						</NcButton>
					</div>
				</form>
			</div>
		</section>
	</div>
</template>

<script>
import NcButton from '@nextcloud/vue/dist/Components/Button'
const APPLICATION_NAME = 'ecloud-accounts';

export default {
	name: 'Signup',
	components: {
		NcButton
	},
	data() {
		return {
			appName: APPLICATION_NAME,
			email: '',
			confirmEmail: '',
			isEmailEmpty: false,
			isConfirmEmailEmpty: false,
		};
	},
	methods: {
		async submitSignupForm() {
			try {
				if (this.email === '') {
					this.isEmailEmpty = true;
				} else {
					this.isEmailEmpty = false;
				}

				if (this.confirmEmail === '') {
					this.isConfirmEmailEmpty = true;
				} else {
					this.isConfirmEmailEmpty = false;
				}

				if (!this.isEmailEmpty && !this.isConfirmEmailEmpty) {
					console.log('No error');
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
};
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
		font-size: 0;
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
	margin-bottom: 20px;
}

#fields {
	font-size: 0;
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

#repassword {
	width: 48%;
	margin-left: 4%;
}

#password {
	width: 48%;
}

#username {
	width: 70%;
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
