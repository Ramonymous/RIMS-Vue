<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import {
    BarChart3,
    Package,
    TrendingDown,
    Filter,
    Download,
    FileSpreadsheet,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
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
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { columns, type Part } from './columns';

type Movement = {
    id: string;
    part: {
        id: string;
        part_number: string;
        part_name: string;
    };
    stock_before: number;
    type: 'in' | 'out';
    qty: number;
    stock_after: number;
    reference_type: string;
    reference_id: string;
    created_at: string;
};

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
    movements: {
        data: Movement[];
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
        status?: string;
        movement_search?: string;
        movement_type?: string;
        movement_start_date?: string;
        movement_end_date?: string;
    };
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Stock', href: '/stock' }];

// Stock tab filters (server-side)
const searchQuery = ref(props.filters.search || '');
const statusFilter = ref<'all' | 'active' | 'inactive'>(
    (props.filters.status as 'all' | 'active' | 'inactive') || 'all',
);

// Movement tab filters (server-side)
const movementSearchQuery = ref(props.filters.movement_search || '');
const movementTypeFilter = ref<'all' | 'in' | 'out'>(
    (props.filters.movement_type as 'all' | 'in' | 'out') || 'all',
);
const movementStartDate = ref(props.filters.movement_start_date || '');
const movementEndDate = ref(props.filters.movement_end_date || '');

// Debounced server-side search for stock
const debouncedStockSearch = useDebounceFn(() => {
    router.get(
        '/stock',
        {
            search: searchQuery.value || undefined,
            status:
                statusFilter.value !== 'all' ? statusFilter.value : undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            only: ['parts', 'filters'],
        },
    );
}, 300);

// Debounced server-side search for movements
const debouncedMovementSearch = useDebounceFn(() => {
    router.get(
        '/stock',
        {
            movement_search: movementSearchQuery.value || undefined,
            movement_type:
                movementTypeFilter.value !== 'all'
                    ? movementTypeFilter.value
                    : undefined,
            movement_start_date: movementStartDate.value || undefined,
            movement_end_date: movementEndDate.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            only: ['movements', 'filters'],
        },
    );
}, 300);

// Watch for filter changes
watch(searchQuery, debouncedStockSearch);
watch(statusFilter, debouncedStockSearch);
watch(movementSearchQuery, debouncedMovementSearch);
watch(movementTypeFilter, debouncedMovementSearch);
watch(movementStartDate, debouncedMovementSearch);
watch(movementEndDate, debouncedMovementSearch);

// Set default movement dates on mount
const formatLocalDate = (date: Date) => {
    const year = date.getFullYear();
    const month = `${date.getMonth() + 1}`.padStart(2, '0');
    const day = `${date.getDate()}`.padStart(2, '0');
    return `${year}-${month}-${day}`;
};

if (!movementStartDate.value && !movementEndDate.value) {
    const now = new Date();
    const start = new Date(now.getFullYear(), now.getMonth(), 1);
    const end = new Date(now.getFullYear(), now.getMonth() + 1, 0);
    movementStartDate.value = formatLocalDate(start);
    movementEndDate.value = formatLocalDate(end);
}

const stockStats = computed(() => {
    return {
        total: props.parts.total,
        totalValue: props.parts.data.reduce((sum, part) => sum + part.stock, 0),
        activeCount: props.parts.data.filter((p) => p.is_active).length,
        inactiveCount: props.parts.data.filter((p) => !p.is_active).length,
    };
});

function clearFilters() {
    searchQuery.value = '';
    statusFilter.value = 'all';
}

function formatDate(date: string) {
    return new Date(date).toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function getReferenceLabel(referenceType: string) {
    if (referenceType.includes('Receiving')) return 'Receiving';
    if (referenceType.includes('Outgoing')) return 'Outgoing';
    return 'Unknown';
}

function exportStock(stockLevel?: string) {
    const params = new URLSearchParams();

    if (searchQuery.value) {
        params.append('search', searchQuery.value);
    }

    if (statusFilter.value !== 'all') {
        params.append('status', statusFilter.value);
    }

    if (stockLevel) {
        params.append('stock_level', stockLevel);
    }

    window.location.href = `/stock/export?${params.toString()}`;
}
</script>

<template>
    <Head title="Stock Management" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">
                        Stock Management
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        Monitor inventory levels and track stock movements
                    </p>
                </div>
                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <Button variant="default">
                            <Download class="mr-2 h-4 w-4" />
                            Export Report
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-56">
                        <DropdownMenuLabel>Export Options</DropdownMenuLabel>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem @click="exportStock()">
                            <FileSpreadsheet class="mr-2 h-4 w-4" />
                            Export All Stock
                        </DropdownMenuItem>
                        <DropdownMenuItem @click="exportStock('out_of_stock')">
                            <FileSpreadsheet
                                class="mr-2 h-4 w-4 text-destructive"
                            />
                            Export Out of Stock
                        </DropdownMenuItem>
                        <DropdownMenuItem @click="exportStock('low')">
                            <FileSpreadsheet
                                class="mr-2 h-4 w-4 text-orange-500"
                            />
                            Export Low Stock
                        </DropdownMenuItem>
                        <DropdownMenuItem @click="exportStock('available')">
                            <FileSpreadsheet
                                class="mr-2 h-4 w-4 text-green-500"
                            />
                            Export Available Stock
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>

            <!-- Stats Cards -->
            <div class="grid gap-4 md:grid-cols-4">
                <Card>
                    <CardHeader
                        class="flex flex-row items-center justify-between space-y-0 pb-2"
                    >
                        <CardTitle class="text-sm font-medium"
                            >Total Parts</CardTitle
                        >
                        <Package class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">
                            {{ stockStats.total }}
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader
                        class="flex flex-row items-center justify-between space-y-0 pb-2"
                    >
                        <CardTitle class="text-sm font-medium"
                            >Total Stock Qty</CardTitle
                        >
                        <BarChart3 class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">
                            {{ stockStats.totalValue.toLocaleString() }}
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader
                        class="flex flex-row items-center justify-between space-y-0 pb-2"
                    >
                        <CardTitle class="text-sm font-medium"
                            >Active Parts</CardTitle
                        >
                        <Package class="h-4 w-4 text-primary" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">
                            {{ stockStats.activeCount }}
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader
                        class="flex flex-row items-center justify-between space-y-0 pb-2"
                    >
                        <CardTitle class="text-sm font-medium"
                            >Inactive Parts</CardTitle
                        >
                        <TrendingDown class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-muted-foreground">
                            {{ stockStats.inactiveCount }}
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Tabs -->
            <Tabs default-value="stock" class="w-full">
                <TabsList>
                    <TabsTrigger value="stock">Stock</TabsTrigger>
                    <TabsTrigger value="movements">Movements</TabsTrigger>
                </TabsList>

                <!-- Stock Tab -->
                <TabsContent value="stock" class="space-y-4">
                    <Card>
                        <CardHeader>
                            <div class="flex items-center justify-between">
                                <div>
                                    <CardTitle>Stock Levels</CardTitle>
                                    <CardDescription>
                                        View and manage current inventory levels
                                    </CardDescription>
                                </div>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    @click="clearFilters"
                                >
                                    <Filter class="mr-2 h-4 w-4" />
                                    Clear Filters
                                </Button>
                            </div>
                        </CardHeader>

                        <CardContent class="space-y-4">
                            <!-- Filters -->
                            <div class="flex flex-col gap-4 md:flex-row">
                                <div class="flex-1">
                                    <Input
                                        v-model="searchQuery"
                                        placeholder="Search by part number or name..."
                                        class="w-full"
                                    />
                                </div>
                                <Select v-model="statusFilter">
                                    <SelectTrigger class="w-full md:w-[180px]">
                                        <SelectValue placeholder="Status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all"
                                            >All Status</SelectItem
                                        >
                                        <SelectItem value="active"
                                            >Active</SelectItem
                                        >
                                        <SelectItem value="inactive"
                                            >Inactive</SelectItem
                                        >
                                    </SelectContent>
                                </Select>
                            </div>

                            <!-- Data Table -->
                            <DataTable
                                :columns="columns"
                                :data="props.parts.data"
                            >
                                <template #empty> No parts found </template>
                                <template #footer>
                                    <DataTablePagination :data="props.parts" />
                                </template>
                            </DataTable>
                        </CardContent>
                    </Card>
                </TabsContent>

                <!-- Movements Tab -->
                <TabsContent value="movements" class="space-y-4">
                    <Card>
                        <CardHeader>
                            <CardTitle>Stock Movements</CardTitle>
                            <CardDescription>
                                View historical stock movements and transactions
                            </CardDescription>
                        </CardHeader>

                        <CardContent class="space-y-4">
                            <!-- Filters -->
                            <div
                                class="flex flex-col gap-4 md:flex-row md:items-end"
                            >
                                <div class="flex-1">
                                    <Input
                                        v-model="movementSearchQuery"
                                        placeholder="Search by part number or name..."
                                        class="w-full"
                                    />
                                </div>
                                <div class="flex flex-col gap-2">
                                    <span class="text-xs text-muted-foreground"
                                        >Start Date</span
                                    >
                                    <Input
                                        v-model="movementStartDate"
                                        type="date"
                                        class="w-full md:w-[180px]"
                                    />
                                </div>
                                <div class="flex flex-col gap-2">
                                    <span class="text-xs text-muted-foreground"
                                        >End Date</span
                                    >
                                    <Input
                                        v-model="movementEndDate"
                                        type="date"
                                        class="w-full md:w-[180px]"
                                    />
                                </div>
                                <Select v-model="movementTypeFilter">
                                    <SelectTrigger class="w-full md:w-[180px]">
                                        <SelectValue
                                            placeholder="Movement Type"
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all"
                                            >All Types</SelectItem
                                        >
                                        <SelectItem value="in"
                                            >Incoming</SelectItem
                                        >
                                        <SelectItem value="out"
                                            >Outgoing</SelectItem
                                        >
                                    </SelectContent>
                                </Select>
                            </div>

                            <!-- Movements List -->
                            <div class="space-y-2">
                                <div
                                    v-if="props.movements.data.length === 0"
                                    class="rounded-lg border border-dashed p-8 text-center text-muted-foreground"
                                >
                                    No movements found
                                </div>

                                <div
                                    v-for="movement in props.movements.data"
                                    :key="movement.id"
                                    class="rounded-lg border p-4"
                                >
                                    <div
                                        class="flex items-start justify-between"
                                    >
                                        <div class="flex-1">
                                            <div
                                                class="flex items-center gap-2"
                                            >
                                                <Badge
                                                    :variant="
                                                        movement.type === 'in'
                                                            ? 'default'
                                                            : 'destructive'
                                                    "
                                                >
                                                    {{
                                                        movement.type === 'in'
                                                            ? 'IN'
                                                            : 'OUT'
                                                    }}
                                                </Badge>
                                                <span class="font-semibold">
                                                    {{
                                                        movement.part
                                                            .part_number
                                                    }}
                                                </span>
                                                <span
                                                    class="text-sm text-muted-foreground"
                                                >
                                                    {{
                                                        movement.part.part_name
                                                    }}
                                                </span>
                                            </div>
                                            <div
                                                class="mt-2 grid grid-cols-2 gap-4 text-sm md:grid-cols-4"
                                            >
                                                <div>
                                                    <span
                                                        class="text-muted-foreground"
                                                        >Qty:</span
                                                    >
                                                    <span
                                                        class="ml-1 font-medium"
                                                        >{{
                                                            movement.qty
                                                        }}</span
                                                    >
                                                </div>
                                                <div>
                                                    <span
                                                        class="text-muted-foreground"
                                                        >Before:</span
                                                    >
                                                    <span
                                                        class="ml-1 font-medium"
                                                        >{{
                                                            movement.stock_before
                                                        }}</span
                                                    >
                                                </div>
                                                <div>
                                                    <span
                                                        class="text-muted-foreground"
                                                        >After:</span
                                                    >
                                                    <span
                                                        class="ml-1 font-medium"
                                                        >{{
                                                            movement.stock_after
                                                        }}</span
                                                    >
                                                </div>
                                                <div>
                                                    <span
                                                        class="text-muted-foreground"
                                                        >Reference:</span
                                                    >
                                                    <span
                                                        class="ml-1 font-medium"
                                                    >
                                                        {{
                                                            getReferenceLabel(
                                                                movement.reference_type,
                                                            )
                                                        }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div
                                            class="text-right text-xs text-muted-foreground"
                                        >
                                            {{
                                                formatDate(movement.created_at)
                                            }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <DataTablePagination :data="props.movements" />
                        </CardContent>
                    </Card>
                </TabsContent>
            </Tabs>
        </div>
    </AppLayout>
</template>
