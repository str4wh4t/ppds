import "./bootstrap";
import "../css/app.css";

import { createApp, h } from "vue";
import { createInertiaApp } from "@inertiajs/vue3";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import { ZiggyVue } from "../../vendor/tightenco/ziggy";
import { createPinia } from "pinia";
import UtilsPlugin from "@/plugins/utils";

import axios from "axios";

axios.defaults.withCredentials = true; // Mengaktifkan pengiriman cookie
axios.defaults.baseURL = "/api"; // Base URL untuk API

const appName = import.meta.env.VITE_APP_NAME || "Laravel";

createInertiaApp({
  title: (title) => `${title} - ${appName}`,
  resolve: (name) =>
    resolvePageComponent(
      `./Pages/${name}.vue`,
      import.meta.glob("./Pages/**/*.vue")
    ),
  setup({ el, App, props, plugin }) {
    const app = createApp({ render: () => h(App, props) });

    return app
      .use(createPinia())
      .use(UtilsPlugin)
      .use(plugin)
      .use(ZiggyVue)
      .mount(el);
  },
  progress: {
    color: "#4B5563",
  },
});
