<template>
	<SettingsSection :title="t('ecloud-accounts', 'Options')">
		<p>
			{{
				t('ecloud-accounts', 'We are going to proceed with your cloud account suppression. Check the box below if you also want to delete the associated shop account.')
			}}
			<span v-if="orderCount" v-html="ordersDescription" />
		</p>
		<form>
			<div v-if="!onlyUser && !onlyAdmin" id="delete-shop-account-settings">
				<div class="delete-shop-input">
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
				<div v-if="!deleteShopAccount" class="delete-shop-input">
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
						:placeholder="t('ecloud-accounts', 'Email Address')"
						class="form-control"
						@input="updateEmailPostDelete()">
				</div>
			</div>
		</form>
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
			ordersDescription: this.t('ecloud-accounts', "For your information you have %d order(s) in <a class='text-color-active' href='%s'>your account</a>."),
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
			this.getOrdersInfo()

		} catch (e) {
			console.error('Error fetching initial state', e)
		}
	},
	methods: {
		async disableOrEnableDeleteAccount() {
			if (!this.deleteShopAccount) {
				this.disableDeleteAccountEvent()
				const status = await this.callAPIToUpdateEmail()
				if (status === 200) {
					this.enableDeleteAccountEvent()
				}
			} else {
				this.enableDeleteAccountEvent()
			}
		},
	 	enableDeleteAccountEvent() {
			const elem = document.getElementById('delete-shop-account-settings')
			const event = new Event('enable-delete-account')
			elem.dispatchEvent(event)
		},
		disableDeleteAccountEvent() {
			const elem = document.getElementById('delete-shop-account-settings')
			const event = new Event('disable-delete-account')
			elem.dispatchEvent(event)
		},
		async getOrdersInfo() {
			try {
				const url = generateUrl(
					`/apps/${this.appName}/shop-accounts/order_info`
				)
				const { status, data } = await Axios.get(url)
				if (status === 200) {
					if (data.count > 0) {
						this.ordersDescription = this.ordersDescription.replace('%d', data.count).replace('%s', data.my_orders_url)
						this.orderCount = data.count
					}
				}
			} catch (e) {
			}
		},
		async updateDeleteShopPreference() {
			await this.disableOrEnableDeleteAccount()
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
				} else {
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

.delete-shop-input {
	margin-bottom: 1em;
}
</style>
