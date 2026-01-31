import { router } from '@inertiajs/vue3';
import { computed, type ComputedRef } from 'vue';
import { useDialog, type DialogState } from './useDialog';
import { useFilter, type FilterState } from './useFilter';

export interface Outgoing {
    id: string;
    doc_number: string;
    issued_by: { id: string; name: string };
    issued_at: string;
    status: 'draft' | 'completed' | 'cancelled';
    is_gi: boolean;
    items: any[];
    created_at: string;
}

export interface OutgoingsState {
    // Dialogs
    cancelDialog: DialogState<Outgoing>;
    giDialog: DialogState<Outgoing>;
    viewDialog: DialogState<Outgoing>;
    
    // Filter
    filter: FilterState<Outgoing>;
    
    // Actions
    cancel: (outgoing: Outgoing) => void;
    updateGiStatus: (outgoing: Outgoing) => void;
    
    // Utilities
    getStatusVariant: (status: string) => string;
    formatDate: (date: string) => string;
    canEdit: ComputedRef<(outgoing: Outgoing) => boolean>;
}

/**
 * Composable for outgoings business logic
 * Handles all outgoing-related operations and state
 */
export function useOutgoings(outgoings: ComputedRef<Outgoing[]>): OutgoingsState {
    // Dialog states
    const cancelDialog = useDialog<Outgoing>();
    const giDialog = useDialog<Outgoing>();
    const viewDialog = useDialog<Outgoing>();

    // Filter state
    const filter = useFilter(outgoings, ['doc_number', 'issued_by', 'status']);

    // Actions
    const cancel = (outgoing: Outgoing) => {
        router.post(`/outgoings/${outgoing.id}/cancel`, {}, {
            preserveScroll: true,
            onSuccess: () => {
                cancelDialog.close();
            },
        });
    };

    const updateGiStatus = (outgoing: Outgoing) => {
        router.post(`/outgoings/${outgoing.id}/gi`, {}, {
            preserveScroll: true,
            onSuccess: () => {
                giDialog.close();
            },
        });
    };

    // Utilities
    const getStatusVariant = (status: string): 'default' | 'destructive' | 'outline' | 'secondary' => {
        const variants: Record<string, 'default' | 'destructive' | 'outline' | 'secondary'> = {
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
        return (outgoing: Outgoing) => {
            return !outgoing.is_gi && outgoing.status !== 'cancelled';
        };
    });

    return {
        cancelDialog,
        giDialog,
        viewDialog,
        filter,
        cancel,
        updateGiStatus,
        getStatusVariant,
        formatDate,
        canEdit,
    };
}
