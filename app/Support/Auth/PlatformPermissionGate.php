<?php

namespace App\Support\Auth;

use App\Models\User;

class PlatformPermissionGate
{
    public function allows(?User $user, string $permission): bool
    {
        if (! $user) {
            return false;
        }

        if ($this->isBootstrapAdmin($user)) {
            return true;
        }

        $permissions = $this->permissions($user);

        return in_array($permission, $permissions, true);
    }

    public function allowsAny(?User $user, array $permissions): bool
    {
        if (! $user) {
            return false;
        }

        if ($this->isBootstrapAdmin($user)) {
            return true;
        }

        $resolvedPermissions = $this->permissions($user);

        foreach ($permissions as $permission) {
            if (in_array($permission, $resolvedPermissions, true)) {
                return true;
            }
        }

        return false;
    }

    public function permissions(?User $user): array
    {
        if (! $user) {
            return [];
        }

        $permissions = $user->permission_snapshot;

        if (! is_array($permissions)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn (mixed $value): string => trim((string) $value),
            $permissions,
        )));
    }

    public function roles(?User $user): array
    {
        if (! $user) {
            return [];
        }

        $roles = $user->role_snapshot;

        if (! is_array($roles)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn (mixed $value): string => trim((string) $value),
            $roles,
        )));
    }

    private function isBootstrapAdmin(User $user): bool
    {
        $roles = $this->roles($user);

        return in_array('super_admin', $roles, true);
    }
}
