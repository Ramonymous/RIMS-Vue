import { ref, type Ref } from 'vue';

export interface DialogState<T = any> {
    isOpen: Ref<boolean>;
    data: Ref<T | null>;
    open: (data?: T) => void;
    close: () => void;
    toggle: () => void;
}

/**
 * Composable for dialog/modal state management
 * Handles open/close state and associated data
 */
export function useDialog<T = any>(
    initialData: T | null = null,
): DialogState<T> {
    const isOpen = ref(false);
    const data = ref<T | null>(initialData) as Ref<T | null>;

    const open = (newData?: T) => {
        if (newData !== undefined) {
            data.value = newData;
        }
        isOpen.value = true;
    };

    const close = () => {
        isOpen.value = false;
        // Don't clear data immediately to allow for exit animations
        setTimeout(() => {
            data.value = initialData;
        }, 300);
    };

    const toggle = () => {
        if (isOpen.value) {
            close();
        } else {
            open();
        }
    };

    return {
        isOpen,
        data,
        open,
        close,
        toggle,
    };
}
