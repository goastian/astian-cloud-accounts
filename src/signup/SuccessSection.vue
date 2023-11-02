<template>
	<div id="successSection">
		<section id="success" style="">
			<div id="successMessages" class="notification isa_success has-text-centered">
				<h3 class="success__title">
					{{ getLocalizedText('Success!') }}
				</h3>

				<p id="accountCreatedMsg" class="font-16">
					Your <b>{{ formData.username }}@{{ domain }}</b> account was successfully created.
				</p>

				<button :wide="true"
					class="btn-primary w-50"
					type="primary"
					@click="useMyAccount">
					{{ getLocalizedText('Use My Account Now') }}
				</button>
				<p id="moreDetailMsg" class="font-16" v-html="getLocalizedText('If you want to use your murena.io email in a mail app like Thunderbird, Outlook or another, please visit <a href=\'https://doc.e.foundation/support-topics/configure-email\'>this page</a>.')" />
			</div>
		</section>
	</div>
</template>
<script>
export default {
	props: {
		value: Object,
	},
	data() {
		return {
			domain: window.location.host,
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
		getLocalizedText(text) {
			return t(this.appName, text)
		},
		useMyAccount() {
			window.location.href = window.location.origin
		},

	},
}
</script>

<style scoped>

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

#fields {
	background-color: white;
}

#fields p {
	font-size: 15px;
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

.success__title {
    font-family: 'Montserrat';
    font-style: normal;
    font-weight: 700;
    font-size: 24px;
    line-height: 150%;
    margin: 1em 0 0.5em 0;
    text-align: center;
}
.font-16{
	font-size: 16px;
}
</style>
