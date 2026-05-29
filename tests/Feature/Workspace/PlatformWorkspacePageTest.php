<?php

use App\Models\User;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config()->set([
        'services.platform_service.base_url' => 'http://127.0.0.1:8011',
        'services.supply_fe.base_url' => 'http://supplyfe.lvh.me:8009',
        'services.supply_service.base_url' => '',
        'services.calculation_fe.base_url' => 'http://calcfe.lvh.me:8001',
        'services.calculation_service.base_url' => 'http://127.0.0.1:8000',
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

    $user = User::factory()->create([
        'role_snapshot' => ['platform_operator'],
        'permission_snapshot' => ['dashboard.view'],
    ]);

    $this->actingAs($user)->withSession([
        'platform_access_token' => 'access-token-123',
    ])->get(route('workspace.index'))
        ->assertRedirect(route('platform.access.pending'));
});

test('access pending page renders transplanted pending access view', function () {
    $user = User::factory()->create([
        'name' => 'Platform User',
        'role_snapshot' => ['platform_operator'],
        'permission_snapshot' => ['dashboard.view'],
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

    $user = User::factory()->create([
        'role_snapshot' => ['platform_operator'],
        'permission_snapshot' => ['dashboard.view'],
    ]);

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
        'role_snapshot' => ['platform_operator'],
        'permission_snapshot' => ['dashboard.view'],
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
        ->assertSee('Manajemen Lantai')
        ->assertSee('Manajemen Area')
        ->assertSee('Manajemen Bidang')
        ->assertSee('http://calcfe.lvh.me:8001/work-items', false)
        ->assertSee('http://calcfe.lvh.me:8001/material-calculations/start', false)
        ->assertSee('http://calcfe.lvh.me:8001/settings/work-floors', false)
        ->assertSee('data-material="brick"', false)
        ->assertSee('Platform Operator')
        ->assertSee('Distribusi Material')
        ->assertDontSee('Draft Hitungan Proyek')
        ->assertDontSee('Log Hitungan Proyek')
        ->assertDontSee('Status Registrasi');
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

test('workspace sidebar shows store warning badge from supply summary', function () {
    config()->set('services.supply_service.base_url', 'http://127.0.0.1:8008');

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
                'services' => [],
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
                'summary' => [],
                'chart' => ['labels' => [], 'data' => []],
                'recent_activities' => [],
                'service_matrix' => [],
            ],
        ]),
        'http://127.0.0.1:8008/api/v1/stores/sidebar-summary' => Http::response([
            'data' => [
                'stores_missing_map_count' => 3,
            ],
        ]),
    ]);

    $user = User::factory()->create([
        'name' => 'Platform User',
        'role_snapshot' => ['platform_operator'],
        'permission_snapshot' => ['dashboard.view'],
    ]);

    $this->actingAs($user)->withSession([
        'platform_access_token' => 'access-token-123',
    ])->get(route('workspace.index'))
        ->assertOk()
        ->assertSee('Toko')
        ->assertSee('3 toko memerlukan perhatian data lokasi');
});

test('workspace sidebar shows project draft badge from calculation drafts', function () {
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
                'services' => [],
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
                'summary' => [],
                'chart' => ['labels' => [], 'data' => []],
                'recent_activities' => [],
                'service_matrix' => [],
            ],
        ]),
        'http://127.0.0.1:8000/api/v1/calculation-drafts*' => Http::response([
            'data' => [
                ['public_id' => 'draft-001'],
                ['public_id' => 'draft-002'],
            ],
        ]),
    ]);

    $user = User::factory()->create([
        'name' => 'Platform User',
        'role_snapshot' => ['platform_operator'],
        'permission_snapshot' => ['dashboard.view'],
    ]);

    $this->actingAs($user)->withSession([
        'platform_access_token' => 'access-token-123',
    ])->get(route('workspace.index'))
        ->assertOk()
        ->assertSee('Proyek')
        ->assertSee('2 draft proyek aktif');
});

test('workers page renders platform-owned placeholder view', function () {
    $user = User::factory()->create([
        'name' => 'Platform User',
        'role_snapshot' => ['platform_operator'],
        'permission_snapshot' => ['workers.view'],
    ]);

    $this->actingAs($user)->withSession([
        'platform_access_token' => 'access-token-123',
    ])->get(route('workers.index'))
        ->assertOk()
        ->assertSee('Under Development')
        ->assertSee('Fitur ini sedang dalam tahap pengembangan');
});

test('skills page renders platform-owned placeholder view', function () {
    $user = User::factory()->create([
        'name' => 'Platform User',
        'role_snapshot' => ['platform_operator'],
        'permission_snapshot' => ['skills.view'],
    ]);

    test('workspace sidebar hides unauthorized modules for limited purchasing role', function () {
        Http::fake([
            'http://127.0.0.1:8011/api/v1/me' => Http::response([
                'data' => [
                    'identity' => [
                        'subject' => 'kc-user-1',
                        'email' => 'purchasing@example.test',
                        'name' => 'Purchasing User',
                    ],
                    'profile' => [
                        'id' => 7,
                        'status' => 'active',
                        'display_name' => 'Purchasing User',
                        'preferred_app' => 'platform',
                    ],
                    'access' => [
                        'pending_access' => false,
                        'allowed_services' => ['platform'],
                        'blocked_services' => ['supply', 'calculation'],
                        'pending_services' => [],
                    ],
                    'roles' => ['purchasing'],
                    'navigation' => [
                        'preferred_route' => 'platform.dashboard',
                    ],
                ],
            ]),
            'http://127.0.0.1:8011/api/v1/navigation' => Http::response([
                'data' => [
                    'services' => [],
                    'preferred_app' => 'platform',
                    'preferred_route' => 'platform.dashboard',
                    'pending_access' => false,
                    'allowed_services' => ['platform'],
                    'blocked_services' => ['supply', 'calculation'],
                    'pending_services' => [],
                ],
            ]),
            'http://127.0.0.1:8011/api/v1/dashboard' => Http::response([
                'data' => [
                    'summary' => [],
                    'chart' => ['labels' => [], 'data' => []],
                    'recent_activities' => [],
                    'service_matrix' => [],
                ],
            ]),
        ]);

        $user = User::factory()->create([
            'name' => 'Purchasing User',
            'role_snapshot' => ['purchasing'],
            'permission_snapshot' => ['dashboard.view', 'stores.view'],
        ]);

        $this->actingAs($user)->withSession([
            'platform_access_token' => 'access-token-123',
        ])->get(route('workspace.index'))
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertSee('Toko')
            ->assertDontSee('Material')
            ->assertDontSee('Proyek')
            ->assertDontSee('Tukang')
            ->assertDontSee('Keahlian')
            ->assertDontSee('Satuan')
            ->assertDontSee('Pengaturan');
    });

    $this->actingAs($user)->withSession([
        'platform_access_token' => 'access-token-123',
    ])->get(route('skills.index'))
        ->assertOk()
        ->assertSee('Under Development')
        ->assertSee('Fitur ini sedang dalam tahap pengembangan');
});
