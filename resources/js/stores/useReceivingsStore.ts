import { ref, computed, shallowRef } from 'vue';
import { useCache } from '@/composables/useCache';
import type { Receiving } from '@/composables/useReceivings';

/**
 * Receivings Store - Global state and caching for receivings data
 * Single source of truth with optimized performance
 */
export function useReceivingsStore() {
    // Cache for receivings data (3 minutes TTL - more dynamic data)
    const cache = useCache<Receiving[]>({ ttl: 3 * 60 * 1000 });

    // Local state - use shallowRef for better performance
    const receivings = shallowRef<Receiving[]>([]);
    const isLoading = ref(false);
    const lastFetch = ref<number>(0);

    // Computed
    const activeReceivings = computed(() =>
        receivings.value.filter((r) => r.status !== 'cancelled'),
    );
    const pendingReceivings = computed(() =>
        receivings.value.filter((r) => r.status === 'draft'),
    );
    const completedReceivings = computed(() =>
        receivings.value.filter((r) => r.status === 'completed'),
    );
    const grConfirmedReceivings = computed(() =>
        receivings.value.filter((r) => r.is_gr),
    );

    // Cache key generator
    const getCacheKey = (filters?: Record<string, any>) => {
        return filters
            ? `receivings-${JSON.stringify(filters)}`
            : 'receivings-all';
    };

    // Get cached receivings or current state
    const getReceivings = (filters?: Record<string, any>) => {
        const cacheKey = getCacheKey(filters);
        const cached = cache.get(cacheKey);
        return cached || receivings.value;
    };

    // Set receivings with caching
    const setReceivings = (
        newReceivings: Receiving[],
        filters?: Record<string, any>,
    ) => {
        receivings.value = newReceivings;
        const cacheKey = getCacheKey(filters);
        cache.set(cacheKey, newReceivings);
        lastFetch.value = Date.now();
    };

    // Find receiving by ID
    const findReceiving = (id: string) => {
        return receivings.value.find((r) => r.id === id);
    };

    // Find receiving by document number
    const findByDocNumber = (docNumber: string) => {
        return receivings.value.find((r) => r.doc_number === docNumber);
    };

    // Optimistic update
    const updateReceivingOptimistic = (
        id: string,
        updates: Partial<Receiving>,
    ) => {
        const index = receivings.value.findIndex((r) => r.id === id);
        if (index !== -1) {
            const original = { ...receivings.value[index] };
            receivings.value[index] = {
                ...receivings.value[index],
                ...updates,
            };

            return {
                revert: () => {
                    receivings.value[index] = original;
                },
            };
        }
        return { revert: () => {} };
    };

    // Invalidate cache
    const invalidateCache = (filters?: Record<string, any>) => {
        if (filters) {
            const cacheKey = getCacheKey(filters);
            cache.remove(cacheKey);
        } else {
            cache.invalidate(/^receivings-/);
        }
    };

    // Clear all data
    const clear = () => {
        receivings.value = [];
        cache.clear();
        lastFetch.value = 0;
    };

    return {
        // State
        receivings,
        isLoading,
        lastFetch,

        // Computed
        activeReceivings,
        pendingReceivings,
        completedReceivings,
        grConfirmedReceivings,

        // Methods
        getReceivings,
        setReceivings,
        findReceiving,
        findByDocNumber,
        updateReceivingOptimistic,
        invalidateCache,
        clear,
    };
}
