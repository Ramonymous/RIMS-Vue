<script setup lang="ts">
import { BrowserMultiFormatReader } from '@zxing/browser';
import { NotFoundException } from '@zxing/library';
import { Camera, CameraOff, ScanLine } from 'lucide-vue-next';
import { onUnmounted, ref, watch } from 'vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';

type Props = {
    isActive: boolean;
};

const props = defineProps<Props>();

const emit = defineEmits<{
    (e: 'scanned', value: string): void;
    (e: 'close'): void;
}>();

const videoRef = ref<HTMLVideoElement | null>(null);
const codeReader = ref<BrowserMultiFormatReader | null>(null);
const isScanning = ref(false);
const error = ref<string | null>(null);
const devices = ref<MediaDeviceInfo[]>([]);
const selectedDeviceId = ref<string | null>(null);
const isInCooldown = ref(false);
const cooldownTimer = ref<number | null>(null);
const scannerControls = ref<{ stop: () => void } | null>(null);

async function getVideoDevices() {
    try {
        // Request camera permissions first
        await navigator.mediaDevices
            .getUserMedia({ video: true })
            .then((stream) => {
                // Stop the stream immediately, we just needed permission
                stream.getTracks().forEach((track) => track.stop());
            });

        const mediaDevices = await navigator.mediaDevices.enumerateDevices();
        devices.value = mediaDevices.filter(
            (device) => device.kind === 'videoinput',
        );

        if (devices.value.length === 0) {
            error.value = 'No camera devices found';
            return;
        }

        // Prefer rear camera on mobile
        const rearCamera = devices.value.find(
            (device) =>
                device.label.toLowerCase().includes('back') ||
                device.label.toLowerCase().includes('rear') ||
                device.label.toLowerCase().includes('environment'),
        );

        selectedDeviceId.value =
            rearCamera?.deviceId || devices.value[0]?.deviceId || null;
    } catch (err) {
        error.value =
            'Unable to access camera. Please grant camera permissions.';
        console.error('Error getting video devices:', err);
    }
}

async function startScanning() {
    // Prevent double initialization
    if (isScanning.value || codeReader.value || scannerControls.value) {
        return;
    }

    if (!videoRef.value) {
        error.value = 'Video element not ready';
        return;
    }

    if (!selectedDeviceId.value) {
        error.value = 'No camera selected';
        return;
    }

    try {
        error.value = null;
        isScanning.value = true;

        codeReader.value = new BrowserMultiFormatReader();

        const controls = await codeReader.value.decodeFromVideoDevice(
            selectedDeviceId.value,
            videoRef.value,
            (result, err) => {
                // Enforce cooldown - ignore scans during cooldown period
                if (isInCooldown.value) {
                    return;
                }

                if (result) {
                    const scannedValue = result.getText();
                    if (scannedValue) {
                        // Start cooldown immediately
                        isInCooldown.value = true;

                        // Emit the scanned value
                        emit('scanned', scannedValue);

                        // Stop scanning
                        stopScanning();

                        // Set 5-second cooldown timer
                        cooldownTimer.value = window.setTimeout(() => {
                            isInCooldown.value = false;
                            cooldownTimer.value = null;
                        }, 5000);
                    }
                }

                if (err && !(err instanceof NotFoundException)) {
                    console.error('Scan error:', err);
                }
            },
        );

        // Store controls for proper cleanup
        scannerControls.value = controls;
    } catch (err: any) {
        error.value =
            err?.message || 'Failed to start camera. Please check permissions.';
        console.error('Error starting scanner:', err);
        isScanning.value = false;
    }
}

function stopScanning() {
    // Stop the scanner using controls
    if (scannerControls.value) {
        scannerControls.value.stop();
        scannerControls.value = null;
    }

    // Clean up code reader
    if (codeReader.value) {
        codeReader.value = null;
    }

    // Manually stop all media stream tracks
    if (videoRef.value && videoRef.value.srcObject) {
        const stream = videoRef.value.srcObject as MediaStream;
        stream.getTracks().forEach((track) => {
            track.stop();
        });
        videoRef.value.srcObject = null;
    }

    isScanning.value = false;
    error.value = null;
}

function handleClose() {
    stopScanning();

    // Clear cooldown timer if exists
    if (cooldownTimer.value) {
        clearTimeout(cooldownTimer.value);
        cooldownTimer.value = null;
    }

    emit('close');
}

async function switchCamera() {
    stopScanning();
    await startScanning();
}

// Watch for active state changes - ONLY way to start scanning
watch(
    () => props.isActive,
    async (active) => {
        if (
            active &&
            !isScanning.value &&
            !codeReader.value &&
            !scannerControls.value
        ) {
            // Add small delay to ensure video element is mounted
            await new Promise((resolve) => setTimeout(resolve, 100));
            await getVideoDevices();
            await startScanning();
        } else if (!active && isScanning.value) {
            stopScanning();
        }
    },
    { immediate: true },
);

onUnmounted(() => {
    stopScanning();

    // Clear cooldown timer on unmount
    if (cooldownTimer.value) {
        clearTimeout(cooldownTimer.value);
        cooldownTimer.value = null;
    }
});
</script>

<template>
    <div
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm p-4"
    >
        <div class="relative w-full max-w-2xl overflow-hidden rounded-xl border bg-background shadow-2xl">
            <div
                class="flex items-center justify-between border-b bg-gradient-to-r from-muted/60 to-background px-4 py-3"
            >
                <h3 class="flex items-center gap-2 text-base font-semibold">
                    <ScanLine class="h-5 w-5" />
                    Scan QR Code
                </h3>
                <Button variant="ghost" size="icon" @click="handleClose">
                    <CameraOff class="h-4 w-4" />
                </Button>
            </div>

            <div class="p-4">
                <Alert v-if="error" variant="destructive" class="mb-4">
                    <AlertDescription>{{ error }}</AlertDescription>
                </Alert>

                <div class="relative aspect-video w-full overflow-hidden rounded-xl bg-muted">
                    <video
                        ref="videoRef"
                        class="h-full w-full object-cover"
                        autoplay
                        playsinline
                    />

                    <!-- Scanning overlay -->
                    <div
                        v-if="isScanning"
                        class="absolute inset-0 flex items-center justify-center"
                    >
                        <div class="relative h-56 w-56">
                            <div class="absolute inset-0 rounded-xl border border-white/20" />
                            <div class="absolute inset-0 rounded-xl ring-2 ring-primary/70" />
                            <div class="absolute -left-1 -top-1 h-6 w-6 rounded-tl-xl border-l-4 border-t-4 border-primary" />
                            <div class="absolute -right-1 -top-1 h-6 w-6 rounded-tr-xl border-r-4 border-t-4 border-primary" />
                            <div class="absolute -left-1 -bottom-1 h-6 w-6 rounded-bl-xl border-l-4 border-b-4 border-primary" />
                            <div class="absolute -right-1 -bottom-1 h-6 w-6 rounded-br-xl border-r-4 border-b-4 border-primary" />
                            <div class="animate-scan absolute inset-0 rounded-xl border-t-4 border-primary" />
                        </div>
                    </div>

                    <!-- Loading state -->
                    <div
                        v-if="!isScanning && !error"
                        class="absolute inset-0 flex items-center justify-center"
                    >
                        <div class="flex flex-col items-center gap-2">
                            <Camera
                                class="h-12 w-12 animate-pulse text-muted-foreground"
                            />
                            <p class="text-sm text-muted-foreground">
                                Initializing camera...
                            </p>
                        </div>
                    </div>
                </div>

                <div class="mt-4 space-y-2">
                    <p class="text-center text-sm text-muted-foreground">
                        Position the QR code within the frame
                    </p>

                    <!-- Device selector if multiple cameras -->
                    <div
                        v-if="devices.length > 1"
                        class="flex justify-center gap-2"
                    >
                        <Button
                            v-for="(device, index) in devices"
                            :key="device.deviceId"
                            variant="outline"
                            size="sm"
                            :class="{
                                'bg-primary text-primary-foreground':
                                    selectedDeviceId === device.deviceId,
                            }"
                            :disabled="isInCooldown"
                            @click="
                                selectedDeviceId = device.deviceId;
                                switchCamera();
                            "
                        >
                            <Camera class="mr-2 h-3 w-3" />
                            Camera {{ index + 1 }}
                        </Button>
                    </div>

                    <!-- Cooldown indicator -->
                    <div v-if="isInCooldown" class="text-center">
                        <p class="text-sm text-muted-foreground">
                            Processing scan... Please wait.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
@keyframes scan {
    0% {
        transform: translateY(-100%);
    }
    100% {
        transform: translateY(100%);
    }
}

.animate-scan {
    animation: scan 2s ease-in-out infinite;
}
</style>
