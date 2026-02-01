<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { PackagePlus, PackageSearch, Search } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import ReceivingDetailsDialog from '@/components/receivings/ReceivingDetailsDialog.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { DataTable, DataTablePagination } from '@/components/ui/data-table';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import { useUserStore } from '@/stores/useUserStore';
import { type BreadcrumbItem } from '@/types';
import { createColumns, type Receiving } from './columns';

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
    filters: {
        search?: string;
    };
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Receivings', href: '/receivings' },
];

// Store
const userStore = useUserStore();

// Dialogs
const cancelDialogOpen = ref(false);
const grDialogOpen = ref(false);
const viewDialogOpen = ref(false);
const selectedReceiving = ref<Receiving | null>(null);

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

    router.post(
        `/receivings/${selectedReceiving.value.id}/cancel`,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                cancelDialogOpen.value = false;
            },
        }
    );
};

const updateGrStatus = () => {
    if (!selectedReceiving.value) return;

    router.post(
        `/receivings/${selectedReceiving.value.id}/gr`,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                grDialogOpen.value = false;
            },
        }
    );
};

// Create columns with context
const columns = createColumns({
    onView: openViewDialog,
    onCancel: openCancelDialog,
    onUpdateGR: openGrDialog,
    canManageReceivings: userStore.canManageReceivings.value,
    canConfirmGR: userStore.canConfirmGR.value,
});

// Server-side search
const searchQuery = ref(props.filters.search || '');

const debouncedSearch = useDebounceFn(() => {
    router.get('/receivings', {
        search: searchQuery.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        only: ['receivings', 'filters'],
    });
}, 300);

watch(searchQuery, debouncedSearch);

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
                                v-model="searchQuery"
                                placeholder="Search by document number or received by..."
                                class="pl-9"
                            />
                        </div>
                    </div>

                    <!-- DataTable -->
                    <DataTable
                        :columns="columns"
                        :data="props.receivings.data"
                        :on-row-click="openViewDialog"
                    />

                    <!-- Pagination -->
                    <div v-if="props.receivings.last_page > 1" class="mt-6">
                        <DataTablePagination :data="props.receivings" />
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
