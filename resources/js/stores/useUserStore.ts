import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

/**
 * User Store - Global authentication and permissions state
 * Single source of truth for user data and permissions
 * Uses the same permission logic as useAuth composable
 */
export function useUserStore() {
    const page = usePage();

    // Computed user data from Inertia page props
    const user = computed(() => page.props.auth?.user || null);
    const permissions = computed(
        () => (page.props.auth?.permissions as string[]) || [],
    );
    const role = computed(() => page.props.auth?.role as string | null);
    const isAuthenticated = computed(() => !!user.value);

    // Permission checkers
    const hasPermission = (permission: string | string[]): boolean => {
        const perms = Array.isArray(permission) ? permission : [permission];
        return perms.some((p) => permissions.value.includes(p));
    };

    const hasAllPermissions = (perms: string[]): boolean => {
        return perms.every((p) => permissions.value.includes(p));
    };

    // Common permission checks matching useAuth composable
    const isAdmin = computed(() => hasPermission('admin'));

    const canManageParts = computed(() => hasPermission(['admin', 'manager']));

    const canViewReceivings = computed(() =>
        hasPermission(['admin', 'receiving', 'manager', 'part_gr']),
    );

    const canManageReceivings = computed(() =>
        hasPermission(['admin', 'receiving', 'manager']),
    );

    const canViewOutgoings = computed(() =>
        hasPermission(['admin', 'outgoing', 'manager', 'part_gi']),
    );

    const canManageOutgoings = computed(() =>
        hasPermission(['admin', 'outgoing', 'manager']),
    );

    const canManageRequests = computed(() =>
        hasPermission(['admin', 'manager', 'receiving']),
    );

    const canSupplyRequests = computed(() =>
        hasPermission(['admin', 'outgoing']),
    );

    const canConfirmGR = computed(() =>
        hasPermission(['admin', 'manager', 'part_gr']),
    );

    const canConfirmGI = computed(() =>
        hasPermission(['admin', 'manager', 'part_gi']),
    );

    const hasReceivingOnly = computed(
        () =>
            hasPermission('receiving') &&
            !hasPermission(['admin', 'outgoing', 'manager']),
    );

    const hasOutgoingOnly = computed(
        () =>
            hasPermission('outgoing') &&
            !hasPermission(['admin', 'receiving', 'manager']),
    );

    return {
        // State
        user,
        permissions,
        role,
        isAuthenticated,

        // Methods
        hasPermission,
        hasAllPermissions,

        // Common permissions
        isAdmin,
        canManageParts,
        canViewReceivings,
        canManageReceivings,
        canViewOutgoings,
        canManageOutgoings,
        canManageRequests,
        canSupplyRequests,
        canConfirmGR,
        canConfirmGI,
        hasReceivingOnly,
        hasOutgoingOnly,
    };
}
