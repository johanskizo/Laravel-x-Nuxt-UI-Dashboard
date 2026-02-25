// 1. Styles
import '../../css/app.css';

// 2. Vue Core & Store
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import piniaPluginPersistedstate from 'pinia-plugin-persistedstate';

// 3. Plugins & Configs
import i18n from './utils/i18n';
import { router } from './router';
import ui from '@nuxt/ui/vue-plugin';

// 4. Root Component
import App from './App.vue';

// --- Initialization ---

const app = createApp(App);
const pinia = createPinia();

// Store Configuration
pinia.use(piniaPluginPersistedstate);

// Register Plugins
app.use(pinia);
app.use(i18n);
app.use(router);
app.use(ui);

// Mount
app.mount('#app');
