<template>
	<VueHcaptcha :sitekey="siteKey" @verify="onVerify" />
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
			const url = generateUrl(`/apps/${APPLICATION_NAME}/accounts/verify_hcaptcha`)
			await Axios.post(url, { token, ekey })
			const isFormValid = true

			this.$emit('form-submitted', { isFormValid })
		},
	},
}
</script>
