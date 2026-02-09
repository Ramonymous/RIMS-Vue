<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useForm } from '@inertiajs/vue3';
import { checkLocations } from '@/actions/App/Http/Controllers/RequestsController';
import QRScanner from '@/components/QRScanner.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { MapPin, QrCode, Search, Package, CheckCircle2, XCircle } from 'lucide-vue-next';

const form = useForm({ part_number: '' });
const result = ref<string | null>(null);
const error = ref<string | null>(null);
const qrScannerActive = ref(false);
const isLoading = computed(() => form.processing);

function submit() {
    error.value = null;
    result.value = null;
    if (!form.part_number.trim()) {
        error.value = 'Part number is required.';
        return;
    }
    form.submit(checkLocations(), {
        preserveScroll: true,
        onSuccess: (page) => {
            const location = page.props?.location;
            result.value = typeof location === 'string' && location.trim() !== ''
                ? location
                : 'Location found.';
        },
        onError: (errors) => {
            error.value = errors.part_number || 'Failed to check location.';
        },
    });
}

function openQRScanner() {
    qrScannerActive.value = true;
}

function closeQRScanner() {
    qrScannerActive.value = false;
}

function handleQRScanned(scannedValue: string) {
    const value = scannedValue?.trim();
    if (!value) {
        closeQRScanner();
        return;
    }

    form.part_number = value;
    closeQRScanner();
    submit();
}

function clearSearch() {
    form.part_number = '';
    result.value = null;
    error.value = null;
}
</script>

<template>
    <Head title="Check Part Location" />
    
    <AppLayout>
        <div class="flex flex-1 flex-col items-center justify-center p-4 sm:p-6">
            <!-- Header Section -->
            <div class="mb-8 text-center">
                <div class="mb-3 inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-500 to-blue-600 shadow-lg shadow-blue-500/30">
                    <Package class="h-8 w-8 text-white" />
                </div>
                <h1 class="text-3xl font-bold tracking-tight">Part Location Finder</h1>
                <p class="mt-2 text-sm text-muted-foreground">
                    Quickly locate parts in your warehouse
                </p>
            </div>

            <!-- Main Card -->
            <Card class="w-full max-w-xl overflow-hidden shadow-lg transition-shadow hover:shadow-xl">
                <CardHeader class="border-b bg-gradient-to-r from-muted/50 to-muted/30">
                    <CardTitle class="flex items-center gap-2 text-lg">
                        <MapPin class="h-5 w-5 text-blue-600" />
                        Search for Parts
                    </CardTitle>
                </CardHeader>
                <CardContent class="space-y-6 p-6 sm:p-8">
                    <!-- Instructions -->
                    <div class="rounded-lg bg-blue-50 p-4 dark:bg-blue-950/20">
                        <p class="text-sm text-blue-900 dark:text-blue-100">
                            💡 <span class="font-medium">Quick tip:</span> Scan a QR code or manually enter a part number to find its exact warehouse location.
                        </p>
                    </div>

                    <!-- Search Form -->
                    <form class="space-y-4" @submit.prevent="submit">
                        <div class="space-y-2">
                            <label class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                                Part Number
                            </label>
                            <div class="flex gap-2">
                                <div class="relative flex-1">
                                    <Search
                                        class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground transition-colors"
                                        :class="{ 'text-blue-500': form.part_number }"
                                    />
                                    <Input
                                        v-model="form.part_number"
                                        placeholder="e.g., PN-12345"
                                        class="pl-9 pr-9 transition-all focus:ring-2 focus:ring-blue-500"
                                        :class="{ 'border-blue-500': form.part_number }"
                                        autofocus
                                        :disabled="isLoading"
                                    />
                                    <button
                                        v-if="form.part_number"
                                        type="button"
                                        @click="clearSearch"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground transition-colors hover:text-foreground"
                                        :disabled="isLoading"
                                    >
                                        <XCircle class="h-4 w-4" />
                                    </button>
                                </div>

                                <Button
                                    type="button"
                                    variant="outline"
                                    size="icon"
                                    class="shrink-0 transition-all hover:scale-105 hover:border-blue-500 hover:text-blue-600"
                                    @click="openQRScanner"
                                    title="Scan QR Code"
                                    :disabled="isLoading"
                                >
                                    <QrCode class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>

                        <Button 
                            type="submit" 
                            class="w-full bg-gradient-to-r from-blue-600 to-blue-700 transition-all hover:from-blue-700 hover:to-blue-800 hover:shadow-lg"
                            :disabled="isLoading || !form.part_number.trim()"
                        >
                            <Search v-if="!isLoading" class="mr-2 h-4 w-4" />
                            <span v-if="isLoading" class="mr-2 h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
                            {{ isLoading ? 'Searching...' : 'Find Location' }}
                        </Button>
                    </form>

                    <!-- Success Result -->
                    <transition
                        enter-active-class="transition-all duration-300 ease-out"
                        enter-from-class="opacity-0 scale-95 translate-y-2"
                        enter-to-class="opacity-100 scale-100 translate-y-0"
                        leave-active-class="transition-all duration-200 ease-in"
                        leave-from-class="opacity-100 scale-100"
                        leave-to-class="opacity-0 scale-95"
                    >
                        <Alert 
                            v-if="result" 
                            class="border-2 border-green-200 bg-gradient-to-r from-green-50 to-emerald-50 shadow-md dark:border-green-900/50 dark:from-green-950/30 dark:to-emerald-950/30"
                        >
                            <AlertDescription>
                                <div class="flex items-start justify-between gap-4">
                                    <div class="flex-1">
                                        <div class="mb-1 flex items-center gap-2">
                                            <CheckCircle2 class="h-5 w-5 text-green-600 dark:text-green-400" />
                                            <span class="text-xs font-semibold uppercase tracking-wider text-green-700 dark:text-green-300">
                                                Location Found
                                            </span>
                                        </div>
                                        <div class="text-2xl font-bold text-green-900 dark:text-green-100">
                                            {{ result }}
                                        </div>
                                        <div class="mt-1 text-xs text-green-700 dark:text-green-300">
                                            Part: {{ form.part_number }}
                                        </div>
                                    </div>
                                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/30">
                                        <MapPin class="h-6 w-6 text-green-600 dark:text-green-400" />
                                    </div>
                                </div>
                            </AlertDescription>
                        </Alert>
                    </transition>

                    <!-- Error Alert -->
                    <transition
                        enter-active-class="transition-all duration-300 ease-out"
                        enter-from-class="opacity-0 scale-95 translate-y-2"
                        enter-to-class="opacity-100 scale-100 translate-y-0"
                        leave-active-class="transition-all duration-200 ease-in"
                        leave-from-class="opacity-100 scale-100"
                        leave-to-class="opacity-0 scale-95"
                    >
                        <Alert v-if="error" variant="destructive" class="shadow-md">
                            <AlertDescription class="flex items-center gap-2">
                                <XCircle class="h-4 w-4" />
                                {{ error }}
                            </AlertDescription>
                        </Alert>
                    </transition>
                </CardContent>
            </Card>

            <!-- Quick Stats or Info Footer -->
            <div class="mt-8 flex flex-wrap justify-center gap-6 text-center text-sm text-muted-foreground">
                <div class="flex items-center gap-2">
                    <div class="h-2 w-2 rounded-full bg-green-500"></div>
                    <span>Fast Lookup</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="h-2 w-2 rounded-full bg-blue-500"></div>
                    <span>QR Scanning</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="h-2 w-2 rounded-full bg-purple-500"></div>
                    <span>Real-time Results</span>
                </div>
            </div>
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