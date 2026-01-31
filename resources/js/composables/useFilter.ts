import { computed, ref, type Ref, type ComputedRef } from 'vue';

export interface FilterState<T> {
    searchQuery: Ref<string>;
    filteredData: ComputedRef<T[]>;
    setSearch: (query: string) => void;
    clearSearch: () => void;
}

/**
 * Composable for client-side filtering
 * Generic filter function for arrays of objects
 */
export function useFilter<T extends Record<string, any>>(
    data: Ref<T[]> | ComputedRef<T[]>,
    searchableFields: (keyof T)[]
): FilterState<T> {
    const searchQuery = ref('');

    const filteredData = computed(() => {
        const query = searchQuery.value.trim();
        
        if (!query) {
            return data.value;
        }

        const lowerQuery = query.toLowerCase();
        
        return data.value.filter((item) => {
            return searchableFields.some((field) => {
                const value = item[field];
                
                if (value === null || value === undefined) {
                    return false;
                }

                // Handle nested objects (e.g., user.name)
                if (typeof value === 'object' && value !== null) {
                    return Object.values(value).some((nestedValue) =>
                        String(nestedValue).toLowerCase().includes(lowerQuery)
                    );
                }

                return String(value).toLowerCase().includes(lowerQuery);
            });
        });
    });

    const setSearch = (query: string) => {
        searchQuery.value = query;
    };

    const clearSearch = () => {
        searchQuery.value = '';
    };

    return {
        searchQuery,
        filteredData,
        setSearch,
        clearSearch,
    };
}
