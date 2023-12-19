<template>
	<div id="recoveryEmailForm">
		<div id="fields">
			<div class="field">
				<div class="control">
					<label class="bold">{{ t(appName,'Recovery Email') }}</label>
					<input id="email"
						v-model="formData.email"
						name="email"
						type="email"
						class="form-input"
						:placeholder="t(appName,'Use an alternative email')"
						@input="validateForm(['email'])">
					<p v-if="validation.isEmailEmpty" class="validation-warning">
						{{ t(appName,'Recovery Email is required.') }}
					</p>
				</div>
			</div>
		</div>

		<div>
			<h1 class="has-text-centered subtitle is-3">
				<span class="bold">{{ t(appName,'Important:') }}</span>
				<span class="pl-10">{{ t(appName,'For security reasons, a recovery email is required. If you decide to set it later, your account will be partially restricted.') }}</span>
			</h1>
		</div>

		<div id="groups" class="aliases-info display-flex">
			<button :wide="true"
				class="btn-primary w-50"
				type="primary"
				@click.prevent="submitRecoveryEmailForm(true)">
				{{ t(appName,'Set my recovery email address') }}
			</button>
			<button :wide="true"
				class="btn-default w-50"
				type="primary"
				@click.prevent="submitRecoveryEmailForm(false)">
				{{ t(appName,'Later') }}
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
		submitRecoveryEmailForm(setrecoveryemail) {
			let isFormValid = true
			if (setrecoveryemail) {
				this.validateForm(['email'])
				isFormValid = Object.values(this.validation).every(value => !value)
				this.$emit('form-submitted', { isFormValid })
			} else {
				this.formData.email = ''
				this.$emit('form-submitted', { isFormValid })
			}
		},
	},
}
</script>
<style scoped>
.bold{
	font-weight: bold;
}
.pl-10{
	padding-left: 10px;
}
#recoveryEmailForm {
    max-width: 670px;
    width: 100%;
}
.display-flex {
	display: flex;
	justify-content: space-between;
	flex-direction: column;
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
	width: 100%;
	background-color: var(--color-primary);
	color: var(--color-primary-text);
	border-color: var(--color-primary);
	font-size: large;
	margin: 15px 0;
}

.btn-default{
	width: 100%;
    background-color: var(--color-primary-text);
    color: var(--color-primary);
    border-color: var(--color-primary);
    font-size: large;
	margin: 15px 0;
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
