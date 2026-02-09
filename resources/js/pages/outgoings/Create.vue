<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    PackageMinus,
    Plus,
    Trash2,
    AlertTriangle,
    Search,
    Clock,
    CheckCircle,
    PackageOpen,
    Hash,
    Calendar,
    FileText,
} from 'lucide-vue-next';
import { nextTick, ref, watch } from 'vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
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
import { Badge } from '@/components/ui/badge';

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
    
    // Scroll to the newly added item after a short delay
    nextTick(() => {
        setTimeout(() => {
            const elementKey = `item-${newId}`;
            if (itemRefs.value[elementKey]) {
                itemRefs.value[elementKey].scrollIntoView({
                    behavior: 'smooth',
                    block: 'nearest',
                });
            }
        }, 100);
    });
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
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
            <!-- Header Card -->
            <Card class="border-primary/20">
                <CardHeader class="pb-4">
                    <div class="flex items-start justify-between">
                        <div>
                            <CardTitle class="flex items-center gap-2 text-2xl">
                                <PackageMinus class="h-6 w-6" />
                                Create Outgoing
                            </CardTitle>
                            <CardDescription class="mt-2">
                                Create a new outgoing document to issue parts from inventory
                            </CardDescription>
                        </div>
                        <Badge variant="outline" class="flex items-center gap-1.5">
                            <Hash class="h-3.5 w-3.5" />
                            {{ form.doc_number }}
                        </Badge>
                    </div>
                </CardHeader>

                <CardContent class="space-y-6">
                    <!-- Document Information -->
                    <div class="rounded-lg border bg-card p-5">
                        <h3 class="mb-4 flex items-center gap-2 font-semibold text-lg">
                            <FileText class="h-4 w-4" />
                            Document Information
                        </h3>

                        <div class="grid gap-5 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="issued-at" class="text-sm font-medium">
                                    <div class="flex items-center gap-2">
                                        <Calendar class="h-3.5 w-3.5" />
                                        Issue Date & Time
                                        <span class="text-destructive">*</span>
                                    </div>
                                </Label>
                                <Input
                                    id="issued-at"
                                    v-model="form.issued_at"
                                    type="datetime-local"
                                    required
                                    class="h-10"
                                />
                                <span
                                    v-if="form.errors.issued_at"
                                    class="text-sm text-destructive"
                                >
                                    {{ form.errors.issued_at }}
                                </span>
                            </div>

                            <div class="space-y-2">
                                <Label for="notes" class="text-sm font-medium">
                                    <div class="flex items-center gap-2">
                                        <FileText class="h-3.5 w-3.5" />
                                        Notes (Optional)
                                    </div>
                                </Label>
                                <Textarea
                                    id="notes"
                                    v-model="form.notes"
                                    placeholder="Add any additional notes or remarks..."
                                    rows="2"
                                    class="min-h-[80px]"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Items Section -->
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="flex items-center gap-2 font-semibold text-lg">
                                <PackageOpen class="h-4 w-4" />
                                Outgoing Items
                                <Badge variant="secondary" class="ml-2">
                                    {{ form.items.length }} {{ form.items.length === 1 ? 'item' : 'items' }}
                                </Badge>
                            </h3>
                            <Button
                                type="button"
                                variant="default"
                                size="sm"
                                @click="addItem"
                                class="gap-1.5"
                            >
                                <Plus class="h-4 w-4" />
                                Add Item
                            </Button>
                        </div>

                        <!-- Alerts -->
                        <Alert v-if="duplicateAlert" variant="destructive" class="animate-pulse">
                            <AlertTriangle class="h-4 w-4" />
                            <AlertDescription class="font-medium">
                                {{ duplicateAlert }}
                            </AlertDescription>
                        </Alert>

                        <Alert
                            v-if="insufficientStockAlert"
                            variant="destructive"
                            class="animate-pulse"
                        >
                            <AlertTriangle class="h-4 w-4" />
                            <AlertDescription class="font-medium">
                                {{ insufficientStockAlert }}
                            </AlertDescription>
                        </Alert>

                        <span
                            v-if="form.errors.items"
                            class="text-sm text-destructive"
                        >
                            {{ form.errors.items }}
                        </span>

                        <!-- Empty State -->
                        <Card
                            v-if="form.items.length === 0"
                            class="border-dashed bg-muted/30 text-center"
                        >
                            <CardContent class="flex flex-col items-center justify-center py-8">
                                <PackageOpen class="mb-3 h-12 w-12 text-muted-foreground" />
                                <h4 class="mb-1 font-medium">No items added yet</h4>
                                <p class="mb-4 text-sm text-muted-foreground">
                                    Start by adding parts to issue from inventory
                                </p>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    @click="addItem"
                                    class="gap-1.5"
                                >
                                    <Plus class="h-3.5 w-3.5" />
                                    Add First Item
                                </Button>
                            </CardContent>
                        </Card>

                        <!-- Items List -->
                        <div class="space-y-3">
                            <div
                                v-for="(item, index) in form.items"
                                :key="item.id"
                                :ref="(el) => setItemRef(item.id, el)"
                                class="rounded-lg border bg-card p-5 transition-all"
                                :class="{
                                    'border-yellow-500/70 bg-yellow-50/50 dark:bg-yellow-950/30 shadow-sm ring-1 ring-yellow-500/20':
                                        item.isHighlighted,
                                    'hover:border-border/80': !item.isHighlighted,
                                }"
                            >
                                <div class="mb-4 flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-7 w-7 items-center justify-center rounded-full bg-primary/10 text-xs font-medium text-primary">
                                            {{ index + 1 }}
                                        </div>
                                        <span class="font-medium">Item #{{ index + 1 }}</span>
                                    </div>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        @click="removeItem(index)"
                                        class="h-8 w-8 p-0 text-muted-foreground hover:text-destructive"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </div>

                                <div class="grid gap-5 md:grid-cols-3">
                                    <!-- Part Selection -->
                                    <div class="grid gap-2 md:col-span-2">
                                        <Label :for="`part-${item.id}`" class="text-sm font-medium">
                                            Part Selection
                                            <span class="text-destructive">*</span>
                                        </Label>
                                        <Select
                                            v-model="item.part_id"
                                            @update:model-value="
                                                checkDuplicatePart(index, $event)
                                            "
                                            required
                                        >
                                            <SelectTrigger :id="`part-${item.id}`" class="h-10">
                                                <SelectValue placeholder="Search and select a part..." />
                                            </SelectTrigger>
                                            <SelectContent class="max-h-[300px]">
                                                <div class="sticky top-0 z-10 bg-popover p-2 pb-1">
                                                    <div class="relative">
                                                        <Search
                                                            class="absolute top-2.5 left-2.5 h-4 w-4 text-muted-foreground"
                                                        />
                                                        <Input
                                                            v-model="
                                                                partSearchQueries[
                                                                    item.id
                                                                ]
                                                            "
                                                            placeholder="Search by part number or name..."
                                                            class="h-9 pl-9 pr-3"
                                                            @keydown.enter.prevent
                                                        />
                                                    </div>
                                                </div>
                                                <div class="max-h-[250px] overflow-y-auto p-1">
                                                    <SelectItem
                                                        v-for="part in getFilteredPartsForItem(
                                                            item.id,
                                                        )"
                                                        :key="part.id"
                                                        :value="part.id"
                                                        class="py-2.5"
                                                    >
                                                        <div class="flex flex-col">
                                                            <span class="font-medium">
                                                                {{ part.part_number }}
                                                            </span>
                                                            <span class="text-sm text-muted-foreground">
                                                                {{ part.part_name }}
                                                            </span>
                                                            <div class="mt-1 flex items-center gap-2 text-xs">
                                                                <span class="rounded-full bg-muted px-2 py-0.5">
                                                                    Stock: {{ part.stock }}
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </SelectItem>
                                                    <div
                                                        v-if="
                                                            getFilteredPartsForItem(
                                                                item.id,
                                                            ).length === 0
                                                        "
                                                        class="py-6 text-center text-sm text-muted-foreground"
                                                    >
                                                        <Search class="mx-auto mb-2 h-6 w-6" />
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

                                    <!-- Quantity Input -->
                                    <div class="grid gap-2">
                                        <Label :for="`qty-${item.id}`" class="text-sm font-medium">
                                            Quantity
                                            <span class="text-destructive">*</span>
                                        </Label>
                                        <Input
                                            :id="`qty-${item.id}`"
                                            v-model.number="item.qty"
                                            type="number"
                                            min="1"
                                            :max="getPartInfo(item.part_id)?.stock || undefined"
                                            required
                                            class="h-10"
                                        />
                                        <span
                                            v-if="form.errors[`items.${index}.qty`]"
                                            class="text-sm text-destructive"
                                        >
                                            {{ form.errors[`items.${index}.qty`] }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Stock Information -->
                                <div
                                    v-if="item.part_id && getPartInfo(item.part_id)"
                                    class="mt-4 rounded-lg border p-4"
                                    :class="[
                                        getTotalQtyForPart(item.part_id) >
                                        (getPartInfo(item.part_id)?.stock || 0)
                                            ? 'border-destructive/30 bg-destructive/5'
                                            : 'border-border bg-muted/30',
                                    ]"
                                >
                                    <div class="grid grid-cols-2 gap-3 text-sm">
                                        <div class="space-y-1">
                                            <div class="text-xs text-muted-foreground">Current Stock</div>
                                            <div class="flex items-center gap-2 font-medium">
                                                <div class="h-2 w-2 rounded-full bg-current"></div>
                                                {{ getPartInfo(item.part_id)?.stock }}
                                            </div>
                                        </div>
                                        <div class="space-y-1">
                                            <div class="text-xs text-muted-foreground">Quantity to Issue</div>
                                            <div class="flex items-center gap-2 font-medium">
                                                <div class="h-2 w-2 rounded-full bg-current"></div>
                                                {{ getTotalQtyForPart(item.part_id) }}
                                            </div>
                                        </div>
                                        <div class="space-y-1">
                                            <div class="text-xs text-muted-foreground">After Issue</div>
                                            <div class="flex items-center gap-2 font-medium" :class="{
                                                'text-destructive': getTotalQtyForPart(item.part_id) > (getPartInfo(item.part_id)?.stock || 0),
                                                'text-green-600': (getPartInfo(item.part_id)?.stock || 0) - getTotalQtyForPart(item.part_id) >= 0
                                            }">
                                                <div class="h-2 w-2 rounded-full bg-current"></div>
                                                {{
                                                    (getPartInfo(item.part_id)?.stock || 0) -
                                                    getTotalQtyForPart(item.part_id)
                                                }}
                                            </div>
                                        </div>
                                        <div class="space-y-1">
                                            <div class="text-xs text-muted-foreground">Status</div>
                                            <Badge
                                                variant="outline"
                                                class="gap-1.5"
                                                :class="{
                                                    'border-destructive/50 text-destructive bg-destructive/10':
                                                        getTotalQtyForPart(item.part_id) > (getPartInfo(item.part_id)?.stock || 0),
                                                    'border-green-600/50 text-green-600 bg-green-600/10':
                                                        (getPartInfo(item.part_id)?.stock || 0) - getTotalQtyForPart(item.part_id) >= 0
                                                }"
                                            >
                                                <AlertTriangle
                                                    v-if="getTotalQtyForPart(item.part_id) > (getPartInfo(item.part_id)?.stock || 0)"
                                                    class="h-3 w-3"
                                                />
                                                <CheckCircle
                                                    v-else
                                                    class="h-3 w-3"
                                                />
                                                {{
                                                    getTotalQtyForPart(item.part_id) > (getPartInfo(item.part_id)?.stock || 0)
                                                    ? 'Insufficient'
                                                    : 'Sufficient'
                                                }}
                                            </Badge>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </CardContent>

                <!-- Footer Actions -->
                <CardFooter class="flex flex-col gap-4 border-t bg-muted/30 px-6 py-6">
                    <div class="flex w-full flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="text-sm text-muted-foreground">
                            {{ form.items.length }} items • Total quantity: {{ form.items.reduce((sum, item) => sum + item.qty, 0) }}
                        </div>
                        <div class="flex flex-col gap-3 sm:flex-row">
                            <Button
                                type="button"
                                variant="outline"
                                @click="router.visit('/outgoings')"
                                :disabled="form.processing"
                                class="gap-1.5"
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
                                class="gap-1.5"
                            >
                                <Clock class="h-4 w-4" />
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
                                class="gap-1.5"
                            >
                                <CheckCircle class="h-4 w-4" />
                                {{
                                    form.processing
                                        ? 'Processing...'
                                        : 'Confirm & Update Stock'
                                }}
                            </Button>
                        </div>
                    </div>
                </CardFooter>
            </Card>
        </div>
    </AppLayout>
</template>