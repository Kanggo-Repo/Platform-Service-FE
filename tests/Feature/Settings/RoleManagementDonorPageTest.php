<?php

use App\Models\User;
use Illuminate\Support\Facades\Http;

test('settings roles donor page renders transplanted role management view', function () {
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
                        'description' => 'Database material, histori, dan utilitas material.',
                        'permissions' => [
                            [
                                'name' => 'materials.manage',
                                'label' => 'Kelola penuh material',
                                'description' => 'Akses penuh material.',
                                'grants' => ['manage', 'view'],
                                'implies' => ['materials.view'],
                            ],
                        ],
                    ],
                ],
                'all' => ['materials.manage', 'materials.view'],
            ],
        ]),
    ]);

    $user = User::factory()->create([
        'permission_snapshot' => ['roles.view', 'roles.create', 'roles.update', 'roles.delete'],
    ]);

    $this->actingAs($user)->withSession([
        'platform_access_token' => 'access-token-123',
    ])->get(route('settings.roles.index'))
        ->assertOk()
        ->assertSee('Tambah Role')
        ->assertSee('Supply Admin')
        ->assertSee('materials.manage');
});

test('settings roles donor page can proxy create role submission', function () {
    Http::fake([
        'http://127.0.0.1:8011/api/v1/roles' => Http::response([
            'data' => [
                'id' => 7,
                'code' => 'supply_admin',
                'name' => 'Supply Admin',
                'permissions' => ['materials.manage'],
            ],
        ], 201),
    ]);

    $user = User::factory()->create([
        'permission_snapshot' => ['roles.view', 'roles.create'],
    ]);

    $this->actingAs($user)->withSession([
        'platform_access_token' => 'access-token-123',
    ])
        ->post(route('settings.roles.store'), [
            'name' => 'Supply Admin',
            'permissions' => ['materials.manage'],
        ])
        ->assertRedirect(route('settings.roles.index'));

    Http::assertSent(function ($request) {
        return $request->url() === 'http://127.0.0.1:8011/api/v1/roles'
            && $request->method() === 'POST'
            && $request['name'] === 'Supply Admin'
            && $request['code'] === 'supply_admin';
    });
});
