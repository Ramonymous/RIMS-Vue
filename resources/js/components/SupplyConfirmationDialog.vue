<script setup lang="ts">
import { ref, watch } from 'vue';
import { ScanLine, Check, X } from 'lucide-vue-next';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Alert, AlertDescription } from '@/components/ui/alert';
import QRScanner from '@/components/QRScanner.vue';

type Props = {
    open: boolean;
    partNumber: string;
    partName: string;
    itemId: string;
    requestedQty: number;
    availableStock: number;
};

const props = defineProps<Props>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'confirmed', itemId: string, scannedPartNumber: string, qty: number): void;
}>();

const showQRScanner = ref(false);
const manualInput = ref('');
const qty = ref(1);
const error = ref<string | null>(null);
const isProcessing = ref(false);
const step = ref<'validate' | 'quantity'>('validate'); // Two-step process
const validatedPartNumber = ref<string>('');

function handleClose() {
    if (isProcessing.value) return;
    
    showQRScanner.value = false;
    manualInput.value = '';
    step.value = 'validate';
    validatedPartNumber.value = '';
    error.value = null;
    emit('update:open', false);
}

function openQRScanner() {
    error.value = null;
    showQRScanner.value = true;
}

function closeQRScanner() {
    showQRScanner.value = false;
}

function handleScanned(partNumber: string) {
    closeQRScanner();
    validatePartNumber(partNumber.trim());
}

function handleManualConfirm() {
    if (!manualInput.value.trim()) {
        error.value = 'Please enter a part number';
        return;
    }
    validatePartNumber(manualInput.value.trim());
}

function validatePartNumber(scannedPartNumber: string) {
    error.value = null;
    
    // Normalize both part numbers for comparison (case-insensitive, trim whitespace)
    const normalizedScanned = scannedPartNumber.toUpperCase().replace(/\s+/g, '');
    const normalizedExpected = props.partNumber.toUpperCase().replace(/\s+/g, '');
    
    if (normalizedScanned !== normalizedExpected) {
        error.value = `Part number mismatch! Expected: ${props.partNumber}, Got: ${scannedPartNumber}`;
        manualInput.value = '';
        return;
    }
    
    // Match confirmed, move to quantity step
    validatedPartNumber.value = scannedPartNumber;
    step.value = 'quantity';
}

function handleConfirmSupply() {
    error.value = null;
    
    // Validate quantity
    if (!qty.value || qty.value < 1) {
        error.value = 'Please enter a valid quantity (minimum 1)';
        return;
    }
    
    if (qty.value > props.availableStock) {
        error.value = `Quantity cannot exceed available stock (${props.availableStock})`;
        return;
    }
    
    // Emit with validated part number and quantity
    isProcessing.value = true;
    emit('confirmed', props.itemId, validatedPartNumber.value, qty.value);
}

// Reset state when dialog closes
watch(() => props.open, (newValue) => {
    if (!newValue) {
        showQRScanner.value = false;
        manualInput.value = '';
        qty.value = 1;
        step.value = 'validate';
        validatedPartNumber.value = '';
        error.value = null;
        isProcessing.value = false;
    } else {
        // Set default quantity to requested quantity when opening
        qty.value = props.requestedQty;
        step.value = 'validate';
    }
});
</script>

<template>
    <Dialog :open="open" @update:open="handleClose">
        <DialogContent class="sm:max-w-[500px]">
            <DialogHeader>
                <DialogTitle>{{ step === 'validate' ? 'Confirm Part Number' : 'Enter Supply Quantity' }}</DialogTitle>
                <DialogDescription>
                    {{ step === 'validate' 
                        ? 'Scan or enter the part number to verify' 
                        : 'Enter the quantity to supply' 
                    }}
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-4 py-4">
                <!-- Expected Part Info -->
                <div class="rounded-lg border bg-muted/50 p-4 space-y-2">
                    <div class="text-sm font-medium text-muted-foreground">Expected Part</div>
                    <div class="font-semibold text-lg">{{ partNumber }}</div>
                    <div class="text-sm text-muted-foreground">{{ partName }}</div>
                    
                    <!-- Show validated checkmark in step 2 -->
                    <div v-if="step === 'quantity'" class="mt-2 flex items-center gap-2 text-green-600 dark:text-green-400">
                        <Check class="h-4 w-4" />
                        <span class="text-sm font-medium">Part number verified</span>
                    </div>
                </div>

                <!-- Step 1: Validate Part Number -->
                <template v-if="step === 'validate'">
                    <!-- Error Alert -->
                    <Alert v-if="error" variant="destructive">
                        <X class="h-4 w-4" />
                        <AlertDescription>{{ error }}</AlertDescription>
                    </Alert>

                    <!-- QR Scanner Button -->
                    <div class="space-y-2">
                        <Button
                            type="button"
                            variant="outline"
                            class="w-full"
                            @click="openQRScanner"
                            :disabled="isProcessing"
                        >
                            <ScanLine class="mr-2 h-4 w-4" />
                            Scan QR Code
                        </Button>
                    </div>

                    <!-- Manual Input -->
                    <div class="space-y-2">
                        <Label for="manual-part" class="text-xs text-muted-foreground">
                            Or enter manually
                        </Label>
                        <div class="flex gap-2">
                            <Input
                                id="manual-part"
                                v-model="manualInput"
                                placeholder="Enter part number"
                                :disabled="isProcessing"
                                @keydown.enter="handleManualConfirm"
                                class="flex-1"
                            />
                            <Button
                                type="button"
                                @click="handleManualConfirm"
                                :disabled="!manualInput.trim() || isProcessing"
                            >
                                <Check class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>
                </template>

                <!-- Step 2: Enter Quantity -->
                <template v-else-if="step === 'quantity'">
                    <!-- Error Alert -->
                    <Alert v-if="error" variant="destructive">
                        <X class="h-4 w-4" />
                        <AlertDescription>{{ error }}</AlertDescription>
                    </Alert>

                    <!-- Quantity Input -->
                    <div class="space-y-2">
                        <Label for="qty">Supply Quantity <span class="text-destructive">*</span></Label>
                        <Input
                            id="qty"
                            v-model.number="qty"
                            type="number"
                            min="1"
                            :max="availableStock"
                            :disabled="isProcessing"
                            placeholder="Enter quantity"
                            autofocus
                        />
                        <p class="text-xs text-muted-foreground">
                            Available stock: {{ availableStock }}
                        </p>
                    </div>
                </template>
            </div>

            <DialogFooter>
                <Button
                    v-if="step === 'validate'"
                    type="button"
                    variant="outline"
                    @click="handleClose"
                    :disabled="isProcessing"
                >
                    Cancel
                </Button>
                
                <template v-else-if="step === 'quantity'">
                    <Button
                        type="button"
                        variant="outline"
                        @click="step = 'validate'; error = null; manualInput = ''"
                        :disabled="isProcessing"
                    >
                        Back
                    </Button>
                    <Button
                        type="button"
                        @click="handleConfirmSupply"
                        :disabled="!qty || qty < 1 || qty > availableStock || isProcessing"
                    >
                        Confirm Supply
                    </Button>
                </template>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <!-- QR Scanner Modal with high z-index -->
    <div v-if="showQRScanner" class="fixed inset-0 z-[100]">
        <QRScanner
            :is-active="showQRScanner"
            @scanned="handleScanned"
            @close="closeQRScanner"
        />
    </div>
</template>
