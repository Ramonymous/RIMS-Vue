import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export function useAuth() {
    const page = usePage();
    
    const user = computed(() => page.props.auth?.user as any);
    const permissions = computed(() => (page.props.auth?.permissions as string[]) || []);
    const role = computed(() => page.props.auth?.role as string | null);

    const hasPermission = (permission: string | string[]): boolean => {
        const perms = Array.isArray(permission) ? permission : [permission];
        return perms.some(p => permissions.value.includes(p));
    };

    const hasAllPermissions = (requiredPermissions: string[]): boolean => {
        return requiredPermissions.every(p => permissions.value.includes(p));
    };

    const isAdmin = computed(() => hasPermission('admin'));
    
    const canManageParts = computed(() => 
        hasPermission(['admin', 'manager'])
    );
    
    // Users with 'receiving' permission can view receivings but may not supply (GR)
    const canViewReceivings = computed(() => 
        hasPermission(['admin', 'receiving', 'manager', 'part_gr'])
    );

    // Users with 'receiving' permission can create/edit receivings
    const canManageReceivings = computed(() => 
        hasPermission(['admin', 'receiving', 'manager'])
    );
    
    // Users with 'outgoing' permission can view outgoings but may not supply (GI)
    const canViewOutgoings = computed(() => 
        hasPermission(['admin', 'outgoing', 'manager', 'part_gi'])
    );

    // Users with 'outgoing' permission can create/edit outgoings
    const canManageOutgoings = computed(() => 
        hasPermission(['admin', 'outgoing', 'manager'])
    );
    
    // Anyone can create requests (no permission needed)
    // Users with 'receiving' or 'manager' can manage requests
    const canManageRequests = computed(() => 
        hasPermission(['admin', 'manager', 'receiving'])
    );

    // Only users with 'outgoing' can supply requests
    const canSupplyRequests = computed(() =>
        hasPermission(['admin', 'outgoing'])
    );

    // Confirm GR (goods receipt) - requires part_gr permission
    const canConfirmGR = computed(() =>
        hasPermission(['admin', 'manager', 'part_gr'])
    );

    // Confirm GI (goods issue) - requires part_gi permission
    const canConfirmGI = computed(() =>
        hasPermission(['admin', 'manager', 'part_gi'])
    );

    // Permission restrictions
    const hasReceivingOnly = computed(() => 
        hasPermission('receiving') && !hasPermission(['admin', 'outgoing', 'manager'])
    );

    const hasOutgoingOnly = computed(() => 
        hasPermission('outgoing') && !hasPermission(['admin', 'receiving', 'manager'])
    );

    return {
        user,
        permissions,
        role,
        hasPermission,
        hasAllPermissions,
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
