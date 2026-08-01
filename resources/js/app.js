import './bootstrap';

import { createApp } from 'vue';
import { createPinia } from 'pinia';

import App from '@/App.vue';
import router from '@/router';

const app = createApp(App).use(createPinia()).use(router);

// Waiting for the first navigation keeps the session lookup in the router guard
// from flashing an empty shell before the real page renders.
router.isReady().then(() => app.mount('#app'));
