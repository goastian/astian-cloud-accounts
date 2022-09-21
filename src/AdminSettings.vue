<template>
	<SettingsSection :title="t('drop_account', 'Account deletion')"
		:description="t('drop_account', 'Allows users to delete themselves their own account.')">
		<form>
			<fieldset>
				<legend style="font-size: 1rem;">
					{{ t('drop_account', 'Email confirmation') }}
				</legend>
				<p>
					<input id="drop_account_require_confirmation"
						v-model="requireConfirmation"
						type="checkbox"
						name="drop_account_require_confirmation"
						class="checkbox"
						@change="toggleRequireConfirmation">
					<label for="drop_account_require_confirmation">
						{{ t('drop_account', 'Require confirmation by email') }}
					</label>
					<br>
					<em>{{ t('drop_account', 'Will require users to click a confirmation link sent by email to confirm their action.') }}</em>
				</p>
			</fieldset>
			<fieldset>
				<legend style="font-size: 1rem;">
					{{ t('drop_account', 'Data purge') }}
				</legend>
				<em style="margin-bottom: 1rem;display: block;">{{ t('drop_account', 'Users are not removed right away but disabled until a background job removed their data for good. In the meanwhile, admins can "save" users by enabling them back.') }}</em>
				<p>
					<input id="drop_account_delay_purge_right_away"
						v-model="delayPurge"
						type="radio"
						name="drop_account_delay_purge"
						class="radio"
						value="no"
						@change="toggleDelayPurge">
					<label for="drop_account_delay_purge_right_away">
						{{ t('drop_account', 'Purge user data as soon as possible.') }}
					</label>
					<br>
					<em>{{ t('drop_account', 'Uses the next background job available to completely remove the user\'s data.') }}</em>
				</p>
				<p>
					<input id="drop_account_delay_purge_delayed"
						v-model="delayPurge"
						type="radio"
						name="drop_account_delay_purge"
						class="radio"
						value="yes"
						@change="toggleDelayPurge">
					<label for="drop_account_delay_purge_delayed">
						{{ t('drop_account', 'Purge user data after a while.') }}
					</label>
					<br>
					<em>{{ t('drop_account', 'Deletes user data after a grace period.') }}</em>
					<br>
					<label>{{ t('drop_account', 'Grace period duration') }}</label>
					<select id="drop_account_delay_purge_delay"
						v-model="delayPurgeHours"
						name="drop_account_delay_purge"
						class="select"
						:disabled="delayPurge === 'no'"
						@change="changeDelayPurgeHours">
						<option value="24">
							{{ t('drop_account', 'One day') }}
						</option>
						<option value="168">
							{{ t('drop_account', 'One week') }}
						</option>
						<option value="720">
							{{ t('drop_account', 'One month') }}
						</option>
					</select>
				</p>
			</fieldset>
		</form>
	</SettingsSection>
</template>
<script>
import { loadState } from '@nextcloud/initial-state'
import SettingsSection from '@nextcloud/vue/dist/Components/SettingsSection.js'
import { showError } from '@nextcloud/dialogs'

export default {
	name: 'AdminSettings',
	components: {
		SettingsSection,
	},
	data() {
		return {
			requireConfirmation: false,
			delayPurge: 'no',
			delayPurgeHours: '24',
			appName: 'drop_account',
		}
	},
	created() {
		try {
			this.requireConfirmation = loadState(this.appName, 'requireConfirmation')
			this.delayPurge = loadState(this.appName, 'delayPurge')
			this.delayPurgeHours = loadState(this.appName, 'delayPurgeHours')
		} catch (e) {
			console.error('Error fetching initial state', e)
		}
	},
	methods: {
		toggleRequireConfirmation() {
			try {
				this.OCP.AppConfig.setValue(this.appName, 'requireConfirmation', this.requireConfirmation ? 'yes' : 'no')
			} catch (e) {
				console.error(e)
				showError(t('drop_account', 'Error while changing require confirmation setting'))
			}
		},
		toggleDelayPurge() {
			try {
				this.OCP.AppConfig.setValue(this.appName, 'delayPurge', this.delayPurge)
			} catch (e) {
				console.error(e)
				showError(t('drop_account', 'Error while changing delay purge setting'))
			}
		},
		changeDelayPurgeHours() {
			try {
				this.OCP.AppConfig.setValue(this.appName, 'delayPurgeHours', this.delayPurgeHours)
			} catch (e) {
				console.error(e)
				showError(t('drop_account', 'Error while changing delay purge hours setting'))
			}
		},
	},

}
</script>
