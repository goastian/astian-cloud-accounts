<template>
	<div id="captchaForm">
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
							v-model="humanverification"
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
				type="primary"
				@click="submitCaptchaForm">
				{{ getLocalizedText('Verify') }}
			</button>
		</div>
	</div>
</template>

<script>
export default {
	props: {
		value: Object,
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

		getLocalizedText(text) {
			return t('ecloud-accounts', text)
		},
	},
}
</script>
