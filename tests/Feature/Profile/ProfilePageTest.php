<?php

use App\Models\User;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config()->set([
        'services.platform_service.base_url' => 'http://127.0.0.1:8011',
    ]);
});

test('profile page renders donor profile shell from platform service payload', function () {
    Http::fake([
        'http://127.0.0.1:8011/api/v1/profile' => Http::response([
            'data' => [
                'id' => 1,
                'name' => 'Platform User',
                'email' => 'user@example.test',
                'status' => 'active',
                'display_name' => 'Platform User',
                'preferred_app' => 'platform',
                'updated_at_human' => '28 Mei 2026, 10:00',
                'roles' => ['Platform Operator'],
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
        ->assertSee('Ubah Data Akun')
        ->assertSee('Platform Operator')
        ->assertSee('Simpan Profile');
});

test('profile update proxies to platform service and refreshes local auth user name', function () {
    Http::fake([
        'http://127.0.0.1:8011/api/v1/profile' => Http::response([
            'data' => [
                'id' => 1,
                'name' => 'Updated User',
                'email' => 'user@example.test',
                'status' => 'active',
                'display_name' => 'Updated User',
                'preferred_app' => 'platform',
                'updated_at_human' => '28 Mei 2026, 10:05',
                'roles' => ['Platform Operator'],
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
        'name' => 'Updated User',
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ])->assertRedirect(route('profile.show'));

    Http::assertSent(function ($request) {
        return $request->url() === 'http://127.0.0.1:8011/api/v1/profile'
            && $request->method() === 'PUT'
            && $request['name'] === 'Updated User';
    });

    expect($user->fresh()->name)->toBe('Updated User');
});
