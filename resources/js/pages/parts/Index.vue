<script setup lang="ts">
import { computed, ref, watch, onMounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Package, Plus, Download, PackageOpen, Search } from 'lucide-vue-next';
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
import EmptyState from '@/components/ui/empty-state.vue';
import PageHeader from '@/components/ui/page-header.vue';
import PartsList from '@/components/parts/PartsList.vue';
import PartFormDialog from '@/components/parts/PartFormDialog.vue';
import PartImportDialog from '@/components/parts/PartImportDialog.vue';
import { exportTemplate } from '@/actions/App/Http/Controllers/PartsController';
import type { Part } from '@/composables/useParts';
import { usePartsStore } from '@/stores/usePartsStore';
import { useTableState } from '@/composables/useTableState';

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
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Parts', href: '/parts' },
];

// Store initialization with caching
const partsStore = usePartsStore();

// Initialize store data on mount
onMounted(() => {
    partsStore.setParts(props.parts.data);
});

// Watch for data changes from Inertia and update store
watch(
    () => props.parts.data,
    (newParts) => {
        partsStore.setParts(newParts);
    }
);

// Table state with optimized search
const tableState = useTableState<Part>({
    defaultSort: 'part_number',
    debounceMs: 300,
});

// Dialogs
const createDialogOpen = ref(false);
const editDialogOpen = ref(false);
const deleteDialogOpen = ref(false);
const selectedPart = ref<Part | null>(null);

// Computed filtered parts with debounced search
const filteredParts = computed(() => {
    const query = tableState.debouncedSearch.value.toLowerCase().trim();
    
    if (!query) {
        return partsStore.parts.value;
    }

    return partsStore.parts.value.filter(
        (part) =>
            part.part_number.toLowerCase().includes(query) ||
            part.part_name.toLowerCase().includes(query) ||
            part.customer_code?.toLowerCase().includes(query) ||
            part.supplier_code?.toLowerCase().includes(query) ||
            part.model?.toLowerCase().includes(query)
    );
});

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

    // Optimistic update
    const rollback = partsStore.deletePartOptimistic(selectedPart.value.id);

    router.delete(`/parts/${selectedPart.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            deleteDialogOpen.value = false;
            selectedPart.value = null;
        },
        onError: () => {
            // Revert optimistic update on error
            rollback.revert();
        },
    });
};

const downloadTemplate = () => {
    window.location.href = exportTemplate.url();
};

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const handleFormSuccess = () => {
    // Reload the page data after import/create/edit
    router.reload({ only: ['parts'] });
};
</script>

<template>
    <Head title="Parts Management" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6 lg:p-8">
            <!-- Page Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
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

                    <Button size="sm" class="gap-2" @click="createDialogOpen = true">
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
                                class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                            />
                            <Input
                                v-model="tableState.searchQuery.value"
                                placeholder="Search parts by number, name, code, or model..."
                                class="pl-9"
                            />
                        </div>
                    </div>

                    <!-- Empty State - No Parts -->
                    <EmptyState
                        v-if="partsStore.parts.value.length === 0 && !tableState.searchQuery.value"
                        :icon="PackageOpen"
                        title="No parts yet"
                        description="Get started by adding your first part to the inventory"
                        action-label="Add Part"
                        @action="createDialogOpen = true"
                    />

                    <!-- Empty State - No Search Results -->
                    <EmptyState
                        v-else-if="filteredParts.length === 0 && tableState.searchQuery.value"
                        :icon="Search"
                        title="No results found"
                        :description="`No parts found matching '${tableState.searchQuery.value}'`"
                    />

                    <!-- Parts List -->
                    <PartsList
                        v-else
                        :parts="filteredParts"
                        :format-date="formatDate"
                        @edit="openEditDialog"
                        @delete="openDeleteDialog"
                    />

                    <!-- Pagination -->
                    <div
                        v-if="props.parts.last_page > 1 && !tableState.searchQuery.value"
                        class="mt-8 flex flex-wrap justify-center gap-2"
                    >
                        <Link
                            v-for="link in props.parts.links"
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
            :part="selectedPart"
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
                        }} - {{ selectedPart?.part_name }}". This action cannot be undone.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="deleteDialogOpen = false">
                        Cancel
                    </Button>
                    <Button variant="destructive" @click="confirmDelete">Delete</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
