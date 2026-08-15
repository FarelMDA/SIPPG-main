<?php

namespace App\Services\Laporan;

use App\Livewire\Admin\KelolaPengguna;
use App\Models\Generus;
use App\Models\Kegiatan;
use App\Models\KegiatanPeserta;
use App\Models\Kelompok;
use App\Models\Musyawaroh;
use App\Models\MusyawarohItem;
use App\Models\ProgramMonitoring;
use App\Models\SensusSnapshot;
use App\Models\User;
use Carbon\CarbonImmutable;

/**
 * Susun `snapshot_data` (SRS-Fase-2 §3.2/§3.3) untuk satu Kelompok+periode dari data LIVE
 * — dipanggil ulang tiap render selama laporan DRAFT, dan sekali lagi oleh
 * Laporan\ViewerLaporanSlide::finalisasi() untuk membekukan hasilnya (SRS §3.1).
 *
 * Deck slide yang dihasilkan viewer dari struktur ini BUKAN 46 slide tetap — JSON §3.3
 * murni ~11 key terbuka (tidak ada indeks/hitungan slide berapa pun), jadi jumlah slide
 * riil mengikuti data (mis. satu slide per Jenjang yang punya generus aktif, lihat
 * `sensus.nama_per_jenjang` di bawah — perluasan dari kerangka minimal §3.3 untuk
 * menaungi "Daftar nama generus per kategori", PRD §13 baris 4-9, yang tidak ada key
 * tersendiri di contoh JSON spek). 4 bagian di bawah ini tidak punya sumber data sama
 * sekali di kodebase saat ini — karakter_29/shodaqoh karena modulnya memang Fase 3;
 * sarpras/foto karena modul Fase 2-nya sendiri (SRS §5/§6) belum dibangun — semuanya
 * diisi {"tersedia": false}, pola yang sama persis diminta §3.3 untuk bagian tanpa
 * sumber data ("Belum tersedia — modul menyusul").
 */
class SusunSnapshotLaporan
{
    public function susunKelompok(Kelompok $kelompok, string $periode): array
    {
        return [
            'cover' => $this->cover($kelompok, $periode),
            'pengurus' => $this->pengurus($kelompok),
            'sensus' => $this->sensus($kelompok, $periode),
            'kehadiran' => $this->kehadiran($kelompok, $periode),
            'karakter_29' => ['tersedia' => false, 'pesan' => 'Modul Monitoring 29 Karakter menyusul di Fase 3.'],
            'sarpras' => ['tersedia' => false, 'pesan' => 'Modul Sarana & Prasarana belum tersedia.'],
            'kegiatan' => $this->kegiatanTambahan($kelompok, $periode),
            'program_monitoring' => $this->programMonitoring($kelompok),
            'shodaqoh' => ['tersedia' => false, 'pesan' => 'Modul Keuangan & Shodaqoh menyusul di Fase 3.'],
            'musyawaroh' => $this->musyawaroh($kelompok, $periode),
            'foto' => ['tersedia' => false, 'pesan' => 'Modul Dokumentasi Foto Kegiatan belum tersedia.'],
        ];
    }

    private function cover(Kelompok $kelompok, string $periode): array
    {
        return ['kelompok' => $kelompok->nama, 'periode' => $periode];
    }

    /** Pengurus = akun internal aktif kelompok ini, jabatan dari label role KelolaPengguna::ROLE_LABELS. */
    private function pengurus(Kelompok $kelompok): array
    {
        return $kelompok->users()
            ->where('is_active', true)
            ->get()
            ->flatMap(fn (User $u) => $u->getRoleNames()->map(fn (string $role) => [
                'nama' => $u->nama,
                'jabatan' => KelolaPengguna::ROLE_LABELS[$role] ?? $role,
            ]))
            ->values()
            ->all();
    }

    private function sensus(Kelompok $kelompok, string $periode): array
    {
        $rows = SensusSnapshot::where('kelompok_id', $kelompok->id)->where('periode', $periode)->get();

        $perKategori = $rows->groupBy('jenjang')->map(fn ($rowsJenjang, string $jenjang) => [
            'jenjang' => $jenjang,
            'laki' => (int) $rowsJenjang->where('jenis_kelamin', 'LAKI')->sum('jumlah'),
            'perempuan' => (int) $rowsJenjang->where('jenis_kelamin', 'PEREMPUAN')->sum('jumlah'),
            'setempat' => (int) $rowsJenjang->where('status_domisili', 'SETEMPAT')->sum('jumlah'),
            'pendatang' => (int) $rowsJenjang->where('status_domisili', 'PENDATANG')->sum('jumlah'),
        ])->values()->all();

        // Daftar nama per Jenjang (PRD §13 baris 4-9) — data individual, beda dari agregat
        // per_kategori di atas; dikelompokkan by kode Jenjang generus saat ini (bukan
        // snapshot historis, karena tidak ada tabel snapshot per-nama di §17.2).
        $namaPerJenjang = Generus::withoutGlobalScopes()
            ->whereHas('kelas', fn ($q) => $q->where('kelompok_id', $kelompok->id))
            ->where('status_aktif', true)
            ->with(['jenjang', 'kelas'])
            ->get()
            ->groupBy(fn (Generus $g) => $g->jenjang->kode)
            ->map(fn ($rowsJenjang) => $rowsJenjang
                ->sortBy('nama')
                ->map(fn (Generus $g) => [
                    'nama' => $g->nama,
                    'kelas' => $g->kelas->nama,
                    'status_domisili' => strtolower($g->status_domisili),
                ])->values()->all())
            ->all();

        return ['per_kategori' => $perKategori, 'nama_per_jenjang' => $namaPerJenjang];
    }

    private function kehadiran(Kelompok $kelompok, string $periode): array
    {
        return [
            'persentase_bulan_ini' => $this->persentaseKehadiranBulan($kelompok, $periode),
            'tren_6_bulan' => collect(range(5, 0))
                ->map(function (int $mundur) use ($kelompok, $periode) {
                    $p = CarbonImmutable::createFromFormat('Y-m', $periode)->subMonths($mundur)->format('Y-m');

                    return ['periode' => $p, 'persentase' => $this->persentaseKehadiranBulan($kelompok, $p)];
                })->values()->all(),
        ];
    }

    /** Formula sama seperti Livewire\Kegiatan\RekapKegiatan: hadir/total*100, dibulatkan. */
    private function persentaseKehadiranBulan(Kelompok $kelompok, string $periode): int
    {
        [$tahun, $bulan] = explode('-', $periode);

        $peserta = KegiatanPeserta::where('kelompok_id', $kelompok->id)
            ->whereHas('kegiatan', fn ($q) => $q->whereYear('tanggal', $tahun)->whereMonth('tanggal', $bulan))
            ->get(['status_presensi']);

        $total = $peserta->count();

        return $total > 0 ? (int) round($peserta->where('status_presensi', 'HADIR')->count() / $total * 100) : 0;
    }

    /** Kegiatan non-KBM (kurikulum_kalender_id NULL) — mencakup PRD §13 baris 28 & 35 (ASAD/Sepakbola cukup entri Kegiatan biasa). */
    private function kegiatanTambahan(Kelompok $kelompok, string $periode): array
    {
        [$tahun, $bulan] = explode('-', $periode);

        return Kegiatan::where('tingkat', 'KELOMPOK')
            ->where('penyelenggara_id', $kelompok->id)
            ->whereNull('kurikulum_kalender_id')
            ->whereYear('tanggal', $tahun)
            ->whereMonth('tanggal', $bulan)
            ->with('peserta')
            ->orderBy('tanggal')
            ->get()
            ->map(function (Kegiatan $k) {
                $total = $k->peserta->count();
                $hadir = $k->peserta->where('status_presensi', 'HADIR')->count();

                return [
                    'nama' => $k->nama,
                    'tanggal' => $k->tanggal->toDateString(),
                    'status' => $k->status,
                    'persentase_kehadiran' => $total > 0 ? (int) round($hadir / $total * 100) : 0,
                ];
            })->values()->all();
    }

    /** Status live saat ini — program_monitoring tidak punya kolom periode (SRS-Fase-1 §17.8). */
    private function programMonitoring(Kelompok $kelompok): array
    {
        return ProgramMonitoring::withoutGlobalScopes()
            ->where('kelompok_id', $kelompok->id)
            ->with('items')
            ->get()
            ->map(fn (ProgramMonitoring $p) => [
                'nama_program' => $p->nama_program,
                'ringkasan_status' => [
                    'belum' => $p->items->where('status_item', 'BELUM')->count(),
                    'proses' => $p->items->where('status_item', 'PROSES')->count(),
                    'selesai' => $p->items->where('status_item', 'SELESAI')->count(),
                ],
            ])->values()->all();
    }

    private function musyawaroh(Kelompok $kelompok, string $periode): array
    {
        [$tahun, $bulan] = explode('-', $periode);
        $periodeLalu = CarbonImmutable::createFromFormat('Y-m', $periode)->subMonth()->format('Y-m');
        [$tahunLalu, $bulanLalu] = explode('-', $periodeLalu);

        // "Absensi Mustin" = Musyawaroh dari jenis yang jenis_musyawaroh.perlu_jumlah_hadir=true
        // (gantikan hardcode lama jenis='MUSTIN_LUPG', lihat App\Models\JenisMusyawaroh).
        $mustin = Musyawaroh::where('kelompok_id', $kelompok->id)
            ->whereHas('jenisMusyawaroh', fn ($q) => $q->where('perlu_jumlah_hadir', true))
            ->whereYear('tanggal', $tahun)->whereMonth('tanggal', $bulan)
            ->first();

        return [
            'absensi_mustin' => [
                'hadir' => $mustin?->jumlah_hadir ?? 0,
                // Tidak ada konsep KK (kepala keluarga) di skema manapun (Generus/Kelompok/Sensus)
                // — dibiarkan null, bukan diisi angka tebakan, sampai sumbernya benar-benar ada.
                'total_kk' => null,
            ],
            // Carry-over otomatis (status_carry_over/item_asal_id, SRS §4) belum dibangun (Modul 3) —
            // dua daftar di bawah murni item notulen bulan lalu/bulan ini apa adanya, tanpa rantai
            // histori yang bisa diklik (UIUX §5 P-LAP-02 mengasumsikan itu tersedia begitu §4 ada).
            'evaluasi_bulan_lalu' => $this->itemMusyawarohBulan($kelompok, $tahunLalu, $bulanLalu),
            'resume_bulan_ini' => $this->itemMusyawarohBulan($kelompok, $tahun, $bulan),
        ];
    }

    /** @return list<string> */
    private function itemMusyawarohBulan(Kelompok $kelompok, string $tahun, string $bulan): array
    {
        return MusyawarohItem::whereHas(
            'musyawaroh',
            fn ($q) => $q->where('kelompok_id', $kelompok->id)
                ->whereYear('tanggal', $tahun)
                ->whereMonth('tanggal', $bulan)
        )
            ->get()
            ->map(fn (MusyawarohItem $item) => trim(
                $item->pokok_masalah
                .($item->keputusan ? ' — '.$item->keputusan : '')
                .($item->pic ? ' (PIC: '.$item->pic.')' : '')
            ))
            ->values()
            ->all();
    }
}
