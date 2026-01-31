<script setup lang="ts">
import { computed, ref, watch, onMounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { PackagePlus, PackageSearch, Search } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import ReceivingsList from '@/components/receivings/ReceivingsList.vue';
import ReceivingDetailsDialog from '@/components/receivings/ReceivingDetailsDialog.vue';
import type { Receiving } from '@/composables/useReceivings';
import { useReceivingsStore } from '@/stores/useReceivingsStore';
import { useUserStore } from '@/stores/useUserStore';
import { useTableState } from '@/composables/useTableState';

type Props = {
    receivings: {
        data: Receiving[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        links: Array<{
            url: string | null;
            label: string;
            active: boolean;
        }>;
    };
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Receivings', href: '/receivings' },
];

// Stores
const receivingsStore = useReceivingsStore();
const userStore = useUserStore();

// Initialize store data
onMounted(() => {
    receivingsStore.setReceivings(props.receivings.data);
});

// Watch for data changes
watch(
    () => props.receivings.data,
    (newReceivings) => {
        receivingsStore.setReceivings(newReceivings);
    }
);

// Table state
const tableState = useTableState<Receiving>({
    debounceMs: 300,
});

// Dialogs
const cancelDialogOpen = ref(false);
const grDialogOpen = ref(false);
const viewDialogOpen = ref(false);
const selectedReceiving = ref<Receiving | null>(null);

// Filtered receivings
const filteredReceivings = computed(() => {
    const query = tableState.debouncedSearch.value.toLowerCase().trim();
    
    if (!query) {
        return receivingsStore.receivings.value;
    }

    return receivingsStore.receivings.value.filter(
        (receiving) =>
            receiving.doc_number.toLowerCase().includes(query) ||
            receiving.received_by.name.toLowerCase().includes(query) ||
            receiving.status.toLowerCase().includes(query)
    );
});

// Actions
const openViewDialog = (receiving: Receiving) => {
    selectedReceiving.value = receiving;
    viewDialogOpen.value = true;
};

const openCancelDialog = (receiving: Receiving) => {
    selectedReceiving.value = receiving;
    cancelDialogOpen.value = true;
};

const openGrDialog = (receiving: Receiving) => {
    selectedReceiving.value = receiving;
    grDialogOpen.value = true;
};

const cancelReceiving = () => {
    if (!selectedReceiving.value) return;

    // Optimistic update
    const rollback = receivingsStore.updateReceivingOptimistic(selectedReceiving.value.id, {
        status: 'cancelled',
    });

    router.post(
        `/receivings/${selectedReceiving.value.id}/cancel`,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                cancelDialogOpen.value = false;
            },
            onError: () => {
                rollback.revert();
            },
        }
    );
};

const updateGrStatus = () => {
    if (!selectedReceiving.value) return;

    // Optimistic update
    const rollback = receivingsStore.updateReceivingOptimistic(selectedReceiving.value.id, {
        is_gr: !selectedReceiving.value.is_gr,
    });

    router.post(
        `/receivings/${selectedReceiving.value.id}/gr`,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                grDialogOpen.value = false;
            },
            onError: () => {
                rollback.revert();
            },
        }
    );
};

// Utilities
const getStatusVariant = (status: string): 'default' | 'destructive' | 'outline' | 'secondary' => {
    const variants: Record<string, 'default' | 'destructive' | 'outline' | 'secondary'> = {
        completed: 'default',
        draft: 'secondary',
        cancelled: 'destructive',
    };
    return variants[status] || 'outline';
};

const formatDate = (date: string): string => {
    return new Date(date).toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const canEdit = (receiving: Receiving) => {
    return !receiving.is_gr && receiving.status !== 'cancelled';
};
</script>

<template>
    <Head title="Receivings Management" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <Card>
                <CardHeader class="flex flex-row items-center justify-between">
                    <div>
                        <CardTitle class="flex items-center gap-2">
                            <PackageSearch class="h-5 w-5" />
                            Receivings Management
                        </CardTitle>
                        <CardDescription>
                            Manage incoming parts and stock receivings
                        </CardDescription>
                    </div>

                    <Button v-if="userStore.canManageReceivings.value" as-child>
                        <Link href="/receivings/create">
                            <PackagePlus class="mr-2 h-4 w-4" />
                            New Receiving
                        </Link>
                    </Button>
                </CardHeader>

                <CardContent>
                    <!-- Search Bar -->
                    <div class="mb-4">
                        <div class="relative">
                            <Search
                                class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                            />
                            <Input
                                v-model="tableState.searchQuery.value"
                                placeholder="Search by document number or received by..."
                                class="pl-9"
                            />
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div
                        v-if="filteredReceivings.length === 0"
                        class="py-8 text-center text-muted-foreground"
                    >
                        {{
                            tableState.searchQuery.value
                                ? 'No receivings found matching your search'
                                : 'No receivings found'
                        }}
                    </div>

                    <!-- Receivings List -->
                    <ReceivingsList
                        v-else
                        :receivings="filteredReceivings"
                        :format-date="formatDate"
                        :get-status-variant="getStatusVariant"
                        :can-edit="canEdit"
                        :can-confirm-g-r="userStore.canConfirmGR.value"
                        :can-manage-receivings="userStore.canManageReceivings.value"
                        @view="openViewDialog"
                        @cancel="openCancelDialog"
                        @update-gr="openGrDialog"
                    />

                    <!-- Pagination -->
                    <div
                        v-if="props.receivings.last_page > 1 && !tableState.searchQuery.value"
                        class="mt-6 flex justify-center gap-2"
                    >
                        <Link
                            v-for="link in props.receivings.links"
                            :key="link.label"
                            :href="link.url || '#'"
                            :class="[
                                'rounded-md px-3 py-2 text-sm',
                                link.active
                                    ? 'bg-primary text-primary-foreground'
                                    : 'bg-muted hover:bg-muted/80',
                                !link.url && 'cursor-not-allowed opacity-50',
                            ]"
                            :disabled="!link.url"
                            v-html="link.label"
                        />
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Cancel Confirmation Dialog -->
        <Dialog v-model:open="cancelDialogOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Cancel Receiving?</DialogTitle>
                    <DialogDescription>
                        This will cancel the receiving "{{
                            selectedReceiving?.doc_number
                        }}". Stock changes will be reversed if applicable.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="cancelDialogOpen = false">
                        Close
                    </Button>
                    <Button variant="destructive" @click="cancelReceiving">
                        Cancel Receiving
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- View Details Dialog -->
        <ReceivingDetailsDialog
            :open="viewDialogOpen"
            :receiving="selectedReceiving"
            :format-date="formatDate"
            :get-status-variant="getStatusVariant"
            @update:open="viewDialogOpen = $event"
        />

        <!-- GR Status Dialog -->
        <Dialog v-model:open="grDialogOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Update GR Status?</DialogTitle>
                    <DialogDescription>
                        This will
                        {{ selectedReceiving?.is_gr ? 'unconfirm' : 'confirm' }} GR for
                        receiving "{{ selectedReceiving?.doc_number }}".
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="grDialogOpen = false">Close</Button>
                    <Button @click="updateGrStatus">
                        {{ selectedReceiving?.is_gr ? 'Unconfirm' : 'Confirm' }} GR
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
