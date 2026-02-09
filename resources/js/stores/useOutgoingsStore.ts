import { ref, computed, shallowRef } from 'vue';
import { useCache } from '@/composables/useCache';
import type { Outgoing } from '@/composables/useOutgoings';

/**
 * Outgoings Store - Global state and caching for outgoings data
 * Single source of truth with optimized performance
 */
export function useOutgoingsStore() {
    // Cache for outgoings data (3 minutes TTL - more dynamic data)
    const cache = useCache<Outgoing[]>({ ttl: 3 * 60 * 1000 });

    // Local state - use shallowRef for better performance
    const outgoings = shallowRef<Outgoing[]>([]);
    const isLoading = ref(false);
    const lastFetch = ref<number>(0);

    // Computed
    const activeOutgoings = computed(() =>
        outgoings.value.filter((o) => o.status !== 'cancelled'),
    );
    const pendingOutgoings = computed(() =>
        outgoings.value.filter((o) => o.status === 'draft'),
    );
    const completedOutgoings = computed(() =>
        outgoings.value.filter((o) => o.status === 'completed'),
    );
    // GI confirmed outgoings removed

    // Cache key generator
    const getCacheKey = (filters?: Record<string, any>) => {
        return filters
            ? `outgoings-${JSON.stringify(filters)}`
            : 'outgoings-all';
    };

    // Get cached outgoings or current state
    const getOutgoings = (filters?: Record<string, any>) => {
        const cacheKey = getCacheKey(filters);
        const cached = cache.get(cacheKey);
        return cached || outgoings.value;
    };

    // Set outgoings with caching
    const setOutgoings = (
        newOutgoings: Outgoing[],
        filters?: Record<string, any>,
    ) => {
        outgoings.value = newOutgoings;
        const cacheKey = getCacheKey(filters);
        cache.set(cacheKey, newOutgoings);
        lastFetch.value = Date.now();
    };

    // Find outgoing by ID
    const findOutgoing = (id: string) => {
        return outgoings.value.find((o) => o.id === id);
    };

    // Find outgoing by document number
    const findByDocNumber = (docNumber: string) => {
        return outgoings.value.find((o) => o.doc_number === docNumber);
    };

    // Optimistic update
    const updateOutgoingOptimistic = (
        id: string,
        updates: Partial<Outgoing>,
    ) => {
        const index = outgoings.value.findIndex((o) => o.id === id);
        if (index !== -1) {
            const original = { ...outgoings.value[index] };
            outgoings.value[index] = { ...outgoings.value[index], ...updates };

            return {
                revert: () => {
                    outgoings.value[index] = original;
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
            cache.invalidate(/^outgoings-/);
        }
    };

    // Clear all data
    const clear = () => {
        outgoings.value = [];
        cache.clear();
        lastFetch.value = 0;
    };

    return {
        // State
        outgoings,
        isLoading,
        lastFetch,

        // Computed
        activeOutgoings,
        pendingOutgoings,
        completedOutgoings,

        // Methods
        getOutgoings,
        setOutgoings,
        findOutgoing,
        findByDocNumber,
        updateOutgoingOptimistic,
        invalidateCache,
        clear,
    };
}
