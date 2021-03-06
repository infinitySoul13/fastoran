/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

require('./bootstrap');

window.Vue = require('vue');

/**
 * The following block of code may be used to automatically register your
 * Vue components. It will recursively scan this directory for the Vue
 * components and automatically register them with their "basename".
 *
 * Eg. ./components/ExampleComponent.vue -> <example-component></example-component>
 */

// const files = require.context('./', true, /\.vue$/i)
// files.keys().map(key => Vue.component(key.split('/').pop().split('.')[0], files(key).default))
import HighchartsVue from 'highcharts-vue'
Vue.use(HighchartsVue);

import Highcharts from 'highcharts'
import drilldownInit from 'highcharts/modules/drilldown'
drilldownInit(Highcharts)
import exportingInit from 'highcharts/modules/exporting'
exportingInit(Highcharts)
import exportDataInit from 'highcharts/modules/export-data'
exportDataInit(Highcharts)

import Vue2TouchEvents from 'vue2-touch-events'

Vue.use(Vue2TouchEvents)

const moment = require('moment')
require('moment/locale/ru')


Vue.use(require('vue-moment'), {
    moment
})

import VueRecord from '@codekraft-studio/vue-record'

Vue.use(VueRecord)


Vue.component(
    'passport-clients',
    require('./components/passport/Clients.vue').default
);

Vue.component(
    'passport-authorized-clients',
    require('./components/passport/AuthorizedClients.vue').default
);

Vue.component(
    'passport-personal-access-tokens',
    require('./components/passport/PersonalAccessTokens.vue').default
);


Vue.component('rest-menu', require('./components/RestMenu.vue').default);
Vue.component('tags-cloud-list', require('./components/TagsCloud.vue').default);

Vue.component('custom-order', require('./components/CustomOrder.vue').default);
Vue.component('phone-order', require('./components/PhoneOrder.vue').default);
Vue.component('deliveryman-quest-order', require('./components/DeliverymanQuestOrder.vue').default);
Vue.component('rest-info', require('./components/RestInfo.vue').default);
Vue.component('cart', require('./components/Cart.vue').default);
Vue.component('table-cart', require('./components/TableCart.vue').default);
Vue.component('header-fastoran', require('./components/Header.vue').default);
Vue.component('add-cart-btn', require('./components/AddCartBtn.vue').default);
Vue.component('cart-count-index', require('./components/CartCountIndex.vue').default);
Vue.component('contact-form', require('./components/CallbackForm.vue').default);
Vue.component('user-profile', require('./components/UserCabinet.vue').default);
Vue.component('login-form', require('./components/LoginFormApi.vue').default);
Vue.component('order-status', require('./components/OrderStatus.vue').default);
Vue.component('promo-block', require('./components/PromoBlock.vue').default);
Vue.component('chelentano-calc', require('./components/calculators/ChelentanoCalc.vue').default);
Vue.component('calc-slider', require('./components/calculators/CalcSlider.vue').default);
Vue.component('isushi-calc', require('./components/calculators/IsushiCalc.vue').default);
Vue.component('burger-bar-calc', require('./components/calculators/BurgerBarCalc.vue').default);
Vue.component('voice-callback-form', require('./components/VoiceCallbackForm.vue').default);

//Vue.component('simple-order', require('./components/SimpleOrder.vue').default);

//Admin
Vue.component('dashboard', require('./components/admin/Dashboard.vue').default);
Vue.component('statistics', require('./components/admin/Statistics.vue').default);
Vue.component('kitchens', require('./components/admin/Kitchens.vue').default);
Vue.component('orders', require('./components/admin/Orders.vue').default);
Vue.component('new-order', require('./components/admin/NewOrder.vue').default);
Vue.component('order-details', require('./components/admin/OrderDetails.vue').default);
Vue.component('menus', require('./components/admin/Menus.vue').default);
Vue.component('menu-categories-list', require('./components/admin/MenuCategories.vue').default);
Vue.component('restorans-list', require('./components/admin/Restorans.vue').default);
Vue.component('stop-list', require('./components/admin/StopList.vue').default);
Vue.component('users', require('./components/admin/Users.vue').default);


//Mobile
Vue.component('mobile-promo-slider', require('./mobile/components/PromoSlider').default);
Vue.component('mobile-product-item', require('./mobile/components/ProductItem').default);
Vue.component('mobile-product-item-v2', require('./mobile/components/ProductItemV2').default);
Vue.component('mobile-product-controls', require('./mobile/components/ProductControls').default);
Vue.component('mobile-tags-cloud', require('./mobile/components/TagsCloud').default);
Vue.component('mobile-callback-from', require('./mobile/components/CallbackFrom').default);
Vue.component('mobile-restoran-info', require('./mobile/components/RestoranInfo').default);
Vue.component('mobile-product-list', require('./mobile/components/ProductList').default);
Vue.component('mobile-cart-counter', require('./mobile/components/CartCounter').default);
Vue.component('mobile-cart', require('./mobile/components/Cart').default);
Vue.component('mobile-order-status', require('./mobile/components/OrderStatus').default);
Vue.component('mobile-voice-callback-form', require('./mobile/components/VoiceCallbackForm.vue').default);
Vue.component('mobile-phone-order', require('./mobile/components/PhoneOrder.vue').default);
Vue.component('mobile-flowers-order', require('./mobile/components/FlowersOrder.vue').default);
Vue.component('mobile-latest-order', require('./mobile/components/LatestOrders.vue').default);
Vue.component('mobile-calc-slider', require('./mobile/components/calculators/CalcSlider.vue').default);
Vue.component('mobile-custom-order', require('./mobile/components/CustomOrder.vue').default);
Vue.component('mobile-restorans', require('./mobile/components/Restorans.vue').default);
Vue.component('mobile-sidebar-menu', require('./mobile/components/SideBarMenu.vue').default);



/**
 * Next, we will create a fresh Vue application instance and attach it to
 * the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.
 */

import {BootstrapVue, IconsPlugin} from 'bootstrap-vue'
import Notifications from 'vue-notification'
import VueCurrencyFilter from 'vue-currency-filter'
import 'lazysizes';

import VueLazyload from 'vue-lazyload'

Vue.use(VueLazyload)

/*import { VueReCaptcha } from 'vue-recaptcha-v3'

// For more options see below
Vue.use(VueReCaptcha, { siteKey: '6Ldj1OgUAAAAAO8NKVROz1FrhdRcx4cpP2rbwltr' })*/

// import a plugin
import 'lazysizes/plugins/parent-fit/ls.parent-fit';

// Install BootstrapVue
Vue.use(BootstrapVue)
// Optionally install the BootstrapVue icon components plugin
Vue.use(IconsPlugin)
Vue.use(Notifications)

import Storage from 'vue-ls';

let options = {
    namespace: 'vuejs__', // key prefix
    name: 'ls', // name variable Vue.[ls] or this.[$ls],
    storage: 'local', // storage name session, local, memory
};

Vue.use(Storage, options);

import 'vue-datetime/dist/vue-datetime.css'

import { Datetime } from 'vue-datetime';

Vue.component('datetime', Datetime);

import Tabs from 'vue-tabs-component';

Vue.use(Tabs);

import VTooltip from 'v-tooltip'

Vue.use(VTooltip)

Vue.use(VueCurrencyFilter,
    {
        symbol: '₽',
        thousandsSeparator: '.',
        fractionCount: 2,
        fractionSeparator: ',',
        symbolPosition: 'back',
        symbolSpacing: true
    })
import store from '../js/store'

const app = new Vue({
    store,
    el: '#wrapper',
});
