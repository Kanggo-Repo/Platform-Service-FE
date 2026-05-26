@extends('layouts.auth')

@section('title', 'Login Admin')
@section('auth_brand_title', 'Portal Login Database Material dan Perhitungan Proyek.')
@section('auth_brand_copy', 'Akses aplikasi dibatasi ke user yang sudah diberikan role oleh administrator. Login dipusatkan di Keycloak agar supply, calculation, dan platform memakai SSO yang sama.')
@section('auth_card_title', 'Masuk')
@section('auth_card_copy', 'Gunakan akun yang sudah didaftarkan di Keycloak untuk membuka workspace platform.')

@section('auth_form')
    <a href="{{ route('auth.redirect') }}" class="btn btn-auth text-white w-100">Masuk dengan Keycloak</a>
@endsection

@section('auth_footer')
    <p class="auth-footer mb-0">
        Login terhubung ke Identity Provider pusat. Jika akses modul belum muncul setelah login, akun Anda akan diarahkan ke halaman menunggu persetujuan.
    </p>
@endsection
