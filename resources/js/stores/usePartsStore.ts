import { ref, computed, shallowRef, readonly, type Ref } from 'vue';
import type { Part, PartFormData } from '@/composables/useParts';
import { useCache } from '@/composables/useCache';

/**
 * Parts Store - Global state and caching for parts data
 * Single source of truth with optimized performance
 */
export function usePartsStore() {
    // Cache for parts data (5 minutes TTL)
    const cache = useCache<Part[]>({ ttl: 5 * 60 * 1000 });
    
    // Local state - use shallowRef for large arrays (better performance)
    const parts = shallowRef<Part[]>([]);
    const isLoading = ref(false);
    const lastFetch = ref<number>(0);
    
    // Index for O(1) lookups
    const partsIndex = ref(new Map<string, Part>());

    // Computed
    const activeParts = computed(() => parts.value.filter((p) => p.is_active));
    const lowStockParts = computed(() => parts.value.filter((p) => p.stock < 10));
    const totalParts = computed(() => parts.value.length);

    // Cache key generator
    const getCacheKey = (filters?: Record<string, any>) => {
        return filters ? `parts-${JSON.stringify(filters)}` : 'parts-all';
    };

    // Get cached parts or current state
    const getParts = (filters?: Record<string, any>) => {
        const cacheKey = getCacheKey(filters);
        const cached = cache.get(cacheKey);
        return cached || parts.value;
    };

    // Set parts with caching and rebuild index
    const setParts = (newParts: Part[], filters?: Record<string, any>) => {
        parts.value = newParts;
        const cacheKey = getCacheKey(filters);
        cache.set(cacheKey, newParts);
        lastFetch.value = Date.now();
        
        // Rebuild index for fast lookups
        partsIndex.value.clear();
        newParts.forEach(part => {
            partsIndex.value.set(part.id, part);
        });
    };

    // Find part by ID (O(1) using Map)
    const findPart = (id: string) => {
        return partsIndex.value.get(id);
    };

    // Find part by part number (still O(n) but rarely used)
    const findByPartNumber = (partNumber: string) => {
        return parts.value.find((p) => p.part_number === partNumber);
    };

    // Optimistic update for part
    const updatePartOptimistic = (id: string, updates: Partial<Part>) => {
        const index = parts.value.findIndex((p) => p.id === id);
        if (index !== -1) {
            const original = { ...parts.value[index] };
            parts.value[index] = { ...parts.value[index], ...updates };
            
            return {
                revert: () => {
                    parts.value[index] = original;
                },
            };
        }
        return { revert: () => {} };
    };

    // Optimistic add for part
    const addPartOptimistic = (part: Part) => {
        parts.value.unshift(part);
        return {
            revert: () => {
                const index = parts.value.findIndex((p) => p.id === part.id);
                if (index !== -1) {
                    parts.value.splice(index, 1);
                }
            },
        };
    };

    // Optimistic delete for part
    const deletePartOptimistic = (id: string) => {
        const index = parts.value.findIndex((p) => p.id === id);
        if (index !== -1) {
            const removed = parts.value.splice(index, 1)[0];
            return {
                revert: () => {
                    parts.value.splice(index, 0, removed);
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
            cache.invalidate(/^parts-/);
        }
    };

    // Clear all data
    const clear = () => {
        parts.value = [];
        cache.clear();
        lastFetch.value = 0;
    };

    return {
        // State
        parts,
        isLoading,
        lastFetch,
        
        // Computed
        activeParts,
        lowStockParts,
        totalParts,
        
        // Methods
        getParts,
        setParts,
        findPart,
        findByPartNumber,
        updatePartOptimistic,
        addPartOptimistic,
        deletePartOptimistic,
        invalidateCache,
        clear,
    };
}
