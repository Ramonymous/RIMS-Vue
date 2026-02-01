<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginationData {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: PaginationLink[];
}

interface DataTablePaginationProps {
    data: PaginationData;
    showing?: number;
}

const props = defineProps<DataTablePaginationProps>();
</script>

<template>
    <div class="flex items-center justify-between px-2">
        <div class="text-sm text-muted-foreground">
            <slot name="info">
                Showing {{ showing ?? data.per_page ?? 0 }} of {{ data.total }} results
            </slot>
        </div>

        <div
            v-if="data.last_page > 1"
            class="flex flex-wrap justify-center gap-2"
        >
            <Link
                v-for="link in data.links"
                :key="link.label"
                :href="link.url || '#'"
                :class="[
                    'rounded-md px-4 py-2 text-sm font-medium transition-all',
                    link.active
                        ? 'bg-primary text-primary-foreground shadow-sm'
                        : 'bg-muted hover:bg-muted/80 text-muted-foreground hover:text-foreground',
                    !link.url && 'cursor-not-allowed opacity-50',
                ]"
                :disabled="!link.url"
                v-html="link.label"
            />
        </div>
    </div>
</template>
