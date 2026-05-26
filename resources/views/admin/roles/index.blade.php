@extends('layouts.app')

@section('content')
    <div class="stack">
        <div class="row">
            <div>
                <div class="pill">Admin</div>
                <h1 style="margin: 10px 0 6px;">Roles & Permissions</h1>
                <div class="muted">Ringkasan role aplikasi dan permission catalog dari platform-service.</div>
            </div>
            <a class="button button-secondary" href="{{ route('workspace.index') }}">Kembali ke Workspace</a>
        </div>

        <div class="card" style="padding: 24px;">
            <div class="row">
                <div class="pill">Total Roles: {{ $roles['summary']['total_roles'] ?? 0 }}</div>
                <div class="pill">Total Permissions: {{ $permissions['total'] ?? 0 }}</div>
                <div class="pill">Assigned Users: {{ $roles['summary']['assigned_users'] ?? 0 }}</div>
            </div>
        </div>

        <div class="card" style="padding: 24px;">
            <h2 style="margin-top: 0;">Roles</h2>
            <div class="stack">
                @foreach(($roles['items'] ?? []) as $role)
                    <div style="padding: 16px 0; border-bottom: 1px solid #eef2f7;">
                        <div class="row">
                            <div>
                                <strong>{{ $role['name'] }}</strong>
                                <div class="muted">{{ $role['description'] ?? '-' }}</div>
                            </div>
                            <div class="pill">{{ $role['users_count'] ?? 0 }} users</div>
                        </div>
                        <div style="margin-top: 10px; display: flex; gap: 8px; flex-wrap: wrap;">
                            @foreach(($role['permissions'] ?? []) as $permission)
                                <span class="pill">{{ $permission }}</span>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="card" style="padding: 24px;">
            <h2 style="margin-top: 0;">Permission Groups</h2>
            <div class="stack">
                @foreach(($permissions['groups'] ?? []) as $group)
                    <div style="padding: 16px 0; border-bottom: 1px solid #eef2f7;">
                        <strong>{{ $group['label'] }}</strong>
                        <div class="muted" style="margin-top: 4px;">{{ $group['description'] }}</div>
                        <div style="margin-top: 10px; display: flex; gap: 8px; flex-wrap: wrap;">
                            @foreach(($group['permissions'] ?? []) as $permission)
                                <span class="pill">{{ $permission['name'] }}</span>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
