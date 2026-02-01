import { ref, type Ref } from 'vue';

/**
 * Composable for optimistic updates
 * Provides immediate UI feedback while API call is in progress
 */
export function useOptimistic<T>(initialData: T) {
    const data = ref<T>(initialData) as Ref<T>;
    const isReverting = ref(false);

    const update = (newData: T) => {
        const previousData = data.value;

        // Optimistically update
        data.value = newData;

        return {
            revert: () => {
                isReverting.value = true;
                data.value = previousData;
                setTimeout(() => {
                    isReverting.value = false;
                }, 300);
            },
        };
    };

    const set = (newData: T) => {
        data.value = newData;
    };

    return {
        data,
        isReverting,
        update,
        set,
    };
}
