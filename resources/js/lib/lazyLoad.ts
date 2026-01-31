import { defineAsyncComponent, type Component } from 'vue';
import LoadingState from '@/components/ui/loading-state.vue';
import ErrorState from '@/components/ui/error-state.vue';

/**
 * Utility for lazy loading components with loading and error states
 * Improves initial bundle size and performance
 * 
 * Usage:
 * const HeavyComponent = lazyLoad(() => import('./HeavyComponent.vue'));
 */
export function lazyLoad(loader: () => Promise<Component>) {
    return defineAsyncComponent({
        loader,
        loadingComponent: LoadingState,
        errorComponent: ErrorState,
        delay: 200, // Delay before showing loading component (prevents flash)
        timeout: 10000, // 10 seconds timeout
    });
}

/**
 * Lazy load with custom loading/error components
 * 
 * Usage:
 * const Chart = lazyLoadWithCustom(
 *     () => import('./Chart.vue'),
 *     ChartLoading,
 *     ChartError
 * );
 */
export function lazyLoadWithCustom(
    loader: () => Promise<Component>,
    loadingComponent?: Component,
    errorComponent?: Component
) {
    return defineAsyncComponent({
        loader,
        loadingComponent: loadingComponent || LoadingState,
        errorComponent: errorComponent || ErrorState,
        delay: 200,
        timeout: 10000,
    });
}

/**
 * Lazy load with no loading component (instant)
 * Use for components that should load immediately without skeleton
 */
export function lazyLoadInstant(loader: () => Promise<Component>) {
    return defineAsyncComponent({
        loader,
        errorComponent: ErrorState,
        delay: 0,
        timeout: 10000,
    });
}

/**
 * Preload a component for better UX
 * Useful for components that will likely be needed soon
 * 
 * Usage:
 * onMounted(() => {
 *     preloadComponent(() => import('./HeavyModal.vue'));
 * });
 */
export async function preloadComponent(loader: () => Promise<Component>): Promise<void> {
    try {
        await loader();
    } catch (error) {
        console.warn('Failed to preload component:', error);
    }
}
