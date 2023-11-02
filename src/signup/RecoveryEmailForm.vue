<template>
	<div id="recoveryEmailForm">
		<div class="">
			<h1 class="has-text-centered subtitle is-3">
				{{ titles.recoveryEmailForm1 }}
			</h1>
			<h1 class="has-text-centered subtitle is-3">
				{{ titles.recoveryEmailForm2 }}
			</h1>
		</div>

		<div id="fields">
			<div class="field">
				<div class="control">
					<label>{{ getLocalizedText(labels.recoveryEmail) }}</label>
					<input id="email"
						v-model="email"
						name="email"
						type="email"
						class="form-input"
						:placeholder="getLocalizedText(placeholders.recoveryEmail)"
						@input="validateForm(['email'])">
					<p v-if="validation.isEmailEmpty" class="validation-warning">
						{{ getLocalizedText(errors.recoveryEmail) }}
					</p>
				</div>
			</div>
		</div>

		<div id="groups" class="aliases-info display-flex">
			<button :wide="true"
				class="btn-default w-50"
				type="primary"
				@click="submitRecoveryEmailForm(false)">
				{{ getLocalizedText(buttons.later) }}
			</button>
			<button :wide="true"
				class="btn-primary w-50"
				type="primary"
				@click="submitRecoveryEmailForm(true)">
				{{ getLocalizedText(buttons.setRecoverEmail) }}
			</button>
		</div>
	</div>
</template>
