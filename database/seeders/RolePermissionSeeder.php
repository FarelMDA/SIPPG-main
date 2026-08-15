<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * 15 role guard `web` (SRS-Fase-1 §5.1 — 5 existing + 9 hasil ekspansi model-role
 * + 1 `sysadmin`) + permission per use case, format `<domain>.<aksi>`
 * (Struktur-Proyek §10) — didaftarkan lengkap di sini, bukan ditulis ad-hoc di
 * tiap Livewire component.
 */
class RolePermissionSeeder extends Seeder
{
    private const PERMISSIONS = [
        'struktur-organisasi.manage',
        'struktur-organisasi.view',
        'kelas.manage',
        'kelas.view',
        'generus.manage',
        'generus.view',
        'pendidik.manage',
        'pendidik.view',
        'users.manage',
        'users.reset-password',
        'roles.view',
        'orangtua.reset-password',
        'import.run',
        'sensus.view',
        'kurikulum.manage',
        'kurikulum.view',
        'musyawaroh.manage',
        'dashboard.view',
        'kegiatan.manage',
        'kegiatan.view',
        'kegiatan-petugas.manage',
        'kegiatan-presensi.manage',
        'program-monitoring.manage',
        'konseling.manage',
        'konseling.view',
        'roles.manage',
        'kegiatan-jadwal.manage',
        'hari-libur.manage',
        'jenis-kegiatan.manage',
        'referensi.manage',
        'jenis-musyawaroh.manage',
        'laporan.manage',
        'laporan.review',
    ];

    /**
     * 15 role — 5 existing (Fase 1 MVP) + 9 hasil ekspansi model-role "setiap jabatan
     * punya akun sendiri" (PRD v1.9 §6, Struktur-Organisasi-dan-Role.md §Matriks Peran)
     * + 1 role `sysadmin` (level tertinggi, satu-satunya pemegang `roles.manage` —
     * bisa mengubah hak akses role lain lewat UI Matriks Role & Permission, berbeda
     * dari `admin-daerah` yang aksesnya penuh tapi tidak bisa mengubah role/permission
     * itu sendiri). `sysadmin` SENGAJA tidak dibuat lewat `Admin\KelolaPengguna` (UI
     * web) — provisioning hanya lewat `php artisan sysadmin:buat` (lihat
     * app/Console/Commands/BuatSysadmin.php) untuk mencegah eskalasi privilese dari
     * akun web manapun, termasuk admin-daerah.
     * Role Fase 2-4 (bidang-sarpras-daerah, bendahara-*, dst.) belum ditambahkan di sini —
     * menyusul saat modul pendukungnya (Sarpras, Keuangan, dst.) mulai dikerjakan.
     *
     * Method, bukan const — `admin-daerah` dihitung dari `array_diff(self::PERMISSIONS, ...)`
     * yang tidak bisa dievaluasi di dalam const expression PHP (const cuma boleh literal).
     *
     * @return array<string, array<int, string>>
     */
    private static function rolePermissions(): array
    {
        return [
            'sysadmin' => self::PERMISSIONS, // level tertinggi — satu-satunya yang punya roles.manage
            'admin-daerah' => array_values(array_diff(self::PERMISSIONS, ['roles.manage'])), // akses penuh KECUALI roles.manage
            'pjp-desa' => [
                'struktur-organisasi.view',
                'kelas.view',
                'generus.manage',
                'pendidik.manage',
                'kegiatan.manage',
                'kegiatan.view',
                'kegiatan-jadwal.manage',
                'users.manage',
                'users.reset-password',
                'laporan.manage',
                'laporan.review',
            ],
            'pjp-kelompok' => [
                'kelas.view',
                'kelas.manage',
                'generus.manage',
                'pendidik.manage',
                'sensus.view',
                'kurikulum.view',
                'musyawaroh.manage',
                'dashboard.view',
                'users.manage',
                'users.reset-password',
                'orangtua.reset-password',
                'kegiatan.manage',
                'kegiatan.view',
                'kegiatan-jadwal.manage',
                'kegiatan-petugas.manage',
                'kegiatan-presensi.manage',
                'program-monitoring.manage',
                'konseling.view',
                'laporan.manage',
            ],
            'sekretaris-kbm' => [
                'generus.manage',
                'musyawaroh.manage',
                'kegiatan.view',
                'kegiatan-petugas.manage',
                'kegiatan-presensi.manage',
                'program-monitoring.manage',
            ],
            'guru' => [
                'kurikulum.view',
                'kegiatan.view',
                'kegiatan-presensi.manage',
            ],

            // -- Role baru (Fase 1), Struktur-Organisasi-dan-Role.md §A/§B/§C --
            'wanbin-daerah' => ['musyawaroh.manage', 'struktur-organisasi.view', 'kelas.view', 'dashboard.view'],
            'sekretaris-ppg' => ['struktur-organisasi.view', 'kelas.view', 'musyawaroh.manage'],
            'bidang-kurikulum' => ['kurikulum.manage', 'kurikulum.view'],
            'bidang-tendik' => ['pendidik.manage'],
            'wanbin-desa' => ['musyawaroh.manage', 'dashboard.view'],
            'sekretaris-desa' => ['musyawaroh.manage', 'generus.view'],
            'wanbin-kelompok' => ['musyawaroh.manage', 'dashboard.view'],
            'bk-kbm' => ['konseling.manage'],
            'pakar-pendidik' => ['kurikulum.view', 'generus.view'],
        ];
    }

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        foreach (self::rolePermissions() as $roleName => $permissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($permissions);
        }
    }
}
