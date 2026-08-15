<?php

namespace App\Services\Kegiatan;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Mesin ekspansi pola Jadwal Kegiatan Berulang (SRS-Fase-2 §2.2–§2.3, UC-28/UC-30) —
 * fungsi murni tanpa DB, dipakai identik oleh pratinjau (`hitungPratinjau()`) maupun
 * generate sungguhan (App\Services\Kegiatan\GeneratorKegiatanDariJadwal). Batas 370
 * kejadian TIDAK dicek di sini — itu tanggung jawab caller (SRS §2.3).
 */
final class EkspansiPolaJadwal
{
    private const HARI_KE_ISO = [
        'SENIN' => 1,
        'SELASA' => 2,
        'RABU' => 3,
        'KAMIS' => 4,
        'JUMAT' => 5,
        'SABTU' => 6,
        'MINGGU' => 7,
    ];

    private const JUMLAH_CONTOH_TANGGAL = 5;

    /**
     * @param  array{
     *     frekuensi_tipe: string,
     *     hari_dalam_minggu: array<int, string>,
     *     minggu_ke_dalam_bulan: array<int, int|string>|null,
     *     interval_minggu: int|null,
     *     tanggal_mulai: CarbonImmutable,
     *     tanggal_selesai: CarbonImmutable,
     *     materi_kurikulum?: Collection<int, array{tanggal_mulai: CarbonImmutable, tanggal_selesai: CarbonImmutable}>,
     * }  $pola  `materi_kurikulum` hanya dipakai untuk frekuensi_tipe=KURIKULUM (docs/Visi-Konvergensi-Kurikulum-Kegiatan-Presensi.md §5)
     * @param  Collection<int, array{mulai: CarbonImmutable, selesai: CarbonImmutable}>  $hariLibur  pre-fetched, tidak query di sini
     */
    public function ekspansi(array $pola, Collection $hariLibur): HasilEkspansi
    {
        $kandidat = match ($pola['frekuensi_tipe']) {
            'HARIAN' => $this->ekspansiHarian($pola),
            'BULANAN' => $this->ekspansiBulanan($pola),
            'MINGGUAN_INTERVAL' => $this->ekspansiMingguanInterval($pola),
            'KURIKULUM' => $this->ekspansiKurikulum($pola),
            default => [],
        };

        $terurut = collect($kandidat)
            ->unique(fn (CarbonImmutable $t) => $t->toDateString())
            ->sort(fn (CarbonImmutable $a, CarbonImmutable $b) => $a <=> $b)
            ->values();

        $final = $terurut->reject(fn (CarbonImmutable $t) => $this->cocokLibur($t, $hariLibur))->values();
        $jumlahDilewati = $terurut->count() - $final->count();

        return new HasilEkspansi(
            tanggalDibuat: $final->all(),
            jumlahDilewatiLibur: $jumlahDilewati,
            contohTanggal: $final->take(self::JUMLAH_CONTOH_TANGGAL)->map(fn (CarbonImmutable $t) => $t->format('d/m/Y'))->all(),
        );
    }

    /** @return list<CarbonImmutable> */
    private function ekspansiHarian(array $pola): array
    {
        $hasil = [];
        $tanggal = $pola['tanggal_mulai'];

        while ($tanggal->lte($pola['tanggal_selesai'])) {
            if (in_array($this->kodeHari($tanggal), $pola['hari_dalam_minggu'], true)) {
                $hasil[] = $tanggal;
            }
            $tanggal = $tanggal->addDay();
        }

        return $hasil;
    }

    /** @return list<CarbonImmutable> */
    private function ekspansiBulanan(array $pola): array
    {
        $hasil = [];
        $mulai = $pola['tanggal_mulai'];
        $selesai = $pola['tanggal_selesai'];

        $bulanIter = CarbonImmutable::create($mulai->year, $mulai->month, 1);
        $bulanAkhir = CarbonImmutable::create($selesai->year, $selesai->month, 1);

        while ($bulanIter->lte($bulanAkhir)) {
            foreach ($pola['hari_dalam_minggu'] as $hari) {
                foreach ($pola['minggu_ke_dalam_bulan'] ?? [] as $n) {
                    $tanggal = $n === 'TERAKHIR'
                        ? $this->tanggalMingguTerakhir($bulanIter->year, $bulanIter->month, $hari)
                        : $this->tanggalMingguKe($bulanIter->year, $bulanIter->month, $hari, (int) $n);

                    if ($tanggal !== null && $tanggal->gte($mulai) && $tanggal->lte($selesai)) {
                        $hasil[] = $tanggal;
                    }
                }
            }
            $bulanIter = $bulanIter->addMonthNoOverflow();
        }

        return $hasil;
    }

    /** @return list<CarbonImmutable> */
    private function ekspansiMingguanInterval(array $pola): array
    {
        $hasil = [];
        $mulai = $pola['tanggal_mulai'];
        $selesai = $pola['tanggal_selesai'];
        $interval = $pola['interval_minggu'];
        $anchorWeekStart = $mulai->startOfWeek(CarbonImmutable::MONDAY);

        $tanggal = $mulai;
        while ($tanggal->lte($selesai)) {
            if (in_array($this->kodeHari($tanggal), $pola['hari_dalam_minggu'], true)) {
                $weekStart = $tanggal->startOfWeek(CarbonImmutable::MONDAY);
                $deltaMinggu = $anchorWeekStart->diffInWeeks($weekStart);

                if ($deltaMinggu % $interval === 0) {
                    $hasil[] = $tanggal;
                }
            }
            $tanggal = $tanggal->addDay();
        }

        return $hasil;
    }

    /**
     * Setiap baris `materi_kurikulum` adalah rentang tanggal literal (boleh multi-hari,
     * mis. Senin-Rabu satu pekan) — di-intersect dengan jendela tanggal_mulai/tanggal_selesai
     * Jadwal, lalu diekspansi jadi tanggal individual. Duplikat/urutan ditangani generic
     * di ekspansi() (unique+sort), libur difilter di pipeline yang sama.
     *
     * @return list<CarbonImmutable>
     */
    private function ekspansiKurikulum(array $pola): array
    {
        $hasil = [];

        foreach ($pola['materi_kurikulum'] ?? [] as $baris) {
            $mulai = $baris['tanggal_mulai']->gt($pola['tanggal_mulai']) ? $baris['tanggal_mulai'] : $pola['tanggal_mulai'];
            $selesai = $baris['tanggal_selesai']->lt($pola['tanggal_selesai']) ? $baris['tanggal_selesai'] : $pola['tanggal_selesai'];

            $tanggal = $mulai;
            while ($tanggal->lte($selesai)) {
                $hasil[] = $tanggal;
                $tanggal = $tanggal->addDay();
            }
        }

        return $hasil;
    }

    /** Kemunculan ke-$n hari-$hari dalam bulan $year-$month, dihitung berurutan dari tanggal 1 (bukan nomor pekan ISO). Null bila tidak ada (mis. minggu ke-5 di bulan pendek). */
    private function tanggalMingguKe(int $year, int $month, string $hari, int $n): ?CarbonImmutable
    {
        $candidate = $this->kemunculanPertama($year, $month, $hari)->addWeeks($n - 1);

        return $candidate->month === $month ? $candidate : null;
    }

    /** Kemunculan TERAKHIR hari-$hari dalam bulan $year-$month — dihitung ulang tiap bulan, selalu ada. */
    private function tanggalMingguTerakhir(int $year, int $month, string $hari): CarbonImmutable
    {
        $targetIso = self::HARI_KE_ISO[$hari];
        $candidate = CarbonImmutable::create($year, $month, 1)->endOfMonth()->startOfDay();

        while ($candidate->dayOfWeekIso !== $targetIso) {
            $candidate = $candidate->subDay();
        }

        return $candidate;
    }

    private function kemunculanPertama(int $year, int $month, string $hari): CarbonImmutable
    {
        $targetIso = self::HARI_KE_ISO[$hari];
        $candidate = CarbonImmutable::create($year, $month, 1);

        while ($candidate->dayOfWeekIso !== $targetIso) {
            $candidate = $candidate->addDay();
        }

        return $candidate;
    }

    private function kodeHari(CarbonImmutable $tanggal): string
    {
        return array_flip(self::HARI_KE_ISO)[$tanggal->dayOfWeekIso];
    }

    /** @param  Collection<int, array{mulai: CarbonImmutable, selesai: CarbonImmutable}>  $hariLibur */
    private function cocokLibur(CarbonImmutable $tanggal, Collection $hariLibur): bool
    {
        return $hariLibur->contains(fn (array $libur) => $tanggal->between($libur['mulai'], $libur['selesai']));
    }
}
