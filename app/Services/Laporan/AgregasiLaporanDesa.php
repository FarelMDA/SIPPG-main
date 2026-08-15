<?php

namespace App\Services\Laporan;

use App\Livewire\Admin\KelolaPengguna;
use App\Models\Desa;
use App\Models\LaporanBulanan;
use App\Models\Musyawaroh;
use App\Models\MusyawarohItem;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Agregasi Laporan Desa dari laporan Kelompok yang sudah FINAL/DISETUJUI (SRS-Fase-2 §3.4,
 * UCIC-Fase-2 UC-32). Sensus dijumlah, kehadiran dirata-rata berbobot jumlah generus per
 * Kelompok, kegiatan/program_monitoring digabung sebagai daftar per-Kelompok (perluasan
 * dari bentuk flat §3.3 yang hanya didefinisikan untuk tingkat Kelompok). Musyawaroh &amp;
 * pengurus di level Desa adalah milik Desa itu sendiri (tingkat=DESA), BUKAN gabungan
 * musyawaroh Kelompok — forum & notulen Desa memang berbeda dari forum Kelompok, tidak
 * ada operasi "gabung" yang masuk akal untuk keduanya (SRS-Fase-1 §11 multi-tingkat).
 */
class AgregasiLaporanDesa
{
    public function agregasi(Desa $desa, string $periode): HasilAgregasiDesa
    {
        $kelompokDesa = $desa->kelompok;

        $laporanFinalPerKelompok = LaporanBulanan::where('tingkat', 'KELOMPOK')
            ->where('periode', $periode)
            ->whereIn('kelompok_id', $kelompokDesa->pluck('id'))
            ->whereIn('status', ['FINAL', 'DISETUJUI'])
            ->with('kelompok')
            ->get()
            ->groupBy('kelompok_id')
            ->map(fn (Collection $rows) => $rows->sortByDesc('versi')->first());

        $snapshot = $this->gabungkan($desa, $periode, $laporanFinalPerKelompok);

        return new HasilAgregasiDesa(
            snapshot: $snapshot,
            jumlahKelompokFinal: $laporanFinalPerKelompok->count(),
            jumlahKelompokTotal: $kelompokDesa->count(),
            kelompokBelumFinal: $kelompokDesa->whereNotIn('id', $laporanFinalPerKelompok->keys())->pluck('nama')->values()->all(),
        );
    }

    /** @param  Collection<int, LaporanBulanan>  $laporanFinalPerKelompok */
    private function gabungkan(Desa $desa, string $periode, Collection $laporanFinalPerKelompok): array
    {
        $snapshots = $laporanFinalPerKelompok->map(fn (LaporanBulanan $l) => $l->snapshot_data);

        return [
            'cover' => ['kelompok' => $desa->nama, 'periode' => $periode],
            'pengurus' => $this->pengurus($desa),
            'sensus' => ['per_kategori' => $this->gabungkanSensus($snapshots)],
            'kehadiran' => $this->gabungkanKehadiran($snapshots),
            'karakter_29' => ['tersedia' => false, 'pesan' => 'Modul Monitoring 29 Karakter menyusul di Fase 3.'],
            'sarpras' => ['tersedia' => false, 'pesan' => 'Modul Sarana & Prasarana belum tersedia.'],
            'kegiatan' => $this->gabungkanPerKelompok($laporanFinalPerKelompok, 'kegiatan'),
            'program_monitoring' => $this->gabungkanPerKelompok($laporanFinalPerKelompok, 'program_monitoring'),
            'shodaqoh' => ['tersedia' => false, 'pesan' => 'Modul Keuangan & Shodaqoh menyusul di Fase 3.'],
            'musyawaroh' => $this->musyawarohDesa($desa, $periode),
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

    /** Bobot = jumlah generus per Kelompok (laki+perempuan dari sensus snapshot Kelompok itu sendiri). */
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
        // Kelompok yang dibekukan tidak menyimpan jumlah generus historis per titik tren.
        $periodeList = $snapshots->first()['kehadiran']['tren_6_bulan'] ?? [];

        return [
            'persentase_bulan_ini' => $rataBerbobot(fn (array $s) => $s['kehadiran']['persentase_bulan_ini'] ?? 0),
            'tren_6_bulan' => collect($periodeList)->map(fn (array $titik, int $i) => [
                'periode' => $titik['periode'],
                'persentase' => $rataBerbobot(fn (array $s) => $s['kehadiran']['tren_6_bulan'][$i]['persentase'] ?? 0),
            ])->values()->all(),
        ];
    }

    /** @param  Collection<int, LaporanBulanan>  $laporanFinalPerKelompok */
    private function gabungkanPerKelompok(Collection $laporanFinalPerKelompok, string $key): array
    {
        return $laporanFinalPerKelompok->values()->map(fn (LaporanBulanan $l) => [
            'kelompok' => $l->kelompok->nama,
            'items' => $l->snapshot_data[$key] ?? [],
        ])->all();
    }

    private function pengurus(Desa $desa): array
    {
        return $desa->users()
            ->where('is_active', true)
            ->get()
            ->flatMap(fn (User $u) => $u->getRoleNames()->map(fn (string $role) => [
                'nama' => $u->nama,
                'jabatan' => KelolaPengguna::ROLE_LABELS[$role] ?? $role,
            ]))
            ->values()
            ->all();
    }

    private function musyawarohDesa(Desa $desa, string $periode): array
    {
        [$tahun, $bulan] = explode('-', $periode);
        $periodeLalu = CarbonImmutable::createFromFormat('Y-m', $periode)->subMonth()->format('Y-m');
        [$tahunLalu, $bulanLalu] = explode('-', $periodeLalu);

        $mustin = Musyawaroh::where('tingkat', 'DESA')
            ->where('penyelenggara_id', $desa->id)
            ->whereHas('jenisMusyawaroh', fn ($q) => $q->where('perlu_jumlah_hadir', true))
            ->whereYear('tanggal', $tahun)->whereMonth('tanggal', $bulan)
            ->first();

        return [
            'absensi_mustin' => ['hadir' => $mustin?->jumlah_hadir ?? 0, 'total_kk' => null],
            'evaluasi_bulan_lalu' => $this->itemMusyawarohDesaBulan($desa, $tahunLalu, $bulanLalu),
            'resume_bulan_ini' => $this->itemMusyawarohDesaBulan($desa, $tahun, $bulan),
        ];
    }

    /** @return list<string> */
    private function itemMusyawarohDesaBulan(Desa $desa, string $tahun, string $bulan): array
    {
        return MusyawarohItem::whereHas(
            'musyawaroh',
            fn ($q) => $q->where('tingkat', 'DESA')
                ->where('penyelenggara_id', $desa->id)
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
