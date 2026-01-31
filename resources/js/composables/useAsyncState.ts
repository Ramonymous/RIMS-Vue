import { ref, type Ref } from 'vue';

export interface AsyncState<T> {
    data: Ref<T | null>;
    loading: Ref<boolean>;
    error: Ref<Error | null>;
    execute: (...args: any[]) => Promise<T | null>;
    reset: () => void;
}

/**
 * Composable for standardized async state management
 * Handles loading, error, and data states consistently across components
 */
export function useAsyncState<T>(
    asyncFn: (...args: any[]) => Promise<T>,
    initialData: T | null = null
): AsyncState<T> {
    const data = ref<T | null>(initialData) as Ref<T | null>;
    const loading = ref(false);
    const error = ref<Error | null>(null);

    const execute = async (...args: any[]): Promise<T | null> => {
        loading.value = true;
        error.value = null;

        try {
            const result = await asyncFn(...args);
            data.value = result;
            return result;
        } catch (err) {
            const errorMessage = err instanceof Error ? err : new Error(String(err));
            error.value = errorMessage;
            console.error('AsyncState error:', errorMessage);
            return null;
        } finally {
            loading.value = false;
        }
    };

    const reset = () => {
        data.value = initialData;
        loading.value = false;
        error.value = null;
    };

    return {
        data,
        loading,
        error,
        execute,
        reset,
    };
}
