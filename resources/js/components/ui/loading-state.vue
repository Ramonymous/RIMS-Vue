<script setup lang="ts">
import { Loader2 } from 'lucide-vue-next';

type Props = {
    message?: string;
    fullHeight?: boolean;
};

withDefaults(defineProps<Props>(), {
    message: 'Loading...',
    fullHeight: true,
});
</script>

<template>
    <div 
        class="flex flex-col items-center justify-center p-8 animate-in fade-in"
        :class="fullHeight ? 'min-h-[400px]' : ''"
    >
        <Loader2 class="h-8 w-8 animate-spin text-primary mb-4" />
        <p class="text-sm text-muted-foreground">{{ message }}</p>
    </div>
</template>
