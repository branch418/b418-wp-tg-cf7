import { createApp } from 'vue';
import App from './Admin.vue';

document.addEventListener('DOMContentLoaded', () => {
    const element = document.getElementById('b418-boilerplate-container');

    if (element) {
        const app = createApp(App);

        app.provide('wpData', window.b418BoilerplateData);
        app.mount('#b418-boilerplate-container');
    }
})