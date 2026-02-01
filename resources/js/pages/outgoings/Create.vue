<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    PackageMinus,
    Plus,
    Trash2,
    AlertTriangle,
    Search,
} from 'lucide-vue-next';
import { nextTick, ref, watch } from 'vue';
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
import { type BreadcrumbItem } from '@/types';

type Part = {
    id: string;
    part_number: string;
    part_name: string;
    stock: number;
};

type OutgoingItem = {
    id: string;
    part_id: string;
    qty: number;
    isHighlighted?: boolean;
};

type Props = {
    parts: Part[];
    nextDocNumber: string;
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Outgoings', href: '/outgoings' },
    { title: 'Create', href: '/outgoings/create' },
];

const form = useForm({
    doc_number: props.nextDocNumber,
    issued_at: new Date().toISOString().slice(0, 16),
    status: 'draft' as 'draft' | 'completed',
    notes: '',
    items: [] as OutgoingItem[],
});

const duplicateAlert = ref<string | null>(null);
const insufficientStockAlert = ref<string | null>(null);
const itemRefs = ref<{ [key: string]: HTMLElement }>({});
const partSearchQueries = ref<{ [key: string]: string }>({});

const getFilteredPartsForItem = (itemId: string) => {
    const searchQuery = partSearchQueries.value[itemId]?.toLowerCase() || '';
    if (!searchQuery) return props.parts;

    return props.parts.filter(
        (part) =>
            part.part_number.toLowerCase().includes(searchQuery) ||
            part.part_name.toLowerCase().includes(searchQuery),
    );
};

function addItem() {
    const newId = crypto.randomUUID();
    form.items.push({
        id: newId,
        part_id: '',
        qty: 1,
    });
    partSearchQueries.value[newId] = '';
}

function removeItem(index: number) {
    const itemId = form.items[index].id;
    form.items.splice(index, 1);
    delete partSearchQueries.value[itemId];
    duplicateAlert.value = null;
    checkStockForAllItems();
}

function checkDuplicatePart(index: number, partId: any) {
    if (!partId) return;

    const partIdStr = String(partId);
    const duplicateIndex = form.items.findIndex(
        (item, idx) => idx !== index && item.part_id === partIdStr,
    );

    if (duplicateIndex !== -1) {
        const part = props.parts.find((p) => p.id === partIdStr);
        duplicateAlert.value = `Part "${part?.part_number}" is already added in this outgoing!`;

        // Highlight both items
        form.items[index].isHighlighted = true;
        form.items[duplicateIndex].isHighlighted = true;

        // Focus on the duplicate item
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
        // Remove highlight
        form.items[index].isHighlighted = false;

        // Check if any duplicates remain
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

    checkStockForAllItems();
}

function checkStockForAllItems() {
    if (form.status !== 'completed') {
        insufficientStockAlert.value = null;
        return;
    }

    const stockCheck: { [key: string]: number } = {};

    for (const item of form.items) {
        if (!item.part_id) continue;

        stockCheck[item.part_id] = (stockCheck[item.part_id] || 0) + item.qty;
    }

    for (const [partId, totalQty] of Object.entries(stockCheck)) {
        const part = props.parts.find((p) => p.id === partId);
        if (part && totalQty > part.stock) {
            insufficientStockAlert.value = `Insufficient stock for "${part.part_number}". Available: ${part.stock}, Required: ${totalQty}`;
            return;
        }
    }

    insufficientStockAlert.value = null;
}

function getPartInfo(partId: string): Part | undefined {
    return props.parts.find((p) => p.id === partId);
}

function getTotalQtyForPart(partId: string): number {
    return form.items
        .filter((item) => item.part_id === partId)
        .reduce((sum, item) => sum + item.qty, 0);
}

function saveDraft() {
    form.status = 'draft';
    submitForm();
}

function confirm() {
    form.status = 'completed';
    checkStockForAllItems();
    if (insufficientStockAlert.value) return;
    submitForm();
}

function submitForm() {
    if (duplicateAlert.value) {
        return;
    }

    form.post('/outgoings', {
        preserveScroll: true,
    });
}

function setItemRef(id: string, el: any) {
    if (el) {
        itemRefs.value[`item-${id}`] = el;
    }
}

// Watch for quantity changes
watch(
    () => form.items.map((i) => i.qty),
    () => {
        checkStockForAllItems();
    },
    { deep: true },
);

// Watch for status changes
watch(
    () => form.status,
    () => {
        checkStockForAllItems();
    },
);
</script>

<template>
    <Head title="Create Outgoing" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <PackageMinus class="h-5 w-5" />
                        Create Outgoing
                    </CardTitle>
                    <CardDescription>
                        Create a new outgoing document for parts issue
                    </CardDescription>
                </CardHeader>

                <CardContent class="space-y-6">
                    <!-- Header Section -->
                    <div class="grid gap-4 rounded-lg border p-4">
                        <h3 class="font-semibold">Document Information</h3>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="doc-number">
                                    Document Number
                                    <span class="text-destructive">*</span>
                                </Label>
                                <Input
                                    id="doc-number"
                                    v-model="form.doc_number"
                                    placeholder="OUT-310126-001"
                                    readonly
                                    class="bg-muted"
                                />
                                <span
                                    v-if="form.errors.doc_number"
                                    class="text-sm text-destructive"
                                >
                                    {{ form.errors.doc_number }}
                                </span>
                            </div>

                            <div class="grid gap-2">
                                <Label for="issued-at">
                                    Issue Date
                                    <span class="text-destructive">*</span>
                                </Label>
                                <Input
                                    id="issued-at"
                                    v-model="form.issued_at"
                                    type="datetime-local"
                                    required
                                />
                                <span
                                    v-if="form.errors.issued_at"
                                    class="text-sm text-destructive"
                                >
                                    {{ form.errors.issued_at }}
                                </span>
                            </div>
                        </div>

                        <div class="grid gap-2">
                            <Label for="notes">Notes</Label>
                            <Textarea
                                id="notes"
                                v-model="form.notes"
                                placeholder="Additional notes..."
                                rows="3"
                            />
                        </div>
                    </div>

                    <!-- Items Section -->
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="font-semibold">Outgoing Items</h3>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                @click="addItem"
                            >
                                <Plus class="mr-2 h-4 w-4" />
                                Add Item
                            </Button>
                        </div>

                        <Alert v-if="duplicateAlert" variant="destructive">
                            <AlertTriangle class="h-4 w-4" />
                            <AlertDescription>
                                {{ duplicateAlert }}
                            </AlertDescription>
                        </Alert>

                        <Alert
                            v-if="insufficientStockAlert"
                            variant="destructive"
                        >
                            <AlertTriangle class="h-4 w-4" />
                            <AlertDescription>
                                {{ insufficientStockAlert }}
                            </AlertDescription>
                        </Alert>

                        <span
                            v-if="form.errors.items"
                            class="text-sm text-destructive"
                        >
                            {{ form.errors.items }}
                        </span>

                        <div
                            v-if="form.items.length === 0"
                            class="rounded-lg border border-dashed p-8 text-center text-muted-foreground"
                        >
                            No items added yet. Click "Add Item" to start.
                        </div>

                        <div
                            v-for="(item, index) in form.items"
                            :key="item.id"
                            :ref="(el) => setItemRef(item.id, el)"
                            class="grid gap-4 rounded-lg border p-4 transition-colors"
                            :class="{
                                'border-yellow-500 bg-yellow-50 dark:bg-yellow-950':
                                    item.isHighlighted,
                            }"
                        >
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium"
                                    >Item #{{ index + 1 }}</span
                                >
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    @click="removeItem(index)"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </Button>
                            </div>

                            <div class="grid gap-4 md:grid-cols-3">
                                <div class="grid gap-2 md:col-span-2">
                                    <Label :for="`part-${item.id}`">
                                        Part
                                        <span class="text-destructive">*</span>
                                    </Label>
                                    <Select
                                        v-model="item.part_id"
                                        @update:model-value="
                                            checkDuplicatePart(index, $event)
                                        "
                                        required
                                    >
                                        <SelectTrigger :id="`part-${item.id}`">
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
                                    <span
                                        v-if="
                                            form.errors[
                                                `items.${index}.part_id`
                                            ]
                                        "
                                        class="text-sm text-destructive"
                                    >
                                        {{
                                            form.errors[
                                                `items.${index}.part_id`
                                            ]
                                        }}
                                    </span>
                                </div>

                                <div class="grid gap-2">
                                    <Label :for="`qty-${item.id}`">
                                        Quantity
                                        <span class="text-destructive">*</span>
                                    </Label>
                                    <Input
                                        :id="`qty-${item.id}`"
                                        v-model.number="item.qty"
                                        type="number"
                                        min="1"
                                        required
                                    />
                                    <span
                                        v-if="form.errors[`items.${index}.qty`]"
                                        class="text-sm text-destructive"
                                    >
                                        {{ form.errors[`items.${index}.qty`] }}
                                    </span>
                                </div>
                            </div>

                            <div
                                v-if="item.part_id && getPartInfo(item.part_id)"
                                class="rounded-md p-3 text-sm"
                                :class="[
                                    getTotalQtyForPart(item.part_id) >
                                    (getPartInfo(item.part_id)?.stock || 0)
                                        ? 'border border-destructive bg-destructive/10'
                                        : 'bg-muted',
                                ]"
                            >
                                <div class="grid gap-1">
                                    <div>
                                        <span class="font-medium"
                                            >Current Stock:</span
                                        >
                                        {{ getPartInfo(item.part_id)?.stock }}
                                    </div>
                                    <div>
                                        <span class="font-medium"
                                            >After Issue:</span
                                        >
                                        {{
                                            (getPartInfo(item.part_id)?.stock ||
                                                0) -
                                            getTotalQtyForPart(item.part_id)
                                        }}
                                        <span
                                            v-if="
                                                getTotalQtyForPart(
                                                    item.part_id,
                                                ) >
                                                (getPartInfo(item.part_id)
                                                    ?.stock || 0)
                                            "
                                            class="ml-2 font-medium text-destructive"
                                        >
                                            (Insufficient Stock!)
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-3 border-t pt-4">
                        <Button
                            type="button"
                            variant="outline"
                            @click="router.visit('/outgoings')"
                            :disabled="form.processing"
                        >
                            Cancel
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            @click="saveDraft"
                            :disabled="
                                form.processing ||
                                !form.items.length ||
                                !!duplicateAlert
                            "
                        >
                            Save as Draft
                        </Button>
                        <Button
                            type="button"
                            @click="confirm"
                            :disabled="
                                form.processing ||
                                !form.items.length ||
                                !!duplicateAlert ||
                                !!insufficientStockAlert
                            "
                        >
                            {{
                                form.processing
                                    ? 'Processing...'
                                    : 'Confirm & Update Stock'
                            }}
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
