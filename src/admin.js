import { createApp } from 'vue';
import App from './Admin.vue';

document.addEventListener('DOMContentLoaded', () => {
    const element = document.getElementById('b418-wp-tg-cf7-container');

    if (element) {
        const app = createApp(App);

        app.provide('wpData', window.b418WpTgCf7Data);
        app.mount('#b418-wp-tg-cf7-container');
    }
})