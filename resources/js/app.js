import '../css/app.css';

import { createInertiaApp, router } from '@inertiajs/vue3';
import { createApp, h } from 'vue';
import { initGoogleAnalytics } from './analytics/googleAnalytics';

createInertiaApp({
  resolve: (name) => {
    const pages = import.meta.glob('./Pages/**/*.vue', { eager: true });
    return pages[`./Pages/${name}.vue`];
  },
  setup({ el, App, props, plugin }) {
    createApp({ render: () => h(App, props) })
      .use(plugin)
      .mount(el);

    initGoogleAnalytics({
      measurementId: props.initialPage.props.analytics?.googleAnalytics?.measurementId,
      router,
    });
  },
});
