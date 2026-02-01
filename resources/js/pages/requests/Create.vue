<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import {
    FileInput,
    Plus,
    Trash2,
    AlertTriangle,
    Search,
    QrCode,
} from 'lucide-vue-next';
import { computed, nextTick, ref } from 'vue';
import QRScanner from '@/components/QRScanner.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { store } from '@/routes/requests';
import { index as requestsIndex } from '@/routes/requests';
import { type BreadcrumbItem } from '@/types';

type Part = {
    id: string;
    part_number: string;
    part_name: string;
    stock: number;
};

type RequestItem = {
    id: string;
    part_id: string;
    qty: number;
    is_urgent: boolean;
    isHighlighted?: boolean;
};

type Props = {
    parts: Part[];
    nextRequestNumber: number;
    pendingRequests: Record<string, string[]>;
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Requests', href: '/requests' },
    { title: 'Create', href: '/requests/create' },
];

const form = useForm({
    requested_at: new Date().toISOString().slice(0, 16),
    destination: '',
    status: 'draft' as 'draft' | 'completed',
    notes: '',
    items: [] as RequestItem[],
});

const duplicateAlert = ref<string | null>(null);
const alreadyRequestedAlert = ref<string | null>(null);
const itemRefs = ref<{ [key: string]: HTMLElement }>({});
const partSearchQueries = ref<{ [key: string]: string }>({});
const qrScannerActive = ref(false);
const activeItemIndex = ref<number | null>(null);

const destinations = ['Line KS', 'Line SU2ID'];

function getFilteredPartsForItem(itemId: string) {
    const searchQuery = partSearchQueries.value[itemId]?.toLowerCase() || '';
    if (!searchQuery) return props.parts;

    return props.parts.filter(
        (part) =>
            part.part_number.toLowerCase().includes(searchQuery) ||
            part.part_name.toLowerCase().includes(searchQuery),
    );
}

function addItem() {
    const newId = crypto.randomUUID();
    form.items.push({
        id: newId,
        part_id: '',
        qty: 1,
        is_urgent: false,
    });
    partSearchQueries.value[newId] = '';
}

function removeItem(index: number) {
    const itemId = form.items[index].id;
    form.items.splice(index, 1);
    delete itemRefs.value[`item-${itemId}`];
    delete partSearchQueries.value[itemId];
    duplicateAlert.value = null;
}

function openQRScanner(index: number) {
    activeItemIndex.value = index;
    qrScannerActive.value = true;
}

function closeQRScanner() {
    qrScannerActive.value = false;
    activeItemIndex.value = null;
}

function handleQRScanned(scannedValue: string) {
    if (activeItemIndex.value === null) return;

    // Find part by part_number
    const part = props.parts.find((p) => p.part_number === scannedValue);

    if (part) {
        form.items[activeItemIndex.value].part_id = part.id;
        checkDuplicatePart(activeItemIndex.value, part.id);
    } else {
        duplicateAlert.value = `Part "${scannedValue}" not found in the system!`;
    }

    closeQRScanner();
}

function checkDuplicatePart(index: number, partId: any) {
    if (!partId) return;

    const partIdStr = String(partId);
    const duplicateIndex = form.items.findIndex(
        (item, idx) => idx !== index && item.part_id === partIdStr,
    );

    if (duplicateIndex !== -1) {
        const part = props.parts.find((p) => p.id === partIdStr);
        duplicateAlert.value = `Part "${part?.part_number}" is already added in this request!`;

        form.items[index].isHighlighted = true;
        form.items[duplicateIndex].isHighlighted = true;

        nextTick(() => {
            const elementKey = `item-${form.items[duplicateIndex].id}`;
            if (itemRefs.value[elementKey]) {
                itemRefs.value[elementKey].scrollIntoView({
                    behavior: 'smooth',
                    block: 'center',
                });
            }
        });
    } else {
        form.items[index].isHighlighted = false;

        const hasDuplicates = form.items.some(
            (item, idx) =>
                form.items.findIndex(
                    (i, iIdx) =>
                        iIdx !== idx && i.part_id === item.part_id && i.part_id,
                ) !== -1,
        );

        if (!hasDuplicates) {
            duplicateAlert.value = null;
            form.items.forEach((item) => (item.isHighlighted = false));
        }
    }

    // Check if part is already requested to the current destination
    checkAlreadyRequested(index, partIdStr);
}

function checkAlreadyRequested(index: number, partId: string) {
    if (!form.destination || !partId) {
        alreadyRequestedAlert.value = null;
        return;
    }

    const part = props.parts.find((p) => p.id === partId);
    if (!part) return;

    const pendingParts = props.pendingRequests[form.destination] || [];

    if (pendingParts.includes(part.part_number)) {
        alreadyRequestedAlert.value = `Part "${part.part_number}" is already requested to ${form.destination} and pending supply!`;
        form.items[index].part_id = ''; // Clear the selection
    } else {
        alreadyRequestedAlert.value = null;
    }
}

function checkAllItemsForDestination() {
    if (!form.destination) return;

    // Check all current items against new destination
    form.items.forEach((item, index) => {
        if (item.part_id) {
            checkAlreadyRequested(index, item.part_id);
        }
    });
}

function submitForm() {
    // Always submit as completed (internal status, not shown to user)
    const transformedData = {
        requested_at: form.requested_at,
        destination: form.destination,
        status: 'completed',
        notes: form.notes,
        items: form.items.map((item: RequestItem) => ({
            part_id: item.part_id,
            qty: 1,
            is_urgent: Boolean(item.is_urgent),
            is_supplied: false,
        })),
    };

    form.transform(() => transformedData).post(store.url(), {
        preserveScroll: true,
        headers: {
            Accept: 'application/json',
        },
        onSuccess: (response: any) => {
            // Response includes message and request_number in data
            const message =
                response.props?.flash?.message ||
                response.props?.jetstream?.flash?.message ||
                'Request confirmed successfully.';

            alert(message);

            // Reset form
            form.reset();
            form.items = [];
            form.requested_at = new Date().toISOString().slice(0, 16);
            duplicateAlert.value = null;
            alreadyRequestedAlert.value = null;
            partSearchQueries.value = {};
        },
        onError: (errors: any) => {
            const errorMessage =
                Object.values(errors).flat().join(', ') ||
                'Failed to create request. Please check the form and try again.';
            alert(errorMessage);
        },
    });
}

const isFormValid = computed(() => {
    return (
        form.requested_at &&
        form.destination &&
        form.items.length > 0 &&
        form.items.every((item) => item.part_id) &&
        !duplicateAlert.value &&
        !alreadyRequestedAlert.value
    );
});
</script>

<template>
    <Head title="Create Request" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <FileInput class="h-5 w-5" />
                        Create New Request
                    </CardTitle>
                    <CardDescription
                        >Request parts for your destination (no stock
                        changes)</CardDescription
                    >
                </CardHeader>

                <CardContent class="space-y-6">
                    <!-- Alert for duplicates -->
                    <Alert v-if="duplicateAlert" variant="destructive">
                        <AlertTriangle class="h-4 w-4" />
                        <AlertDescription>{{
                            duplicateAlert
                        }}</AlertDescription>
                    </Alert>

                    <!-- Alert for already requested items -->
                    <Alert v-if="alreadyRequestedAlert" variant="destructive">
                        <AlertTriangle class="h-4 w-4" />
                        <AlertDescription>{{
                            alreadyRequestedAlert
                        }}</AlertDescription>
                    </Alert>

                    <!-- Backend validation errors -->
                    <Alert
                        v-if="Object.keys(form.errors).length > 0"
                        variant="destructive"
                    >
                        <AlertTriangle class="h-4 w-4" />
                        <AlertDescription>
                            <div class="space-y-1">
                                <p
                                    v-for="(error, key) in form.errors"
                                    :key="key"
                                    class="text-sm"
                                >
                                    {{ error }}
                                </p>
                            </div>
                        </AlertDescription>
                    </Alert>

                    <!-- Request Details -->
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="requested_at">Request Date*</Label>
                            <Input
                                id="requested_at"
                                v-model="form.requested_at"
                                type="datetime-local"
                                :class="{
                                    'border-red-500': form.errors.requested_at,
                                }"
                            />
                            <p
                                v-if="form.errors.requested_at"
                                class="text-sm text-red-500"
                            >
                                {{ form.errors.requested_at }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label>Destination*</Label>
                            <Select
                                v-model="form.destination"
                                @update:model-value="
                                    checkAllItemsForDestination
                                "
                            >
                                <SelectTrigger
                                    :class="{
                                        'border-red-500':
                                            form.errors.destination,
                                    }"
                                >
                                    <SelectValue
                                        placeholder="Select destination"
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="dest in destinations"
                                        :key="dest"
                                        :value="dest"
                                    >
                                        {{ dest }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p
                                v-if="form.errors.destination"
                                class="text-sm text-red-500"
                            >
                                {{ form.errors.destination }}
                            </p>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="notes">Notes</Label>
                        <Textarea
                            id="notes"
                            v-model="form.notes"
                            placeholder="Additional notes..."
                            rows="3"
                        />
                    </div>

                    <!-- Items Section -->
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <Label>Items*</Label>
                            <Button type="button" size="sm" @click="addItem">
                                <Plus class="mr-2 h-4 w-4" />
                                Add Item
                            </Button>
                        </div>

                        <div
                            v-if="form.items.length === 0"
                            class="rounded-lg border-2 border-dashed p-8 text-center"
                        >
                            <p class="text-muted-foreground">
                                No items added yet. Click "Add Item" to start.
                            </p>
                        </div>

                        <div v-else class="space-y-3">
                            <div
                                v-for="(item, index) in form.items"
                                :key="item.id"
                                :ref="
                                    (el) =>
                                        (itemRefs[`item-${item.id}`] =
                                            el as HTMLElement)
                                "
                                class="rounded-lg border p-4"
                                :class="{
                                    'border-red-500 bg-red-50 dark:bg-red-950/20':
                                        item.isHighlighted,
                                }"
                            >
                                <div class="flex gap-4">
                                    <div class="flex-1 space-y-2">
                                        <div
                                            class="flex items-center justify-between"
                                        >
                                            <Label>Part*</Label>
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="sm"
                                                class="h-7"
                                                @click="openQRScanner(index)"
                                            >
                                                <QrCode class="mr-1 h-3 w-3" />
                                                Scan QR
                                            </Button>
                                        </div>
                                        <Select
                                            v-model="item.part_id"
                                            @update:model-value="
                                                checkDuplicatePart(
                                                    index,
                                                    $event,
                                                )
                                            "
                                        >
                                            <SelectTrigger>
                                                <SelectValue
                                                    placeholder="Select part"
                                                />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <div
                                                    class="sticky top-0 z-10 bg-popover p-2 pb-1"
                                                >
                                                    <div class="relative">
                                                        <Search
                                                            class="absolute top-2.5 left-2 h-4 w-4 text-muted-foreground"
                                                        />
                                                        <Input
                                                            v-model="
                                                                partSearchQueries[
                                                                    item.id
                                                                ]
                                                            "
                                                            placeholder="Search parts..."
                                                            class="h-9 pl-8"
                                                            @keydown.enter.prevent
                                                        />
                                                    </div>
                                                </div>
                                                <div
                                                    class="max-h-60 overflow-y-auto"
                                                >
                                                    <SelectItem
                                                        v-for="part in getFilteredPartsForItem(
                                                            item.id,
                                                        )"
                                                        :key="part.id"
                                                        :value="part.id"
                                                    >
                                                        {{ part.part_number }} -
                                                        {{ part.part_name }}
                                                        <span
                                                            class="text-muted-foreground"
                                                        >
                                                            (Stock:
                                                            {{ part.stock }})
                                                        </span>
                                                    </SelectItem>
                                                    <div
                                                        v-if="
                                                            getFilteredPartsForItem(
                                                                item.id,
                                                            ).length === 0
                                                        "
                                                        class="py-6 text-center text-sm text-muted-foreground"
                                                    >
                                                        No parts found
                                                    </div>
                                                </div>
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <div
                                        class="flex items-center space-x-2 pt-7"
                                    >
                                        <input
                                            :id="`urgent-${item.id}`"
                                            v-model="item.is_urgent"
                                            type="checkbox"
                                            class="h-4 w-4 cursor-pointer rounded border-gray-300 text-primary focus:ring-2 focus:ring-primary focus:ring-offset-2"
                                        />
                                        <Label
                                            :for="`urgent-${item.id}`"
                                            class="cursor-pointer text-sm leading-none font-medium"
                                        >
                                            Urgent
                                        </Label>
                                    </div>

                                    <div class="flex items-end">
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="icon"
                                            @click="removeItem(index)"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <p
                            v-if="form.errors.items"
                            class="text-sm text-red-500"
                        >
                            {{ form.errors.items }}
                        </p>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-3 pt-4">
                        <Button
                            type="button"
                            variant="outline"
                            @click="router.visit(requestsIndex.url())"
                        >
                            Cancel
                        </Button>
                        <Button
                            type="button"
                            :disabled="!isFormValid || form.processing"
                            @click="submitForm()"
                        >
                            Submit Request
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- QR Scanner Modal -->
        <QRScanner
            v-if="qrScannerActive"
            :is-active="qrScannerActive"
            @scanned="handleQRScanned"
            @close="closeQRScanner"
        />
    </AppLayout>
</template>
