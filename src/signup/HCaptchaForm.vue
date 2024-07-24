<template>
	<vue-hcaptcha sitekey="**Your sitekey here**"></vue-hcaptcha>
</template>

<script>
import { loadState } from '@nextcloud/initial-state'

const APPLICATION_NAME = 'ecloud-accounts'

export default {
	data() {
		return {
			siteKey: loadState(APPLICATION_NAME, 'hCaptchaSiteKey')
		}
	}
}
</script>
