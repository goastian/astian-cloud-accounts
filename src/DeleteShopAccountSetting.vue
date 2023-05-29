<template>
	<SettingsSection v-if="shopUsers.length > 0" :title="t('ecloud-accounts', 'Options')">
		<div>
			<p>
				{{
					t('ecloud-accounts', 'We are going to proceed with your cloud account suppression.')
				}}
				<span v-if="subscriptionCount === 0">
					{{
						t('ecloud-accounts', 'Check the box below if you also want to delete the associated shop account(s).')
					}}
				</span>
			</p>

			<ShopUserOrders v-for="(s, index) in shopUsers"
				:key="s.id"
				:index="index"
				v-bind="s" />

			<form @submit.prevent>
				<div v-if="!onlyUser && !onlyAdmin" id="delete-shop-account-settings">
					<div class="delete-shop-input">
						<input id="shop-accounts_confirm"
							v-model="deleteShopAccount"
							type="checkbox"
							name="shop-accounts_confirm"
							class="checkbox"
							:disabled="subscriptionCount > 0 || !allowDelete"
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
							:disabled="subscriptionCount > 0 || !allowDelete"
							@blur="updateEmailPostDelete($event)">
					</div>
				</div>
			</form>
		</div>
	</SettingsSection>
</template>

<script>
import { loadState } from '@nextcloud/initial-state'
import SettingsSection from '@nextcloud/vue/dist/Components/SettingsSection.js'
import Axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { showError } from '@nextcloud/dialogs'
import ShopUserOrders from './components/ShopUserOrders.vue'

const APPLICATION_NAME = 'ecloud-accounts'

export default {
	name: 'DeleteShopAccountSetting',
	components: {
		SettingsSection,
		ShopUserOrders,
	},
	data() {
		return {
			shopUsers: [],
			deleteShopAccount: loadState(APPLICATION_NAME, 'delete_shop_account'),
			shopEmailPostDelete: loadState(APPLICATION_NAME, 'shop_email_post_delete'),
			shopEmailDefault: loadState(APPLICATION_NAME, 'shop_email_post_delete'),
			appName: APPLICATION_NAME,
			userEmail: loadState(APPLICATION_NAME, 'email'),
			orderCount: 0,
			subscriptionCount: 0,
			loading: true,
			showError: false,
			allowDelete: true,
		}
	},
	mounted() {
		this.getShopUsers()
	},
	created() {
		this.disableOrEnableDeleteAccount()
	},
	methods: {
		async disableOrEnableDeleteAccount() {
			if (this.shopUsers.length > 0 && !this.deleteShopAccount) {
				this.disableDeleteAccountEvent()
				let hasActiveSubscription = false
				for (let i = 0; i < this.shopUsers.length; i++) {
					if (this.shopUsers[i].has_active_subscription) {
						hasActiveSubscription = true
						break
					}
				}
				if (hasActiveSubscription) {
					this.allowDelete = false
					return
				}

				const status = await this.checkShopEmailPostDelete()
				if (status === 200) {
					this.enableDeleteAccountEvent()
				}
			} else {
				this.enableDeleteAccountEvent()
			}
		},
		async checkShopEmailPostDelete() {
			try {
				const url = generateUrl(
					`/apps/${this.appName}/shop-accounts/check_shop_email_post_delete`,

				)
				const { status } = await Axios.get(
					url,
					{
						params: {
							shopEmailPostDelete: this.shopEmailPostDelete,
						},
					}
				)
				return status
			} catch (err) {
				return err.response.status
			}
		},
		async getShopUsers() {
			try {
				const url = generateUrl(
					`/apps/${this.appName}/shop-accounts/users`
				)
				const { status, data } = await Axios.get(url)
				if (status === 200) {
					this.shopUsers = data
				}
				if (status === 400) {
					this.enableDeleteAccountEvent()
				}
			} catch (e) {
			}
		},
		async getOrdersInfo() {
			try {
				const url = generateUrl(
					`/apps/${this.appName}/shop-accounts/order_info?user=${this.shopUser.id}`
				)
				const { status, data } = await Axios.get(url)
				if (status === 200) {
					this.orderCount = data.order_count
					if (this.orderCount) {
						this.ordersDescription = this.ordersDescription.replace('%d', this.orderCount).replace('%s', data.my_orders_url)
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
				const { status, data } = await Axios.post(url, {
					shopEmailPostDelete: this.shopEmailPostDelete,
				})
				return { status, data }
			} catch (err) {
				return { status: err.response.status, data: err.response.data }
			}
		},
		async updateEmailPostDelete(event) {
			if (document.activeElement === event.target) {
				return
			}
			if (this.shopEmailPostDelete === this.userEmail) {
				showError(
					t(
						'ecloud-accounts',
						"Murena.com email cannot be same as this account's email."
					)
				)
			} else {
				const { status, data } = await this.callAPIToUpdateEmail()
				if (status !== 200) {
					this.disableDeleteAccountEvent()
					showError(
						t(
							'ecloud-accounts',
							data.message
						)
					)
				} else {
					this.enableDeleteAccountEvent()
				}
			}
		},
		enableDeleteAccountEvent() {
			const elem = document.getElementById('body-settings')
			const event = new Event('enable-delete-account')
			elem.dispatchEvent(event)
		},
		disableDeleteAccountEvent() {
			const elem = document.getElementById('body-settings')
			const event = new Event('disable-delete-account')
			elem.dispatchEvent(event)
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
input#shop-alternate-email:disabled {
    background: var(--color-background-dark);
}
.delete-shop-input {
	margin-bottom: 1em;
}
</style>
