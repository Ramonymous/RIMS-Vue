<script setup lang="ts">
type Props = {
    title: string;
    description?: string;
};

defineProps<Props>();
</script>

<template>
    <div class="flex flex-col gap-2 mb-6 animate-in slide-in-from-top">
        <h1 class="text-3xl font-bold tracking-tight">{{ title }}</h1>
        <p v-if="description" class="text-muted-foreground">
            {{ description }}
        </p>
    </div>
</template>
