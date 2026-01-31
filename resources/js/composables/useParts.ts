import { router } from '@inertiajs/vue3';
import { computed, ref, type ComputedRef, type Ref } from 'vue';
import { useDialog, type DialogState } from './useDialog';
import { useFilter, type FilterState } from './useFilter';

export interface Part {
    id: string;
    part_number: string;
    part_name: string;
    customer_code: string | null;
    supplier_code: string | null;
    model: string | null;
    variant: string | null;
    standard_packing: number;
    stock: number;
    address: string | null;
    is_active: boolean;
    created_at: string;
}

export interface PartFormData {
    part_number: string;
    part_name: string;
    customer_code: string;
    supplier_code: string;
    model: string;
    variant: string;
    standard_packing: number;
    stock: number;
    address: string;
    is_active: boolean;
}

export interface PartsState {
    // Dialogs
    createDialog: DialogState;
    editDialog: DialogState<Part>;
    deleteDialog: DialogState<Part>;
    importDialog: DialogState;
    
    // Filter
    filter: FilterState<Part>;
    
    // File upload
    importFile: Ref<File | null>;
    fileInputRef: Ref<HTMLInputElement | null>;
    
    // Actions
    deletePart: (part: Part) => void;
    exportTemplate: () => void;
    handleImport: () => void;
    
    // Utilities
    getStockBadgeVariant: (stock: number) => string;
    formatDate: (date: string) => string;
}

/**
 * Composable for parts business logic
 * Handles all part-related operations and state
 */
export function useParts(parts: ComputedRef<Part[]>): PartsState {
    // Dialog states
    const createDialog = useDialog();
    const editDialog = useDialog<Part>();
    const deleteDialog = useDialog<Part>();
    const importDialog = useDialog();

    // Filter state
    const filter = useFilter(parts, ['part_number', 'part_name', 'customer_code', 'supplier_code', 'model']);

    // File upload state
    const importFile = ref<File | null>(null);
    const fileInputRef = ref<HTMLInputElement | null>(null);

    // Actions
    const deletePart = (part: Part) => {
        router.delete(`/parts/${part.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                deleteDialog.close();
            },
        });
    };

    const exportTemplate = () => {
        window.location.href = '/parts/export-template';
    };

    const handleImport = () => {
        if (!importFile.value) return;

        const formData = new FormData();
        formData.append('file', importFile.value);

        router.post('/parts/import', formData, {
            preserveScroll: true,
            onSuccess: () => {
                importDialog.close();
                importFile.value = null;
                if (fileInputRef.value) {
                    fileInputRef.value.value = '';
                }
            },
        });
    };

    // Utilities
    const getStockBadgeVariant = (stock: number): string => {
        if (stock === 0) return 'destructive';
        if (stock < 10) return 'warning';
        return 'default';
    };

    const formatDate = (date: string): string => {
        return new Date(date).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        });
    };

    return {
        createDialog,
        editDialog,
        deleteDialog,
        importDialog,
        filter,
        importFile,
        fileInputRef,
        deletePart,
        exportTemplate,
        handleImport,
        getStockBadgeVariant,
        formatDate,
    };
}
