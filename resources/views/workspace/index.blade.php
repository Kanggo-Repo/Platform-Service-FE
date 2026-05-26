@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
@php
    $summary = $dashboard['summary'] ?? [];
    $chart = $dashboard['chart'] ?? ['labels' => [], 'data' => []];
    $recentActivities = collect($dashboard['recent_activities'] ?? []);
    $serviceMatrix = $dashboard['service_matrix'] ?? [];
    $registrationEnabled = (bool) ($summary['registration_enabled'] ?? false);
@endphp

<div class="row">
    <div>
        <div class="welcome-card p-5 rounded-4 position-relative overflow-hidden" style="background: linear-gradient(135deg, #891313 0%, #4a0404 100%);">
            <div class="position-relative z-1">
                <h1 class="sub-welcome-card display-5 mb-2">Selamat Datang di Platform Service</h1>
                <p class="sub-welcome-card lead">Kelola user, roles, permission, dan akses lintas service supply serta calculation dari satu workspace admin.</p>
            </div>
            <img src="{{ asset('kanggo.png') }}" alt="Logo" class="position-absolute opacity-10" style="height: 100%; width: auto; right: 0; bottom: 0; transform: rotate(0deg);">
        </div>
    </div>
</div>

<div class="stats-grid-container mb-3">
    <div class="modern-stat-card">
        <div class="card-icon-wrapper red">
            <i class="bi bi-people"></i>
        </div>
        <div class="card-content">
            <p class="card-label text-shadow-bottom">Total Pengguna</p>
            <h2 class="card-value text-shadow-bottom">{{ number_format((int) ($summary['total_users'] ?? 0), 0, ',', '.') }}</h2>
        </div>
        <div class="card-overlay red"></div>
    </div>

    <div class="modern-stat-card">
        <div class="card-icon-wrapper cyan">
            <i class="bi bi-shield-lock"></i>
        </div>
        <div class="card-content">
            <p class="card-label text-shadow-bottom">Total Role</p>
            <h2 class="card-value text-shadow-bottom">{{ number_format((int) ($summary['role_count'] ?? 0), 0, ',', '.') }}</h2>
        </div>
        <div class="card-overlay cyan"></div>
    </div>

    <div class="modern-stat-card">
        <div class="card-icon-wrapper orange">
            <i class="bi bi-key"></i>
        </div>
        <div class="card-content">
            <p class="card-label text-shadow-bottom">Permission Catalog</p>
            <h2 class="card-value text-shadow-bottom">{{ number_format((int) ($summary['permission_count'] ?? 0), 0, ',', '.') }}</h2>
        </div>
        <div class="card-overlay orange"></div>
    </div>

    <div class="modern-stat-card">
        <div class="card-meta">
            <span class="status-badge {{ $registrationEnabled ? 'green' : 'red' }} text-shadow-bottom">
                {{ $registrationEnabled ? 'Aktif' : 'Nonaktif' }}
            </span>
        </div>
        <div class="card-icon-wrapper green">
            <i class="bi bi-person-plus"></i>
        </div>
        <div class="card-content">
            <p class="card-label text-shadow-bottom">Register Akun</p>
            <h2 class="card-value text-shadow-bottom">{{ $registrationEnabled ? 'ON' : 'OFF' }}</h2>
        </div>
        <div class="card-overlay green"></div>
    </div>

    <div class="modern-stat-card">
        <div class="card-icon-wrapper blue">
            <i class="bi bi-hourglass-split"></i>
        </div>
        <div class="card-content">
            <p class="card-label text-shadow-bottom">Akses Pending</p>
            <h2 class="card-value text-shadow-bottom">{{ number_format((int) ($summary['pending_access_count'] ?? 0), 0, ',', '.') }}</h2>
        </div>
        <div class="card-overlay blue"></div>
    </div>

    <div class="modern-stat-card">
        <div class="card-icon-wrapper purple">
            <i class="bi bi-diagram-3"></i>
        </div>
        <div class="card-content">
            <p class="card-label text-shadow-bottom">User Akses Aktif</p>
            <h2 class="card-value text-shadow-bottom">{{ number_format((int) ($summary['allowed_user_count'] ?? 0), 0, ',', '.') }}</h2>
        </div>
        <div class="card-overlay purple"></div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white border-0 pt-3 px-3 pb-0 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0">Distribusi Akses Layanan</h6>
                <div class="dropdown">
                    <button class="btn btn-sm btn-light rounded-pill px-2 py-0" type="button" style="font-size: 0.75rem; color: #000000 !important;">
                        <i class="bi bi-filter"></i> Matrix
                    </button>
                </div>
            </div>
            <div class="card-body p-3">
                <div style="height: 220px; width: 100%;">
                    <canvas id="serviceChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-0 pt-3 px-3 pb-0">
                <h6 class="fw-bold mb-0">Aktivitas Terakhir</h6>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush py-1">
                    @forelse($recentActivities as $activity)
                        <div class="list-group-item border-0 px-3 py-1.5 d-flex align-items-center hover-bg-light transition-base">
                            <div class="avatar rounded-circle bg-{{ $activity['category_color'] ?? 'primary' }} bg-opacity-10 text-{{ $activity['category_color'] ?? 'primary' }} p-1 me-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; font-size: 1rem;">
                                <i class="bi bi-person-badge"></i>
                            </div>
                            <div class="flex-grow-1 min-width-0">
                                <h6 class="mb-0 text-truncate fw-semibold font-sans" style="font-size: 0.85rem;">{{ $activity['name'] ?? '-' }}</h6>
                                <small class="text-muted" style="font-size: 0.75rem;">{{ $activity['category'] ?? 'Menunggu Akses' }}</small>
                            </div>
                            <small class="ms-2 whitespace-nowrap text-muted" style="font-size: 0.7rem;">{{ $activity['updated_at_human'] ?? '-' }}</small>
                        </div>
                    @empty
                        <div class="text-center py-4 text-shadow-bottom">
                            <i class="bi bi-inbox fs-2 mb-1 d-block"></i>
                            Belum ada aktivitas
                        </div>
                    @endforelse
                </div>
            </div>
            <div class="card-footer bg-white border-0 px-3 pb-3 pt-0">
                <div class="row g-2">
                    <div class="col-4">
                        <div class="mini-service-stat">
                            <small>Platform</small>
                            <strong>{{ number_format((int) ($serviceMatrix['platform'] ?? 0), 0, ',', '.') }}</strong>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="mini-service-stat">
                            <small>Supply</small>
                            <strong>{{ number_format((int) ($serviceMatrix['supply'] ?? 0), 0, ',', '.') }}</strong>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="mini-service-stat">
                            <small>Calculation</small>
                            <strong>{{ number_format((int) ($serviceMatrix['calculation'] ?? 0), 0, ',', '.') }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .stats-grid-container {
        display: grid;
        grid-template-columns: repeat(6, 1fr);
        grid-column-gap: 10px;
    }

    @media (max-width: 992px) {
        .stats-grid-container {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 576px) {
        .stats-grid-container {
            grid-template-columns: 1fr;
        }
    }

    .modern-stat-card {
        background: #ffffff;
        border-radius: 20px;
        padding: 24px;
        position: relative;
        overflow: hidden;
        border: 1px solid #f1f5f9;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02), 0 2px 4px -1px rgba(0, 0, 0, 0.02);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex;
        flex-direction: column;
        justify-content: flex-start;
        min-height: 150px;
    }

    .modern-stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        border-color: transparent;
    }

    .card-icon-wrapper {
        width: 48px;
        height: 48px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        margin-bottom: 12px;
        transition: transform 0.3s ease;
    }

    .modern-stat-card:hover .card-icon-wrapper {
        transform: scale(1.1) rotate(5deg);
    }

    .red i { color: #ef4444; } .red.card-icon-wrapper { background: #fee2e2; } .red.card-overlay { background: radial-gradient(circle at top right, #fee2e2 0%, transparent 70%); }
    .cyan i { color: #06b6d4; } .cyan.card-icon-wrapper { background: #cffafe; } .cyan.card-overlay { background: radial-gradient(circle at top right, #cffafe 0%, transparent 70%); }
    .orange i { color: #f97316; } .orange.card-icon-wrapper { background: #ffedd5; } .orange.card-overlay { background: radial-gradient(circle at top right, #ffedd5 0%, transparent 70%); }
    .green i { color: #10b981; } .green.card-icon-wrapper { background: #d1fae5; } .green.card-overlay { background: radial-gradient(circle at top right, #d1fae5 0%, transparent 70%); }
    .blue i { color: #3b82f6; } .blue.card-icon-wrapper { background: #dbeafe; } .blue.card-overlay { background: radial-gradient(circle at top right, #dbeafe 0%, transparent 70%); }
    .purple i { color: #8b5cf6; } .purple.card-icon-wrapper { background: #ede9fe; } .purple.card-overlay { background: radial-gradient(circle at top right, #ede9fe 0%, transparent 70%); }

    .card-label {
        font-size: 14px;
        font-weight: 600;
        color: #000000;
        margin-bottom: 4px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .card-value {
        font-size: 32px;
        font-weight: 700;
        color: #000000;
        margin-bottom: 0;
        line-height: 1;
    }

    .card-meta {
        position: absolute;
        top: 20px;
        right: 20px;
        display: flex;
        align-items: center;
        font-size: 13px;
        font-weight: 500;
        z-index: 2;
    }

    .status-badge {
        padding: 4px 10px;
        border-radius: 99px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        color: #ffffff;
        -webkit-text-stroke: 0.2px black;
    }
    .status-badge.red { background: #fee2e2; color: #b91c1c; }
    .status-badge.green { background: #f0fdf4; color: #15803d; }

    .card-overlay {
        position: absolute;
        top: 0;
        right: 0;
        width: 150px;
        height: 150px;
        opacity: 0.4;
        border-radius: 0 0 0 100%;
        pointer-events: none;
        transition: opacity 0.3s ease;
    }

    .modern-stat-card:hover .card-overlay {
        opacity: 0.8;
    }

    .text-shadow-bottom {
        text-shadow: 0 1.1px 0 rgba(0, 0, 0, 1);
    }

    .font-sans { font-family: 'Inter', sans-serif; }
    .transition-base { transition: all 0.2s ease; }
    .hover-bg-light:hover { background-color: #f8fafc !important; }

    .welcome-card {
        background-size: cover;
        background-position: center;
    }

    .sub-welcome-card {
        color: #ffffff;
        -webkit-text-stroke: var(--special-text-stroke);
        font-weight: 700;
        text-shadow: var(--special-text-shadow);
    }

    .mini-service-stat {
        border-radius: 14px;
        border: 1px solid #e2e8f0;
        padding: 10px 12px;
        background: #f8fafc;
        display: grid;
        gap: 2px;
    }

    .mini-service-stat small {
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #64748b;
        font-size: 0.62rem;
        font-weight: 800;
    }

    .mini-service-stat strong {
        color: #172033;
        font-size: 1rem;
    }
</style>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const canvas = document.getElementById('serviceChart');

    if (!canvas) {
        return;
    }

    const ctx = canvas.getContext('2d');

    function formatDynamicPlain(value) {
        const num = Number(value);
        if (!isFinite(num)) return '';
        if (num === 0) return '0';
        return String(num);
    }

    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: @json($chart['labels'] ?? []),
            datasets: [{
                data: @json($chart['data'] ?? []),
                backgroundColor: ['#891313', '#0dcaf0', '#6c757d'],
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '75%',
            plugins: {
                legend: {
                    display: true,
                    position: 'right',
                    labels: {
                        usePointStyle: true,
                        boxWidth: 6,
                        padding: 10,
                        font: { family: "'Inter', sans-serif", size: 11 }
                    }
                },
                tooltip: {
                    backgroundColor: '#1e293b',
                    padding: 12,
                    cornerRadius: 8,
                    titleFont: { family: "'Inter', sans-serif", size: 13 },
                    bodyFont: { family: "'Inter', sans-serif", size: 13 },
                    displayColors: true,
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            let value = context.parsed || 0;
                            let total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const pct = total > 0 ? ((value / total) * 100).toFixed(1) : '0.0';
                            const percentage = formatDynamicPlain(pct).replace('.', ',') + '%';
                            const valueText = formatDynamicPlain(value).replace('.', ',');
                            return label + ': ' + valueText + ' (' + percentage + ')';
                        }
                    }
                }
            }
        }
    });
});
</script>
@endpush
@endsection
