<?php

namespace App\Support\Auth;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PermissionRegistry
{
    public static function modules(): array
    {
        return [
            'dashboard' => [
                'label' => 'Dashboard',
                'description' => 'Akses halaman ringkasan utama sistem.',
                'permissions' => [
                    self::permission('dashboard.view', 'Lihat dashboard', 'Melihat ringkasan dan kartu utama dashboard.', ['view']),
                ],
            ],
            'materials' => [
                'label' => 'Material',
                'description' => 'Database material, histori, dan utilitas material.',
                'permissions' => [
                    self::permission('materials.view', 'Lihat material', 'Melihat daftar, detail, dan utilitas baca material.', ['view']),
                    self::permission('materials.create', 'Tambah material', 'Menambah data material baru.', ['create', 'view'], ['materials.view']),
                    self::permission('materials.update', 'Ubah material', 'Mengubah data material dan restore histori.', ['update', 'view'], ['materials.view']),
                    self::permission('materials.delete', 'Hapus material', 'Menghapus data material.', ['delete', 'view'], ['materials.view']),
                    self::permission('materials.import', 'Import material', 'Menjalankan proses import material.', ['import', 'view'], ['materials.view']),
                    self::permission('materials.export', 'Export material', 'Mengambil data material keluar sistem.', ['export', 'view'], ['materials.view']),
                    self::permission('materials.recycle-bin.view', 'Lihat recycle bin material', 'Melihat daftar material yang dihapus di recycle bin.', ['view'], ['materials.view']),
                    self::permission('materials.recycle-bin.restore', 'Restore material dari recycle bin', 'Mengembalikan material yang dihapus dari recycle bin.', ['restore', 'view'], ['materials.recycle-bin.view']),
                    self::permission('materials.recycle-bin.delete', 'Hapus permanen material', 'Menghapus material secara permanen dari recycle bin.', ['delete', 'view'], ['materials.recycle-bin.view']),
                    self::permission('materials.manage', 'Kelola penuh material', 'Akses penuh untuk semua aksi material.', ['manage', 'view', 'create', 'update', 'delete', 'import', 'export'], [
                        'materials.view',
                        'materials.create',
                        'materials.update',
                        'materials.delete',
                        'materials.import',
                        'materials.export',
                        'materials.recycle-bin.view',
                        'materials.recycle-bin.restore',
                        'materials.recycle-bin.delete',
                    ]),
                ],
            ],
            'stores' => [
                'label' => 'Toko',
                'description' => 'Master toko, lokasi toko, dan data material per lokasi.',
                'permissions' => [
                    self::permission('stores.view', 'Lihat toko', 'Melihat daftar, detail, lokasi, dan material toko.', ['view']),
                    self::permission('stores.create', 'Tambah toko', 'Menambah toko dan lokasi baru.', ['create', 'view'], ['stores.view']),
                    self::permission('stores.update', 'Ubah toko', 'Mengubah toko, lokasi, dan data terkait.', ['update', 'view'], ['stores.view']),
                    self::permission('stores.delete', 'Hapus toko', 'Menghapus toko atau lokasi terkait.', ['delete', 'view'], ['stores.view']),
                    self::permission('stores.manage', 'Kelola penuh toko', 'Akses penuh untuk semua aksi toko.', ['manage', 'view', 'create', 'update', 'delete'], ['stores.view', 'stores.create', 'stores.update', 'stores.delete']),
                ],
            ],
            'roles' => [
                'label' => 'Roles',
                'description' => 'Manajemen role dan matriks permission.',
                'permissions' => [
                    self::permission('roles.view', 'Lihat roles', 'Melihat daftar role dan permission.', ['view']),
                    self::permission('roles.create', 'Tambah role', 'Membuat role baru.', ['create', 'view'], ['roles.view']),
                    self::permission('roles.update', 'Ubah role', 'Mengubah role dan permission.', ['update', 'view'], ['roles.view']),
                    self::permission('roles.delete', 'Hapus role', 'Menghapus role non inti.', ['delete', 'view'], ['roles.view']),
                    self::permission('roles.manage', 'Kelola penuh roles', 'Akses penuh untuk semua aksi roles.', ['manage', 'view', 'create', 'update', 'delete'], ['roles.view', 'roles.create', 'roles.update', 'roles.delete']),
                ],
            ],
            'users' => [
                'label' => 'Users',
                'description' => 'Manajemen user, role assignment, dan pengaturan registrasi.',
                'permissions' => [
                    self::permission('users.view', 'Lihat user', 'Melihat daftar user dan pengaturan registrasi.', ['view']),
                    self::permission('users.create', 'Tambah user', 'Membuat user baru.', ['create', 'view'], ['users.view']),
                    self::permission('users.update', 'Ubah user', 'Mengubah profil dan status user.', ['update', 'view'], ['users.view']),
                    self::permission('users.delete', 'Hapus user', 'Menghapus user.', ['delete', 'view'], ['users.view']),
                    self::permission('users.assign-roles', 'Assign role user', 'Menetapkan atau mengubah role user.', ['assign-roles', 'view', 'update'], ['users.view', 'users.update']),
                    self::permission('users.manage', 'Kelola penuh users', 'Akses penuh untuk semua aksi users.', ['manage', 'view', 'create', 'update', 'delete', 'assign-roles'], ['users.view', 'users.create', 'users.update', 'users.delete', 'users.assign-roles']),
                ],
            ],
            'settings' => [
                'label' => 'Settings Legacy',
                'description' => 'Permission kompatibilitas lama untuk pengaturan sistem.',
                'permissions' => [
                    self::permission('settings.manage', 'Kelola settings (legacy)', 'Kompatibilitas lama untuk seluruh pengaturan.', ['legacy', 'manage', 'view', 'update', 'create', 'delete'], [
                        'roles.view',
                        'roles.create',
                        'roles.update',
                        'roles.delete',
                        'users.view',
                        'users.create',
                        'users.update',
                        'users.delete',
                        'users.assign-roles',
                    ]),
                ],
            ],
        ];
    }

    public static function definitions(): array
    {
        return self::flat()->all();
    }

    public static function displayModuleFromPermissionName(string $permissionName): string
    {
        $module = Str::before($permissionName, '.');

        if ($module === '') {
            return Str::headline($permissionName);
        }

        return self::definitions()[$permissionName]['module_label'] ?? Str::headline($module);
    }

    private static function permission(string $name, string $label, string $description, array $grants, array $implies = []): array
    {
        return [
            'name' => $name,
            'label' => $label,
            'description' => $description,
            'grants' => array_values($grants),
            'implies' => array_values($implies),
        ];
    }

    private static function flat(): Collection
    {
        return collect(self::modules())->flatMap(function (array $module, string $moduleKey) {
            return collect($module['permissions'])->mapWithKeys(function (array $permission) use ($module, $moduleKey) {
                return [
                    $permission['name'] => [
                        'module' => $moduleKey,
                        'module_label' => $module['label'],
                        'module_description' => $module['description'],
                        'name' => $permission['name'],
                        'label' => $permission['label'],
                        'description' => $permission['description'],
                        'grants' => $permission['grants'],
                        'implies' => $permission['implies'] ?? [],
                    ],
                ];
            });
        });
    }
}
