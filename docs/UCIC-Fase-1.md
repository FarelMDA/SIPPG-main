# Use Case Integration Contract (UCIC)
# SI-PPG — Fase 1: Fondasi Pendataan Harian & Portal Orang Tua

**Nama Sistem:** SI-PPG — Sistem Informasi Pembinaan Generus
**Versi Dokumen:** 1.14
**Tanggal:** 2 Agustus 2026
**Status:** Draft
**Klasifikasi:** Internal — Terbatas
**Dokumen Sumber:** [SRS-Fase-1.md](SRS-Fase-1.md)
**Dokumen Terkait:** [PRD-Aplikasi-Pendataan-Pelaporan-PPG.md](PRD-Aplikasi-Pendataan-Pelaporan-PPG.md), [Struktur-Proyek-Fase-1.md](Struktur-Proyek-Fase-1.md), [Struktur-Organisasi-dan-Role.md](Struktur-Organisasi-dan-Role.md)

---

## Daftar Isi

1. [Tujuan Dokumen](#1-tujuan-dokumen)
2. [Konvensi Dokumen](#2-konvensi-dokumen)
3. [Daftar Use Case](#3-daftar-use-case)
4. [UC-01 — Login](#uc-01--login)
5. [UC-02 — Ganti Password](#uc-02--ganti-password)
6. [UC-03 — Reset Password oleh Admin](#uc-03--reset-password-oleh-admin)
7. [UC-04 — Kelola Struktur Organisasi (Desa/Kelompok/Kelas)](#uc-04--kelola-struktur-organisasi-desakelompokkelas)
8. [UC-05 — Kelola Data Generus](#uc-05--kelola-data-generus)
9. [UC-06 — Kelola Data Pendidik](#uc-06--kelola-data-pendidik)
10. [UC-07 — Kelola Akun Internal](#uc-07--kelola-akun-internal)
11. [UC-08 — Impor Massal Data Awal](#uc-08--impor-massal-data-awal)
12. [UC-09 — Lihat Sensus](#uc-09--lihat-sensus)
13. [UC-10 — Kelola Kalender Kurikulum](#uc-10--kelola-kalender-kurikulum)
14. [UC-11 — Konfirmasi Realisasi Materi Harian](#uc-11--konfirmasi-realisasi-materi-harian)
15. [UC-12 — Input Presensi Harian](#uc-12--input-presensi-harian)
16. [UC-13 — Input & Approval Jurnal Harian Mengajar](#uc-13--input--approval-jurnal-harian-mengajar)
17. [UC-14 — Sinkronisasi Offline (Presensi & Jurnal)](#uc-14--sinkronisasi-offline-presensi--jurnal)
18. [UC-15 — Kelola Musyawaroh & Notulen](#uc-15--kelola-musyawaroh--notulen)
19. [UC-16 — Provisioning Akun Portal Orang Tua (Otomatis)](#uc-16--provisioning-akun-portal-orang-tua-otomatis)
20. [UC-17 — Lihat Data Anak (Portal Orang Tua)](#uc-17--lihat-data-anak-portal-orang-tua)
21. [UC-18 — Notifikasi Alpha](#uc-18--notifikasi-alpha)
22. [UC-19 — Dashboard Kelompok](#uc-19--dashboard-kelompok)
23. [UC-20 — Ekspor Cetak (Print-CSS)](#uc-20--ekspor-cetak-print-css)
24. [UC-21 — Kelola Kegiatan](#uc-21--kelola-kegiatan)
25. [UC-22 — Kelola Petugas Presensi Kegiatan](#uc-22--kelola-petugas-presensi-kegiatan)
26. [UC-23 — Input Presensi Kegiatan](#uc-23--input-presensi-kegiatan)
27. [UC-24 — Kelola Program Monitoring](#uc-24--kelola-program-monitoring)
28. [UC-25 — Rekap Kegiatan & Program Monitoring (Cetak)](#uc-25--rekap-kegiatan--program-monitoring-cetak)
29. [UC-26 — Kelola Matriks Role & Permission](#uc-26--kelola-matriks-role--permission)
30. [UC-27 — Kelola Catatan Konseling (Rekam Kasus)](#uc-27--kelola-catatan-konseling-rekam-kasus)

---

## 1. Tujuan Dokumen

Dokumen ini mendefinisikan **kontrak integrasi** antara setiap use case Fase 1 SI-PPG dengan komponen sistem (Livewire component, endpoint JSON, database). Setiap kontrak mencakup aktor & prasyarat, alur sukses/gagal, kontrak teknis (properti/aksi Livewire atau endpoint JSON), aturan bisnis, dan implikasi keamanan/audit.

Karena arsitektur Fase 1 adalah **hybrid** (SRS §2.1): mayoritas UI dibangun Livewire (server-rendered, tanpa JSON eksplisit), sedangkan mekanisme offline memakai JSON API murni. Setiap use case di bawah menandai jenis kontraknya secara eksplisit:

- **[LIVEWIRE]** — kontrak berupa nama Component, Properties (state), dan Actions (method yang bisa dipanggil dari UI).
- **[JSON]** — kontrak berupa endpoint REST sungguhan dengan request/response body.

---

## 2. Konvensi Dokumen

| Simbol / Label | Makna |
|----------------|-------|
| `[WAJIB]` | Field harus ada dalam request/properti |
| `[OPSIONAL]` | Field boleh tidak ada |
| `[AUDIT]` | Aksi ini wajib dicatat di `activity_log` (`spatie/laravel-activitylog`) |
| `[RBAC]` | Akses dikontrol oleh role/permission yang disebutkan (`spatie/laravel-permission`) |
| `→ 200` | HTTP response code sukses (khusus kontrak **[JSON]**) |

**Base URL API (khusus UC-14):** `https://<host>/api/v1`

**Header Auth endpoint JSON:**
```
Authorization: Bearer <sanctum_token>
```

**Catatan Guard:** Dua guard terpisah berlaku di seluruh dokumen ini — `web` (akun internal: Admin Daerah, PJP Desa, PJP Kelompok, Sekretaris KBM, Guru) dan `orangtua` (akun Portal Orang Tua). Session/token tidak pernah dipertukarkan antar guard. **UC-23** memakai jalur akses ketiga di luar kedua guard ini — tautan token per-Kegiatan tanpa akun sama sekali (SRS §2.1, §18.2), khusus Petugas Presensi Kegiatan yang ditugaskan ke seorang Generus.

---

## 3. Daftar Use Case

| ID | Nama Use Case | Aktor Utama | Jenis Kontrak |
|----|---------------|-------------|----------------|
| UC-01 | Login | Semua role (kedua guard) | LIVEWIRE |
| UC-02 | Ganti Password | Semua role (kedua guard) | LIVEWIRE |
| UC-03 | Reset Password oleh Admin | Admin Daerah, PJP Kelompok | LIVEWIRE |
| UC-04 | Kelola Struktur Organisasi (Desa/Kelompok/Kelas) | Admin Daerah, PJP Desa (lihat), Wanbin Daerah (lihat) | LIVEWIRE |
| UC-05 | Kelola Data Generus | Sekretaris KBM, PJP Kelompok, PJP Desa, Admin Daerah, Pakar Pendidik (lihat) | LIVEWIRE |
| UC-06 | Kelola Data Pendidik | PJP Kelompok, PJP Desa, Admin Daerah, Bidang Tendik | LIVEWIRE |
| UC-07 | Kelola Akun Internal | Admin Daerah, PJP Kelompok | LIVEWIRE |
| UC-08 | Impor Massal Data Awal | Admin Daerah | LIVEWIRE |
| UC-09 | Lihat Sensus | PJP Kelompok, Admin Daerah | LIVEWIRE |
| UC-10 | Kelola Kalender Kurikulum | Admin Daerah, Bidang Kurikulum; Pakar Pendidik (lihat) | LIVEWIRE |
| UC-11 | Konfirmasi Realisasi Materi Harian | Guru | LIVEWIRE |
| UC-12 | Input Presensi Harian | Guru, Sekretaris KBM | LIVEWIRE |
| UC-13 | Input & Approval Jurnal Harian Mengajar | Guru; PJP Kelompok (approval) | LIVEWIRE |
| UC-14 | Sinkronisasi Offline (Presensi & Jurnal) | Sistem klien (otomatis, atas nama Guru/Sekretaris) | **JSON** |
| UC-15 | Kelola Musyawaroh & Notulen | Sekretaris KBM, Sekretaris Desa, Sekretaris PPG; PJP Kelompok, Wanbin Kelompok, Wanbin Desa, Wanbin Daerah (memimpin/mengesahkan) | LIVEWIRE |
| UC-16 | Provisioning Akun Portal Orang Tua (Otomatis) | Sistem (dipicu UC-05) | LIVEWIRE (internal) |
| UC-17 | Lihat Data Anak (Portal Orang Tua) | Orang Tua | LIVEWIRE |
| UC-18 | Notifikasi Alpha | Sistem (dipicu UC-12/UC-14); Orang Tua (penerima) | LIVEWIRE |
| UC-19 | Dashboard Kelompok | PJP Kelompok, Wanbin Kelompok, Wanbin Desa, Admin Daerah | LIVEWIRE |
| UC-20 | Ekspor Cetak (Print-CSS) | Semua role internal | LIVEWIRE (client-side) |
| UC-21 | Kelola Kegiatan | PJP Kelompok, PJP Desa, Admin Daerah | LIVEWIRE |
| UC-22 | Kelola Petugas Presensi Kegiatan | PJP Kelompok, Sekretaris KBM | LIVEWIRE |
| UC-23 | Input Presensi Kegiatan | Petugas Presensi Kegiatan (akun internal atau Generus via token) | LIVEWIRE |
| UC-24 | Kelola Program Monitoring | Sekretaris KBM, PJP Kelompok | LIVEWIRE |
| UC-25 | Rekap Kegiatan & Program Monitoring (Cetak) | PJP Kelompok, PJP Desa, Admin Daerah | LIVEWIRE (client-side) |
| UC-26 | Kelola Matriks Role & Permission | Admin Daerah | LIVEWIRE |
| UC-27 | Kelola Catatan Konseling (Rekam Kasus) | Bagian BK/Konselor (`bk-kbm`); PJP Kelompok, Admin Daerah (lihat) | LIVEWIRE |

---

## UC-01 — Login

### Deskripsi
User (internal maupun Orang Tua) melakukan autentikasi untuk mendapatkan sesi aktif. Dua guard terpisah dengan halaman login berbeda.

### Aktor
- **Utama:** Semua role internal (guard `web`); Orang Tua (guard `orangtua`)

### Prasyarat
- Akun terdaftar (`users` atau `akun_orang_tua`), `is_active = true`

### Pasca-kondisi (Sukses)
- Session Laravel terbentuk (cookie HttpOnly)
- Jika `must_change_password = true` → dipaksa redirect ke UC-02, tidak bisa mengakses halaman lain
- Event login dicatat `[AUDIT]`

### Pasca-kondisi (Gagal)
- Tidak ada sesi terbentuk; pesan error generik ("username/nomor HP atau password salah") — tidak membedakan apakah akun ada atau tidak, untuk mencegah enumerasi akun

---

### Kontrak **[LIVEWIRE]**

**Component:** `Auth\LoginForm` (guard `web`) / `Auth\LoginOrangTuaForm` (guard `orangtua`)

**Properties:**
```
username / nomor_hp : string [WAJIB]
password            : string [WAJIB]
```

**Actions:**
```
submit()
  → validasi input tidak kosong
  → Auth::guard($guard)->attempt([...])
  → gagal  → tampilkan error generik, hitung percobaan gagal (rate limit)
  → sukses → cek must_change_password → redirect sesuai UC-02 atau Dashboard/Portal
```

#### Kondisi Gagal

| Kondisi | Pesan |
|---------|-------|
| Kredensial salah | "Username/nomor HP atau password salah" |
| Akun `is_active = false` | "Akun tidak aktif, hubungi pengurus" |
| >5 percobaan gagal dalam 10 menit (per username/IP) | "Terlalu banyak percobaan, coba lagi nanti" |

### Aturan Bisnis
- Password diverifikasi hash bcrypt (bawaan Laravel), tidak pernah dibandingkan plain-text.
- Rate limiting: maksimal 5 percobaan gagal / 10 menit per kombinasi username+IP.
- Guard `orangtua` login memakai **nomor HP** sebagai username (PRD §9.18), bukan email/username bebas.
- Redirect setelah login sukses (guard `web`) role-aware lewat `User::landingRouteName()`: PJP Desa → UC-04 (Kelola Struktur Organisasi, belum punya Dashboard di Fase 1 — SRS §1.1); role lain → UC-19 (Dashboard Kelompok). Dashboard khusus PJP Desa menyusul Fase 2 (UCIC-Fase-2 UC-36).

---

## UC-02 — Ganti Password

### Deskripsi
User mengganti password sendiri — wajib (dipaksa, tidak bisa dilewati) bila `must_change_password = true`, atau sukarela kapan saja setelahnya.

### Aktor
- **Utama:** Semua role (kedua guard)

### Prasyarat
- Sesi aktif (sudah login, lihat UC-01)

### Pasca-kondisi (Sukses)
- Password baru tersimpan (hash), `must_change_password = false`
- Dicatat `[AUDIT]`

---

### Kontrak **[LIVEWIRE]**

**Component:** `Auth\GantiPasswordForm`

**Properties:**
```
password_lama      : string [WAJIB, dilewati jika dipaksa dari must_change_password pertama kali]
password_baru       : string [WAJIB] — minimal 8 karakter
password_konfirmasi : string [WAJIB] — harus sama dengan password_baru
```

**Actions:**
```
submit()
  → validasi: password_baru ≥ 8 karakter, password_konfirmasi cocok, berbeda dari password_lama
  → jika bukan dipaksa (must_change_password sudah false): verifikasi password_lama benar
  → simpan hash baru, must_change_password = false
  → catat activity_log [AUDIT]
  → redirect ke Portal (guard orangtua) atau sesuai role (guard web, sama seperti UC-01)
```

#### Kondisi Gagal

| Kondisi | Pesan |
|---------|-------|
| Password baru < 8 karakter | "Password minimal 8 karakter" |
| Konfirmasi tidak cocok | "Konfirmasi password tidak sama" |
| Password baru = password lama | "Password baru harus berbeda dari password lama" |
| Password lama salah (mode sukarela) | "Password saat ini salah" |

### Aturan Bisnis
- Middleware `EnsurePasswordChanged` memblokir seluruh rute lain (kecuali logout) selama `must_change_password = true` — berlaku untuk kedua guard, termasuk akun Portal Orang Tua yang baru diprovisioning (PRD §9.18) dan akun internal baru/direset (SRS §3.4).

---

## UC-03 — Reset Password oleh Admin

### Deskripsi
Admin Daerah atau PJP Kelompok mereset password akun (internal maupun Orang Tua) yang lupa passwordnya. **Tidak ada jalur self-service** di Fase 1 (SRS §1.1).

### Aktor
- **Utama:** Admin Daerah (semua akun); PJP Kelompok (akun Sekretaris/Guru/Orang Tua di kelompoknya)

### Prasyarat
- Permission `users.reset-password` atau `orangtua.reset-password` `[RBAC]`

### Pasca-kondisi (Sukses)
- Password baru (string acak 8 karakter) tersimpan; `must_change_password = true`
- Seluruh sesi aktif akun tsb diinvalidasi (force logout)
- Dicatat `[AUDIT]`

---

### Kontrak **[LIVEWIRE]**

**Component:** `Admin\KelolaPengguna` / `Admin\KelolaAkunOrangTua`

**Actions:**
```
resetPassword($userId atau $akunOrangTuaId)
  → cek permission & scope (PJP Kelompok hanya untuk akun di kelompoknya)
  → generate string acak 8 karakter
  → simpan hash, must_change_password = true
  → invalidate seluruh session/token aktif akun tsb
  → tampilkan password baru ke Admin (untuk disampaikan manual — SRS §3.4)
  → catat activity_log [AUDIT]
```

#### Kondisi Gagal

| Kondisi | Pesan |
|---------|-------|
| Di luar scope (PJP Kelompok reset akun kelompok lain) | "Anda tidak berwenang mereset akun ini" |
| Akun tidak ditemukan | "Akun tidak ditemukan" |

### Aturan Bisnis
- Password baru **selalu** string acak 8 karakter — pola sama persis dengan provisioning awal (PRD §9.18), tidak ada pola lain (bukan tanggal lahir atau kode tetap).
- Reset akun Orang Tua yang tertaut ke >1 Generus berlaku untuk akun tsb secara keseluruhan (bukan per-anak) — konsisten PRD §9.18.

---

## UC-04 — Kelola Struktur Organisasi (Desa/Kelompok/Kelas)

### Deskripsi
Admin Daerah mengelola data Desa, Kelompok, Jenjang, dan Kelas sebagai fondasi hierarki organisasi (SRS §4, §6.1). Halaman ini punya 4 tab: **Desa**, **Kelompok**, **Jenjang** (katalog global PAUD_A..GPN_B — tidak terikat Kelompok, memakai komponen Referensi generik), dan **Kelas** (Kelas sungguhan di lapangan, per Kelompok, bisa menggabungkan >1 Jenjang — SRS §6.1). Tab Jenjang & tab Kelas menaungi dua tabel berbeda dengan tujuan berbeda: `jenjang` (referensi) vs `rombongan_belajar` (struktural, pelaporan) — lihat SRS §6.1 untuk perbedaannya. Tabel `kelas` (junction internal Kelompok×Jenjang yang dipakai pipeline KBM/presensi) **tidak** punya tab tersendiri — murni backend, auto-provisioned.

### Aktor
- **Utama:** Admin Daerah (kelola penuh seluruh tab); PJP Desa (lihat saja tab Desa/Kelompok/Kelas — daftar di desanya, §4.2); Wanbin Daerah (lihat saja, seluruh Daerah, `wanbin-daerah` — Struktur-Organisasi-dan-Role.md §A)

### Prasyarat
- Tab Desa/Kelompok/Kelas: permission `struktur-organisasi.manage` (Admin Daerah) atau `struktur-organisasi.view` (PJP Desa, Wanbin Daerah — read-only) `[RBAC]`
- Tab Jenjang: permission `referensi.manage` (Admin Daerah/Sysadmin saja — **bukan** `struktur-organisasi.*`) `[RBAC]`; role lain (termasuk PJP Desa/Wanbin Daerah yang punya `struktur-organisasi.view`) melihat pesan tidak-ada-akses di tab ini, bukan error

---

### Kontrak **[LIVEWIRE]**

**Component:** `MasterData\KelolaDesa`, `MasterData\KelolaKelompok`, `MasterData\KelolaReferensi` (tab Jenjang — komponen generik yang sama dipakai halaman Master Data > Referensi untuk Jenis Kelamin/Status Domisili/Jenis Pendidik/Status Presensi), `MasterData\KelolaKelas` (tab Kelas — CRUD `rombongan_belajar`)

**Properties (`KelolaKelas`):**
```
kelompok_id : integer [WAJIB]
nama        : string [WAJIB] — mis. "Kelas ACR A", bebas diisi admin
jenjangIds  : integer[] [WAJIB, min 1] — checkbox multi-select ke tabel master `jenjang`; boleh >1 (Kelas gabungan)
```

**Actions:**
```
simpan()          → create/update baris rombongan_belajar + sync pivot rombongan_belajar_jenjang
toggleAktif($id)  → flip status_aktif
hapus($id)        → soft-delete; ditolak jika ada Generus aktif di Kelompok itu yang jenjang_id-nya termasuk salah satu Jenjang Kelas ini
```

#### Kondisi Gagal

| Kondisi | Pesan |
|---------|-------|
| Hapus Kelas yang masih dipakai Generus | "Tidak dapat menghapus — masih ada Generus di Kelas ini" |

### Aturan Bisnis
- Struktur Daerah bersifat statis (satu baris, SRS §17.1) — tidak ada UI tambah/hapus Daerah di Fase 1.
- Saat Kelompok baru dibuat, `rombongan_belajar` auto-provisioned 1 baris per Jenjang (nama = label Jenjang) sebagai starting point — admin bisa gabungkan beberapa Jenjang jadi satu Kelas atau ganti nama belakangan lewat tab ini, kapan saja.
- Kelas gabungan (>1 Jenjang) murni untuk pelaporan — **tidak** memengaruhi generate Kegiatan KBM, materi, atau presensi (UC-05, §9/§18 SRS), yang tetap beroperasi per-Jenjang lewat tabel `kelas` (tidak berubah).

---

## UC-05 — Kelola Data Generus

### Deskripsi
Sekretaris KBM atau PJP Kelompok menambah/mengedit data Generus, termasuk status domisili (Setempat/Pendatang) dan riwayat kenaikan kelas. PJP Desa dan Admin Daerah juga bisa mengelola Generus di lintas-Kelompok (§4.2 SRS) — keduanya memilih Kelompok tujuan secara eksplisit lewat field Desa/Kelompok tambahan sebelum memilih Jenjang. Form memilih **Jenjang** langsung (bukan Kelas) — `kelas_id` di-derive otomatis dari pasangan (Kelompok, Jenjang) lewat tabel `kelas` (SRS §6.1), karena satu Kelas sungguhan (`rombongan_belajar`, UC-04) bisa menggabungkan >1 Jenjang sehingga tidak lagi cukup jadi basis pemilihan.

### Aktor
- **Utama:** Sekretaris KBM, PJP Kelompok (scope kelompoknya); PJP Desa (scope seluruh Kelompok dalam Desanya); Admin Daerah (scope seluruh Daerah); Pakar Pendidik (lihat saja, seluruh Daerah — `pakar-pendidik`, baru, akses konsultatif read-only, Struktur-Organisasi-dan-Role.md §D)

### Prasyarat
- Permission `generus.manage` `[RBAC]` (Sekretaris KBM, PJP Kelompok, PJP Desa, Admin Daerah) atau `generus.view` `[RBAC]` (Pakar Pendidik, baru — read-only); Generus (via `kelas_id`) berada dalam scope kelompok user — untuk PJP Desa, scope-nya seluruh Kelompok yang `desa_id`-nya sama dengan Desa miliknya; Pakar Pendidik tidak dibatasi scope kelompok/desa (LUPG, lintas-tingkat)

### Pasca-kondisi (Sukses — Tambah Baru)
- Baris `generus` tersimpan
- **Memicu UC-16** (provisioning akun Portal Orang Tua otomatis)
- Dicatat `[AUDIT]`

### Pasca-kondisi (Sukses — Ubah Status Domisili)
- Baris baru di `generus_status_histories` (SRS §17.2) — riwayat, bukan menimpa

### Pasca-kondisi (Sukses — Kenaikan Kelas)
- Baris baru di `generus_kelas_histories` (SRS §17.2) — riwayat, bukan menimpa
- Dicatat `[AUDIT]`

---

### Kontrak **[LIVEWIRE]**

**Component:** `MasterData\KelolaGenerus`

**Properties:**
```
nama                : string [WAJIB]
tanggal_lahir       : date [WAJIB]
jenis_kelamin       : enum [WAJIB] — LAKI|PEREMPUAN
desa_id             : integer [OPSIONAL] — khusus Admin Daerah, filter opsi Kelompok di bawah
kelompok_id         : integer [KONDISIONAL] — wajib untuk Admin Daerah & PJP Desa (memfilter opsi jenjang_id ke Jenjang yang aktif di Kelompok itu); tersembunyi & otomatis untuk PJP Kelompok/Sekretaris KBM
jenjang_id          : integer [WAJIB] — Jenjang Generus saat ini (dipilih langsung); kelas_id di-derive dari sini + kelompok_id, tidak dipilih manual
nama_orang_tua      : string [WAJIB]
nomor_hp_orang_tua  : string [WAJIB] — format nomor HP Indonesia valid
status_domisili     : enum [WAJIB] — SETEMPAT|PENDATANG — flag klasifikasi kontak konfirmasi hasil KBM, tidak memengaruhi kelas_id/jenjang_id
```

**Actions:**
```
simpan()
  → validasi field wajib
  → derive kelas_id = Kelas dengan (kelompok_id, jenjang_id) yang cocok — selalu ada (auto-provisioned, UC-04)
  → validasi Jenjang tsb aktif di Kelompok itu (kecuali edit tanpa ubah jenjang_id) & kelompok_id dalam scope user (kelompoknya, atau seluruh Kelompok di Desanya utk PJP Desa, atau bebas utk Admin Daerah)
  → simpan generus (kelas_id & jenjang_id)
  → jika baru: trigger UC-16 (provisioning akun orang tua)
  → jika status_domisili berubah dari sebelumnya: insert generus_status_histories
  → catat activity_log [AUDIT]

naikkanKelas($generusId, $jenjangBaruId, $semester)
  → derive kelas_id baru dari (kelompok_id Generus saat ini, jenjangBaruId) — naik kelas tetap dalam Kelompok yang sama, cuma pindah Jenjang
  → insert generus_kelas_histories (dicatat_oleh = user aktif)
  → update kelas_id & jenjang_id
  → catat activity_log [AUDIT]
```

#### Kondisi Gagal

| Kondisi | Pesan |
|---------|-------|
| Nomor HP orang tua format tidak valid | "Format nomor HP tidak valid" |
| `jenjang_id` (via Kelas turunannya) di luar scope user (kelompok/Desa), atau tidak aktif di Kelompok tsb | "Jenjang tidak valid untuk Anda" |

### Aturan Bisnis
- Sesuai PRD §8: `jenjang_id` dan `kelas_id` selalu wajib diisi, untuk Generus Setempat maupun Pendatang — tidak ada kondisi boleh kosong. `jenjang_id` adalah Jenjang individual Generus (basis sensus per-Jenjang, SRS §7); `kelas_id` tetap basis Kegiatan KBM/presensi (SRS §9/§18), sekarang di-derive bukan dipilih manual.
- Menyimpan Generus baru **selalu** memicu UC-16, tidak terkecuali.
- **Pakar Pendidik** hanya membuka `KelolaGenerus` dalam mode lihat (tanpa `simpan()`/`naikkanKelas()`) — UI menyembunyikan seluruh kontrol tulis bila user hanya punya `generus.view` tanpa `generus.manage`, pola sama dengan PJP Desa read-only di UC-04. Tidak ada akses ke data konseling/rekam kasus (UC-27) lewat halaman ini.
- PJP Desa & Admin Daerah memilih Kelompok tujuan secara eksplisit (field `kelompok_id`) sebelum daftar `kelas_id` terisi — mencegah salah tempatkan Generus ke Kelompok yang tidak dimaksud saat scope-nya mencakup lebih dari satu Kelompok.

---

## UC-06 — Kelola Data Pendidik

### Deskripsi
PJP Kelompok menambah/mengedit data Pendidik (MT/MS) dan penugasan ke kelas. PJP Desa, Admin Daerah, dan Bidang Tenaga Pendidik juga bisa mengelola Pendidik di lintas-Kelompok (§4.2 SRS) — ketiganya memilih Kelompok tujuan secara eksplisit lewat field Desa/Kelompok tambahan.

### Aktor
- **Utama:** PJP Kelompok (scope kelompoknya); PJP Desa (scope seluruh Kelompok dalam Desanya); Admin Daerah, Bidang Tenaga Pendidik/Tendik (`bidang-tendik`, baru — Struktur-Organisasi-dan-Role.md §A) — keduanya scope seluruh Daerah

### Prasyarat
- Permission `pendidik.manage` `[RBAC]` — Daerah-wide untuk Admin Daerah & Bidang Tendik; untuk PJP Desa, Kelompok tujuan harus berada dalam Desa miliknya

---

### Kontrak **[LIVEWIRE]**

**Component:** `MasterData\KelolaPendidik`

**Properties:**
```
nama         : string [WAJIB]
jenis        : enum [WAJIB] — MT|MS
desa_id      : integer [OPSIONAL] — khusus Admin Daerah/Bidang Tendik, filter opsi Kelompok di bawah
kelompok_id  : integer [KONDISIONAL] — wajib untuk Admin Daerah, Bidang Tendik, & PJP Desa (menentukan Kelompok pemilik Pendidik & memfilter opsi kelas_ids); tersembunyi & otomatis untuk PJP Kelompok
kelas_ids    : array<integer> [OPSIONAL] — kelas yang diampu (many-to-many)
```

**Actions:**
```
simpan()   → validasi kelompok_id (utk PJP Desa, wajib berada di Desa miliknya) → create/update pendidik + sync pendidik_kelas
hapus($id) → tolak jika masih mengampu kelas dengan Jurnal Harian aktif bulan berjalan
```

#### Kondisi Gagal

| Kondisi | Pesan |
|---------|-------|
| `kelompok_id` di luar Desa milik PJP Desa | "Kelompok tidak berada di Desa Anda" |

### Aturan Bisnis
- Satu Pendidik bisa mengampu lebih dari satu kelas (SRS §6.3).
- PJP Desa, Admin Daerah, & Bidang Tendik memilih Kelompok tujuan secara eksplisit sebelum daftar `kelas_ids` terisi — mencegah salah tempatkan Pendidik ke Kelompok yang tidak dimaksud.
- Bidang Tendik memiliki scope identik Admin Daerah untuk modul ini (Daerah-wide, PRD §6: "Kelola data & distribusi pendidik se-Daerah") — tidak dibatasi ke satu Desa/Kelompok tertentu.

---

## UC-07 — Kelola Akun Internal

### Deskripsi
Admin Daerah atau PJP Kelompok membuat/mengelola akun internal — sejak revisi ini mencakup seluruh jabatan struktur organisasi (Struktur-Organisasi-dan-Role.md §Matriks), bukan hanya Sekretaris KBM/Guru — dengan provisioning password generik.

### Aktor
- **Utama:** Admin Daerah (semua role); PJP Kelompok (`sekretaris-kbm`, `guru`, `wanbin-kelompok`, `bk-kbm` di kelompoknya)

### Prasyarat
- Permission `users.manage` `[RBAC]`

### Pasca-kondisi (Sukses)
- Akun `users` baru dengan password acak 8 karakter, `must_change_password = true`
- Dicatat `[AUDIT]`

---

### Kontrak **[LIVEWIRE]**

**Component:** `Admin\KelolaPengguna`

**Properties:**
```
nama          : string [WAJIB]
username      : string [WAJIB] — unik
role          : enum [WAJIB] — admin-daerah|pjp-desa|pjp-kelompok|sekretaris-kbm|guru|wanbin-daerah|wanbin-desa|wanbin-kelompok|bidang-kurikulum|bidang-tendik|sekretaris-ppg|sekretaris-desa|bk-kbm|pakar-pendidik
kelompok_id   : integer [WAJIB kecuali role ∈ {admin-daerah, pjp-desa, wanbin-daerah, wanbin-desa, bidang-kurikulum, bidang-tendik, sekretaris-ppg, sekretaris-desa, pakar-pendidik}]
desa_id       : integer [WAJIB jika role ∈ {pjp-desa, wanbin-desa, sekretaris-desa}]
```

**Actions:**
```
simpan()
  → validasi: PJP Kelompok hanya boleh assign role sekretaris-kbm/guru/wanbin-kelompok/bk-kbm, di kelompoknya sendiri
  → generate password acak 8 karakter
  → simpan users + assignRole (spatie/laravel-permission)
  → tampilkan password ke pembuat akun untuk disampaikan manual
  → catat activity_log [AUDIT]

toggleActive($userId) → aktifkan/nonaktifkan akun
```

#### Kondisi Gagal

| Kondisi | Pesan |
|---------|-------|
| Username sudah dipakai | "Username sudah digunakan" |
| PJP Kelompok mencoba assign role setingkat/lebih tinggi | "Anda tidak berwenang membuat akun dengan role ini" |

### Aturan Bisnis
- Sama seperti UC-05 (Generus), password awal **selalu** string acak 8 karakter dengan wajib ganti di login pertama (SRS §5.2, konsisten PRD §9.18/§11.1).
- **Role baru hasil ekspansi model per-jabatan** (`wanbin-*`, `bidang-kurikulum`, `bidang-tendik`, `sekretaris-ppg`, `sekretaris-desa`, `bk-kbm`, `pakar-pendidik`) boleh punya lebih dari satu akun aktif per lokasi — berbeda dari `pjp-desa`/`pjp-kelompok` yang tetap dimaksudkan sebagai Koordinator tunggal (Catatan Implementasi #1, Struktur-Organisasi-dan-Role.md); validasi keunikan `pjp-desa`/`pjp-kelompok` itu sendiri **sudah** diimplementasikan (`KelolaPengguna::koordinatorDuplikat()`, lihat SRS §5.2).
- `pakar-pendidik` tidak terikat `kelompok_id`/`desa_id` — scope-nya lintas-tingkat (LUPG) sesuai Struktur-Organisasi-dan-Role.md §D, konsisten dengan cara `generus.view`-nya di-scope di UC-05.
- Role baru Fase 2 (`bidang-sarpras-daerah`, `pembantu-umum-kbm`, `seksi-kbm-reguler-desa` — lihat UCIC-Fase-2 UC-33/UC-36) **belum** ditambahkan ke enum `role` di atas; akan diperluas saat implementasi Fase 2 dimulai, di luar ruang lingkup dokumen Fase 1 ini.

---

## UC-08 — Impor Massal Data Awal

### Deskripsi
Admin Daerah mengimpor data Generus/Pendidik existing (format Excel organisasi) untuk migrasi awal (PRD §12).

### Aktor
- **Utama:** Admin Daerah

### Prasyarat
- Permission `import.run` `[RBAC]`
- File Excel sesuai template yang ditentukan

### Pasca-kondisi (Sukses)
- Baris Generus/Pendidik baru tersimpan; **setiap Generus baru memicu UC-16** (provisioning akun orang tua) satu per satu
- Ringkasan hasil impor ditampilkan (jumlah sukses, jumlah gagal + alasan per baris)

---

### Kontrak **[LIVEWIRE]**

**Component:** `MasterData\ImporMassal`

**Properties:**
```
file        : file [WAJIB] — .xlsx, max 5 MB
tipe_impor  : enum [WAJIB] — GENERUS|PENDIDIK
```

**Actions:**
```
unggah()
  → parse file (PhpSpreadsheet/Laravel-Excel)
  → validasi tiap baris (field wajib sesuai UC-05/UC-06)
  → baris valid: simpan (memicu UC-16 untuk Generus)
  → baris tidak valid: catat di laporan_gagal (tidak menghentikan proses baris lain)
  → tampilkan ringkasan
```

#### Kondisi Gagal

| Kondisi | Perilaku |
|---------|----------|
| Baris tidak lengkap/format salah | Baris tsb dilewati, dicatat di laporan gagal — baris lain tetap diproses |
| File bukan .xlsx atau > 5 MB | Ditolak sebelum parsing, pesan error |

### Aturan Bisnis
- Impor bersifat **tambah** (append), tidak menimpa data existing berdasarkan nama (mencegah duplikasi tidak disengaja harus dicek manual oleh Admin sebelum impor).

---

## UC-09 — Lihat Sensus

### Deskripsi
PJP Kelompok/Admin Daerah melihat rekap sensus generus & pendidik, dihitung otomatis dari Master Data (PRD §9.2).

### Aktor
- **Utama:** PJP Kelompok (kelompoknya), Admin Daerah (semua)

### Prasyarat
- Permission `sensus.view` `[RBAC]`

---

### Kontrak **[LIVEWIRE]**

**Component:** `Sensus\SensusDashboard`

**Properties:**
```
kelompok_id : integer [WAJIB]
periode     : string [OPSIONAL] — default bulan berjalan, format 'YYYY-MM'
```

**Actions:**
```
mount()  → baca sensus_snapshots untuk periode terpilih; jika belum ada snapshot periode ini, hitung on-the-fly dari generus (fallback)
bandingkanPeriode($periodeA, $periodeB) → tampilkan selisih jumlah antar bulan
```

### Aturan Bisnis
- Perhitungan berbasis `kelas_id` (Kelompok tempat Generus berada & mengaji saat ini) — Generus Pendatang terhitung di Kelompok tempat mereka aktif saat ini, sama seperti Generus Setempat (PRD §9.2, SRS §7).
- Snapshot bulanan dijalankan otomatis via Laravel Scheduler tanggal 1 tiap bulan (job terpisah, bukan dipicu tampilan halaman ini).

---

## UC-10 — Kelola Kalender Kurikulum

> **Digantikan pasca-konvergensi** (lihat [Visi-Konvergensi-Kurikulum-Kegiatan-Presensi.md](Visi-Konvergensi-Kurikulum-Kegiatan-Presensi.md), SRS-Fase-1.md v2.0 §8) — component berganti jadi `Kurikulum\KelolaKurikulum` (CRUD rentang tanggal + impor massal template baru), bukan lagi `Kurikulum\ImporKalender` berbasis "10 sheet Bulan × hari". Kontrak di bawah ini dipertahankan sebagai catatan historis desain Fase 1 awal.

### Deskripsi
Admin Daerah atau Bidang Kurikulum mengimpor kalender materi baku (format Excel existing organisasi, per Paket/Kelas) sebagai master kurikulum; Pakar Pendidik melihatnya secara read-only untuk keperluan konsultasi metode pengajaran.

### Aktor
- **Utama:** Admin Daerah, Bidang Kurikulum (`bidang-kurikulum`, baru — kelola penuh, permission identik Admin Daerah untuk modul ini); Pakar Pendidik (`pakar-pendidik`, baru — lihat saja)

### Prasyarat
- Permission `kurikulum.manage` (Admin Daerah, Bidang Kurikulum) atau `kurikulum.view` (Pakar Pendidik, read-only) `[RBAC]`

---

### Kontrak **[LIVEWIRE]**

**Component:** `Kurikulum\ImporKalender`

**Properties:**
```
file     : file [WAJIB] — .xlsx sesuai struktur 10 sheet "Bulan" × hari
jenjang  : string [WAJIB] — kode dari tabel master `jenjang` (mis. `PAUD_A`); kolom `kurikulum_kalender.jenjang` tetap string, bukan FK
```

**Actions:**
```
unggah()
  → parse tiap sheet "Bulan", tiap baris hari → item_materi (JSON array)
  → simpan/replace kurikulum_kalender untuk jenjang tsb
```

**Component turunan (read-only):** `Kurikulum\LihatKalender` — dipakai Pakar Pendidik, menampilkan `kurikulum_kalender` per jenjang tanpa aksi `unggah()`.

#### Kondisi Gagal

| Kondisi | Pesan |
|---------|-------|
| Struktur sheet tidak sesuai template | "Format file tidak sesuai template kalender kurikulum" |

### Aturan Bisnis
- Impor ulang untuk jenjang yang sama **menggantikan** (replace) data kalender jenjang tsb, bukan menambah duplikat.
- Bidang Kurikulum memiliki permission identik Admin Daerah untuk modul ini (`kurikulum.manage` + `kurikulum.view`, PRD §6) — kurikulum bersifat satu set data untuk seluruh Daerah (bukan per Kelompok/Desa), jadi tidak ada pembatasan scope tambahan di antara keduanya.
- Pakar Pendidik hanya bisa membuka `LihatKalender` (tanpa `unggah()`) — akses konsultatif read-only sesuai peran LUPG-nya (Struktur-Organisasi-dan-Role.md §D: "memberi masukan metode pengajaran kepada pengurus").

---

## UC-11 — Konfirmasi Realisasi Materi Harian

> **Digantikan pasca-konvergensi** — realisasi materi kini diisi bersamaan dengan presensi lewat `Kegiatan\InputPresensiKegiatan` (field `realisasi_status`/`realisasi_catatan` pada `kegiatan`), bukan component `KonfirmasiRealisasiMateri` embedded di Jurnal Harian (yang sudah dihapus). Kontrak di bawah ini dipertahankan sebagai catatan historis.

### Deskripsi
Guru mengonfirmasi realisasi materi terjadwal hari itu sebagai bagian Jurnal Harian (PRD §9.3).

### Aktor
- **Utama:** Guru (kelas yang diampu)

### Prasyarat
- Kalender kurikulum untuk jenjang kelas tsb sudah ada (UC-10)

### Pasca-kondisi (Sukses)
- Field `realisasi_status`/`realisasi_catatan` pada baris Jurnal Harian (UC-13) hari itu terisi

---

### Kontrak **[LIVEWIRE]**

**Component:** `Presensi\KonfirmasiRealisasiMateri` (embedded di dalam `InputJurnalHarian`, UC-13)

**Properties:**
```
materi_terjadwal  : array (read-only, dari kurikulum_kalender sesuai jenjang & hari_ke)
realisasi_status  : enum [WAJIB] — SESUAI_JADWAL|TIDAK_TERLAKSANA|PENGGANTI
realisasi_catatan : string [WAJIB jika TIDAK_TERLAKSANA atau PENGGANTI]
```

**Actions:**
```
pilihStatus($status) → set realisasi_status, tampilkan field catatan bila perlu
```

### Aturan Bisnis
- Bagian tak terpisahkan dari UC-13 (satu baris Jurnal Harian per kelas per hari) — bukan tabel/entri terpisah.

---

## UC-12 — Input Presensi Harian

> **Digantikan pasca-konvergensi** — presensi KBM reguler kini dicatat lewat `Kegiatan\InputPresensiKegiatan` atas kejadian `Kegiatan` ber-`kurikulum_kalender_id` (Guru pengajar Kelas tsb dapat carve-out otorisasi khusus), bukan `Presensi\InputPresensi` + tabel `presensi` (sudah dihapus). Kontrak di bawah ini dipertahankan sebagai catatan historis.

### Deskripsi
Guru atau Sekretaris KBM mencatat kehadiran seluruh Generus di satu kelas untuk satu pertemuan.

### Aktor
- **Utama:** Guru (kelas yang diampu); Sekretaris KBM (semua kelas di kelompoknya)

### Prasyarat
- Permission `presensi.manage` `[RBAC]`
- Daftar Generus di kelas tsb tersedia (online: dari DB; offline: dari cache IndexedDB, lihat SRS §13.1)

### Pasca-kondisi (Sukses)
- Satu baris `presensi` per (kelas_id, generus_id, tanggal); bila status = ALPHA → **memicu UC-18** (notifikasi orang tua)
- Dicatat `[AUDIT]`

---

### Kontrak **[LIVEWIRE]**

**Component:** `Presensi\InputPresensi`

**Properties:**
```
kelas_id   : integer [WAJIB]
tanggal    : date [WAJIB] — default hari ini
daftar     : array<{generus_id, status}> — status ∈ HADIR|IZIN|SAKIT|ALPHA, default HADIR untuk semua
```

**Actions:**
```
simpan()
  → untuk tiap baris: UPSERT presensi berdasar (kelas_id, generus_id, tanggal)
  → jika client_uuid belum ada (entri baru dari sesi online): generate UUID v4 di sisi klien sebelum kirim
    (menjaga konsistensi kunci idempotent yang sama dipakai jalur offline, SRS §13.2)
  → status = ALPHA pada baris manapun → dispatch job UC-18 untuk generus tsb
  → catat activity_log [AUDIT]
```

#### Kondisi Gagal

| Kondisi | Pesan |
|---------|-------|
| Tanggal di masa depan | "Tidak dapat mencatat presensi untuk tanggal yang akan datang" |
| Kelas di luar scope user | "Anda tidak berwenang mengisi presensi kelas ini" |

### Aturan Bisnis
- Presensi yang diinput ulang untuk (kelas_id, generus_id, tanggal) yang sama **meng-update** baris existing, bukan membuat baris baru (idempotent by design, selaras dengan mekanisme sync UC-14).

---

## UC-13 — Input & Approval Jurnal Harian Mengajar

> **Digantikan pasca-konvergensi** — realisasi materi melekat ke `Kegiatan` (§UC-11 baru), tanpa approval terpisah oleh PJP Kelompok (PJP Kelompok tetap bisa mencatat/mengoreksi langsung karena juga berwenang atas presensi Kegiatan Kelompok). Tabel `jurnal_harian` sudah dihapus. Kontrak di bawah ini dipertahankan sebagai catatan historis.

### Deskripsi
Guru mencatat satu sesi mengajar per hari (gabungan realisasi materi, catatan); PJP Kelompok opsional memberi approval sebagai pengganti tanda tangan kertas.

### Aktor
- **Utama:** Guru (input); PJP Kelompok (approval, opsional)

### Prasyarat
- Permission `jurnal.manage` `[RBAC]` (Guru); `jurnal.approve` `[RBAC]` (PJP Kelompok)

### Pasca-kondisi (Sukses — Input)
- Satu baris `jurnal_harian` per (kelas_id, tanggal)

### Pasca-kondisi (Sukses — Approval)
- `disetujui_oleh`/`disetujui_pada` terisi

---

### Kontrak **[LIVEWIRE]**

**Component:** `Presensi\InputJurnalHarian`

**Properties:**
```
kelas_id          : integer [WAJIB]
tanggal           : date [WAJIB]
realisasi_status  : enum [WAJIB] — lihat UC-11
realisasi_catatan : string [OPSIONAL/WAJIB kondisional — lihat UC-11]
catatan_guru      : string [OPSIONAL]
```

**Actions:**
```
simpan()   → UPSERT jurnal_harian berdasar (kelas_id, tanggal); generate client_uuid bila belum ada
setujui()  → (PJP Kelompok saja) set disetujui_oleh & disetujui_pada
```

#### Kondisi Gagal

| Kondisi | Pesan |
|---------|-------|
| realisasi_status = TIDAK_TERLAKSANA/PENGGANTI tanpa catatan | "Catatan wajib diisi untuk status ini" |
| Approval oleh selain PJP Kelompok | "Anda tidak berwenang menyetujui jurnal ini" |

### Aturan Bisnis
- Approval **tidak** memblokir input Guru — bersifat jejak tinjauan tambahan, bukan gerbang wajib (PRD §9.5).

---

## UC-14 — Sinkronisasi Offline (Presensi & Jurnal)

> **Digantikan pasca-konvergensi** — endpoint sync kini menyasar `Kegiatan`/`KegiatanPeserta` (`POST /api/v1/sync/presensi`, `POST /api/v1/sync/realisasi-kegiatan`), bukan `presensi`/`jurnal-harian` yang sudah dihapus. Mekanisme idempotensi (`client_uuid`) & resolusi konflik per-field tidak berubah — lihat SRS-Fase-1.md v2.0 §13. Kontrak di bawah ini dipertahankan sebagai catatan historis.

### Deskripsi
Perangkat klien mengirim seluruh draft Presensi/Jurnal Harian yang dibuat saat offline ke server, begitu koneksi tersedia kembali. Satu-satunya use case yang benar-benar memakai kontrak JSON murni (SRS §2.1, §9.19 PRD).

### Aktor
- **Utama:** Sistem klien (service worker, otomatis atas nama Guru/Sekretaris yang sedang login)

### Prasyarat
- Token Sanctum valid (diterbitkan saat login UC-01, disimpan di IndexedDB)
- Ada draft tersimpan di IndexedDB dengan `client_uuid` masing-masing

### Pasca-kondisi (Sukses)
- Setiap draft ter-UPSERT di server berdasar `client_uuid` — **idempotent**, retry tidak menghasilkan duplikat
- Draft yang berhasil sync dihapus dari antrian IndexedDB lokal
- Konflik (SRS §13.3) terselesaikan otomatis (last-write-wins per field) + notifikasi ke pihak yang "kalah"

---

### Kontrak **[JSON]**

**Endpoint:** `GET /api/v1/sync/bootstrap?kelasId=<id>`
**Auth:** Bearer Sanctum Token `[WAJIB]`
**Tujuan:** Dipanggil saat online, untuk mengisi cache IndexedDB (SRS §13.1) sebelum offline.

#### Response Sukses → 200
```json
{
  "generus": [
    { "id": "integer", "nama": "string", "status_domisili": "SETEMPAT|PENDATANG" }
  ],
  "kalender_materi": [
    { "hari_ke": "integer", "item_materi": ["string", "..."] }
  ]
}
```

---

**Endpoint:** `POST /api/v1/sync/presensi`
**Auth:** Bearer Sanctum Token `[WAJIB]`

#### Request Body
```json
{
  "entries": [
    {
      "client_uuid": "string [WAJIB] — UUID v4",
      "kelas_id": "integer [WAJIB]",
      "generus_id": "integer [WAJIB]",
      "tanggal": "string [WAJIB] — YYYY-MM-DD",
      "status": "string [WAJIB] — HADIR|IZIN|SAKIT|ALPHA",
      "updated_at": "string [WAJIB] — ISO 8601, timestamp lokal saat entri dibuat/diubah di klien"
    }
  ]
}
```

#### Response Sukses → 200
```json
{
  "synced": ["client_uuid, ..."],
  "conflicts": [
    {
      "client_uuid": "string",
      "resolved_field_winner": "string — updated_at pemenang",
      "message": "string — untuk ditampilkan sebagai notifikasi in-app ke pihak yang kalah"
    }
  ]
}
```

#### Response Gagal

| HTTP Status | Kondisi | Error Code |
|-------------|---------|------------|
| 401 | Token tidak valid/kedaluwarsa | `UNAUTHENTICATED` |
| 422 | Field wajib kosong pada salah satu entri | `VALIDATION_ERROR` |

---

**Endpoint:** `POST /api/v1/sync/jurnal-harian`
**Auth:** Bearer Sanctum Token `[WAJIB]`
**Request/Response:** Struktur sama seperti `/sync/presensi`, field menyesuaikan skema `jurnal_harian` (SRS §17.4).

### Aturan Bisnis
- `client_uuid` adalah **unique constraint** di tabel `presensi`/`jurnal_harian` (SRS §17.4) — kunci UPSERT.
- Resolusi konflik: **last-write-wins per field** berdasar `updated_at` yang dikirim klien, bukan per baris utuh (SRS §13.3).
- Endpoint ini **tidak** dipakai untuk entri yang dibuat saat online (UC-12/UC-13 menyimpan langsung via Livewire) — hanya untuk batch sync draft offline.

---

## UC-15 — Kelola Musyawaroh & Notulen

### Deskripsi
Mencatat notulen musyawaroh berjenjang: tingkat Kelompok (Pengurus KBM / 5 Unsur / Pertemuan 5 Unsur / Mustin LUPG, sudah ada sejak versi awal dokumen ini), **diperluas** ke tingkat Desa (Musyawarah PPD–PPK, bulanan) dan tingkat Daerah (Musyawarah PPG–PJP Desa, tiap 3 bulan) — mengikuti pola cakupan bertingkat yang sama dengan Kegiatan (UC-21) — sebagai bagian dari ekspansi model peran per jabatan (Struktur-Organisasi-dan-Role.md §Matriks). Tanpa mekanisme carry-over otomatis (fitur Fase 2, lihat UCIC-Fase-2 UC-35).

### Aktor
- **Tingkat Kelompok:** Sekretaris KBM (input); PJP Kelompok, Wanbin Kelompok (`wanbin-kelompok`, baru — memimpin & meninjau, khususnya Mustin LUPG)
- **Tingkat Desa:** Sekretaris Desa (`sekretaris-desa`, baru — input; permission `musyawaroh.manage` sebagian aktif Fase 1, lihat Aturan Bisnis); PJP Desa, Wanbin Desa (`wanbin-desa`, baru — memimpin & meninjau)
- **Tingkat Daerah:** Sekretaris PPG (`sekretaris-ppg`, baru — input; permission `musyawaroh.manage` sebagian aktif Fase 1); Wanbin Daerah (`wanbin-daerah`, baru — memimpin & **mengesahkan**, kewenangan riil Kyai, lihat Aturan Bisnis)

### Prasyarat
- Permission `musyawaroh.manage` `[RBAC]` — scope ditentukan oleh `kelompok_id`/`desa_id` milik user (Kelompok untuk Sekretaris KBM/PJP Kelompok/Wanbin Kelompok; Desa untuk Sekretaris Desa/PJP Desa/Wanbin Desa; Daerah otomatis untuk Sekretaris PPG/Wanbin Daerah/Admin Daerah)

### Pasca-kondisi (Sukses)
- Baris `musyawaroh` (kini membawa `tingkat` & `penyelenggara_id` sesuai scope pencatat, pola sama dengan `kegiatan` UC-21) + satu atau lebih `musyawaroh_item`
- Khusus tingkat DAERAH: bila disahkan Wanbin Daerah, `disahkan_oleh`/`disahkan_pada` terisi

---

### Kontrak **[LIVEWIRE]**

**Component:** `Musyawaroh\KelolaMusyawaroh`

**Properties:**
```
tingkat        : enum [WAJIB, terkunci sesuai role pencatat] — KELOMPOK|DESA|DAERAH
jenis          : enum [WAJIB, opsi terbatas sesuai tingkat — lihat Aturan Bisnis]
                   — tingkat KELOMPOK: PENGURUS_KBM|LIMA_UNSUR|PERTEMUAN_LIMA_UNSUR|MUSTIN_LUPG
                   — tingkat DESA (baru): MUSYAWARAH_PPD_PPK
                   — tingkat DAERAH (baru): MUSYAWARAH_PPG_PPD
tanggal        : date [WAJIB]
jumlah_hadir   : integer [OPSIONAL] — khusus MUSTIN_LUPG (Absensi Mustin, PRD §9.14)
items          : array<{pokok_masalah, keputusan, pic, keterangan}>
disahkan_oleh  : integer, nullable (read-only) — khusus tingkat DAERAH, terisi setelah Wanbin Daerah mengesahkan
disahkan_pada  : datetime, nullable (read-only)
```

**Actions:**
```
tambahItem()          → tambah baris kosong ke items[]
simpan()
  → set tingkat & penyelenggara_id sesuai scope user login (tidak bisa dipilih bebas, sama pola UC-21)
  → simpan musyawaroh + seluruh musyawaroh_item
hapusItem($index)      → hapus satu baris item sebelum disimpan

sahkan($musyawarohId)  → khusus Wanbin Daerah, tingkat=DAERAH
  → set disahkan_oleh = user aktif, disahkan_pada = sekarang
  → catat activity_log [AUDIT]
```

#### Kondisi Gagal

| Kondisi | Pesan |
|---------|-------|
| `jenis` tidak sesuai daftar yang diizinkan untuk `tingkat` terpilih | "Jenis musyawarah ini tidak berlaku untuk tingkat yang dipilih" |
| `sahkan()` dipanggil oleh selain Wanbin Daerah, atau pada musyawaroh bukan tingkat DAERAH | "Anda tidak berwenang mengesahkan notulen ini" |

### Aturan Bisnis
- **Tidak ada carry-over otomatis di Fase 1** (SRS §11) — item bulan lalu tidak muncul otomatis di form bulan ini; disalin manual bila perlu, sama seperti proses lama, hanya kini tersimpan digital.
- Field `tingkat` terkunci sesuai role pencatat, pola identik `kegiatan` (UC-21): Sekretaris KBM/PJP Kelompok/Wanbin Kelompok → KELOMPOK; Sekretaris Desa/PJP Desa/Wanbin Desa → DESA; Sekretaris PPG/Wanbin Daerah/Admin Daerah → DAERAH.
- **Sekretaris Desa dan Sekretaris PPG di Fase 1 hanya mendapat permission `musyawaroh.manage`** (Struktur-Organisasi-dan-Role.md §Matriks, ditandai "sebagian") — permission tambahan mereka (`generus.view`/`pendidik.view`) belum aktif sampai Fase 3, tidak dibahas di dokumen ini.
- **Pengesahan (`sahkan()`) khusus Wanbin Daerah, tingkat DAERAH** — merefleksikan kewenangan riil Kyai ("berwenang mengeluarkan keputusan ... memimpin Musyawarah 5 Unsur", Struktur-Organisasi-dan-Role.md §D) yang lebih formal dibanding Wanbin Desa/Kelompok yang sekadar "memimpin & meninjau". **Tidak memblokir** pencatatan notulen — Sekretaris PPG tetap bisa `simpan()` notulen tanpa menunggu `sahkan()`, pola sama dengan approval non-blocking di UC-13 (Jurnal Harian).
- Wanbin Kelompok memimpin `jenis=MUSTIN_LUPG` (sudah ada sejak versi awal dokumen ini) berdampingan dengan PJP Kelompok — keduanya "memimpin & meninjau", tidak ada hierarki baru di antara keduanya untuk musyawarah ini.
- Data ini menjadi bahan Ekspor Cetak (UC-20), untuk seluruh tingkat.

---

## UC-16 — Provisioning Akun Portal Orang Tua (Otomatis)

### Deskripsi
Sistem otomatis membuat atau menautkan akun Portal Orang Tua setiap kali Generus baru disimpan (dipicu dari UC-05 atau UC-08). Tidak ada UI terpisah — ini proses latar belakang.

### Aktor
- **Utama:** Sistem (dipicu UC-05/UC-08)

### Prasyarat
- Generus baru berhasil disimpan dengan `nomor_hp_orang_tua` terisi

### Pasca-kondisi (Sukses — Nomor Baru)
- Baris baru di `akun_orang_tua` (password acak 8 karakter, `must_change_password = true`)
- Baris baru di `akun_orang_tua_generus` (tautan)

### Pasca-kondisi (Sukses — Nomor Sudah Ada)
- **Tidak ada akun/password baru** — hanya baris baru di `akun_orang_tua_generus`

---

### Kontrak **[LIVEWIRE internal — dipanggil dari listener event `GenerusDisimpan`]**

```
handle(GenerusDisimpan $event)
  → cari akun_orang_tua WHERE nomor_hp = $event->generus->nomor_hp_orang_tua
  → jika TIDAK ditemukan:
      buat akun_orang_tua baru (nomor_hp, password=acak 8 karakter, must_change_password=true)
      tautkan ke generus via akun_orang_tua_generus
  → jika ditemukan:
      tautkan langsung ke akun existing via akun_orang_tua_generus (tanpa buat akun/password baru)
  → catat activity_log [AUDIT] (subject: generus, causer: system)
```

### Aturan Bisnis
- Ini adalah implementasi persis PRD §9.18 — deteksi duplikasi nomor HP untuk mendukung kasus kakak-adik (satu akun, banyak anak) tanpa membuat kredensial ganda.
- Bila satu Generus perlu ditautkan ke wali kedua (nomor HP berbeda), Sekretaris menambahkan nomor kedua di UC-05 — memicu event yang sama, independen dari akun pertama.

---

## UC-17 — Lihat Data Anak (Portal Orang Tua)

### Deskripsi
Orang Tua melihat presensi & jurnal materi harian anaknya; bila akun tertaut >1 Generus, tersedia pemilih anak.

### Aktor
- **Utama:** Orang Tua (guard `orangtua`)

### Prasyarat
- Sesi aktif guard `orangtua` (UC-01), `must_change_password = false` (UC-02)

---

### Kontrak **[LIVEWIRE]**

**Component:** `PortalOrangTua\Dashboard`

**Properties:**
```
generus_terpilih_id : integer — default: satu-satunya anak jika hanya 1, atau anak pertama bila >1
daftar_anak         : array (read-only) — seluruh Generus tertaut ke akun ini (nama, kelas, kelompok)
```

**Actions:**
```
pilihAnak($generusId) → set generus_terpilih_id, refresh tampilan presensi/jurnal
```

**Component turunan:** `PortalOrangTua\LihatPresensi`, `PortalOrangTua\LihatJurnal` — menampilkan data untuk `generus_terpilih_id`, real-time dari tabel `presensi`/`jurnal_harian`.

### Aturan Bisnis
- Data yang ditampilkan **dibatasi hanya** ke Generus yang tertaut ke akun yang login (query di-scope via `akun_orang_tua_generus`, tidak pernah oleh input bebas ID) — PRD §9.18.
- Tidak ada akses ke data konseling/rekam kasus (modul BK di luar ruang lingkup Fase 1 — SRS §2.2).

---

## UC-18 — Notifikasi Alpha

### Deskripsi
Orang Tua menerima notifikasi in-app saat anaknya tercatat Alpha (dari UC-12 online atau UC-14 sync offline).

### Aktor
- **Utama:** Sistem (pemicu); Orang Tua (penerima)

### Prasyarat
- Presensi tersimpan dengan `status = ALPHA`
- Generus tsb tertaut ke minimal satu `akun_orang_tua`

### Pasca-kondisi (Sukses)
- Baris baru di `notifikasi_orang_tua` untuk setiap akun yang tertaut ke Generus tsb

---

### Kontrak **[LIVEWIRE internal — job queued, dipicu dari UC-12/UC-14]**

```
handle(PresensiAlphaDicatat $event)
  → cari seluruh akun_orang_tua tertaut ke $event->generus_id (via akun_orang_tua_generus)
  → untuk tiap akun: insert notifikasi_orang_tua (tipe=ALPHA, pesan="{nama} tidak hadir pada {tanggal}")
```

**Component tampilan:** `PortalOrangTua\NotifikasiFeed`

**Properties:**
```
notifikasi : array (read-only) — daftar notifikasi milik akun yang login, terbaru dulu
```

**Actions:**
```
tandaiDibaca($notifikasiId) → set dibaca_pada
```

### Aturan Bisnis
- Notifikasi in-app adalah **jalur utama** (gratis, tidak bergantung pihak ketiga) — WhatsApp bersifat tambahan opsional dan **tidak** dibangun di Fase 1 (PRD §16, §17.3; SRS §1.1).
- Bila akun tertaut ke >1 anak, notifikasi dari semua anak digabung dalam satu feed dengan nama anak ditandai per baris (PRD §9.18).

---

## UC-19 — Dashboard Kelompok

### Deskripsi
PJP Kelompok/Wanbin Kelompok/Admin Daerah melihat ringkasan kondisi Kelompok: kehadiran, sensus, status musyawaroh, reminder presensi belum diisi; Wanbin Desa melihat halaman yang sama untuk Kelompok mana pun di desanya (pemilih dibatasi ke desanya, bukan seluruh Daerah).

### Aktor
- **Utama:** PJP Kelompok, Wanbin Kelompok (`wanbin-kelompok`, baru — kelompoknya); Wanbin Desa (`wanbin-desa`, baru — Kelompok mana pun dalam desanya, dengan pemilih kelompok); Admin Daerah (semua, dengan pemilih kelompok)

### Prasyarat
- Permission `dashboard.view` `[RBAC]`

---

### Kontrak **[LIVEWIRE]**

**Component:** `Dashboard\DashboardKelompok`

**Properties:**
```
kelompok_id : integer [WAJIB]
```

**Actions:**
```
mount()
  → hitung ringkasan kehadiran bulan berjalan (agregat presensi)
  → baca sensus_snapshots bulan berjalan
  → cek apakah sudah ada musyawaroh bulan ini (per jenis)
  → hitung daftar kelas yang belum ada presensi hari ini (reminder)
```

### Aturan Bisnis
- Tidak ada widget Sarpras (modul sumbernya masih Fase 2) — sesuai SRS §14. Widget Kegiatan mendatang sudah tersedia sejak Fase 1 (SRS §14, §18).
- PJP Desa **tidak** punya akses ke halaman ini sama sekali di Fase 1 (bukan sekadar "tanpa agregasi") — role ini sengaja tidak diberi permission `dashboard.view` (SRS §1.1, §5.1), landing page-nya UC-04 (Kelola Struktur Organisasi) sebagai gantinya. Dashboard Desa sungguhan (agregasi lintas-Kelompok) menyusul Fase 2 sebagai use case terpisah — UCIC-Fase-2 UC-36, permission `dashboard-desa.view`.
- **Wanbin Desa, meski berada di tingkat Desa, sengaja diberi `dashboard.view`** (berbeda dari PJP Desa) — Struktur-Organisasi-dan-Role.md §B mencantumkannya sebagai permission "ada" untuk `wanbin-desa` sejak Fase 1. Perannya memimpin musyawarah lintas Kelompok (UC-15) memerlukan visibilitas kondisi tiap Kelompok satu per satu lewat pemilih kelompok — bukan agregasi Desa (itu tetap UC-36, khusus PJP Desa/Seksi KBM Reguler Desa, Fase 2).

---

## UC-20 — Ekspor Cetak (Print-CSS)

### Deskripsi
User internal mencetak/menyimpan sebagai PDF halaman rekap (Presensi bulanan, Sensus, Notulen Musyawaroh) memakai print stylesheet browser, tanpa proses generate PDF di server (PRD §18.2).

### Aktor
- **Utama:** Semua role internal (sesuai halaman yang bisa diakses)

### Prasyarat
- Sedang membuka salah satu halaman rekap yang mendukung cetak

### Pasca-kondisi (Sukses)
- Tidak ada perubahan data — murni aksi tampilan/cetak sisi klien

---

### Kontrak **[LIVEWIRE, client-side]**

**Tidak ada Livewire action atau endpoint backend** — tombol "Cetak / Simpan sebagai PDF" memanggil `window.print()` JavaScript bawaan browser pada halaman yang sudah memiliki CSS `@media print` (SRS §15).

```html
<button onclick="window.print()">Cetak / Simpan sebagai PDF</button>
```

**Halaman yang mendukung print-CSS di Fase 1:** Rekap Presensi Bulanan (UC-12), Sensus (UC-09), Notulen Musyawaroh (UC-15), Rekap Kegiatan & Program Monitoring (UC-25).

### Aturan Bisnis
- `@page { size: landscape; }` + `page-break-after: always` per bagian, memastikan hasil cetak rapi per halaman (PRD §11.8).
- Tidak ada fallback dompdf di Fase 1 — dibangun hanya bila kebutuhan PDF-tergenerate-otomatis di server benar-benar muncul (PRD §18.2), belum relevan untuk rekap sederhana Fase 1.

---

## UC-21 — Kelola Kegiatan

### Deskripsi
PJP Kelompok, PJP Desa, atau Admin Daerah membuat & mengelola Kegiatan tambahan/penguatan/program khusus pada tingkat yang sesuai dengan perannya masing-masing (SRS §18.1).

### Aktor
- **Utama:** PJP Kelompok (tingkat Kelompok); PJP Desa (tingkat Desa); Admin Daerah (tingkat Daerah)

### Prasyarat
- Permission `kegiatan.manage` `[RBAC]`

### Pasca-kondisi (Sukses)
- Baris baru `kegiatan` tersimpan dengan `tingkat` & `penyelenggara_id` sesuai scope pembuat (bukan input bebas)
- Dicatat `[AUDIT]`

---

### Kontrak **[LIVEWIRE]**

**Component:** `Kegiatan\KelolaKegiatan`

**Properties:**
```
nama     : string [WAJIB]
tingkat  : enum [WAJIB, terkunci sesuai role pembuat] — KELOMPOK|DESA|DAERAH
jenis    : enum [WAJIB] — TAMBAHAN|PENGUATAN|PROGRAM_KHUSUS|EKSTRAKURIKULER
tanggal  : date [WAJIB]
tempat   : string [OPSIONAL]
```

**Actions:**
```
simpan()
  → set penyelenggara_id = kelompok_id/desa_id/daerah_id user yang login, sesuai tingkat (tidak bisa dipilih bebas)
  → validasi tingkat sesuai wewenang role (PJP Kelompok hanya KELOMPOK, PJP Desa hanya DESA, Admin Daerah hanya DAERAH)
  → simpan kegiatan, status default TERJADWAL
  → catat activity_log [AUDIT]

tandaiStatus($kegiatanId, $status)
  → update status → TERLAKSANA | TIDAK_TERLAKSANA
  → catat activity_log [AUDIT]
```

#### Kondisi Gagal

| Kondisi | Pesan |
|---------|-------|
| PJP Kelompok memilih tingkat Desa/Daerah | "Anda hanya dapat membuat Kegiatan tingkat Kelompok" |
| PJP Desa memilih tingkat Daerah | "Anda hanya dapat membuat Kegiatan tingkat Desa" |

### Aturan Bisnis
- Field `tingkat` terkunci sesuai role pembuat — tidak ada pilihan bebas, mencegah mis. PJP Kelompok membuat Kegiatan tingkat Desa (SRS §18.1).
- Use case ini **tidak** menetapkan daftar peserta di muka — untuk Kegiatan tingkat Desa/Daerah, cakupan peserta otomatis mengikuti aturan `tingkat` (SRS §18.1); siapa saja yang benar-benar hadir dicatat langsung oleh Petugas Presensi tiap Kelompok (UC-22/UC-23), bukan pre-registrasi.

---

## UC-22 — Kelola Petugas Presensi Kegiatan

### Deskripsi
PJP Kelompok atau Sekretaris KBM menunjuk satu atau lebih Petugas Presensi dari kelompoknya sendiri untuk sebuah Kegiatan tingkat Desa/Daerah yang diikuti kelompoknya — bisa akun internal existing atau seorang Generus (menerima tautan token, SRS §18.2).

### Aktor
- **Utama:** PJP Kelompok, Sekretaris KBM (kelompoknya sendiri)

### Prasyarat
- Permission `kegiatan-petugas.manage` `[RBAC]`
- Kegiatan target berstatus `tingkat = DESA` atau `DAERAH` (Kegiatan tingkat Kelompok tidak memerlukan penugasan terpisah — lihat Aturan Bisnis)

### Pasca-kondisi (Sukses)
- Baris baru `kegiatan_petugas_presensi`
- Bila petugas = Generus: `token` (UUID v4) di-generate, `token_kedaluwarsa` = `kegiatan.tanggal + 1 hari`
- Dicatat `[AUDIT]`

---

### Kontrak **[LIVEWIRE]**

**Component:** `Kegiatan\KelolaPetugasPresensi`

**Properties:**
```
kegiatan_id  : integer [WAJIB]
tipe_petugas : enum [WAJIB] — AKUN_INTERNAL|GENERUS
user_id      : integer [WAJIB jika tipe_petugas=AKUN_INTERNAL]
generus_id   : integer [WAJIB jika tipe_petugas=GENERUS]
```

**Actions:**
```
tunjuk()
  → validasi kegiatan.tingkat != KELOMPOK
  → validasi user_id/generus_id berada di kelompok_id user yang login
  → simpan kegiatan_petugas_presensi (kelompok_id = kelompok user)
  → jika tipe_petugas=GENERUS: generate token UUID v4, set token_kedaluwarsa
  → tampilkan tautan token sekali untuk disalin/dibagikan manual (WhatsApp/cetak)
  → catat activity_log [AUDIT]

cabut($id)
  → hapus penugasan sebelum Kegiatan berlangsung — token yang sudah dibagikan langsung tidak valid
  → catat activity_log [AUDIT]
```

#### Kondisi Gagal

| Kondisi | Pesan |
|---------|-------|
| Kegiatan bertingkat KELOMPOK | "Kegiatan tingkat Kelompok tidak memerlukan penunjukan Petugas Presensi terpisah" |
| Generus/akun di luar kelompok user | "Anda hanya dapat menunjuk petugas dari kelompok Anda sendiri" |

### Aturan Bisnis
- Satu Kelompok bisa punya lebih dari satu Petugas Presensi untuk Kegiatan yang sama, mis. sebagai cadangan (SRS §18.2).
- Token ditampilkan **sekali** saat dibuat — pola sama seperti password awal Portal Orang Tua/akun internal (UC-07, UC-16), disampaikan manual oleh penunjuk.
- Untuk Kegiatan tingkat `KELOMPOK`, PJP Kelompok/Sekretaris KBM kelompok itu sendiri langsung mencatat presensi via UC-23 tanpa perlu use case ini — setara pola UC-12 (Presensi Harian).

---

## UC-23 — Input Presensi Kegiatan

### Deskripsi
Petugas Presensi (akun internal via sesi `web` biasa, atau Generus via tautan token — SRS §18.2) mencatat kehadiran generus dari kelompoknya sendiri pada satu Kegiatan.

### Aktor
- **Utama:** Petugas Presensi Kegiatan (akun internal atau Generus via token, §2 catatan guard)

### Prasyarat
- Akun internal: permission `kegiatan-presensi.manage` di-scope ke (kegiatan_id, kelompok_id) penugasan (UC-22); atau Kegiatan tingkat KELOMPOK dengan permission `kegiatan.manage` biasa
- Generus: token valid, belum melewati `token_kedaluwarsa` (SRS §18.2)

### Pasca-kondisi (Sukses)
- Satu baris `kegiatan_peserta` per (kegiatan_id, generus_id), terbatas ke generus di `kelompok_id` penugasan petugas
- Dicatat `[AUDIT]` (`causer` = user bila akun internal; ditandai "via token" bila Generus tanpa akun)

---

### Kontrak **[LIVEWIRE]**

**Component:** `Kegiatan\InputPresensiKegiatan`

**Properties:**
```
kegiatan_id : integer [WAJIB]
kelompok_id : integer [WAJIB] — terkunci ke kelompok penugasan petugas (UC-22) atau kelompok sendiri (Kegiatan tingkat KELOMPOK)
daftar      : array<{generus_id, status}> — status ∈ HADIR|IZIN|SAKIT|ALPHA, default HADIR, sumber: seluruh Generus aktif di kelompok_id (lintas kelas/jenjang)
```

**Actions:**
```
simpan()
  → validasi kegiatan_id & kelompok_id sesuai penugasan petugas (UC-22), atau Kegiatan tingkat KELOMPOK = kelompok sendiri
  → untuk tiap baris: UPSERT kegiatan_peserta berdasar (kegiatan_id, generus_id)
  → catat activity_log [AUDIT]
```

#### Kondisi Gagal

| Kondisi | Pesan |
|---------|-------|
| Token kedaluwarsa | "Tautan ini sudah tidak berlaku, hubungi pengurus Kelompok Anda" |
| Kelompok di luar penugasan | "Anda tidak berwenang mencatat presensi untuk kelompok ini" |

### Aturan Bisnis
- UPSERT idempotent, pola sama dengan UC-12 (Presensi Harian) — input ulang untuk generus yang sama meng-update baris existing.
- Akses via token **hanya** membuka komponen ini dalam mode terbatas — tidak ada navigasi ke halaman lain (SRS §18.2).

---

## UC-24 — Kelola Program Monitoring

### Deskripsi
Sekretaris KBM atau PJP Kelompok mencatat & memantau progres program berkelanjutan (Turba ke Rumah GPN, Progres Pernikahan, GOMA, GMKM, Gerakan Tertib Sholat 5 Waktu, dsb.) secara generik, tanpa struktur khusus per nama program (SRS §18.3).

### Aktor
- **Utama:** Sekretaris KBM, PJP Kelompok

### Prasyarat
- Permission `program-monitoring.manage` `[RBAC]`

### Pasca-kondisi (Sukses)
- Baris `program_monitoring` + satu atau lebih `program_monitoring_item` tersimpan
- Dicatat `[AUDIT]`

---

### Kontrak **[LIVEWIRE]**

**Component:** `Kegiatan\KelolaProgramMonitoring`

**Properties:**
```
nama_program   : string [WAJIB] — bebas teks, bukan pilihan tetap
target_peserta : string [OPSIONAL]
tenggat        : date [OPSIONAL]
items          : array<{generus_id?, temuan, pic, status_item, tenggat_item}>
```

**Actions:**
```
simpan()              → create/update program_monitoring + sync items
tambahItem()           → tambah baris kosong ke items[]
hapusItem($index)      → hapus satu baris item sebelum disimpan
updateStatusItem($itemId, $status) → BELUM|PROSES|SELESAI
```

### Aturan Bisnis
- **Tidak ada carry-over otomatis** ke bulan berikutnya untuk item yang belum selesai (SRS §18.3) — sama pola dengan Musyawaroh & Notulen (UC-15).
- `nama_program` bebas teks, bukan enum tetap — mendukung penambahan program lokal Kelompok tanpa perubahan skema (PRD §16 mitigasi risiko).

---

## UC-25 — Rekap Kegiatan & Program Monitoring (Cetak)

### Deskripsi
PJP Kelompok/PJP Desa/Admin Daerah melihat & mencetak rekap kehadiran satu Kegiatan (dikelompokkan per Kelompok peserta) beserta status Program Monitoring kelompoknya.

### Aktor
- **Utama:** PJP Kelompok, PJP Desa, Admin Daerah (sesuai tingkat Kegiatan)

### Prasyarat
- Permission `kegiatan.view` `[RBAC]`

---

### Kontrak **[LIVEWIRE]**

**Component:** `Kegiatan\RekapKegiatan`

**Properties:**
```
kegiatan_id : integer [WAJIB]
```

**Actions:**
```
mount()
  → hitung rekap kegiatan_peserta dikelompokkan per kelompok_id (agregat HADIR/IZIN/SAKIT/ALPHA + % kehadiran per Kelompok)
```

### Aturan Bisnis
- Rekap dihitung on-the-fly dari `kegiatan_peserta`, sama pola dengan Rekap Presensi Harian (UC-12) — tidak perlu tabel agregat terpisah di Fase 1 (SRS §18.1).
- Cetak memakai pola print-CSS yang sama dengan UC-20, tanpa endpoint backend terpisah.

---

## UC-26 — Kelola Matriks Role & Permission

### Deskripsi
Admin Daerah melihat tabel role × permission (siapa punya akses apa) untuk audit hak akses — murni tampilan, tidak ada aksi tulis/edit. Dibangun setelah ditemukan beberapa role kehilangan/kelebihan permission tanpa disadari selama pengembangan Fase 1 (lihat Aturan Bisnis).

### Aktor
- **Utama:** Admin Daerah

### Prasyarat
- Permission `roles.view` `[RBAC]`

---

### Kontrak **[LIVEWIRE]**

**Component:** `Admin\MatriksRolePermission`

**Actions:**
```
mount()
  → baca seluruh Role beserta Permission-nya langsung dari database (spatie/laravel-permission),
    bukan dari konstanta RolePermissionSeeder — otomatis mengikuti role/permission baru
    yang ditambahkan Fase 2+ tanpa perlu update kode halaman ini
```

### Aturan Bisnis
- Read-only sepenuhnya — tidak ada `simpan()`/`toggle()`, perubahan role/permission tetap lewat `RolePermissionSeeder` (kode) seperti sebelumnya, halaman ini murni alat bantu audit/verifikasi.
- Motivasi: selama pengembangan Fase 1 ditemukan beberapa celah role kehilangan permission yang seharusnya dimiliki (mis. `pjp-kelompok` sempat tidak punya `dashboard.view`, wewenang mengelola akun pengguna tidak divalidasi ulang di server) atau kelebihan permission yang tidak sesuai desain (`pjp-desa` sempat diberi `dashboard.view` — lihat UC-01, UC-19). Matriks ini membuat kondisi role saat ini terlihat langsung tanpa perlu `php artisan permission:show` di CLI.

---

## UC-27 — Kelola Catatan Konseling (Rekam Kasus)

### Deskripsi
Bagian BK/Konselor (`bk-kbm`, baru) mencatat catatan konseling/rekam kasus Generus yang ditangani di kelompoknya — fitur sederhana (teks bebas, tanpa struktur kategori kasus) sesuai keputusan Fase 1-2 (Struktur-Organisasi-dan-Role.md §C); PJP Kelompok dan Admin Daerah dapat melihat catatan ini sesuai batas visibilitas PRD §11.1. Ini adalah use case baru — belum ada modul konseling di Fase 1 sebelum revisi ini.

### Aktor
- **Utama:** Bagian BK/Konselor (`bk-kbm`, baru — input, kelompoknya sendiri); PJP Kelompok, Admin Daerah (lihat — dua-duanya sudah termasuk dalam lingkar visibilitas PRD §11.1)

### Prasyarat
- Permission `konseling.manage` `[RBAC]` (`bk-kbm`, baca+tulis) atau `konseling.view` `[RBAC]` (`pjp-kelompok`, baca saja — baru, resolusi eksplisit dari Struktur-Organisasi-dan-Role.md §"Catatan Implementasi #2": `bk-kbm` satu-satunya yang menulis/mengedit, `pjp-kelompok` cuma lihat, `admin-daerah` otomatis dapat semua permission) — scope kelompok milik user
- Akun `bk-kbm` disediakan lewat UC-07 (Kelola Akun Internal, ditambahkan ke enum `role` pada revisi ini)

### Pasca-kondisi (Sukses)
- Baris baru `konseling_catatan` tersimpan (kelompok_id, generus_id, dicatat_oleh)
- Dicatat `[AUDIT]`

---

### Kontrak **[LIVEWIRE]**

**Component:** `Konseling\KelolaKonseling`

**Properties:**
```
generus_id : integer [WAJIB]
tanggal    : date [WAJIB]
catatan    : text [WAJIB] — teks bebas, tanpa kategori/struktur kasus (fitur sederhana Fase 1-2)
status     : enum [OPSIONAL] — BERLANGSUNG|SELESAI, default BERLANGSUNG
```

**Actions:**
```
simpan()
  → validasi generus_id berada di kelompok_id milik bk-kbm yang login
  → simpan konseling_catatan (kelompok_id, generus_id, dicatat_oleh)
  → catat activity_log [AUDIT]
```

**Component turunan (read-only):** `Konseling\LihatKonseling` — dipakai PJP Kelompok (kelompoknya) & Admin Daerah (seluruh Daerah), tanpa aksi tulis.

#### Kondisi Gagal

| Kondisi | Pesan |
|---------|-------|
| Generus di luar kelompok bk-kbm yang login | "Anda hanya dapat mencatat konseling Generus di kelompok Anda" |
| Akses oleh role selain bk-kbm/PJP Kelompok/Admin Daerah | "Anda tidak berwenang melihat catatan ini" |

### Aturan Bisnis
- **Visibilitas dibatasi hanya ke Bagian BK/Konselor (pencatat), PJP Kelompok, dan Admin Daerah** sesuai PRD §11.1 — Guru, Sekretaris KBM, dan role lain di kelompok yang sama **tidak bisa** mengakses halaman/data ini sama sekali; ini adalah *authorization check* di level query/policy, bukan sekadar disembunyikan di UI.
- Fitur sengaja sederhana di Fase 1-2 (catatan teks bebas + status) — kategori kasus terstruktur, riwayat penanganan multi-sesi, dsb. di luar ruang lingkup ini, menyusul bila dibutuhkan di luar roadmap saat ini.
- **Tidak** dapat diakses dari Portal Orang Tua (konsisten UC-17 Aturan Bisnis) — sesuai catatan privasi PRD §11.1.
- **Catatan terbuka untuk diverifikasi:** Struktur-Organisasi-dan-Role.md §C tidak secara eksplisit mencantumkan permission `konseling.*` untuk `pjp-kelompok` (hanya `bk-kbm` yang tercantum), meski PRD §11.1 menyebut PJP Kelompok sebagai salah satu dari 3 pihak yang boleh melihat rekam kasus. Use case ini berasumsi PJP Kelompok mendapat `konseling.manage` di scope kelompoknya (co-manage, bukan sekadar view) — konsisten pola PJP Kelompok yang selalu berwenang penuh atas modul lain di kelompoknya (Presensi, Jurnal, dst). **Perlu dikonfirmasi & ditambahkan eksplisit ke matrix role** (`docs/Struktur-Organisasi-dan-Role.md` §C) saat implementasi.

---

**Riwayat Revisi:**

| Versi | Tanggal | Perubahan |
|-------|---------|-----------|
| 1.0 | 29 Juli 2026 | Dokumen awal UCIC Fase 1, diturunkan dari SRS-Fase-1.md (20 use case: UC-01–UC-20) |
| 1.1 | 30 Juli 2026 | Update enum `jenjang` (UC-04) — Menengah 1-3→Menengah 7-9, Lanjutan 1-6→Lanjutan 10-12; ganti role slug `koordinator-ppd`/`koordinator-ppk`→`pjp-desa`/`pjp-kelompok` dan seluruh sebutan Koordinator PPD/PPK→PJP Desa/PJP Kelompok |
| 1.2 | 30 Juli 2026 | UC-05: hapus properti `kelompok_asal_id` & validasi "Setempat harus di Kelompok Asal"; `kelas_aktif_id`→`kelas_id` (wajib); UC-09: basis perhitungan sensus ganti dari `kelompok_asal_id` ke `kelas_id` (Kelompok tempat Generus berada saat ini) |
| 1.3 | 30 Juli 2026 | UC-05: `naikkanKelas()` kini juga catat `dicatat_oleh` & `activity_log [AUDIT]` (samakan dengan alur ubah status domisili); tambah Pasca-kondisi (Sukses — Kenaikan Kelas) |
| 1.4 | 30 Juli 2026 | **Modul Kegiatan (SRS §18) dipindahkan ke Fase 1** — tambah UC-21 (Kelola Kegiatan), UC-22 (Kelola Petugas Presensi Kegiatan), UC-23 (Input Presensi Kegiatan, termasuk jalur akses token tanpa akun), UC-24 (Kelola Program Monitoring), UC-25 (Rekap Kegiatan & Program Monitoring/Cetak); update §2 Konvensi Dokumen (catatan jalur akses token) dan §20 (halaman print-CSS bertambah) |
| 1.5 | 31 Juli 2026 | Pecah enum `jenjang` (UC-04) — `PAUD`→`PAUD_A`/`PAUD_B` (usia 3-4 th / 4-5 th), pola sama seperti `GPN_A`/`GPN_B` |
| 1.6 | 31 Juli 2026 | Ganti nilai enum `status_domisili` (UC-05, UC-09, UC-14) — `PERANTAUAN`→`PENDATANG`, istilah "Perantauan" jadi "Pendatang" di seluruh dokumen |
| 1.7 | 31 Juli 2026 | **Majukan scope PJP Desa dari Fase 2 ke Fase 1** — permission `generus.manage` & `pendidik.manage` ditambahkan ke role `pjp-desa`, scope dibatasi ke seluruh Kelompok dalam Desa miliknya (bukan agregasi dashboard, yang tetap Fase 2). UC-05 & UC-06: tambah aktor PJP Desa, field `desa_id`/`kelompok_id` di Properties untuk Admin Daerah & PJP Desa memilih Kelompok tujuan secara eksplisit sebelum memilih Kelas |
| 1.8 | 31 Juli 2026 | **Jenjang jadi tabel master data sendiri** — UC-04 (`KelolaKelas`): properti `jenjang: enum` → `jenjang_id: integer` (FK ke tabel `jenjang`); UC-10 (`ImporKalender`): properti `jenjang` tetap string tapi kini merujuk kode dari tabel master, bukan enum hardcode |
| 1.9 | 31 Juli 2026 | **UC-04 rename "Kelola Struktur Wilayah" → "Kelola Struktur Organisasi (Desa/Kelompok/Kelas)"** (selaraskan SRS §4.1); permission `wilayah.manage`/`wilayah.view` → `struktur-organisasi.manage`/`struktur-organisasi.view`; tambah PJP Desa sebagai aktor sekunder (read-only, sebelumnya tidak tercatat meski sudah punya akses di kode) |
| 1.10 | 1 Agustus 2026 | **PJP Desa dipastikan tidak punya akses Dashboard di Fase 1** (UC-01, UC-02, UC-19 Aturan Bisnis) — cabut permission `dashboard.view` yang sempat keliru diberikan; redirect pasca-login/ganti-password kini role-aware (`User::landingRouteName()`), PJP Desa mendarat di UC-04 (Kelola Struktur Organisasi), bukan UC-19. **Tambah UC-26 — Kelola Matriks Role & Permission** (Admin Daerah, read-only, dibaca langsung dari database) — alat bantu audit yang lahir dari insiden di atas. Perbaiki referensi silang di UCIC-Fase-2.md/SRS-Fase-2.md yang keliru menyebut UC-19 sebagai placeholder Fase 1 PJP Desa (seharusnya UC-04); akibatnya UCIC-Fase-2.md me-renumber seluruh use case-nya UC-26–UC-39 → UC-27–UC-40 agar tidak tabrakan dengan UC-26 baru ini (lihat UCIC-Fase-2.md v1.3) |
| 1.11 | 2 Agustus 2026 | **Setiap jabatan struktur organisasi kini punya role/akun sendiri** (PRD v1.9 §6, `Struktur-Organisasi-dan-Role.md` §Matriks) — tambah 9 role baru bertanda Fase 1: `wanbin-daerah` (UC-04 lihat, UC-15 memimpin & mengesahkan Daerah, UC-19 dashboard), `wanbin-desa` (UC-15 memimpin Desa, UC-19 dashboard lintas-Kelompok di desanya), `wanbin-kelompok` (UC-15 memimpin Mustin LUPG, UC-19 dashboard), `bidang-kurikulum` (UC-10, kelola penuh setara Admin Daerah), `bidang-tendik` (UC-06, kelola Daerah-wide), `sekretaris-ppg`/`sekretaris-desa` (UC-15, input notulen Daerah/Desa — permission `musyawaroh.manage` saja, sebagian sesuai matrix), `pakar-pendidik` (UC-05 & UC-10, lihat saja lintas-tingkat), dan **UC-27 — Kelola Catatan Konseling (Rekam Kasus)** baru untuk `bk-kbm` (fitur sederhana, visibilitas dibatasi PRD §11.1). **UC-15 diperluas signifikan** — tambah field `tingkat`/`disahkan_oleh`/`disahkan_pada`, jenis notulen baru `MUSYAWARAH_PPD_PPK` (Desa)/`MUSYAWARAH_PPG_PPD` (Daerah), action `sahkan()` khusus Wanbin Daerah. **UC-07 diperluas** — enum `role` menambahkan 9 slug di atas, PJP Kelompok kini juga bisa assign `wanbin-kelompok`/`bk-kbm`. Perbaiki 2 referensi silang basi ke UCIC-Fase-2 (UC-01, UC-19: "UC-34" → **UC-36**, mengikuti pergeseran nomor v1.3 yang sebelumnya tidak ikut diperbarui di sini). Akibat penambahan UC-27, UCIC-Fase-2.md me-renumber seluruh use case-nya UC-27–UC-40 → UC-28–UC-41 (lihat UCIC-Fase-2.md v1.4), sekaligus menambah 3 role Fase 2 (`bidang-sarpras-daerah`, `pembantu-umum-kbm`, `seksi-kbm-reguler-desa`) sebagai aktor pada use case Sarpras/Dashboard Desa yang sudah ada. Role bertanda Fase 3/4 di matrix (`bendahara-*`, `bidang-oras`, `bidang-kemandirian`, `wali-kelas`, dst.) **tidak** dibahas di revisi ini — di luar ruang lingkup, menunggu dokumen fase terkait |
| 1.12 | 2 Agustus 2026 | **Rekonsiliasi pasca-integrasi role baru** — UC-27 (Kelola Catatan Konseling) Prasyarat diperjelas: `pjp-kelompok` mendapat permission terpisah `konseling.view` (baca saja, baru), berbeda dari `konseling.manage` milik `bk-kbm` (satu-satunya penulis/pengedit) — sebelumnya draf v1.11 hanya menyebut akses "lihat" tanpa nama permission eksplisit; ini menutup celah agar konsisten dengan resolusi `.manage` vs `.view` di `Struktur-Organisasi-dan-Role.md` Catatan Implementasi #2 |
| 1.13 | 4 Agustus 2026 | **Tandai UC-10 s.d. UC-14 digantikan** oleh Konvergensi Kurikulum-Kegiatan-Presensi (lihat [Visi-Konvergensi-Kurikulum-Kegiatan-Presensi.md](Visi-Konvergensi-Kurikulum-Kegiatan-Presensi.md), SRS-Fase-1.md v2.0) — Presensi Harian, Jurnal Harian Mengajar, dan Konfirmasi Realisasi Materi tidak lagi modul/tabel terpisah; digantikan alur presensi+realisasi pada `Kegiatan\InputPresensiKegiatan` untuk kejadian `Kegiatan` ber-`kurikulum_kalender_id` (KBM Reguler), dan `Kurikulum\KelolaKurikulum` menggantikan `Kurikulum\ImporKalender`. Sinkronisasi Offline (UC-14) endpoint-nya ikut berubah target model. Kontrak detail tiap UC dipertahankan sebagai catatan historis desain Fase 1 awal, bukan dihapus — lihat catatan di masing-masing UC. Tidak ada UC baru/renumbering di dokumen ini akibat revisi ini |
| 1.14 | 6 Agustus 2026 | **UC-04: tab Kelas jadi CRUD `rombongan_belajar` (Kelas sungguhan, bisa gabung >1 Jenjang), Jenjang jadi tab tersendiri** (SRS-Fase-1.md v2.1) — kontrak lama `KelolaKelas` (properti `nama`/`jenjang_id` tunggal, tanpa `hapus()`) sudah tidak sesuai kode sejak simplifikasi Kelas jadi auto-provisioned (SRS v1.10/v1.11), diperbaiki sekalian: sekarang `KelolaKelas` mengelola `rombongan_belajar` (properti `jenjangIds` array, actions `simpan()`/`toggleAktif()`/`hapus()` dengan guard Generus), sementara tab Jenjang pakai `KelolaReferensi` generik (permission `referensi.manage` — beda dari `struktur-organisasi.*` tab lain di halaman yang sama, dicatat eksplisit karena PJP Desa/Wanbin Daerah jadi tidak bisa lihat tab ini). **UC-05: form pilih Jenjang langsung, `kelas_id` di-derive** — karena satu Kelas (`rombongan_belajar`) kini bisa menaungi >1 Jenjang, tidak lagi valid jadi basis pemilihan tunggal; properti `kelas_id`→`jenjang_id`, `naikkanKelas($generusId, $kelasBaruId, $semester)`→`naikkanKelas($generusId, $jenjangBaruId, $semester)` (derive `kelas_id` dari Kelompok Generus + Jenjang baru, tetap dalam Kelompok yang sama). Pipeline Kegiatan KBM/materi/presensi (UC-05 Aturan Bisnis, SRS §9/§18) tidak berubah — tetap per-Jenjang lewat tabel `kelas` lama |
