<template>
	<SettingsSection v-if="shopUsers.length > 0" :title="t(appName, 'Options')">
		<div>
			<p>
				{{
					t(appName, 'We are going to proceed with your cloud account suppression.')
				}}
				<span v-if="!hasActiveSubscription">
					{{
						t(appName, 'Check the box below if you also want to delete the associated shop account(s).')
					}}
				</span>
			</p>

			<p><span v-if="orderCount > 0" v-html="ordersDescription" /></p>
			<p v-if="hasActiveSubscription">
				<b>
					{{
						t(appName, 'A subscription is active in this account. Please cancel it or let it expire before deleting your account.')
					}}
				</b>
			</p>

			<form @submit.prevent>
				<div v-if="!onlyUser && !onlyAdmin" id="delete-shop-account-settings">
					<div class="delete-shop-input">
						<input id="shop-accounts_confirm"
							v-model="deleteShopAccount"
							type="checkbox"
							name="shop-accounts_confirm"
							class="checkbox"
							:disabled="hasActiveSubscription || !allowDelete"
							@change="updateDeleteShopPreference()">
						<label for="shop-accounts_confirm">{{
							t(
								appName,
								"I also want to delete my shop account"
							)
						}}</label>
					</div>
					<div v-if="!deleteShopAccount" class="delete-shop-input">
						<label for="shop-alternate-email">
							{{
								t(
									appName,
									"If you want to keep your shop account please validate or modify the email address below. This email address will become your new login to the shop."
								)
							}}
						</label>
						<input id="shop-alternate-email"
							v-model="shopEmailPostDelete"
							type="email"
							name="shop-alternate-email"
							:placeholder="t(appName, 'Email Address')"
							class="form-control"
							:disabled="hasActiveSubscription || !allowDelete"
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

const APPLICATION_NAME = 'ecloud-accounts'

export default {
	name: 'DeleteShopAccountSetting',
	components: {
		SettingsSection,
	},
	data() {
		return {
			shopUsers: [],
			deleteShopAccount: loadState(APPLICATION_NAME, 'delete_shop_account'),
			shopEmailPostDelete: loadState(APPLICATION_NAME, 'shop_email_post_delete'),
			shopEmailDefault: loadState(APPLICATION_NAME, 'shop_email_post_delete'),
			appName: APPLICATION_NAME,
			userEmail: loadState(APPLICATION_NAME, 'email'),
			loading: true,
			showError: false,
			allowDelete: true,
			ordersDescription: '',
		}
	},
	computed: {
		hasActiveSubscription() {
			for (let index = 0; index < this.shopUsers.length; index++) {
				if (this.shopUsers[index].has_active_subscription) {
					return true
				}
			}
			return false
		},
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
		setOrderDescription() {
			if (this.shopUsers.length === 1) {
				const ordersDescription = this.t(APPLICATION_NAME, "For your information you have %d order(s) in <a class='text-color-active' href='%s' target='_blank'>your account</a>.")
				const orderCount = this.shopUsers[0].order_count
				const myOrdersUrl = this.shopUsers[0].my_orders_url
				this.ordersDescription = ordersDescription.replace('%d', orderCount).replace('%s', myOrdersUrl)
			} else if (this.shopUsers.length >= 1) {
				let ordersDescription = this.t(APPLICATION_NAME, 'For your information you have %d order(s) in your accounts: ')

				const orderCount = this.shopUsers.reduce((accumulator, user) => {
					return accumulator + user.order_count
				}, 0)
				ordersDescription = ordersDescription.replace('%d', orderCount)

				const links = this.shopUsers.map((user, index) => {
					return `<a href='${user.shop_url}' target='_blank'>[${index}]</a>`
				})
				this.ordersDescription = ordersDescription + links.join(' ')
			}
		},
		async getShopUsers() {
			try {
				const url = generateUrl(
					`/apps/${this.appName}/shop-accounts/users`
				)
				const { data } = await Axios.get(url)
				this.shopUsers = data
				this.setOrderDescription()
				this.loading = false
			} catch (e) {
				this.disableDeleteAccountEvent()
				showError(
					t(APPLICATION_NAME, 'Temporary error contacting murena.com; please try again later!')
				)
				this.allowDelete = false
				this.loading = false
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
						t(APPLICATION_NAME, 'Error while setting shop delete preference')
					)
				}
			} catch (e) {
				showError(
					t(APPLICATION_NAME, 'Error while setting shop delete preference')
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
						APPLICATION_NAME,
						"Murena.com email cannot be same as this account's email."
					)
				)
			} else {
				const { status, data } = await this.callAPIToUpdateEmail()
				if (status !== 200) {
					this.disableDeleteAccountEvent()
					showError(
						t(
							APPLICATION_NAME,
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
