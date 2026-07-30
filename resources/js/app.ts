import '@css/app.css';
import 'primeicons/primeicons.css';

import '@/lib/echo';

import { createApp, h, type DefineComponent } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

import PrimeVue from 'primevue/config';
import Aura from '@primevue/themes/aura';
import ToastService from 'primevue/toastservice';
import Toast from 'primevue/toast';
import ConfirmationService from 'primevue/confirmationservice';
import ConfirmDialog from 'primevue/confirmdialog';

createInertiaApp({
    title: (title) => (title ? `${title} — Codebase LLM Assistant` : 'Codebase LLM Assistant'),
    resolve: async (name): Promise<DefineComponent> => {
        const page = await resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        );

        return page as DefineComponent;
    },
    setup({ el, App, props, plugin }) {
        createApp({
            render: () => h('div', [h(App, props), h(Toast), h(ConfirmDialog)]),
        })
            .use(plugin)
            .use(PrimeVue, {
                theme: {
                    preset: Aura,
                    options: {
                        darkModeSelector: false,
                    },
                },
            })
            .use(ToastService)
            .use(ConfirmationService)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
