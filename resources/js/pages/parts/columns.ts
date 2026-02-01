import type { ColumnDef } from '@tanstack/vue-table';
import { Pencil, Trash2 } from 'lucide-vue-next';
import { h } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

export interface Part {
    id: string;
    part_number: string;
    part_name: string;
    customer_code?: string;
    supplier_code?: string;
    model?: string;
    variant?: string;
    stock: number;
    standard_packing?: number;
    address?: string;
    is_active: boolean;
    created_at: string;
}

type ColumnContext = {
    onEdit: (part: Part) => void;
    onDelete: (part: Part) => void;
};

export const createColumns = (context: ColumnContext): ColumnDef<Part>[] => [
    {
        accessorKey: 'part_number',
        header: 'Part Number',
        cell: ({ row }) => {
            const partNumber = row.getValue('part_number') as string;
            return h('div', { class: 'font-medium' }, partNumber);
        },
    },
    {
        accessorKey: 'part_name',
        header: 'Part Name',
        cell: ({ row }) => {
            const partName = row.getValue('part_name') as string;
            return h(
                'div',
                { class: 'text-sm text-muted-foreground' },
                partName,
            );
        },
    },
    {
        accessorKey: 'stock',
        header: 'Stock',
        cell: ({ row }) => {
            const stock = row.getValue('stock') as number;
            return h(
                'div',
                { class: 'text-right font-medium' },
                stock.toLocaleString(),
            );
        },
    },
    {
        accessorKey: 'is_active',
        header: 'Status',
        cell: ({ row }) => {
            const isActive = row.getValue('is_active') as boolean;
            return h(
                Badge,
                { variant: isActive ? 'default' : 'secondary' },
                () => (isActive ? 'Active' : 'Inactive'),
            );
        },
    },
    {
        accessorKey: 'created_at',
        header: 'Created',
        cell: ({ row }) => {
            const date = row.getValue('created_at') as string;
            const formatted = new Date(date).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
            });
            return h(
                'div',
                { class: 'text-sm text-muted-foreground' },
                formatted,
            );
        },
    },
    {
        id: 'actions',
        header: 'Actions',
        cell: ({ row }) => {
            const part = row.original;

            return h('div', { class: 'flex gap-2' }, [
                h(
                    Button,
                    {
                        variant: 'outline',
                        size: 'sm',
                        onClick: () => context.onEdit(part),
                    },
                    () => h(Pencil, { class: 'h-4 w-4' }),
                ),
                h(
                    Button,
                    {
                        variant: 'outline',
                        size: 'sm',
                        onClick: () => context.onDelete(part),
                    },
                    () => h(Trash2, { class: 'h-4 w-4' }),
                ),
            ]);
        },
    },
];
