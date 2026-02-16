import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from 'ziggy-js';
import { createPinia } from 'pinia';
import PrimeVue from 'primevue/config';
import Aura from '@primevue/themes/aura';
import ToastService from 'primevue/toastservice';
import ConfirmationService from 'primevue/confirmationservice';
import Tooltip from 'primevue/tooltip';

// PrimeVue Components
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Dropdown from 'primevue/dropdown';
import Card from 'primevue/card';
import Tag from 'primevue/tag';
import Rating from 'primevue/rating';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';
import Panel from 'primevue/panel';
import Paginator from 'primevue/paginator';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Badge from 'primevue/badge';
import Chip from 'primevue/chip';
import Dialog from 'primevue/dialog';
import Divider from 'primevue/divider';
import Chart from 'primevue/chart';

// PrimeVue CSS
import 'primeicons/primeicons.css';

const appName = import.meta.env.VITE_APP_NAME || 'Easy School ERP';
const pinia = createPinia();

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(pinia)
            .use(PrimeVue, {
                theme: {
                    preset: Aura,
                    options: {
                        prefix: 'p',
                        darkModeSelector: '.dark',
                        cssLayer: false
                    }
                }
            })
            .use(ToastService)
            .use(ConfirmationService)
            .directive('tooltip', Tooltip);

        // Register PrimeVue components globally
        app.component('DataTable', DataTable);
        app.component('Column', Column);
        app.component('Button', Button);
        app.component('InputText', InputText);
        app.component('Password', Password);
        app.component('Dropdown', Dropdown);
        app.component('Card', Card);
        app.component('Tag', Tag);
        app.component('Rating', Rating);
        app.component('Toast', Toast);
        app.component('ConfirmDialog', ConfirmDialog);
        app.component('Panel', Panel);
        app.component('Paginator', Paginator);
        app.component('IconField', IconField);
        app.component('InputIcon', InputIcon);
        app.component('Badge', Badge);
        app.component('Chip', Chip);
        app.component('Dialog', Dialog);
        app.component('Divider', Divider);
        app.component('Chart', Chart);

        return app.mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});