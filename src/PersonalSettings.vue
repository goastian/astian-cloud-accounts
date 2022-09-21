<template>
	<SettingsSection :title="t('ecloud-accounts', 'Delete shop account')"
		:description="t('ecloud-accounts', 'You can delete your shop account with deleting ecloud account.')">
		<div id="delete-shop-account-settings" v-if="!onlyUser && !onlyAdmin">
			<div>
				<input id="shop-accounts_confirm"
					v-model="checked"
					type="checkbox"
					name="shop-accounts_confirm"
					class="checkbox"
					checked>
				<label for="shop-accounts_confirm">{{ t('ecloud-accounts', 'Check this to confirm the deletion request for shop account') }}</label>
			</div>
			<div>
				<input id="shop-alternate-email"
					type="email"
					:disabled="checked"
					name="shop-alternate-email"
					:placeholder="('ecloud-accounts', 'Email Address')"
					class="form-control">
			</div>
		</div>
		<p v-if="onlyUser" class="warnings">
				{{ t('drop_account', 'You are the only user of this instance, you can\'t delete your account.') }}
			</p>
		<p v-if="onlyAdmin" class="warnings">
				{{ t('ecloud-accounts', 'You are the only admin of this instance, you can\'t delete your account.') }}
		</p>
	</SettingsSection>
</template>


<script>
import { loadState } from '@nextcloud/initial-state'
import SettingsSection from '@nextcloud/vue/dist/Components/SettingsSection.js'
import Axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { showError } from '@nextcloud/dialogs'

export default {
	name: 'PersonalSettings',
	components: {
		SettingsSection,
	},
	data() {
		return {
			deleteShopAccount: false,
			shopEmailPostDelete: '',
			appName: 'ecloud-accounts',
			checked: false,
			onlyAdmin: false,
			onlyUser: false,
		}
	},
	created() {
		try {
			this.onlyUser = loadState(this.appName, 'only_user')
			this.onlyAdmin = loadState(this.appName, 'only_admin')
			this.deleteShopAccount = loadState(this.appName,'shop_email_post_delete')
			this.shopEmailPostDelete = loadState(this.appName, 'delete_shop_account')
		} catch (e) {
			console.error('Error fetching initial state', e)
		}
	},
	methods: {
		async updateDeleteShopPreference() {
			try {
				const url = generateUrl(
					`/apps/${this.appName}/set_shop_delete_preference`
				)
				const { status } = await Axios.post(url, {})
				if (status !== 200) {
					showError(
						t('ecloud-accounts', 'Error while setting shop delete preference')
					)
				}
			} catch (e) {
				showError(
					t('ecloud-accounts', 'Error while setting shop delete preference')
				)
			}
		},
		async updateEmailPostDelete() {
			try {
				const url = generateUrl(
					`/apps/${this.appName}/set_shop_email_post_delete`
				)
				const { status } = await Axios.post(url, {})
				if (status !== 200) {
					showError(
						t('ecloud-accounts', 'Error while setting shop email preference')
					)
				}
			} catch (e) {
				showError(
					t('ecloud-accounts', 'Error while setting shop email preference')
				)
			}
		},
	},
}
</script>
<style>
	#delete-shop-account-settings .form-control {
		display: block;
		width: 300px;
		padding: 0.375rem 0.75rem;
		font-size: 1rem;
		font-weight: 400;
		line-height: 1.5;
		color: #212529;
		background-color: #fff;
		border: 1px solid #a2a2a2;
		border-radius: 0.25rem;
		transition: border-color .15s ease-in-out,box-shadow .15s ease-in-out;
		margin-top: 20px;
	}
	#delete-shop-account-settings .form-control:focus {
		color: #212529;
		background-color: #fff;
		border-color: #86b7fe;
		outline: 0;
		box-shadow: 0 0 0 0.25rem rgb(13 110 253 / 25%);
	}
</style>