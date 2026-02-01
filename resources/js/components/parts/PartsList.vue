<script setup lang="ts">
import { Pencil, Trash2 } from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { Part } from '@/composables/useParts';

interface Props {
    parts: Part[];
    formatDate: (date: string) => string;
    animationDelay?: number;
}

interface Emits {
    (e: 'edit', part: Part): void;
    (e: 'delete', part: Part): void;
}

withDefaults(defineProps<Props>(), {
    animationDelay: 50,
});

const emit = defineEmits<Emits>();

const getStockBadgeVariant = (stock: number) => {
    if (stock === 0) return 'destructive';
    if (stock < 10) return 'secondary';
    return 'default';
};
</script>

<template>
    <div class="grid gap-4">
        <div
            v-for="(part, index) in parts"
            :key="part.id"
            class="group rounded-xl border bg-card/80 p-4 transition-all hover:-translate-y-0.5 hover:shadow-lg"
            :style="{ animationDelay: `${index * animationDelay}ms` }"
        >
            <div class="flex flex-col gap-4">
                <!-- Header -->
                <div
                    class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="truncate text-lg font-semibold">
                                {{ part.part_number }}
                            </h3>
                            <Badge
                                :variant="
                                    part.is_active ? 'default' : 'secondary'
                                "
                            >
                                {{ part.is_active ? 'Active' : 'Inactive' }}
                            </Badge>
                            <Badge :variant="getStockBadgeVariant(part.stock)">
                                {{ part.stock.toLocaleString() }} in stock
                            </Badge>
                        </div>
                        <p
                            class="mt-1 line-clamp-1 text-sm text-muted-foreground"
                        >
                            {{ part.part_name }}
                        </p>
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-2 sm:flex-none">
                        <Button
                            variant="outline"
                            size="sm"
                            @click="emit('edit', part)"
                            class="gap-2"
                        >
                            <Pencil class="h-4 w-4" />
                            <span class="hidden sm:inline">Edit</span>
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            @click="emit('delete', part)"
                            class="gap-2 text-destructive hover:bg-destructive hover:text-destructive-foreground"
                        >
                            <Trash2 class="h-4 w-4" />
                            <span class="hidden sm:inline">Delete</span>
                        </Button>
                    </div>
                </div>

                <div class="h-px w-full bg-muted" />

                <!-- Part Details Grid -->
                <div
                    class="grid grid-cols-2 gap-3 text-xs text-muted-foreground sm:grid-cols-3 lg:grid-cols-6"
                >
                    <div v-if="part.customer_code" class="flex flex-col">
                        <span class="font-medium text-foreground"
                            >Customer</span
                        >
                        <span class="truncate">{{ part.customer_code }}</span>
                    </div>
                    <div v-if="part.supplier_code" class="flex flex-col">
                        <span class="font-medium text-foreground"
                            >Supplier</span
                        >
                        <span class="truncate">{{ part.supplier_code }}</span>
                    </div>
                    <div v-if="part.model" class="flex flex-col">
                        <span class="font-medium text-foreground">Model</span>
                        <span class="truncate">{{ part.model }}</span>
                    </div>
                    <div v-if="part.variant" class="flex flex-col">
                        <span class="font-medium text-foreground">Variant</span>
                        <span class="truncate">{{ part.variant }}</span>
                    </div>
                    <div class="flex flex-col">
                        <span class="font-medium text-foreground">Packing</span>
                        <span>{{ part.standard_packing }}</span>
                    </div>
                    <div v-if="part.address" class="flex flex-col">
                        <span class="font-medium text-foreground">Address</span>
                        <span class="truncate">{{ part.address }}</span>
                    </div>
                    <div class="flex flex-col">
                        <span class="font-medium text-foreground">Created</span>
                        <span>{{ formatDate(part.created_at) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
