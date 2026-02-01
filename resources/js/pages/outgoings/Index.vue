<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { PackageMinus, PackageOpen, Search } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { Badge } from '@/components/ui/badge';
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
import { usePermissions } from '@/composables/usePermissions';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { createColumns, type Outgoing } from './columns';

type Props = {
    outgoings: {
        data: Outgoing[];
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
    { title: 'Outgoings', href: '/outgoings' },
];

// Permissions
const { canManageOutgoings, canConfirmGI } = usePermissions();

// Dialogs
const cancelDialogOpen = ref(false);
const giDialogOpen = ref(false);
const viewDialogOpen = ref(false);
const selectedOutgoing = ref<Outgoing | null>(null);

// Actions
const openViewDialog = (outgoing: Outgoing) => {
    selectedOutgoing.value = outgoing;
    viewDialogOpen.value = true;
};

const openCancelDialog = (outgoing: Outgoing) => {
    selectedOutgoing.value = outgoing;
    cancelDialogOpen.value = true;
};

const openGiDialog = (outgoing: Outgoing) => {
    selectedOutgoing.value = outgoing;
    giDialogOpen.value = true;
};

const cancelOutgoing = () => {
    if (!selectedOutgoing.value) return;

    router.post(
        `/outgoings/${selectedOutgoing.value.id}/cancel`,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                cancelDialogOpen.value = false;
            },
        },
    );
};

const updateGiStatus = () => {
    if (!selectedOutgoing.value) return;

    router.post(
        `/outgoings/${selectedOutgoing.value.id}/gi`,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                giDialogOpen.value = false;
            },
        },
    );
};

// Create columns with context
const columns = createColumns({
    onView: openViewDialog,
    onCancel: openCancelDialog,
    onUpdateGI: openGiDialog,
    canManageOutgoings: canManageOutgoings.value,
    canConfirmGI: canConfirmGI.value,
});

// Server-side search
const searchQuery = ref(props.filters.search || '');

const debouncedSearch = useDebounceFn(() => {
    router.get(
        '/outgoings',
        {
            search: searchQuery.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            only: ['outgoings', 'filters'],
        },
    );
}, 300);

watch(searchQuery, debouncedSearch);

// Utilities
const getStatusVariant = (
    status: string,
): 'default' | 'destructive' | 'outline' | 'secondary' => {
    const variants: Record<
        string,
        'default' | 'destructive' | 'outline' | 'secondary'
    > = {
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
    <Head title="Outgoings Management" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <Card>
                <CardHeader class="flex flex-row items-center justify-between">
                    <div>
                        <CardTitle class="flex items-center gap-2">
                            <PackageOpen class="h-5 w-5" />
                            Outgoings Management
                        </CardTitle>
                        <CardDescription>
                            Manage outgoing parts and stock issues
                        </CardDescription>
                    </div>

                    <Button v-if="canManageOutgoings" as-child>
                        <Link href="/outgoings/create">
                            <PackageMinus class="mr-2 h-4 w-4" />
                            New Outgoing
                        </Link>
                    </Button>
                </CardHeader>

                <CardContent>
                    <!-- Search Bar -->
                    <div class="mb-4">
                        <div class="relative">
                            <Search
                                class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                            />
                            <Input
                                v-model="searchQuery"
                                placeholder="Search by document number or issued by..."
                                class="pl-9"
                            />
                        </div>
                    </div>

                    <!-- DataTable -->
                    <DataTable
                        :columns="columns"
                        :data="props.outgoings.data"
                    />

                    <!-- Pagination -->
                    <div v-if="props.outgoings.last_page > 1" class="mt-6">
                        <DataTablePagination :data="props.outgoings" />
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Cancel Confirmation Dialog -->
        <Dialog v-model:open="cancelDialogOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Cancel Outgoing?</DialogTitle>
                    <DialogDescription>
                        This will cancel the outgoing "{{
                            selectedOutgoing?.doc_number
                        }}". Stock changes will be reversed if applicable.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="cancelDialogOpen = false">
                        Close
                    </Button>
                    <Button variant="destructive" @click="cancelOutgoing">
                        Cancel Outgoing
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- View Items Dialog -->
        <Dialog v-model:open="viewDialogOpen">
            <DialogContent class="max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Outgoing Details</DialogTitle>
                    <DialogDescription>
                        {{ selectedOutgoing?.doc_number }}
                    </DialogDescription>
                </DialogHeader>
                <div v-if="selectedOutgoing" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="font-medium">Status:</span>
                            <Badge
                                :variant="
                                    getStatusVariant(selectedOutgoing.status)
                                "
                                class="ml-2"
                            >
                                {{ selectedOutgoing.status }}
                            </Badge>
                            <Badge
                                v-if="selectedOutgoing.is_gi"
                                variant="default"
                                class="ml-2"
                            >
                                GI Confirmed
                            </Badge>
                        </div>
                        <div>
                            <span class="font-medium">Issued By:</span>
                            {{ selectedOutgoing.issued_by.name }}
                        </div>
                        <div>
                            <span class="font-medium">Issued At:</span>
                            {{ formatDate(selectedOutgoing.issued_at) }}
                        </div>
                    </div>

                    <div class="border-t pt-4">
                        <h4 class="mb-3 font-medium">
                            Items ({{ selectedOutgoing.items.length }})
                        </h4>
                        <div class="space-y-2">
                            <div
                                v-for="(item, index) in selectedOutgoing.items"
                                :key="index"
                                class="rounded-lg border p-3"
                            >
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <p class="font-medium">
                                            {{ item.part.part_number }}
                                        </p>
                                        <p
                                            class="text-sm text-muted-foreground"
                                        >
                                            {{ item.part.part_name }}
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-medium">
                                            Qty: {{ item.qty }}
                                        </p>
                                        <p
                                            class="text-xs text-muted-foreground"
                                        >
                                            Stock: {{ item.part.stock }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="viewDialogOpen = false">
                        Close
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- GI Status Dialog -->
        <Dialog v-model:open="giDialogOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Update GI Status?</DialogTitle>
                    <DialogDescription>
                        This will
                        {{ selectedOutgoing?.is_gi ? 'unconfirm' : 'confirm' }}
                        GI for outgoing "{{ selectedOutgoing?.doc_number }}".
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="giDialogOpen = false">
                        Close
                    </Button>
                    <Button @click="updateGiStatus">
                        {{ selectedOutgoing?.is_gi ? 'Unconfirm' : 'Confirm' }}
                        GI
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
