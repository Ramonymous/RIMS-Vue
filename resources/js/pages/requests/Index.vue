<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { FileInput, AlertCircle, PackageCheck, Volume2, VolumeX, MapPin } from 'lucide-vue-next';
import { useEchoPublic } from '@laravel/echo-vue';
import { create as createRequest } from '@/routes/requests';
import { supply as supplyItem, pickCommand as pickCommandItem } from '@/routes/request-items';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import VoicePermissionModal from '@/components/VoicePermissionModal.vue';
import SupplyConfirmationDialog from '@/components/SupplyConfirmationDialog.vue';
import { useRequestVoice } from '@/composables/useRequestVoice';
import { usePermissions } from '@/composables/usePermissions';

type Part = {
    id: string;
    part_number: string;
    part_name: string;
    stock: number;
};

type RequestItem = {
    id: string;
    part: Part;
    qty: number;
    is_urgent: boolean;
    is_supplied: boolean;
    request: {
        id: string;
        request_number: string;
        destination: string;
        requested_by: { name: string };
        requested_at: string;
    };
};

type Props = {
    requestItems: {
        data: RequestItem[];
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

const { state: voiceState, initialize, requestPermission, announceItem, remindItem } = useRequestVoice();
const showPermissionModal = ref(false);
const voiceEnabled = ref(false);
let reminderInterval: ReturnType<typeof setInterval> | null = null;

// Supply confirmation dialog
const showSupplyDialog = ref(false);
const selectedItem = ref<RequestItem | null>(null);

// Voice queue management
const MAX_QUEUE_SIZE = 20; // Prevent queue overflow
type QueueItem = { item: RequestItem; isReminder: boolean };
const voiceQueue = ref<QueueItem[]>([]);
const isAnnouncing = ref(false);

// Local state for realtime items
const realtimeItemsRaw = ref<RequestItem[]>([...props.requestItems.data]);

// Reactive current time to trigger delay recalculations
const currentTime = ref(Date.now());
let timeUpdateInterval: ReturnType<typeof setInterval> | null = null;

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Requests', href: '/requests' },
];

const { canManageRequests, canSupplyRequests } = usePermissions();
const canManage = canManageRequests;
const canSupply = canSupplyRequests;

// Memoized delay calculations per item - cache Date parsing
// Depends on currentTime to recalculate as time passes
const itemDelays = computed(() => {
    const now = currentTime.value;
    const delays = new Map<string, number>();
    
    for (const item of realtimeItemsRaw.value) {
        const requestTime = new Date(item.request.requested_at).getTime();
        delays.set(item.id, Math.floor((now - requestTime) / 60000));
    }
    
    return delays;
});

// Sorted items: urgent → delayed → normal
const realtimeItems = computed(() => {
    return [...realtimeItemsRaw.value].sort((a, b) => {
        const aDelay = itemDelays.value.get(a.id) || 0;
        const bDelay = itemDelays.value.get(b.id) || 0;
        
        // Priority 1: Urgent items first
        if (a.is_urgent && !b.is_urgent) return -1;
        if (!a.is_urgent && b.is_urgent) return 1;
        
        // Priority 2: Delayed items (>5 min) before normal
        const aIsDelayed = aDelay > 5;
        const bIsDelayed = bDelay > 5;
        
        if (aIsDelayed && !bIsDelayed) return -1;
        if (!aIsDelayed && bIsDelayed) return 1;
        
        // Within same priority, sort by delay time (longer delays first)
        if (aIsDelayed && bIsDelayed) {
            return bDelay - aDelay;
        }
        
        // For normal items, newest first (by requested_at)
        return new Date(b.request.requested_at).getTime() - new Date(a.request.requested_at).getTime();
    });
});

// Memoized card colors
const itemColors = computed(() => {
    return new Map(
        realtimeItems.value.map(item => {
            if (item.is_urgent) {
                return [item.id, 'border-red-300 bg-red-50 dark:border-red-800 dark:bg-red-950/20'];
            }
            const delay = itemDelays.value.get(item.id) || 0;
            if (delay > 10) {
                return [item.id, 'border-red-300 bg-red-50 dark:border-red-800 dark:bg-red-950/20'];
            } else if (delay > 5) {
                return [item.id, 'border-yellow-300 bg-yellow-50 dark:border-yellow-800 dark:bg-yellow-950/20'];
            }
            return [item.id, ''];
        })
    );
});

function formatDate(date: string) {
    return new Date(date).toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function getDelayMinutes(itemId: string): number {
    return itemDelays.value.get(itemId) || 0;
}

function getCardColorClass(itemId: string): string {
    return itemColors.value.get(itemId) || '';
}

function supply(itemId: string) {
    const item = realtimeItemsRaw.value.find(i => i.id === itemId);
    if (!item) return;
    
    selectedItem.value = item;
    showSupplyDialog.value = true;
}

function checkLocation(itemId: string) {
    router.post(pickCommandItem.url({ requestItem: itemId }), {}, {
        preserveScroll: true,
    });
}

function handleSupplyConfirmed(itemId: string, scannedPartNumber: string, qty: number) {
    // Remove from local state immediately for instant feedback
    const index = realtimeItemsRaw.value.findIndex(item => item.id === itemId);
    if (index !== -1) {
        realtimeItemsRaw.value.splice(index, 1);
    }
    
    // Close dialog
    showSupplyDialog.value = false;
    selectedItem.value = null;
    
    // Submit to backend with scanned part number and quantity
    router.post(supplyItem.url({ requestItem: itemId }), 
        { 
            scanned_part_number: scannedPartNumber,
            qty: qty
        },
        {
            preserveScroll: true,
            onError: () => {
                // Re-fetch on error
                router.reload({ only: ['requestItems'] });
            }
        }
    );
}

async function handleGrantPermission() {
    const granted = await requestPermission();
    if (granted) {
        voiceEnabled.value = true;
        // Announce existing items immediately when voice is first enabled
        announceExistingItems();
    }
}

function toggleVoice() {
    if (voiceEnabled.value) {
        voiceEnabled.value = false;
        voiceQueue.value = []; // Clear queue when disabling
    } else {
        showPermissionModal.value = true;
    }
}

// Process voice queue with priority for urgent items
async function processVoiceQueue() {
    if (isAnnouncing.value || voiceQueue.value.length === 0 || !voiceEnabled.value) return;
    
    isAnnouncing.value = true;
    
    // Queue is already sorted, just take the first item
    const queueItem = voiceQueue.value.shift();
    if (queueItem) {
        try {
            // Use remindItem for reminders, announceItem for new items
            if (queueItem.isReminder) {
                await remindItem(queueItem.item);
            } else {
                await announceItem(queueItem.item);
            }
            // Small delay between announcements
            await new Promise(resolve => setTimeout(resolve, 500));
        } catch (error) {
            console.error('Voice announcement failed:', error);
        }
    }
    
    isAnnouncing.value = false;
    
    // Process next item if queue not empty
    if (voiceQueue.value.length > 0) {
        processVoiceQueue();
    }
}

// Add item to voice queue with priority handling (maintains sorted order)
function queueVoiceAnnouncement(item: RequestItem, isNewItem = false, isReminder = false) {
    if (!voiceEnabled.value) return;
    
    // Check if item is already being announced (avoid duplicates in queue)
    if (voiceQueue.value.some(q => q.item.id === item.id)) {
        // If item is urgent and exists in queue, move it to front
        if (item.is_urgent) {
            voiceQueue.value = voiceQueue.value.filter(q => q.item.id !== item.id);
            voiceQueue.value.unshift({ item, isReminder });
        }
        return;
    }
    
    // Queue overflow protection
    if (voiceQueue.value.length >= MAX_QUEUE_SIZE) {
        console.warn('Voice queue full, dropping oldest normal priority item');
        // Remove oldest normal priority item to make room
        const normalIndex = voiceQueue.value.findIndex(q => !q.item.is_urgent);
        if (normalIndex !== -1) {
            voiceQueue.value.splice(normalIndex, 1);
        } else {
            // Queue full with urgent items only, skip this announcement
            return;
        }
    }
    
    const queueItem: QueueItem = { item, isReminder };
    
    // NEW urgent items from realtime updates get highest priority
    if (isNewItem && item.is_urgent) {
        // Add to absolute front (interrupt even other urgent items from reminders)
        voiceQueue.value.unshift(queueItem);
        
        // If currently announcing a non-urgent item, we could interrupt it
        // but for now, it will be next in queue
    } else if (item.is_urgent) {
        // Urgent items from reminders: insert after new urgent items
        const firstNonUrgentIndex = voiceQueue.value.findIndex(q => !q.item.is_urgent);
        if (firstNonUrgentIndex === -1) {
            voiceQueue.value.push(queueItem);
        } else {
            voiceQueue.value.splice(firstNonUrgentIndex, 0, queueItem);
        }
    } else {
        // Add normal/delayed items at the end
        voiceQueue.value.push(queueItem);
    }
    
    // Start processing
    processVoiceQueue();
}

function handleNewRequestItem(data: { item: RequestItem }) {
    const newItem = data.item;
    
    // Check if item already exists (avoid duplicates)
    if (realtimeItemsRaw.value.some(item => item.id === newItem.id)) return;

    // Simply add to array, computed property will sort it
    realtimeItemsRaw.value.push(newItem);

    // Queue for voice announcement with highest priority for new items
    queueVoiceAnnouncement(newItem, true);
}

// Listen for realtime updates using useEchoPublic
useEchoPublic<{ item: RequestItem }>('request-items', 'RequestItemCreated', handleNewRequestItem);

// Announce existing items when voice is first enabled
function announceExistingItems() {
    if (!voiceEnabled.value || realtimeItemsRaw.value.length === 0) return;
    
    const urgentItems: RequestItem[] = [];
    const delayedItems: RequestItem[] = [];
    const normalItems: RequestItem[] = [];
    
    // Categorize items in single pass
    realtimeItemsRaw.value.forEach((item) => {
        if (item.is_urgent) {
            urgentItems.push(item);
        } else {
            const delay = itemDelays.value.get(item.id) || 0;
            if (delay > 5) {
                delayedItems.push(item);
            } else {
                normalItems.push(item);
            }
        }
    });
    
    // Queue all items (urgent first, then delayed, then normal)
    [...urgentItems, ...delayedItems, ...normalItems].forEach(item => {
        queueVoiceAnnouncement(item, true);
    });
}

// Check for items that need reminders (optimized with priority queueing)
function checkReminders() {
    if (!voiceEnabled.value || realtimeItemsRaw.value.length === 0) return;
    
    const urgentItems: RequestItem[] = [];
    const delayedItems: RequestItem[] = [];
    
    // Categorize items in single pass
    realtimeItemsRaw.value.forEach((item) => {
        if (item.is_urgent) {
            urgentItems.push(item);
        } else {
            const delay = itemDelays.value.get(item.id) || 0;
            if (delay > 5) {
                delayedItems.push(item);
            }
        }
    });
    
    // Queue all reminders with isReminder=true (urgent first, then delayed)
    [...urgentItems, ...delayedItems].forEach(item => {
        queueVoiceAnnouncement(item, false, true);
    });
}

// Initialize voice system on mount
onMounted(() => {
    initialize().then(() => {
        if (!voiceState.value.permissionGranted) {
            showPermissionModal.value = true;
        } else {
            voiceEnabled.value = true;
            // Announce existing items immediately when page loads with voice permission
            announceExistingItems();
        }
    });
    
    // Update current time every minute to trigger delay recalculations
    timeUpdateInterval = setInterval(() => {
        currentTime.value = Date.now();
    }, 60000);
    
    // Start reminder interval (check every 10 minutes)
    reminderInterval = setInterval(checkReminders, 600000);
});

onUnmounted(() => {
    // Clear time update interval
    if (timeUpdateInterval) {
        clearInterval(timeUpdateInterval);
        timeUpdateInterval = null;
    }
    
    // Clear reminder interval
    if (reminderInterval) {
        clearInterval(reminderInterval);
        reminderInterval = null;
    }
});
</script>

<template>
    <Head title="Requests Management" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <Card>
                <CardHeader class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <CardTitle class="flex items-center gap-2">
                            <FileInput class="h-5 w-5" />
                            Requested Items
                            <Badge v-if="voiceEnabled" variant="outline" class="ml-2 gap-1">
                                <Volume2 class="h-3 w-3" />
                                Voice On
                            </Badge>
                        </CardTitle>
                        <CardDescription>
                            View and manage all requested parts
                        </CardDescription>
                    </div>

                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                        <Button
                            variant="outline"
                            size="icon"
                            @click="toggleVoice"
                            :title="voiceEnabled ? 'Disable voice announcements' : 'Enable voice announcements'"
                            class="relative self-start sm:self-auto"
                        >
                            <Volume2 v-if="voiceEnabled" class="h-4 w-4" />
                            <VolumeX v-else class="h-4 w-4" />
                            
                            <!-- Queue count indicator -->
                            <span 
                                v-if="voiceEnabled && voiceQueue.length > 0" 
                                class="absolute -top-1 -right-1 bg-blue-500 text-white text-xs rounded-full min-w-[1.25rem] h-5 flex items-center justify-center px-1 font-semibold shadow-md"
                            >
                                {{ voiceQueue.length }}
                            </span>
                        </Button>

                        <Button v-if="canManage" as-child class="w-full sm:w-auto">
                            <Link :href="createRequest.url()">
                                <FileInput class="mr-2 h-4 w-4" />
                                New Request
                            </Link>
                        </Button>
                    </div>
                </CardHeader>

                <CardContent>
                    <!-- No Items Message -->
                    <div
                        v-if="realtimeItems.length === 0"
                        class="py-8 text-center text-muted-foreground"
                    >
                        No pending requests found
                    </div>

                    <!-- Items List (Full Width Cards) -->
                    <div class="space-y-3">
                        <Card
                            v-for="item in realtimeItems"
                            :key="item.id"
                            :class="getCardColorClass(item.id)"
                            v-memo="[item.id, item.is_urgent, getDelayMinutes(item.id), item.part.stock]"
                        >
                            <CardContent class="p-4">
                                <div class="flex items-center justify-between gap-4 flex-wrap">
                                    <!-- Part Number -->
                                    <div class="flex-1 min-w-[200px]">
                                        <div class="text-xs font-medium text-muted-foreground">Part Number</div>
                                        <div class="text-lg font-semibold">{{ item.part.part_number }}</div>
                                    </div>

                                    <!-- Stock -->
                                    <div class="flex-1 min-w-[120px]">
                                        <div class="text-xs font-medium text-muted-foreground">Stock</div>
                                        <div 
                                            class="text-base font-medium"
                                            :class="item.part.stock < item.qty ? 'text-red-600' : 'text-green-600'"
                                        >
                                            {{ item.part.stock }} pcs
                                        </div>
                                    </div>

                                    <!-- Destination -->
                                    <div class="flex-1 min-w-[150px]">
                                        <div class="text-xs font-medium text-muted-foreground">Destination</div>
                                        <div class="text-base font-medium">{{ item.request.destination }}</div>
                                    </div>

                                    <!-- Request Time -->
                                    <div class="flex-1 min-w-[180px]">
                                        <div class="text-xs font-medium text-muted-foreground">Requested At</div>
                                        <div class="text-sm font-medium">{{ formatDate(item.request.requested_at) }}</div>
                                    </div>

                                    <!-- Urgency -->
                                    <div class="flex-shrink-0">
                                        <Badge v-if="item.is_urgent" variant="destructive" class="gap-1">
                                            <AlertCircle class="h-3 w-3" />
                                            Urgent
                                        </Badge>
                                        <Badge v-else-if="getDelayMinutes(item.id) > 10" variant="destructive" class="gap-1">
                                            <AlertCircle class="h-3 w-3" />
                                            Delayed
                                        </Badge>
                                        <Badge v-else-if="getDelayMinutes(item.id) > 5" class="gap-1 bg-yellow-500 text-white hover:bg-yellow-600">
                                            <AlertCircle class="h-3 w-3" />
                                            Waiting
                                        </Badge>
                                        <Badge v-else variant="secondary" class="gap-1">
                                            Normal
                                        </Badge>
                                    </div>

                                    <!-- Status -->
                                    <div class="flex-shrink-0">
                                        <Badge variant="outline" class="gap-1">
                                            Pending
                                        </Badge>
                                    </div>

                                    <!-- Action Button -->
                                    <div v-if="canSupply" class="flex flex-wrap gap-2 flex-shrink-0">
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            @click="checkLocation(item.id)"
                                        >
                                            <MapPin class="mr-1 h-4 w-4" />
                                            Check Location
                                        </Button>
                                        <Button
                                            size="sm"
                                            @click="supply(item.id)"
                                        >
                                            <PackageCheck class="mr-1 h-4 w-4" />
                                            Supply
                                        </Button>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Pagination -->
                    <div
                        v-if="props.requestItems.last_page > 1"
                        class="mt-6 flex justify-center gap-2"
                    >
                        <Link
                            v-for="link in props.requestItems.links"
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

        <!-- Voice Permission Modal -->
        <VoicePermissionModal
            v-model:open="showPermissionModal"
            @grant="handleGrantPermission"
        />

        <!-- Supply Confirmation Dialog -->
        <SupplyConfirmationDialog
            v-if="selectedItem"
            v-model:open="showSupplyDialog"
            :part-number="selectedItem.part.part_number"
            :part-name="selectedItem.part.part_name"
            :item-id="selectedItem.id"
            :requested-qty="selectedItem.qty"
            :available-stock="selectedItem.part.stock"
            @confirmed="handleSupplyConfirmed"
        />
    </AppLayout>
</template>
