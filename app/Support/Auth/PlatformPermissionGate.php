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

        $permissions = $this->permissions($user);

        if ($permissions === []) {
            return true;
        }

        return in_array($permission, $permissions, true);
    }

    public function allowsAny(?User $user, array $permissions): bool
    {
        if (! $user) {
            return false;
        }

        $resolvedPermissions = $this->permissions($user);

        if ($resolvedPermissions === []) {
            return true;
        }

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
}
