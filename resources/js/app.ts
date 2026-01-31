import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import '../css/app.css';
import 'vue-sonner/style.css';
import { initializeTheme } from './composables/useAppearance';
import { configureEcho } from '@laravel/echo-vue';
import { toast } from 'vue-sonner';

configureEcho({
    broadcaster: 'reverb',
});

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

const loadingEvent = 'inertia:loading';

router.on('start', () => {
    window.dispatchEvent(new CustomEvent(loadingEvent, { detail: { loading: true } }));
});

router.on('finish', () => {
    window.dispatchEvent(new CustomEvent(loadingEvent, { detail: { loading: false } }));
});

router.on('invalid', () => {
    window.dispatchEvent(new CustomEvent(loadingEvent, { detail: { loading: false } }));
});

router.on('error', () => {
    window.dispatchEvent(new CustomEvent(loadingEvent, { detail: { loading: false } }));
});

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
    progress: false,
});

// Handle Inertia errors globally
router.on('error', (event) => {
    console.error('[Inertia Error]', event);
    
    // Access the response from the axios error
    const error = (event as any).detail;
    const response = error?.response;
    const status = response?.status;
    const message = response?.data?.message || error?.message || 'An error occurred';

    if (status === 403) {
        console.warn('Access Denied:', message);
        
        // Show toast notification for 403 errors
        toast.error('Access Denied', {
            description: message || 'You do not have permission to access this resource.',
            duration: 5000,
        });
        
        // Redirect to dashboard on unauthorized access
        router.visit('/');
    } else if (status === 419) {
        console.warn('Session Expired');
        // Reload the page on session expiration
        window.location.reload();
    } else if (status >= 500) {
        console.error('Server Error:', message);
    }
});

// This will set light / dark mode on page load...
initializeTheme();
