import './bootstrap';

import { createApp } from 'vue';
import { createPinia } from 'pinia';

import App from '@/App.vue';
import router from '@/router';
import i18n from '@/i18n';

const app = createApp(App).use(createPinia()).use(i18n).use(router);

// Waiting for the first navigation keeps the session lookup in the router guard
// from flashing an empty shell before the real page renders.
router.isReady().then(() => app.mount('#app'));
