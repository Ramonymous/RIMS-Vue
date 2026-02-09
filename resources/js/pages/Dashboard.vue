<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Donut } from '@unovis/ts';
import {
    VisAxis,
    VisDonut,
    VisLine,
    VisSingleContainer,
    VisXYContainer,
} from '@unovis/vue';
import {
    Package,
    PackagePlus,
    PackageMinus,
    TrendingUp,
    TrendingDown,
    AlertCircle,
    Clock,
    ArrowRight,
    BarChart3,
    Sparkles,
    Activity,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
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
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

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
    return props.stockDistribution.map((item) => {
        let fill = '';
        if (item.status === 'Available')
            fill = donutChartConfig.available.color;
        else if (item.status === 'Low Stock')
            fill = donutChartConfig.lowStock.color;
        else if (item.status === 'Out of Stock')
            fill = donutChartConfig.outOfStock.color;

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
    return props.monthlyData.map((item) => ({
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
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4 sm:p-6">
            <!-- Hero Header with Animated Gradient -->
            <div class="dashboard-header group relative overflow-hidden rounded-3xl">
                <div class="dashboard-gradient"></div>
                <div class="dashboard-noise"></div>
                
                <Card class="relative overflow-hidden border-none bg-transparent shadow-2xl">
                    <CardContent class="relative z-10 flex flex-col gap-6 p-8 sm:flex-row sm:items-center sm:justify-between sm:p-10">
                        <div class="space-y-3">
                            <div class="flex items-center gap-3">
                                <div class="animate-float">
                                    <Sparkles class="h-8 w-8 text-amber-400" />
                                </div>
                                <h1 class="dashboard-title text-4xl font-black tracking-tight text-white sm:text-5xl">
                                    Dashboard
                                </h1>
                            </div>
                            <p class="text-lg text-white/80 sm:text-xl">
                                Real-time insights into your warehouse operations
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-3">
                            <div class="stat-badge stat-badge-success">
                                <TrendingUp class="h-5 w-5" />
                                <div class="flex flex-col">
                                    <span class="text-xs font-medium opacity-80">7d Incoming</span>
                                    <span class="text-lg font-bold">
                                        {{ trend.incomingTotal.toLocaleString() }}
                                        <span class="text-sm">({{ formatTrend(trend.incomingPct) }})</span>
                                    </span>
                                </div>
                            </div>
                            <div class="stat-badge stat-badge-danger">
                                <TrendingDown class="h-5 w-5" />
                                <div class="flex flex-col">
                                    <span class="text-xs font-medium opacity-80">7d Outgoing</span>
                                    <span class="text-lg font-bold">
                                        {{ trend.outgoingTotal.toLocaleString() }}
                                        <span class="text-sm">({{ formatTrend(trend.outgoingPct) }})</span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Primary KPIs - Inventory Metrics -->
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <Card class="kpi-card kpi-card-blue group">
                    <div class="kpi-accent"></div>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-bold uppercase tracking-wider opacity-80">
                            Total Parts
                        </CardTitle>
                        <div class="kpi-icon kpi-icon-blue">
                            <Package class="h-5 w-5" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="kpi-value">
                            {{ stats.totalParts.toLocaleString() }}
                        </div>
                        <div class="mt-2 flex items-center gap-2">
                            <div class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></div>
                            <p class="text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                                {{ stats.activeParts }} active
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <Card class="kpi-card kpi-card-purple group">
                    <div class="kpi-accent"></div>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-bold uppercase tracking-wider opacity-80">
                            Total Stock
                        </CardTitle>
                        <div class="kpi-icon kpi-icon-purple">
                            <BarChart3 class="h-5 w-5" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="kpi-value">
                            {{ stats.totalStock.toLocaleString() }}
                        </div>
                        <p class="mt-2 text-xs font-medium opacity-60">
                            Units in inventory
                        </p>
                    </CardContent>
                </Card>

                <Card class="kpi-card kpi-card-orange group">
                    <div class="kpi-accent"></div>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-bold uppercase tracking-wider opacity-80">
                            Low Stock
                        </CardTitle>
                        <div class="kpi-icon kpi-icon-orange">
                            <AlertCircle class="h-5 w-5" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="kpi-value text-orange-600 dark:text-orange-400">
                            {{ stats.lowStockCount }}
                        </div>
                        <p class="mt-2 text-xs font-medium opacity-60">
                            Below threshold
                        </p>
                    </CardContent>
                </Card>

                <Card class="kpi-card kpi-card-red group">
                    <div class="kpi-accent"></div>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-bold uppercase tracking-wider opacity-80">
                            Out of Stock
                        </CardTitle>
                        <div class="kpi-icon kpi-icon-red">
                            <AlertCircle class="h-5 w-5" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="kpi-value text-red-600 dark:text-red-400">
                            {{ stats.outOfStockCount }}
                        </div>
                        <p class="mt-2 text-xs font-medium opacity-60">
                            Need restocking
                        </p>
                    </CardContent>
                </Card>
            </div>

            <!-- Operations & Quick Actions -->
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <Card 
                    class="action-card action-card-clickable group"
                    @click="navigateTo('/receivings')"
                >
                    <div class="action-card-glow action-card-glow-green"></div>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-bold uppercase tracking-wider">
                            Receivings
                        </CardTitle>
                        <div class="action-icon action-icon-green">
                            <PackagePlus class="h-5 w-5" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-black">
                            {{ stats.totalReceivings }}
                        </div>
                        <div class="mt-2 flex items-center gap-2">
                            <Clock class="h-3.5 w-3.5 text-orange-500" />
                            <p class="text-xs font-bold text-orange-600 dark:text-orange-400">
                                {{ stats.pendingReceivings }} pending GR
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <Card 
                    class="action-card action-card-clickable group"
                    @click="navigateTo('/outgoings')"
                >
                    <div class="action-card-glow action-card-glow-red"></div>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-bold uppercase tracking-wider">
                            Outgoings
                        </CardTitle>
                        <div class="action-icon action-icon-red">
                            <PackageMinus class="h-5 w-5" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-black">
                            {{ stats.totalOutgoings }}
                        </div>
                        <div class="mt-2 flex items-center gap-2">
                            <Clock class="h-3.5 w-3.5 text-orange-500" />
                            <p class="text-xs font-bold text-orange-600 dark:text-orange-400">
                                {{ stats.pendingOutgoings }} pending GI
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <Card 
                    class="action-card action-card-primary group cursor-pointer"
                    @click="navigateTo('/receivings/create')"
                >
                    <div class="action-card-glow action-card-glow-primary"></div>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-xs font-bold uppercase tracking-widest opacity-70">
                            Quick Action
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <Button class="w-full bg-gradient-to-r from-emerald-600 to-green-600 font-bold shadow-lg shadow-emerald-500/30 transition-all hover:scale-105 hover:shadow-xl hover:shadow-emerald-500/40" size="lg">
                            <PackagePlus class="mr-2 h-5 w-5" />
                            New Receiving
                        </Button>
                    </CardContent>
                </Card>

                <Card 
                    class="action-card action-card-primary group cursor-pointer"
                    @click="navigateTo('/outgoings/create')"
                >
                    <div class="action-card-glow action-card-glow-primary"></div>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-xs font-bold uppercase tracking-widest opacity-70">
                            Quick Action
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <Button class="w-full bg-gradient-to-r from-rose-600 to-red-600 font-bold shadow-lg shadow-rose-500/30 transition-all hover:scale-105 hover:shadow-xl hover:shadow-rose-500/40" size="lg">
                            <PackageMinus class="mr-2 h-5 w-5" />
                            New Outgoing
                        </Button>
                    </CardContent>
                </Card>
            </div>

            <!-- Chart Section -->
            <Card class="chart-card overflow-hidden">
                <CardHeader class="chart-header border-b">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-center gap-4">
                            <div class="chart-icon">
                                <Activity class="h-6 w-6" />
                            </div>
                            <div>
                                <CardTitle class="text-2xl font-black">Daily Trends</CardTitle>
                                <CardDescription class="mt-1 text-base">
                                    Tracking inventory movements over time
                                </CardDescription>
                            </div>
                        </div>
                        <Select v-model="timeRange">
                            <SelectTrigger class="w-[180px] rounded-xl border-2 font-semibold">
                                <SelectValue placeholder="Last 30 days" />
                            </SelectTrigger>
                            <SelectContent class="rounded-xl">
                                <SelectItem value="weekly" class="rounded-lg font-semibold">
                                    <div class="flex items-center gap-2">
                                        <Clock class="h-4 w-4" />
                                        <span>Last 7 days</span>
                                    </div>
                                </SelectItem>
                                <SelectItem value="monthly" class="rounded-lg font-semibold">
                                    <div class="flex items-center gap-2">
                                        <Clock class="h-4 w-4" />
                                        <span>Last 30 days</span>
                                    </div>
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </CardHeader>
                <CardContent class="px-2 pt-8 pb-6 sm:px-6">
                    <ChartContainer
                        :config="chartConfig"
                        class="aspect-auto h-[420px] w-full"
                        :cursor="false"
                    >
                        <VisXYContainer
                            :data="filterRange"
                            :margin="{
                                left: -30,
                                right: 10,
                                top: 10,
                                bottom: 10,
                            }"
                        >
                            <VisLine
                                :x="(d: ChartDataPoint) => d.date"
                                :y="[
                                    (d: ChartDataPoint) => d.incoming,
                                    (d: ChartDataPoint) => d.outgoing,
                                ]"
                                :color="[
                                    chartConfig.incoming.color,
                                    chartConfig.outgoing.color,
                                ]"
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
                                :tick-format="
                                    (d: number) => {
                                        const date = new Date(d);
                                        return date.toLocaleDateString(
                                            'en-US',
                                            {
                                                month: 'short',
                                                day: 'numeric',
                                            },
                                        );
                                    }
                                "
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
                                :template="
                                    componentToString(
                                        chartConfig,
                                        ChartTooltipContent,
                                        {
                                            labelFormatter: (d: any) => {
                                                return new Date(
                                                    d,
                                                ).toLocaleDateString('en-US', {
                                                    weekday: 'short',
                                                    month: 'short',
                                                    day: 'numeric',
                                                    year: 'numeric',
                                                });
                                            },
                                        },
                                    )
                                "
                                :color="
                                    (d: ChartDataPoint, i: number) =>
                                        [
                                            chartConfig.incoming.color,
                                            chartConfig.outgoing.color,
                                        ][i % 2]
                                "
                            />
                        </VisXYContainer>
                        <ChartLegendContent />
                    </ChartContainer>
                </CardContent>
            </Card>

            <!-- Secondary Info Grid -->
            <div class="grid gap-4 lg:grid-cols-2">
                <!-- Top Parts -->
                <Card class="info-card">
                    <CardHeader class="border-b">
                        <div class="flex items-center justify-between">
                            <div>
                                <CardTitle class="text-xl font-black">Top Inventory</CardTitle>
                                <CardDescription class="mt-1">
                                    Parts with highest stock levels
                                </CardDescription>
                            </div>
                            <div class="info-badge">
                                <Package class="h-5 w-5" />
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent class="pt-6">
                        <div class="space-y-3">
                            <div
                                v-for="(part, index) in topPartsByStock"
                                :key="index"
                                class="part-item group"
                            >
                                <div class="flex items-center gap-4">
                                    <div class="rank-badge">
                                        <span class="rank-number">#{{ index + 1 }}</span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="truncate font-bold text-foreground">
                                            {{ part.part_number }}
                                        </p>
                                        <p class="truncate text-sm text-muted-foreground">
                                            {{ part.part_name }}
                                        </p>
                                    </div>
                                </div>
                                <Badge class="stock-badge">
                                    {{ part.stock.toLocaleString() }}
                                </Badge>
                            </div>
                            <Button
                                variant="outline"
                                class="mt-6 w-full rounded-xl border-2 font-bold transition-all hover:scale-105 hover:border-primary hover:bg-primary hover:text-primary-foreground"
                                size="lg"
                                @click="navigateTo('/stock')"
                            >
                                View All Stock
                                <ArrowRight class="ml-2 h-5 w-5" />
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <!-- Stock Distribution -->
                <Card class="info-card flex flex-col">
                    <CardHeader class="border-b">
                        <div class="flex items-center justify-between">
                            <div>
                                <CardTitle class="text-xl font-black">Stock Status</CardTitle>
                                <CardDescription class="mt-1">
                                    Distribution by availability
                                </CardDescription>
                            </div>
                            <div class="info-badge">
                                <BarChart3 class="h-5 w-5" />
                            </div>
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
                                        [Donut.selectors.segment]:
                                            componentToString(
                                                donutChartConfig,
                                                ChartTooltipContent,
                                                {
                                                    nameKey: 'status',
                                                },
                                            )!,
                                    }"
                                />
                            </VisSingleContainer>
                        </ChartContainer>

                        <div class="mt-6 space-y-3">
                            <div
                                v-for="(item, index) in donutChartData"
                                :key="index"
                                class="distribution-item"
                            >
                                <div class="flex items-center gap-3">
                                    <div
                                        class="h-4 w-4 rounded-full ring-2 ring-offset-2 ring-offset-background"
                                        :style="{ backgroundColor: item.fill, borderColor: item.fill }"
                                    />
                                    <span class="font-bold">{{ item.status }}</span>
                                </div>
                                <span class="text-xl font-black">{{ item.parts }}</span>
                            </div>
                        </div>
                    </CardContent>
                    <CardFooter class="flex-col gap-3 border-t bg-muted/30 pt-4">
                        <div class="flex w-full items-baseline justify-between">
                            <span class="text-sm font-bold uppercase tracking-wider text-muted-foreground">
                                Total Parts
                            </span>
                            <span class="text-3xl font-black">{{ stats.totalParts }}</span>
                        </div>
                    </CardFooter>
                </Card>
            </div>

            <!-- Recent Activities -->
            <Card class="activities-card">
                <CardHeader class="border-b">
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle class="text-xl font-black">Recent Activity</CardTitle>
                            <CardDescription class="mt-1">
                                Latest stock movements
                            </CardDescription>
                        </div>
                        <Button
                            variant="outline"
                            class="rounded-xl border-2 font-bold"
                            @click="navigateTo('/stock?tab=movements')"
                        >
                            View All
                            <ArrowRight class="ml-2 h-4 w-4" />
                        </Button>
                    </div>
                </CardHeader>
                <CardContent class="pt-6">
                    <div class="space-y-3">
                        <div
                            v-for="activity in recentActivities"
                            :key="activity.id"
                            class="activity-item group"
                        >
                            <div class="flex items-center gap-4">
                                <div
                                    class="activity-icon"
                                    :class="
                                        activity.type === 'in'
                                            ? 'activity-icon-in'
                                            : 'activity-icon-out'
                                    "
                                >
                                    <PackagePlus
                                        v-if="activity.type === 'in'"
                                        class="h-5 w-5"
                                    />
                                    <PackageMinus v-else class="h-5 w-5" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-bold text-foreground">
                                        {{ activity.part.part_number }}
                                    </p>
                                    <p class="truncate text-sm text-muted-foreground">
                                        {{ activity.part.part_name }}
                                    </p>
                                </div>
                            </div>
                            <div class="text-right">
                                <Badge
                                    class="activity-badge font-bold"
                                    :variant="activity.type === 'in' ? 'default' : 'destructive'"
                                >
                                    {{ activity.type === 'in' ? '+' : '-' }}{{ activity.qty }}
                                </Badge>
                                <p class="mt-1 text-xs font-medium text-muted-foreground">
                                    {{ formatDate(activity.created_at) }}
                                </p>
                            </div>
                        </div>

                        <div
                            v-if="recentActivities.length === 0"
                            class="empty-state"
                        >
                            <Package class="h-12 w-12 opacity-20" />
                            <p class="mt-3 font-semibold text-muted-foreground">
                                No recent activities
                            </p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Dashboard Header */
.dashboard-header {
    position: relative;
    min-height: 160px;
}

.dashboard-gradient {
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, 
        hsl(217, 91%, 60%) 0%, 
        hsl(262, 83%, 58%) 50%, 
        hsl(291, 64%, 42%) 100%
    );
    opacity: 0.95;
    animation: gradientShift 8s ease infinite;
}

.dashboard-noise {
    position: absolute;
    inset: 0;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 400 400' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noiseFilter'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' /%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noiseFilter)' /%3E%3C/svg%3E");
    opacity: 0.08;
    mix-blend-mode: overlay;
}

.dashboard-title {
    text-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    letter-spacing: -0.02em;
}

@keyframes gradientShift {
    0%, 100% { filter: hue-rotate(0deg); }
    50% { filter: hue-rotate(20deg); }
}

@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-10px); }
}

.animate-float {
    animation: float 3s ease-in-out infinite;
}

/* Stat Badges */
.stat-badge {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem 1.25rem;
    border-radius: 1rem;
    backdrop-filter: blur(12px);
    font-weight: 700;
    transition: all 0.3s ease;
}

.stat-badge-success {
    background: rgba(16, 185, 129, 0.15);
    border: 2px solid rgba(16, 185, 129, 0.3);
    color: rgb(255, 255, 255);
}

.stat-badge-danger {
    background: rgba(239, 68, 68, 0.15);
    border: 2px solid rgba(239, 68, 68, 0.3);
    color: rgb(255, 255, 255);
}

/* KPI Cards */
.kpi-card {
    position: relative;
    overflow: hidden;
    border: 2px solid transparent;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    background: hsl(var(--card));
}

.kpi-card::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, transparent 0%, rgba(255, 255, 255, 0.05) 100%);
    opacity: 0;
    transition: opacity 0.4s ease;
}

.kpi-card:hover::before {
    opacity: 1;
}

.kpi-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 20px 40px -12px rgba(0, 0, 0, 0.15);
}

.kpi-accent {
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 5px;
    transition: width 0.4s ease;
}

.kpi-card:hover .kpi-accent {
    width: 8px;
}

.kpi-card-blue .kpi-accent { background: linear-gradient(to bottom, hsl(217, 91%, 60%), hsl(217, 91%, 50%)); }
.kpi-card-purple .kpi-accent { background: linear-gradient(to bottom, hsl(262, 83%, 58%), hsl(262, 83%, 48%)); }
.kpi-card-orange .kpi-accent { background: linear-gradient(to bottom, hsl(25, 95%, 53%), hsl(25, 95%, 43%)); }
.kpi-card-red .kpi-accent { background: linear-gradient(to bottom, hsl(0, 84%, 60%), hsl(0, 84%, 50%)); }

.kpi-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 3rem;
    height: 3rem;
    border-radius: 1rem;
    transition: all 0.4s ease;
}

.kpi-card:hover .kpi-icon {
    transform: rotate(10deg) scale(1.1);
}

.kpi-icon-blue { background: linear-gradient(135deg, hsl(217, 91%, 95%), hsl(217, 91%, 85%)); color: hsl(217, 91%, 40%); }
.kpi-icon-purple { background: linear-gradient(135deg, hsl(262, 83%, 95%), hsl(262, 83%, 85%)); color: hsl(262, 83%, 40%); }
.kpi-icon-orange { background: linear-gradient(135deg, hsl(25, 95%, 95%), hsl(25, 95%, 85%)); color: hsl(25, 95%, 40%); }
.kpi-icon-red { background: linear-gradient(135deg, hsl(0, 84%, 95%), hsl(0, 84%, 85%)); color: hsl(0, 84%, 40%); }

:is(.dark .kpi-icon-blue) { background: linear-gradient(135deg, hsl(217, 91%, 15%), hsl(217, 91%, 20%)); color: hsl(217, 91%, 70%); }
:is(.dark .kpi-icon-purple) { background: linear-gradient(135deg, hsl(262, 83%, 15%), hsl(262, 83%, 20%)); color: hsl(262, 83%, 70%); }
:is(.dark .kpi-icon-orange) { background: linear-gradient(135deg, hsl(25, 95%, 15%), hsl(25, 95%, 20%)); color: hsl(25, 95%, 70%); }
:is(.dark .kpi-icon-red) { background: linear-gradient(135deg, hsl(0, 84%, 15%), hsl(0, 84%, 20%)); color: hsl(0, 84%, 70%); }

.kpi-value {
    font-size: 2.5rem;
    font-weight: 900;
    line-height: 1;
    letter-spacing: -0.03em;
    background: linear-gradient(135deg, hsl(var(--foreground)) 0%, hsl(var(--foreground) / 0.7) 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

/* Action Cards */
.action-card {
    position: relative;
    overflow: hidden;
    border: 2px solid hsl(var(--border));
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.action-card-glow {
    position: absolute;
    inset: -100%;
    background: radial-gradient(circle at center, transparent 0%, transparent 100%);
    opacity: 0;
    transition: opacity 0.5s ease;
}

.action-card:hover .action-card-glow {
    opacity: 0.15;
}

.action-card-glow-green { background: radial-gradient(circle at 50% 50%, hsl(142, 76%, 36%) 0%, transparent 70%); }
.action-card-glow-red { background: radial-gradient(circle at 50% 50%, hsl(0, 84%, 60%) 0%, transparent 70%); }
.action-card-glow-primary { background: radial-gradient(circle at 50% 50%, hsl(var(--primary)) 0%, transparent 70%); }

.action-card-clickable {
    cursor: pointer;
}

.action-card-clickable:hover {
    transform: translateY(-6px) scale(1.02);
    border-color: hsl(var(--primary) / 0.5);
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
}

.action-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 3rem;
    height: 3rem;
    border-radius: 1rem;
    transition: all 0.4s ease;
}

.action-card:hover .action-icon {
    transform: rotate(-10deg) scale(1.15);
}

.action-icon-green { background: linear-gradient(135deg, hsl(142, 76%, 95%), hsl(142, 76%, 85%)); color: hsl(142, 76%, 30%); }
.action-icon-red { background: linear-gradient(135deg, hsl(0, 84%, 95%), hsl(0, 84%, 85%)); color: hsl(0, 84%, 40%); }

:is(.dark .action-icon-green) { background: linear-gradient(135deg, hsl(142, 76%, 15%), hsl(142, 76%, 20%)); color: hsl(142, 76%, 70%); }
:is(.dark .action-icon-red) { background: linear-gradient(135deg, hsl(0, 84%, 15%), hsl(0, 84%, 20%)); color: hsl(0, 84%, 70%); }

/* Chart Card */
.chart-card {
    border: 2px solid hsl(var(--border));
    background: hsl(var(--card));
    box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.1);
}

.chart-header {
    background: linear-gradient(to right, hsl(var(--muted) / 0.3), hsl(var(--muted) / 0.1));
}

.chart-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 3.5rem;
    height: 3.5rem;
    border-radius: 1.25rem;
    background: linear-gradient(135deg, hsl(var(--primary) / 0.15), hsl(var(--primary) / 0.05));
    color: hsl(var(--primary));
}

/* Info Cards */
.info-card {
    border: 2px solid hsl(var(--border));
    transition: all 0.3s ease;
}

.info-card:hover {
    border-color: hsl(var(--primary) / 0.3);
    box-shadow: 0 15px 35px -8px rgba(0, 0, 0, 0.15);
}

.info-badge {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2.75rem;
    height: 2.75rem;
    border-radius: 0.875rem;
    background: linear-gradient(135deg, hsl(var(--primary) / 0.15), hsl(var(--primary) / 0.05));
    color: hsl(var(--primary));
}

.part-item {
    display: flex;
    align-items: center;
    justify-content: between;
    gap: 1rem;
    padding: 1rem;
    border-radius: 0.875rem;
    border: 2px solid hsl(var(--border));
    transition: all 0.3s ease;
}

.part-item:hover {
    border-color: hsl(var(--primary) / 0.4);
    background: hsl(var(--muted) / 0.3);
    transform: translateX(4px);
}

.rank-badge {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 0.75rem;
    background: linear-gradient(135deg, hsl(var(--primary)), hsl(var(--primary) / 0.8));
    box-shadow: 0 4px 12px hsl(var(--primary) / 0.3);
}

.rank-number {
    font-size: 0.875rem;
    font-weight: 900;
    color: white;
}

.stock-badge {
    font-size: 1rem;
    font-weight: 900;
    padding: 0.5rem 1rem;
    border-radius: 0.75rem;
    background: hsl(var(--primary) / 0.1);
    color: hsl(var(--primary));
    border: 2px solid hsl(var(--primary) / 0.2);
}

.distribution-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem 1rem;
    border-radius: 0.75rem;
    border: 2px solid hsl(var(--border));
    transition: all 0.3s ease;
}

.distribution-item:hover {
    background: hsl(var(--muted) / 0.3);
    transform: scale(1.02);
}

/* Activities */
.activities-card {
    border: 2px solid hsl(var(--border));
}

.activity-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 1rem;
    border-radius: 0.875rem;
    border: 2px solid hsl(var(--border));
    transition: all 0.3s ease;
}

.activity-item:hover {
    background: hsl(var(--muted) / 0.3);
    border-color: hsl(var(--primary) / 0.3);
    transform: translateX(4px);
}

.activity-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 3rem;
    height: 3rem;
    border-radius: 0.875rem;
    transition: all 0.3s ease;
}

.activity-item:hover .activity-icon {
    transform: scale(1.1) rotate(5deg);
}

.activity-icon-in {
    background: linear-gradient(135deg, hsl(142, 76%, 95%), hsl(142, 76%, 85%));
    color: hsl(142, 76%, 30%);
}

.activity-icon-out {
    background: linear-gradient(135deg, hsl(0, 84%, 95%), hsl(0, 84%, 85%));
    color: hsl(0, 84%, 40%);
}

:is(.dark .activity-icon-in) {
    background: linear-gradient(135deg, hsl(142, 76%, 15%), hsl(142, 76%, 20%));
    color: hsl(142, 76%, 70%);
}

:is(.dark .activity-icon-out) {
    background: linear-gradient(135deg, hsl(0, 84%, 15%), hsl(0, 84%, 20%));
    color: hsl(0, 84%, 70%);
}

.activity-badge {
    font-size: 1rem;
    padding: 0.375rem 0.875rem;
}

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 4rem 2rem;
    border-radius: 1rem;
    border: 2px dashed hsl(var(--border));
}
</style>