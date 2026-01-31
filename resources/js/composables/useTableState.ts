import { ref, computed, type Ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { useDebounce } from './useDebounce';

export interface TableColumn<T = any> {
    key: keyof T;
    label: string;
    sortable?: boolean;
    searchable?: boolean;
}

export interface TableState<T> {
    // Search
    searchQuery: Ref<string>;
    debouncedSearch: Ref<string>;
    
    // Sorting
    sortKey: Ref<keyof T | ''>;
    sortOrder: Ref<'asc' | 'desc'>;
    
    // Selection
    selectedItems: Ref<Set<string>>;
    
    // Methods
    setSearch: (query: string) => void;
    clearSearch: () => void;
    sort: (key: keyof T) => void;
    toggleSelection: (id: string) => void;
    toggleAll: (items: T[]) => void;
    clearSelection: () => void;
    isSelected: (id: string) => boolean;
    allSelected: (items: T[]) => boolean;
}

/**
 * Composable for table state management
 * Handles search, sorting, and selection with optimized performance
 */
export function useTableState<T extends { id: string }>(
    config: {
        defaultSort?: keyof T;
        defaultOrder?: 'asc' | 'desc';
        debounceMs?: number;
    } = {}
): TableState<T> {
    const searchQuery = ref('');
    const sortKey = ref<keyof T | ''>(config.defaultSort || '');
    const sortOrder = ref<'asc' | 'desc'>(config.defaultOrder || 'asc');
    const selectedItems = ref<Set<string>>(new Set());

    // Debounce search for performance
    const debouncedSearch = useDebounce(searchQuery, config.debounceMs || 300);

    const setSearch = (query: string) => {
        searchQuery.value = query;
    };

    const clearSearch = () => {
        searchQuery.value = '';
    };

    const sort = (key: keyof T) => {
        if (sortKey.value === key) {
            // Toggle sort order
            sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
        } else {
            sortKey.value = key;
            sortOrder.value = 'asc';
        }
    };

    const toggleSelection = (id: string) => {
        if (selectedItems.value.has(id)) {
            selectedItems.value.delete(id);
        } else {
            selectedItems.value.add(id);
        }
    };

    const toggleAll = (items: T[]) => {
        if (allSelected(items)) {
            clearSelection();
        } else {
            items.forEach((item) => selectedItems.value.add(item.id));
        }
    };

    const clearSelection = () => {
        selectedItems.value.clear();
    };

    const isSelected = (id: string) => {
        return selectedItems.value.has(id);
    };

    const allSelected = (items: T[]) => {
        return items.length > 0 && items.every((item) => selectedItems.value.has(item.id));
    };

    return {
        searchQuery,
        debouncedSearch,
        sortKey,
        sortOrder,
        selectedItems,
        setSearch,
        clearSearch,
        sort,
        toggleSelection,
        toggleAll,
        clearSelection,
        isSelected,
        allSelected,
    };
}
