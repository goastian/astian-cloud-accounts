<template>
	<div id="successSection">
		<section id="success" style="">
			<div id="successMessages" class="notification isa_success has-text-centered">
				<h3 class="success__title" v-html="success.successMessage" />
				<p id="accountCreatedMsg" class="font-16" v-html="success.accountCreated" />
				<button :wide="true"
					class="btn-primary w-50"
					type="primary"
					@click="useMyAccount">
					{{ getLocalizedText(buttons.useMyAccountNow) }}
				</button>
				<p id="moreDetailMsg" class="font-16" v-html="success.supportMessage" />
			</div>
		</section>
	</div>
</template>
