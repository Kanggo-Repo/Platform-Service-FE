@extends('layouts.app')

@section('title', 'Registration Policy')
@section('topbar-title', 'Pengaturan')
@section('topbar-title-html')
    Pengaturan <i class="bi bi-caret-right-fill"></i> Registrasi
@endsection

@section('content')
    <div class="card mb-3" style="border: 1px solid #e2e8f0; border-radius: 12px;">
        <div class="card-body" style="padding: 16px;">
            <form action="{{ route('settings.registration.update') }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="registration_enabled" style="font-weight: 600;">
                            Status Registrasi
                        </label>
                        <select
                            class="form-control"
                            id="registration_enabled"
                            name="registration_enabled"
                            required
                        >
                            <option value="1" @selected(($policy['registration_enabled'] ?? false) === true)>Aktif</option>
                            <option value="0" @selected(($policy['registration_enabled'] ?? false) === false)>Nonaktif</option>
                        </select>
                        <small class="text-muted">
                            Menentukan apakah user baru diizinkan mendaftarkan akun melalui alur registrasi platform.
                        </small>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="approval_mode" style="font-weight: 600;">
                            Mode Persetujuan
                        </label>
                        <select
                            class="form-control"
                            id="approval_mode"
                            name="approval_mode"
                            required
                        >
                            <option value="admin_approval" @selected(($policy['approval_mode'] ?? '') === 'admin_approval')>Admin Approval</option>
                            <option value="auto_approve" @selected(($policy['approval_mode'] ?? '') === 'auto_approve')>Auto Approve</option>
                        </select>
                        <small class="text-muted">
                            Tentukan apakah user baru harus menunggu persetujuan admin atau langsung aktif setelah dibuat.
                        </small>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="default_new_user_status" style="font-weight: 600;">
                            Status Default User Baru
                        </label>
                        <select
                            class="form-control"
                            id="default_new_user_status"
                            name="default_new_user_status"
                            required
                        >
                            <option value="pending_access" @selected(($policy['default_new_user_status'] ?? '') === 'pending_access')>Pending Access</option>
                            <option value="active" @selected(($policy['default_new_user_status'] ?? '') === 'active')>Active</option>
                            <option value="suspended" @selected(($policy['default_new_user_status'] ?? '') === 'suspended')>Suspended</option>
                            <option value="archived" @selected(($policy['default_new_user_status'] ?? '') === 'archived')>Archived</option>
                        </select>
                        <small class="text-muted">
                            Status dasar yang akan disimpan ke profile internal platform saat user pertama kali diproyeksikan.
                        </small>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="notes" style="font-weight: 600;">
                            Catatan Policy
                        </label>
                        <textarea
                            class="form-control"
                            id="notes"
                            name="notes"
                            rows="5"
                            placeholder="Catatan internal untuk menjelaskan policy registrasi saat ini..."
                        >{{ $policy['notes'] ?? '' }}</textarea>
                        <small class="text-muted">
                            Catatan ini berguna untuk mengingat alasan policy aktif, misalnya pilot rollout atau freeze registrasi.
                        </small>
                    </div>
                </div>

                <div class="mt-3 p-3" style="background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 10px;">
                    <div style="font-weight: 700; color: #334155; margin-bottom: 6px;">Ringkasan Perilaku</div>
                    <div class="text-muted" style="font-size: 13px; line-height: 1.55;">
                        Jika registrasi aktif, akun baru tetap bisa diarahkan ke halaman <b>pending access</b> sampai mendapatkan role atau izin layanan.
                        Jika registrasi nonaktif, hanya admin platform yang dapat menambahkan user baru melalui halaman <b>Pengguna</b>.
                    </div>
                </div>

                <div class="d-flex justify-content-end mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i> Simpan Policy Registrasi
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
