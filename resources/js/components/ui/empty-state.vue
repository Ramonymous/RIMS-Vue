<script setup lang="ts">
import { Package } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';

type Props = {
    icon?: any;
    title: string;
    description?: string;
    actionLabel?: string;
    actionHref?: string;
};

const props = withDefaults(defineProps<Props>(), {
    icon: Package,
});

const emit = defineEmits<{
    action: [];
}>();
</script>

<template>
    <div class="flex min-h-[400px] flex-col items-center justify-center rounded-lg border-2 border-dashed border-muted-foreground/25 p-8 text-center animate-in fade-in">
        <div class="flex h-20 w-20 items-center justify-center rounded-full bg-muted/50 mb-4">
            <component :is="icon" class="h-10 w-10 text-muted-foreground" />
        </div>
        <h3 class="text-lg font-semibold mb-2">{{ title }}</h3>
        <p v-if="description" class="text-sm text-muted-foreground max-w-sm mb-6">
            {{ description }}
        </p>
        <Button
            v-if="actionLabel"
            @click="emit('action')"
            size="sm"
            class="gap-2"
        >
            <component :is="icon" class="h-4 w-4" />
            {{ actionLabel }}
        </Button>
    </div>
</template>
