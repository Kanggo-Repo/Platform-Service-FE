@extends('layouts.app')

@section('content')
    <div class="stack">
        <div class="row">
            <div>
                <div class="pill">Admin</div>
                <h1 style="margin: 10px 0 6px;">Registration Policy</h1>
                <div class="muted">Kontrol toggle register dan policy user baru.</div>
            </div>
            <a class="button button-secondary" href="{{ route('workspace.index') }}">Kembali ke Workspace</a>
        </div>

        <div class="card" style="padding: 24px;">
            <form method="POST" action="{{ route('admin.registration.update') }}" class="stack">
                @csrf
                @method('PUT')

                <label style="display: grid; gap: 8px;">
                    <span style="font-weight: 600;">Registration Enabled</span>
                    <select name="registration_enabled" style="padding: 12px; border-radius: 12px; border: 1px solid #d7deea;">
                        <option value="1" @selected(($policy['registration_enabled'] ?? false) === true)>true</option>
                        <option value="0" @selected(($policy['registration_enabled'] ?? false) === false)>false</option>
                    </select>
                </label>

                <label style="display: grid; gap: 8px;">
                    <span style="font-weight: 600;">Approval Mode</span>
                    <input type="text" name="approval_mode" value="{{ $policy['approval_mode'] ?? '' }}" style="padding: 12px; border-radius: 12px; border: 1px solid #d7deea;">
                </label>

                <label style="display: grid; gap: 8px;">
                    <span style="font-weight: 600;">Default New User Status</span>
                    <input type="text" name="default_new_user_status" value="{{ $policy['default_new_user_status'] ?? '' }}" style="padding: 12px; border-radius: 12px; border: 1px solid #d7deea;">
                </label>

                <label style="display: grid; gap: 8px;">
                    <span style="font-weight: 600;">Notes</span>
                    <textarea name="notes" rows="4" style="padding: 12px; border-radius: 12px; border: 1px solid #d7deea;">{{ $policy['notes'] ?? '' }}</textarea>
                </label>

                <div>
                    <button type="submit" class="button">Simpan Policy</button>
                </div>
            </form>
        </div>
    </div>
@endsection
