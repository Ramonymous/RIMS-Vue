<script setup lang="ts">
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Upload } from 'lucide-vue-next';
import { importMethod } from '@/actions/App/Http/Controllers/PartsController';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface Emits {
    (e: 'success'): void;
}

const emit = defineEmits<Emits>();

const isOpen = ref(false);
const importFile = ref<File | null>(null);
const fileInputRef = ref<HTMLInputElement | null>(null);

const importForm = useForm({
    file: null as File | null,
});

const handleFileChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        importFile.value = target.files[0];
        importForm.file = target.files[0];
    }
};

const submitImport = () => {
    if (!importForm.file) return;

    importForm.post(importMethod.url(), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            isOpen.value = false;
            importForm.reset();
            importFile.value = null;
            if (fileInputRef.value) {
                fileInputRef.value.value = '';
            }
            emit('success');
        },
    });
};

const handleOpenChange = (open: boolean) => {
    isOpen.value = open;
    if (!open) {
        importForm.reset();
        importFile.value = null;
        if (fileInputRef.value) {
            fileInputRef.value.value = '';
        }
    }
};
</script>

<template>
    <Dialog :open="isOpen" @update:open="handleOpenChange">
        <DialogTrigger as-child>
            <Button variant="outline" size="sm" class="gap-2">
                <Upload class="h-4 w-4" />
                <span class="hidden sm:inline">Import</span>
            </Button>
        </DialogTrigger>
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Import Parts from Excel</DialogTitle>
                <DialogDescription>
                    Upload an Excel file to bulk import parts. Download the template to see
                    the required format.
                </DialogDescription>
            </DialogHeader>
            <form @submit.prevent="submitImport">
                <div class="grid gap-4 py-4">
                    <div class="grid gap-2">
                        <Label for="import-file">
                            Excel File
                            <span class="text-destructive">*</span>
                        </Label>
                        <Input
                            id="import-file"
                            ref="fileInputRef"
                            type="file"
                            accept=".xlsx,.xls,.csv"
                            @change="handleFileChange"
                            required
                        />
                        <span v-if="importForm.errors.file" class="text-sm text-destructive">
                            {{ importForm.errors.file }}
                        </span>
                        <p class="text-sm text-muted-foreground">
                            Accepted formats: .xlsx, .xls, .csv (Max 10MB)
                        </p>
                    </div>
                    <div
                        v-if="importFile"
                        class="rounded-md bg-muted p-3 text-sm"
                    >
                        Selected: {{ importFile.name }} ({{
                            (importFile.size / 1024).toFixed(2)
                        }}
                        KB)
                    </div>
                </div>
                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="isOpen = false"
                    >
                        Cancel
                    </Button>
                    <Button
                        type="submit"
                        :disabled="importForm.processing || !importFile"
                    >
                        {{ importForm.processing ? 'Importing...' : 'Import' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
