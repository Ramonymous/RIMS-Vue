<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { Receiving } from '@/composables/useReceivings';

interface Props {
    open: boolean;
    receiving: Receiving | null;
    formatDate: (date: string) => string;
    getStatusVariant: (status: string) => string;
}

interface Emits {
    (e: 'update:open', value: boolean): void;
}

defineProps<Props>();
const emit = defineEmits<Emits>();
</script>

<template>
    <Dialog :open="open" @update:open="(val) => emit('update:open', val)">
        <DialogContent class="max-w-2xl">
            <DialogHeader>
                <DialogTitle>Receiving Details</DialogTitle>
                <DialogDescription>
                    {{ receiving?.doc_number }}
                </DialogDescription>
            </DialogHeader>
            <div v-if="receiving" class="space-y-4">
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="font-medium">Status:</span>
                        <Badge
                            :variant="getStatusVariant(receiving.status)"
                            class="ml-2"
                        >
                            {{ receiving.status }}
                        </Badge>
                        <Badge
                            v-if="receiving.is_gr"
                            variant="default"
                            class="ml-2"
                        >
                            GR Confirmed
                        </Badge>
                    </div>
                    <div>
                        <span class="font-medium">Received By:</span>
                        {{ receiving.received_by.name }}
                    </div>
                    <div>
                        <span class="font-medium">Received At:</span>
                        {{ formatDate(receiving.received_at) }}
                    </div>
                </div>

                <div class="border-t pt-4">
                    <h4 class="mb-3 font-medium">
                        Items ({{ receiving.items.length }})
                    </h4>
                    <div class="max-h-96 space-y-2 overflow-y-auto">
                        <div
                            v-for="(item, index) in receiving.items"
                            :key="index"
                            class="rounded-lg border p-3"
                        >
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <p class="font-medium">
                                        {{ item.part.part_number }}
                                    </p>
                                    <p class="text-sm text-muted-foreground">
                                        {{ item.part.part_name }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-medium">
                                        Qty: {{ item.qty }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        Stock: {{ item.part.stock }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <DialogFooter>
                <Button variant="outline" @click="emit('update:open', false)">
                    Close
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
