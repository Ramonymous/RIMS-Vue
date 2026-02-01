import { ref } from 'vue';

export interface CacheEntry<T> {
    data: T;
    timestamp: number;
}

export interface CacheOptions {
    ttl?: number; // Time to live in milliseconds
    maxSize?: number; // Maximum cache size
}

/**
 * Composable for client-side data caching
 * Reduces unnecessary API calls and improves performance
 */
export function useCache<T>(options: CacheOptions = {}) {
    const { ttl = 5 * 60 * 1000, maxSize = 100 } = options; // Default 5 minutes TTL

    const cache = ref<Map<string, CacheEntry<T>>>(new Map());

    const get = (key: string): T | null => {
        const entry = cache.value.get(key);

        if (!entry) return null;

        // Check if entry has expired
        const age = Date.now() - entry.timestamp;
        if (age > ttl) {
            cache.value.delete(key);
            return null;
        }

        return entry.data;
    };

    const set = (key: string, data: T) => {
        // Enforce max cache size (LRU-like behavior)
        if (cache.value.size >= maxSize) {
            const firstKey = cache.value.keys().next().value;
            cache.value.delete(firstKey);
        }

        cache.value.set(key, {
            data,
            timestamp: Date.now(),
        });
    };

    const has = (key: string): boolean => {
        return get(key) !== null;
    };

    const remove = (key: string) => {
        cache.value.delete(key);
    };

    const clear = () => {
        cache.value.clear();
    };

    const invalidate = (pattern?: string | RegExp) => {
        if (!pattern) {
            clear();
            return;
        }

        const keys = Array.from(cache.value.keys());
        const regex =
            typeof pattern === 'string' ? new RegExp(pattern) : pattern;

        keys.forEach((key) => {
            if (regex.test(key)) {
                cache.value.delete(key);
            }
        });
    };

    return {
        get,
        set,
        has,
        remove,
        clear,
        invalidate,
    };
}
