# Platform Service FE

`platform-service-fe` adalah shell frontend utama untuk workspace platform. Repo ini menjadi pintu masuk admin, owner profile, dashboard lintas service, manajemen user, manajemen role, dan status registrasi.

## Tanggung Jawab Utama

- menjalankan login browser ke Keycloak
- memanggil `platform-service-be` untuk identity, navigation, dashboard, profile, role, user, dan registration settings
- menjadi owner page untuk:
  - dashboard platform
  - profile
  - manajemen user
  - manajemen role
  - status registrasi
  - placeholder lokal untuk `workers` dan `skills`
- menyediakan sidebar lintas service yang terasa seperti monolith, tetapi diarahkan ke owner service yang benar

## Posisi Dalam Arsitektur

```text
browser
  -> platform-service-fe
    -> Keycloak
    -> platform-service-be
    -> supply-service-be
    -> calculation-service-be
    -> supply-service-fe / calculation-service-fe (via link workspace)
```

## Halaman yang Dimiliki

### Auth

- `GET /login`
- `GET /auth/redirect`
- `GET /auth/callback`
- `POST /logout`

Login langsung diarahkan ke Keycloak. Tidak ada lagi login form lokal sebagai source of truth auth.

### Workspace dan Profile

- `GET /workspace`
- `GET /profile`
- `PUT /profile`
- `GET /access-pending`

### Platform-Owned Admin Surfaces

- `GET /settings/users`
- `POST /settings/users`
- `PUT /settings/users/{user}`
- `DELETE /settings/users/{user}`
- `POST /settings/users/registration`
- `GET /settings/roles`
- `POST /settings/roles`
- `PUT /settings/roles/{role}`
- `DELETE /settings/roles/{role}`
- `GET /settings/registration`
- `PUT /settings/registration`

### Local Placeholder Pages

- `GET /workers`
- `GET /skills`

Halaman ini sudah disediakan lokal di platform FE agar tidak lagi bergantung ke staging monolith.

## Auth dan Cross-Service Session Model

Repo ini memakai Keycloak OIDC browser flow.

Flow ringkas:

1. user membuka page yang diproteksi
2. middleware `platform.auth` mengecek session lokal
3. bila perlu login, user diarahkan ke Keycloak
4. callback menyimpan session lokal FE
5. FE membaca identity dan permission snapshot dari `platform-service-be`
6. shared auth subject cookie dipakai agar logout/login lintas service tetap sinkron

### Standby / Reauth Behavior

Saat session perlu reauth setelah idle:

- URL terakhir disimpan
- setelah callback Keycloak, user dikembalikan ke URL asal
- user tidak lagi dilempar selalu ke default page service

## Authorization Model di FE

Sidebar dan route guard mengikuti `permission_snapshot` dari platform API.

Implikasinya:

- menu yang tidak dimiliki role user akan disembunyikan
- bila user memaksa membuka route yang tidak diizinkan, FE akan mengarahkan user kembali dengan alert `Akses Ditolak`
- `super_admin` adalah bootstrap admin penuh

## Integrasi Keluar

### Platform Backend

Owner API utama:

- `/api/v1/me`
- `/api/v1/navigation`
- `/api/v1/dashboard`
- `/api/v1/profile`
- `/api/v1/roles`
- `/api/v1/users`
- `/api/v1/settings/registration`

### Supply Backend

Dipakai untuk:

- badge store sidebar
- modal tambah material owner supply
- preferensi tab material lintas service

### Calculation Backend

Dipakai untuk:

- badge draft proyek sidebar
- redirect flow ke page start calculation

### Keycloak

Dipakai untuk:

- login browser
- logout browser
- shared active subject cookie sync

## Konfigurasi Environment Penting

Salin `.env.example` menjadi `.env`, lalu isi minimal grup berikut.

### App

- `APP_NAME`
- `APP_ENV`
- `APP_DEBUG`
- `APP_URL`
- `SESSION_DOMAIN`

### Platform Backend

- `PLATFORM_SERVICE_BASE_URL`

### Keycloak

- `KEYCLOAK_BASE_URL`
- `KEYCLOAK_REALM`
- `KEYCLOAK_CLIENT_ID`
- `KEYCLOAK_VERIFY_SSL`
- `KEYCLOAK_CA_BUNDLE`
- `KEYCLOAK_SHARED_SUBJECT_COOKIE`

### Cross-Service Links dan Owner APIs

- `SUPPLY_FE_BASE_URL`
- `SUPPLY_SERVICE_BASE_URL`
- `SUPPLY_SERVICE_VERIFY_SSL`
- `SUPPLY_SERVICE_CA_BUNDLE`
- `CALCULATION_FE_BASE_URL`
- `CALCULATION_SERVICE_BASE_URL`
- `CALCULATION_SERVICE_VERIFY_SSL`
- `CALCULATION_SERVICE_CA_BUNDLE`
- `INTERNAL_CALLER_NAME`
- `INTERNAL_SERVICE_TOKEN`

## Local Development Setup

### Prasyarat

- PHP 8.3+
- Composer
- Node.js dan npm
- `platform-service-be` aktif
- Keycloak realm `kanggo` aktif
- `supply-service-be` dan `calculation-service-be` aktif bila ingin seluruh badge dan modal lintas service bekerja penuh

### Instalasi

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

### Menjalankan Aplikasi

```bash
composer run dev
```

Atau manual:

```bash
php artisan serve --host=platformfe.lvh.me --port=8021
npm run dev
```

Gunakan host dan port yang konsisten dengan allowlist Keycloak team.

## Development Commands

```bash
php artisan test
vendor/bin/pint
npm run build
```

## Sidebar dan Navigation Notes

Repo ini menyatukan feel lintas service seperti monolith.

Prinsip yang dipakai:

- menu tetap terlihat seragam di `platform`, `supply`, dan `calculation`
- link diarahkan ke owner service yang benar
- `Platform` menjadi owner untuk:
  - profile
  - user management
  - role management
  - registration policy
  - workers
  - skills
- `Supply` menjadi owner untuk:
  - material
  - units
  - stores
  - store locations
  - store search radius
- `Calculation` menjadi owner untuk:
  - work items
  - draft/log calculation
  - work taxonomy
  - recommendation settings

## UI Behavior Khusus

### Dashboard

- tampilan mengikuti monolith 1:1 semaksimal mungkin
- data berasal dari owner service melalui platform backend

### Material Modal

- `Tambah Material` membuka owner modal supply dari mana pun di shell platform
- state tab material lintas service dipertahankan agar klik menu `Material` kembali ke last state terakhir

### Alerts dan Confirmation

- confirm memakai modal tengah ala monolith
- alert memakai toast kanan bawah ala monolith

## Testing Strategy

Test utama repo ini mencakup:

- OIDC flow
- workspace dashboard
- profile page
- user management donor page
- role management
- sidebar permission behavior
- cross-service auth sync

## Docker dan Deploy

Repo ini memiliki:

- `compose.yml`
- `compose.staging.yml`
- `compose.production.yml`
- `Dockerfile`
- `Dockerfile.production`
- `docker/entrypoint.sh`

`compose.production.yml` memakai baseline production ala monolith service split:

- image production multi-stage
- asset Vite dibuild di image
- `php-fpm`
- blue/green app service
- external Docker network `frontend` dan `backend`

## CI

Workflow `.github/workflows/ci.yml` menggunakan base monolith organization.

Job umum:

- install composer dan npm
- build frontend
- jalankan test Laravel
- validasi compose bila file compose ada

## Struktur Folder Penting

- `app/Http/Controllers/Auth` flow login/logout Keycloak
- `app/Http/Controllers` page owners platform
- `app/Services` HTTP client ke platform/supply/calculation
- `resources/views/layouts/app.blade.php` shell global, topbar, sidebar, toast, confirm
- `resources/views/profile` owner page profile
- `resources/views/settings/users` dan `resources/views/settings/roles` owner page admin

## Troubleshooting

### Sidebar menampilkan menu yang salah untuk role tertentu

Cek response identity dari platform backend:

- roles efektif
- permission snapshot
- allowed services

### Profile tidak sinkron dengan Keycloak

Cek:

- response `GET /api/v1/profile`
- subject user aktif
- callback login memakai user yang benar

### Badge `Toko` atau `Proyek` tidak muncul

Cek:

- koneksi ke owner service backend
- env `SUPPLY_SERVICE_BASE_URL` dan `CALCULATION_SERVICE_BASE_URL`
- service token internal

### Logout platform tidak menjatuhkan service lain

Cek:

- shared auth subject cookie
- session domain
- middleware auth sync di FE lain

## Related Repositories

- `platform-service-be`
- `supply-service-fe`
- `supply-service-be`
- `calculation-service-fe`
- `calculation-service-be`