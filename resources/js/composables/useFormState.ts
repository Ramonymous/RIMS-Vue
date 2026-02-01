import { useForm, type InertiaForm } from '@inertiajs/vue3';
import { ref, watch, type Ref } from 'vue';

export interface FormState<T extends Record<string, any>> {
    form: InertiaForm<T>;
    isDirty: Ref<boolean>;
    reset: () => void;
    submit: (url: string, options?: any) => void;
}

/**
 * Composable for standardized form handling with Inertia
 * Provides form state, dirty tracking, and submit handling
 */
export function useFormState<T extends Record<string, any>>(
    initialData: T,
    config: {
        onSuccess?: () => void;
        onError?: (errors: any) => void;
        preserveScroll?: boolean;
        method?: 'post' | 'put' | 'patch' | 'delete';
    } = {},
): FormState<T> {
    const form = useForm(initialData);
    const isDirty = ref(false);
    const originalData = { ...initialData };

    // Track if form has been modified
    watch(
        () => form.data(),
        (newData) => {
            isDirty.value =
                JSON.stringify(newData) !== JSON.stringify(originalData);
        },
        { deep: true },
    );

    const reset = () => {
        form.reset();
        isDirty.value = false;
    };

    const submit = (url: string, options: any = {}) => {
        const method = config.method || options.method || 'post';
        const submitOptions = {
            preserveScroll:
                config.preserveScroll ?? options.preserveScroll ?? true,
            onSuccess: () => {
                isDirty.value = false;
                config.onSuccess?.();
                options.onSuccess?.();
            },
            onError: (errors: any) => {
                config.onError?.(errors);
                options.onError?.(errors);
            },
            ...options,
        };

        form[method](url, submitOptions);
    };

    return {
        form,
        isDirty,
        reset,
        submit,
    };
}
