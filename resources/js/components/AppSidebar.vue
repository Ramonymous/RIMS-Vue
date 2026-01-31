<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { LayoutGrid, UsersRound, Package, PackageSearch, PackageMinus, BarChart3, FileInput } from 'lucide-vue-next';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { type NavItem } from '@/types';
import AppLogo from './AppLogo.vue';
import { computed } from 'vue';
import { cn } from '@/lib/utils';
import { useAuth } from '@/composables/useAuth';

const {
    isAdmin,
    canManageParts,
} = useAuth();

const navGroups = computed(() => {
    const groups = [];
    
    // Inventory section - Globally accessible (no permission checks)
    const inventoryItems: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboard.url(),
            icon: LayoutGrid,
        },
        {
            title: 'Stock',
            href: '/stock',
            icon: BarChart3,
        },
        {
            title: 'Receivings',
            href: '/receivings',
            icon: PackageSearch,
        },
        {
            title: 'Outgoings',
            href: '/outgoings',
            icon: PackageMinus,
        },
        {
            title: 'Requests',
            href: '/requests',
            icon: FileInput,
        },
    ];

    groups.push({
        label: 'Inventory',
        items: inventoryItems,
    });

    // Admin section - Admin only
    if (isAdmin.value) {
        groups.push({
            label: 'Admin',
            items: [
                {
                    title: 'Users',
                    href: '/users',
                    icon: UsersRound,
                },
            ],
        });
    }

    // Parts - Admin or Manager permission
    if (canManageParts.value) {
        if (isAdmin.value) {
            // Add to Admin section if admin
            groups.find(g => g.label === 'Admin')?.items.push({
                title: 'Parts',
                href: '/parts',
                icon: Package,
            });
        } else {
            // Create Management section for non-admin managers
            groups.push({
                label: 'Management',
                items: [
                    {
                        title: 'Parts',
                        href: '/parts',
                        icon: Package,
                    },
                ],
            });
        }
    }

    return groups;
});

const currentDate = new Date();
const currentYear = currentDate.getFullYear();
const currentMonth = currentDate.getMonth();
const today = currentDate.getDate();

const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

const getDaysInMonth = (year: number, month: number) => {
    return new Date(year, month + 1, 0).getDate();
};

const getFirstDayOfMonth = (year: number, month: number) => {
    return new Date(year, month, 1).getDay();
};

const calendarDays = computed(() => {
    const daysInMonth = getDaysInMonth(currentYear, currentMonth);
    const firstDay = getFirstDayOfMonth(currentYear, currentMonth);
    const days: (number | null)[] = [];
    
    // Add empty slots for days before the first day of the month
    for (let i = 0; i < firstDay; i++) {
        days.push(null);
    }
    
    // Add all days of the month
    for (let i = 1; i <= daysInMonth; i++) {
        days.push(i);
    }
    
    return days;
});
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard.url()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :groups="navGroups" />
        </SidebarContent>

        <SidebarFooter class="pb-4">
            <!-- Calendar Section -->
            <div class="px-2 pb-3">
                <div class="mb-2 text-center text-sm font-medium">
                    {{ monthNames[currentMonth] }} {{ currentYear }}
                </div>
                <div class="grid grid-cols-7 gap-0.5 text-xs">
                    <!-- Day headers -->
                    <div class="text-center font-medium text-muted-foreground py-1">S</div>
                    <div class="text-center font-medium text-muted-foreground py-1">M</div>
                    <div class="text-center font-medium text-muted-foreground py-1">T</div>
                    <div class="text-center font-medium text-muted-foreground py-1">W</div>
                    <div class="text-center font-medium text-muted-foreground py-1">T</div>
                    <div class="text-center font-medium text-muted-foreground py-1">F</div>
                    <div class="text-center font-medium text-muted-foreground py-1">S</div>
                    
                    <!-- Calendar days -->
                    <div
                        v-for="(day, index) in calendarDays"
                        :key="index"
                        :class="cn(
                            'flex h-7 w-full items-center justify-center rounded-md text-xs',
                            'group-data-[collapsible=icon]:h-6 group-data-[collapsible=icon]:text-[10px]',
                            day === today && 'bg-primary text-primary-foreground font-semibold',
                            day && day !== today && 'hover:bg-accent',
                            !day && 'pointer-events-none'
                        )"
                    >
                        {{ day }}
                    </div>
                </div>
            </div>
            
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
