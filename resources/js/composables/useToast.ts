import { toast } from 'vue-sonner';

export function useToast() {
    return {
        success: (message: string, description?: string) => {
            toast.success(message, {
                description,
                duration: 4000,
            });
        },
        error: (message: string, description?: string) => {
            toast.error(message, {
                description,
                duration: 5000,
            });
        },
        warning: (message: string, description?: string) => {
            toast.warning(message, {
                description,
                duration: 4000,
            });
        },
        info: (message: string, description?: string) => {
            toast.info(message, {
                description,
                duration: 4000,
            });
        },
        loading: (message: string, description?: string) => {
            return toast.loading(message, {
                description,
            });
        },
        promise: <T>(
            promise: Promise<T>,
            messages: {
                loading: string;
                success: string | ((data: T) => string);
                error: string | ((error: any) => string);
            },
        ) => {
            return toast.promise(promise, messages);
        },
        dismiss: (id?: string | number) => {
            toast.dismiss(id);
        },
    };
}
