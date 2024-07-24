<template>
	<vue-hcaptcha :sitekey="siteKey" @verify="onVerify" />
</template>

<script>
import { loadState } from '@nextcloud/initial-state'
import Axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

const APPLICATION_NAME = 'ecloud-accounts'

export default {
	data() {
		return {
			siteKey: loadState(APPLICATION_NAME, 'hCaptchaSiteKey'),
		}
	},
	methods: {
		async onVerify(token, ekey) {
			const url = generateUrl(`/apps/${this.appName}/accounts/verify_hcaptcha`)
			await Axios.post(url, { token, ekey })
			const isFormValid = true

			this.$emit('form-submitted', { isFormValid })
		},
	},
}
</script>
