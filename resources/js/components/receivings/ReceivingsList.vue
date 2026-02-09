<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Pencil, XCircle, CheckCircle, Eye } from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { Receiving } from '@/composables/useReceivings';

interface Props {
    receivings: Receiving[];
    formatDate: (date: string) => string;
    getStatusVariant: (
        status: string,
    ) => 'default' | 'destructive' | 'outline' | 'secondary';
    canEdit: (receiving: Receiving) => boolean;
    canConfirmGR: boolean;
    canManageReceivings: boolean;
}

interface Emits {
    (e: 'view', receiving: Receiving): void;
    (e: 'cancel', receiving: Receiving): void;
    (e: 'updateGr', receiving: Receiving): void;
}

defineProps<Props>();
const emit = defineEmits<Emits>();
</script>

<template>
    <div class="space-y-4">
        <div
            v-for="receiving in receivings"
            :key="receiving.id"
            class="flex items-start justify-between rounded-lg border p-4 transition-all hover:shadow-md"
        >
            <div class="flex-1">
                <div class="mb-2 flex items-center gap-2">
                    <h3 class="font-semibold">
                        {{ receiving.doc_number }}
                    </h3>
                    <Badge :variant="getStatusVariant(receiving.status)">
                        {{ receiving.status }}
                    </Badge>
                    <!-- GR Confirmed badge removed -->
                </div>
                <div
                    class="mt-2 grid grid-cols-2 gap-x-4 gap-y-1 text-xs text-muted-foreground md:grid-cols-4"
                >
                    <div>
                        <span class="font-medium">Items:</span>
                        {{ receiving.items.length }}
                    </div>
                    <div>
                        <span class="font-medium">Received By:</span>
                        {{ receiving.received_by.name }}
                    </div>
                    <div>
                        <span class="font-medium">Received At:</span>
                        {{ formatDate(receiving.received_at) }}
                    </div>
                    <div>
                        <span class="font-medium">Created:</span>
                        {{ formatDate(receiving.created_at) }}
                    </div>
                </div>
            </div>

            <div class="flex gap-2">
                <Button
                    variant="outline"
                    size="sm"
                    @click="emit('view', receiving)"
                >
                    <Eye class="h-4 w-4" />
                </Button>
                <Button
                    v-if="canManageReceivings && canEdit(receiving)"
                    variant="outline"
                    size="sm"
                    as-child
                >
                    <Link :href="`/receivings/${receiving.id}/edit`">
                        <Pencil class="h-4 w-4" />
                    </Link>
                </Button>
                <Button
                    v-if="canConfirmGR"
                    variant="outline"
                    size="sm"
                    @click="emit('updateGr', receiving)"
                >
                    <CheckCircle class="h-4 w-4" />
                </Button>
                <Button
                    v-if="
                        canManageReceivings && receiving.status !== 'cancelled'
                    "
                    variant="outline"
                    size="sm"
                    @click="emit('cancel', receiving)"
                    class="text-destructive hover:bg-destructive hover:text-destructive-foreground"
                >
                    <XCircle class="h-4 w-4" />
                </Button>
            </div>
        </div>
    </div>
</template>
