<?php

use App\Models\User;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config()->set([
        'services.keycloak.base_url' => 'https://auth.example.test',
        'services.keycloak.realm' => 'kanggo',
        'services.keycloak.client_id' => 'platform-fe',
        'services.platform_service.base_url' => 'http://127.0.0.1:8011',
    ]);
});

test('login page renders keycloak entry point', function () {
    $this->get('/login')
        ->assertOk()
        ->assertSee('Masuk dengan Keycloak')
        ->assertSee('Portal Login Database Material dan Perhitungan Proyek.')
        ->assertSee(route('auth.redirect'), false);
});

test('auth redirect sends browser to keycloak authorize endpoint', function () {
    $response = $this->get(route('auth.redirect'));

    $response->assertRedirect();

    $redirectUrl = $response->headers->get('Location');

    expect($redirectUrl)->toContain('https://auth.example.test/realms/kanggo/protocol/openid-connect/auth');
    expect($redirectUrl)->toContain('client_id=platform-fe');
    expect($redirectUrl)->toContain('response_type=code');
    expect(session()->has('oidc_state'))->toBeTrue();
    expect(session()->has('oidc_code_verifier'))->toBeTrue();
});

test('callback exchanges authorization code and stores tokens in session', function () {
    Http::fake([
        'https://auth.example.test/realms/kanggo/protocol/openid-connect/token' => Http::response([
            'access_token' => 'access-token-123',
            'refresh_token' => 'refresh-token-123',
            'id_token' => 'id-token-123',
            'expires_in' => 300,
        ]),
        'http://127.0.0.1:8011/api/v1/me' => Http::response([
            'data' => [
                'identity' => [
                    'subject' => 'kc-user-1',
                    'email' => 'user@example.test',
                    'name' => 'Platform User',
                    'realm_roles' => ['platform_operator'],
                ],
                'roles' => ['super_admin'],
                'permissions' => ['roles.manage', 'users.manage'],
            ],
        ]),
    ]);

    $this->withSession([
        'oidc_state' => 'expected-state',
        'oidc_code_verifier' => 'verifier-123',
    ])->get(route('auth.callback', [
        'code' => 'authorization-code',
        'state' => 'expected-state',
    ]))
        ->assertRedirect(route('workspace.index'));

    expect(session('platform_access_token'))->toBe('access-token-123');
    expect(session('platform_refresh_token'))->toBe('refresh-token-123');
    expect(auth()->check())->toBeTrue();
    expect(User::query()->where('auth_subject', 'keycloak:kc-user-1')->exists())->toBeTrue();
    expect(User::query()->where('auth_subject', 'keycloak:kc-user-1')->firstOrFail()->role_snapshot)->toBe(['super_admin']);
    expect(User::query()->where('auth_subject', 'keycloak:kc-user-1')->firstOrFail()->permission_snapshot)->toBe(['roles.manage', 'users.manage']);
});

test('logout clears session and redirects to keycloak logout endpoint', function () {
    $response = $this->withSession([
        'platform_access_token' => 'access-token-123',
        'platform_refresh_token' => 'refresh-token-123',
    ])->post(route('auth.logout'));

    $response->assertRedirect();

    expect(session()->has('platform_access_token'))->toBeFalse();
    expect(session()->has('platform_refresh_token'))->toBeFalse();
    expect($response->headers->get('Location'))->toContain('https://auth.example.test/realms/kanggo/protocol/openid-connect/logout');
});
