<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { UsersRound, Plus, Pencil, Trash2 } from 'lucide-vue-next';
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
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';

type PermissionKey = 'admin' | 'receiving' | 'outgoing' | 'manager';

type PermissionsState = {
    [K in PermissionKey]: boolean;
};

type User = {
    id: string;
    name: string;
    email: string;
    permissions: string[];
    created_at: string;
};

type Props = {
    users: {
        data: User[];
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
    {
        title: 'Users',
        href: '/users',
    },
];

const createDialogOpen = ref(false);
const editDialogOpen = ref(false);
const deleteDialogOpen = ref(false);
const selectedUser = ref<User | null>(null);

const createForm = useForm({
    name: '',
    email: '',
    password: '',
    permissions: [] as string[],
});

const editForm = useForm({
    name: '',
    email: '',
    password: '',
    permissions: [] as string[],
});

const availablePermissions = [
    { value: 'admin', label: 'Admin', description: 'Full system access' },
    { value: 'receiving', label: 'Receiving', description: 'Manage goods receipt' },
    { value: 'outgoing', label: 'Outgoing', description: 'Manage goods issue' },
    { value: 'manager', label: 'Manager', description: 'Manage parts and requests' },
];

const createPermissions = ref<PermissionsState>({
    admin: false,
    receiving: false,
    outgoing: false,
    manager: false,
});

const editPermissions = ref<PermissionsState>({
    admin: false,
    receiving: false,
    outgoing: false,
    manager: false,
});

function syncPermissionsToForm(permissions: typeof createPermissions, form: typeof createForm | typeof editForm) {
    form.permissions = Object.entries(permissions.value)
        .filter(([_, checked]) => checked)
        .map(([permission]) => permission);
}

function openEditDialog(user: User) {
    selectedUser.value = user;
    editForm.name = user.name;
    editForm.email = user.email;
    editForm.password = '';
    editForm.permissions = [...user.permissions];
    
    // Set permission checkboxes
    editPermissions.value = {
        admin: user.permissions.includes('admin'),
        receiving: user.permissions.includes('receiving'),
        outgoing: user.permissions.includes('outgoing'),
        manager: user.permissions.includes('manager'),
    };
    
    editDialogOpen.value = true;
}

function openDeleteDialog(user: User) {
    selectedUser.value = user;
    deleteDialogOpen.value = true;
}

function submitCreate() {
    syncPermissionsToForm(createPermissions, createForm);
    createForm.post('/users', {
        preserveScroll: true,
        onSuccess: () => {
            createDialogOpen.value = false;
            createForm.reset();
            // Reset permission checkboxes
            createPermissions.value = {
                admin: false,
                receiving: false,
                outgoing: false,
                manager: false,
            };
        },
    });
}

function submitEdit() {
    if (!selectedUser.value) return;

    syncPermissionsToForm(editPermissions, editForm);
    editForm.put(`/users/${selectedUser.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            editDialogOpen.value = false;
            editForm.reset();
            selectedUser.value = null;
        },
    });
}

function confirmDelete() {
    if (!selectedUser.value) return;

    router.delete(`/users/${selectedUser.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            deleteDialogOpen.value = false;
            selectedUser.value = null;
        },
    });
}

function formatDate(date: string) {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}
</script>

<template>
    <Head title="User Management" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <Card>
                <CardHeader class="flex flex-row items-center justify-between">
                    <div>
                        <CardTitle class="flex items-center gap-2">
                            <UsersRound class="h-5 w-5" />
                            User Management
                        </CardTitle>
                        <CardDescription>
                            Manage system users and their permissions
                        </CardDescription>
                    </div>

                    <Dialog v-model:open="createDialogOpen">
                        <DialogTrigger as-child>
                            <Button>
                                <Plus class="mr-2 h-4 w-4" />
                                Add User
                            </Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>Create New User</DialogTitle>
                                <DialogDescription>
                                    Add a new user to the system
                                </DialogDescription>
                            </DialogHeader>
                            <form @submit.prevent="submitCreate">
                                <div class="grid gap-4 py-4">
                                    <div class="grid gap-2">
                                        <Label for="create-name">Name</Label>
                                        <Input
                                            id="create-name"
                                            v-model="createForm.name"
                                            placeholder="John Doe"
                                            required
                                        />
                                        <span
                                            v-if="createForm.errors.name"
                                            class="text-sm text-destructive"
                                        >
                                            {{ createForm.errors.name }}
                                        </span>
                                    </div>
                                    <div class="grid gap-2">
                                        <Label for="create-email">Email</Label>
                                        <Input
                                            id="create-email"
                                            v-model="createForm.email"
                                            type="email"
                                            placeholder="john@example.com"
                                            required
                                        />
                                        <span
                                            v-if="createForm.errors.email"
                                            class="text-sm text-destructive"
                                        >
                                            {{ createForm.errors.email }}
                                        </span>
                                    </div>
                                    <div class="grid gap-2">
                                        <Label for="create-password">Password</Label>
                                        <Input
                                            id="create-password"
                                            v-model="createForm.password"
                                            type="password"
                                            placeholder="••••••••"
                                            required
                                        />
                                        <span
                                            v-if="createForm.errors.password"
                                            class="text-sm text-destructive"
                                        >
                                            {{ createForm.errors.password }}
                                        </span>
                                    </div>
                                    <div class="grid gap-3">
                                        <Label>Permissions</Label>
                                        <div class="space-y-2">
                                            <div
                                                v-for="permission in availablePermissions"
                                                :key="permission.value"
                                                class="flex items-start space-x-2"
                                            >
                                                <input
                                                    :id="`create-${permission.value}`"
                                                    v-model="createPermissions[permission.value as PermissionKey]"
                                                    type="checkbox"
                                                    class="mt-1 h-4 w-4 rounded border-gray-300 text-primary focus:ring-2 focus:ring-primary focus:ring-offset-2 cursor-pointer"
                                                />
                                                <div class="grid gap-1 leading-none">
                                                    <Label
                                                        :for="`create-${permission.value}`"
                                                        class="cursor-pointer font-medium"
                                                    >
                                                        {{ permission.label }}
                                                    </Label>
                                                    <p class="text-xs text-muted-foreground">
                                                        {{ permission.description }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <DialogFooter>
                                    <Button
                                        type="submit"
                                        :disabled="createForm.processing"
                                    >
                                        Create User
                                    </Button>
                                </DialogFooter>
                            </form>
                        </DialogContent>
                    </Dialog>
                </CardHeader>

                <CardContent>
                    <div class="space-y-4">
                        <div
                            v-if="props.users.data.length === 0"
                            class="py-8 text-center text-muted-foreground"
                        >
                            No users found
                        </div>

                        <div
                            v-for="user in props.users.data"
                            :key="user.id"
                            class="flex items-center justify-between rounded-lg border p-4"
                        >
                            <div class="flex-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h3 class="font-semibold">{{ user.name }}</h3>
                                    <Badge
                                        v-for="permission in user.permissions"
                                        :key="permission"
                                        :variant="permission === 'admin' ? 'default' : 'secondary'"
                                    >
                                        {{ permission.charAt(0).toUpperCase() + permission.slice(1) }}
                                    </Badge>
                                </div>
                                <p class="text-sm text-muted-foreground">
                                    {{ user.email }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    Created: {{ formatDate(user.created_at) }}
                                </p>
                            </div>

                            <div class="flex gap-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    @click="openEditDialog(user)"
                                >
                                    <Pencil class="h-4 w-4" />
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    @click="openDeleteDialog(user)"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div
                        v-if="props.users.last_page > 1"
                        class="mt-6 flex justify-center gap-2"
                    >
                        <Link
                            v-for="link in props.users.links"
                            :key="link.label"
                            :href="link.url || '#'"
                            :class="[
                                'rounded-md px-3 py-2 text-sm',
                                link.active
                                    ? 'bg-primary text-primary-foreground'
                                    : 'bg-muted hover:bg-muted/80',
                                !link.url && 'cursor-not-allowed opacity-50',
                            ]"
                            :disabled="!link.url"
                            v-html="link.label"
                        />
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Edit Dialog -->
        <Dialog v-model:open="editDialogOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Edit User</DialogTitle>
                    <DialogDescription>
                        Update user information
                    </DialogDescription>
                </DialogHeader>
                <form @submit.prevent="submitEdit">
                    <div class="grid gap-4 py-4">
                        <div class="grid gap-2">
                            <Label for="edit-name">Name</Label>
                            <Input
                                id="edit-name"
                                v-model="editForm.name"
                                required
                            />
                            <span
                                v-if="editForm.errors.name"
                                class="text-sm text-destructive"
                            >
                                {{ editForm.errors.name }}
                            </span>
                        </div>
                        <div class="grid gap-2">
                            <Label for="edit-email">Email</Label>
                            <Input
                                id="edit-email"
                                v-model="editForm.email"
                                type="email"
                                required
                            />
                            <span
                                v-if="editForm.errors.email"
                                class="text-sm text-destructive"
                            >
                                {{ editForm.errors.email }}
                            </span>
                        </div>
                        <div class="grid gap-2">
                            <Label for="edit-password">
                                Password
                                <span class="text-xs text-muted-foreground">
                                    (leave blank to keep current)
                                </span>
                            </Label>
                            <Input
                                id="edit-password"
                                v-model="editForm.password"
                                type="password"
                                placeholder="••••••••"
                            />
                            <span
                                v-if="editForm.errors.password"
                                class="text-sm text-destructive"
                            >
                                {{ editForm.errors.password }}
                            </span>
                        </div>
                        <div class="grid gap-3">
                            <Label>Permissions</Label>
                            <div class="space-y-2">
                                <div
                                    v-for="permission in availablePermissions"
                                    :key="permission.value"
                                    class="flex items-start space-x-2"
                                >
                                    <input
                                        :id="`edit-${permission.value}`"
                                        v-model="editPermissions[permission.value as PermissionKey]"
                                        type="checkbox"
                                        class="mt-1 h-4 w-4 rounded border-gray-300 text-primary focus:ring-2 focus:ring-primary focus:ring-offset-2 cursor-pointer"
                                    />
                                    <div class="grid gap-1 leading-none">
                                        <Label
                                            :for="`edit-${permission.value}`"
                                            class="cursor-pointer font-medium"
                                        >
                                            {{ permission.label }}
                                        </Label>
                                        <p class="text-xs text-muted-foreground">
                                            {{ permission.description }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <DialogFooter>
                        <Button type="submit" :disabled="editForm.processing">
                            Update User
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Delete Confirmation Dialog -->
        <Dialog v-model:open="deleteDialogOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Are you sure?</DialogTitle>
                    <DialogDescription>
                        This will permanently delete the user "{{
                            selectedUser?.name
                        }}". This action cannot be undone.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="deleteDialogOpen = false">
                        Cancel
                    </Button>
                    <Button variant="destructive" @click="confirmDelete">
                        Delete
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
