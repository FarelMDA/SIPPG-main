# Struktur Proyek — SI-PPG Fase 1

**Nama Sistem:** SI-PPG — Sistem Informasi Pembinaan Generus
**Versi Dokumen:** 1.2
**Tanggal:** 30 Juli 2026
**Status:** Draft
**Klasifikasi:** Internal — Terbatas
**Dokumen Sumber:** [SRS-Fase-1.md](SRS-Fase-1.md), [UCIC-Fase-1.md](UCIC-Fase-1.md)
**Dokumen Terkait:** [PRD-Aplikasi-Pendataan-Pelaporan-PPG.md](PRD-Aplikasi-Pendataan-Pelaporan-PPG.md#18-rekomendasi-arsitektur--stack-teknologi), [UIUX-Reference-Fase-1.md](UIUX-Reference-Fase-1.md)

> **Catatan Ruang Lingkup:** Dokumen ini adalah skeleton struktur folder Laravel 13 untuk **Fase 1**, diturunkan langsung dari kontrak Livewire/endpoint di SRS §16 dan UCIC (nama Component per use case) serta skema tabel di SRS §17. Ini **bukan** kode — belum ada implementasi apa pun di repo ini — melainkan peta "apa taruh di mana" sebelum development dimulai, supaya tim tidak perlu menebak konvensi penamaan saat mulai coding.

---

## Daftar Isi

1. [Prasyarat & Asumsi](#1-prasyarat--asumsi)
2. [Struktur Folder Root](#2-struktur-folder-root)
3. [`app/` — Kode Aplikasi](#3-app--kode-aplikasi)
4. [`database/` — Migrasi, Seeder, Factory](#4-database--migrasi-seeder-factory)
5. [`resources/` — View, Asset, PWA](#5-resources--view-asset-pwa)
6. [`routes/`](#6-routes)
7. [`tests/`](#7-tests)
8. [Konfigurasi Tambahan](#8-konfigurasi-tambahan)
9. [Environment & Deployment (Shared Hosting)](#9-environment--deployment-shared-hosting)
10. [Konvensi Penamaan](#10-konvensi-penamaan)
11. [Catatan Konsistensi Dokumen](#11-catatan-konsistensi-dokumen)

---

## 1. Prasyarat & Asumsi

Sesuai PRD §18 (stack sudah diputuskan, bukan opsi terbuka):

| Alat | Versi/Pilihan | Catatan |
|---|---|---|
| PHP | 8.3+ | Minimum yang disyaratkan Laravel 13 (naik dari 8.2 di Laravel 11/12) — **wajib dikonfirmasi tersedia** di paket shared hosting cPanel target sebelum setup proyek dimulai (PRD §18, SRS §1.1) |
| Laravel | 13.x | Rilis 17 Maret 2026. Skeleton default (sejak Laravel 11, **tidak berubah** di 12/13) tidak lagi punya `app/Http/Kernel.php`, `app/Console/Kernel.php`, `app/Exceptions/Handler.php` terpisah — semua didaftarkan di `bootstrap/app.php` (lihat §3) |
| Livewire | 3.x | Namespace komponen default `App\Livewire\...` (bukan `App\Http\Livewire` seperti Livewire 2) |
| Node.js/npm | Hanya untuk **build lokal/CI** | Shared hosting **tidak** menjalankan Node runtime (PRD §18) — `npm run build` dijalankan di mesin developer/CI, hasil `public/build/*` yang di-upload, bukan source Node |
| Composer | 2.x | |
| Database | MySQL 8 / MariaDB | |

---

## 2. Struktur Folder Root

```
si-ppg/
├── app/
│   ├── Console/
│   │   └── Commands/
│   ├── Events/
│   ├── Http/
│   │   └── Middleware/
│   ├── Jobs/
│   ├── Listeners/
│   ├── Livewire/
│   ├── Models/
│   │   └── Concerns/
│   ├── Notifications/
│   ├── Policies/
│   └── Providers/
│       └── AppServiceProvider.php
├── bootstrap/
│   ├── app.php              ← middleware, exceptions, scheduler didaftarkan di sini (pola sejak Laravel 11, dipakai apa adanya di Laravel 13)
│   └── providers.php
├── config/
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
├── public/
│   ├── build/                ← hasil `npm run build`, di-upload manual/CI ke shared hosting
│   ├── manifest.json         ← PWA
│   ├── service-worker.js     ← PWA, cache & background sync
│   └── index.php
├── resources/
│   ├── css/
│   ├── js/
│   └── views/
│       ├── layouts/
│       └── livewire/
├── routes/
│   ├── web.php
│   ├── api.php
│   └── console.php
├── storage/
├── tests/
│   ├── Feature/
│   └── Unit/
├── .env.example
├── artisan
└── composer.json
```

---

## 3. `app/` — Kode Aplikasi

### 3.1 `app/Livewire/` — satu subfolder per modul, sesuai namespace Component di UCIC

Nama class & path di bawah ini **mengikuti persis** nama `Component:` yang sudah dikontrakkan di UCIC — jangan improvisasi nama baru saat implementasi, supaya kontrak tetap valid tanpa perlu update dokumen.

| Namespace UCIC | File | Acuan UC |
|---|---|---|
| `Auth\LoginForm` | `app/Livewire/Auth/LoginForm.php` | UC-01 |
| `Auth\LoginOrangTuaForm` | `app/Livewire/Auth/LoginOrangTuaForm.php` | UC-01 |
| `Auth\GantiPasswordForm` | `app/Livewire/Auth/GantiPasswordForm.php` | UC-02 |
| `Admin\KelolaPengguna` | `app/Livewire/Admin/KelolaPengguna.php` | UC-03, UC-07 |
| `Admin\KelolaAkunOrangTua` | `app/Livewire/Admin/KelolaAkunOrangTua.php` | UC-03 |
| `MasterData\KelolaDesa` | `app/Livewire/MasterData/KelolaDesa.php` | UC-04 |
| `MasterData\KelolaKelompok` | `app/Livewire/MasterData/KelolaKelompok.php` | UC-04 |
| `MasterData\KelolaKelas` | `app/Livewire/MasterData/KelolaKelas.php` | UC-04 |
| `MasterData\KelolaGenerus` | `app/Livewire/MasterData/KelolaGenerus.php` | UC-05 |
| `MasterData\KelolaPendidik` | `app/Livewire/MasterData/KelolaPendidik.php` | UC-06 |
| `MasterData\ImporMassal` | `app/Livewire/MasterData/ImporMassal.php` | UC-08 |
| `Sensus\SensusDashboard` | `app/Livewire/Sensus/SensusDashboard.php` | UC-09 |
| `Kurikulum\ImporKalender` | `app/Livewire/Kurikulum/ImporKalender.php` | UC-10 |
| `Presensi\KonfirmasiRealisasiMateri` | `app/Livewire/Presensi/KonfirmasiRealisasiMateri.php` | UC-11 (embedded di `InputJurnalHarian`) |
| `Presensi\InputPresensi` | `app/Livewire/Presensi/InputPresensi.php` | UC-12 |
| `Presensi\InputJurnalHarian` | `app/Livewire/Presensi/InputJurnalHarian.php` | UC-13 |
| `Musyawaroh\KelolaMusyawaroh` | `app/Livewire/Musyawaroh/KelolaMusyawaroh.php` | UC-15 |
| `Musyawaroh\KelolaNotulenItem` | `app/Livewire/Musyawaroh/KelolaNotulenItem.php` | UC-15 (embedded — item repeatable) |
| `Kegiatan\KelolaKegiatan` | `app/Livewire/Kegiatan/KelolaKegiatan.php` | UC-21 |
| `Kegiatan\KelolaPetugasPresensi` | `app/Livewire/Kegiatan/KelolaPetugasPresensi.php` | UC-22 |
| `Kegiatan\InputPresensiKegiatan` | `app/Livewire/Kegiatan/InputPresensiKegiatan.php` | UC-23 — dipakai baik sesi `web` maupun rute token (§6.2) |
| `Kegiatan\KelolaProgramMonitoring` | `app/Livewire/Kegiatan/KelolaProgramMonitoring.php` | UC-24 |
| `Kegiatan\RekapKegiatan` | `app/Livewire/Kegiatan/RekapKegiatan.php` | UC-25 |
| `PortalOrangTua\Dashboard` | `app/Livewire/PortalOrangTua/Dashboard.php` | UC-17 |
| `PortalOrangTua\LihatPresensi` | `app/Livewire/PortalOrangTua/LihatPresensi.php` | UC-17 |
| `PortalOrangTua\LihatJurnal` | `app/Livewire/PortalOrangTua/LihatJurnal.php` | UC-17 |
| `PortalOrangTua\NotifikasiFeed` | `app/Livewire/PortalOrangTua/NotifikasiFeed.php` | UC-18 |
| `Dashboard\DashboardKelompok` | `app/Livewire/Dashboard/DashboardKelompok.php` | UC-19 |

Halaman rekap cetak (UC-09 Sensus, UC-12 rekap Presensi, UC-15 notulen, UC-25 Kegiatan) **tidak** punya Livewire component terpisah untuk aksi cetak — tombol "Cetak" murni `window.print()` sisi klien (UC-20, §5).

### 3.2 `app/Models/`

Satu model per tabel di SRS §17, plus pivot yang butuh model eksplisit (pivot dengan kolom tambahan/logika, bukan sekadar `belongsToMany`):

```
app/Models/
├── Daerah.php
├── Desa.php
├── Kelompok.php
├── Kelas.php
├── Generus.php
├── GenerusStatusHistory.php
├── GenerusKelasHistory.php
├── Pendidik.php
├── SensusSnapshot.php
├── KurikulumKalender.php
├── Presensi.php
├── JurnalHarian.php
├── Musyawaroh.php
├── MusyawarohItem.php
├── User.php                      ← guard `web`, pakai trait spatie HasRoles
├── AkunOrangTua.php               ← guard `orangtua`, model auth terpisah dari User
├── NotifikasiOrangTua.php
├── Kegiatan.php                   ← relasi penyelenggara polimorfik, lihat Concerns/
├── KegiatanPeserta.php
├── KegiatanPetugasPresensi.php
├── ProgramMonitoring.php
├── ProgramMonitoringItem.php
└── Concerns/
    ├── BelongsToKelompok.php      ← trait + Eloquent Global Scope (SRS §4.2)
    └── HasPolymorphicPenyelenggara.php  ← helper accessor Kegiatan->penyelenggara (Kelompok/Desa/Daerah, SRS §18.1)
```

**Catatan Global Scope (SRS §4.2):** scoping otomatis `kelompok_id` milik user login **bukan** dicek manual di tiap Livewire component, melainkan `BelongsToKelompok` trait yang mendaftarkan Eloquent Global Scope di `booted()`. Model yang perlu scope ini: `Generus`, `Pendidik`, `Presensi`, `JurnalHarian`, `Musyawaroh`. Admin Daerah di-bypass scope ini (lihat implementasi trait — cek role sebelum apply constraint).

### 3.3 `app/Events/` & `app/Listeners/`

Mengikuti pola event-driven yang sudah dikontrakkan di UCIC (bukan dipanggil langsung dari controller):

```
app/Events/
├── GenerusDisimpan.php            ← dipicu UC-05/UC-08, ditangkap UC-16
└── PresensiAlphaDicatat.php       ← dipicu UC-12/UC-14, ditangkap UC-18

app/Listeners/
├── ProvisioningAkunOrangTua.php   ← UC-16, handle(GenerusDisimpan)
└── KirimNotifikasiAlpha.php       ← UC-18, handle(PresensiAlphaDicatat), implements ShouldQueue
```

### 3.4 `app/Jobs/`

```
app/Jobs/
├── HitungSensusSnapshotBulanan.php   ← UC-09, dijadwalkan tanggal 1 tiap bulan (SRS §7)
└── SinkronisasiOfflineDiproses.php   ← opsional, hanya jika UPSERT batch UC-14 perlu diantre (bukan synchronous)
```

### 3.5 `app/Http/Middleware/`

```
app/Http/Middleware/
├── EnsurePasswordChanged.php      ← SRS §3, blokir semua rute selain logout selama must_change_password=true
└── ValidasiTokenPetugasPresensi.php  ← SRS §18.2, jalur akses ketiga (bukan guard) untuk UC-23 via token
```

Didaftarkan di `bootstrap/app.php` (bukan `Kernel.php` — pola sejak Laravel 11, tetap dipakai di Laravel 13):
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [\App\Http\Middleware\EnsurePasswordChanged::class]);
    $middleware->alias([
        'token.kegiatan' => \App\Http\Middleware\ValidasiTokenPetugasPresensi::class,
    ]);
})
```

### 3.6 `app/Console/Commands/`

```
app/Console/Commands/
└── (kosong di Fase 1 — scheduler cukup memanggil Job/Artisan bawaan langsung dari bootstrap/app.php, lihat §8)
```

---

## 4. `database/` — Migrasi, Seeder, Factory

### 4.1 Urutan Migrasi (mengikuti dependency FK, dikelompokkan sesuai SRS §17.x)

```
database/migrations/
├── 0001_create_daerah_table.php                         (§17.1)
├── 0002_create_desa_table.php                            (§17.1)
├── 0003_create_kelompok_table.php                        (§17.1)
├── 0004_create_kelas_table.php                           (§17.1)
├── 0005_create_generus_table.php                         (§17.2)
├── 0006_create_generus_status_histories_table.php        (§17.2)
├── 0007_create_generus_kelas_histories_table.php         (§17.2)
├── 0008_create_pendidik_table.php                        (§17.2)
├── 0009_create_pendidik_kelas_table.php                  (§17.2)
├── 0010_create_sensus_snapshots_table.php                (§17.2)
├── 0011_create_kurikulum_kalender_table.php              (§17.3)
├── 0012_create_users_table.php                           (§17.6 — kolom kelompok_id/desa_id scope, §4.2)
├── 0013_create_permission_tables.php                     (paket spatie/laravel-permission, di-publish bukan ditulis manual)
├── 0014_create_presensi_table.php                        (§17.4)
├── 0015_create_jurnal_harian_table.php                   (§17.4)
├── 0016_create_musyawaroh_table.php                      (§17.5)
├── 0017_create_musyawaroh_item_table.php                 (§17.5)
├── 0018_create_akun_orang_tua_table.php                  (§17.6)
├── 0019_create_akun_orang_tua_generus_table.php          (§17.6)
├── 0020_create_notifikasi_orang_tua_table.php            (§17.6)
├── 0021_create_activity_log_table.php                    (paket spatie/laravel-activitylog, di-publish)
├── 0022_create_kegiatan_table.php                        (§17.8)
├── 0023_create_kegiatan_peserta_table.php                (§17.8)
├── 0024_create_kegiatan_petugas_presensi_table.php       (§17.8)
├── 0025_create_program_monitoring_table.php              (§17.8)
└── 0026_create_program_monitoring_item_table.php         (§17.8)
```

*(Prefix angka di atas hanya ilustrasi urutan logis — nama file migrasi sungguhan akan pakai timestamp `YYYY_MM_DD_HHMMSS` standar Laravel sesuai kapan file dibuat, bukan angka urut manual.)*

### 4.2 Seeder

```
database/seeders/
├── DatabaseSeeder.php
├── RolePermissionSeeder.php   ← 5 role §5.1 SRS (admin-daerah, pjp-desa, pjp-kelompok, sekretaris-kbm, guru)
│                                  + permission per UC (generus.manage, presensi.manage, kegiatan.manage, dst. — lihat §11 di bawah)
└── DaerahSeeder.php            ← satu baris konfigurasi Daerah (PRD §18.4, tidak multi-tenant)
```

### 4.3 Factory

Satu factory per model utama untuk kebutuhan testing (§7) — `database/factories/GenerusFactory.php`, `KelompokFactory.php`, `KegiatanFactory.php`, dst., mengikuti nama model di §3.2.

---

## 5. `resources/` — View, Asset, PWA

### 5.1 `resources/views/`

```
resources/views/
├── layouts/
│   ├── app.blade.php              ← shell Aplikasi Internal (sidebar + topbar, UIUX §7.1)
│   ├── portal.blade.php           ← shell Portal Orang Tua (bottom nav mobile-first, UIUX §7.1)
│   └── kegiatan-token.blade.php   ← shell minimal TANPA navigasi, khusus P-KGT-TOKEN-01 (UIUX §3.3.3)
└── livewire/                       ← Livewire 3 auto-resolve kebab-case, mengikuti path app/Livewire/
    ├── auth/
    ├── admin/
    ├── master-data/
    ├── sensus/
    ├── kurikulum/
    ├── presensi/
    ├── musyawaroh/
    ├── kegiatan/
    ├── portal-orang-tua/
    └── dashboard/
```

**Tiga app-shell terpisah** (bukan dua) — ini titik penting yang berbeda dari UIUX §1 yang awalnya hanya menyebut "dua sisi antarmuka" (internal & Portal Orang Tua): sejak Modul Kegiatan masuk Fase 1, `kegiatan-token.blade.php` jadi shell ketiga khusus UC-23 jalur token (SRS §18.2, UIUX §3.3.3) — tanpa sidebar/topbar apa pun, lihat §11.

### 5.2 `resources/js/`

```
resources/js/
├── app.js                  ← entry Alpine.js + registrasi service worker
└── offline/
    └── db.js               ← skema Dexie.js (IndexedDB): generus, kalender_materi, draft_presensi, draft_jurnal (SRS §13.1)
```

### 5.3 `resources/css/`

```
resources/css/
└── app.css                 ← entry Tailwind CSS, termasuk kelas @media print (SRS §15, UIUX §7.5)
```

### 5.4 PWA (`public/`)

```
public/
├── manifest.json            ← nama app, ikon, splash screen (UIUX §7.7)
└── service-worker.js        ← cache shell + background sync ke /api/v1/sync/* (SRS §13.2)
```

---

## 6. `routes/`

```php
// routes/web.php
Route::middleware('guest:web')->group(function () {
    Route::get('/login', ...);   // P-AUTH-01, UC-01
});
Route::middleware('auth:web')->group(function () {
    Route::get('/dashboard', ...);           // UC-19
    Route::get('/master-data/...', ...);     // UC-04..08
    Route::get('/sensus', ...);              // UC-09
    Route::get('/kurikulum', ...);           // UC-10
    Route::get('/presensi/{kelasId}/{tanggal}', ...); // UC-12
    Route::get('/jurnal/{kelasId}/{tanggal}', ...);   // UC-13
    Route::get('/musyawaroh/...', ...);      // UC-15
    Route::get('/kegiatan', ...);            // UC-21
    Route::get('/kegiatan/{id}/petugas-presensi', ...); // UC-22
    Route::get('/kegiatan/{id}/presensi/{kelompokId}', ...); // UC-23 (jalur sesi web)
    Route::get('/kegiatan/program-monitoring', ...);    // UC-24
    Route::get('/kegiatan/{id}/rekap', ...); // UC-25
    Route::get('/pengaturan/pengguna', ...); // UC-03, UC-07
});

Route::middleware('guest:orangtua')->group(function () {
    Route::get('/portal/login', ...);        // P-AUTH-02, UC-01
});
Route::middleware('auth:orangtua')->group(function () {
    Route::get('/portal/dashboard', ...);    // UC-17
    Route::get('/portal/presensi', ...);
    Route::get('/portal/jurnal', ...);
    Route::get('/portal/notifikasi', ...);   // UC-18
});

// Jalur ketiga — TANPA guard, lihat SRS §2.1 & §18.2
Route::middleware('token.kegiatan')->get('/kegiatan/presensi/{token}', ...); // UC-23 jalur token
```

```php
// routes/api.php — HANYA untuk sinkronisasi offline (SRS §2.1, §13, UC-14)
Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    Route::get('/sync/bootstrap', ...);
    Route::post('/sync/presensi', ...);
    Route::post('/sync/jurnal-harian', ...);
});
```

`routes/api.php` **tidak ada secara default** di skeleton Laravel (sejak Laravel 11, tetap berlaku di Laravel 13) — harus dibuat manual via `php artisan install:api` (yang sekaligus memasang Sanctum) sebelum menulis rute di atas.

---

## 7. `tests/`

Satu Feature test per use case (UC-01 s.d. UC-25), dikelompokkan folder yang sama dengan `app/Livewire/`:

```
tests/Feature/
├── Auth/                LoginTest.php, GantiPasswordTest.php, ResetPasswordTest.php
├── MasterData/           KelolaGenerusTest.php, ...
├── Presensi/             InputPresensiTest.php, InputJurnalHarianTest.php
├── Offline/              SinkronisasiOfflineTest.php   ← UC-14, prioritas tinggi (SRS §11.3: "bagian paling rawan bug")
├── Kegiatan/             KelolaKegiatanTest.php, KelolaPetugasPresensiTest.php,
│                         InputPresensiKegiatanTest.php (termasuk skenario token kedaluwarsa),
│                         KelolaProgramMonitoringTest.php
└── PortalOrangTua/       ProvisioningAkunTest.php, LihatDataAnakTest.php, NotifikasiAlphaTest.php

tests/Unit/
└── Models/               BelongsToKelompokScopeTest.php   ← pastikan Global Scope §4.2 tidak bocor lintas Kelompok
```

---

## 8. Konfigurasi Tambahan

| File | Tujuan |
|---|---|
| `config/permission.php` | Publish dari `spatie/laravel-permission`, tidak diedit strukturnya kecuali `teams` (tidak dipakai, Fase 1 single-tenant) |
| `config/activitylog.php` | Publish dari `spatie/laravel-activitylog`, `default_log_name` diseragamkan per modul (SRS §11.5) |
| `config/sanctum.php` | Token untuk `/api/v1/sync/*` saja (SRS §2.1) |
| `config/queue.php` | Driver `database` (bukan Redis) — sesuai batas shared hosting, PRD §18.3 |
| `bootstrap/app.php` | Scheduler didaftarkan via `->withSchedule()`, bukan `app/Console/Kernel.php` (pola sejak Laravel 11, tetap di Laravel 13) — isi: `HitungSensusSnapshotBulanan` (tanggal 1/bulan) + `queue:work --stop-when-empty` (tiap menit, dipicu cron cPanel, PRD §18.3) |

---

## 9. Environment & Deployment (Shared Hosting)

Sesuai PRD §18.1–§18.6 — **tidak** ada Docker/Node runtime/proses long-running di server produksi:

0. **Sebelum langkah lain mana pun** — konfirmasi versi PHP aktif di paket cPanel target via EasyApache/MultiPHP Manager sudah **8.3 atau lebih baru** (PRD §18, SRS §1.1). Bila belum, pindah ke PHP 8.3 di panel hosting (biasanya tidak perlu upgrade paket hosting, cukup ganti versi PHP terpasang) sebelum `composer create-project` dijalankan.
1. Document root cPanel diarahkan ke `public/` (bukan root repo).
2. Build asset (`npm run build`) dijalankan **lokal atau CI**, hasil `public/build/` di-upload sebagai bagian deployment — server produksi tidak pernah menjalankan `npm`.
3. Satu cron job cPanel (1x/menit) → `php artisan schedule:run` — dari situ scheduler Laravel yang memicu snapshot sensus & `queue:work --stop-when-empty` (§8).
4. `storage:link` dijalankan sekali saat setup awal (untuk upload — meski Fase 1 minim upload file, lihat SRS §1.1 soal Foto Kegiatan yang masih Fase 2).
5. Backup database harian **disalin keluar server** (PRD §18.5) — bukan cukup fitur backup bawaan cPanel yang tersimpan di server yang sama.
6. HTTPS (Let's Encrypt) aktif sejak hari pertama, non-negotiable (PRD §18.5).

---

## 10. Konvensi Penamaan

- **Nama Livewire Component = nama `Component:` di UCIC, apa adanya** — jangan disingkat/diubah saat coding. Bila UC baru ditambah di masa depan (Fase 2+), tambahkan barisnya ke tabel §3.1 di dokumen ini sebagai bagian dari definition-of-done use case tsb, sebelum PR dibuka.
- **Nama tabel/kolom = persis seperti SQL di SRS §17** — migrasi tidak boleh menyimpang (mis. tidak boleh `nama_kegiatan` bila SRS menulis `nama`).
- **Permission `spatie/laravel-permission`** memakai format `<domain>.<aksi>` kebab/snake sesuai yang sudah disebut di UCIC per UC, mis. `generus.manage`, `presensi.manage`, `kegiatan.manage`, `kegiatan-petugas.manage`, `kegiatan-presensi.manage`, `program-monitoring.manage`, `kegiatan.view` — didaftarkan lengkap di `RolePermissionSeeder` (§4.2), bukan ditulis ad-hoc di tiap component.
- **Route name** mengikuti pola `<modul>.<aksi>`, mis. `kegiatan.index`, `kegiatan.petugas-presensi`, `kegiatan.presensi.token` — belum dikontrakkan eksplisit di SRS/UCIC (keputusan teknis bebas saat implementasi), tapi harus konsisten satu pola untuk seluruh modul.

---

## 11. Catatan Konsistensi Dokumen

Saat menyusun dokumen ini (v1.0), sempat ditemukan **penamaan Livewire yang tidak seragam** antara SRS §16 (daftar endpoint, sebelumnya ditulis singkat/naratif tanpa namespace) dan UCIC (kontrak resmi per use case, ditandai `Component:`) — mencakup modul Master Data, Sensus, Kurikulum, Presensi, Musyawaroh, Portal Orang Tua, dan Dashboard. **Sudah diselaraskan** ke SRS-Fase-1.md v1.4: seluruh entri di §16 kini memakai namespace lengkap yang sama persis dengan `Component:` di UCIC (mis. `PortalOrangTua\Dashboard`, bukan `PortalDashboard`). Dua penyimpangan tambahan yang ikut terkoreksi di §16 SRS pada saat yang sama:

- `MasterData\KelolaDesa` sebelumnya **hilang** dari daftar §16 padahal ada di kontrak UCIC UC-04 — sudah ditambahkan.
- `ImporKalenderKurikulum` (§16) vs `Kurikulum\ImporKalender` (UCIC UC-10) — nama berbeda, bukan cuma kurang namespace; diselaraskan ke nama UCIC.
- `ApprovalJurnalHarian` sempat tertulis di §16 seakan-akan component terpisah, padahal UCIC UC-13 menempatkan approval sebagai aksi `setujui()` di dalam `Presensi\InputJurnalHarian` yang sama — baris tsb dihapus, diganti catatan aksi pada baris `InputJurnalHarian`.

Tabel §3.1 di dokumen ini tetap menjadi acuan tunggal (mengikuti UCIC), sekarang juga sudah identik dengan SRS §16.

---

**Riwayat Revisi:**

| Versi | Tanggal | Perubahan |
|-------|---------|-----------|
| 1.0 | 30 Juli 2026 | Dokumen awal — struktur folder Laravel 11 lengkap (app/, database/, resources/, routes/, tests/), mapping tiap Livewire component & tabel ke acuan SRS/UCIC, tiga app-shell (internal/Portal Ortu/token Kegiatan), konvensi penamaan, dan catatan inkonsistensi penamaan SRS §16 vs UCIC yang ditemukan saat penyusunan |
| 1.1 | 30 Juli 2026 | Update §11 — inkonsistensi penamaan Livewire SRS §16 vs UCIC sudah diperbaiki di SRS-Fase-1.md v1.4 (bukan lagi item terbuka); catat 2 penyimpangan tambahan yang ikut ditemukan & dikoreksi (`MasterData\KelolaDesa` sempat hilang, `ApprovalJurnalHarian` sempat ditulis seolah component terpisah) |
| 1.2 | 30 Juli 2026 | Update §1 & seluruh referensi versi: Laravel 11→**Laravel 13**, PHP minimum 8.2→**8.3** (Laravel 13 rilis 17 Maret 2026, terverifikasi dari laravel.com/docs/13.x/releases — pola `bootstrap/app.php` tanpa `Kernel.php` sejak Laravel 11 tidak berubah, jadi struktur folder §2–§8 lain tidak perlu diubah); tambah langkah 0 di §9 untuk konfirmasi versi PHP aktif di cPanel sebelum setup proyek |
