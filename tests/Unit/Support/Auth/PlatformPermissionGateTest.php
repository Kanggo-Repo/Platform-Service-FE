<?php

namespace Tests\Unit\Support\Auth;

use App\Models\User;
use App\Support\Auth\PlatformPermissionGate;
use Tests\TestCase;

class PlatformPermissionGateTest extends TestCase
{
    public function test_regular_user_without_permission_snapshot_is_not_treated_as_full_access(): void
    {
        $user = new User([
            'role_snapshot' => ['purchasing'],
            'permission_snapshot' => [],
        ]);

        $gate = new PlatformPermissionGate;

        $this->assertFalse($gate->allows($user, 'materials.view'));
        $this->assertFalse($gate->allowsAny($user, ['materials.view', 'stores.view']));
    }

    public function test_super_admin_role_is_treated_as_bootstrap_admin(): void
    {
        $user = new User([
            'role_snapshot' => ['super_admin'],
            'permission_snapshot' => [],
        ]);

        $gate = new PlatformPermissionGate;

        $this->assertTrue($gate->allows($user, 'materials.view'));
        $this->assertTrue($gate->allowsAny($user, ['users.view', 'roles.view']));
    }
}
