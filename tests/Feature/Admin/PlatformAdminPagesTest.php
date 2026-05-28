<?php

use App\Models\User;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config()->set([
        'services.platform_service.base_url' => 'http://127.0.0.1:8011',
    ]);
});

test('legacy admin roles route redirects to donor settings roles page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->withSession([
        'platform_access_token' => 'access-token-123',
    ])->get(route('admin.roles.index'))
        ->assertRedirect(route('settings.roles.index'));
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
    ])->get(route('settings.registration.edit'))
        ->assertOk()
        ->assertSee('Status Registrasi')
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
    ])->put(route('settings.registration.update'), [
        'registration_enabled' => false,
        'approval_mode' => 'admin_approval',
        'default_new_user_status' => 'pending_access',
        'notes' => 'Closed again',
    ])->assertRedirect(route('settings.registration.edit'));

    Http::assertSent(function ($request) {
        return $request->url() === 'http://127.0.0.1:8011/api/v1/settings/registration'
            && $request->method() === 'PUT'
            && $request['approval_mode'] === 'admin_approval';
    });
});

test('legacy admin registration route redirects to settings registration page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->withSession([
        'platform_access_token' => 'access-token-123',
    ])->get(route('admin.registration.edit'))
        ->assertRedirect(route('settings.registration.edit'));
});
