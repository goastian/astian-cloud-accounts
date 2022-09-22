<template>
	<SettingsSection :title="t('ecloud-accounts', 'Delete shop account')"
		:description="
			t(
				'ecloud-accounts',
				'You can delete your shop account with deleting ecloud account.'
			)
		">
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
						"Check this to confirm the deletion request for shop account"
					)
				}}</label>
			</div>
			<div v-if="!deleteShopAccount">
				<input id="shop-alternate-email"
					type="email"
					name="shop-alternate-email"
					:placeholder="('ecloud-accounts', 'Email Address')"
					class="form-control"
					v-model="shopEmailPostDelete"
					@input="updateEmailPostDelete()">
			</div>
			<div v-if="orderCount > 0">
				{{
					t(
						"ecloud-accounts", 
						"You have %s orders with your shop account. To check, please go to <a href='https://staging01.murena.com/my-account/orders/'>your shop orders</a>."
					).replace('%s', `${orderCount}`)
				}}
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
		} catch (e) {
			console.error('Error fetching initial state', e)
		}
	},
	methods: {
		async getOrderCount() {
			try {
				const url = generateUrl(
					`/apps/${this.appName}/shop-accounts/get_order_count`
				)
				const { status, data } = await Axios.get(url)
				if (status === 200) {
					this.orderCount = data.count
				}
			} catch (e) {
			}
		},
		async updateDeleteShopPreference() {
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
		updateEmailPostDelete:
			debounce(async function(e) {
				if (this.shopEmailPostDelete === this.userEmail) {
					showError(
						t(
							'ecloud-accounts',
							"Shop email cannot be same as this account's email!"
						)
					)
				} else {
					try {
						const url = generateUrl(
							`/apps/${this.appName}/shop-accounts/set_shop_email_post_delete`
						)
						const { status } = await Axios.post(url, {
							shopEmailPostDelete: this.shopEmailPostDelete,
						})
						if (status !== 200) {
							showError(
								t(
									'ecloud-accounts',
									'Error while setting shop email preference'
								)
							)
						}
					} catch (e) {
						showError(
							t('ecloud-accounts', 'Error while setting shop email preference')
						)
						this.shopEmailPostDelete = this.shopEmailDefault
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
