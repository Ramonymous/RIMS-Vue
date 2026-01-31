<script setup lang="ts">
import { Card, CardContent } from '@/components/ui/card';
import { TrendingUp, TrendingDown } from 'lucide-vue-next';

type Props = {
    label: string;
    value: string | number;
    icon?: any;
    trend?: 'up' | 'down';
    trendValue?: string;
    description?: string;
    variant?: 'default' | 'primary' | 'success' | 'warning' | 'danger';
    clickable?: boolean;
};

const props = withDefaults(defineProps<Props>(), {
    variant: 'default',
    clickable: false,
});

const emit = defineEmits<{
    click: [];
}>();

const variantClasses = {
    default: 'border-l-4 border-l-primary',
    primary: 'border-l-4 border-l-blue-500',
    success: 'border-l-4 border-l-green-500',
    warning: 'border-l-4 border-l-orange-500',
    danger: 'border-l-4 border-l-red-500',
};

const iconBgClasses = {
    default: 'bg-primary/10',
    primary: 'bg-blue-100 dark:bg-blue-950',
    success: 'bg-green-100 dark:bg-green-950',
    warning: 'bg-orange-100 dark:bg-orange-950',
    danger: 'bg-red-100 dark:bg-red-950',
};

const iconColorClasses = {
    default: 'text-primary',
    primary: 'text-blue-600 dark:text-blue-400',
    success: 'text-green-600 dark:text-green-400',
    warning: 'text-orange-600 dark:text-orange-400',
    danger: 'text-red-600 dark:text-red-400',
};
</script>

<template>
    <Card 
        :class="[
            variantClasses[variant],
            clickable ? 'cursor-pointer hover-lift' : '',
            'transition-all duration-200 animate-in slide-in-from-bottom'
        ]"
        @click="clickable ? emit('click') : null"
    >
        <CardContent class="p-6">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <p class="text-sm font-medium text-muted-foreground mb-1">{{ label }}</p>
                    <div class="flex items-baseline gap-2">
                        <p class="text-3xl font-bold tracking-tight">{{ value }}</p>
                        <div v-if="trend" class="flex items-center gap-1 text-sm">
                            <TrendingUp v-if="trend === 'up'" class="h-4 w-4 text-green-600" />
                            <TrendingDown v-if="trend === 'down'" class="h-4 w-4 text-red-600" />
                            <span :class="trend === 'up' ? 'text-green-600' : 'text-red-600'">
                                {{ trendValue }}
                            </span>
                        </div>
                    </div>
                    <p v-if="description" class="text-xs text-muted-foreground mt-2">
                        {{ description }}
                    </p>
                </div>
                <div 
                    v-if="icon"
                    :class="[iconBgClasses[variant], 'rounded-full p-3']"
                >
                    <component :is="icon" :class="[iconColorClasses[variant], 'h-5 w-5']" />
                </div>
            </div>
        </CardContent>
    </Card>
</template>
