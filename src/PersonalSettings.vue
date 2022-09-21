<template>
	<SettingsSection :title="t('drop_account', 'RONAK Delete my wp account')"
		:description="t('drop_account', 'Deleting your account will delete all your files and data from the apps you use, such as calendar and contacts.')">
		<EmptyContent v-if="deleted" icon="icon-delete">
			{{ t('drop_account', 'Account marked for deletion') }}
			<template #desc>
				<p>
					{{ t('drop_account', 'Your account has been disabled and the data will be removed shortly.') }}
				</p>
				<p>
					{{ t('drop_account', 'You are going to be redirected to the login page in a few seconds…') }}
				</p>
			</template>
		</EmptyContent>
		<EmptyContent v-else-if="confirmationSent" icon="icon-mail">
			{{ t('drop_account', 'Email confirmation required') }}
			<template #desc>
				<p>
					{{ t('drop_account', 'Please click the link into the email we\'ve just sent you to finish deleting your account.') }}
				</p>
			</template>
		</EmptyContent>
		<div v-else id="delete-account-settings">
			<p v-if="willDelayPurge" class="settings-hint">
				{{ n('drop_account', 'This action will be reversible by an administrator for {nbDays} day after you request deletion.', 'This action will be reversible by an administrator for {nbDays} days after you request deletion.',nbDaysForPurge, { nbDays: nbDaysForPurge}) }}
			</p>
			<p v-else class="settings-hint">
				<b>{{ t('drop_account', 'This action is irreversible!') }}</b>
			</p>
			<p v-if="!requireConfirmation" class="settings-hint">
				{{ t('drop_account', 'After confirming the deletion of your account, you will be redirected to the login page.') }}
			</p>
			<p v-if="onlyUser" class="warnings">
				{{ t('drop_account', 'You are the only user of this instance, you can\'t delete your account.') }}
			</p>
			<p v-if="onlyAdmin" class="warnings">
				{{ t('drop_account', 'You are the only admin of this instance, you can\'t delete your account.') }}
			</p>
			<p v-if="!emailIfConfirmation" class="warnings">
				{{ t('drop_account', 'An email confirmation is required by your admin to delete your account. Please fill your email in your personal settings first.') }}
			</p>
			<div v-if="!onlyUser && !onlyAdmin && emailIfConfirmation">
				<h3> {{ t('drop_account', 'Do you really wish to delete your account?') }}</h3>
				<p v-if="requireConfirmation">
					{{ t('drop_account', 'We will send you an email to confirm this action.') }}
				</p>
				<input id="drop_account_confirm"
					v-model="checked"
					type="checkbox"
					name="drop_account_confirm"
					class="checkbox">
				<label for="drop_account_confirm">{{ t('drop_account', 'Check this to confirm the deletion request') }}</label>
				<br>
				<h3> {{ t('drop_account', 'Do you really wish to delete Shop account?') }}</h3>
				<p v-if="requireConfirmation">
					{{ t('drop_account', 'We will send you an email to confirm this action.') }}
				</p>
				<input id="drop_shop_account_confirm"
					v-model="checked"
					type="checkbox"
					name="drop_shop_account_confirm"
					class="checkbox">
				<label for="drop_shop_account_confirm">{{ t('drop_account', 'Check this to confirm the deletion request for shop account') }}</label>
				<br>
				<p>
					<button id="deleteaccount"
						class="button"
						:disabled="!checked"
						@click="deleteAccount">
						<span class="icon icon-delete" />
						<span class="icon icon-loading-small" style="display: none" />
						<span>{{ t('drop_account', 'Delete my account') }}</span>
					</button>
				</p>
				<p v-show="deleting" class="deleting-data-msg">
					{{ t('drop_account', 'Deleting your data…') }}
				</p>
			</div>
		</div>
	</SettingsSection>
</template>
<script>
import { loadState } from '@nextcloud/initial-state'
import SettingsSection from '@nextcloud/vue/dist/Components/SettingsSection.js'
import Axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { showError } from '@nextcloud/dialogs'
import confirmPassword from '@nextcloud/password-confirmation'
import EmptyContent from '@nextcloud/vue/dist/Components/EmptyContent.js'

export default {
	name: 'PersonalSettings',
	components: {
		SettingsSection,
		EmptyContent,
	},
	data() {
		return {
			onlyAdmin: false,
			onlyUser: false,
			requireConfirmation: false,
			hasEmailForConfirmation: false,
			appName: 'drop_account',
			checked: false,
			deleting: false,
			deleted: false,
			confirmationSent: false,
			willDelayPurge: false,
			delayPurgeHours: 24,
		}
	},
	computed: {
		emailIfConfirmation() {
			if (this.requireConfirmation) {
				return this.hasEmailForConfirmation
			}
			return true
		},
		nbDaysForPurge() {
			if (this.willDelayPurge && this.delayPurgeHours) {
				return ~~(this.delayPurgeHours / 24)
			}
			return 0
		},
	},
	created() {
		try {
			this.onlyUser = loadState(this.appName, 'only_user')
			this.onlyAdmin = loadState(this.appName, 'only_admin')
			this.requireConfirmation = loadState(this.appName, 'require_confirmation')
			this.hasEmailForConfirmation = loadState(this.appName, 'has_email_for_confirmation')
			this.willDelayPurge = loadState(this.appName, 'will_delay_purge')
			this.delayPurgeHours = loadState(this.appName, 'delay_purge_hours')
		} catch (e) {
			console.error('Error fetching initial state', e)
		}
	},
	methods: {
		async deleteAccount() {
			try {
				await confirmPassword()
				const url = generateUrl(`/apps/${this.appName}/delete`)
				const { status } = await Axios.post(url, {})
				if (status === 202) {
					this.deleted = true
					setTimeout(() => OC.reload(), 10000)
				} else if (status === 201) {
					this.confirmationSent = true
				}
			} catch (e) {
				showError(t('drop_account', 'Error while deleting the account'))
			}
		},
	},

}
</script>
