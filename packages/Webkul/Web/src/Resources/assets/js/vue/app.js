import { createApp } from 'vue/dist/vue.esm-bundler.js';
import { registerPublicWebComponents } from './components';

/**
 * Mount one compiler-enabled Vue application over the server-rendered public
 * layout. Blade remains responsible for the page and supplies component slots.
 */
export function mountPublicWebApp() {
    const root = document.getElementById('app');

    if (! root || root.dataset.webVueMounted === 'true') {
        return;
    }

    const app = createApp({ name: 'CampusHubPublicWeb' });

    registerPublicWebComponents(app);
    app.mount(root);
    root.dataset.webVueMounted = 'true';
}
