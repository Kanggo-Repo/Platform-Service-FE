<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $explicitTitle = trim($__env->yieldContent('title', ''));
        $explicitTopbarTitle = trim($__env->yieldContent('topbar-title', ''));
        $explicitTopbarTitleHtml = trim($__env->yieldContent('topbar-title-html', ''));
        $topbarTitle = $explicitTopbarTitle !== '' ? $explicitTopbarTitle : ($explicitTitle !== '' ? $explicitTitle : 'Platform Service');
        $routeTitleMap = [
            'workspace.*' => 'Dashboard',
            'profile.*' => 'Profile',
            'workers.*' => 'Tukang',
            'skills.*' => 'Keahlian',
            'platform.access.pending' => 'Akses Menunggu Persetujuan',
            'settings.*' => 'Pengaturan',
            'admin.roles.*' => 'Pengaturan',
            'admin.registration.*' => 'Pengaturan',
        ];

        if ($explicitTopbarTitle === '') {
            foreach ($routeTitleMap as $pattern => $title) {
                if (request()->routeIs($pattern)) {
                    $topbarTitle = $title;
                    break;
                }
            }
        }

        $supplyFeBaseUrl = rtrim((string) config('services.supply_fe.base_url', ''), '/');
        $supplyFeUrl = static fn (string $path = ''): string => $supplyFeBaseUrl !== ''
            ? $supplyFeBaseUrl.'/'.ltrim($path, '/')
            : '#';
        $calculationFeBaseUrl = rtrim((string) config('services.calculation_fe.base_url', ''), '/');
        $calculationFeUrl = static fn (string $path = ''): string => $calculationFeBaseUrl !== ''
            ? $calculationFeBaseUrl.'/'.ltrim($path, '/')
            : '#';
    @endphp
    <title>{{ $topbarTitle }}</title>
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Anton&family=Montserrat:ital,wght@0,100..900;1,100..900&family=Nunito:ital,wght@0,200..1000;1,200..1000&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/global.css') }}?v={{ @filemtime(public_path('css/global.css')) }}">
    <script src="{{ asset('js/number-helper-client.js') }}"></script>
    <style>
        html:not(.page-ready) body {
            opacity: 0;
            transition: opacity 0.15s ease-in;
        }

        html.page-ready body {
            opacity: 1;
        }

        .topbar-account {
            margin-left: auto;
            display: inline-flex;
            align-items: center;
        }

        .topbar-account-dropdown .dropdown-toggle::after {
            display: none;
        }

        .topbar-account-trigger {
            min-height: 44px;
            padding: 6px 8px 6px 12px;
            border: 1px solid rgba(226, 232, 240, 0.95);
            border-radius: 18px;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.98) 0%, rgba(248, 250, 252, 0.98) 100%);
            display: inline-flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 10px 22px rgba(15, 23, 42, 0.08);
            transition: transform .16s ease, box-shadow .16s ease, border-color .16s ease;
        }

        .topbar-account-trigger:hover,
        .topbar-account-trigger:focus {
            transform: translateY(-1px);
            border-color: rgba(137, 19, 19, 0.22);
            box-shadow: 0 14px 28px rgba(15, 23, 42, 0.12);
        }

        .topbar-account-meta {
            display: grid;
            gap: 1px;
            text-align: right;
        }

        .topbar-account-name {
            font-family: 'Montserrat', sans-serif;
            font-size: 13px;
            line-height: 1.1;
            font-weight: 800;
            letter-spacing: -0.02em;
            background: linear-gradient(135deg, #891313 0%, #e10009 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            white-space: nowrap;
            max-width: 180px;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .topbar-role {
            font-size: 10px;
            font-weight: 800;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            white-space: nowrap;
        }

        .topbar-avatar {
            width: 34px;
            height: 34px;
            border-radius: 12px;
            background: linear-gradient(135deg, #891313 0%, #e10009 100%);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 13px;
            font-weight: 900;
            box-shadow: 0 10px 20px rgba(137, 19, 19, 0.18);
            flex-shrink: 0;
        }

        .topbar-chevron {
            color: #94a3b8;
            font-size: 11px;
            transition: transform .16s ease;
        }

        .topbar-account-dropdown .dropdown-toggle[aria-expanded="true"] .topbar-chevron {
            transform: rotate(180deg);
        }

        .topbar-account-menu {
            width: min(280px, calc(100vw - 32px));
            margin-top: 10px !important;
            border: 1px solid rgba(226, 232, 240, 0.95);
            border-radius: 20px;
            padding: 10px;
            box-shadow: 0 20px 42px rgba(15, 23, 42, 0.16);
        }

        .topbar-menu-head {
            padding: 10px 12px 12px;
            border-radius: 14px;
            background: linear-gradient(135deg, rgba(255, 243, 240, 0.92) 0%, rgba(248, 250, 252, 0.95) 100%);
            border: 1px solid rgba(244, 196, 191, 0.8);
            margin-bottom: 8px;
        }

        .topbar-menu-name {
            font-size: 0.92rem;
            line-height: 1.15;
            font-weight: 800;
            color: #172033;
            margin: 0;
            letter-spacing: -0.02em;
        }

        .topbar-menu-email {
            font-size: 0.74rem;
            color: #6b7280;
            margin: 4px 0 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .topbar-menu-role {
            display: inline-flex;
            align-items: center;
            margin-top: 8px;
            padding: 5px 9px;
            border-radius: 999px;
            background: #fff;
            border: 1px solid rgba(226, 232, 240, 0.9);
            color: #891313;
            font-size: 0.68rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .topbar-account-menu .dropdown-item {
            display: flex;
            align-items: center;
            gap: 10px;
            border-radius: 12px;
            padding: 10px 12px;
            color: #172033;
            font-size: 0.84rem;
            font-weight: 700;
        }

        .topbar-account-menu .dropdown-item:hover,
        .topbar-account-menu .dropdown-item:focus {
            background: #f8fafc;
        }

        .topbar-account-menu .dropdown-item.text-danger {
            color: #b91c1c !important;
        }

        .topbar-account-menu .dropdown-divider {
            margin: 8px 2px;
            border-color: rgba(226, 232, 240, 0.9);
        }

        .topbar-logout-button {
            width: 100%;
            border: none;
            background: transparent;
            text-align: left;
        }

        .page-content {
            min-height: calc(100vh - 96px);
        }

        .material-wrapper:hover .nav-dropdown-menu,
        .work-item-wrapper:hover .nav-dropdown-menu,
        .settings-wrapper:hover .nav-dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
            pointer-events: auto;
        }

        .sidebar-nav .material-wrapper:hover .nav-dropdown-menu,
        .sidebar-nav .work-item-wrapper:hover .nav-dropdown-menu,
        .sidebar-nav .settings-wrapper:hover .nav-dropdown-menu {
            transform: translateX(0);
        }

        .sidebar-warning-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 18px;
            height: 18px;
            margin-left: 8px;
            padding: 0 6px;
            border-radius: 999px;
            background: #E9D502;
            color: #4a3f00;
            font-size: 10px;
            font-weight: 800;
            line-height: 1;
            box-shadow: 0 8px 18px rgba(233, 213, 2, 0.28);
        }

        .material-wrapper .nav-link-btn,
        .work-item-wrapper .nav-link-btn,
        .settings-wrapper .nav-link-btn {
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .artifact-loading {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 48px 24px;
            width: 100%;
            min-height: 160px;
        }

        .artifact-loading--compact {
            padding: 28px 18px;
            min-height: 120px;
        }

        .artifact-loading__spinner {
            width: 48px;
            height: 48px;
            position: relative;
            border-radius: 50%;
            animation: artifact-spin 3s linear infinite;
        }

        .artifact-loading--compact .artifact-loading__spinner {
            width: 38px;
            height: 38px;
        }

        .artifact-loading__spinner::before,
        .artifact-loading__spinner::after {
            content: "";
            position: absolute;
            inset: 0;
            border-radius: 50%;
            border: 4px solid transparent;
        }

        .artifact-loading__spinner::before {
            border-top-color: #f59e0b;
            border-right-color: rgba(245, 158, 11, 0.3);
            animation: artifact-spin-reverse 1.6s linear infinite;
        }

        .artifact-loading__spinner::after {
            inset: 6px;
            border-bottom-color: #0ea5e9;
            border-left-color: rgba(14, 165, 233, 0.28);
            animation: artifact-spin 1.15s linear infinite;
        }

        @keyframes artifact-spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @keyframes artifact-spin-reverse {
            0% { transform: rotate(360deg); }
            100% { transform: rotate(0deg); }
        }
    </style>
    <script>
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function () {
                requestAnimationFrame(function () {
                    document.documentElement.classList.add('page-ready');
                });
            });
        } else {
            document.documentElement.classList.add('page-ready');
        }
    </script>
</head>
<body>
    <div class="global-topbar" id="globalTopbar">
        <button type="button" class="topbar-logo-btn" id="navLogoToggle" aria-label="Buka menu">
            <img src="/kanggo.png" alt="Kanggo">
        </button>
        <div class="topbar-title">
            <i class="bi bi-caret-right-fill"></i>
            @if ($explicitTopbarTitleHtml !== '')
                {!! $explicitTopbarTitleHtml !!}
            @else
                {{ $topbarTitle }}
            @endif
            @yield('topbar-badge')
        </div>
        <div class="topbar-account">
            @auth
                @php
                    $activeUser = auth()->user();
                    $activeRole = $activeUser->getRoleNames()->first() ?? 'user';
                @endphp
                <div class="dropdown topbar-account-dropdown">
                    <button class="btn topbar-account-trigger dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="topbar-account-meta">
                            <span class="topbar-account-name">{{ $activeUser->name }}</span>
                            <span class="topbar-role">{{ \Illuminate\Support\Str::headline($activeRole) }}</span>
                        </div>
                        <span class="topbar-avatar" aria-hidden="true" title="{{ $activeUser->name }}">
                            {{ strtoupper(substr($activeUser->name, 0, 1)) }}
                        </span>
                        <i class="bi bi-chevron-down topbar-chevron"></i>
                    </button>

                    <div class="dropdown-menu dropdown-menu-end topbar-account-menu">
                        <div class="topbar-menu-head">
                            <p class="topbar-menu-name">{{ $activeUser->name }}</p>
                            <p class="topbar-menu-email">{{ $activeUser->email }}</p>
                            <span class="topbar-menu-role">{{ \Illuminate\Support\Str::headline($activeRole) }}</span>
                        </div>

                        <a class="dropdown-item" href="{{ route('profile.show') }}">
                            <i class="bi bi-person-badge"></i>
                            <span>Profile</span>
                        </a>

                        <div class="dropdown-divider"></div>

                        <form method="POST" action="{{ route('auth.logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item text-danger topbar-logout-button">
                                <i class="bi bi-box-arrow-right"></i>
                                <span>Logout</span>
                            </button>
                        </form>
                    </div>
                </div>
            @endauth
        </div>
    </div>

    <div class="nav-overlay" id="navOverlay"></div>
    @php
        $sidebarUser = auth()->user();
        $sidebarGate = app(\App\Support\Auth\PlatformPermissionGate::class);
        $canSeeDashboard = $sidebarUser !== null;
        $canSeeRoles = $sidebarGate->allowsAny($sidebarUser, [
            'roles.view', 'roles.create', 'roles.update', 'roles.delete', 'roles.manage', 'settings.manage',
        ]);
        $canSeeUsers = $sidebarGate->allowsAny($sidebarUser, [
            'users.view', 'users.create', 'users.update', 'users.delete', 'users.assign-roles', 'users.manage', 'settings.manage',
        ]);
        $canSeeRecommendations = $sidebarGate->allowsAny($sidebarUser, [
            'recommendations.view', 'recommendations.update', 'recommendations.manage', 'settings.manage',
        ]);
        $canSeeStoreSearchRadiusSettings = $sidebarGate->allowsAny($sidebarUser, [
            'store-search-radius.view', 'store-search-radius.update', 'store-search-radius.manage', 'settings.manage',
        ]);
        $canSeeTaxonomy = $sidebarGate->allowsAny($sidebarUser, [
            'work-taxonomy.view', 'work-taxonomy.create', 'work-taxonomy.update', 'work-taxonomy.delete', 'work-taxonomy.manage', 'settings.manage',
        ]);
        $canSeeRegistration = $sidebarGate->allowsAny($sidebarUser, [
            'users.update', 'users.manage', 'settings.manage',
        ]);
        $canSeeSettings = $canSeeRoles || $canSeeUsers || $canSeeRecommendations || $canSeeStoreSearchRadiusSettings || $canSeeTaxonomy || $canSeeRegistration;
        $isSettingsRoute = request()->routeIs('settings.*', 'admin.roles.*', 'admin.registration.*');
    @endphp
    <aside class="sidebar-nav" id="sidebarNav">
        <div class="nav">
            @if($canSeeDashboard)
                <a href="{{ route('workspace.index') }}" class="{{ request()->routeIs('workspace.*') ? 'active' : '' }}">
                    <i class="bi bi-houses"></i> Dashboard
                </a>
            @endif

            @if($supplyFeBaseUrl !== '')
                <div class="nav-dropdown-wrapper material-wrapper">
                    <a href="{{ $supplyFeUrl('/materials') }}" class="nav-link-btn" id="materialNavLink">
                        <i class="bi bi-box-seam"></i> Material <i class="bi bi-caret-right-fill nav-caret" style="font-size: 10px; opacity: 0.7;"></i>
                    </a>

                    <div class="nav-dropdown-menu" id="materialDropdownMenu">
                        <div class="nav-dropdown-content">
                            <div class="dropdown-item-parent">
                                <div class="dropdown-item-trigger" tabindex="0" role="button">
                                    Lihat Material
                                    <i class="bi bi-caret-right-fill ms-auto" style="font-size: 10px; opacity: 0.6;"></i>
                                </div>

                                <div class="dropdown-sub-menu">
                                    <div class="dropdown-header">Pilih Material</div>
                                    <div class="dropdown-grid">
                                        <label class="dropdown-item checkbox-item"><input type="checkbox" class="nav-material-toggle" data-material="brick"> Bata</label>
                                        <label class="dropdown-item checkbox-item"><input type="checkbox" class="nav-material-toggle" data-material="cat"> Cat</label>
                                        <label class="dropdown-item checkbox-item"><input type="checkbox" class="nav-material-toggle" data-material="ceramic"> Keramik</label>
                                        <label class="dropdown-item checkbox-item"><input type="checkbox" class="nav-material-toggle" data-material="sand"> Pasir</label>
                                        <label class="dropdown-item checkbox-item"><input type="checkbox" class="nav-material-toggle" data-material="cement"> Semen</label>
                                        <label class="dropdown-item checkbox-item"><input type="checkbox" class="nav-material-toggle" data-material="steel"> Besi</label>
                                        <label class="dropdown-item checkbox-item"><input type="checkbox" class="nav-material-toggle" data-material="kasa_gypsum"> Kasa Gypsum</label>
                                        <label class="dropdown-item checkbox-item"><input type="checkbox" class="nav-material-toggle" data-material="paku_tembak"> Paku Tembak</label>
                                        <label class="dropdown-item checkbox-item"><input type="checkbox" class="nav-material-toggle" data-material="paku"> Paku</label>
                                    </div>
                                    <div class="nav-material-actions">
                                        <button type="button" id="applyMaterialFilter" class="btn btn-primary nav-material-apply">Terapkan Filter</button>
                                        <button type="button" id="resetMaterialFilterNav" class="btn btn-primary nav-material-reset">Reset</button>
                                    </div>
                                </div>
                            </div>

                            <div class="dropdown-item-parent">
                                <div class="dropdown-item-trigger" tabindex="0" role="button">
                                    Tambah Material
                                    <i class="bi bi-caret-right-fill ms-auto" style="font-size: 10px; opacity: 0.6;"></i>
                                </div>

                                <div class="dropdown-sub-menu">
                                    <div class="dropdown-header">Pilih Material</div>
                                    <div class="dropdown-grid">
                                        <a href="{{ $supplyFeUrl('/bricks/create?embedded=1') }}" class="dropdown-item js-open-remote-material-modal" data-modal-title="Tambah Bata">Bata</a>
                                        <a href="{{ $supplyFeUrl('/cats/create?embedded=1') }}" class="dropdown-item js-open-remote-material-modal" data-modal-title="Tambah Cat">Cat</a>
                                        <a href="{{ $supplyFeUrl('/ceramics/create?embedded=1') }}" class="dropdown-item js-open-remote-material-modal" data-modal-title="Tambah Keramik">Keramik</a>
                                        <a href="{{ $supplyFeUrl('/sands/create?embedded=1') }}" class="dropdown-item js-open-remote-material-modal" data-modal-title="Tambah Pasir">Pasir</a>
                                        <a href="{{ $supplyFeUrl('/cements/create?embedded=1') }}" class="dropdown-item js-open-remote-material-modal" data-modal-title="Tambah Semen">Semen</a>
                                        <a href="{{ $supplyFeUrl('/steels/create?embedded=1') }}" class="dropdown-item js-open-remote-material-modal" data-modal-title="Tambah Besi">Besi</a>
                                        <a href="{{ $supplyFeUrl('/kasa_gypsums/create?embedded=1') }}" class="dropdown-item js-open-remote-material-modal" data-modal-title="Tambah Kasa Gypsum">Kasa Gypsum</a>
                                        <a href="{{ $supplyFeUrl('/paku_tembaks/create?embedded=1') }}" class="dropdown-item js-open-remote-material-modal" data-modal-title="Tambah Paku Tembak">Paku Tembak</a>
                                        <a href="{{ $supplyFeUrl('/pakus/create?embedded=1') }}" class="dropdown-item js-open-remote-material-modal" data-modal-title="Tambah Paku">Paku</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <a href="{{ $supplyFeUrl('/stores') }}" target="_self">
                    <i class="bi bi-shop"></i> Toko
                    @if(($sidebarStoresMissingMapCount ?? 0) > 0)
                        <span class="sidebar-warning-count" title="{{ $sidebarStoresMissingMapCount }} toko memerlukan perhatian data lokasi">
                            {{ $sidebarStoresMissingMapCount }}
                        </span>
                    @endif
                </a>
            @endif

            @if($calculationFeBaseUrl !== '')
                <div class="nav-dropdown-wrapper work-item-wrapper">
                    <button type="button" class="nav-link-btn" id="workItemDropdownToggle">
                        <i class="bi bi-building-gear"></i> Proyek
                        @if(($sidebarProjectDraftCount ?? 0) > 0)
                            <span class="sidebar-warning-count" title="{{ $sidebarProjectDraftCount }} draft proyek aktif">
                                {{ $sidebarProjectDraftCount }}
                            </span>
                        @endif
                        <i class="bi bi-caret-right-fill nav-caret" style="font-size: 10px; opacity: 0.7;"></i>
                    </button>

                    <div class="nav-dropdown-menu" id="workItemDropdownMenu">
                        <div class="nav-dropdown-content">
                            <div class="dropdown-item-parent">
                                <a href="{{ $calculationFeUrl('/work-items') }}" class="dropdown-item-trigger d-flex align-items-center text-decoration-none" role="button">
                                    Lihat Daftar Item Pekerjaan
                                </a>
                            </div>
                            <div class="dropdown-item-parent">
                                <a href="{{ $calculationFeUrl('/material-calculations/start') }}" class="dropdown-item-trigger d-flex align-items-center text-decoration-none" role="button">
                                    Hitung Item Pekerjaan Proyek
                                </a>
                            </div>
                            <div class="dropdown-item-parent">
                                <a href="https://docs.google.com/spreadsheets/d/1tsEQ3a4duHw2AROxsbHaz41n3EiwoFQEpqmWc5XdMP4/edit?usp=sharing" target="_blank" class="dropdown-item-trigger d-flex align-items-center text-decoration-none" role="button">
                                    Tambah Item Pekerjaan
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <a href="{{ route('workers.index') }}" class="{{ request()->routeIs('workers.*') ? 'active' : '' }}">
                <i class="bi bi-people"></i> Tukang
            </a>

            <a href="{{ route('skills.index') }}" class="{{ request()->routeIs('skills.*') ? 'active' : '' }}">
                <i class="bi bi-tools"></i> Keahlian
            </a>

            @if($supplyFeBaseUrl !== '')
                <a href="{{ $supplyFeUrl('/units') }}" target="_self">
                    <i class="bi bi-rulers"></i> Satuan
                </a>
            @endif

            @if($canSeeSettings)
                <div class="nav-dropdown-wrapper settings-wrapper" style="margin-left: auto;">
                    <a href="{{ $canSeeUsers ? route('settings.users.index') : route('settings.roles.index') }}" class="nav-link-btn {{ $isSettingsRoute ? 'active' : '' }}" id="settingsDropdownToggle">
                        <i class="bi bi-gear"></i> Pengaturan <i class="bi bi-caret-right-fill nav-caret" style="font-size: 10px; opacity: 0.7;"></i>
                    </a>

                    <div class="nav-dropdown-menu" id="settingsDropdownMenu" style="left: auto; right: 0;">
                        <div class="nav-dropdown-content">
                            @if($calculationFeBaseUrl !== '' && $canSeeRecommendations)
                                <div class="dropdown-item-parent">
                                    <a href="{{ $calculationFeUrl('/settings/recommendations') }}" class="dropdown-item-trigger d-flex align-items-center text-decoration-none" role="button">
                                        Manajemen Filter Preferensi
                                    </a>
                                </div>
                            @endif
                            @if($supplyFeBaseUrl !== '' && $canSeeStoreSearchRadiusSettings)
                                <div class="dropdown-item-parent">
                                    <a href="{{ $supplyFeUrl('/settings/store-search-radius') }}" class="dropdown-item-trigger d-flex align-items-center text-decoration-none" role="button">
                                        Radius Pencarian Toko
                                    </a>
                                </div>
                            @endif
                            @if($canSeeTaxonomy)
                                <div class="dropdown-item-parent">
                                    <a href="{{ $calculationFeUrl('/settings/work-floors') }}" class="dropdown-item-trigger d-flex align-items-center text-decoration-none" role="button">
                                        Manajemen Lantai
                                    </a>
                                </div>
                                <div class="dropdown-item-parent">
                                    <a href="{{ $calculationFeUrl('/settings/work-areas') }}" class="dropdown-item-trigger d-flex align-items-center text-decoration-none" role="button">
                                        Manajemen Area
                                    </a>
                                </div>
                                <div class="dropdown-item-parent">
                                    <a href="{{ $calculationFeUrl('/settings/work-fields') }}" class="dropdown-item-trigger d-flex align-items-center text-decoration-none" role="button">
                                        Manajemen Bidang
                                    </a>
                                </div>
                            @endif
                            @if($canSeeUsers)
                                <div class="dropdown-item-parent">
                                    <a href="{{ route('settings.users.index') }}" class="dropdown-item-trigger d-flex align-items-center text-decoration-none {{ request()->routeIs('settings.users.*') ? 'active' : '' }}" role="button">
                                        Manajemen User
                                    </a>
                                </div>
                            @endif
                            @if($canSeeRoles)
                                <div class="dropdown-item-parent">
                                    <a href="{{ route('settings.roles.index') }}" class="dropdown-item-trigger d-flex align-items-center text-decoration-none {{ request()->routeIs('settings.roles.*') ? 'active' : '' }}" role="button">
                                        Manajemen Role
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </aside>

    <div class="container page-content">
        <div id="toast-container" class="toast-container" role="status" aria-live="polite" aria-atomic="true"></div>

        @php
            $toasts = [];
            if (session('success')) {
                $toasts[] = ['type' => 'success', 'message' => session('success')];
            }
            if (session('error') && ! trim($__env->yieldContent('suppress_session_error_toast'))) {
                $toasts[] = ['type' => 'error', 'message' => session('error')];
            }
        @endphp
        <script>
            window.__TOASTS__ = @json($toasts);
        </script>

        @yield('content')
    </div>

    <div id="confirm-modal" class="confirm-modal" aria-hidden="true">
        <div class="confirm-backdrop" data-confirm-close></div>
        <div class="confirm-dialog" role="dialog" aria-modal="true" aria-labelledby="confirm-title">
            <div class="confirm-header">
                <div class="confirm-title" id="confirm-title">Konfirmasi</div>
                <button type="button" class="confirm-close" data-confirm-close aria-label="Tutup">&times;</button>
            </div>
            <div class="confirm-message" id="confirm-message">Apakah Anda yakin?</div>
            <div class="confirm-actions">
                <button type="button" class="confirm-btn cancel" id="confirm-cancel">Batal</button>
                <button type="button" class="confirm-btn confirm" id="confirm-ok">Hapus</button>
            </div>
        </div>
    </div>

    <div id="remoteMaterialModal" class="floating-modal global-modal-layer">
        <div class="floating-modal-backdrop" data-remote-material-close></div>
        <div class="floating-modal-content" style="width: min(1280px, calc(100vw - 40px)); max-width: 1280px; height: min(92vh, 940px);">
            <div class="floating-modal-header">
                <h2 id="remoteMaterialModalTitle">Tambah Material</h2>
                <button class="floating-modal-close" id="remoteMaterialCloseModal">&times;</button>
            </div>
            <div class="floating-modal-body" style="padding: 0; overflow: hidden; background: #fff;">
                <iframe
                    id="remoteMaterialModalFrame"
                    title="Form Material Supply"
                    src="about:blank"
                    style="width: 100%; height: 76vh; min-height: 0; border: 0; background: #fff;">
                </iframe>
            </div>
        </div>
    </div>

    @stack('scripts')

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('js/search-debounce.js') }}"></script>
    <script src="{{ asset('js/lazy-loading.js') }}"></script>
    <script>
        (function () {
            const navOverlay = document.getElementById('navOverlay');
            const navLogoToggle = document.getElementById('navLogoToggle');

            function closeNav() {
                document.body.classList.remove('nav-open');
            }

            function toggleNav() {
                document.body.classList.toggle('nav-open');
            }

            navLogoToggle?.addEventListener('click', toggleNav);
            navOverlay?.addEventListener('click', closeNav);

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    closeNav();
                }
            });

            const activeDropdowns = new Set();

            function initializeDropdown(toggleId, menuId) {
                const dropdownToggle = document.getElementById(toggleId);
                const dropdownMenu = document.getElementById(menuId);

                if (!dropdownToggle || !dropdownMenu) {
                    return;
                }

                const openDropdown = () => {
                    activeDropdowns.forEach((dropdownInfo) => {
                        if (dropdownInfo.toggleId !== toggleId) {
                            dropdownInfo.closeDropdown();
                        }
                    });
                    dropdownMenu.classList.add('show');
                    dropdownToggle.classList.add('dropdown-open');
                    dropdownToggle.setAttribute('aria-expanded', 'true');
                };

                const closeDropdown = () => {
                    dropdownMenu.classList.remove('show');
                    dropdownToggle.classList.remove('dropdown-open');
                    dropdownToggle.setAttribute('aria-expanded', 'false');
                };

                activeDropdowns.add({
                    toggleId,
                    closeDropdown,
                });

                dropdownToggle.addEventListener('click', function (event) {
                    event.preventDefault();
                    event.stopPropagation();

                    if (dropdownMenu.classList.contains('show')) {
                        closeDropdown();
                        return;
                    }

                    openDropdown();
                });

                document.addEventListener('click', function (event) {
                    if (dropdownMenu.contains(event.target) || dropdownToggle.contains(event.target)) {
                        return;
                    }

                    closeDropdown();
                });
            }

            initializeDropdown('materialNavLink', 'materialDropdownMenu');
            initializeDropdown('workItemDropdownToggle', 'workItemDropdownMenu');
            initializeDropdown('settingsDropdownToggle', 'settingsDropdownMenu');

            const materialNavLink = document.getElementById('materialNavLink');
            const navToggles = document.querySelectorAll('.nav-material-toggle');
            const applyFilterBtn = document.getElementById('applyMaterialFilter');
            const resetFilterBtn = document.getElementById('resetMaterialFilterNav');
            const materialsBaseUrl = @json($supplyFeUrl('/materials'));
            const sharedLastUrlCookie = 'supply_last_materials_url';
            const remoteMaterialModal = document.getElementById('remoteMaterialModal');
            const remoteMaterialFrame = document.getElementById('remoteMaterialModalFrame');
            const remoteMaterialTitle = document.getElementById('remoteMaterialModalTitle');
            const remoteMaterialCloseTargets = document.querySelectorAll('[data-remote-material-close], #remoteMaterialCloseModal');

            function readCookie(name) {
                const pattern = new RegExp(`(?:^|; )${name.replace(/[$()*+.?[\\\]^{|}]/g, '\\$&')}=([^;]*)`);
                const match = document.cookie.match(pattern);
                return match ? decodeURIComponent(match[1]) : '';
            }

            function openRemoteMaterialModal(url, title) {
                if (!remoteMaterialModal || !remoteMaterialFrame || !url || url === '#') {
                    if (url && url !== '#') {
                        window.location.href = url;
                    }
                    return;
                }

                remoteMaterialTitle.textContent = title || 'Tambah Material';
                remoteMaterialFrame.src = url;
                remoteMaterialModal.classList.add('active');
                document.body.classList.add('modal-open');
            }

            function closeRemoteMaterialModal() {
                if (!remoteMaterialModal || !remoteMaterialFrame) {
                    return;
                }

                remoteMaterialModal.classList.remove('active');
                document.body.classList.remove('modal-open');
                remoteMaterialFrame.style.height = '76vh';
                window.setTimeout(() => {
                    remoteMaterialFrame.src = 'about:blank';
                }, 160);
            }

            remoteMaterialCloseTargets.forEach((target) => {
                target?.addEventListener('click', closeRemoteMaterialModal);
            });

            document.querySelectorAll('.js-open-remote-material-modal').forEach((link) => {
                link.addEventListener('click', function (event) {
                    event.preventDefault();
                    openRemoteMaterialModal(this.href, this.dataset.modalTitle || this.textContent.trim());
                });
            });

            window.addEventListener('message', function (event) {
                if (!remoteMaterialFrame || event.source !== remoteMaterialFrame.contentWindow) {
                    return;
                }

                if (event.data?.type !== 'supply-material-embedded-height') {
                    return;
                }

                const nextHeight = Number(event.data.height || 0);
                if (!Number.isFinite(nextHeight) || nextHeight <= 0) {
                    return;
                }

                const maxHeight = Math.max(window.innerHeight - 180, 420);
                remoteMaterialFrame.style.height = `${Math.min(nextHeight, maxHeight)}px`;
            });

            function isMaterialsIndexUrl(url) {
                if (!url || materialsBaseUrl === '#') {
                    return false;
                }

                try {
                    const parsed = new URL(url, window.location.origin);
                    const base = new URL(materialsBaseUrl, window.location.origin);

                    return parsed.origin === base.origin && parsed.pathname === base.pathname;
                } catch (error) {
                    return false;
                }
            }

            materialNavLink?.addEventListener('click', function (event) {
                if (materialsBaseUrl === '#') {
                    return;
                }

                event.preventDefault();

                const lastUrl = readCookie(sharedLastUrlCookie);
                if (isMaterialsIndexUrl(lastUrl)) {
                    window.location.href = lastUrl;
                    return;
                }

                window.location.href = materialsBaseUrl;
            });

            if (navToggles.length > 0 && applyFilterBtn && resetFilterBtn) {
                const selectedSet = new Set(
                    readCookie(sharedLastUrlCookie)
                        ? (() => {
                            try {
                                const parsed = new URL(readCookie(sharedLastUrlCookie), window.location.origin);
                                const repeated = parsed.searchParams.getAll('materials[]');
                                const csv = (parsed.searchParams.get('materials') || '')
                                    .split(',')
                                    .map((value) => value.trim())
                                    .filter(Boolean);

                                return [...repeated, ...csv];
                            } catch (error) {
                                return [];
                            }
                        })()
                        : []
                );

                navToggles.forEach((toggle) => {
                    const wrapper = toggle.closest('.dropdown-item');
                    toggle.checked = selectedSet.has(toggle.dataset.material);
                    wrapper?.classList.toggle('checked', toggle.checked);

                    toggle.addEventListener('change', function () {
                        wrapper?.classList.toggle('checked', this.checked);
                    });
                });

                applyFilterBtn.addEventListener('click', function () {
                    const selected = Array.from(navToggles)
                        .filter((toggle) => toggle.checked)
                        .map((toggle) => toggle.dataset.material);

                    const targetUrl = new URL(materialsBaseUrl, window.location.origin);
                    if (selected.length > 0) {
                        targetUrl.searchParams.set('materials', selected.join(','));
                        targetUrl.searchParams.set('tab', selected[0]);
                    }
                    window.location.href = targetUrl.toString();
                });

                resetFilterBtn.addEventListener('click', function () {
                    navToggles.forEach((toggle) => {
                        toggle.checked = false;
                        toggle.closest('.dropdown-item')?.classList.remove('checked');
                    });
                    window.location.href = materialsBaseUrl;
                });
            }
        })();
    </script>
    <script>
        (function () {
            const container = document.getElementById('toast-container');
            if (!container) {
                return;
            }

            function createToast(message, type = 'success', options = {}) {
                const title = options.title || (type === 'error' ? 'Gagal' : 'Berhasil');
                const duration = Math.max(1800, Number(options.duration || 3200));
                const toast = document.createElement('div');
                toast.className = `toast-notification ${type}`;

                toast.innerHTML = `
                    <div class="toast-icon"><i class="bi ${type === 'error' ? 'bi-exclamation-octagon-fill' : 'bi-check-circle-fill'}"></i></div>
                    <div class="toast-content">
                        <div class="toast-title">${title}</div>
                        <div class="toast-message">${message}</div>
                    </div>
                    <button type="button" class="toast-close" aria-label="Tutup"></button>
                    <div class="toast-progress"></div>
                `;

                container.appendChild(toast);
                requestAnimationFrame(() => toast.classList.add('show'));

                let removed = false;
                const removeToast = () => {
                    if (removed) return;
                    removed = true;
                    toast.classList.add('hide');
                    window.setTimeout(() => toast.remove(), 250);
                };

                const timeoutId = window.setTimeout(removeToast, duration);
                toast.querySelector('.toast-close')?.addEventListener('click', () => {
                    window.clearTimeout(timeoutId);
                    removeToast();
                });
            }

            window.showToast = function (message, type = 'success', options = {}) {
                createToast(message, type, options);
            };

            const initialToasts = Array.isArray(window.__TOASTS__) ? window.__TOASTS__ : [];
            initialToasts.forEach((toast) => {
                if (toast?.message) {
                    createToast(toast.message, toast.type || 'success', {
                        duration: toast.duration,
                        title: toast.title,
                    });
                }
            });

            const pending = sessionStorage.getItem('pendingToast');
            if (pending) {
                try {
                    const parsed = JSON.parse(pending);
                    if (parsed?.message) {
                        createToast(parsed.message, parsed.type || 'success', parsed.options || {});
                    }
                } catch (error) {
                    console.error('Failed to parse pending toast', error);
                }
                sessionStorage.removeItem('pendingToast');
            }
        })();
    </script>
    <script>
        (function () {
            const modal = document.getElementById('confirm-modal');
            if (!modal) return;

            const titleEl = modal.querySelector('#confirm-title');
            const messageEl = modal.querySelector('#confirm-message');
            const okBtn = modal.querySelector('#confirm-ok');
            const cancelBtn = modal.querySelector('#confirm-cancel');
            const closeTargets = modal.querySelectorAll('[data-confirm-close]');

            let resolver = null;
            let cancelValue = false;

            function closeConfirm(result) {
                if (!resolver) return;
                const resolve = resolver;
                resolver = null;
                modal.classList.remove('active');
                modal.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('confirm-open');
                resolve(result);
            }

            function openConfirm(options) {
                const opts = options || {};
                const hideCancel = !!opts.hideCancel;

                titleEl.textContent = opts.title || 'Konfirmasi';
                messageEl.textContent = opts.message || 'Apakah Anda yakin?';
                okBtn.textContent = opts.confirmText || (hideCancel ? 'Tutup' : 'Hapus');
                cancelBtn.textContent = opts.cancelText || 'Batal';
                modal.dataset.type = opts.type || 'danger';
                cancelBtn.hidden = hideCancel;
                cancelBtn.style.display = hideCancel ? 'none' : '';
                cancelValue = opts.cancelValue !== undefined ? opts.cancelValue : false;

                modal.classList.add('active');
                modal.setAttribute('aria-hidden', 'false');
                document.body.classList.add('confirm-open');
            }

            window.showConfirm = function (options) {
                return new Promise((resolve) => {
                    if (resolver) {
                        resolver(false);
                    }
                    resolver = resolve;
                    openConfirm(options);
                });
            };

            okBtn?.addEventListener('click', () => closeConfirm(true));
            cancelBtn?.addEventListener('click', () => closeConfirm(cancelValue));
            closeTargets.forEach((el) => el.addEventListener('click', () => closeConfirm(false)));

            document.addEventListener('keydown', (event) => {
                if (!modal.classList.contains('active')) return;
                if (event.key === 'Escape') {
                    closeConfirm(false);
                }
            });

            document.addEventListener('submit', async (event) => {
                const form = event.target;
                if (!(form instanceof HTMLFormElement)) return;
                const message = form.getAttribute('data-confirm');
                if (!message) return;

                event.preventDefault();
                const confirmed = await window.showConfirm({
                    title: form.dataset.confirmTitle || 'Konfirmasi',
                    message,
                    confirmText: form.dataset.confirmOk || 'Hapus',
                    cancelText: form.dataset.confirmCancel || 'Batal',
                    type: form.dataset.confirmType || 'danger',
                });

                if (confirmed) {
                    form.submit();
                }
            });
        })();
    </script>
</body>
</html>
