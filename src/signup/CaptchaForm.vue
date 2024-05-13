<template>
	<div id="captchaForm">
		<form @submit.prevent="submitCaptchaForm">
			<div id="fields">
				<div class="display-flex">
					<h1 id="registerHeading" class="has-text-centered subtitle is-3">
						{{ t(appName,'Captcha Verification') }}
					</h1>
				</div>

				<div class="field">
					<div class="control">
						<label>{{ t(appName,'Human Verification') }}<sup>*</sup></label>
						<div class="captchaInput-group">
							<input id="captchaInput"
								v-model="formData.captchaInput"
								name="captchaInput"
								class="form-input"
								:placeholder="t(appName,'Human Verification')"
								type="text">
						</div>
						<p v-if="validation.isCaptchaInputEmpty" class="validation-warning">
							{{ t(appName,'Human Verification is required.') }}
						</p>
						<p v-else-if="validation.isCaptchaInputNotMatched"
							class="validation-warning">
							{{ t(appName,'Human Verification code is not correct.') }}
						</p>
					</div>
				</div>
			</div>

			<div id="fields">
				<div class="field np-captcha-section">
					<div class="control np-captcha-container">
						<img :src="captchaImageUrl" alt="Captcha Image">
					</div>
				</div>
			</div>

			<div id="groups" class="aliases-info">
				<button :wide="true"
					class="btn-primary"
					type="primary">
					<!-- @click="submitCaptchaForm" -->
					{{ t(appName,'Verify') }}
				</button>
			</div>
		</form>
	</div>
</template>

<script>
import Axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { showSuccess, showError } from '@nextcloud/dialogs'

const APPLICATION_NAME = 'ecloud-accounts'
export default {
	props: {
		value: Object,
	},
	data() {
		return {
			appName: APPLICATION_NAME,
			validation: {
				isCaptchaInputEmpty: false,
				isCaptchaInputNotMatched: false,
			},
			captchaImageUrl: generateUrl(`/apps/${APPLICATION_NAME}/accounts/captcha`),
			bypassToken: null,
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
		this.bypassToken = this.$route.query.bypassToken
	},
	methods: {
		async checkAnswer() {
			this.validation.isCaptchaInputNotMatched = false

			try {
				const data = {
					captchaInput: this.formData.captchaInput,
				}
				if (this.bypassToken === 1) {
					data.bypassToken = this.bypassToken
				}
				const url = generateUrl(`/apps/${this.appName}/accounts/verify_captcha`)
				await Axios.post(url, data)
				const isFormValid = true
				this.$emit('form-submitted', { isFormValid })
			} catch (error) {
				this.validation.isCaptchaInputNotMatched = true
				this.refreshCaptchaImage()
			}
		},
		submitCaptchaForm() {
			this.validation.isCaptchaInputEmpty = this.formData.captchaInput === ''
			if (!this.validation.isCaptchaInputEmpty) {
				this.checkAnswer()
			}
		},
		showMessage(message, type) {
			type === 'success' ? showSuccess(message) : showError(message)
		},
		refreshCaptchaImage() {
			this.captchaImageUrl = generateUrl(`/apps/${this.appName}/accounts/captcha?v=${Date.now()}`)
		},
	},
}
</script>
<style scoped>
#captchaForm {
	max-width: 500px;
	margin: 0 auto;
	padding: 0 10px;
}
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
	margin: 10px 0;
	background-color: white;
}
#fields .control {
	text-align: left;
	margin-top: 10px;
	margin-bottom: 10px;
}
#captchaForm #fields input {
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
#captcha_img {
	font-size: 12px;
	width: 100%;
}
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
	#registerHeading {
		font-size: 18px;
	}
}
.np-captcha-section {
	display: flex;
	width: fit-content;
}

</style>
