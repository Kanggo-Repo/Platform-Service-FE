<?php

use App\Models\User;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config()->set([
        'services.platform_service.base_url' => 'http://127.0.0.1:8011',
    ]);
});

test('profile page renders keycloak-backed profile details and keycloak-style fields from platform service payload', function () {
    Http::fake([
        'http://127.0.0.1:8011/api/v1/profile' => Http::response([
            'data' => [
                'id' => 1,
                'name' => 'Platform User',
                'full_name' => 'Platform User',
                'first_name' => 'Platform',
                'last_name' => 'User',
                'email' => 'user@example.test',
                'status' => 'active',
                'display_name' => 'Platform User',
                'preferred_app' => 'platform',
                'updated_at_human' => '28 Mei 2026, 10:00',
                'roles' => ['Platform Operator'],
                'identity' => [
                    'provider' => 'keycloak',
                    'provider_label' => 'Keycloak',
                    'subject' => 'kc-user-1',
                    'username' => 'platform.user',
                    'preferred_username' => 'platform.user',
                    'realm_roles' => ['super_admin'],
                    'email_verified' => true,
                ],
            ],
        ]),
    ]);

    $user = User::factory()->create([
        'name' => 'Platform User',
        'email' => 'user@example.test',
    ]);

    $this->actingAs($user)->withSession([
        'platform_access_token' => 'access-token-123',
    ])->get(route('profile.show'))
        ->assertOk()
        ->assertSee('First Name')
        ->assertSee('Last Name')
        ->assertSee('Username')
        ->assertSee('Platform Operator')
        ->assertSee('Keycloak')
        ->assertSee('platform.user')
        ->assertSee('kc-user-1')
        ->assertSee('Simpan Profile');
});

test('profile update proxies keycloak-style name parts to platform service and refreshes local auth user name', function () {
    Http::fake([
        'http://127.0.0.1:8011/api/v1/profile' => Http::response([
            'data' => [
                'id' => 1,
                'name' => 'Updated User',
                'full_name' => 'Updated User',
                'first_name' => 'Updated',
                'last_name' => 'User',
                'email' => 'user@example.test',
                'status' => 'active',
                'display_name' => 'Updated User',
                'preferred_app' => 'platform',
                'updated_at_human' => '28 Mei 2026, 10:05',
                'roles' => ['Platform Operator'],
                'identity' => [
                    'provider' => 'keycloak',
                    'provider_label' => 'Keycloak',
                    'subject' => 'kc-user-1',
                    'username' => 'platform.user',
                    'preferred_username' => 'platform.user',
                    'realm_roles' => ['super_admin'],
                    'email_verified' => true,
                ],
            ],
        ]),
    ]);

    $user = User::factory()->create([
        'name' => 'Platform User',
        'email' => 'user@example.test',
    ]);

    $this->actingAs($user)->withSession([
        'platform_access_token' => 'access-token-123',
    ])->put(route('profile.update'), [
        'first_name' => 'Updated',
        'last_name' => 'User',
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ])->assertRedirect(route('profile.show'));

    Http::assertSent(function ($request) {
        return $request->url() === 'http://127.0.0.1:8011/api/v1/profile'
            && $request->method() === 'PUT'
            && $request['first_name'] === 'Updated'
            && $request['last_name'] === 'User';
    });

    expect($user->fresh()->name)->toBe('Updated User');
});

test('profile page keeps blank keycloak last name blank instead of falling back to stale local name', function () {
    Http::fake([
        'http://127.0.0.1:8011/api/v1/profile' => Http::response([
            'data' => [
                'id' => 1,
                'name' => 'Platform',
                'full_name' => 'Platform',
                'first_name' => 'Platform',
                'last_name' => null,
                'email' => 'user@example.test',
                'status' => 'active',
                'display_name' => 'Platform',
                'preferred_app' => 'platform',
                'updated_at_human' => '28 Mei 2026, 10:00',
                'roles' => ['Super Admin'],
                'identity' => [
                    'provider' => 'keycloak',
                    'provider_label' => 'Keycloak',
                    'subject' => 'kc-user-1',
                    'username' => 'platform.user',
                    'preferred_username' => 'platform.user',
                    'realm_roles' => ['super_admin'],
                    'email_verified' => true,
                ],
            ],
        ]),
    ]);

    $user = User::factory()->create([
        'name' => 'Platform User',
        'email' => 'user@example.test',
    ]);

    $this->actingAs($user)->withSession([
        'platform_access_token' => 'access-token-123',
    ])->get(route('profile.show'))
        ->assertOk()
        ->assertSee('value="Platform"', false)
        ->assertSee('name="last_name" value=""', false)
        ->assertDontSee('name="last_name" value="User"', false);
});
