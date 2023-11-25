<template>
	<div id="recoveryEmailForm">
		<div>
			<h1 class="has-text-centered subtitle is-3">
				{{ t(appName,'For security reasons you need to set a recovery address for your Murena Cloud account.') }}
			</h1>
			<h1 class="has-text-centered subtitle is-3">
				{{ t(appName,'As long as you don\'t, you\'ll have limited access to your account.') }}
			</h1>
		</div>

		<div id="fields">
			<div class="field">
				<div class="control">
					<label>{{ t(appName,'Recovery Email') }}</label>
					<input id="email"
						v-model="formData.email"
						name="email"
						type="email"
						class="form-input"
						:placeholder="t(appName,'Recovery Email')"
						@input="validateForm(['email'])">
					<p v-if="validation.isEmailEmpty" class="validation-warning">
						{{ t(appName,'Recovery Email is required.') }}
					</p>
				</div>
			</div>
		</div>

		<div id="groups" class="aliases-info display-flex">
			<button :wide="true"
				class="btn-default w-50"
				type="primary"
				@click.prevent="laterSubmit()">
				{{ t(appName,'Later') }}
			</button>
			<button :wide="true"
				class="btn-primary w-50"
				type="primary"
				@click.prevent="submitRecoveryEmailForm()">
				{{ t(appName,'Set my recovery email address') }}
			</button>
		</div>
	</div>
</template>

<script>
const APPLICATION_NAME = 'ecloud-accounts'
export default {
	props: {
		value: Object,
	},
	data() {
		return {
			appName: APPLICATION_NAME,
			validation: {
				isEmailEmpty: false,
			},
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
				this.validation[`is${field.charAt(0).toUpperCase() + field.slice(1)}Empty`] = this.formData[field] === ''
			})
		},
		laterSubmit() {
			const isFormValid = true
			this.email = ''
			this.$emit('form-submitted', { isFormValid })
		},
		submitRecoveryEmailForm() {
			let isFormValid = true
			this.validateForm(['email'])
			isFormValid = Object.values(this.validation).every(value => !value)
			this.$emit('form-submitted', { isFormValid })
		},
	},
}
</script>
<style scoped>
.display-flex {
	display: flex;
	justify-content: space-between;
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

#fields {
	background-color: white;
}

#fields .field {
	font-size: 1.3em;
}

#fields p {
	font-size: 15px;
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
}
</style>
