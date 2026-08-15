# Use Case Integration Contract (UCIC)
# SI-PPG — Fase 2: Pelaporan Otomatis & Multi-Kelompok

**Nama Sistem:** SI-PPG — Sistem Informasi Pembinaan Generus
**Versi Dokumen:** 1.5
**Tanggal:** 12 Agustus 2026
**Status:** Draft
**Klasifikasi:** Internal — Terbatas
**Dokumen Sumber:** [SRS-Fase-2.md](SRS-Fase-2.md)
**Dokumen Terkait:** [UCIC-Fase-1.md](UCIC-Fase-1.md), [PRD-Aplikasi-Pendataan-Pelaporan-PPG.md](PRD-Aplikasi-Pendataan-Pelaporan-PPG.md), [Struktur-Organisasi-dan-Role.md](Struktur-Organisasi-dan-Role.md)

> **Catatan Ruang Lingkup:** Melanjutkan penomoran use case dari [UCIC-Fase-1.md](UCIC-Fase-1.md) (berakhir di UC-27, setelah penambahan UC-27 Kelola Catatan Konseling (Rekam Kasus) — perluasan role per jabatan, lihat `Struktur-Organisasi-dan-Role.md`) — dokumen ini dimulai dari **UC-28**, seluruh nomor UC-28–UC-40 versi sebelumnya (v1.3) bergeser +1 menjadi UC-28–UC-41 akibat penambahan tsb (pola sama seperti pergeseran v1.3, lihat Riwayat Revisi). Use case Fase 1 (UC-01–UC-27) tetap berlaku apa adanya kecuali disebut eksplisit sebagai "Perluasan" di bawah.

---

## Daftar Isi

1. [Tujuan Dokumen](#1-tujuan-dokumen)
2. [Konvensi Dokumen](#2-konvensi-dokumen)
3. [Daftar Use Case](#3-daftar-use-case)
4. [UC-28 — Kelola Jadwal Kegiatan Berulang](#uc-28--kelola-jadwal-kegiatan-berulang)
5. [UC-29 — Kelola Penargetan Peserta Kegiatan](#uc-29--kelola-penargetan-peserta-kegiatan)
6. [UC-30 — Generate & Regenerasi Kejadian Kegiatan (Sistem)](#uc-30--generate--regenerasi-kejadian-kegiatan-sistem)
7. [UC-31 — Generator Laporan Bulanan (Kelompok)](#uc-31--generator-laporan-bulanan-kelompok)
8. [UC-32 — Agregasi, Approval & Penelusuran Laporan Berjenjang](#uc-32--agregasi-approval--penelusuran-laporan-berjenjang)
9. [UC-33 — Kelola Sarana & Prasarana](#uc-33--kelola-sarana--prasarana)
10. [UC-34 — Upload & Kelola Dokumentasi Foto Kegiatan](#uc-34--upload--kelola-dokumentasi-foto-kegiatan)
11. [UC-35 — Carry-over Musyawaroh Otomatis](#uc-35--carry-over-musyawaroh-otomatis)
12. [UC-36 — Dashboard Desa (Agregasi)](#uc-36--dashboard-desa-agregasi)
13. [UC-37 — Notifikasi Jadwal Kegiatan (Portal Orang Tua)](#uc-37--notifikasi-jadwal-kegiatan-portal-orang-tua)
14. [UC-38 — Kelola Kalender Hari Libur](#uc-38--kelola-kalender-hari-libur)
15. [UC-39 — Sinkronisasi Otomatis Kegiatan vs Hari Libur (Sistem)](#uc-39--sinkronisasi-otomatis-kegiatan-vs-hari-libur-sistem)
16. [UC-40 — Kelola Program Kegiatan & Rekap Gabungan](#uc-40--kelola-program-kegiatan--rekap-gabungan)
17. [UC-41 — Sinkronisasi Kalender Hari Libur dari Google Calendar (Sistem)](#uc-41--sinkronisasi-kalender-hari-libur-dari-google-calendar-sistem)

---

## 1. Tujuan Dokumen

Dokumen ini mendefinisikan **kontrak integrasi** untuk seluruh use case baru/diperluas di Fase 2, mengikuti format & konvensi yang sama dengan [UCIC-Fase-1.md](UCIC-Fase-1.md) §1. Seluruh kontrak Fase 2 berjenis **[LIVEWIRE]** (SRS-Fase-2 §9) — tidak ada endpoint `[JSON]` baru, mekanisme offline tetap terbatas ke Presensi & Jurnal Harian (SRS-Fase-1 §13).

---

## 2. Konvensi Dokumen

Sama dengan [UCIC-Fase-1.md §2](UCIC-Fase-1.md#2-konvensi-dokumen) — simbol `[WAJIB]`/`[OPSIONAL]`/`[AUDIT]`/`[RBAC]` dipakai identik.

---

## 3. Daftar Use Case

| ID | Nama Use Case | Aktor Utama | Jenis Kontrak |
|----|---------------|-------------|----------------|
| UC-28 | Kelola Jadwal Kegiatan Berulang | PJP Kelompok, PJP Desa, Admin Daerah | LIVEWIRE |
| UC-29 | Kelola Penargetan Peserta Kegiatan | PJP Kelompok, PJP Desa, Admin Daerah, Sekretaris KBM | LIVEWIRE |
| UC-30 | Generate & Regenerasi Kejadian Kegiatan (Sistem) | Sistem (dipicu UC-28) | LIVEWIRE (internal) |
| UC-31 | Generator Laporan Bulanan (Kelompok) | PJP Kelompok | LIVEWIRE |
| UC-32 | Agregasi, Approval & Penelusuran Laporan Berjenjang | PJP Desa, Admin Daerah | LIVEWIRE |
| UC-33 | Kelola Sarana & Prasarana | PJP Kelompok, Pembantu Umum KBM (kelola); PJP Desa, Admin Daerah, Bidang Sarpras Daerah (lihat) | LIVEWIRE |
| UC-34 | Upload & Kelola Dokumentasi Foto Kegiatan | PJP Kelompok, Sekretaris KBM | LIVEWIRE |
| UC-35 | Carry-over Musyawaroh Otomatis | Sistem (dipicu UC-15); Sekretaris KBM (penerima) | LIVEWIRE |
| UC-36 | Dashboard Desa (Agregasi) | PJP Desa, Seksi KBM Reguler Desa | LIVEWIRE |
| UC-37 | Notifikasi Jadwal Kegiatan (Portal Orang Tua) | Sistem (job terjadwal); Orang Tua (penerima) | LIVEWIRE (internal) |
| UC-38 | Kelola Kalender Hari Libur | Admin Daerah | LIVEWIRE |
| UC-39 | Sinkronisasi Otomatis Kegiatan vs Hari Libur (Sistem) | Sistem (dipicu UC-38) | LIVEWIRE (internal) |
| UC-40 | Kelola Program Kegiatan & Rekap Gabungan | PJP Kelompok, PJP Desa, Admin Daerah | LIVEWIRE |
| UC-41 | Sinkronisasi Kalender Hari Libur dari Google Calendar (Sistem) | Sistem (job terjadwal bulanan) | LIVEWIRE (internal) |

---

## UC-28 — Kelola Jadwal Kegiatan Berulang

### Deskripsi
PJP Kelompok/Desa/Admin Daerah mendefinisikan pola Kegiatan **Rutin** (Harian atau Bulanan) yang akan menghasilkan banyak kejadian `kegiatan` sekaligus, menggantikan pembuatan manual satu-satu untuk kegiatan yang polanya sudah pasti berulang (SRS-Fase-2 §2).

### Aktor
- **Utama:** PJP Kelompok (tingkat Kelompok); PJP Desa (tingkat Desa); Admin Daerah (tingkat Daerah)

### Prasyarat
- Permission `kegiatan-jadwal.manage` `[RBAC]`

### Pasca-kondisi (Sukses)
- Baris baru `kegiatan_jadwal` tersimpan
- **Memicu UC-30** (generate kejadian awal)
- Dicatat `[AUDIT]`

---

### Kontrak **[LIVEWIRE]**

**Component:** `Kegiatan\KelolaJadwalKegiatan`

**Properties:**
```
nama                        : string [WAJIB]
deskripsi                   : string [OPSIONAL]
tingkat                     : enum [WAJIB, terkunci sesuai role pembuat] — KELOMPOK|DESA|DAERAH
jenis                       : enum [WAJIB] — TAMBAHAN|PENGUATAN|PROGRAM_KHUSUS|EKSTRAKURIKULER
frekuensi_tipe               : enum [WAJIB] — HARIAN|BULANAN|MINGGUAN_INTERVAL
hari_dalam_minggu            : array<enum> [WAJIB, min 1] — SENIN..MINGGU
minggu_ke_dalam_bulan        : array<int|'TERAKHIR'> [WAJIB jika frekuensi_tipe=BULANAN, harus kosong jika lainnya] — nilai 1-5 dan/atau 'TERAKHIR'
interval_minggu              : integer [WAJIB (≥2) jika frekuensi_tipe=MINGGUAN_INTERVAL, harus kosong jika lainnya]
jumlah_sesi_per_kemunculan   : integer [OPSIONAL] — default 1
tanggal_mulai                : date [WAJIB]
tanggal_selesai               : date [WAJIB] — harus > tanggal_mulai
tempat                       : string [OPSIONAL] — diabaikan jika rotasi_tempat diisi
rotasi_tempat                 : array<string> [OPSIONAL] — daftar tempat bergantian, lihat SRS-Fase-2 §2.7
target_tipe                  : enum [OPSIONAL] — SEMUA (default)|JENJANG_KELAS|INDIVIDU, lihat UC-29
kegiatan_program_id           : integer [OPSIONAL] — label Program, lihat UC-40
jumlah_pratinjau              : integer (read-only, dihitung) — jumlah kejadian yang akan dibuat
jumlah_dilewati_libur         : integer (read-only, dihitung) — jumlah tanggal yang cocok pola tapi dilewati karena Hari Libur
peringatan_tabrakan           : string, nullable (read-only, dihitung) — pesan non-blocking bila ada Jadwal aktif lain yang polanya tumpang tindih
```

**Actions:**
```
hitungPratinjau()
  → validasi field pola (§ di bawah)
  → cek tabrakan pola dengan Jadwal AKTIF lain di tingkat/penyelenggara sama (non-blocking, isi peringatan_tabrakan bila ada)
  → ekspansi pola (SRS-Fase-2 §2.3, termasuk pengecualian Hari Libur & opsi TERAKHIR/MINGGUAN_INTERVAL)
    tanpa menyimpan, tampilkan jumlah_pratinjau + jumlah_dilewati_libur + beberapa tanggal contoh

simpan()
  → validasi tingkat sesuai wewenang role (sama UC-21, UCIC-Fase-1)
  → validasi jumlah_pratinjau <= 370 (SRS-Fase-2 §2.3)
  → simpan kegiatan_jadwal, status default AKTIF
  → catat activity_log [AUDIT]
  → trigger UC-30 (generate kejadian awal, termasuk penentuan tempat via rotasi bila rotasi_tempat diisi)

perbaruiJadwal($id)
  → validasi perubahan (rentang tanggal, pola)
  → simpan perubahan kegiatan_jadwal
  → catat activity_log [AUDIT]
  → trigger UC-30 (regenerasi, hanya kejadian mendatang & belum ada presensi)

nonaktifkan($id)
  → set status = NONAKTIF
  → catat activity_log [AUDIT]
  → TIDAK menghapus kejadian yang sudah ter-generate
```

#### Kondisi Gagal

| Kondisi | Pesan |
|---------|-------|
| PJP Kelompok memilih tingkat Desa/Daerah | "Anda hanya dapat membuat Jadwal Kegiatan tingkat Kelompok" |
| `tanggal_selesai` <= `tanggal_mulai` | "Tanggal selesai harus setelah tanggal mulai" |
| `frekuensi_tipe=BULANAN` tapi `minggu_ke_dalam_bulan` kosong | "Pilih minimal satu minggu-ke (atau Minggu Terakhir) untuk pola Rutin Bulanan" |
| `frekuensi_tipe=HARIAN`/`MINGGUAN_INTERVAL` tapi `minggu_ke_dalam_bulan` terisi | "Minggu-ke hanya berlaku untuk pola Rutin Bulanan" |
| `frekuensi_tipe=MINGGUAN_INTERVAL` tapi `interval_minggu` kosong atau < 2 | "Isi interval minggu (minimal 2) untuk pola Rutin Interval Mingguan" |
| Jumlah kejadian hasil ekspansi > 370 | "Rentang tanggal terlalu panjang — mohon persempit rentang atau buat Jadwal terpisah per periode" |

### Aturan Bisnis
- Field `tingkat` terkunci sesuai role pembuat, identik UC-21 (UCIC-Fase-1).
- `hari_dalam_minggu` dan `minggu_ke_dalam_bulan` dikombinasikan sebagai cartesian product (SRS-Fase-2 §2.2); nilai `TERAKHIR` dihitung ulang tiap bulan (bisa jatuh minggu ke-4 atau ke-5).
- Pola `MINGGUAN_INTERVAL` dihitung dari kelipatan `interval_minggu` sejak minggu yang memuat `tanggal_mulai`, lepas dari batas bulan kalender (SRS-Fase-2 §2.2).
- Regenerasi (`perbaruiJadwal`) **tidak pernah** menyentuh kejadian dengan `tanggal < hari ini` atau yang sudah punya `kegiatan_peserta` (SRS-Fase-2 §2.3) — histori presensi selalu aman.
- `peringatan_tabrakan` bersifat informatif saja — tidak pernah memblokir `simpan()`, karena sistem tidak mencatat jam sehingga tidak bisa memastikan tabrakan waktu sungguhan (SRS-Fase-2 §1.1).
- Kegiatan Insidental (UC-21, UCIC-Fase-1) tetap tersedia tanpa perubahan untuk kegiatan yang tidak berpola — lihat SRS-Fase-2 §2.1 untuk kriteria pemilihan Insidental vs Rutin.

---

## UC-29 — Kelola Penargetan Peserta Kegiatan

### Deskripsi
Membatasi cakupan peserta Kegiatan (baik Insidental — perluasan UC-21 — maupun Jadwal Kegiatan Berulang — UC-28) ke Jenjang/Kelas tertentu atau ke daftar individu Generus tertentu, sebagai alternatif cakupan otomatis by tingkat yang berlaku sejak Fase 1 (SRS-Fase-2 §2.4).

### Aktor
- **Utama:** PJP Kelompok, PJP Desa, Admin Daerah (Kegiatan/Jadwal tingkat masing-masing); Sekretaris KBM (Kegiatan Insidental tingkat Kelompok)

### Prasyarat
- Sedang mengisi form UC-21 (Kegiatan Insidental) atau UC-28 (Jadwal Kegiatan Berulang)

### Pasca-kondisi (Sukses)
- Baris `kegiatan_target_kelas`/`kegiatan_target_individu` (Insidental) atau `kegiatan_jadwal_target_kelas`/`kegiatan_jadwal_target_individu` (Jadwal) tersimpan

---

### Kontrak **[LIVEWIRE]**

**Component:** `Kegiatan\KelolaPenargetanPeserta` (embedded di `KelolaKegiatan` UC-21 dan `KelolaJadwalKegiatan` UC-28)

**Properties:**
```
target_tipe        : enum [WAJIB] — SEMUA|JENJANG_KELAS|INDIVIDU
jenjang_dipilih     : array<integer> [KONDISIONAL] — jalan pintas, dipakai hanya saat memilih target_tipe=JENJANG_KELAS
kelas_terpilih      : array<integer> [KONDISIONAL WAJIB jika target_tipe=JENJANG_KELAS]
filter_nama         : string [OPSIONAL] — pencarian individu, dipakai hanya saat target_tipe=INDIVIDU
filter_jenis_kelamin : enum [OPSIONAL] — LAKI|PEREMPUAN|SEMUA, dipakai hanya saat target_tipe=INDIVIDU
individu_terpilih   : array<integer> [KONDISIONAL WAJIB jika target_tipe=INDIVIDU] — kumulatif dari beberapa kali pencarian
```

**Actions:**
```
pilihJenjang($jenjangId)
  → cari seluruh kelas_id berjenjang tsb dalam cakupan tingkat Kegiatan/Jadwal
  → tambahkan ke kelas_terpilih (union, tidak menghapus pilihan manual sebelumnya)

cariIndividu()
  → query generus WHERE nama LIKE filter_nama AND jenis_kelamin sesuai filter (bila diisi)
    AND berada dalam cakupan tingkat Kegiatan/Jadwal
  → tampilkan hasil untuk dicentang

tambahIndividu($generusId)   → tambahkan ke individu_terpilih (kumulatif)
hapusIndividu($generusId)    → keluarkan dari individu_terpilih

simpan()  → dipanggil sebagai bagian dari simpan() UC-21/UC-28 (bukan submit terpisah)
  → validasi seluruh kelas_terpilih/individu_terpilih berada dalam cakupan tingkat
  → simpan baris target sesuai target_tipe
```

#### Kondisi Gagal

| Kondisi | Pesan |
|---------|-------|
| Kelas/Individu terpilih di luar cakupan tingkat Kegiatan/Jadwal | "Kelas/Generus ini berada di luar cakupan Kegiatan" |
| `target_tipe=JENJANG_KELAS` tapi `kelas_terpilih` kosong | "Pilih minimal satu Kelas atau Jenjang" |
| `target_tipe=INDIVIDU` tapi `individu_terpilih` kosong | "Pilih minimal satu Generus" |

### Aturan Bisnis
- Untuk Jadwal Kegiatan Berulang, penargetan **disalin (snapshot)** ke tiap kejadian hasil generate (UC-30) — mengubah target Jadwal setelahnya hanya berlaku untuk kejadian yang ikut diregenerasi (SRS-Fase-2 §2.4).
- Memilih target via jalan pintas Jenjang tetap disimpan sebagai daftar `kelas_id` konkret, bukan filter `jenjang_id` dinamis — Kelas baru berjenjang sama yang dibuat setelahnya **tidak** otomatis ikut tertarget.
- `target_tipe = SEMUA` (default) tidak mengaktifkan komponen ini sama sekali — perilaku identik Fase 1.
- Mengubah `target_tipe` Kegiatan/Jadwal yang sudah pernah dicatat presensinya (`kegiatan_peserta` sudah ada baris) tetap diizinkan, tapi **tidak** menghapus baris `kegiatan_peserta` yang sudah tercatat di luar target baru — hanya memengaruhi calon peserta yang ditampilkan untuk pencatatan berikutnya (lihat UC-23, UCIC-Fase-1, perluasan §ini).

---

## UC-30 — Generate & Regenerasi Kejadian Kegiatan (Sistem)

### Deskripsi
Proses latar belakang yang mengekspansi pola `kegiatan_jadwal` menjadi baris-baris `kegiatan` konkret, dipicu dari UC-28. Tidak ada UI terpisah — bagian dari alur simpan/edit Jadwal.

### Aktor
- **Utama:** Sistem (dipicu UC-28 `simpan()`/`perbaruiJadwal()`)

### Prasyarat
- `kegiatan_jadwal` valid tersimpan (baru atau diedit)

### Pasca-kondisi (Sukses — Generate Awal)
- N baris `kegiatan` baru (N = jumlah_pratinjau dari UC-28), tiap baris membawa `kegiatan_jadwal_id`, target tersalin dari Jadwal (UC-29)

### Pasca-kondisi (Sukses — Regenerasi)
- Kejadian mendatang yang belum ada presensi: dihapus lalu dibuat ulang sesuai pola baru
- Kejadian lampau/sudah ada presensi: tidak berubah

---

### Kontrak **[LIVEWIRE internal — dipanggil dari `KelolaJadwalKegiatan::simpan()`/`perbaruiJadwal()`]**

```
generate(KegiatanJadwal $jadwal, bool $regenerasi = false)
  → daftarTanggal = ekspansiPola($jadwal)  // SRS-Fase-2 §2.3: cartesian hari × minggu_ke/interval,
                                            //   MENGECUALIKAN tanggal yang termasuk rentang hari_libur manapun (§2.6)
  → jika $regenerasi:
      hapus kegiatan WHERE kegiatan_jadwal_id = $jadwal->id
        AND tanggal >= CURRENT_DATE
        AND id NOT IN (SELECT DISTINCT kegiatan_id FROM kegiatan_peserta)
  → urutkan daftarTanggal ASC (tanggal, sesi_ke) untuk keperluan indeks rotasi tempat (§2.7)
  → untuk tiap (tanggal, sesi_ke) dalam daftarTanggal, dengan indeks urutan ke-i (0-based):
      jika belum ada baris kegiatan (kegiatan_jadwal_id, tanggal, sesi_ke) — cek UNIQUE constraint:
        tempatKejadian = jadwal->rotasi_tempat ? jadwal->rotasi_tempat[i % count(jadwal->rotasi_tempat)] : jadwal->tempat
        insert kegiatan (nama, deskripsi, tingkat, penyelenggara_id, jenis, tanggal, sesi_ke,
                          tempat=tempatKejadian, target_tipe, kegiatan_program_id,
                          kegiatan_jadwal_id, dibuat_oleh, status=TERJADWAL)
        salin baris kegiatan_jadwal_target_kelas/individu → kegiatan_target_kelas/individu
  → catat activity_log [AUDIT] (subject: kegiatan_jadwal, ringkasan jumlah kejadian dibuat/dihapus/dilewati libur)
```

### Aturan Bisnis
- Idempotent by design — `UNIQUE (kegiatan_jadwal_id, tanggal, sesi_ke)` (SRS-Fase-2 §10.1) mencegah duplikasi bila `generate()` terpanggil berulang.
- Batas 370 kejadian per pemanggilan divalidasi **sebelum** proses ini berjalan (di UC-28, bukan di sini) — proses ini murni eksekusi setelah validasi lolos, dihitung dari kejadian yang benar-benar dibuat (tidak termasuk tanggal yang dilewati karena hari libur).
- Kejadian hasil generate berperilaku identik Kegiatan Insidental untuk seluruh use case selanjutnya (UC-22/UC-23/UC-25, UCIC-Fase-1) — tidak ada cabang logika khusus "kejadian hasil Jadwal" di luar `kegiatan_jadwal_id` sebagai penanda asal.
- Proses ini **tidak** menangani pembatalan kejadian akibat Hari Libur yang ditambahkan **setelah** kejadian sudah ter-generate — itu ditangani terpisah oleh UC-39 (dipicu dari UC-38, bukan dari `generate()` ini).

---

## UC-31 — Generator Laporan Bulanan (Kelompok)

### Deskripsi
PJP Kelompok men-generate Laporan Bulanan (46 slide HTML interaktif) untuk periode tertentu, meninjau data live, lalu memfinalisasinya sebagai snapshot beku (SRS-Fase-2 §3).

### Aktor
- **Utama:** PJP Kelompok

### Prasyarat
- Permission `laporan.manage` `[RBAC]`

### Pasca-kondisi (Sukses — Generate)
- Baris `laporan_bulanan` status `DRAFT` (atau baris baru dengan `versi` +1 bila revisi pasca-final)

### Pasca-kondisi (Sukses — Finalisasi)
- Status → `FINAL`, `snapshot_data` terisi beku, `difinalisasi_oleh`/`difinalisasi_pada` terisi
- Dicatat `[AUDIT]`

---

### Kontrak **[LIVEWIRE]**

**Component:** `Laporan\GenerateLaporanKelompok`

**Properties:**
```
kelompok_id : integer [WAJIB]
periode     : string [WAJIB] — format YYYY-MM
```

**Actions:**
```
generate()
  → jika belum ada laporan_bulanan(kelompok_id, periode) berstatus DRAFT: buat baris baru status=DRAFT
  → tampilkan seluruh 46 slide dengan data LIVE query (SRS-Fase-2 §3.2) — bagian tanpa sumber
    (29 Karakter, Shodaqoh) tampil "Belum tersedia — modul menyusul di Fase 3"
  → catat activity_log [AUDIT]

finalisasi()
  → hitung ulang seluruh angka final saat ini
  → simpan snapshot_data (SRS-Fase-2 §3.3), status = FINAL
  → catat difinalisasi_oleh, difinalisasi_pada
  → catat activity_log [AUDIT]
  → laporan masuk antrian review PJP Desa (UC-32)
```

**Component turunan:** `Laporan\ViewerLaporanSlide($laporanId)` — render 46 slide (live bila DRAFT, dari `snapshot_data` bila FINAL/DISETUJUI/REVISI_DIMINTA), tombol cetak client-side (pola sama UC-20, UCIC-Fase-1).

#### Kondisi Gagal

| Kondisi | Pesan |
|---------|-------|
| Finalisasi laporan yang bukan status DRAFT | "Laporan ini sudah difinalisasi — buat revisi untuk mengubahnya" |
| Generate untuk Kelompok di luar scope user | "Anda tidak berwenang membuat laporan Kelompok ini" |

### Aturan Bisnis
- Selama `DRAFT`, seluruh angka **live** — perubahan Presensi/Sensus/Kegiatan/Sarpras/Musyawaroh sesudahnya langsung tercermin di viewer (SRS-Fase-2 §3.1).
- Finalisasi membekukan angka ke `snapshot_data` — perubahan data sumber setelahnya tidak lagi memengaruhi laporan tsb (SRS-Fase-2 §3.1).
- Revisi pasca-final (dipicu dari UC-32 saat ditolak) membuat baris `laporan_bulanan` baru dengan `versi` bertambah — versi lama tetap tersimpan sebagai arsip.

---

## UC-32 — Agregasi, Approval & Penelusuran Laporan Berjenjang

### Deskripsi
PJP Desa men-generate Laporan Desa sebagai agregasi otomatis laporan Kelompok yang sudah `FINAL`/`DISETUJUI`, lalu Admin Daerah mereview & menyetujui/menolak; pola yang sama juga dipakai PJP Desa untuk mereview laporan Kelompok di desanya (SRS-Fase-2 §3.4). **Diperluas (permintaan fitur baru, dimajukan dari rencana awal Fase 4):** Admin Daerah men-generate Laporan Daerah sebagai agregasi otomatis laporan Desa yang sudah `FINAL`/`DISETUJUI` — jenjang teratas, tidak ada approval lanjutan (SRS-Fase-2 §3.5). PJP Desa & Admin Daerah juga bisa **menelusuri (drill-down)** laporan individual jenjang di bawah scope mereka kapan saja, bukan cuma yang sedang menunggu review (SRS-Fase-2 §3.6).

### Aktor
- **Utama:** PJP Desa (agregasi Desa; review laporan Kelompok; telusuri laporan individual Kelompok di desanya); Admin Daerah (agregasi Daerah; review laporan Desa; telusuri laporan individual Desa & Kelompok di daerahnya)

### Prasyarat
- Permission `laporan.review` `[RBAC]` — juga dipakai untuk penelusuran read-only (tidak ada permission terpisah)
- (Agregasi Desa) minimal satu laporan Kelompok berstatus `FINAL`/`DISETUJUI` untuk periode tsb di Desa yang bersangkutan
- (Agregasi Daerah) minimal satu laporan Desa berstatus `FINAL`/`DISETUJUI` untuk periode tsb

### Pasca-kondisi (Sukses — Agregasi Desa)
- Baris baru `laporan_bulanan` (`desa_id` terisi, `tingkat=DESA`, status `DRAFT`)

### Pasca-kondisi (Sukses — Agregasi Daerah)
- Baris baru `laporan_bulanan` (`daerah_id` terisi, `tingkat=DAERAH`, status `DRAFT`)

### Pasca-kondisi (Sukses — Setuju)
- Status → `DISETUJUI`, `disetujui_oleh`/`disetujui_pada` terisi

### Pasca-kondisi (Sukses — Tolak)
- Status → `REVISI_DIMINTA`, `catatan_revisi` terisi

### Pasca-kondisi (Sukses — Finalisasi Laporan Daerah)
- Status → `FINAL` langsung sebagai status akhir (tidak ada `DISETUJUI`/`REVISI_DIMINTA` untuk tingkat `DAERAH`, lihat Aturan Bisnis)

---

### Kontrak **[LIVEWIRE]**

**Component:** `Laporan\GenerateLaporanDesa`

**Properties:**
```
desa_id : integer [WAJIB]
periode : string [WAJIB]
```

**Actions:**
```
generate()
  → cari laporan_bulanan Kelompok (desa_id sama, periode sama, status IN (FINAL, DISETUJUI))
  → jika ada Kelompok di Desa ini yang laporannya belum final: tampilkan peringatan
    "{n} dari {total} Kelompok belum finalisasi laporan", tetap izinkan lanjut
  → gabungkan snapshot_data (sensus dijumlah, kehadiran rata-rata berbobot, kegiatan &
    program monitoring digabung per-Kelompok — SRS-Fase-2 §3.4)
  → simpan laporan_bulanan baru (tingkat=DESA, status=DRAFT)

finalisasi() — pola sama UC-31 (bekukan ke snapshot_data, status=FINAL), dipicu dari tombol
  Finalisasi di ViewerLaporanSlide; laporan masuk antrian review Admin Daerah
```

**Component:** `Laporan\GenerateLaporanDaerah` `[BARU]`

**Properties:**
```
periode : string [WAJIB]
```

**Actions:**
```
generate()
  → cari laporan_bulanan Desa (periode sama, status IN (FINAL, DISETUJUI)) — tidak ada scope
    entitas (Daerah tunggal per instalasi, PRD §18.4)
  → jika ada Desa yang laporannya belum final: tampilkan peringatan
    "{n} dari {total} Desa belum finalisasi laporan", tetap izinkan lanjut
  → gabungkan snapshot_data (sensus dijumlah, kehadiran rata-rata berbobot, kegiatan &
    program monitoring digabung per-Desa — SRS-Fase-2 §3.5, formula sama §3.4 satu tingkat lebih tinggi)
  → simpan laporan_bulanan baru (tingkat=DAERAH, status=DRAFT)

finalisasi()
  → bekukan angka ke snapshot_data, status=FINAL
  → catat difinalisasi_oleh, difinalisasi_pada
  → catat activity_log [AUDIT]
  → TIDAK masuk antrian review siapa pun — FINAL adalah status akhir untuk tingkat DAERAH
    (tidak ada approver di atas Daerah, SRS-Fase-2 §3.1/§3.5)
```

**Component:** `Laporan\DaftarLaporanBerjenjang` `[BARU]`

**Properties:**
```
tingkat    : enum [WAJIB] — KELOMPOK atau DESA (tingkat entitas yang ditelusuri)
entitas_id : integer [WAJIB] — kelompok_id atau desa_id yang ditelusuri
```

**Actions:**
```
mount()
  → validasi entitas_id berada dalam scope aktor (PJP Desa: Kelompok harus di desanya;
    Admin Daerah: tanpa batasan tambahan, scope Daerah penuh)
  → tampilkan seluruh laporan_bulanan milik entitas_id (semua status & versi, terbaru dahulu) — read-only,
    bukan cuma yang FINAL menunggu review (beda dari antrian di ApprovalLaporan)
  → klik baris → buka Laporan\ViewerLaporanSlide (read-only, tombol Setuju/Tolak/Finalisasi disembunyikan
    kecuali aktor juga berwenang lewat UC-31/ApprovalLaporan untuk laporan tsb)
```

**Component:** `Laporan\ApprovalLaporan`

**Properties:**
```
laporan_id : integer [WAJIB]
catatan    : string [WAJIB jika aksi=tolak]
```

**Actions:**
```
setujui()
  → validasi laporan berstatus FINAL
  → status = DISETUJUI, catat disetujui_oleh & disetujui_pada
  → catat activity_log [AUDIT]

tolak($catatan)
  → validasi laporan berstatus FINAL
  → status = REVISI_DIMINTA, simpan catatan_revisi
  → catat activity_log [AUDIT]
  → pembuat laporan (PJP Kelompok/Desa) melihat status ini di UC-31/agregasi ulang di sini,
    merevisi data sumber, lalu memfinalisasi ulang (versi baru, UC-31)
```

#### Kondisi Gagal

| Kondisi | Pesan |
|---------|-------|
| Setuju/Tolak laporan yang belum FINAL | "Laporan ini belum difinalisasi oleh pembuatnya" |
| Tolak tanpa catatan | "Catatan revisi wajib diisi" |
| Agregasi Desa di luar scope PJP Desa | "Anda tidak berwenang membuat laporan Desa ini" |
| Setuju/Tolak laporan tingkat DAERAH | "Laporan tingkat Daerah tidak melalui approval — sudah final begitu difinalisasi pembuatnya" |
| Penelusuran (`DaftarLaporanBerjenjang`) Kelompok di luar Desa milik PJP Desa | "Anda tidak berwenang melihat laporan Kelompok ini" |

### Aturan Bisnis
- Laporan Kelompok direview PJP Desa; laporan Desa (agregasi) direview Admin Daerah — dua jenjang approval berbeda approver, pola sama-sama pakai component `ApprovalLaporan` ini.
- Regenerasi laporan Desa (generate ulang setelah ada Kelompok tambahan yang final) selalu membuat **draft baru**, tidak menimpa draft yang sudah ada — PJP Desa menentukan kapan generate ulang (SRS-Fase-2 §3.4).
- **Laporan tingkat Daerah kini ada sejak Fase 2** (permintaan fitur baru, dimajukan dari rencana awal Fase 4, PRD v1.12 §14) — Admin Daerah men-generate Laporan Daerah sebagai agregasi laporan Desa `FINAL`/`DISETUJUI`, pola sama persis §3.4 satu tingkat lebih tinggi (SRS-Fase-2 §3.5). Berbeda dari Kelompok/Desa, laporan Daerah **tidak direview siapa pun** — Daerah adalah jenjang teratas struktur organisasi (PRD §18.4, satu Daerah per instalasi), sehingga `FINAL` sudah jadi status akhir; component `ApprovalLaporan` tidak dipakai untuk tingkat ini.
- PJP Desa & Admin Daerah bisa **menelusuri (drill-down)** laporan individual jenjang di bawah scope-nya lewat `DaftarLaporanBerjenjang` — read-only, mencakup seluruh status & versi (bukan cuma yang menunggu review di antrian `ApprovalLaporan`). Ini melengkapi, bukan menggantikan, alur approval yang sudah ada (SRS-Fase-2 §3.6).

---

## UC-33 — Kelola Sarana & Prasarana

### Deskripsi
PJP Kelompok/Bagian Pembantu Umum memperbarui kondisi 14 item Sarpras baku kelompoknya; PJP Desa, Admin Daerah, & Bidang Sarpras Daerah melihat rekap read-only lintas-Kelompok untuk perencanaan pengadaan (SRS-Fase-2 §5, PRD §6).

### Aktor
- **Utama:** PJP Kelompok, Bagian Pembantu Umum KBM (`pembantu-umum-kbm`, baru — kelola kelompoknya, pola sama dengan PJP Kelompok untuk modul ini); PJP Desa (lihat se-Desa); Admin Daerah (lihat se-Daerah, kelola master item); Bidang Sarpras Daerah (`bidang-sarpras-daerah`, baru — lihat se-Daerah, permission `sarpras.view` saja, tanpa kelola master item)

### Prasyarat
- Permission `sarpras.manage` (PJP Kelompok, Pembantu Umum KBM) atau `sarpras.view` (PJP Desa, Admin Daerah, Bidang Sarpras Daerah) `[RBAC]`
- Akun dengan role `pembantu-umum-kbm`/`bidang-sarpras-daerah` disediakan lewat UC-07 (UCIC-Fase-1, Kelola Akun Internal) — enum `role` di kontrak tsb belum diperluas menambahkan kedua slug ini pada revisi UCIC-Fase-1.md saat ini (di luar ruang lingkup Fase 1), perlu ditambahkan saat implementasi Fase 2 dimulai

### Pasca-kondisi (Sukses)
- Baris `sarpras_item` (UPSERT per kelompok_id + sarpras_master_item_id) diperbarui
- Dicatat `[AUDIT]`

---

### Kontrak **[LIVEWIRE]**

**Component:** `Sarpras\KelolaSarpras`

**Properties:**
```
kelompok_id : integer [WAJIB]
items       : array<{sarpras_master_item_id, nama (read-only), kondisi, catatan}> — 14+ baris, auto-lengkap
```

**Actions:**
```
perbaruiKondisi($sarprasMasterItemId, $kondisi, $catatan)
  → UPSERT sarpras_item (kelompok_id, sarpras_master_item_id) → kondisi, catatan, diperbarui_oleh
  → catat activity_log [AUDIT]
```

**Component:** `Sarpras\RekapSarprasDesa` / `Sarpras\RekapSarprasDaerah` — read-only, agregasi `kondisi ∈ {RUSAK_BERAT, TIDAK_ADA}` per Kelompok dalam scope. Bidang Sarpras Daerah memakai `RekapSarprasDaerah` yang sama persis dengan yang dipakai Admin Daerah (tidak ada component terpisah).

#### Kondisi Gagal

| Kondisi | Pesan |
|---------|-------|
| PJP Kelompok/Pembantu Umum KBM mengubah Sarpras Kelompok lain | "Anda tidak berwenang mengubah data Sarpras kelompok ini" |

### Aturan Bisnis
- Satu baris `sarpras_item` per (kelompok_id, sarpras_master_item_id) — kondisi terkini saja, bukan log historis (SRS-Fase-2 §5).
- Kelompok baru otomatis mendapat 14 baris `sarpras_item` berkondisi default `TIDAK_ADA` — checklist selalu lengkap tanpa baris hilang.
- Menjadi sumber slide 27 pada Generator Laporan (UC-31, PRD §13).
- **Pembantu Umum KBM** memiliki permission `sarpras.manage` identik PJP Kelompok untuk modul ini (`perbaruiKondisi()` tanpa pembatasan tambahan) — mencerminkan tugas hariannya mengurus sarana pembelajaran & kondisi sarpras kelompok (PRD §6, Struktur-Organisasi-dan-Role.md §C); tidak ada pembagian sebagian-item antara PJP Kelompok dan Pembantu Umum KBM, keduanya bisa memperbarui seluruh 14 item.
- **Bidang Sarpras Daerah** murni `sarpras.view` (rekap se-Daerah untuk perencanaan pengadaan) — **tidak** mendapat `sarpras.manage`, berbeda dari Admin Daerah yang juga bisa kelola master item; kewenangannya setara PJP Desa untuk modul ini tapi discoped ke seluruh Daerah alih-alih satu Desa.

---

## UC-34 — Upload & Kelola Dokumentasi Foto Kegiatan

### Deskripsi
PJP Kelompok/Sekretaris KBM mengunggah foto dokumentasi untuk suatu Kegiatan atau Program Monitoring, dikompresi di sisi klien sebelum diunggah (SRS-Fase-2 §6).

### Aktor
- **Utama:** PJP Kelompok, Sekretaris KBM

### Prasyarat
- Permission `kegiatan-foto.manage` `[RBAC]`
- Koneksi internet aktif (tidak didukung offline, SRS-Fase-2 §6.1)

### Pasca-kondisi (Sukses)
- Satu atau lebih baris `kegiatan_foto` tersimpan
- Dicatat `[AUDIT]`

---

### Kontrak **[LIVEWIRE]**

**Component:** `Kegiatan\UploadFotoKegiatan`

**Properties:**
```
kegiatan_id            : integer [KONDISIONAL — salah satu dari ini wajib]
program_monitoring_id  : integer [KONDISIONAL — salah satu dari ini wajib]
files                  : array<file> [WAJIB] — hasil resize sisi klien (maks 1600px sisi terpanjang, JPEG ~80%)
```

**Actions:**
```
unggah()
  → validasi tepat satu dari kegiatan_id/program_monitoring_id terisi
  → untuk tiap file (sudah di-resize klien): simpan ke storage, insert kegiatan_foto
  → catat activity_log [AUDIT]
```

**Component:** `Kegiatan\GaleriFotoKegiatan($kegiatanId atau $programMonitoringId)` — tampilan galeri, dipakai juga sebagai sumber slide Foto Kegiatan di Generator Laporan (UC-31).

#### Kondisi Gagal

| Kondisi | Pesan |
|---------|-------|
| Upload tanpa koneksi internet | "Upload foto memerlukan koneksi internet aktif" |
| File bukan hasil resize (validasi ukuran sisi server, sanity check) | "Ukuran file tidak sesuai — mohon unggah ulang" |

### Aturan Bisnis
- Kompresi/resize **selalu** terjadi di sisi klien sebelum upload (SRS-Fase-2 §6.1) — backend tidak melakukan pemrosesan gambar ulang.
- Berlaku untuk Kegiatan apa pun (Insidental maupun hasil Jadwal Berulang, UC-28) tanpa pembatasan tambahan.
- Dikecualikan dari mekanisme offline (SRS-Fase-1 §13.1) — konsisten keputusan Fase 1.

---

## UC-35 — Carry-over Musyawaroh Otomatis

### Deskripsi
Saat Sekretaris KBM membuka form Musyawaroh baru (perluasan UC-15, UCIC-Fase-1), sistem otomatis menyalin item notulen bulan sebelumnya yang masih berstatus `BELUM_TERLAKSANA` sebagai baris awal, menghilangkan kebutuhan salin manual (SRS-Fase-2 §4).

### Aktor
- **Utama:** Sistem (dipicu saat `KelolaMusyawaroh::mount()`); Sekretaris KBM (melanjutkan/menandai status)

### Prasyarat
- Ada notulen `musyawaroh` bulan sebelumnya untuk `(kelompok_id, jenis)` yang sama, dengan minimal satu `musyawaroh_item` berstatus `BELUM_TERLAKSANA`

### Pasca-kondisi (Sukses)
- Form notulen baru terisi baris awal hasil carry-over (`item_asal_id` menautkan ke item lama)

---

### Kontrak **[LIVEWIRE — perluasan `Musyawaroh\KelolaMusyawaroh::mount()`, UCIC-Fase-1 UC-15]**

```
mount($kelompokId, $jenis)
  → cari musyawaroh bulan sebelumnya (kelompok_id, jenis)
  → untuk tiap musyawaroh_item terkait dengan status_carry_over = BELUM_TERLAKSANA:
      tambahkan ke items[] form baru: pokok_masalah & keputusan LAMA read-only sebagai konteks,
      keputusan/keterangan BARU kosong menunggu diisi, item_asal_id = id item lama
  → Sekretaris tetap bisa tambahItem() baru seperti UC-15 (tanpa item_asal_id)

simpan()  → sama seperti UC-15, ditambah: status_carry_over per item WAJIB diisi (SELESAI|BELUM_TERLAKSANA)
```

#### Kondisi Gagal

| Kondisi | Pesan |
|---------|-------|
| Item carry-over disimpan tanpa status_carry_over dipilih | "Tandai status tindak lanjut untuk tiap item" |

### Aturan Bisnis
- Carry-over berantai — item hasil carry-over yang masih `BELUM_TERLAKSANA` ikut ter-carry-over lagi ke bulan berikutnya, `item_asal_id` selalu menunjuk item langsung sebelumnya (SRS-Fase-2 §4.2).
- Item berstatus `SELESAI` berhenti muncul di notulen bulan berikutnya.
- Data historis dari sebelum perluasan ini (Fase 1) diperlakukan `BELUM_TERLAKSANA` secara default saat migrasi (SRS-Fase-2 §4.2, §10.3).
- Menjadi sumber slide "Evaluasi Bulan Lalu & Resume Bulan Ini" pada Generator Laporan (UC-31) — status carry-over bisa diklik di laporan untuk melihat rantai histori.

---

## UC-36 — Dashboard Desa (Agregasi)

### Deskripsi
PJP Desa (kelola tersirat lewat kewenangan jabatannya) dan Seksi KBM Reguler (`seksi-kbm-reguler-desa`, baru) melihat ringkasan lintas-Kelompok di desanya: kehadiran, sensus, status laporan bulanan, Sarpras kritis, Kegiatan tingkat Desa mendatang — menggantikan placeholder Fase 1 yang hanya daftar Kelompok read-only (SRS-Fase-2 §7).

### Aktor
- **Utama:** PJP Desa; Seksi KBM Reguler Desa (`seksi-kbm-reguler-desa`, baru — permission `dashboard-desa.view` saja, lihat Aturan Bisnis)

### Prasyarat
- Permission `dashboard-desa.view` `[RBAC]`
- Akun dengan role `seksi-kbm-reguler-desa` disediakan lewat UC-07 (UCIC-Fase-1, Kelola Akun Internal) — enum `role` di kontrak tsb belum diperluas menambahkan slug ini pada revisi UCIC-Fase-1.md saat ini (di luar ruang lingkup Fase 1), perlu ditambahkan saat implementasi Fase 2 dimulai

---

### Kontrak **[LIVEWIRE]**

**Component:** `Dashboard\DashboardDesa`

**Properties:**
```
desa_id : integer [WAJIB]
```

**Actions:**
```
mount()
  → hitung ringkasan kehadiran bulan berjalan per Kelompok (agregat presensi, SRS-Fase-1 §17.4)
  → jumlahkan sensus_snapshots seluruh Kelompok di desa (SRS-Fase-1 §17.2)
  → baca status laporan_bulanan tiap Kelompok periode berjalan (UC-31)
  → baca sarpras_item WHERE kondisi IN (RUSAK_BERAT, TIDAK_ADA), dikelompokkan per Kelompok (UC-33)
  → baca kegiatan WHERE tingkat=DESA, penyelenggara_id=desa_id, tanggal >= hari ini (UC-28/UC-21)
```

### Aturan Bisnis
- Menggantikan landing page Fase 1 (SRS-Fase-1 §1.1/§3.1, UC-04 Kelola Struktur Organisasi — PJP Desa tidak punya `dashboard.view` di Fase 1) — kini widget agregat penuh, pola sama seperti Dashboard Kelompok (SRS-Fase-1 §14, UC-19) tapi lintas-Kelompok.
- **Seksi KBM Reguler Desa** mendapat akses identik PJP Desa untuk halaman ini (`mount()` yang sama, tidak ada mode/tampilan terpisah) — sesuai Struktur-Organisasi-dan-Role.md §B, permission-nya di Fase 2 hanya `dashboard-desa.view` ini; permission `kbm-reguler.view` (monitoring kepatuhan standar KBM se-desa, item terpisah di matrix yang sama) **belum** dibahas di UCIC ini — di luar ruang lingkup revisi ini, menyusul terpisah bila modul monitoring kepatuhannya sudah dirancang.
- Tidak ada **Dashboard** Daerah — Admin Daerah tetap memakai Dashboard Kelompok per-Kelompok, Dashboard Daerah penuh adalah Fase 4. Beda dari **Laporan** Daerah (UC-32), yang sudah aktif sejak Fase 2.

---

## UC-37 — Notifikasi Jadwal Kegiatan (Portal Orang Tua)

### Deskripsi
Sistem mengirim notifikasi in-app H-1 ke orang tua generus yang menjadi target suatu Kegiatan mendatang (SRS-Fase-2 §8) — perluasan UC-18 (Notifikasi Alpha, UCIC-Fase-1).

### Aktor
- **Utama:** Sistem (job terjadwal harian); Orang Tua (penerima)

### Prasyarat
- Ada `kegiatan` dengan `tanggal` = besok, status `TERJADWAL`

### Pasca-kondisi (Sukses)
- Baris baru `notifikasi_orang_tua` (`tipe=JADWAL_KEGIATAN`) untuk tiap akun orang tua yang tertaut ke generus target Kegiatan tsb

---

### Kontrak **[LIVEWIRE internal — job terjadwal `App\Jobs\KirimReminderKegiatanBesok`]**

```
handle()  // dijalankan Laravel Scheduler tiap hari, mis. pukul 18:00
  → cari kegiatan WHERE tanggal = CURRENT_DATE + 1, status = TERJADWAL
  → untuk tiap kegiatan:
      resolusi daftar generus target (SRS-Fase-2 §2.4 — SEMUA/JENJANG_KELAS/INDIVIDU)
      untuk tiap generus target:
        cari akun_orang_tua tertaut (akun_orang_tua_generus, SRS-Fase-1 §17.6)
        untuk tiap akun: insert notifikasi_orang_tua
          (tipe=JADWAL_KEGIATAN, kegiatan_id, generus_id,
           pesan="Kegiatan '{nama}' untuk {nama generus} dijadwalkan besok, {tanggal}")
```

**Component tampilan:** `PortalOrangTua\NotifikasiFeed` (UC-18, UCIC-Fase-1) — diperluas menampilkan dua tipe notifikasi (`ALPHA`, `JADWAL_KEGIATAN`) dalam satu feed yang sama, terbaru dulu.

### Aturan Bisnis
- Satu notifikasi per (akun_orang_tua, generus, kegiatan) — akun dengan >1 anak yang sama-sama target mendapat notifikasi terpisah per anak (pola sama UC-18).
- Job berjalan H-1 sore, **tidak** mengejar Kegiatan yang dibuat mendadak kurang dari H-1 — tidak ada jalur notifikasi instan terpisah untuk kasus ini (SRS-Fase-2 §8.2).
- Notifikasi in-app tetap jalur utama; WhatsApp tidak dibangun di Fase 2 (konsisten SRS-Fase-1 §1.1).

---

## UC-38 — Kelola Kalender Hari Libur

### Deskripsi
Admin Daerah mengelola satu daftar hari libur berlaku untuk seluruh Daerah (Idul Fitri, libur semester, tanggal merah nasional, dsb.), dipakai sebagai pengecualian otomatis saat Jadwal Kegiatan Berulang di-generate (SRS-Fase-2 §2.6).

### Aktor
- **Utama:** Admin Daerah

### Prasyarat
- Permission `hari-libur.manage` `[RBAC]`

### Pasca-kondisi (Sukses — Tambah/Ubah)
- Baris `hari_libur` tersimpan
- **Memicu UC-39** (sinkronisasi kejadian mendatang yang bertabrakan)
- Dicatat `[AUDIT]`

---

### Kontrak **[LIVEWIRE]**

**Component:** `Kegiatan\KelolaHariLibur`

**Properties:**
```
nama              : string [WAJIB]
tanggal_mulai     : date [WAJIB]
tanggal_selesai   : date [WAJIB] — boleh sama dengan tanggal_mulai (libur satu hari)
sumber            : enum (read-only) — MANUAL|OTOMATIS_GOOGLE, lihat UC-41
disunting_manual  : boolean (read-only, dihitung) — true setelah baris OTOMATIS_GOOGLE pernah diedit lewat simpan()
```

**Actions:**
```
simpan()
  → validasi tanggal_selesai >= tanggal_mulai
  → simpan hari_libur
  → jika baris existing bersumber OTOMATIS_GOOGLE: set disunting_manual = true
    (melindungi dari tertimpa sinkronisasi UC-41 berikutnya)
  → catat activity_log [AUDIT]
  → dispatch event HariLiburDisimpan (UC-39 — sinkronisasi retroaktif)

hapus($id)
  → jika sumber = MANUAL: hard-delete baris hari_libur seperti biasa
  → jika sumber = OTOMATIS_GOOGLE: soft-delete — set dihapus_pada = sekarang,
    baris disembunyikan dari daftar aktif & dari pengecekan generate (SRS-Fase-2 §2.6.1)
  → catat activity_log [AUDIT]
  → TIDAK mengembalikan kejadian yang sebelumnya sudah dibatalkan UC-39 (SRS-Fase-2 §2.6)
```

#### Kondisi Gagal

| Kondisi | Pesan |
|---------|-------|
| `tanggal_selesai` < `tanggal_mulai` | "Tanggal selesai tidak boleh sebelum tanggal mulai" |
| Bukan Admin Daerah | "Anda tidak berwenang mengelola Kalender Hari Libur" |

### Aturan Bisnis
- Satu kalender berlaku untuk seluruh Daerah (PRD §18.4 — tidak ada multi-tenant), tidak ada versi per Kelompok/Desa.
- Kegiatan Insidental (UC-21, UCIC-Fase-1) yang dibuat manual pada tanggal hari libur **tidak diblokir**, hanya diberi peringatan non-blocking saat disimpan.
- Baris bersumber `OTOMATIS_GOOGLE` tetap bisa diedit/dihapus Admin Daerah lewat use case ini seperti baris `MANUAL` — bedanya hanya konsekuensi teknis di balik layar (`disunting_manual`/soft-delete, SRS-Fase-2 §2.6.1), tidak ada UI atau alur terpisah untuk mengelolanya.

---

## UC-39 — Sinkronisasi Otomatis Kegiatan vs Hari Libur (Sistem)

### Deskripsi
Proses latar belakang yang membatalkan kejadian `kegiatan` mendatang yang bertabrakan dengan Hari Libur baru/berubah, dipicu dari UC-38 (Admin Daerah menyimpan manual) **maupun** UC-41 (hasil sinkronisasi Google Calendar). Tidak ada UI terpisah — logikanya satu listener yang sama dipakai kedua jalur.

### Aktor
- **Utama:** Sistem (dipicu event `HariLiburDisimpan` — dari `KelolaHariLibur::simpan()` di UC-38, atau di-dispatch oleh `SinkronisasiHariLiburGoogle::handle()` di UC-41 untuk tiap baris `hari_libur` yang di-insert/di-update)

### Prasyarat
- `hari_libur` baru disimpan atau rentang tanggalnya diubah — baik lewat input manual (UC-38) maupun sinkronisasi otomatis (UC-41)

### Pasca-kondisi (Sukses)
- Kejadian `kegiatan` yang memenuhi kriteria (lihat kontrak) berstatus `TIDAK_TERLAKSANA` dengan `catatan_status` terisi

---

### Kontrak **[LIVEWIRE internal — listener event `HariLiburDisimpan`, di-dispatch dari `KelolaHariLibur::simpan()` (UC-38) maupun `SinkronisasiHariLiburGoogle::handle()` (UC-41)]**

```
handle(HariLibur $libur)
  → cari kegiatan WHERE kegiatan_jadwal_id IS NOT NULL
      AND tanggal BETWEEN libur->tanggal_mulai AND libur->tanggal_selesai
      AND tanggal >= CURRENT_DATE
      AND id NOT IN (SELECT DISTINCT kegiatan_id FROM kegiatan_peserta)
  → untuk tiap kegiatan ditemukan:
      update status = TIDAK_TERLAKSANA,
             catatan_status = "Dibatalkan otomatis — Hari Libur: {libur->nama}"
  → catat activity_log [AUDIT] (subject: hari_libur, ringkasan jumlah kegiatan dibatalkan)
```

### Aturan Bisnis
- Kejadian dengan `tanggal < hari ini` atau yang sudah punya `kegiatan_peserta` (sudah tercatat presensinya) **tidak pernah** disentuh — prinsip sama dengan regenerasi Jadwal (UC-30).
- Menandai `TIDAK_TERLAKSANA` dengan `catatan_status`, **bukan** menghapus baris `kegiatan` — kejadian yang batal tetap terlihat di riwayat beserta alasannya.
- Tidak berjalan mundur (retroaktif) untuk Kegiatan Insidental — hanya kejadian yang punya `kegiatan_jadwal_id` (hasil generate) yang ikut proses ini, karena Insidental sudah divalidasi/diperingatkan satu-satu saat dibuat (UC-38).

---

## UC-40 — Kelola Program Kegiatan & Rekap Gabungan

### Deskripsi
PJP Kelompok/Desa/Admin Daerah membuat label Program untuk mengelompokkan Jadwal Kegiatan Berulang dan/atau Kegiatan Insidental yang secara konsep satu program walau tersebar di beberapa tingkat penyelenggara, lalu melihat rekap kehadiran gabungan lintas-tingkat (SRS-Fase-2 §2.8).

### Aktor
- **Utama:** PJP Kelompok, PJP Desa, Admin Daerah (siapa pun yang berwenang mengelola Jadwal/Kegiatan di tingkat masing-masing)

### Prasyarat
- Permission `kegiatan-jadwal.manage` atau `kegiatan.manage` `[RBAC]` (sama seperti UC-21/UC-28 — tidak ada permission baru khusus)

### Pasca-kondisi (Sukses)
- Baris `kegiatan_program` tersimpan; Jadwal/Kegiatan yang ditandai mendapat `kegiatan_program_id` terisi

---

### Kontrak **[LIVEWIRE]**

**Component:** `Kegiatan\KelolaProgramKegiatan` (embedded sebagai pemilih di `KelolaKegiatan` UC-21 dan `KelolaJadwalKegiatan` UC-28)

**Properties:**
```
nama               : string [WAJIB, saat buat Program baru]
tingkat_tertinggi  : enum [WAJIB, saat buat Program baru] — KELOMPOK|DESA|DAERAH, metadata informatif
```

**Actions:**
```
buatProgram()
  → simpan kegiatan_program
  → catat activity_log [AUDIT]

tandaiKegiatan($kegiatanIdAtauJadwalId, $kegiatanProgramId)
  → set kegiatan_program_id pada kegiatan atau kegiatan_jadwal terkait
  → dipanggil sebagai bagian dari simpan() UC-21/UC-28, bukan submit terpisah
```

**Component:** `Kegiatan\RekapProgramKegiatan($kegiatanProgramId, $periode)`

**Actions:**
```
mount()
  → kumpulkan seluruh kegiatan (dari kegiatan_jadwal manapun maupun Insidental) dengan
    kegiatan_program_id = $kegiatanProgramId, tanggal dalam $periode
  → hitung agregat kehadiran (HADIR/IZIN/SAKIT/ALPHA + % kehadiran), dikelompokkan per
    tingkat/penyelenggara asal (mis. subtotal Kelompok, subtotal Desa)
```

#### Kondisi Gagal

| Kondisi | Pesan |
|---------|-------|
| Menandai Jadwal/Kegiatan dengan Program di luar wewenang pembuat (mis. Program dibuat orang lain di scope berbeda) | *(tidak dibatasi — lihat Aturan Bisnis)* |

### Aturan Bisnis
- Satu `kegiatan_jadwal`/`kegiatan` bisa ditandai **paling banyak satu** `kegiatan_program_id` (SRS-Fase-2 §2.8) — bukan many-to-many.
- Menandai Program **tidak mengubah** validasi cakupan tingkat/penyelenggara masing-masing Jadwal/Kegiatan — setiap Jadwal/Kegiatan tetap divalidasi sendiri-sendiri sesuai UC-21/UC-28. Tidak ada pembatasan tambahan siapa boleh memakai Program siapa — Program murni label bersama, bukan objek dengan kepemilikan eksklusif.
- Rekap Gabungan (`RekapProgramKegiatan`) murni agregasi baca — tidak mengubah data `kegiatan`/`kegiatan_jadwal` manapun.

---

## UC-41 — Sinkronisasi Kalender Hari Libur dari Google Calendar (Sistem)

### Deskripsi
Proses latar belakang bulanan yang menarik hari libur nasional dari kalender publik Google Calendar dan meng-upsert ke `hari_libur`, sebagai pelengkap opsional dari pencatatan manual (UC-38) — bukan pengganti (SRS-Fase-2 §2.6.1).

### Aktor
- **Utama:** Sistem (job terjadwal bulanan)

### Prasyarat
- `GOOGLE_CALENDAR_API_KEY` terkonfigurasi di `.env` — bila belum, use case ini berhenti di langkah pertama tanpa efek apa pun (bukan gagal/error)

### Pasca-kondisi (Sukses)
- Baris `hari_libur` baru bersumber `OTOMATIS_GOOGLE` tersimpan untuk event yang belum pernah ada; baris existing yang memenuhi syarat (§ di bawah) diperbarui bila datanya berubah dari sisi Google
- Baris baru/berubah **memicu UC-39** (sinkronisasi retroaktif terhadap kejadian mendatang)

---

### Kontrak **[LIVEWIRE internal — job terjadwal `App\Jobs\SinkronisasiHariLiburGoogle`]**

```
handle()  // dijalankan Laravel Scheduler tiap bulan
  → jika env('GOOGLE_CALENDAR_API_KEY') kosong → return, tidak melakukan apa pun (no-op)
  → panggil Google Calendar API events.list untuk kalender publik libur nasional Indonesia,
    rentang waktu = tahun berjalan + tahun depan
  → untuk tiap event (google_event_id, nama, tanggal_mulai, tanggal_selesai) hasil panggilan:
      cari hari_libur WHERE google_event_id = event.google_event_id
      jika tidak ditemukan:
        insert hari_libur (nama, tanggal_mulai, tanggal_selesai, sumber=OTOMATIS_GOOGLE,
                            google_event_id, disunting_manual=false, dibuat_oleh=NULL)
        dispatch event HariLiburDisimpan untuk baris baru ini (UC-39)
      jika ditemukan DAN disunting_manual=false DAN dihapus_pada IS NULL DAN data berubah:
        update nama/tanggal_mulai/tanggal_selesai
        dispatch event HariLiburDisimpan untuk baris yang berubah ini (UC-39)
      jika ditemukan DAN (disunting_manual=true ATAU dihapus_pada terisi):
        lewati, tidak disentuh
  → jika panggilan API gagal (kuota/jaringan): catat ke log aplikasi, hentikan proses ini
    dengan aman (tidak melempar exception yang mengganggu job terjadwal lain), coba lagi
    siklus bulan berikutnya
  → TIDAK mencatat activity_log [AUDIT] per baris (volume berpotensi besar & bukan aksi
    pengguna) — cukup satu ringkasan log aplikasi per eksekusi (jumlah baru/diperbarui/dilewati)
```

### Aturan Bisnis
- Murni pelengkap opsional — seluruh mekanisme Kalender Hari Libur (UC-38) berfungsi penuh tanpa job ini bila API key tidak dikonfigurasi.
- Hanya mencakup hari libur **nasional**; libur internal organisasi (libur semester PPG, dsb.) di luar cakupan integrasi ini dan tetap harus dicatat manual lewat UC-38.
- Idempotent by design — `google_event_id` adalah kunci upsert unik (SRS-Fase-2 §10.1), pemanggilan job berulang untuk periode yang sama tidak menghasilkan duplikat.
- `disunting_manual`/`dihapus_pada` memastikan penyuntingan/penghapusan manual Admin Daerah (UC-38) terhadap baris hasil sinkronisasi **tidak pernah tertimpa** oleh eksekusi job berikutnya.

---

**Riwayat Revisi:**

| Versi | Tanggal | Perubahan |
|-------|---------|-----------|
| 1.0 | 1 Agustus 2026 | Dokumen awal UCIC Fase 2, diturunkan dari SRS-Fase-2.md (10 use case baru: UC-27–UC-36), melanjutkan penomoran dari UCIC-Fase-1.md (berakhir UC-26) |
| 1.1 | 1 Agustus 2026 | Tambah UC-37 (Kelola Kalender Hari Libur), UC-38 (Sinkronisasi Otomatis Kegiatan vs Hari Libur), UC-39 (Kelola Program Kegiatan & Rekap Gabungan) sesuai SRS-Fase-2.md v1.1 §2.6–§2.8; perluas UC-27 (properti `frekuensi_tipe=MINGGUAN_INTERVAL`, `interval_minggu`, `minggu_ke_dalam_bulan` mendukung `TERAKHIR`, `rotasi_tempat`, `kegiatan_program_id`, `peringatan_tabrakan`) dan UC-29 (`generate()` kini mengecualikan tanggal hari libur & menentukan tempat via rotasi) |
| 1.2 | 1 Agustus 2026 | Tambah UC-40 (Sinkronisasi Kalender Hari Libur dari Google Calendar, sesuai SRS-Fase-2.md v1.2 §2.6.1); perluas UC-37 (properti `sumber`/`disunting_manual`, `simpan()` menandai `disunting_manual` pada baris `OTOMATIS_GOOGLE`, `hapus()` kini soft-delete untuk baris `OTOMATIS_GOOGLE` vs hard-delete untuk `MANUAL`) |
| 1.3 | 1 Agustus 2026 | **Renumber seluruh use case UC-26–UC-39 → UC-27–UC-40** — UCIC-Fase-1.md menambah UC-26 (Kelola Matriks Role & Permission), sehingga penomoran Fase 2 yang "melanjutkan dari UC-25" harus bergeser +1 agar tidak tabrakan. Perubahan murni penomoran/referensi silang, tidak ada perubahan aturan bisnis atau kontrak teknis apa pun |
| 1.4 | 2 Agustus 2026 | **Renumber seluruh use case UC-27–UC-40 → UC-28–UC-41** (pola sama seperti v1.3) — UCIC-Fase-1.md menambah UC-27 (Kelola Catatan Konseling/Rekam Kasus, role `bk-kbm`), sehingga penomoran Fase 2 bergeser +1 lagi. Sekaligus **tambah 3 role baru hasil ekspansi model peran per jabatan** (`docs/Struktur-Organisasi-dan-Role.md` §Matriks, ditandai Fase 2): UC-33 "Kelola Sarana & Prasarana" — tambah aktor **Bagian Pembantu Umum KBM** (`pembantu-umum-kbm`, kelola kondisi sarpras kelompoknya, permission `sarpras.manage` identik PJP Kelompok) dan **Bidang Sarpras Daerah** (`bidang-sarpras-daerah`, lihat rekap se-Daerah, permission `sarpras.view` saja); UC-36 "Dashboard Desa (Agregasi)" — tambah aktor **Seksi KBM Reguler Desa** (`seksi-kbm-reguler-desa`, permission `dashboard-desa.view`, akses identik PJP Desa untuk halaman ini). Ketiganya reuse use case existing (tidak ada UC baru untuk role ini) — modul Sarpras & Dashboard Desa sudah lebih dulu ada di Fase 2 sebelum perluasan role ini. Permission `kbm-reguler.view` milik Seksi KBM Reguler Desa **tidak** dibahas di revisi ini (di luar ruang lingkup). Catatan: enum `role` di UC-07 (UCIC-Fase-1, Kelola Akun Internal) belum diperluas untuk 3 role baru ini — perlu ditambahkan saat implementasi Fase 2 dimulai |
| 1.5 | 12 Agustus 2026 | **UC-32 diganti nama jadi "Agregasi, Approval & Penelusuran Laporan Berjenjang"** — permintaan fitur baru, dimajukan dari rencana awal Fase 4 (PRD v1.12 §14, SRS-Fase-2.md v1.7 §3.5/§3.6): tambah component `Laporan\GenerateLaporanDaerah` (Admin Daerah men-generate Laporan Daerah sebagai agregasi otomatis Laporan Desa `FINAL`/`DISETUJUI`, pola sama agregasi Desa satu tingkat lebih tinggi; siklus berhenti di `FINAL` — tidak ada approval lanjutan karena Daerah jenjang teratas, `ApprovalLaporan` tidak dipakai untuk tingkat ini) dan `Laporan\DaftarLaporanBerjenjang` (PJP Desa/Admin Daerah menelusuri read-only laporan individual jenjang di bawah scope-nya — PJP Desa ke tiap Kelompok di desanya, Admin Daerah ke tiap Desa maupun tiap Kelompok di daerahnya — bukan cuma yang menunggu review di antrian approval). Tambah Pasca-kondisi, Kondisi Gagal, dan Aturan Bisnis terkait |
