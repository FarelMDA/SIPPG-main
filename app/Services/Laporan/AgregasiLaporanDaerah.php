<?php

namespace App\Services\Laporan;

use App\Livewire\Admin\KelolaPengguna;
use App\Models\Daerah;
use App\Models\LaporanBulanan;
use App\Models\Musyawaroh;
use App\Models\MusyawarohItem;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Agregasi Laporan Daerah dari laporan Desa yang sudah FINAL/DISETUJUI (SRS-Fase-2 §3.5,
 * UCIC-Fase-2 UC-32) — permintaan fitur baru, dimajukan dari rencana awal Fase 4. Pola
 * identik AgregasiLaporanDesa satu tingkat lebih tinggi: sensus dijumlah, kehadiran
 * dirata-rata berbobot jumlah generus per Desa, kegiatan/program_monitoring digabung
 * sebagai daftar per-Desa (bukan per-Kelompok). Pengurus & musyawaroh Daerah adalah milik
 * Daerah itu sendiri (tingkat=DAERAH; akun berscope Daerah dikenali dari kelompok_id DAN
 * desa_id yang keduanya NULL, PRD §18.4 — satu Daerah per instalasi, tidak ada FK
 * users.daerah_id tersendiri), BUKAN gabungan dari Desa — sama alasannya seperti
 * AgregasiLaporanDesa: forum & notulen tiap tingkat memang berbeda, tidak ada operasi
 * "gabung" yang masuk akal untuk keduanya.
 */
class AgregasiLaporanDaerah
{
    public function agregasi(Daerah $daerah, string $periode): HasilAgregasiDaerah
    {
        $desaDaerah = $daerah->desa;

        $laporanFinalPerDesa = LaporanBulanan::where('tingkat', 'DESA')
            ->where('periode', $periode)
            ->whereIn('desa_id', $desaDaerah->pluck('id'))
            ->whereIn('status', ['FINAL', 'DISETUJUI'])
            ->with('desa')
            ->get()
            ->groupBy('desa_id')
            ->map(fn (Collection $rows) => $rows->sortByDesc('versi')->first());

        $snapshot = $this->gabungkan($daerah, $periode, $laporanFinalPerDesa);

        return new HasilAgregasiDaerah(
            snapshot: $snapshot,
            jumlahDesaFinal: $laporanFinalPerDesa->count(),
            jumlahDesaTotal: $desaDaerah->count(),
            desaBelumFinal: $desaDaerah->whereNotIn('id', $laporanFinalPerDesa->keys())->pluck('nama')->values()->all(),
        );
    }

    /** @param  Collection<int, LaporanBulanan>  $laporanFinalPerDesa */
    private function gabungkan(Daerah $daerah, string $periode, Collection $laporanFinalPerDesa): array
    {
        $snapshots = $laporanFinalPerDesa->map(fn (LaporanBulanan $l) => $l->snapshot_data);

        return [
            'cover' => ['kelompok' => $daerah->nama, 'periode' => $periode],
            'pengurus' => $this->pengurus(),
            'sensus' => ['per_kategori' => $this->gabungkanSensus($snapshots)],
            'kehadiran' => $this->gabungkanKehadiran($snapshots),
            'karakter_29' => ['tersedia' => false, 'pesan' => 'Modul Monitoring 29 Karakter menyusul di Fase 3.'],
            'sarpras' => ['tersedia' => false, 'pesan' => 'Modul Sarana & Prasarana belum tersedia.'],
            'kegiatan' => $this->gabungkanPerDesa($laporanFinalPerDesa, 'kegiatan'),
            'program_monitoring' => $this->gabungkanPerDesa($laporanFinalPerDesa, 'program_monitoring'),
            'shodaqoh' => ['tersedia' => false, 'pesan' => 'Modul Keuangan & Shodaqoh menyusul di Fase 3.'],
            'musyawaroh' => $this->musyawarohDaerah($daerah, $periode),
            'foto' => ['tersedia' => false, 'pesan' => 'Modul Dokumentasi Foto Kegiatan belum tersedia.'],
        ];
    }

    /** @param  Collection<int, array>  $snapshots */
    private function gabungkanSensus(Collection $snapshots): array
    {
        return $snapshots
            ->flatMap(fn (array $s) => $s['sensus']['per_kategori'] ?? [])
            ->groupBy('jenjang')
            ->map(fn (Collection $rows, string $jenjang) => [
                'jenjang' => $jenjang,
                'laki' => (int) $rows->sum('laki'),
                'perempuan' => (int) $rows->sum('perempuan'),
                'setempat' => (int) $rows->sum('setempat'),
                'pendatang' => (int) $rows->sum('pendatang'),
            ])->values()->all();
    }

    /** Bobot = jumlah generus per Desa (laki+perempuan dari sensus snapshot Desa itu sendiri). */
    private function gabungkanKehadiran(Collection $snapshots): array
    {
        $bobot = $snapshots->map(fn (array $s) => collect($s['sensus']['per_kategori'] ?? [])->sum(fn ($r) => $r['laki'] + $r['perempuan']));

        $rataBerbobot = function (\Closure $ambil) use ($snapshots, $bobot) {
            $totalBobot = $bobot->sum();

            if ($totalBobot === 0) {
                return 0;
            }

            $jumlah = $snapshots->values()->reduce(function (float $carry, array $s, int $i) use ($ambil, $bobot) {
                return $carry + ($ambil($s) * $bobot->values()[$i]);
            }, 0.0);

            return (int) round($jumlah / $totalBobot);
        };

        // tren_6_bulan diasumsikan berbobot sama seperti bobot periode berjalan — snapshot
        // Desa yang dibekukan tidak menyimpan jumlah generus historis per titik tren.
        $periodeList = $snapshots->first()['kehadiran']['tren_6_bulan'] ?? [];

        return [
            'persentase_bulan_ini' => $rataBerbobot(fn (array $s) => $s['kehadiran']['persentase_bulan_ini'] ?? 0),
            'tren_6_bulan' => collect($periodeList)->map(fn (array $titik, int $i) => [
                'periode' => $titik['periode'],
                'persentase' => $rataBerbobot(fn (array $s) => $s['kehadiran']['tren_6_bulan'][$i]['persentase'] ?? 0),
            ])->values()->all(),
        ];
    }

    /**
     * Snapshot Desa menyimpan $key (kegiatan/program_monitoring) sebagai daftar per-Kelompok
     * ([{kelompok, items}], lihat AgregasiLaporanDesa::gabungkanPerKelompok) — di-flatten di
     * sini karena tingkat Daerah hanya menampilkan daftar per-Desa, bukan per-Kelompok.
     *
     * @param  Collection<int, LaporanBulanan>  $laporanFinalPerDesa
     */
    private function gabungkanPerDesa(Collection $laporanFinalPerDesa, string $key): array
    {
        return $laporanFinalPerDesa->values()->map(fn (LaporanBulanan $l) => [
            'desa' => $l->desa->nama,
            'items' => collect($l->snapshot_data[$key] ?? [])
                ->flatMap(fn (array $perKelompok) => $perKelompok['items'] ?? [])
                ->values()
                ->all(),
        ])->all();
    }

    /** Pengurus tingkat Daerah = akun berscope Daerah (kelompok_id & desa_id NULL, PRD §18.4). */
    private function pengurus(): array
    {
        return User::whereNull('kelompok_id')
            ->whereNull('desa_id')
            ->where('is_active', true)
            ->get()
            ->flatMap(fn (User $u) => $u->getRoleNames()->map(fn (string $role) => [
                'nama' => $u->nama,
                'jabatan' => KelolaPengguna::ROLE_LABELS[$role] ?? $role,
            ]))
            ->values()
            ->all();
    }

    private function musyawarohDaerah(Daerah $daerah, string $periode): array
    {
        [$tahun, $bulan] = explode('-', $periode);
        $periodeLalu = CarbonImmutable::createFromFormat('Y-m', $periode)->subMonth()->format('Y-m');
        [$tahunLalu, $bulanLalu] = explode('-', $periodeLalu);

        $mustin = Musyawaroh::where('tingkat', 'DAERAH')
            ->where('penyelenggara_id', $daerah->id)
            ->whereHas('jenisMusyawaroh', fn ($q) => $q->where('perlu_jumlah_hadir', true))
            ->whereYear('tanggal', $tahun)->whereMonth('tanggal', $bulan)
            ->first();

        return [
            'absensi_mustin' => ['hadir' => $mustin?->jumlah_hadir ?? 0, 'total_kk' => null],
            'evaluasi_bulan_lalu' => $this->itemMusyawarohDaerahBulan($daerah, $tahunLalu, $bulanLalu),
            'resume_bulan_ini' => $this->itemMusyawarohDaerahBulan($daerah, $tahun, $bulan),
        ];
    }

    /** @return list<string> */
    private function itemMusyawarohDaerahBulan(Daerah $daerah, string $tahun, string $bulan): array
    {
        return MusyawarohItem::whereHas(
            'musyawaroh',
            fn ($q) => $q->where('tingkat', 'DAERAH')
                ->where('penyelenggara_id', $daerah->id)
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
