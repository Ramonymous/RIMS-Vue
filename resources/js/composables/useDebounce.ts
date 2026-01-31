import { ref, watch, onUnmounted, type Ref } from 'vue';

/**
 * Composable for debouncing values
 * Useful for search inputs and real-time filtering
 */
export function useDebounce<T>(value: Ref<T>, delay: number = 300): Ref<T> {
    const debouncedValue = ref(value.value) as Ref<T>;
    let timeoutId: ReturnType<typeof setTimeout> | null = null;

    const stopWatch = watch(value, (newValue) => {
        if (timeoutId) {
            clearTimeout(timeoutId);
        }

        timeoutId = setTimeout(() => {
            debouncedValue.value = newValue;
        }, delay);
    });

    // Cleanup on unmount
    onUnmounted(() => {
        if (timeoutId) {
            clearTimeout(timeoutId);
        }
        stopWatch();
    });

    return debouncedValue;
}

/**
 * Composable for debouncing function calls
 */
export function useDebounceFn<T extends (...args: any[]) => any>(
    fn: T,
    delay: number = 300
): (...args: Parameters<T>) => void {
    let timeoutId: ReturnType<typeof setTimeout> | null = null;

    // Cleanup on unmount
    onUnmounted(() => {
        if (timeoutId) {
            clearTimeout(timeoutId);
        }
    });

    return (...args: Parameters<T>) => {
        if (timeoutId) {
            clearTimeout(timeoutId);
        }

        timeoutId = setTimeout(() => {
            fn(...args);
        }, delay);
    };
}
