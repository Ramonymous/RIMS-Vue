import { router } from '@inertiajs/vue3';
import { computed, type ComputedRef } from 'vue';
import { useDialog, type DialogState } from './useDialog';
import { useFilter, type FilterState } from './useFilter';

export interface Receiving {
    id: string;
    doc_number: string;
    received_by: { id: string; name: string };
    received_at: string;
    status: 'draft' | 'completed' | 'cancelled';
    // is_gr removed
    items: any[];
    created_at: string;
}

export interface ReceivingsState {
    // Dialogs
    cancelDialog: DialogState<Receiving>;
    grDialog: DialogState<Receiving>;
    viewDialog: DialogState<Receiving>;

    // Filter
    filter: FilterState<Receiving>;

    // Actions
    cancel: (receiving: Receiving) => void;
    updateGrStatus: (receiving: Receiving) => void;

    // Utilities
    getStatusVariant: (status: string) => string;
    formatDate: (date: string) => string;
    canEdit: ComputedRef<(receiving: Receiving) => boolean>;
}

/**
 * Composable for receivings business logic
 * Handles all receiving-related operations and state
 */
export function useReceivings(
    receivings: ComputedRef<Receiving[]>,
): ReceivingsState {
    // Dialog states
    const cancelDialog = useDialog<Receiving>();
    const grDialog = useDialog<Receiving>();
    const viewDialog = useDialog<Receiving>();

    // Filter state
    const filter = useFilter(receivings, [
        'doc_number',
        'received_by',
        'status',
    ]);

    // Actions
    const cancel = (receiving: Receiving) => {
        router.post(
            `/receivings/${receiving.id}/cancel`,
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    cancelDialog.close();
                },
            },
        );
    };

    const updateGrStatus = (receiving: Receiving) => {
        router.post(
            `/receivings/${receiving.id}/gr`,
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    grDialog.close();
                },
            },
        );
    };

    // Utilities
    const getStatusVariant = (
        status: string,
    ): 'default' | 'destructive' | 'outline' | 'secondary' => {
        const variants: Record<
            string,
            'default' | 'destructive' | 'outline' | 'secondary'
        > = {
            completed: 'default',
            draft: 'secondary',
            cancelled: 'destructive',
        };
        return variants[status] || 'outline';
    };

    const formatDate = (date: string): string => {
        return new Date(date).toLocaleString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    const canEdit = computed(() => {
        return (receiving: Receiving) => {
            return receiving.status !== 'cancelled';
        };
    });

    return {
        cancelDialog,
        grDialog,
        viewDialog,
        filter,
        cancel,
        updateGrStatus,
        getStatusVariant,
        formatDate,
        canEdit,
    };
}
