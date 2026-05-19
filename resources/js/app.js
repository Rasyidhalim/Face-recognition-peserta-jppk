import './bootstrap';
import { createApp } from 'vue';
import App from './App.vue'; // Ubah ini, import App.vue

const app = createApp(App); // Mount App.vue
app.mount('#app');