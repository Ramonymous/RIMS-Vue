import { Link } from '@inertiajs/vue3';
import type { ColumnDef } from '@tanstack/vue-table';
import { Eye, CheckCircle, XCircle, Pencil } from 'lucide-vue-next';
import { h } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

export interface Outgoing {
    id: string;
    doc_number: string;
    issued_by: {
        id?: string;
        name: string;
    };
    status: 'completed' | 'draft' | 'cancelled';
    // is_gi removed
    items: any[];
    created_at: string;
    issued_at: string;
}

type ColumnContext = {
    onView: (outgoing: Outgoing) => void;
    onCancel: (outgoing: Outgoing) => void;
    canManageOutgoings: boolean;
};

const canEdit = (outgoing: Outgoing) => {
    return outgoing.status !== 'completed' && outgoing.status !== 'cancelled';
};

export const createColumns = (
    context: ColumnContext,
): ColumnDef<Outgoing>[] => [
    {
        accessorKey: 'doc_number',
        header: 'Document Number',
        cell: ({ row }) => {
            const docNumber = row.getValue('doc_number') as string;
            return h('div', { class: 'font-medium' }, docNumber);
        },
    },
    {
        accessorKey: 'issued_by',
        header: 'Issued By',
        cell: ({ row }) => {
            const issuedBy = row.getValue('issued_by') as { name: string };
            return h(
                'div',
                { class: 'text-sm text-muted-foreground' },
                issuedBy?.name || '-',
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
    // GI Status column removed
    {
        accessorKey: 'created_at',
        header: 'Created',
        cell: ({ row }) => {
            const date = row.getValue('created_at') as string;
            const formatted = new Date(date).toLocaleString('en-US', {
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
            const outgoing = row.original;
            const canEditItem = canEdit(outgoing);

            return h('div', { class: 'flex gap-2' }, [
                h(
                    Button,
                    {
                        variant: 'outline',
                        size: 'sm',
                        onClick: () => context.onView(outgoing),
                    },
                    () => h(Eye, { class: 'h-4 w-4' }),
                ),
                context.canManageOutgoings && canEditItem
                    ? h(
                          Button,
                          {
                              variant: 'outline',
                              size: 'sm',
                              asChild: true,
                          },
                          {
                              default: () =>
                                  h(
                                      Link,
                                      {
                                          href: `/outgoings/${outgoing.id}/edit`,
                                      },
                                      () => h(Pencil, { class: 'h-4 w-4' }),
                                  ),
                          },
                      )
                    : null,
                context.canManageOutgoings && outgoing.status !== 'cancelled'
                    ? h(
                          Button,
                          {
                              variant: 'outline',
                              size: 'sm',
                              onClick: () => context.onCancel(outgoing),
                          },
                          () => h(XCircle, { class: 'h-4 w-4' }),
                      )
                    : null,
            ]);
        },
    },
];
