<template>
	<SettingsSection :title="t('ecloud-accounts', 'Options')" 
						:description ="description">
		<div v-if="!onlyUser && !onlyAdmin" id="delete-shop-account-settings">
			<div>
				<input id="shop-accounts_confirm"
					v-model="deleteShopAccount"
					type="checkbox"
					name="shop-accounts_confirm"
					class="checkbox"
					@change="updateDeleteShopPreference()">
				<label for="shop-accounts_confirm">{{
					t(
						"ecloud-accounts",
						"I also want to delete my shop account"
					)
				}}</label>
			</div>
			<div v-if="!deleteShopAccount">
				<label for="shop-alternate-email">
					{{
						t(
							"ecloud-accounts",
							"If you want to keep your shop account please validate or modify the email address below. This email address will become your new login to the shop."
						)
					}}
				</label>
				<input id="shop-alternate-email"
					v-model="shopEmailPostDelete"
					type="email"
					name="shop-alternate-email"
					:placeholder="('ecloud-accounts', 'Email Address')"
					class="form-control"
					@input="updateEmailPostDelete()">
			</div>
		</div>
		<p v-if="onlyUser" class="warnings">
			{{
				t(
					"drop_account",
					"You are the only user of this instance, you can't delete your account."
				)
			}}
		</p>
		<p v-if="onlyAdmin" class="warnings">
			{{
				t(
					"drop_account",
					"You are the only admin of this instance, you can't delete your account."
				)
			}}
		</p>
	</SettingsSection>
</template>

<script>
import { loadState } from '@nextcloud/initial-state'
import SettingsSection from '@nextcloud/vue/dist/Components/SettingsSection.js'
import Axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { showError } from '@nextcloud/dialogs'
import { debounce } from 'lodash'

export default {
	name: 'PersonalSettings',
	components: {
		SettingsSection,
	},
	data() {
		return {
			deleteShopAccount: false,
			shopEmailPostDelete: '',
			shopEmailDefault: '',
			appName: 'ecloud-accounts',
			userEmail: '',
			onlyAdmin: false,
			onlyUser: false,
			orderCount: 0,
			description: this.t('ecloud-accounts', 'We are going to proceed with your cloud account suppression. Check the box below if you also want to delete the associated shop account.')
		}
	},
	created() {
		try {
			this.onlyUser = loadState(this.appName, 'only_user')
			this.onlyAdmin = loadState(this.appName, 'only_admin')
			this.deleteShopAccount = loadState(this.appName, 'delete_shop_account')
			this.shopEmailPostDelete = loadState(this.appName, 'shop_email_post_delete')
			this.shopEmailDefault = loadState(this.appName, 'shop_email_post_delete')
			this.userEmail = loadState(this.appName, 'email')
			this.disableOrEnableDeleteAccount()
			this.getOrderCount()

		} catch (e) {
			console.error('Error fetching initial state', e)
		}
	},
	methods: {
		async disableOrEnableDeleteAccount() {
			if (!this.deleteShopAccount) {
				const status = await this.callAPIToUpdateEmail()
				if (status !== 200) {
					this.disableDeleteAccountEvent()
				}
			} else {
				this.enableDeleteAccountEvent()
			}
		},
		async enableDeleteAccountEvent() {
			const elem = document.getElementById('body-settings')
			const event = new Event('enable-delete-account')
			elem.dispatchEvent(event)
		},
		async disableDeleteAccountEvent(status = null) {
			const elem = document.getElementById('body-settings')
			const event = new Event('disable-delete-account')
			elem.dispatchEvent(event)
		},
		async getOrderCount() {
			try {
				const url = generateUrl(
					`/apps/${this.appName}/shop-accounts/order_info`
				)
				const { status, data } = await Axios.get(url)
				if (status === 200) {
					this.orderCount = data['count']
					if (this.orderCount > 0) {
						const myOrdersUrl = data['my_orders_url']
						let ordersDescription = this.t('For your information you have %d invoices in your account. Click <a href="%s">here</a> to download them.')
						ordersDescription = ordersDescription.replace('%d', data['count']).replace('%s', myOrdersUrl)
						this.description += ordersDescription
					}
				}
			} catch (e) {
			}
		},
		async updateDeleteShopPreference() {
			this.disableOrEnableDeleteAccount()
			try {
				const url = generateUrl(
					`/apps/${this.appName}/shop-accounts/set_shop_delete_preference`
				)
				const { status } = await Axios.post(url, {
					deleteShopAccount: this.deleteShopAccount,
				})
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
		async callAPIToUpdateEmail() {
			try {
				const url = generateUrl(
					`/apps/${this.appName}/shop-accounts/set_shop_email_post_delete`
				)
				const { status } = await Axios.post(url, {
					shopEmailPostDelete: this.shopEmailPostDelete,
				})
				return status
			} catch (err) {
				return err.response.status
			}
		},
		updateEmailPostDelete: debounce(async function(e) {
			if (this.shopEmailPostDelete === this.userEmail) {
				showError(
					t(
						'ecloud-accounts',
						"Shop email cannot be same as this account's email!"
					)
				)
			} else {
				const status = await this.callAPIToUpdateEmail()
				if (status !== 200) {
					this.disableDeleteAccountEvent()
					showError(
						t(
							'ecloud-accounts',
							'Invalid Shop Email!'
						)
					)
				}
				else {
					this.enableDeleteAccountEvent()
				}
			}
		}, 1000),
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
  transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
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
