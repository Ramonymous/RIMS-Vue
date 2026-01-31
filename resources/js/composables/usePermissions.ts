import { computed, type ComputedRef } from 'vue';
import { useAuth } from './useAuth';

export interface PermissionsState {
    hasPermission: (permission: string) => boolean;
    hasAnyPermission: (...permissions: string[]) => boolean;
    hasAllPermissions: (...permissions: string[]) => boolean;
    canManageParts: ComputedRef<boolean>;
    canViewReceivings: ComputedRef<boolean>;
    canManageReceivings: ComputedRef<boolean>;
    canViewOutgoings: ComputedRef<boolean>;
    canManageOutgoings: ComputedRef<boolean>;
    canManageRequests: ComputedRef<boolean>;
    canSupplyRequests: ComputedRef<boolean>;
    canConfirmGR: ComputedRef<boolean>;
    canConfirmGI: ComputedRef<boolean>;
    isAdmin: ComputedRef<boolean>;
    isManager: ComputedRef<boolean>;
    hasReceivingOnly: ComputedRef<boolean>;
    hasOutgoingOnly: ComputedRef<boolean>;
}

/**
 * Composable for permission checking
 * Provides standardized permission checks across components
 * Delegates to useAuth for actual permission logic
 */
export function usePermissions(): PermissionsState {
    const auth = useAuth();

    const hasPermission = (permission: string): boolean => {
        return auth.hasPermission(permission);
    };

    const hasAnyPermission = (...permissions: string[]): boolean => {
        return auth.hasPermission(permissions);
    };

    const hasAllPermissions = (...permissions: string[]): boolean => {
        return auth.hasAllPermissions(permissions);
    };

    const isManager = computed(() => hasPermission('manager'));

    return {
        hasPermission,
        hasAnyPermission,
        hasAllPermissions,
        canManageParts: auth.canManageParts,
        canViewReceivings: auth.canViewReceivings,
        canManageReceivings: auth.canManageReceivings,
        canViewOutgoings: auth.canViewOutgoings,
        canManageOutgoings: auth.canManageOutgoings,
        canManageRequests: auth.canManageRequests,
        canSupplyRequests: auth.canSupplyRequests,
        canConfirmGR: auth.canConfirmGR,
        canConfirmGI: auth.canConfirmGI,
        isAdmin: auth.isAdmin,
        isManager,
        hasReceivingOnly: auth.hasReceivingOnly,
        hasOutgoingOnly: auth.hasOutgoingOnly,
    };
}
