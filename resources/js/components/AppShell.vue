<script setup lang="ts">
import type { PageProps as InertiaPageProps } from '@inertiajs/core';
import { usePage } from '@inertiajs/vue3';
import { Loader2 } from 'lucide-vue-next';
import { onMounted, onUnmounted, ref, watch } from 'vue';
import { SidebarProvider } from '@/components/ui/sidebar';
import { Toaster } from '@/components/ui/sonner';
import { useToast } from '@/composables/useToast';
import type { AppShellVariant } from '@/types';

type Props = {
    variant?: AppShellVariant;
};

defineProps<Props>();

type FlashMessages = {
    success?: string | null;
    error?: string | null;
    warning?: string | null;
    info?: string | null;
};

type PageProps = InertiaPageProps & {
    sidebarOpen: boolean;
    flash?: FlashMessages;
};

const page = usePage<PageProps>();
const isOpen = page.props.sidebarOpen;
const toast = useToast();
const isLoading = ref(false);

const loadingEvent = 'inertia:loading';
const handleLoadingEvent = (event: Event) => {
    const customEvent = event as CustomEvent<{ loading: boolean }>;
    isLoading.value = customEvent.detail?.loading ?? false;
};

onMounted(() => {
    window.addEventListener(loadingEvent, handleLoadingEvent);
});

onUnmounted(() => {
    window.removeEventListener(loadingEvent, handleLoadingEvent);
});

watch(
    () => page.props.flash,
    (flash) => {
        if (!flash) return;

        if (flash.success) {
            toast.success(flash.success);
        }
        if (flash.error) {
            toast.error(flash.error);
        }
        if (flash.warning) {
            toast.warning(flash.warning);
        }
        if (flash.info) {
            toast.info(flash.info);
        }
    },
    { deep: true },
);
</script>

<template>
    <div v-if="variant === 'header'" class="flex min-h-screen w-full flex-col">
        <slot />
        <Toaster />
    </div>
    <SidebarProvider v-else :default-open="isOpen">
        <slot />
        <Toaster />
    </SidebarProvider>
    <div
        v-if="isLoading"
        class="fixed inset-0 z-[9999] flex items-center justify-center bg-background/60 backdrop-blur-sm"
        aria-live="polite"
        aria-busy="true"
    >
        <div
            class="flex items-center gap-3 rounded-lg border bg-card px-4 py-3 shadow-lg"
        >
            <Loader2 class="h-5 w-5 animate-spin text-primary" />
            <span class="text-sm font-medium text-foreground">Loading...</span>
        </div>
    </div>
</template>
