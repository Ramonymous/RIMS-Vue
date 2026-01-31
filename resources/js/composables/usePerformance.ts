import { ref, computed, onMounted, onUnmounted, type Ref } from 'vue';

/**
 * Composable for intersection observer (lazy loading visibility)
 * Detects when an element enters the viewport
 */
export function useIntersectionObserver(
    target: Ref<Element | null>,
    callback: (entry: IntersectionObserverEntry) => void,
    options: IntersectionObserverInit = {}
) {
    const isSupported = typeof window !== 'undefined' && 'IntersectionObserver' in window;
    const isIntersecting = ref(false);
    let observer: IntersectionObserver | null = null;

    const cleanup = () => {
        if (observer) {
            observer.disconnect();
            observer = null;
        }
    };

    onMounted(() => {
        if (!isSupported || !target.value) return;

        observer = new IntersectionObserver(
            ([entry]) => {
                isIntersecting.value = entry.isIntersecting;
                callback(entry);
            },
            {
                threshold: 0.1,
                ...options,
            }
        );

        observer.observe(target.value);
    });

    onUnmounted(cleanup);

    return {
        isIntersecting,
        isSupported,
        cleanup,
    };
}

/**
 * Composable for lazy loading images
 * Loads images only when they enter the viewport
 */
export function useLazyImage(src: string) {
    const imageRef = ref<HTMLImageElement | null>(null);
    const isLoaded = ref(false);
    const isError = ref(false);

    const { isIntersecting } = useIntersectionObserver(imageRef, (entry) => {
        if (entry.isIntersecting && imageRef.value && !isLoaded.value) {
            const img = imageRef.value;
            img.src = src;
            img.onload = () => {
                isLoaded.value = true;
            };
            img.onerror = () => {
                isError.value = true;
            };
        }
    });

    return {
        imageRef,
        isLoaded,
        isError,
        isIntersecting,
    };
}

/**
 * Composable for virtual scrolling optimization
 * Only renders visible items in a large list
 */
export function useVirtualScroll<T>(
    items: Ref<T[]>,
    itemHeight: number,
    containerHeight: number,
    buffer = 5
) {
    const scrollTop = ref(0);

    const visibleRange = computed(() => {
        const start = Math.max(0, Math.floor(scrollTop.value / itemHeight) - buffer);
        const end = Math.min(
            items.value.length,
            Math.ceil((scrollTop.value + containerHeight) / itemHeight) + buffer
        );
        return { start, end };
    });

    const visibleItems = computed(() => {
        const { start, end } = visibleRange.value;
        return items.value.slice(start, end).map((item, index) => ({
            item,
            index: start + index,
        }));
    });

    const totalHeight = computed(() => items.value.length * itemHeight);
    const offsetY = computed(() => visibleRange.value.start * itemHeight);

    const onScroll = (event: Event) => {
        const target = event.target as HTMLElement;
        scrollTop.value = target.scrollTop;
    };

    return {
        visibleItems,
        totalHeight,
        offsetY,
        onScroll,
    };
}
