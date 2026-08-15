# SI-PPG — Sistem Informasi Pembinaan Generus

Aplikasi pendataan & pelaporan rutin PPG (Penggerak Pembina Generus) — Fase 1.
Dibangun dengan **Laravel 13 + Livewire 3 + Tailwind CSS** (TALL stack).

Dokumen kebutuhan lengkap ada di [`docs/`](docs/): `PRD-Aplikasi-Pendataan-Pelaporan-PPG.md`, `SRS-Fase-1.md`, `UCIC-Fase-1.md`, `UIUX-Reference-Fase-1.md`, `Struktur-Proyek-Fase-1.md`.

---

## 1. Instalasi di Local

### 1.1 Prasyarat

| Kebutuhan | Versi | Cek dengan |
|---|---|---|
| PHP | 8.3+ | `php -v` |
| Ekstensi PHP | `mbstring`, `xml`, `curl`, `pdo_mysql`, `zip`, `bcmath`, `gd`, `intl`, `sqlite3`/`pdo_sqlite` (untuk test) | `php -m` |
| Composer | 2.x | `composer -V` |
| Node.js | 18+ (disarankan 20) | `node -v` |
| MySQL / MariaDB | 8.x / 10.11+ | `mysql --version` |

Bila ada yang belum terpasang (Debian/Ubuntu):

```bash
sudo apt-get install -y php8.3 php8.3-cli php8.3-mbstring php8.3-xml php8.3-curl \
  php8.3-mysql php8.3-zip php8.3-bcmath php8.3-gd php8.3-intl php8.3-sqlite3 \
  mariadb-server

curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### 1.2 Clone & install dependency

```bash
git clone <url-repo> sig
cd sig

composer install
npm install
```

### 1.3 Siapkan database

Buat database & user MySQL/MariaDB (sesuaikan nama/password sesuai selera):

```bash
sudo mysql -e "
CREATE DATABASE IF NOT EXISTS si_ppg;
CREATE USER IF NOT EXISTS 'si_ppg'@'127.0.0.1' IDENTIFIED BY 'password-development';
GRANT ALL PRIVILEGES ON si_ppg.* TO 'si_ppg'@'127.0.0.1';
FLUSH PRIVILEGES;
"
```

### 1.4 Konfigurasi environment

```bash
cp .env.example .env
php artisan key:generate
```

Buka `.env`, sesuaikan bagian database dengan yang dibuat di langkah 1.3:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=si_ppg
DB_USERNAME=si_ppg
DB_PASSWORD=password-development
```

### 1.5 Migrasi & seed data awal

```bash
php artisan migrate --seed
```

Perintah ini membuat seluruh tabel **dan** mengisi data awal:
- 1 Daerah ("Jakarta Selatan 1"), 7 Desa, 44 Kelompok (data riil dari `docs/dataset/*.csv`)
- Role & permission (`admin-daerah`, `pjp-desa`, `pjp-kelompok`, `sekretaris-kbm`, `guru`)
- 1 akun Admin Daerah bawaan (lihat [§3](#3-akses-yang-bisa-digunakan-di-local))

### 1.6 Build asset front-end

```bash
npm run build
```

---

## 2. Menjalankan di Local

Butuh **3 terminal terpisah** (jalankan dari root proyek):

```bash
# Terminal 1 — server aplikasi
php artisan serve
# → http://127.0.0.1:8000

# Terminal 2 — asset front-end dengan hot-reload (opsional saat mengedit CSS/JS)
npm run dev

# Terminal 3 — queue worker (WAJIB agar notifikasi Alpha ke Orang Tua terkirim,
# karena listener-nya di-queue — lihat SRS §12.3)
php artisan queue:work
```

> Kalau tidak mau menjalankan queue worker terus-menerus saat development, alternatif
> tercepat: set `QUEUE_CONNECTION=sync` di `.env` (job jalan langsung tanpa antrean).
> Jangan pakai `sync` di production (PRD §18.3 — shared hosting pakai driver `database`
> + cron `schedule:run`).

Setelah semua jalan, buka:
- **Aplikasi Internal**: http://127.0.0.1:8000/login
- **Portal Orang Tua**: http://127.0.0.1:8000/portal/login

### Menjalankan test

```bash
php artisan test
```

### Reset database (hapus semua data & seed ulang dari awal)

```bash
php artisan migrate:fresh --seed
```

---

## 3. Akses yang Bisa Digunakan di Local

### 3.1 Aplikasi Internal (`/login`)

Akun bawaan hasil seeding (`php artisan migrate --seed`):

| Username | Password | Role | Catatan |
|---|---|---|---|
| `admin` | `password` | Admin Daerah | **Wajib ganti password** di login pertama (tidak bisa dilewati — SRS §3.3) |

Setelah login sebagai `admin`, akun peran lain (PJP Desa, PJP Kelompok, Sekretaris KBM, Guru) dibuat manual lewat menu **Pengaturan → Kelola Pengguna → Tambah Akun**. Password awal berupa **string acak 8 karakter** yang ditampilkan **satu kali** di layar saat akun dibuat — salin dan catat saat itu juga, sistem tidak menyimpan salinannya (SRS §5.2, §9.18).

**Akun demo PJP Desa & PJP Kelompok** (opsional, tidak ikut `--seed` baseline) — kalau kamu sudah menjalankan seeder data dummy di bawah ([§4](#4-data-dummy-opsional-kelompok-pondok-jaya)), 2 akun ini juga ikut dibuat:

| Username | Password | Role | Scope |
|---|---|---|---|
| `pjp.desa.bintarojaya` | `password` | PJP Desa | Desa Bintaro Jaya (id 2) — kelola Generus & Pendidik lintas-Kelompok di desa ini |
| `pjp.kelompok.pondokjaya` | `password` | PJP Kelompok | Kelompok Pondok Jaya (id 14) |

Sama seperti `admin`, keduanya **wajib ganti password** di login pertama.

### 3.2 Portal Orang Tua (`/portal/login`)

**Tidak ada akun bawaan** — akun Portal Orang Tua diprovisioning otomatis hanya saat sebuah Generus (santri) baru disimpan (UC-16), memakai nomor HP orang tua sebagai username. Untuk mencoba Portal Orang Tua secara lokal:

1. Login sebagai `admin` (atau akun PJP Kelompok/Sekretaris KBM).
2. Buka **Master Data → Struktur Organisasi**, pastikan ada minimal satu **Kelas** di bawah salah satu dari 44 Kelompok yang sudah ter-seed (kalau belum ada, tambah dulu satu Kelas).
3. Buka **Master Data → Generus → Tambah Generus**, isi data + Nomor HP Orang Tua (mis. `081234567890`).
4. Sistem otomatis membuat akun Portal Orang Tua (username = nomor HP tsb, password acak 8 karakter). Password awal ini **tidak ditampilkan di UI saat provisioning otomatis** (prosesnya di belakang layar, lihat SRS §12.2) — cara termudah mendapatkan password yang bisa dipakai login di local:

   Buka **Pengaturan → Kelola Pengguna → tab Akun Orang Tua**, cari nomor HP yang baru dibuat, klik **Reset Password** — password baru akan ditampilkan sekali di layar (sama seperti akun internal, SRS §3.4), lalu langsung login ke `/portal/login` dengan nomor HP + password tsb.

### 3.3 Akses Presensi Kegiatan via Token (tanpa akun)

Khusus Petugas Presensi Kegiatan berupa Generus (bukan akun internal) — tautan token di-generate otomatis di menu **Kegiatan → Kelola Petugas Presensi** setiap kali seorang Generus ditunjuk sebagai petugas untuk Kegiatan tingkat Desa/Daerah. Tautan ditampilkan sekali di layar (`/kegiatan/presensi/{token}`), berlaku sampai H+1 tanggal Kegiatan.

---

## 4. Data Dummy Opsional (Kelompok Pondok Jaya)

Untuk mencoba tampilan dengan data yang lebih realistis (sensus, dashboard, presensi, login PJP) tanpa input manual satu-satu, ada seeder terpisah yang mengisi Kelompok **Pondok Jaya** (Desa Bintaro Jaya) dengan data contoh. **Tidak ikut** `migrate --seed` baseline — jalankan manual setelah langkah [§1.5](#15-migrasi--seed-data-awal):

```bash
php artisan db:seed --class=DummyPondokJayaSeeder
```

Seeder ini membuat:
- 16 Kelas (PAUD A/B s.d. Kelas 12, GPN-A/B) dan 4 Pendidik dengan penugasan kelas
- 102 Generus (siswa) tersebar di seluruh kelas, lengkap dengan akun Portal Orang Tua masing-masing (nomor HP acak, password acak — reset lewat **Kelola Pengguna → Akun Orang Tua** kalau perlu login, sama seperti [§3.2](#32-portal-orang-tua-portallogin))
- 2 akun PJP demo (`pjp.desa.bintarojaya`, `pjp.kelompok.pondokjaya`, password `password`) — lihat [§3.1](#31-aplikasi-internal-login)

Aman dijalankan berulang (idempotent) — tidak akan menduplikasi data kalau sudah pernah dijalankan.

---

## 5. Data Dummy Opsional (Laporan Bulanan)

Untuk mencoba seluruh alur Laporan Bulanan (SRS-Fase-2 §3) — Laporan Kelompok, Laporan Desa, Laporan Daerah, antrian approval, dan telusur laporan berjenjang — tanpa mengisi manual dulu lewat UI, ada seeder terpisah. **Tidak ikut** `migrate --seed` baseline. Scope-nya sengaja terpisah dari [§4](#4-data-dummy-opsional-kelompok-pondok-jaya) — 4 Kelompok baru (BIG 1, BIG 2 di Desa Bumi Indah Grogol; Bintaro Jaya Barat, Bintaro Jaya Timur di Desa Bintaro Jaya) — supaya bisa direset tanpa menyentuh data Pondok Jaya atau data asli. Jalankan manual setelah [§1.5](#15-migrasi--seed-data-awal):

```bash
php artisan db:seed --class=DummyLaporanBulananSeeder
```

Seeder ini membuat, untuk 3 periode (2 bulan lalu, bulan lalu, bulan berjalan — mengikuti `now()` saat dijalankan):
- 4 Kelas per Kelompok berisi Generus dummy (cukup untuk mengisi Sensus & Daftar Generus)
- Kegiatan Tambahan + presensi, Program Monitoring, dan Musyawaroh tingkat Kelompok/Desa/Daerah
- Sensus Snapshot bulanan
- Laporan Bulanan tingkat Kelompok, Desa, dan Daerah dengan status yang sengaja dibuat bervariasi supaya semua state di UI bisa didemokan sekaligus:
  - **Kelompok BIG 1**: riwayat revisi (v1 `REVISI_DIMINTA` dengan catatan revisi, v2 `DISETUJUI`)
  - **Kelompok lain**: periode 2 bulan lalu `DISETUJUI`, bulan lalu `FINAL` (menunggu review PJP Desa), bulan berjalan `DRAFT`
  - **Desa**: periode 2 bulan lalu `DISETUJUI`, bulan lalu `FINAL` (menunggu review Admin Daerah)
  - **Daerah**: 2 periode `FINAL` (alur Laporan Daerah berhenti di `FINAL`, tidak ada approval lanjutan)
- 6 akun PJP demo (password `password`, wajib ganti di login pertama): `pjp.kelompok.big1`, `pjp.kelompok.big2`, `pjp.kelompok.bintarojayabarat`, `pjp.kelompok.bintarojayatimur`, `pjp.desa.bumiindahgrogol`, `pjp.desa.bintarojaya`

> `pjp.desa.bintarojaya` adalah akun yang sama dengan PJP Desa Bintaro Jaya di [§4](#4-data-dummy-opsional-kelompok-pondok-jaya) (di-`updateOrCreate`, bukan dibuat baru) — dipakai bersama oleh kedua seeder karena sama-sama menyentuh Desa Bintaro Jaya.

Idempotent — `run()` selalu menghapus data lamanya sendiri lebih dulu, lalu membangun ulang dari nol, jadi aman dijalankan berulang.

Untuk menghapus data dummy ini saja tanpa membangunnya ulang:

```bash
php artisan dummy:laporan-reset
```

> Perintah ini juga menghapus akun `pjp.desa.bintarojaya`. Kalau kamu masih butuh akun itu untuk demo Pondok Jaya ([§4](#4-data-dummy-opsional-kelompok-pondok-jaya)), jalankan ulang `php artisan db:seed --class=DummyPondokJayaSeeder` setelah reset.
