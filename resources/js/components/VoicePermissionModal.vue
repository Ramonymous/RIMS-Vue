<script setup lang="ts">
import { Volume2 } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

interface Props {
    open: boolean;
}

interface Emits {
    (e: 'update:open', value: boolean): void;
    (e: 'grant'): void;
}

defineProps<Props>();
const emit = defineEmits<Emits>();

function handleGrant() {
    emit('grant');
    emit('update:open', false);
}

function handleDismiss() {
    emit('update:open', false);
}
</script>

<template>
    <Dialog :open="open" @update:open="(value) => emit('update:open', value)">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                    <Volume2 class="h-5 w-5" />
                    Enable Voice Announcements
                </DialogTitle>
                <DialogDescription>
                    Allow voice announcements for new part requests. This will
                    help you stay informed about urgent and normal requests
                    without constantly checking the screen.
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-3 py-4">
                <div class="flex items-start gap-3">
                    <div
                        class="mt-1 flex h-8 w-8 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/20"
                    >
                        <span
                            class="text-sm font-semibold text-red-600 dark:text-red-400"
                            >!</span
                        >
                    </div>
                    <div>
                        <p class="font-medium">Urgent Requests</p>
                        <p class="text-sm text-muted-foreground">
                            Higher priority with faster speech
                        </p>
                    </div>
                </div>

                <div class="flex items-start gap-3">
                    <div
                        class="mt-1 flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/20"
                    >
                        <span
                            class="text-sm font-semibold text-blue-600 dark:text-blue-400"
                            >i</span
                        >
                    </div>
                    <div>
                        <p class="font-medium">Normal Requests</p>
                        <p class="text-sm text-muted-foreground">
                            Standard announcements for new items
                        </p>
                    </div>
                </div>
            </div>

            <DialogFooter class="flex-col gap-2 sm:flex-row sm:justify-end">
                <Button variant="outline" @click="handleDismiss">
                    Not Now
                </Button>
                <Button @click="handleGrant">
                    <Volume2 class="mr-2 h-4 w-4" />
                    Enable Sound
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
