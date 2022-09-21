<template>
  <SettingsSection
    :title="t('ecloud-accounts', 'Delete Shop Account')"
    :description="
      t(
        'ecloud-accounts',
        'Set your shop account preferences when your Murena ID is deleted'
      )
    "
  >
  
  </SettingsSection>
</template>
<script>
import { loadState } from "@nextcloud/initial-state";
import SettingsSection from "@nextcloud/vue/dist/Components/SettingsSection.js";
import Axios from "@nextcloud/axios";
import { generateUrl } from "@nextcloud/router";
import { showError } from "@nextcloud/dialogs";

export default {
  name: "PersonalSettings",
  components: {
    SettingsSection,
  },
  data() {
    return {
      deleteShopAccount: false,
      shopEmailPostDelete: "",
      appName: "ecloud-accounts",
    };
  },
  created() {
    try {
      this.deleteShopAccount = loadState(
        this.appName,
        "shop_email_post_delete"
      );
      this.shopEmailPostDelete = loadState(this.appName, "delete_shop_account");
    } catch (e) {
      console.error("Error fetching initial state", e);
    }
  },
  methods: {
    async updateDeleteShopPreference() {
      try {
        const url = generateUrl(
          `/apps/${this.appName}/set_shop_delete_preference`
        );
        const { status } = await Axios.post(url, {});
        if (status !== 200) {
          showError(
            t("drop_account", "Error while setting shop delete preference")
          );
        }
      } catch (e) {
        showError(
          t("drop_account", "Error while setting shop delete preference")
        );
      }
    },
    async updateEmailPostDelete() {
      try {
        const url = generateUrl(
          `/apps/${this.appName}/set_shop_email_post_delete`
        );
        const { status } = await Axios.post(url, {});
        if (status !== 200) {
          showError(
            t("drop_account", "Error while setting shop email preference")
          );
        }
      } catch (e) {
        showError(
          t("drop_account", "Error while setting shop email preference")
        );
      }
    },
  },
};
</script>
