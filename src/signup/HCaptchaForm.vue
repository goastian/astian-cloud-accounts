<template>
	<div id="hcaptchaForm">
		<div class="display-flex">
			<h1 id="registerHeading" class="has-text-centered subtitle is-3">
				{{ t(appName,'Captcha Verification') }}
			</h1>
		</div>
		<VueHcaptcha :sitekey="siteKey" @verify="onVerify" />
	</div>
</template>

<script>
import { loadState } from '@nextcloud/initial-state'
import Axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import VueHcaptcha from '@hcaptcha/vue-hcaptcha'

const APPLICATION_NAME = 'ecloud-accounts'

export default {
	components: {
		VueHcaptcha,
	},
	data() {
		return {
			siteKey: loadState(APPLICATION_NAME, 'hCaptchaSiteKey'),
		}
	},
	methods: {
		async onVerify(token, ekey) {
			const url = generateUrl(`/apps/${APPLICATION_NAME}/accounts/verify_captcha`)
			await Axios.post(url, { userToken: token })
			const isFormValid = true

			this.$emit('form-submitted', { isFormValid })
		},
	},
}
</script>

<style scoped>
#hcaptchaForm {
	max-width: 500px;
	margin: 0 auto;
	padding: 0 10px;
}
.display-flex {
	display: flex;
	justify-content: space-between;
}

@media screen and (max-width: 768px) {
	#registerHeading {
		font-size: 1.5em;
	}
}

#registerHeading {
	margin-bottom: 10%;
	font-size: 24px;
	text-align: left !important;
	font-weight: 500;
}
@media screen and (max-width: 500px) {
	#registerHeading {
		font-size: 18px;
	}
}

</style>
