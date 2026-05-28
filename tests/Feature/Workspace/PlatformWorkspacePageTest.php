<?php

use App\Models\User;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config()->set([
        'services.platform_service.base_url' => 'http://127.0.0.1:8011',
        'services.supply_fe.base_url' => 'http://supplyfe.lvh.me:8009',
        'services.calculation_fe.base_url' => 'http://calcfe.lvh.me:8001',
        'services.monolith_app.base_url' => 'http://legacy.lvh.me:8002',
    ]);
});

test('workspace redirects to login when session does not have access token', function () {
    $this->get(route('workspace.index'))
        ->assertRedirect(route('auth.redirect'));
});

test('workspace redirects pending access users to access pending page', function () {
    Http::fake([
        'http://127.0.0.1:8011/api/v1/me' => Http::response([
            'data' => [
                'identity' => [
                    'subject' => 'kc-user-1',
                    'email' => 'user@example.test',
                    'name' => 'Platform User',
                ],
                'profile' => [
                    'id' => 1,
                    'status' => 'pending_access',
                    'display_name' => 'Platform User',
                    'preferred_app' => null,
                ],
                'access' => [
                    'pending_access' => true,
                    'allowed_services' => [],
                    'blocked_services' => [],
                    'pending_services' => ['platform', 'supply', 'calculation'],
                ],
                'roles' => ['platform_operator'],
                'navigation' => [
                    'preferred_route' => 'platform.access.pending',
                ],
            ],
        ]),
        'http://127.0.0.1:8011/api/v1/navigation' => Http::response([
            'data' => [
                'services' => [
                    ['service' => 'platform', 'label' => 'Platform', 'access_status' => 'pending', 'entry_url' => null],
                    ['service' => 'supply', 'label' => 'Supply', 'access_status' => 'pending', 'entry_url' => null],
                    ['service' => 'calculation', 'label' => 'Calculation', 'access_status' => 'pending', 'entry_url' => null],
                ],
                'preferred_app' => null,
                'preferred_route' => 'platform.access.pending',
                'pending_access' => true,
                'allowed_services' => [],
                'blocked_services' => [],
                'pending_services' => ['platform', 'supply', 'calculation'],
            ],
        ]),
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)->withSession([
        'platform_access_token' => 'access-token-123',
    ])->get(route('workspace.index'))
        ->assertRedirect(route('platform.access.pending'));
});

test('access pending page renders transplanted pending access view', function () {
    $user = User::factory()->create([
        'name' => 'Platform User',
    ]);

    $this->actingAs($user)
        ->get(route('platform.access.pending'))
        ->assertOk()
        ->assertSee('Akun aktif, akses modul belum diberikan')
        ->assertSee('Halo, Platform User.');
});

test('workspace performs one-time handoff to preferred supply app after login', function () {
    Http::fake([
        'http://127.0.0.1:8011/api/v1/me' => Http::response([
            'data' => [
                'identity' => [
                    'subject' => 'kc-user-1',
                    'email' => 'user@example.test',
                    'name' => 'Platform User',
                ],
                'profile' => [
                    'id' => 1,
                    'status' => 'active',
                    'display_name' => 'Platform User',
                    'preferred_app' => 'supply',
                ],
                'access' => [
                    'pending_access' => false,
                    'allowed_services' => ['platform', 'supply'],
                    'blocked_services' => [],
                    'pending_services' => ['calculation'],
                ],
                'roles' => ['platform_operator'],
                'navigation' => [
                    'preferred_route' => 'service.supply',
                ],
            ],
        ]),
        'http://127.0.0.1:8011/api/v1/navigation' => Http::response([
            'data' => [
                'services' => [
                    ['service' => 'platform', 'label' => 'Platform', 'access_status' => 'allowed', 'entry_url' => null],
                    ['service' => 'supply', 'label' => 'Supply', 'access_status' => 'allowed', 'entry_url' => null],
                    ['service' => 'calculation', 'label' => 'Calculation', 'access_status' => 'pending', 'entry_url' => null],
                ],
                'preferred_app' => 'supply',
                'preferred_route' => 'service.supply',
                'pending_access' => false,
                'allowed_services' => ['platform', 'supply'],
                'blocked_services' => [],
                'pending_services' => ['calculation'],
            ],
        ]),
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)->withSession([
        'platform_access_token' => 'access-token-123',
        'platform_post_login_redirect' => true,
    ])->get(route('workspace.index'))
        ->assertRedirect('http://supplyfe.lvh.me:8009');
});

test('workspace renders donor dashboard for active platform users', function () {
    Http::fake([
        'http://127.0.0.1:8011/api/v1/me' => Http::response([
            'data' => [
                'identity' => [
                    'subject' => 'kc-user-1',
                    'email' => 'user@example.test',
                    'name' => 'Platform User',
                ],
                'profile' => [
                    'id' => 1,
                    'status' => 'active',
                    'display_name' => 'Platform User',
                    'preferred_app' => 'platform',
                ],
                'access' => [
                    'pending_access' => false,
                    'allowed_services' => ['platform'],
                    'blocked_services' => [],
                    'pending_services' => ['supply', 'calculation'],
                ],
                'roles' => ['platform_operator'],
                'navigation' => [
                    'preferred_route' => 'platform.dashboard',
                ],
            ],
        ]),
        'http://127.0.0.1:8011/api/v1/navigation' => Http::response([
            'data' => [
                'services' => [
                    ['service' => 'platform', 'label' => 'Platform', 'access_status' => 'allowed', 'entry_url' => null],
                    ['service' => 'supply', 'label' => 'Supply', 'access_status' => 'pending', 'entry_url' => null],
                    ['service' => 'calculation', 'label' => 'Calculation', 'access_status' => 'pending', 'entry_url' => null],
                ],
                'preferred_app' => 'platform',
                'preferred_route' => 'platform.dashboard',
                'pending_access' => false,
                'allowed_services' => ['platform'],
                'blocked_services' => [],
                'pending_services' => ['supply', 'calculation'],
            ],
        ]),
        'http://127.0.0.1:8011/api/v1/dashboard' => Http::response([
            'data' => [
                'summary' => [
                    'total_users' => 12,
                    'role_count' => 4,
                    'permission_count' => 58,
                    'pending_access_count' => 2,
                    'allowed_user_count' => 9,
                    'registration_enabled' => true,
                ],
                'chart' => [
                    'labels' => ['Platform', 'Supply', 'Calculation'],
                    'data' => [9, 6, 4],
                ],
                'recent_activities' => [
                    [
                        'id' => 1,
                        'name' => 'Platform User',
                        'category' => 'Platform Operator',
                        'category_color' => 'primary',
                        'updated_at_human' => '1 menit yang lalu',
                    ],
                ],
                'service_matrix' => [
                    'platform' => 9,
                    'supply' => 6,
                    'calculation' => 4,
                ],
            ],
        ]),
    ]);

    $user = User::factory()->create([
        'name' => 'Platform User',
    ]);

    $this->actingAs($user)->withSession([
        'platform_access_token' => 'access-token-123',
    ])->get(route('workspace.index'))
        ->assertOk()
        ->assertSee('Selamat Datang di Material Database')
        ->assertSee('Total Material')
        ->assertSee('Mitra Toko')
        ->assertSee('Tukang')
        ->assertSee('Keahlian')
        ->assertSee('Manajemen User')
        ->assertSee('Manajemen Role')
        ->assertSee('Status Registrasi')
        ->assertSee('data-material="brick"', false)
        ->assertSee('Platform Operator')
        ->assertSee('Distribusi Material');
});

test('workspace resets session and redirects to login when dashboard request fails', function () {
    Http::fake([
        'http://127.0.0.1:8011/api/v1/me' => Http::response([
            'data' => [
                'identity' => [
                    'subject' => 'kc-user-1',
                    'email' => 'user@example.test',
                    'name' => 'Platform User',
                ],
                'profile' => [
                    'id' => 1,
                    'status' => 'active',
                    'display_name' => 'Platform User',
                    'preferred_app' => 'platform',
                ],
                'access' => [
                    'pending_access' => false,
                    'allowed_services' => ['platform'],
                    'blocked_services' => [],
                    'pending_services' => ['supply', 'calculation'],
                ],
                'roles' => ['platform_operator'],
                'navigation' => [
                    'preferred_route' => 'platform.dashboard',
                ],
            ],
        ]),
        'http://127.0.0.1:8011/api/v1/navigation' => Http::response([
            'data' => [
                'services' => [
                    ['service' => 'platform', 'label' => 'Platform', 'access_status' => 'allowed', 'entry_url' => null],
                    ['service' => 'supply', 'label' => 'Supply', 'access_status' => 'pending', 'entry_url' => null],
                    ['service' => 'calculation', 'label' => 'Calculation', 'access_status' => 'pending', 'entry_url' => null],
                ],
                'preferred_app' => 'platform',
                'preferred_route' => 'platform.dashboard',
                'pending_access' => false,
                'allowed_services' => ['platform'],
                'blocked_services' => [],
                'pending_services' => ['supply', 'calculation'],
            ],
        ]),
        'http://127.0.0.1:8011/api/v1/dashboard' => Http::response([
            'message' => 'Upstream dashboard error.',
        ], 500),
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)->withSession([
        'platform_access_token' => 'access-token-123',
        'platform_refresh_token' => 'refresh-token-123',
        'platform_id_token' => 'id-token-123',
        'platform_token_expires_at' => now()->addHour()->timestamp,
    ])->get(route('workspace.index'))
        ->assertRedirect(route('login'))
        ->assertSessionMissing([
            'platform_access_token',
            'platform_refresh_token',
            'platform_id_token',
            'platform_token_expires_at',
        ]);
});
