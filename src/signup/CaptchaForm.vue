<template>
	<div id="captchaForm">
		<form @submit.prevent="submitCaptchaForm">
			<div id="fields">
				<div class="display-flex">
					<h1 id="registerHeading" class="has-text-centered subtitle is-3">
						{{ getLocalizedText('Captcha Verification') }}
					</h1>
				</div>

				<div class="field">
					<div class="control">
						<label>{{ getLocalizedText('Human Verification') }}<sup>*</sup></label>
						<div class="humanverification-group">
							<input id="humanverification"
								v-model="formData.humanverification"
								name="humanverification"
								class="form-input"
								:placeholder="getLocalizedText('Human Verification')"
								type="text">
						</div>
						<p v-if="validation.isHumanverificationEmpty" class="validation-warning">
							{{ getLocalizedText('Human Verification is required.') }}
						</p>
						<p v-else-if="!validation.isHumanverificationEmpty && validation.isHumanverificationNotMatched"
							class="validation-warning">
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
					type="primary">
					<!-- @click="submitCaptchaForm" -->
					{{ getLocalizedText('Verify') }}
				</button>
			</div>
		</form>
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
				isHumanverificationEmpty: false,
				isHumanverificationNotMatched: false,
			},
			captcha: [],
			num1: '',
			num2: '',
			operator: '',
			captchaResult: '',
			operators: ['+', '-'],
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
		this.createCaptcha()
	},
	methods: {
		validateForm(fieldsToValidate) {
			fieldsToValidate.forEach(field => {
				this.validation[`is${field.charAt(0).toUpperCase() + field.slice(1)}Empty`] = this.formData[field] === ''
			})
			if (fieldsToValidate.includes('humanverification')) {
				this.checkAnswer()
			}
		},
		createCaptcha() {
			this.num1 = this.getRandomCharacter()
			this.num2 = this.getRandomCharacter()
			const operators = this.operators
			this.operator = operators[Math.floor(Math.random() * operators.length)]
			this.captcha.push(this.num1)
			this.captcha.push(this.operator)
			this.captcha.push(this.num2)
		},
		getRandomCharacter() {
			const numbers = '123456789'
			const randomNumber = Math.floor(Math.random() * numbers.length)
			return numbers.charAt(randomNumber)
		},
		calculateResult() {
			const num1 = parseFloat(this.num1)
			const num2 = parseFloat(this.num2)

			switch (this.operator) {
			case '+':
				return num1 + num2
			case '-':
				return num1 - num2
			default:
				return NaN
			}
		},
		checkAnswer() {
			const result = this.calculateResult()
			this.captchaResult = parseInt(result, 10)
			if (parseInt(this.formData.humanverification, 10) !== this.captchaResult) {
				this.validation.isHumanverificationNotMatched = true
			} else {
				this.validation.isHumanverificationNotMatched = false
			}
		},
		getFontSize() {
			const fontVariations = [14, 16, 18, 20]
			return fontVariations[Math.floor(Math.random() * fontVariations.length)]
		},
		getRotationAngle() {
			const rotationVariations = [10, 5, -5, -10]
			return rotationVariations[Math.floor(Math.random() * rotationVariations.length)]
		},
		submitCaptchaForm() {
			this.validateForm(['humanverification'])
			const isFormValid = Object.values(this.validation).every(value => !value)
			if (isFormValid) {
				this.$emit('form-submitted', { isFormValid })
			}
		},
		getLocalizedText(text) {
			return t('ecloud-accounts', text)
		},
	},
}
</script>
<style scoped>
.display-flex {
	display: flex;
	justify-content: space-between;
}

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
