<template>
	<SettingsSection>
		<div v-if="!isBetaUser" class="section padding-0">
			<h2>
				{{
					t(appName, 'Do you want to become a beta user?')
				}}
			</h2>
			<p class="settings-hint">
				{{
					t(appName, 'You want to experiment new features ahead of the others and provide feedback on them before and if they\'re released? This section is made for you!')
				}}
			</p>
			<p class="settings-hint">
				{{
					t(appName, 'To get a preview of our new features you need to become part of our beta users.To do so, simply click on the button below.You can opt out of beta features at anytime.')
				}}
			</p>
			<div id="groups" class="aliases-info">
				<input type="button"
					class="width300"
					:value="t(appName, 'Become a beta user')"
					@click="becomeBetaUser()">
			</div>
			<div class="margin-top-10">
				<p class="settings-hint">
					{{ t(appName, 'Here is the list of currently available beta features: ') }}
				</p>
				<ul class="beta-apps settings-hint">
					<li v-for="app in betaApps" :key="app">
						{{ app }}
					</li>
				</ul>
			</div>
		</div>
		<div v-if="isBetaUser" class="section padding-0">
			<h2>
				{{ t(appName,'You are part of the beta users.') }}
			</h2>
			<p class="settings-hint">
				{{ t(appName,'Note : as the features are not released yet, you may encounter some bugs. Please report them or give your feedback using the form below.') }}
			</p>
			<div>
				<p class="settings-hint">
					{{ t(appName,'Here is the list of currently available beta features:') }}
				</p>
				<ul class="beta-apps settings-hint">
					<li v-for="app in betaApps" :key="app">
						{{ app }}
					</li>
				</ul>
			</div>
			<form id="issue-submit-form" class="mt-20" @submit="submitFeedback">
				<p>
					<label id="title_label" for="title">
						<b>
							{{ t(appName,'Title') }} <sup class="color-red">*</sup>
						</b>
					</label>
				</p>
				<p>
					<input id="title"
						v-model="title"
						type="text"
						:placeholder="t(appName, 'Summary of your feedback')">
				</p>
				<p class="mt-20">
					<label id="description_label" for="description">
						<b>
							{{ t(appName,'Description') }} <sup class="color-red">*</sup>
						</b>
					</label>
				</p>
				<p>
					<textarea id="description" v-model="description" :placeholder="t(appName, 'Please give us as many details as possible')" />
				</p>
				<p class="mt-20">
					<input type="submit"
						:value="t(appName, 'Submit')"
						class="width300"
						:disabled="isDisabled">
				</p>
			</form>
			<p class="settings-hint mt-20">
				{{ t(appName,'Want to take a break from beta features? Just click on the button below. You can become a beta user again anytime!') }}
			</p>
			<div id="beta-form">
				<input type="submit"
					class="width300 btn-optout"
					:value="t(appName, 'Opt out of beta features')"
					@click="optOutFromBetaUser()">
			</div>
		</div>
	</SettingsSection>
</template>

<script>
import { loadState } from '@nextcloud/initial-state'
import SettingsSection from '@nextcloud/vue/dist/Components/SettingsSection.js'
import Axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { showSuccess, showError } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'

export default {
	name: 'BecomeBetaUser',
	components: {
		SettingsSection,
	},
	data() {
		return {
			appName: 'ecloud-accounts',
			isBetaUser: loadState('ecloud-accounts', 'is_beta_user'),
			betaApps: loadState('ecloud-accounts', 'beta_apps'),
			title: '',
			description: '',
			loading: true,
		}
	},
	computed: {
		isDisabled() {
			return (this.description === '' || this.title === '')
		},
	},
	methods: {
		async becomeBetaUser() {
			try {
				const url = generateUrl(
					`/apps/${this.appName}/beta/add`
				)
				await Axios.get(url)
				this.isBetaUser = true
				showSuccess(t(this.appName, 'Congratulations! You\'ve successfully been added to the beta users.'))
			} catch (e) {
				showError(t(this.appName, 'Something went wrong.'))
			}
		},
		async optOutFromBetaUser() {
			try {
				const url = generateUrl(
					`/apps/${this.appName}/beta/remove`
				)
				await Axios.get(url)
				this.isBetaUser = false
				showSuccess(t(this.appName, 'You no longer have access to experimental features.'))
			} catch (e) {
				showError(t(this.appName, 'Something went wrong.'))
			}
		},
		async submitFeedback(e) {
			e.preventDefault()
			try {
				const url = generateUrl(
					`/apps/${this.appName}/issue/submit`
				)
				await Axios.post(url, { title: this.title, description: this.description })
				showSuccess(t(this.appName, 'Issue submitted successfully.'))
				this.description = ''
				this.title = ''
			} catch (e) {
				showError(t(this.appName, 'Something went wrong.'))
			}
		},
	},
}
</script>
<style>
.alert {
	position: relative;
	margin-top: 1rem;
	margin-bottom: 1rem;
	border: 1px solid transparent;
	border-radius: 0.25rem;
	width: fit-content;
}

.alert-success {
	color: #155724;
	background-color: #d4edda;
	border-color: #c3e6cb;
	padding: 0.75rem 1.25rem;
}

.alert-fail {
	color: #721c24;
	background-color: #f8d7da;
	border-color: #f5c6cb;
	padding: 0.75rem 1.25rem;
}

#issue-submit-form #title,
#issue-submit-form textarea {
	width: 450px;
}

#issue-submit-form textarea {
	height: 100px;
}

#issue-submit-form textarea:hover {
	border-color: var(--color-primary-element) !important;
}

.mt-20 {
	margin-top: 20px !important;
}

#beta-form .btn-optout {
	background-color: white;
	color: var(--color-delete);
	border-color: var(--color-delete);
}

#beta-form .width300,
#issue-submit-form .width300 {
	width: 300px;
}

.color-red {
	color: red;
}

ul.beta-apps {
	list-style: none;
	margin-left: 0;
	padding-left: 1em;
}

ul.beta-apps > li:before {
	display: inline-block;
	content: '-';
	width: 1em;
	margin-left: -1em;
}

.padding-0{
	padding: 0;
}
</style>
