import type { ColumnDef } from '@tanstack/vue-table';
import { h } from 'vue';
import { Badge } from '@/components/ui/badge';

export interface User {
    id: string;
    name: string;
    email: string;
    permissions: string[];
    created_at: string;
}

export const columns: ColumnDef<User>[] = [
    {
        accessorKey: 'name',
        header: 'Name',
        cell: ({ row }) => {
            const name = row.getValue('name') as string;
            return h('div', { class: 'font-medium' }, name);
        },
    },
    {
        accessorKey: 'email',
        header: 'Email',
        cell: ({ row }) => {
            const email = row.getValue('email') as string;
            return h('div', { class: 'text-sm text-muted-foreground' }, email);
        },
    },
    {
        accessorKey: 'permissions',
        header: 'Permissions',
        cell: ({ row }) => {
            const permissions = row.getValue('permissions') as string[];
            return h(
                'div',
                { class: 'flex flex-wrap gap-1' },
                permissions.map((perm) =>
                    h(
                        Badge,
                        {
                            key: perm,
                            variant: perm === 'admin' ? 'default' : 'secondary',
                            class: 'text-xs',
                        },
                        () => perm.charAt(0).toUpperCase() + perm.slice(1),
                    ),
                ),
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
];
