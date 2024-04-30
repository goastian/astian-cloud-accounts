<template>
	<div id="recoveryEmailForm">
		<div id="fields">
			<div class="mx-10">
				<h1 class="text-justified title">
					{{ t(appName,'Set a recovery email address') }}
				</h1>
			</div>

			<div class="field mx-10">
				<div class="control">
					<label>{{ t(appName,'Recovery Email') }}</label>
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

		<div class="mx-10">
			<h1 class="text-justified subtitle is-3">
				<span class="important">{{ t(appName,'Important:') }}</span>
				<span>{{ t(appName,'For security reasons, a recovery email is required. If you decide to set it later, your account will be partially restricted.') }}</span>
			</h1>
		</div>

		<div id="groups" class="aliases-info display-flex">
			<button :wide="true"
				class="btn-primary w-50 mx-10"
				type="primary"
				@click.prevent="submitRecoveryEmailForm(true)">
				{{ t(appName,'Set My Recovery Email Now') }}
			</button>
			<button :wide="true"
				class="btn-default w-50 mx-10"
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
.important{
	font-weight: bold;
	color: black;
}
.text-justified{
	text-align: justify;
}
#recoveryEmailForm {
	max-width: 500px;
	margin: 0 auto;
	padding: 0 10px;
}
.mx-10{
	margin: 10px 0;
}
.title{
	font-size: 20px;
	color: black;
	margin-bottom: 10%;
	font-weight: bold;
}
.display-flex {
	display: flex;
	justify-content: space-between;
	flex-direction: column;
}

/** mobile font sizes **/
@media screen and (max-width: 650px) {
	#fields input {
		padding-left: 2%;
		padding-right: 2%;
	}
}

@media screen and (max-width: 768px) {

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

#fields .control {
	text-align: left;
	margin-top: 10px;
	margin-bottom: 10px;
}

#recoveryEmailForm #fields input{
	background-color: #ffffff;
	margin-bottom: 0;
	color: #000000;
	display: block;
	width: 100%;
	font-size: 16px;
	line-height: 1.3em;
	transition: all 0.5s linear;
	border: 1px solid #E6E8E9;
	border-radius: 8px;
	padding: 30px 20px;
	margin: 10px 0;
	box-sizing: border-box;
}

#fields .field {
	font-size: 1.3em;
}

#fields p {
	font-size: 15px;
}

#fields label {
	color: black;
	font-size: 16px;
	font-weight: 900;
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
}

.btn-default{
	width: 100%;
	background-color: var(--color-primary-text);
	color: var(--color-primary);
	border-color: var(--color-primary);
	font-size: large;
}

</style>
