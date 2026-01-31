<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import {
    Package,
    PackagePlus,
    PackageMinus,
    TrendingUp,
    TrendingDown,
    AlertCircle,
    CheckCircle,
    Clock,
    ArrowRight,
    BarChart3,
} from 'lucide-vue-next';
import { VisArea, VisAxis, VisDonut, VisLine, VisSingleContainer, VisStackedBar, VisXYContainer } from '@unovis/vue';
import { Donut } from '@unovis/ts';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import {
    ChartContainer,
    ChartCrosshair,
    ChartLegendContent,
    ChartTooltip,
    ChartTooltipContent,
    componentToString,
    type ChartConfig,
} from '@/components/ui/chart';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type Activity = {
    id: string;
    part: {
        part_number: string;
        part_name: string;
    };
    type: 'in' | 'out';
    qty: number;
    stock_before: number;
    stock_after: number;
    created_at: string;
};

type Part = {
    part_number: string;
    part_name: string;
    stock: number;
};

type Props = {
    stats: {
        totalParts: number;
        activeParts: number;
        totalStock: number;
        lowStockCount: number;
        outOfStockCount: number;
        totalReceivings: number;
        pendingReceivings: number;
        totalOutgoings: number;
        pendingOutgoings: number;
    };
    trend: {
        incomingTotal: number;
        outgoingTotal: number;
        incomingPct: number;
        outgoingPct: number;
    };
    monthlyData: Array<{
        date: string;
        incoming: number;
        outgoing: number;
    }>;
    recentActivities: Activity[];
    topPartsByStock: Part[];
    stockDistribution: Array<{
        status: string;
        count: number;
    }>;
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/',
    },
];

// Chart configuration
const chartConfig = {
    incoming: {
        label: 'Incoming',
        color: 'hsl(var(--chart-1))',
    },
    outgoing: {
        label: 'Outgoing',
        color: 'hsl(var(--chart-2))',
    },
} satisfies ChartConfig;

// Donut chart configuration for stock distribution
const donutChartConfig = {
    parts: {
        label: 'Parts',
        color: undefined,
    },
    available: {
        label: 'Available',
        color: 'hsl(142, 76%, 36%)', // green-600
    },
    lowStock: {
        label: 'Low Stock',
        color: 'hsl(25, 95%, 53%)', // orange-500
    },
    outOfStock: {
        label: 'Out of Stock',
        color: 'hsl(0, 84%, 60%)', // red-500
    },
} satisfies ChartConfig;

// Transform stock distribution data for donut chart
type DonutDataPoint = {
    status: string;
    parts: number;
    fill: string;
};

const donutChartData = computed<DonutDataPoint[]>(() => {
    return props.stockDistribution.map(item => {
        let fill = '';
        if (item.status === 'Available') fill = donutChartConfig.available.color;
        else if (item.status === 'Low Stock') fill = donutChartConfig.lowStock.color;
        else if (item.status === 'Out of Stock') fill = donutChartConfig.outOfStock.color;
        
        return {
            status: item.status,
            parts: item.count,
            fill,
        };
    });
});

const timeRange = ref('monthly');

// Transform daily data for chart
type ChartDataPoint = {
    date: Date;
    dateStr: string;
    incoming: number;
    outgoing: number;
};

const chartData = computed<ChartDataPoint[]>(() => {
    return props.monthlyData.map(item => ({
        date: new Date(item.date),
        dateStr: item.date,
        incoming: item.incoming,
        outgoing: item.outgoing,
    }));
});

const filterRange = computed(() => {
    if (!chartData.value.length) return [];
    
    const today = new Date();
    today.setHours(23, 59, 59, 999); // End of today
    
    let daysToShow = 30;
    if (timeRange.value === 'weekly') {
        daysToShow = 7;
    } else if (timeRange.value === 'monthly') {
        daysToShow = 30;
    }
    
    const startDate = new Date(today);
    startDate.setDate(startDate.getDate() - daysToShow + 1); // Include today
    startDate.setHours(0, 0, 0, 0); // Start of day
    
    return chartData.value.filter((item) => {
        return item.date >= startDate && item.date <= today;
    });
});

function formatDate(date: string) {
    return new Date(date).toLocaleString('en-US', {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function navigateTo(route: string) {
    router.visit(route);
}

function formatTrend(value: number): string {
    const sign = value > 0 ? '+' : '';
    return `${sign}${value}%`;
}
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Header -->
            <Card class="overflow-hidden border-none bg-gradient-to-r from-primary/10 via-background to-background shadow-sm">
                <CardContent class="flex flex-col gap-4 p-6 sm:flex-row sm:items-center sm:justify-between">
                    <div class="space-y-1">
                        <h1 class="text-2xl font-bold tracking-tight">Dashboard</h1>
                        <p class="text-sm text-muted-foreground">
                            Overview of your inventory management system
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <Badge variant="secondary" class="gap-2 px-3 py-1">
                            <TrendingUp class="h-4 w-4 text-emerald-500" />
                            Incoming 7d: {{ trend.incomingTotal.toLocaleString() }}
                            <span class="text-emerald-600">({{ formatTrend(trend.incomingPct) }})</span>
                        </Badge>
                        <Badge variant="secondary" class="gap-2 px-3 py-1">
                            <TrendingDown class="h-4 w-4 text-rose-500" />
                            Outgoing 7d: {{ trend.outgoingTotal.toLocaleString() }}
                            <span class="text-rose-600">({{ formatTrend(trend.outgoingPct) }})</span>
                        </Badge>
                    </div>
                </CardContent>
            </Card>

            <!-- KPI Cards Row 1 - Inventory -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <Card class="hover-lift border-l-4 border-l-blue-500 bg-card/80">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total Parts</CardTitle>
                        <div class="rounded-full bg-blue-100 p-2 dark:bg-blue-950">
                            <Package class="h-4 w-4 text-blue-600 dark:text-blue-400" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold">{{ stats.totalParts.toLocaleString() }}</div>
                        <p class="mt-1 text-xs text-muted-foreground">
                            <span class="font-medium text-green-600">{{ stats.activeParts }}</span> active parts
                        </p>
                    </CardContent>
                </Card>

                <Card class="hover-lift border-l-4 border-l-purple-500 bg-card/80">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total Stock</CardTitle>
                        <div class="rounded-full bg-purple-100 p-2 dark:bg-purple-950">
                            <BarChart3 class="h-4 w-4 text-purple-600 dark:text-purple-400" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold">{{ stats.totalStock.toLocaleString() }}</div>
                        <p class="mt-1 text-xs text-muted-foreground">
                            Units in inventory
                        </p>
                    </CardContent>
                </Card>

                <Card class="hover-lift border-l-4 border-l-orange-500 bg-card/80">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Low Stock</CardTitle>
                        <div class="rounded-full bg-orange-100 p-2 dark:bg-orange-950">
                            <AlertCircle class="h-4 w-4 text-orange-600 dark:text-orange-400" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold text-orange-600 dark:text-orange-400">{{ stats.lowStockCount }}</div>
                        <p class="mt-1 text-xs text-muted-foreground">
                            Parts below threshold
                        </p>
                    </CardContent>
                </Card>

                <Card class="hover-lift border-l-4 border-l-red-500 bg-card/80">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Out of Stock</CardTitle>
                        <div class="rounded-full bg-red-100 p-2 dark:bg-red-950">
                            <AlertCircle class="h-4 w-4 text-red-600 dark:text-red-400" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold text-red-600 dark:text-red-400">{{ stats.outOfStockCount }}</div>
                        <p class="mt-1 text-xs text-muted-foreground">
                            Parts need restocking
                        </p>
                    </CardContent>
                </Card>
            </div>

            <!-- KPI Cards Row 2 - Operations -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <Card class="hover-lift cursor-pointer border-l-4 border-l-green-500 bg-card/80" @click="navigateTo('/receivings')">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total Receivings</CardTitle>
                        <div class="rounded-full bg-green-100 p-2 dark:bg-green-950">
                            <PackagePlus class="h-4 w-4 text-green-600 dark:text-green-400" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold">{{ stats.totalReceivings }}</div>
                        <p class="mt-1 text-xs text-muted-foreground">
                            <span class="font-medium text-orange-600">{{ stats.pendingReceivings }}</span> pending GR
                        </p>
                    </CardContent>
                </Card>

                <Card class="hover-lift cursor-pointer border-l-4 border-l-red-500 bg-card/80" @click="navigateTo('/outgoings')">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total Outgoings</CardTitle>
                        <div class="rounded-full bg-red-100 p-2 dark:bg-red-950">
                            <PackageMinus class="h-4 w-4 text-red-600 dark:text-red-400" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold">{{ stats.totalOutgoings }}</div>
                        <p class="mt-1 text-xs text-muted-foreground">
                            <span class="font-medium text-orange-600">{{ stats.pendingOutgoings }}</span> pending GI
                        </p>
                    </CardContent>
                </Card>

                <Card class="hover-lift cursor-pointer bg-card/80" @click="navigateTo('/receivings/create')">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Quick Action</CardTitle>
                        <div class="rounded-full bg-primary/10 p-2">
                            <PackagePlus class="h-4 w-4 text-primary" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <Button class="w-full" variant="default" size="sm">
                            <PackagePlus class="mr-2 h-4 w-4" />
                            Create Receiving
                        </Button>
                    </CardContent>
                </Card>

                <Card class="hover-lift cursor-pointer bg-card/80" @click="navigateTo('/outgoings/create')">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Quick Action</CardTitle>
                        <div class="rounded-full bg-primary/10 p-2">
                            <PackageMinus class="h-4 w-4 text-primary" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <Button class="w-full" variant="default" size="sm">
                            <PackageMinus class="mr-2 h-4 w-4" />
                            Create Outgoing
                        </Button>
                    </CardContent>
                </Card>
            </div>

            <!-- Chart Section - Full Width -->
            <Card class="shadow-md">
                <CardHeader class="flex items-center gap-2 space-y-0 border-b bg-muted/30 py-5 sm:flex-row">
                    <div class="grid flex-1 gap-1">
                        <div class="flex items-center gap-2">
                            <div class="rounded-full bg-primary/10 p-2">
                                <BarChart3 class="h-5 w-5 text-primary" />
                            </div>
                            <div>
                                <CardTitle>Daily Inventory Trends</CardTitle>
                                <CardDescription>Track incoming vs outgoing movements</CardDescription>
                            </div>
                        </div>
                    </div>
                    <Select v-model="timeRange">
                        <SelectTrigger
                            class="w-[180px] rounded-lg sm:ml-auto"
                            aria-label="Select time range"
                        >
                            <SelectValue placeholder="Last 30 days" />
                        </SelectTrigger>
                        <SelectContent class="rounded-xl">
                            <SelectItem value="weekly" class="rounded-lg">
                                <div class="flex items-center gap-2">
                                    <Clock class="h-4 w-4" />
                                    <span>Last 7 days</span>
                                </div>
                            </SelectItem>
                            <SelectItem value="monthly" class="rounded-lg">
                                <div class="flex items-center gap-2">
                                    <Clock class="h-4 w-4" />
                                    <span>Last 30 days</span>
                                </div>
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </CardHeader>
                <CardContent class="px-2 pt-6 sm:px-6 pb-6">
                    <ChartContainer
                        :config="chartConfig"
                        class="aspect-auto h-[400px] w-full"
                        :cursor="false"
                    >
                        <VisXYContainer
                            :data="filterRange"
                            :margin="{ left: -30, right: 10, top: 10, bottom: 10 }"
                        >
                            <VisLine
                                :x="(d: ChartDataPoint) => d.date"
                                :y="[(d: ChartDataPoint) => d.incoming, (d: ChartDataPoint) => d.outgoing]"
                                :color="[chartConfig.incoming.color, chartConfig.outgoing.color]"
                                :line-width="3"
                                :curve-type="'monotoneX'"
                            />
                            <VisAxis
                                type="x"
                                :x="(d: ChartDataPoint) => d.date"
                                :tick-line="false"
                                :domain-line="false"
                                :grid-line="false"
                                :num-ticks="timeRange === 'weekly' ? 7 : 10"
                                :tick-format="(d: number) => {
                                    const date = new Date(d);
                                    return date.toLocaleDateString('en-US', {
                                        month: 'short',
                                        day: 'numeric',
                                    });
                                }"
                            />
                            <VisAxis
                                type="y"
                                :num-ticks="6"
                                :tick-line="false"
                                :domain-line="false"
                                :grid-line="true"
                            />
                            <ChartTooltip />
                            <ChartCrosshair
                                :template="componentToString(chartConfig, ChartTooltipContent, {
                                    labelFormatter: (d: any) => {
                                        return new Date(d).toLocaleDateString('en-US', {
                                            weekday: 'short',
                                            month: 'short',
                                            day: 'numeric',
                                            year: 'numeric',
                                        });
                                    },
                                })"
                                :color="(d: ChartDataPoint, i: number) => [chartConfig.incoming.color, chartConfig.outgoing.color][i % 2]"
                            />
                        </VisXYContainer>
                        <ChartLegendContent />
                    </ChartContainer>
                </CardContent>
            </Card>

            <!-- Additional Info Cards -->
            <div class="grid gap-4 lg:grid-cols-2">
                <!-- Top Parts by Stock -->
                <Card>
                    <CardHeader class="border-b">
                        <div class="flex items-center justify-between">
                            <div>
                                <CardTitle>Top Parts by Stock</CardTitle>
                                <CardDescription>Parts with highest inventory levels</CardDescription>
                            </div>
                            <Package class="h-5 w-5 text-muted-foreground" />
                        </div>
                    </CardHeader>
                    <CardContent class="pt-6">
                        <div class="space-y-3">
                            <div
                                v-for="(part, index) in topPartsByStock"
                                :key="index"
                                class="flex items-center justify-between rounded-lg border p-3 transition-colors hover:bg-muted/50"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-primary/10">
                                        <span class="text-xs font-bold text-primary">#{{ index + 1 }}</span>
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-sm font-semibold">{{ part.part_number }}</p>
                                        <p class="text-xs text-muted-foreground truncate">{{ part.part_name }}</p>
                                    </div>
                                </div>
                                <Badge variant="secondary" class="ml-2 font-semibold">
                                    {{ part.stock.toLocaleString() }}
                                </Badge>
                            </div>
                            <Button
                                variant="outline"
                                class="mt-4 w-full"
                                size="sm"
                                @click="navigateTo('/stock')"
                            >
                                View All Stock
                                <ArrowRight class="ml-2 h-4 w-4" />
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <!-- Stock Distribution -->
                <Card class="flex flex-col">
                    <CardHeader class="border-b items-center">
                        <div class="flex items-center justify-between w-full">
                            <div>
                                <CardTitle>Stock Distribution</CardTitle>
                                <CardDescription>Overview of inventory status</CardDescription>
                            </div>
                            <BarChart3 class="h-5 w-5 text-muted-foreground" />
                        </div>
                    </CardHeader>
                    <CardContent class="flex-1 pt-6 pb-4">
                        <ChartContainer
                            :config="donutChartConfig"
                            class="mx-auto aspect-square max-h-[280px]"
                        >
                            <VisSingleContainer
                                :data="donutChartData"
                                :margin="{ top: 20, bottom: 20 }"
                            >
                                <VisDonut
                                    :value="(d: DonutDataPoint) => d.parts"
                                    :color="(d: DonutDataPoint) => d.fill"
                                    :arc-width="80"
                                />
                                <ChartTooltip
                                    :triggers="{
                                        [Donut.selectors.segment]: componentToString(donutChartConfig, ChartTooltipContent, { 
                                            nameKey: 'status'
                                        })!,
                                    }"
                                />
                            </VisSingleContainer>
                        </ChartContainer>
                        
                        <!-- Legend -->
                        <div class="mt-4 space-y-2">
                            <div
                                v-for="(item, index) in donutChartData"
                                :key="index"
                                class="flex items-center justify-between text-sm"
                            >
                                <div class="flex items-center gap-2">
                                    <div
                                        class="h-3 w-3 rounded-full"
                                        :style="{ backgroundColor: item.fill }"
                                    />
                                    <span class="font-medium">{{ item.status }}</span>
                                </div>
                                <span class="font-bold">{{ item.parts }} parts</span>
                            </div>
                        </div>
                    </CardContent>
                    <CardFooter class="flex-col gap-2 border-t pt-4 text-sm">
                        <div class="flex items-center justify-between w-full">
                            <span class="text-muted-foreground">Total Parts</span>
                            <span class="text-2xl font-bold">{{ stats.totalParts }}</span>
                        </div>
                        <div class="text-center text-muted-foreground">
                            Real-time inventory status distribution
                        </div>
                    </CardFooter>
                </Card>
            </div>

            <!-- Recent Activities -->
            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle>Recent Activities</CardTitle>
                            <CardDescription>Latest stock movements and transactions</CardDescription>
                        </div>
                        <Button variant="outline" size="sm" @click="navigateTo('/stock?tab=movements')">
                            View All
                            <ArrowRight class="ml-2 h-4 w-4" />
                        </Button>
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="space-y-3">
                        <div
                            v-for="activity in recentActivities"
                            :key="activity.id"
                            class="flex items-center justify-between rounded-lg border p-3"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex h-10 w-10 items-center justify-center rounded-full"
                                    :class="activity.type === 'in' ? 'bg-green-100 dark:bg-green-950' : 'bg-red-100 dark:bg-red-950'"
                                >
                                    <PackagePlus
                                        v-if="activity.type === 'in'"
                                        class="h-5 w-5 text-green-600 dark:text-green-400"
                                    />
                                    <PackageMinus
                                        v-else
                                        class="h-5 w-5 text-red-600 dark:text-red-400"
                                    />
                                </div>
                                <div>
                                    <p class="text-sm font-medium">{{ activity.part.part_number }}</p>
                                    <p class="text-xs text-muted-foreground">{{ activity.part.part_name }}</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <Badge :variant="activity.type === 'in' ? 'default' : 'destructive'">
                                    {{ activity.type === 'in' ? '+' : '-' }}{{ activity.qty }}
                                </Badge>
                                <p class="mt-1 text-xs text-muted-foreground">
                                    {{ formatDate(activity.created_at) }}
                                </p>
                            </div>
                        </div>

                        <div
                            v-if="recentActivities.length === 0"
                            class="rounded-lg border border-dashed p-8 text-center text-muted-foreground"
                        >
                            No recent activities
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
