<?php

use App\Models\User;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config()->set([
        'services.platform_service.base_url' => 'http://127.0.0.1:8011',
    ]);
});

test('settings users donor page renders transplanted user management view with keycloak-style fields', function () {
    Http::fake([
        'http://127.0.0.1:8011/api/v1/users' => Http::response([
            'data' => [
                'items' => [
                    [
                        'id' => 7,
                        'name' => 'Supply Owner',
                        'full_name' => 'Supply Owner',
                        'first_name' => 'Supply',
                        'last_name' => 'Owner',
                        'username' => 'supply.owner@example.test',
                        'email' => 'supply.owner@example.test',
                        'status' => 'active',
                        'roles' => ['Platform Operator'],
                    ],
                ],
                'roles' => [
                    [
                        'id' => 1,
                        'name' => 'Platform Operator',
                        'users_count' => 1,
                    ],
                ],
                'registration_enabled' => true,
                'summary' => [
                    'total_users' => 1,
                    'with_roles' => 1,
                    'pending_access' => 0,
                ],
            ],
        ]),
    ]);

    $user = User::factory()->create([
        'permission_snapshot' => ['users.view', 'users.create', 'users.update', 'users.delete'],
    ]);

    $this->actingAs($user)->withSession([
        'platform_access_token' => 'access-token-123',
    ])->get(route('settings.users.index'))
        ->assertOk()
        ->assertSee('Tambah User')
        ->assertSee('Supply Owner')
        ->assertSee('Username: supply.owner@example.test')
        ->assertSee('First Name')
        ->assertSee('Last Name')
        ->assertSee('Platform Operator')
        ->assertSee('Register aktif');
});

test('settings users donor page can proxy create user submission with keycloak-style fields', function () {
    Http::fake([
        'http://127.0.0.1:8011/api/v1/users' => Http::response([
            'data' => [
                'id' => 8,
                'name' => 'Supply Owner',
                'full_name' => 'Supply Owner',
                'first_name' => 'Supply',
                'last_name' => 'Owner',
                'username' => 'supply.owner@example.test',
                'email' => 'supply.owner@example.test',
                'status' => 'active',
                'roles' => ['Platform Operator'],
            ],
        ], 201),
    ]);

    $user = User::factory()->create([
        'permission_snapshot' => ['users.view', 'users.create'],
    ]);

    $this->actingAs($user)->withSession([
        'platform_access_token' => 'access-token-123',
    ])->post(route('settings.users.store'), [
        'first_name' => 'Supply',
        'last_name' => 'Owner',
        'email' => 'supply.owner@example.test',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'roles' => ['Platform Operator'],
    ])->assertRedirect(route('settings.users.index'));

    Http::assertSent(function ($request) {
        return $request->url() === 'http://127.0.0.1:8011/api/v1/users'
            && $request->method() === 'POST'
            && $request['first_name'] === 'Supply'
            && $request['last_name'] === 'Owner'
            && $request['email'] === 'supply.owner@example.test'
            && $request['password'] === 'password123'
            && $request['roles'][0] === 'Platform Operator';
    });
});
