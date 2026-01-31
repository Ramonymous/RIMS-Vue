<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { PackageMinus, PackageOpen, Pencil, XCircle, CheckCircle, Search, Eye } from 'lucide-vue-next';
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
import { Badge } from '@/components/ui/badge';
import { useOutgoings, type Outgoing } from '@/composables/useOutgoings';
import { usePermissions } from '@/composables/usePermissions';

type Props = {
    outgoings: {
        data: Outgoing[];
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
    { title: 'Outgoings', href: '/outgoings' },
];

const outgoingsData = computed(() => props.outgoings.data);

const {
    cancelDialog,
    giDialog,
    viewDialog,
    filter,
    cancel,
    updateGiStatus,
    getStatusVariant,
    formatDate,
    canEdit,
} = useOutgoings(outgoingsData);

const { canManageOutgoings, canConfirmGI } = usePermissions();
</script>

<template>
    <Head title="Outgoings Management" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <Card>
                <CardHeader class="flex flex-row items-center justify-between">
                    <div>
                        <CardTitle class="flex items-center gap-2">
                            <PackageOpen class="h-5 w-5" />
                            Outgoings Management
                        </CardTitle>
                        <CardDescription>
                            Manage outgoing parts and stock issues
                        </CardDescription>
                    </div>

                    <Button v-if="canManageOutgoings" as-child>
                        <Link href="/outgoings/create">
                            <PackageMinus class="mr-2 h-4 w-4" />
                            New Outgoing
                        </Link>
                    </Button>
                </CardHeader>

                <CardContent>
                    <!-- Search Bar -->
                    <div class="mb-4">
                        <div class="relative">
                            <Search
                                class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                            />
                            <Input
                                v-model="filter.searchQuery.value"
                                placeholder="Search by document number or issued by..."
                                class="pl-9"
                            />
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div
                            v-if="filter.filteredData.value.length === 0"
                            class="py-8 text-center text-muted-foreground"
                        >
                            {{
                                filter.searchQuery.value
                                    ? 'No outgoings found matching your search'
                                    : 'No outgoings found'
                            }}
                        </div>

                        <div
                            v-for="outgoing in filter.filteredData.value"
                            :key="outgoing.id"
                            class="flex items-start justify-between rounded-lg border p-4"
                        >
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <h3 class="font-semibold">
                                        {{ outgoing.doc_number }}
                                    </h3>
                                    <Badge :variant="getStatusVariant(outgoing.status) as 'default' | 'destructive' | 'outline' | 'secondary'">
                                        {{ outgoing.status }}
                                    </Badge>
                                    <Badge v-if="outgoing.is_gi" variant="default">
                                        GI Confirmed
                                    </Badge>
                                </div>
                                <div
                                    class="mt-2 grid grid-cols-2 gap-x-4 gap-y-1 text-xs text-muted-foreground md:grid-cols-4"
                                >
                                    <div>
                                        <span class="font-medium">Items:</span>
                                        {{ outgoing.items.length }}
                                    </div>
                                    <div>
                                        <span class="font-medium">Issued By:</span>
                                        {{ outgoing.issued_by.name }}
                                    </div>
                                    <div>
                                        <span class="font-medium">Issued At:</span>
                                        {{ formatDate(outgoing.issued_at) }}
                                    </div>
                                    <div>
                                        <span class="font-medium">Created:</span>
                                        {{ formatDate(outgoing.created_at) }}
                                    </div>
                                </div>
                            </div>

                            <div class="flex gap-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    @click="viewDialog.open(outgoing)"
                                >
                                    <Eye class="h-4 w-4" />
                                </Button>
                                <Button
                                    v-if="canManageOutgoings && canEdit(outgoing)"
                                    variant="outline"
                                    size="sm"
                                    as-child
                                >
                                    <Link :href="`/outgoings/${outgoing.id}/edit`">
                                        <Pencil class="h-4 w-4" />
                                    </Link>
                                </Button>
                                <Button
                                    v-if="canConfirmGI"
                                    variant="outline"
                                    size="sm"
                                    @click="giDialog.open(outgoing)"
                                >
                                    <CheckCircle class="h-4 w-4" />
                                </Button>
                                <Button
                                    v-if="canManageOutgoings && outgoing.status !== 'cancelled'"
                                    variant="outline"
                                    size="sm"
                                    @click="cancelDialog.open(outgoing)"
                                >
                                    <XCircle class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div
                        v-if="props.outgoings.last_page > 1"
                        class="mt-6 flex justify-center gap-2"
                    >
                        <Link
                            v-for="link in props.outgoings.links"
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

        <!-- Cancel Confirmation Dialog -->
        <Dialog v-model:open="cancelDialog.isOpen.value">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Cancel Outgoing?</DialogTitle>
                    <DialogDescription>
                        This will cancel the outgoing "{{
                            cancelDialog.data.value?.doc_number
                        }}". Stock changes will be reversed if applicable.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="cancelDialog.close()">
                        Close
                    </Button>
                    <Button 
                        variant="destructive" 
                        @click="cancelDialog.data.value && cancel(cancelDialog.data.value)"
                    >
                        Cancel Outgoing
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- View Items Dialog -->
        <Dialog v-model:open="viewDialog.isOpen.value">
            <DialogContent class="max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Outgoing Details</DialogTitle>
                    <DialogDescription>
                        {{ viewDialog.data.value?.doc_number }}
                    </DialogDescription>
                </DialogHeader>
                <div v-if="viewDialog.data.value" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="font-medium">Status:</span>
                            <Badge :variant="getStatusVariant(viewDialog.data.value.status) as 'default' | 'destructive' | 'outline' | 'secondary'" class="ml-2">
                                {{ viewDialog.data.value.status }}
                            </Badge>
                            <Badge v-if="viewDialog.data.value.is_gi" variant="default" class="ml-2">
                                GI Confirmed
                            </Badge>
                        </div>
                        <div>
                            <span class="font-medium">Issued By:</span>
                            {{ viewDialog.data.value.issued_by.name }}
                        </div>
                        <div>
                            <span class="font-medium">Issued At:</span>
                            {{ formatDate(viewDialog.data.value.issued_at) }}
                        </div>
                    </div>

                    <div class="border-t pt-4">
                        <h4 class="mb-3 font-medium">Items ({{ viewDialog.data.value.items.length }})</h4>
                        <div class="space-y-2">
                            <div
                                v-for="(item, index) in viewDialog.data.value.items"
                                :key="index"
                                class="rounded-lg border p-3"
                            >
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <p class="font-medium">{{ item.part.part_number }}</p>
                                        <p class="text-sm text-muted-foreground">
                                            {{ item.part.part_name }}
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-medium">Qty: {{ item.qty }}</p>
                                        <p class="text-xs text-muted-foreground">
                                            Stock: {{ item.part.stock }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="viewDialog.close()">
                        Close
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- GI Status Dialog -->
        <Dialog v-model:open="giDialog.isOpen.value">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Update GI Status?</DialogTitle>
                    <DialogDescription>
                        This will {{ giDialog.data.value?.is_gi ? 'unconfirm' : 'confirm' }} GI for outgoing "{{
                            giDialog.data.value?.doc_number
                        }}".
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="giDialog.close()">
                        Close
                    </Button>
                    <Button @click="giDialog.data.value && updateGiStatus(giDialog.data.value)">
                        {{ giDialog.data.value?.is_gi ? 'Unconfirm' : 'Confirm' }} GI
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
