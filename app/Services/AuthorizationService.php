<?php

namespace App\Services;

use App\Models\User;

class AuthorizationService
{
    /**
     * Check if user has any of the required permissions
     */
    public function hasAnyPermission(User $user, array $permissions): bool
    {
        $userPermissions = $user->permissions ?? [];

        return ! empty(array_intersect($permissions, $userPermissions));
    }

    /**
     * Check if user has all of the required permissions
     */
    public function hasAllPermissions(User $user, array $permissions): bool
    {
        $userPermissions = $user->permissions ?? [];

        return count(array_intersect($permissions, $userPermissions)) === count($permissions);
    }

    /**
     * Check if user has role
     */
    public function hasRole(User $user, string|array $roles): bool
    {
        if (is_string($roles)) {
            return $user->role === $roles;
        }

        return in_array($user->role, $roles);
    }

    /**
     * Authorize user for action or throw exception
     */
    public function authorize(User $user, array $permissions, string $message = 'Unauthorized access.'): void
    {
        if (! $this->hasAnyPermission($user, $permissions)) {
            abort(403, $message);
        }
    }

    /**
     * Check if user can manage parts
     */
    public function canManageParts(User $user): bool
    {
        return $this->hasRole($user, ['admin', 'manager']);
    }

    /**
     * Check if user can manage receivings
     */
    public function canManageReceivings(User $user): bool
    {
        return $this->hasAnyPermission($user, ['admin', 'receiving', 'manager', 'part_gr']);
    }

    /**
     * Check if user can manage outgoings
     */
    public function canManageOutgoings(User $user): bool
    {
        return $this->hasAnyPermission($user, ['admin', 'outgoing', 'manager', 'part_gi']);
    }

    /**
     * Check if user can confirm GR/GI
     */
    public function canConfirmGoodsMovement(User $user, string $type = 'gr'): bool
    {
        $permission = $type === 'gr' ? 'part_gr' : 'part_gi';

        return $this->hasAnyPermission($user, ['admin', 'manager', $permission]);
    }
}
