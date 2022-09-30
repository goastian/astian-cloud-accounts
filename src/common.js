import Vue from 'vue'
import { translate, translatePlural } from '@nextcloud/l10n'
import '@nextcloud/dialogs/styles/toast.scss'

Vue.prototype.OC = window.OC
Vue.prototype.OCP = window.OCP
Vue.prototype.t = translate
Vue.prototype.n = translatePlural
