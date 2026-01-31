<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import type { Part } from '@/composables/useParts';
import { useFormState } from '@/composables/useFormState';

interface Props {
    open: boolean;
    part?: Part | null;
}

interface Emits {
    (e: 'update:open', value: boolean): void;
    (e: 'success'): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

// Form state with dirty tracking
const { form, isDirty, submit } = useFormState(
    {
        part_number: props.part?.part_number || '',
        part_name: props.part?.part_name || '',
        customer_code: props.part?.customer_code || '',
        supplier_code: props.part?.supplier_code || '',
        model: props.part?.model || '',
        variant: props.part?.variant || '',
        standard_packing: props.part?.standard_packing || 1,
        stock: props.part?.stock || 0,
        address: props.part?.address || '',
        is_active: props.part?.is_active ?? true,
    },
    {
        onSuccess: () => {
            emit('update:open', false);
            emit('success');
        },
    }
);

// Update form when part changes
const updateFormData = () => {
    if (props.part) {
        form.part_number = props.part.part_number;
        form.part_name = props.part.part_name;
        form.customer_code = props.part.customer_code || '';
        form.supplier_code = props.part.supplier_code || '';
        form.model = props.part.model || '';
        form.variant = props.part.variant || '';
        form.standard_packing = props.part.standard_packing;
        form.stock = props.part.stock;
        form.address = props.part.address || '';
        form.is_active = props.part.is_active;
    }
};

const handleSubmit = () => {
    const url = props.part ? `/parts/${props.part.id}` : '/parts';
    const method = props.part ? 'put' : 'post';
    submit(url, { method });
};
</script>

<template>
    <Dialog :open="open" @update:open="(val) => emit('update:open', val)">
        <DialogContent class="max-w-2xl max-h-[90vh] overflow-y-auto">
            <DialogHeader>
                <DialogTitle>{{ part ? 'Edit Part' : 'Create New Part' }}</DialogTitle>
                <DialogDescription>
                    {{ part ? 'Update part information' : 'Add a new part to the inventory' }}
                </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="handleSubmit">
                <div class="grid gap-4 py-4">
                    <!-- Part Number & Name -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="part-number">
                                Part Number
                                <span class="text-destructive">*</span>
                            </Label>
                            <Input
                                id="part-number"
                                v-model="form.part_number"
                                placeholder="PN-001"
                                required
                            />
                            <span v-if="form.errors.part_number" class="text-sm text-destructive">
                                {{ form.errors.part_number }}
                            </span>
                        </div>
                        <div class="grid gap-2">
                            <Label for="part-name">
                                Part Name
                                <span class="text-destructive">*</span>
                            </Label>
                            <Input
                                id="part-name"
                                v-model="form.part_name"
                                placeholder="Widget Assembly"
                                required
                            />
                            <span v-if="form.errors.part_name" class="text-sm text-destructive">
                                {{ form.errors.part_name }}
                            </span>
                        </div>
                    </div>

                    <!-- Customer & Supplier Code -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="customer-code">Customer Code</Label>
                            <Input
                                id="customer-code"
                                v-model="form.customer_code"
                                placeholder="CUST-001"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="supplier-code">Supplier Code</Label>
                            <Input
                                id="supplier-code"
                                v-model="form.supplier_code"
                                placeholder="SUPP-001"
                            />
                        </div>
                    </div>

                    <!-- Model & Variant -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="model">Model</Label>
                            <Input id="model" v-model="form.model" placeholder="Model X" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="variant">Variant</Label>
                            <Input id="variant" v-model="form.variant" placeholder="V1" />
                        </div>
                    </div>

                    <!-- Packing, Stock, Address -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="grid gap-2">
                            <Label for="standard-packing">
                                Standard Packing
                                <span class="text-destructive">*</span>
                            </Label>
                            <Input
                                id="standard-packing"
                                v-model.number="form.standard_packing"
                                type="number"
                                min="1"
                                required
                            />
                            <span
                                v-if="form.errors.standard_packing"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.standard_packing }}
                            </span>
                        </div>
                        <div class="grid gap-2">
                            <Label for="stock">
                                Stock
                                <span class="text-destructive">*</span>
                            </Label>
                            <Input
                                id="stock"
                                v-model.number="form.stock"
                                type="number"
                                min="0"
                                required
                            />
                            <span v-if="form.errors.stock" class="text-sm text-destructive">
                                {{ form.errors.stock }}
                            </span>
                        </div>
                        <div class="grid gap-2">
                            <Label for="address">Address</Label>
                            <Input id="address" v-model="form.address" placeholder="A1-B2-C3" />
                        </div>
                    </div>

                    <!-- Active Status -->
                    <div class="flex items-center space-x-2">
                        <Checkbox id="is-active" v-model:checked="form.is_active" />
                        <Label for="is-active" class="cursor-pointer font-normal">Active</Label>
                    </div>
                </div>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="emit('update:open', false)"
                    >
                        Cancel
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Saving...' : part ? 'Update Part' : 'Create Part' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
