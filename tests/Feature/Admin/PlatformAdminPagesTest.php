<?php

use App\Models\User;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config()->set([
        'services.platform_service.base_url' => 'http://127.0.0.1:8011',
    ]);
});

test('roles page renders roles and permission chips from platform service', function () {
    Http::fake([
        'http://127.0.0.1:8011/api/v1/roles' => Http::response([
            'data' => [
                'items' => [
                    [
                        'id' => 1,
                        'code' => 'supply_admin',
                        'name' => 'Supply Admin',
                        'description' => 'Owner for supply operations',
                        'is_system' => false,
                        'is_deletable' => true,
                        'users_count' => 3,
                        'permissions' => ['materials.manage', 'stores.manage'],
                    ],
                ],
                'summary' => [
                    'total_roles' => 1,
                    'total_permissions' => 58,
                    'assigned_users' => 3,
                ],
            ],
        ]),
        'http://127.0.0.1:8011/api/v1/permissions' => Http::response([
            'data' => [
                'total' => 58,
                'groups' => [
                    [
                        'key' => 'materials',
                        'label' => 'Material',
                        'description' => 'Database material',
                        'permissions' => [
                            [
                                'name' => 'materials.manage',
                                'label' => 'Kelola penuh material',
                                'description' => 'Full access materials',
                                'grants' => ['manage', 'view'],
                                'implies' => ['materials.view'],
                            ],
                        ],
                    ],
                ],
                'all' => ['materials.manage'],
            ],
        ]),
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)->withSession([
        'platform_access_token' => 'access-token-123',
    ])->get(route('admin.roles.index'))
        ->assertOk()
        ->assertSee('Supply Admin')
        ->assertSee('materials.manage')
        ->assertSee('58');
});

test('registration settings page renders current toggle state', function () {
    Http::fake([
        'http://127.0.0.1:8011/api/v1/settings/registration' => Http::response([
            'data' => [
                'registration_enabled' => true,
                'approval_mode' => 'auto_approve',
                'default_new_user_status' => 'active',
                'notes' => 'Pilot rollout',
            ],
        ]),
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)->withSession([
        'platform_access_token' => 'access-token-123',
    ])->get(route('admin.registration.edit'))
        ->assertOk()
        ->assertSee('Registration Policy')
        ->assertSee('Pilot rollout')
        ->assertSee('auto_approve');
});

test('registration settings update proxies to platform service and redirects back', function () {
    Http::fake([
        'http://127.0.0.1:8011/api/v1/settings/registration' => Http::response([
            'data' => [
                'registration_enabled' => false,
                'approval_mode' => 'admin_approval',
                'default_new_user_status' => 'pending_access',
                'notes' => 'Closed again',
            ],
        ]),
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)->withSession([
        'platform_access_token' => 'access-token-123',
    ])->put(route('admin.registration.update'), [
        'registration_enabled' => false,
        'approval_mode' => 'admin_approval',
        'default_new_user_status' => 'pending_access',
        'notes' => 'Closed again',
    ])->assertRedirect(route('admin.registration.edit'));

    Http::assertSent(function ($request) {
        return $request->url() === 'http://127.0.0.1:8011/api/v1/settings/registration'
            && $request->method() === 'PUT'
            && $request['approval_mode'] === 'admin_approval';
    });
});
