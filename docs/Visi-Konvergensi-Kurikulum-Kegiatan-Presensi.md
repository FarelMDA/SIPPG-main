# Visi Arsitektur — Konvergensi Kurikulum, Kegiatan, dan Presensi

**Nama Sistem:** SI-PPG — Sistem Informasi Pembinaan Generus
**Versi Dokumen:** 1.1
**Tanggal:** 4 Agustus 2026
**Status:** **Diimplementasikan** — lihat §6 untuk ringkasan & penyimpangan dari rencana awal di §1-§5
**Klasifikasi:** Internal — Terbatas
**Dokumen Terkait:** [SRS-Fase-1.md](SRS-Fase-1.md) §8 (Kurikulum), §16-17 (Presensi & Jurnal Harian), §18 (Kegiatan); [SRS-Fase-2.md](SRS-Fase-2.md) §2 (Jadwal Kegiatan Berulang); [PRD-Aplikasi-Pendataan-Pelaporan-PPG.md](PRD-Aplikasi-Pendataan-Pelaporan-PPG.md) §9.3-9.5, §9.9, §9.12, §9.16, §14 (Roadmap Fase 3-4)

> **Catatan penting:** Dokumen ini **bukan** spesifikasi siap-bangun seperti SRS-Fase-*.md — ini adalah **acuan alur besar (north star)** yang menjelaskan ke mana arsitektur Kurikulum/Kegiatan/Presensi seharusnya menyatu jangka panjang, supaya setiap fase pengembangan berikutnya (SRS-Fase-3 dst.) tidak mengambil keputusan yang bertentangan dengan arah ini. Bagian §2 berisi audit eksplisit: apa yang **sudah** sesuai di implementasi saat ini, dan apa yang **masih berbeda** (gap) — supaya gap itu ditutup secara sadar di fase mendatang, bukan tidak sengaja diperparah oleh fitur baru.
>
> **Konteks penting soal risiko:** Pembagian Fase 1/Fase 2 di dokumen SRS/PRD murni alat bantu QC selama development — **belum ada satu pun Fase yang di-deploy ke lapangan**, sehingga tidak ada data produksi sungguhan yang menjadi taruhannya. Konsekuensinya, item yang di §4 ditandai 🔴 ("pembalikan keputusan Fase 1") **tidak perlu diperlakukan sebagai fase migrasi berisiko tinggi** — itu bisa dan sebaiknya dikerjakan sebagai revisi langsung terhadap kode Fase 1/Fase 2 Modul 1 yang sudah ada sekarang, selama masih dalam masa development. Lihat §5 untuk dampak konkretnya ke modul yang sudah diimplementasikan.

---

## 1. Alur Besar

1. **Kurikulum** dipecah (breakdown) **per-tanggal kalender** dalam satu tahun ajaran KBM — bukan per nomor urut hari mengajar. Breakdown ini mencakup: materi yang harus disampaikan per kelas/jenjang pada tanggal tsb, tanggal libur (dikecualikan dari jadwal mengajar), dan tanggal Munaqosah (ujian kenaikan/kelulusan materi). Kurikulum **dibuat oleh Daerah** — Desa/Kelompok tidak membuat kurikulum sendiri, hanya melaksanakan.

2. **Kegiatan** adalah wadah pelaksanaan dari breakdown Kurikulum tsb di lapangan. Contoh konkret: Kurikulum Daerah menyatakan "Tilawati 1 halaman 1 diajarkan Senin–Rabu pekan ke-1, halaman 2 Senin–Rabu pekan ke-2, dst." untuk siswa kelas 1 SD (jenjang ACR). Di tingkat Kelompok, pelaksanaan breakdown ini **muncul sebagai Kegiatan** — misalnya "Kegiatan Kelas ACR A" yang berlangsung berulang sesuai pola yang sudah dipecah Daerah. Presensi yang dihasilkan dari pelaksanaan itu **adalah presensi Kegiatan "Kelas ACR A"** tsb — bukan tabel presensi yang berdiri sendiri di luar Kegiatan.

   Kegiatan bisa dibuat dan dilaksanakan di tingkat **Daerah, Desa, maupun Kelompok** — KBM reguler harian (seperti contoh di atas) adalah salah satu *jenis* Kegiatan (Rutin, tingkat Kelompok, target satu Kelas spesifik), berdampingan dengan jenis Kegiatan lain yang sudah ada sekarang (Tambahan/Penguatan/Program Khusus/Ekstrakurikuler — GOMA, GMKM, pengajian, dst.).

3. **Kegiatan bisa Rutin atau Tidak Rutin.** Rutin dipecah lagi jadi Harian, Bulanan, atau tiap-beberapa-minggu/bulan (pola berulang, di-generate otomatis dari definisi pola). Tidak Rutin dijadwalkan satu-per-satu sewaktu-waktu, tanpa pola tetap.

4. Setiap Kegiatan membawa empat unsur: **Peserta** (siapa yang ditargetkan), **Materi** (apa yang disampaikan/dilakukan — untuk Kegiatan hasil breakdown Kurikulum, ini adalah item materi Kurikulum hari itu), **Waktu**, dan **Tempat**. Keempatnya bersama-sama menjadi dasar pencatatan presensi.

5. **Peserta Kegiatan selalu berakar di tingkat Kelompok** — siapa pun peserta akhirnya, ia adalah anggota satu Kelompok tertentu. Kegiatan tingkat Desa berarti gabungan peserta dari beberapa Kelompok se-Desa; Kegiatan tingkat Daerah berarti gabungan peserta lintas-Desa (secara transitif, lintas-Kelompok se-Daerah).

6. **Setiap Kegiatan di setiap kelas, di tingkat manapun, punya laporannya sendiri** — dan laporan di tingkat yang lebih tinggi adalah **agregasi otomatis** dari laporan tingkat di bawahnya (Kelompok → Desa → Daerah), termasuk breakdown sampai ke level kelas, bukan cuma level Kelompok.

7. Kegiatan — baik Rutin maupun Tidak Rutin — **dapat dipantau oleh seluruh stakeholder**, dengan pengaturan akses yang **fleksibel/dinamis** (siapa boleh lihat apa diatur lewat mekanisme role & permission yang bisa diubah tanpa deploy ulang), bukan daftar role yang di-hardcode secara tetap di kode.

---

## 2. Status Implementasi Saat Ini vs Target (Gap Analysis)

Audit terhadap kode per 4 Agustus 2026. Tujuannya bukan mengkritik desain yang ada — desain saat ini valid untuk cakupan Fase 1/2 yang sudah disepakati — melainkan memetakan jarak ke visi §1 supaya fase berikutnya punya baseline yang jelas.

### 2.1 Kurikulum — perlu pindah dari `hari_ke` ke tanggal kalender

**Saat ini:** `kurikulum_kalender` (SRS-Fase-1 §8) disusun per **jenjang + `hari_ke`** (nomor urut hari mengajar ke berapa), bukan tanggal kalender eksplisit. Konsekuensinya:
- Tidak ada mekanisme libur di dalam Kurikulum sendiri — hari libur otomatis "tidak dihitung" sebagai `hari_ke`, tapi tidak ada baris/penanda eksplisit untuk itu.
- Tidak ada penjadwalan Munaqosah/ujian sama sekali — baik sebagai baris khusus di Kurikulum maupun fitur terpisah.

**Target (§1.1):** Kurikulum dipecah per tanggal kalender eksplisit, dengan tanggal libur dan tanggal Munaqosah sebagai bagian dari breakdown itu sendiri (bukan tabel `hari_libur` yang saat ini hanya dipakai Kegiatan Jadwal Berulang dan tidak terhubung ke Kurikulum sama sekali).

**Implikasi desain:** Ini perubahan skema, bukan penambahan kolom kecil — `kurikulum_kalender` perlu direstrukturisasi dari (jenjang, hari_ke) → (jenjang/kelas, tanggal). Perlu dipikirkan juga migrasi data lama dan bagaimana breakdown "per pekan ke-N" (seperti contoh Tilawati di §1.2) diterjemahkan ke tanggal kalender aktual (yang bergantung tahun ajaran & hari libur yang berlaku saat itu).

### 2.2 Kegiatan sebagai wadah KBM reguler — gap arsitektur paling besar

**Saat ini:** KBM reguler harian dicatat lewat `Presensi` + `Jurnal Harian` (SRS-Fase-1 §16-17) — sistem yang **sepenuhnya terpisah** dari `Kegiatan` (SRS-Fase-1 §18, diperluas SRS-Fase-2 §2). Tidak ada baris kode yang menghubungkan keduanya: `Kegiatan` tidak punya kolom `kurikulum_kalender_id` maupun `kelas_id`; Kurikulum hanya direferensikan *read-only* oleh Jurnal Harian (ditampilkan sebagai "materi terjadwal hari ini", tidak disimpan). PRD bahkan eksplisit mendefinisikan Kegiatan sebagai "kegiatan tambahan **di luar** KBM harian".

**Target (§1.2):** KBM reguler per kelas (hasil breakdown Kurikulum) **adalah** Kegiatan Rutin tingkat Kelompok, bertarget satu Kelas spesifik ("Kegiatan Kelas ACR A"), di-generate otomatis mengikuti breakdown yang sudah dipecah Daerah. Presensinya adalah presensi Kegiatan tsb.

**Implikasi desain:**
- Perlu jalur generate Kegiatan dari `kurikulum_kalender` per kelas — mirip `GeneratorKegiatanDariJadwal` yang sudah ada untuk `KegiatanJadwal`, tapi sumber polanya dari Kurikulum, bukan definisi jadwal manual.
- `Jurnal Harian` saat ini mencatat lebih dari sekadar hadir/tidak — ada `realisasi_status` (SESUAI_JADWAL/TIDAK_TERLAKSANA/PENGGANTI) untuk melacak realisasi materi vs rencana. Konsep ini perlu ada padanannya di model Kegiatan hasil-Kurikulum, karena presensi Kegiatan saat ini (`KegiatanPeserta.status_presensi`) hanya melacak kehadiran peserta, bukan realisasi materi.
- Karena belum ada Fase yang di-deploy ke lapangan (lihat catatan konteks di atas), tidak ada data presensi produksi sungguhan yang perlu strategi migrasi/koeksistensi — data yang ada sekarang murni data dummy/seed (`DummyPondokJayaSeeder`, factory test), aman diregenerasi ulang. Ini menurunkan `Presensi`/`Jurnal Harian` dari "modul yang menyentuh data sensitif produksi" jadi "modul yang tinggal direfactor langsung" — lihat §5 untuk rincian dampaknya ke kode yang sudah ada.

### 2.3 Field Materi di Kegiatan

**Saat ini:** `Kegiatan` punya `deskripsi` (teks bebas), tidak ada representasi terstruktur untuk "materi yang diajarkan/dibahas".

**Target (§1.4):** Kegiatan hasil breakdown Kurikulum perlu membawa referensi materi terstruktur (idealnya relasi ke item Kurikulum yang mendasarinya, bukan cuma teks bebas), supaya rekap bisa menjawab "materi apa saja yang sudah/belum tersampaikan", bukan cuma "berapa kali Kegiatan terlaksana".

### 2.4 Peserta & cakupan tingkat

**Saat ini:** Sudah sebagian besar sesuai — `KegiatanPeserta.kelompok_id` didenormalisasi persis untuk memastikan peserta Kegiatan tingkat Desa/Daerah tetap bisa dipecah per asal Kelompok (`HasKelompokCakupan`). Satu nuansa: Kegiatan tingkat DAERAH saat ini mengambil **seluruh Kelompok se-Daerah secara flat**, bukan disusun berjenjang lewat Desa sebagai unit antara — secara hasil akhir (siapa saja pesertanya) ini setara dengan §1.5, jadi **tidak dianggap gap** kecuali fase mendatang butuh Desa sebagai unit agregasi eksplisit (lihat §2.5).

### 2.5 Rollup laporan lintas tingkat & per-kelas — kemampuan baru yang perlu dibangun

**Saat ini:** Agregasi Kelompok→Desa→Daerah **hanya otomatis di dalam satu baris Kegiatan yang sama** (satu Kegiatan tingkat Desa, rekapnya dipecah on-the-fly per Kelompok peserta — lihat `RekapKegiatan`). Kalau Kelompok dan Desa punya **baris Kegiatan masing-masing yang independen** (mis. tiap Kelompok generate Kegiatan Kelas ACR A miliknya sendiri dari Kurikulum, dan Desa ingin melihat rekap gabungan semua Kelompok di bawahnya), **tidak ada rollup otomatis** — yang ada baru `kegiatan_program_id` (`RekapProgramKegiatan`), itu pun sifatnya *tagging manual/opt-in*, bukan otomatis mengikuti struktur organisasi. Selain itu, `kegiatan_peserta` cuma punya `kelompok_id` (bukan `kelas_id`), jadi breakdown laporan ke level kelas belum ada.

**Target (§1.6):** Rollup otomatis mengikuti struktur organisasi (Kelompok ⊂ Desa ⊂ Daerah) tanpa perlu tagging manual, dan laporan bisa pecah sampai ke level kelas.

**Implikasi desain:** Ini kemungkinan besar konsekuensi alami dari §2.2 — begitu KBM reguler per kelas jadi Kegiatan sungguhan (dengan `kelas_id` sebagai bagian dari target/identitasnya), rollup per-kelas dan per-jenjang-organisasi jadi bisa dihitung langsung dari relasi struktural yang sudah ada (`kelas.kelompok_id`, `kelompok.desa_id`), tanpa mekanisme baru yang rumit — tapi untuk Kegiatan non-KBM (Tambahan/dst.) yang independen antar tingkat, rollup otomatis lintas-baris tetap perlu dirancang terpisah dari pola `kegiatan_program_id` yang ada sekarang.

### 2.6 Akses monitoring lintas stakeholder

**Saat ini:** `kegiatan.view`/`kegiatan.manage` cuma dipegang `sysadmin`, `admin-daerah`, `pjp-desa`, `pjp-kelompok`, `sekretaris-kbm`. Role lain (Guru, Sekretaris Desa/PPG, BK-KBM, Pakar Pendidik) tidak punya akses lihat Kegiatan sama sekali; Wanbin* cuma melihat widget "Kegiatan mendatang" di Dashboard umum (`dashboard.view`), bukan rekap penuh.

**Target (§1.7):** Akses monitoring diatur **fleksibel/dinamis**, bukan daftar role tetap. Aplikasi ini sudah punya fondasi untuk itu — Matriks Role & Permission (`roles.manage`, halaman `pengaturan.matriks-role`) memungkinkan Admin Daerah/sysadmin mengatur permission per role tanpa ubah kode. Prinsip desainnya: **jangan hardcode asumsi "role X butuh/tidak butuh akses Y"** di seeder sebagai keputusan permanen — cukup pastikan `kegiatan.view` *bisa* diberikan ke role manapun lewat Matriks yang sudah ada, dan biarkan Admin Daerah yang memutuskan siapa saja stakeholder yang perlu memantau sesuai kebutuhan organisasi masing-masing (yang bisa berbeda antar Daerah/waktu).

---

## 3. Pertanyaan Terbuka untuk Perencanaan Fase Berikutnya

Hal-hal yang sengaja belum diputuskan di dokumen ini — perlu didalami saat menyusun SRS fase yang benar-benar menggarap konvergensi ini:

1. ~~Migrasi data historis~~ — *tidak lagi relevan*: karena belum ada Fase yang di-deploy ke lapangan, tidak ada data `Presensi`/`Jurnal Harian` produksi yang perlu direpresentasikan ulang (lihat catatan konteks di awal dokumen & §5). Pertanyaan ini otomatis kembali relevan kalau suatu saat ada Fase yang sudah live sebelum konvergensi ini selesai dikerjakan.
2. **Realisasi materi vs presensi kehadiran** — Jurnal Harian melacak dua hal berbeda (materi terlaksana sesuai rencana ATAU tidak/diganti, DAN kehadiran siswa). Apakah keduanya tetap satu kesatuan di model Kegiatan hasil-Kurikulum, atau dipisah jadi dua konsep?
3. **Granularitas breakdown Kurikulum** — pola seperti "halaman 1 Senin–Rabu pekan ke-1" perlu aturan konversi ke tanggal kalender aktual (bergantung tahun ajaran, hari libur berlaku). Siapa yang mendefinisikan tanggal mulai tahun ajaran, dan bagaimana kalau breakdown "meleset" karena libur mendadak?
4. **Munaqosah** — apakah strukturnya sama dengan breakdown materi biasa (satu baris Kurikulum dengan jenis "UJIAN"), atau perlu model/alur tersendiri (mis. penilaian, bukan cuma presensi)?
5. **Kegiatan non-KBM lintas tingkat** — untuk Kegiatan Tambahan (bukan hasil Kurikulum) yang dibuat independen di tiap tingkat, mekanisme rollup otomatis (§2.5) perlu dirancang terpisah dari kasus KBM — apakah semua jenis Kegiatan butuh rollup otomatis, atau hanya yang terhubung ke struktur Kurikulum?

---

## 4. Kaitan dengan Fase yang Sudah Ditetapkan

Bagian §2 memetakan gap terhadap kode saat ini, tapi gap itu tidak semuanya berstatus sama terhadap dokumen resmi (PRD/SRS/Roadmap §14). Sebagian adalah **ruang kosong yang memang belum digarap** (aman ditutup fase mendatang tanpa efek samping), tapi sebagian lain adalah **pembalikan keputusan Fase 1 yang sudah tertulis, disepakati, dan live di produksi** — dua kategori ini butuh penanganan perencanaan yang berbeda. Tabel berikut memetakannya secara eksplisit.

| Bagian §2 | Berkaitan dengan | Sifat overlap |
|---|---|---|
| §2.1 (Kurikulum → tanggal kalender) | **PRD §9.3** (Fase 1, sudah live) | 🔴 **Pembalikan keputusan, bukan gap.** PRD eksplisit memakai struktur "10 Bulan × hari sekolah" (persis `hari_ke`). Desain ini juga jadi acuan eksplisit **§9.9** (Fase 3, Master Kompetensi RPS): "keyed per jenjang, sama seperti `KURIKULUM_KALENDER` §9.3, bukan per Kelas individual". Mengubah Kurikulum ke tanggal kalender berarti desain Kompetensi Master di Fase 3 juga perlu ditinjau ulang supaya tetap konsisten satu sama lain.
| §2.2 (KBM reguler jadi Kegiatan) | **PRD §9.12 baris 366** (Fase 1, sudah live) | 🔴 **Pembalikan keputusan paling mendasar.** PRD eksplisit mendefinisikan Kegiatan sebagai "kegiatan tambahan **di luar KBM harian §9.4**" — bukan area abu-abu yang belum diputuskan, tapi garis batas tertulis yang sudah diimplementasikan penuh dan dipakai KBM pilot. Konvergensi ini membalik salah satu keputusan desain paling dasar Fase 1, sehingga menyentuh data presensi yang sudah berjalan produksi.
| §2.3 (field Materi di Kegiatan) | **PRD §9.9 baris 284** (Fase 3, direncanakan belum dibangun) | 🟢 **Overlap yang mendukung.** Fase 3 sudah merencanakan "Kegiatan Ekstrakurikuler dimodelkan sebagai Kompetensi Dasar tambahan ... tertaut ke data kehadiran Modul Kegiatan §9.12" — cikal-bakal linkage Kegiatan↔materi yang sudah dipikirkan, tapi baru untuk kasus ekstrakurikuler, belum untuk semua Kegiatan/KBM seperti divisikan di §1.4.
| §2.5 (rollup lintas tingkat) | **PRD §9.16** (Fase 2, sudah dispesifikasikan) | 🟡 **Perlu dibedakan levelnya, bukan langsung tumpang tindih.** PRD §9.16 sudah punya rollup: "Laporan Desa & Daerah adalah agregasi otomatis dari laporan Kelompok yang sudah `final`" — tapi itu rollup di level **snapshot laporan bulanan**, bukan rollup live per-baris Kegiatan/kelas yang dibahas §2.5. Yang benar-benar belum ada bukan "rollup secara umum", tapi rollup granular per-Kegiatan/per-kelas di luar siklus laporan bulanan.
| §2.5 (rollup) — juga | **Roadmap Fase 4, "Agregasi Daerah"** (§14 baris 543-545) | 🟡 **Tema sama, cakupan beda.** Fase 4 scope-nya "Dashboard & laporan Daerah penuh" (mengaktifkan §9.17 di level Daerah), bukan rollup granular Kegiatan/kelas. Kalau §2.5 digarap, koordinasikan dengan Fase 4 supaya tidak membangun dua mekanisme agregasi berjenjang yang berbeda dan tidak konsisten.
| §2.6 (akses monitoring dinamis) | Tidak ada overlap dengan fase manapun | ⚪ Murni prinsip desain (pakai Matriks Role & Permission yang sudah ada) — tidak bertentangan atau tumpang tindih dengan roadmap manapun.

**Konsekuensi untuk perencanaan:** §2.1 dan §2.2 (ditandai 🔴) sebaiknya **tidak** diperlakukan sebagai "fitur tambahan" di SRS-Fase-3/4 yang sudah direncanakan — keduanya perlu jadi **revisi arsitektur tersendiri terhadap kode Fase 1/2 yang sudah ada**. Karena belum ada Fase yang di-deploy ke lapangan (lihat catatan konteks di awal dokumen), revisi ini **tidak perlu menunggu jendela migrasi khusus atau strategi koeksistensi data produksi** — cukup dikerjakan sebagai refactor langsung selama masih masa development, lihat §5 untuk peta dampaknya per modul. §2.3 (🟢) relatif aman digabung ke Fase 3 karena memang searah dengan yang sudah direncanakan di sana. §2.5 (🟡) perlu keputusan eksplisit dulu soal cakupan (granular per-Kegiatan vs. snapshot laporan bulanan yang sudah ada) sebelum masuk perencanaan fase manapun.

---

## 5. Dampak Konkret ke Modul yang Sudah Diimplementasikan (Fase 1 & Fase 2 Modul 1)

Audit per 4 Agustus 2026 terhadap kode yang **sudah ditulis** (Fase 1 penuh: Master Data, Sensus, Kurikulum, Presensi & Jurnal Harian, Musyawaroh, Kegiatan & Program Monitoring, Dashboard, Portal Ortu dasar; Fase 2 Modul 1: Kegiatan Jadwal Berulang & Penargetan Peserta — SRS-Fase-2 §2). Karena belum ada Fase yang live di lapangan, daftar ini ditulis sebagai **peta perubahan yang aman dikerjakan sekarang**, bukan wishlist fase mendatang.

**Temuan kunci:** infrastruktur Jadwal Berulang yang baru selesai dibangun di Fase 2 Modul 1 (`KegiatanJadwal`, `GeneratorKegiatanDariJadwal`, `EkspansiPolaJadwal`, `HariLibur`, `KegiatanProgram`, `JenisKegiatan`) **sebagian besar sudah menjadi mesin yang tepat** untuk mewujudkan §1.2/§1.3 — pola `frekuensi_tipe=HARIAN` + `target_tipe=JENJANG_KELAS` sudah bisa memodelkan persis "Kegiatan Kelas ACR A, Senin–Rabu". Konvergensi ini **bukan bangun dari nol**, melainkan menyambungkan mesin yang sudah ada ke Kurikulum, plus retire modul yang jadi berlebih (Presensi/Jurnal Harian).

| Modul yang sudah ada | Dampak | Tindakan |
|---|---|---|
| `kurikulum_kalender` (jenjang + `hari_ke`) | Direstrukturisasi | Ganti skema ke (jenjang/kelas + tanggal kalender). Karena cuma data seed/dummy, aman diganti langsung lewat migration baru (drop & recreate), tanpa dual-write atau kolom transisi. |
| Mekanisme libur untuk Kurikulum | **Tidak perlu dibangun baru** | §2.1 draf awal menyiratkan Kurikulum butuh mekanisme libur sendiri — ternyata **tidak**: tabel `hari_libur` (Fase 2 Modul 1, sudah Daerah-wide & generic) bisa langsung dipakai bersama sebagai satu sumber kebenaran hari libur, baik untuk generate Kegiatan Jadwal Berulang maupun untuk exclude tanggal di breakdown Kurikulum. |
| `ImporKalender` (upload Excel per jenjang, `hari_ke`-based) | Perlu diubah | Format impor & parsing perlu tahu tanggal mulai tahun ajaran untuk menerjemahkan "pekan ke-N" jadi tanggal kalender aktual (lihat pertanyaan terbuka §3.3). |
| `Presensi` + `Jurnal Harian` (tabel, model, Livewire `InputPresensi`/`InputJurnalHarian`/`KonfirmasiRealisasiMateri`/`RekapPresensi`) | **Kandidat retire penuh**, bukan sekadar direfactor | Begitu KBM-sebagai-Kegiatan siap, seluruh modul ini jadi berlebih. Karena tidak ada data produksi, tidak perlu jalur koeksistensi — bisa langsung dihapus setelah alur penggantinya (via Kegiatan) selesai & teruji. |
| Konsep `realisasi_status` (SESUAI_JADWAL/TIDAK_TERLAKSANA/PENGGANTI) dari Jurnal Harian | Perlu rumah baru | Realisasi materi itu levelnya per-kejadian-Kegiatan (bukan per-siswa), jadi field ini lebih tepat pindah ke `Kegiatan`/kejadian hasil generate, sejalan dengan §2.3 (field Materi), bukan ke `KegiatanPeserta`. |
| `KegiatanJadwal` + `GeneratorKegiatanDariJadwal` + `EkspansiPolaJadwal` | **Diperluas, bukan diganti** | Tambahkan opsi generate dari breakdown Kurikulum (sumber pola dari `kurikulum_kalender`, bukan cuma `hari_dalam_minggu` manual) sebagai mode baru di generator yang sudah ada. |
| `jenis_kegiatan` (master data, baru dibangun) | **Tidak perlu perubahan skema** | Sudah bisa menampung kategori "KBM Reguler" sebagai satu jenis Kegiatan baru — cukup Daerah menambah entri lewat halaman Master Data Jenis Kegiatan yang sudah ada, tanpa perubahan kode. |
| `kegiatan_peserta` (kolom `kelompok_id`, tanpa `kelas_id`) | Perlu kolom tambahan | Tambah `kelas_id` supaya breakdown laporan per-kelas (§2.5) punya dasar query langsung, bukan join tidak langsung lewat `generus.kelas_id`. |
| Rollup lintas tingkat untuk KBM (§2.5) | Lebih sederhana dari dugaan awal | Karena KBM per kelas selalu tingkat KELOMPOK, rollup ke Desa/Daerah untuk KBM cukup query lintas-Kegiatan yang sumber polanya sama (`kurikulum_kalender`/jenjang yang sama), dikelompokkan via relasi struktural yang sudah ada (`kelas.kelompok_id` → `kelompok.desa_id`) — **tidak perlu mekanisme rollup baru yang rumit** untuk kasus KBM. Rollup untuk Kegiatan Tambahan lintas-tingkat yang independen (bukan hasil Kurikulum) tetap kasus terpisah yang belum terjawab (§3.5). |
| Dashboard Kelompok & Portal Orang Tua dasar | Sumber data pindah | Keduanya saat ini membaca `Presensi`/`Jurnal Harian` langsung — begitu itu retire, query-nya diarahkan ke `Kegiatan`/`KegiatanPeserta`. Murni perubahan query, tanpa risiko data pengguna nyata. |
| Master Data, Sensus, Musyawaroh, Program Monitoring generik (Turba/GOMA/GMKM), Sarpras, Dokumentasi Foto Kegiatan | **Tidak terdampak** | Independen dari konvergensi Kurikulum-Kegiatan — aman berjalan tanpa perubahan. |

**Kesimpulan:** karena mesin generate-berulang & master data pendukungnya (Jadwal Berulang, Hari Libur, Jenis Kegiatan) kebetulan baru selesai dibangun tepat sebelum audit ini, momentumnya tepat untuk langsung menyambungkannya ke Kurikulum sekarang — menunda ke fase nanti berisiko modul Presensi/Jurnal Harian terlanjur berkembang lebih jauh (lebih banyak kode yang harus di-retire kemudian).

---

## 6. Implementasi Selesai (4 Agustus 2026)

Seluruh visi §1 dikerjakan sekaligus, sesuai keputusan di §4/§5. Detail teknis lengkap ada di SRS-Fase-1.md v2.0 (§8/§9/§10/§13/§16/§17) dan SRS-Fase-2.md v1.6 (§2.9) — ringkasan realisasi vs rencana:

- **§2.1 (Kurikulum → tanggal kalender):** selesai, dengan penyesuaian dari rencana pattern-per-pekan di draf awal — keputusan final memakai **rentang tanggal literal** (`tanggal_mulai`/`tanggal_selesai` per baris, boleh multi-hari), bukan ekspansi pola mingguan otomatis. Tabel `kurikulum_kalender` di-drop & recreate (migration `2025_02_02_000001`).
- **§2.2 (KBM sebagai Kegiatan):** selesai. `Presensi`/`Jurnal Harian` (model, tabel, Livewire, endpoint sync) **dihapus total**. KBM digenerate lewat `KegiatanJadwal` dengan `frekuensi_tipe` baru `KURIKULUM` (SRS-Fase-2 §2.9) — `EkspansiPolaJadwal`/`GeneratorKegiatanDariJadwal` diperluas (bukan diganti, sesuai prediksi §5), menyambungkan ke `kurikulum_kalender` lewat Kelas target. Ditemukan satu kebutuhan yang tidak terprediksi di analisis awal: **carve-out otorisasi Guru** di `InputPresensiKegiatan` — otorisasi presensi Kegiatan tingkat Kelompok semula sengaja melarang Guru (untuk Kegiatan Tambahan), butuh cabang baru khusus Kegiatan ber-`kurikulum_kalender_id`.
- **§2.3 (field Materi):** selesai — `kegiatan.materi` (JSON, snapshot) + `kurikulum_kalender_id` (FK jejak sumber) + `realisasi_status`/`realisasi_catatan` (menggantikan konsep sama di Jurnal Harian).
- **§2.5 (rollup & per-kelas):** selesai sesuai batasan yang ditetapkan di §5 — rollup otomatis **hanya** untuk Kegiatan hasil-Kurikulum (`Kurikulum\RekapKbmLintasKelompok`, via relasi struktural), Kegiatan Tambahan independen tetap `kegiatan_program_id` opt-in (tidak dibangun mekanisme baru, sesuai jawaban pertanyaan terbuka §3.5). Breakdown per-kelas (`kegiatan_peserta.kelas_id`) diterapkan generik ke `RekapKegiatan` juga, tidak cuma KBM.
- **§2.6 (akses monitoring dinamis):** tidak ada perubahan kode — prinsipnya sudah terpenuhi oleh Matriks Role & Permission yang sudah ada.
- **Offline (konfirmasi lanjutan di luar draf awal):** dibangun ulang penuh — `SyncController`, `resources/js/offline/db.js` & `sync.js` diarahkan ke `Kegiatan`/`KegiatanPeserta` (endpoint `/sync/presensi` & `/sync/realisasi-kegiatan`, gantikan `/sync/jurnal-harian`), supaya tidak regresi dari kemampuan offline Presensi Harian Fase 1.
- **Munaqosah (§3.4):** diselesaikan sesuai rekomendasi — murni jenis kejadian terjadwal (`kurikulum_kalender.jenis=MUNAQOSAH`), tanpa struktur penilaian (tetap menyusul Fase 3).
- **Pertanyaan terbuka §3.2 (realisasi vs presensi):** dijawab — tetap satu kesatuan, realisasi melekat ke `Kegiatan` (per-kejadian), presensi tetap per-siswa di `kegiatan_peserta`.
- **Dokumentasi:** SRS-Fase-1.md, SRS-Fase-2.md, PRD, UCIC-Fase-1.md, UIUX-Reference-Fase-1.md diperbarui dengan Riwayat Revisi masing-masing; UCIC/UIUX hanya ditandai "digantikan" (bukan didesain ulang detail) sesuai batasan scope yang ditetapkan sebelum eksekusi.
