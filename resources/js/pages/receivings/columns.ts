import type { ColumnDef } from '@tanstack/vue-table';
import { Eye, CheckCircle, XCircle } from 'lucide-vue-next';
import { h } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

export interface Receiving {
    id: string;
    doc_number: string;
    received_by: {
        id: string;
        name: string;
    };
    status: 'completed' | 'draft' | 'cancelled';
    // is_gr removed
    items_count?: number;
    created_at: string;
    issued_at?: string;
    received_at: string;
    items: any[];
}

type ColumnContext = {
    onView: (receiving: Receiving) => void;
    onCancel: (receiving: Receiving) => void;
    canManageReceivings: boolean;
};

export const createColumns = (
    context: ColumnContext,
): ColumnDef<Receiving>[] => [
    {
        accessorKey: 'doc_number',
        header: 'Document Number',
        cell: ({ row }) => {
            const docNumber = row.getValue('doc_number') as string;
            return h('div', { class: 'font-medium' }, docNumber);
        },
    },
    {
        accessorKey: 'received_by',
        header: 'Received By',
        cell: ({ row }) => {
            const receivedBy = row.getValue('received_by') as { name: string };
            return h(
                'div',
                { class: 'text-sm text-muted-foreground' },
                receivedBy?.name || '-',
            );
        },
    },
    {
        accessorKey: 'status',
        header: 'Status',
        cell: ({ row }) => {
            const status = row.getValue('status') as string;
            const variant =
                status === 'completed'
                    ? 'default'
                    : status === 'draft'
                      ? 'secondary'
                      : 'destructive';
            return h(
                Badge,
                { variant },
                () => status.charAt(0).toUpperCase() + status.slice(1),
            );
        },
    },
    // GR Status column removed
    {
        accessorKey: 'created_at',
        header: 'Created',
        cell: ({ row }) => {
            const date = row.getValue('created_at') as string;
            const formatted = new Date(date).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
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
            const receiving = row.original;
            const canEdit =
                receiving.status !== 'completed' && receiving.status !== 'cancelled';

            return h('div', { class: 'flex gap-2' }, [
                h(
                    Button,
                    {
                        variant: 'outline',
                        size: 'sm',
                        onClick: () => context.onView(receiving),
                    },
                    () => h(Eye, { class: 'h-4 w-4' }),
                ),
                context.canManageReceivings && canEdit
                    ? h(
                          Button,
                          {
                              variant: 'outline',
                              size: 'sm',
                              onClick: () => context.onCancel(receiving),
                          },
                          () => h(XCircle, { class: 'h-4 w-4' }),
                      )
                    : null,
                // GR button removed
            ]);
        },
    },
];
