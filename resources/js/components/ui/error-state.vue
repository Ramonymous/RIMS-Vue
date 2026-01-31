<script setup lang="ts">
import { AlertCircle } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';

type Props = {
    title?: string;
    message?: string;
    actionLabel?: string;
};

withDefaults(defineProps<Props>(), {
    title: 'Something went wrong',
    message: 'An error occurred while loading the data.',
    actionLabel: 'Try Again',
});

const emit = defineEmits<{
    retry: [];
}>();
</script>

<template>
    <div class="flex min-h-[400px] flex-col items-center justify-center p-8 text-center animate-in fade-in">
        <div class="flex h-20 w-20 items-center justify-center rounded-full bg-destructive/10 mb-4">
            <AlertCircle class="h-10 w-10 text-destructive" />
        </div>
        <h3 class="text-lg font-semibold mb-2">{{ title }}</h3>
        <p class="text-sm text-muted-foreground max-w-sm mb-6">
            {{ message }}
        </p>
        <Button
            @click="emit('retry')"
            variant="outline"
            size="sm"
            class="gap-2"
        >
            <AlertCircle class="h-4 w-4" />
            {{ actionLabel }}
        </Button>
    </div>
</template>
