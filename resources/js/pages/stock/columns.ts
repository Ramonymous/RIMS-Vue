import type { ColumnDef } from '@tanstack/vue-table';
import { h } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';

export type Part = {
    id: string;
    part_number: string;
    part_name: string;
    stock: number;
    is_active: boolean;
    created_at: string;
};

function getStockLevel(stock: number) {
    if (stock === 0) {
        return { variant: 'destructive' as const, label: 'Out of Stock' };
    } else if (stock < 10) {
        return { variant: 'secondary' as const, label: 'Low' };
    }
    return { variant: 'default' as const, label: 'Available' };
}

export const columns: ColumnDef<Part>[] = [
    {
        id: 'select',
        header: ({ table }) =>
            h(Checkbox, {
                modelValue: table.getIsAllPageRowsSelected(),
                'onUpdate:modelValue': (value: boolean | 'indeterminate') =>
                    table.toggleAllPageRowsSelected(!!value),
                ariaLabel: 'Select all',
            }),
        cell: ({ row }) =>
            h(Checkbox, {
                modelValue: row.getIsSelected(),
                'onUpdate:modelValue': (value: boolean | 'indeterminate') =>
                    row.toggleSelected(!!value),
                ariaLabel: 'Select row',
            }),
        enableSorting: false,
        enableHiding: false,
    },
    {
        accessorKey: 'part_number',
        header: 'Part Number',
        cell: ({ row }) =>
            h('span', { class: 'font-medium' }, row.getValue('part_number')),
        filterFn: (row, id, value) => {
            const partNumber = row.getValue(id) as string;
            const partName = row.getValue('part_name') as string;
            const searchValue = value.toLowerCase();
            return (
                partNumber.toLowerCase().includes(searchValue) ||
                partName.toLowerCase().includes(searchValue)
            );
        },
    },
    {
        accessorKey: 'part_name',
        header: 'Part Name',
    },
    {
        accessorKey: 'stock',
        header: () => h('div', { class: 'text-center' }, 'Stock'),
        cell: ({ row }) =>
            h(
                'div',
                { class: 'text-center font-semibold' },
                row.getValue('stock'),
            ),
    },
    {
        accessorKey: 'is_active',
        header: 'Status',
        cell: ({ row }) =>
            h(
                Badge,
                {
                    variant: row.getValue('is_active')
                        ? 'default'
                        : 'secondary',
                },
                () => (row.getValue('is_active') ? 'Active' : 'Inactive'),
            ),
    },
    {
        id: 'stock_level',
        header: 'Stock Level',
        cell: ({ row }) => {
            const stock = row.getValue('stock') as number;
            const level = getStockLevel(stock);
            return h(Badge, { variant: level.variant }, () => level.label);
        },
    },
];
