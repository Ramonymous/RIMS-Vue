<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { Package, Plus, Download, PackageOpen, Search } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import PartFormDialog from '@/components/parts/PartFormDialog.vue';
import PartImportDialog from '@/components/parts/PartImportDialog.vue';
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
import EmptyState from '@/components/ui/empty-state.vue';
import { Input } from '@/components/ui/input';
import PageHeader from '@/components/ui/page-header.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { createColumns, type Part } from './columns';

type Props = {
    parts: {
        data: Part[];
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

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Parts', href: '/parts' }];

// Dialogs
const createDialogOpen = ref(false);
const editDialogOpen = ref(false);
const deleteDialogOpen = ref(false);
const selectedPart = ref<Part | null>(null);

// Actions
const openEditDialog = (part: Part) => {
    selectedPart.value = part;
    editDialogOpen.value = true;
};

const openDeleteDialog = (part: Part) => {
    selectedPart.value = part;
    deleteDialogOpen.value = true;
};

const confirmDelete = () => {
    if (!selectedPart.value) return;

    router.delete(`/parts/${selectedPart.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            deleteDialogOpen.value = false;
            selectedPart.value = null;
        },
    });
};

const downloadTemplate = () => {
    window.location.href = '/parts/template';
};

const handleFormSuccess = () => {
    // Reload the page data after import/create/edit
    router.reload({ only: ['parts'] });
};

// Create columns with context
const columns = createColumns({
    onEdit: openEditDialog,
    onDelete: openDeleteDialog,
});

// Server-side search
const searchQuery = ref(props.filters.search || '');

const debouncedSearch = useDebounceFn(() => {
    router.get(
        '/parts',
        {
            search: searchQuery.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            only: ['parts', 'filters'],
        },
    );
}, 300);

watch(searchQuery, debouncedSearch);
</script>

<template>
    <Head title="Parts Management" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6 lg:p-8">
            <!-- Page Header -->
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <PageHeader
                    title="Parts Management"
                    description="Manage inventory parts and stock levels"
                />

                <div class="flex flex-wrap gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        @click="downloadTemplate"
                        class="gap-2"
                    >
                        <Download class="h-4 w-4" />
                        <span class="hidden sm:inline">Template</span>
                    </Button>

                    <PartImportDialog @success="handleFormSuccess" />

                    <Button
                        size="sm"
                        class="gap-2"
                        @click="createDialogOpen = true"
                    >
                        <Plus class="h-4 w-4" />
                        Add Part
                    </Button>
                </div>
            </div>

            <!-- Main Content Card -->
            <Card>
                <CardHeader class="border-b bg-muted/30">
                    <div class="flex items-center gap-3">
                        <div class="rounded-full bg-primary/10 p-2">
                            <Package class="h-5 w-5 text-primary" />
                        </div>
                        <div>
                            <CardTitle>Part Inventory</CardTitle>
                            <CardDescription>
                                {{ props.parts.total }} total parts in system
                            </CardDescription>
                        </div>
                    </div>
                </CardHeader>

                <CardContent class="p-6">
                    <!-- Search Bar -->
                    <div class="mb-6">
                        <div class="relative">
                            <Search
                                class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                            />
                            <Input
                                v-model="searchQuery"
                                placeholder="Search parts by number, name, code, or model..."
                                class="pl-9"
                            />
                        </div>
                    </div>

                    <!-- Empty State - No Parts -->
                    <EmptyState
                        v-if="props.parts.data.length === 0 && !searchQuery"
                        :icon="PackageOpen"
                        title="No parts yet"
                        description="Get started by adding your first part to the inventory"
                        action-label="Add Part"
                        @action="createDialogOpen = true"
                    />

                    <!-- Empty State - No Search Results -->
                    <EmptyState
                        v-else-if="props.parts.data.length === 0 && searchQuery"
                        :icon="Search"
                        title="No results found"
                        :description="`No parts found matching '${searchQuery}'`"
                    />

                    <!-- DataTable -->
                    <DataTable
                        v-else
                        :columns="columns"
                        :data="props.parts.data"
                    />

                    <!-- Pagination -->
                    <div v-if="props.parts.last_page > 1" class="mt-8">
                        <DataTablePagination :data="props.parts" />
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Create/Edit Form Dialog -->
        <PartFormDialog
            :open="createDialogOpen"
            @update:open="createDialogOpen = $event"
            @success="handleFormSuccess"
        />

        <PartFormDialog
            :open="editDialogOpen"
            :part="selectedPart as any"
            @update:open="editDialogOpen = $event"
            @success="handleFormSuccess"
        />

        <!-- Delete Confirmation Dialog -->
        <Dialog v-model:open="deleteDialogOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Are you sure?</DialogTitle>
                    <DialogDescription>
                        This will permanently delete the part "{{
                            selectedPart?.part_number
                        }}
                        - {{ selectedPart?.part_name }}". This action cannot be
                        undone.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="deleteDialogOpen = false">
                        Cancel
                    </Button>
                    <Button variant="destructive" @click="confirmDelete"
                        >Delete</Button
                    >
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
