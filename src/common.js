import Vue from 'vue'
import { translate, translatePlural } from '@nextcloud/l10n'
import '@nextcloud/dialogs/style.css'

Vue.prototype.OC = window.OC
Vue.prototype.OCP = window.OCP
Vue.prototype.t = translate
Vue.prototype.n = translatePlural
