# DataTable Component

Reusable data table component built with TanStack Table and shadcn-vue.

## Features

- ✅ Server-side pagination
- ✅ Sorting
- ✅ Filtering
- ✅ Column visibility
- ✅ Row selection
- ✅ Customizable slots
- ✅ Fully typed with TypeScript

## Basic Usage

### 1. Define your columns

```typescript
import { type ColumnDef } from '@tanstack/vue-table';
import { h } from 'vue';
import { Badge } from '@/components/ui/badge';

export interface Part {
    id: string;
    part_number: string;
    part_name: string;
    stock: number;
    is_active: boolean;
}

export const columns: ColumnDef<Part>[] = [
    {
        accessorKey: 'part_number',
        header: 'Part Number',
    },
    {
        accessorKey: 'part_name',
        header: 'Part Name',
    },
    {
        accessorKey: 'stock',
        header: 'Stock',
        cell: ({ row }) => {
            const stock = row.getValue('stock') as number;
            return h('div', { class: 'font-medium' }, stock);
        },
    },
    {
        accessorKey: 'is_active',
        header: 'Status',
        cell: ({ row }) => {
            const isActive = row.getValue('is_active') as boolean;
            return h(Badge, { 
                variant: isActive ? 'default' : 'secondary' 
            }, () => isActive ? 'Active' : 'Inactive');
        },
    },
];
```

### 2. Use the DataTable component

```vue
<script setup lang="ts">
import { DataTable, DataTablePagination } from '@/components/ui/data-table';
import { columns, type Part } from './columns';

interface Props {
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
}

const props = defineProps<Props>();
</script>

<template>
    <DataTable :columns="columns" :data="props.parts.data">
        <template #empty>
            No results found
        </template>
        <template #footer>
            <DataTablePagination :data="props.parts" />
        </template>
    </DataTable>
</template>
```

### 3. Server-side search and filtering

For server-side search, use Inertia with debounced search:

```vue
<script setup lang="ts">
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';

const searchQuery = ref('');
const statusFilter = ref('all');

// Debounced server-side search
const debouncedSearch = useDebounceFn(() => {
    router.get('/parts', {
        search: searchQuery.value || undefined,
        status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        only: ['parts', 'filters'],
    });
}, 300);

watch(searchQuery, debouncedSearch);
watch(statusFilter, debouncedSearch);
</script>

<template>
    <div class="space-y-4">
        <Input
            v-model="searchQuery"
            placeholder="Search..."
        />
        <DataTable :columns="columns" :data="props.parts.data">
            <template #footer>
                <DataTablePagination :data="props.parts" />
            </template>
        </DataTable>
    </div>
</template>
```

## Controller Setup

Your Laravel controller should return paginated data:

```php
public function index(Request $request): Response
{
    $query = Part::query();
    
    // Server-side search
    if ($request->filled('search')) {
        $search = $request->search;
        $query->where(function ($q) use ($search) {
            $q->where('part_number', 'LIKE', "%{$search}%")
              ->orWhere('part_name', 'LIKE', "%{$search}%");
        });
    }
    
    // Server-side filtering
    if ($request->filled('status') && $request->status !== 'all') {
        $query->where('is_active', $request->status === 'active');
    }
    
    $parts = $query->paginate(50)->withQueryString();
    
    return Inertia::render('parts/Index', [
        'parts' => $parts,
        'filters' => $request->only(['search', 'status']),
    ]);
}
```

## Available Slots

- `toolbar` - Custom toolbar above the table
- `empty` - Custom empty state message
- `footer` - Custom footer (typically used for pagination)

## Advanced Example with Toolbar

```vue
<DataTable :columns="columns" :data="props.parts.data">
    <template #toolbar="{ table }">
        <div class="flex items-center justify-between">
            <Input
                :model-value="table.getColumn('part_number')?.getFilterValue() as string"
                placeholder="Filter parts..."
                @input="table.getColumn('part_number')?.setFilterValue($event.target.value)"
            />
            <Button @click="table.resetColumnFilters()">
                Reset Filters
            </Button>
        </div>
    </template>
    
    <template #footer>
        <DataTablePagination :data="props.parts" />
    </template>
</DataTable>
```

## Why Server-Side Search?

Client-side filtering only searches within the current page's data. With pagination, you need server-side search to search across all records. This ensures users can find data regardless of which page it's on.
