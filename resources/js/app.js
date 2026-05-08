import '../css/app.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { createApp, h } from 'vue';

const canonicalLocalPort = '8010';

if (
  ['127.0.0.1', 'localhost'].includes(window.location.hostname)
  && window.location.port === '8000'
) {
  const canonicalUrl = `${window.location.protocol}//${window.location.hostname}:${canonicalLocalPort}${window.location.pathname}${window.location.search}${window.location.hash}`;

  window.location.replace(canonicalUrl);
}

createInertiaApp({
  resolve: (name) => {
    const pages = import.meta.glob('./Pages/**/*.vue', { eager: true });
    return pages[`./Pages/${name}.vue`];
  },
  setup({ el, App, props, plugin }) {
    createApp({ render: () => h(App, props) })
      .use(plugin)
      .mount(el);
  },
});
