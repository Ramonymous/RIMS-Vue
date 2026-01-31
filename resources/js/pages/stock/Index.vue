<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { BarChart3, Package, TrendingUp, TrendingDown, Filter, X, Download, FileSpreadsheet } from 'lucide-vue-next';
import {
    FlexRender,
    getCoreRowModel,
    getFilteredRowModel,
    getSortedRowModel,
    useVueTable,
    type ColumnFiltersState,
    type SortingState,
    type VisibilityState,
} from '@tanstack/vue-table';
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
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
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
    };
    movements: {
        data: Movement[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Stock', href: '/stock' },
];

// Stock tab state
const sorting = ref<SortingState>([]);
const columnFilters = ref<ColumnFiltersState>([]);
const columnVisibility = ref<VisibilityState>({});
const rowSelection = ref({});
const statusFilter = ref<'all' | 'active' | 'inactive'>('all');

// Movement tab state
const movementSearchQuery = ref('');
const movementTypeFilter = ref<'all' | 'in' | 'out'>('all');
const movementStartDate = ref('');
const movementEndDate = ref('');

// Filtered data based on status
const filteredData = computed(() => {
    if (statusFilter.value === 'all') return props.parts.data;
    return props.parts.data.filter((part) =>
        statusFilter.value === 'active' ? part.is_active : !part.is_active
    );
});

// Initialize table
const table = useVueTable({
    get data() {
        return filteredData.value;
    },
    get columns() {
        return columns;
    },
    getCoreRowModel: getCoreRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    onSortingChange: (updaterOrValue) => {
        sorting.value =
            typeof updaterOrValue === 'function'
                ? updaterOrValue(sorting.value)
                : updaterOrValue;
    },
    onColumnFiltersChange: (updaterOrValue) => {
        columnFilters.value =
            typeof updaterOrValue === 'function'
                ? updaterOrValue(columnFilters.value)
                : updaterOrValue;
    },
    onColumnVisibilityChange: (updaterOrValue) => {
        columnVisibility.value =
            typeof updaterOrValue === 'function'
                ? updaterOrValue(columnVisibility.value)
                : updaterOrValue;
    },
    onRowSelectionChange: (updaterOrValue) => {
        rowSelection.value =
            typeof updaterOrValue === 'function'
                ? updaterOrValue(rowSelection.value)
                : updaterOrValue;
    },
    state: {
        get sorting() {
            return sorting.value;
        },
        get columnFilters() {
            return columnFilters.value;
        },
        get columnVisibility() {
            return columnVisibility.value;
        },
        get rowSelection() {
            return rowSelection.value;
        },
    },
});

const selectedRowCount = computed(() => table.getFilteredSelectedRowModel().rows.length);

const filteredMovements = computed(() => {
    let filtered = props.movements.data;

    // Search filter
    const searchQuery = movementSearchQuery.value?.trim();
    if (searchQuery) {
        const query = searchQuery.toLowerCase();
        filtered = filtered.filter((movement) => {
            // Cache toLowerCase results
            const partNumber = movement.part.part_number.toLowerCase();
            const partName = movement.part.part_name.toLowerCase();
            return partNumber.includes(query) || partName.includes(query);
        });
    }

    // Type filter
    if (movementTypeFilter.value !== 'all') {
        filtered = filtered.filter((movement) => movement.type === movementTypeFilter.value);
    }

    // Date range filter
    if (movementStartDate.value || movementEndDate.value) {
        const start = movementStartDate.value
            ? new Date(`${movementStartDate.value}T00:00:00`)
            : null;
        const end = movementEndDate.value
            ? new Date(`${movementEndDate.value}T23:59:59`)
            : null;

        filtered = filtered.filter((movement) => {
            const createdAt = new Date(movement.created_at);
            if (start && createdAt < start) return false;
            if (end && createdAt > end) return false;
            return true;
        });
    }

    return filtered;
});

const formatLocalDate = (date: Date) => {
    const year = date.getFullYear();
    const month = `${date.getMonth() + 1}`.padStart(2, '0');
    const day = `${date.getDate()}`.padStart(2, '0');
    return `${year}-${month}-${day}`;
};

const setDefaultMovementDates = () => {
    const now = new Date();
    const start = new Date(now.getFullYear(), now.getMonth(), 1);
    const end = new Date(now.getFullYear(), now.getMonth() + 1, 0);

    movementStartDate.value = formatLocalDate(start);
    movementEndDate.value = formatLocalDate(end);
};

setDefaultMovementDates();

const stockStats = computed(() => {
    // Single pass optimization - avoid multiple array iterations
    let activeCount = 0;
    let totalValue = 0;
    
    for (const part of props.parts.data) {
        if (part.is_active) activeCount++;
        totalValue += part.stock;
    }
    
    const total = props.parts.data.length;
    const inactiveCount = total - activeCount;

    return { total, activeCount, inactiveCount, totalValue };
});

function clearSelection() {
    table.resetRowSelection();
}

function clearFilters() {
    table.getColumn('part_number')?.setFilterValue('');
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
    
    // Get current search value
    const searchValue = table.getColumn('part_number')?.getFilterValue() as string;
    if (searchValue) {
        params.append('search', searchValue);
    }
    
    // Get status filter
    if (statusFilter.value !== 'all') {
        params.append('status', statusFilter.value);
    }
    
    // Add stock level filter if specified
    if (stockLevel) {
        params.append('stock_level', stockLevel);
    }
    
    // Navigate to export endpoint
    window.location.href = `/stock/export?${params.toString()}`;
}

</script>

<template>
    <Head title="Stock Management" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">Stock Management</h1>
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
                            <FileSpreadsheet class="mr-2 h-4 w-4 text-destructive" />
                            Export Out of Stock
                        </DropdownMenuItem>
                        <DropdownMenuItem @click="exportStock('low')">
                            <FileSpreadsheet class="mr-2 h-4 w-4 text-orange-500" />
                            Export Low Stock
                        </DropdownMenuItem>
                        <DropdownMenuItem @click="exportStock('available')">
                            <FileSpreadsheet class="mr-2 h-4 w-4 text-green-500" />
                            Export Available Stock
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>

            <!-- Stats Cards -->
            <div class="grid gap-4 md:grid-cols-4">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total Parts</CardTitle>
                        <Package class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stockStats.total }}</div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total Stock Qty</CardTitle>
                        <BarChart3 class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stockStats.totalValue.toLocaleString() }}</div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Active Parts</CardTitle>
                        <Package class="h-4 w-4 text-primary" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stockStats.activeCount }}</div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Inactive Parts</CardTitle>
                        <TrendingDown class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-muted-foreground">{{ stockStats.inactiveCount }}</div>
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
                                <div class="flex gap-2">
                                    <Button
                                        v-if="selectedRowCount > 0"
                                        variant="outline"
                                        size="sm"
                                        @click="clearSelection"
                                    >
                                        <X class="mr-2 h-4 w-4" />
                                        Clear ({{ selectedRowCount }})
                                    </Button>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        @click="clearFilters"
                                    >
                                        <Filter class="mr-2 h-4 w-4" />
                                        Clear Filters
                                    </Button>
                                </div>
                            </div>
                        </CardHeader>

                        <CardContent class="space-y-4">
                            <!-- Filters -->
                            <div class="flex flex-col gap-4 md:flex-row">
                                <div class="flex-1">
                                    <Input
                                        :model-value="table.getColumn('part_number')?.getFilterValue() as string"
                                        placeholder="Search by part number or name..."
                                        class="w-full"
                                        @input="table.getColumn('part_number')?.setFilterValue($event.target.value)"
                                    />
                                </div>
                                <Select v-model="statusFilter">
                                    <SelectTrigger class="w-full md:w-[180px]">
                                        <SelectValue placeholder="Status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All Status</SelectItem>
                                        <SelectItem value="active">Active</SelectItem>
                                        <SelectItem value="inactive">Inactive</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <!-- Data Table -->
                            <div class="rounded-md border">
                                <Table>
                                    <TableHeader>
                                        <TableRow
                                            v-for="headerGroup in table.getHeaderGroups()"
                                            :key="headerGroup.id"
                                        >
                                            <TableHead
                                                v-for="header in headerGroup.headers"
                                                :key="header.id"
                                            >
                                                <FlexRender
                                                    v-if="!header.isPlaceholder"
                                                    :render="header.column.columnDef.header"
                                                    :props="header.getContext()"
                                                />
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        <template v-if="table.getRowModel().rows?.length">
                                            <TableRow
                                                v-for="row in table.getRowModel().rows"
                                                :key="row.id"
                                                :data-state="row.getIsSelected() ? 'selected' : undefined"
                                            >
                                                <TableCell
                                                    v-for="cell in row.getVisibleCells()"
                                                    :key="cell.id"
                                                >
                                                    <FlexRender
                                                        :render="cell.column.columnDef.cell"
                                                        :props="cell.getContext()"
                                                    />
                                                </TableCell>
                                            </TableRow>
                                        </template>
                                        <template v-else>
                                            <TableRow>
                                                <TableCell :colspan="columns.length" class="h-24 text-center">
                                                    No parts found
                                                </TableCell>
                                            </TableRow>
                                        </template>
                                    </TableBody>
                                </Table>
                            </div>

                            <div class="text-sm text-muted-foreground">
                                Showing {{ table.getFilteredRowModel().rows.length }} of {{ props.parts.data.length }} parts
                                <span v-if="selectedRowCount > 0" class="ml-2 font-medium">
                                    ({{ selectedRowCount }} selected)
                                </span>
                            </div>
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
                            <div class="flex flex-col gap-4 md:flex-row md:items-end">
                                <div class="flex-1">
                                    <Input
                                        v-model="movementSearchQuery"
                                        placeholder="Search by part number or name..."
                                        class="w-full"
                                    />
                                </div>
                                <div class="flex flex-col gap-2">
                                    <span class="text-xs text-muted-foreground">Start Date</span>
                                    <Input
                                        v-model="movementStartDate"
                                        type="date"
                                        class="w-full md:w-[180px]"
                                    />
                                </div>
                                <div class="flex flex-col gap-2">
                                    <span class="text-xs text-muted-foreground">End Date</span>
                                    <Input
                                        v-model="movementEndDate"
                                        type="date"
                                        class="w-full md:w-[180px]"
                                    />
                                </div>
                                <Select v-model="movementTypeFilter">
                                    <SelectTrigger class="w-full md:w-[180px]">
                                        <SelectValue placeholder="Movement Type" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All Types</SelectItem>
                                        <SelectItem value="in">Incoming</SelectItem>
                                        <SelectItem value="out">Outgoing</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <!-- Movements List -->
                            <div class="space-y-2">
                                <div
                                    v-if="filteredMovements.length === 0"
                                    class="rounded-lg border border-dashed p-8 text-center text-muted-foreground"
                                >
                                    No movements found
                                </div>

                                <div
                                    v-for="movement in filteredMovements"
                                    :key="movement.id"
                                    class="rounded-lg border p-4"
                                >
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2">
                                                <Badge
                                                    :variant="movement.type === 'in' ? 'default' : 'destructive'"
                                                >
                                                    {{ movement.type === 'in' ? 'IN' : 'OUT' }}
                                                </Badge>
                                                <span class="font-semibold">
                                                    {{ movement.part.part_number }}
                                                </span>
                                                <span class="text-sm text-muted-foreground">
                                                    {{ movement.part.part_name }}
                                                </span>
                                            </div>
                                            <div class="mt-2 grid grid-cols-2 gap-4 text-sm md:grid-cols-4">
                                                <div>
                                                    <span class="text-muted-foreground">Qty:</span>
                                                    <span class="ml-1 font-medium">{{ movement.qty }}</span>
                                                </div>
                                                <div>
                                                    <span class="text-muted-foreground">Before:</span>
                                                    <span class="ml-1 font-medium">{{ movement.stock_before }}</span>
                                                </div>
                                                <div>
                                                    <span class="text-muted-foreground">After:</span>
                                                    <span class="ml-1 font-medium">{{ movement.stock_after }}</span>
                                                </div>
                                                <div>
                                                    <span class="text-muted-foreground">Reference:</span>
                                                    <span class="ml-1 font-medium">
                                                        {{ getReferenceLabel(movement.reference_type) }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="text-right text-xs text-muted-foreground">
                                            {{ formatDate(movement.created_at) }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="text-sm text-muted-foreground">
                                Showing {{ filteredMovements.length }} of {{ props.movements.data.length }} movements
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>
            </Tabs>
        </div>
    </AppLayout>
</template>
