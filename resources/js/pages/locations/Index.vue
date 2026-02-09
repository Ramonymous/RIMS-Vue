<script setup lang="ts">
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useForm } from '@inertiajs/vue3';
import { checkLocations } from '@/actions/App/Http/Controllers/RequestsController';

const form = useForm({ part_number: '' });
const result = ref<string | null>(null);
const error = ref<string | null>(null);

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
</script>

<template>
    <Head title="Check Part Location" />
    <AppLayout>
        <div class="flex flex-1 flex-col items-center justify-center p-4">
            <Card class="w-full max-w-md">
                <CardHeader>
                    <CardTitle>Check Part Location</CardTitle>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit">
                        <Input
                            v-model="form.part_number"
                            placeholder="Enter part number"
                            class="mb-4"
                            autofocus
                        />
                        <Button type="submit" class="w-full">Check Location</Button>
                    </form>
                    <div v-if="result" class="mt-4 text-green-600 font-semibold">
                        {{ result }}
                    </div>
                    <div v-if="error" class="mt-4 text-red-600">
                        {{ error }}
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
