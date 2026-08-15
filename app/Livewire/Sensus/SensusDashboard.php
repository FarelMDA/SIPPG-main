<?php

namespace App\Livewire\Sensus;

use App\Jobs\HitungSensusSnapshotBulanan;
use App\Models\Generus;
use App\Models\Jenjang;
use App\Models\Kelompok;
use App\Models\Pendidik;
use App\Models\SensusSnapshot;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * UC-09 — Lihat Sensus. SRS §7, UCIC UC-09.
 */
#[Layout('layouts.app')]
class SensusDashboard extends Component
{
    private const FORMAT_PERIODE = '%04d-%02d';

    public ?int $kelompok_id = null;

    public string $periode = '';

    /** Periode pembanding (default: bulan sebelum $periode) — lihat bandingkanPeriode(). */
    public string $periodeBanding = '';

    public int $bulanFilter;

    public int $tahunFilter;

    public int $bulanBanding;

    public int $tahunBanding;

    public function mount(): void
    {
        $this->authorize('sensus.view');

        $user = Auth::guard('web')->user();
        $this->kelompok_id = $user->hasRole('admin-daerah') ? Kelompok::value('id') : $user->kelompok_id;
        $this->bulanFilter = now()->month;
        $this->tahunFilter = now()->year;
        $this->periode = now()->format('Y-m');

        $bulanLalu = now()->subMonthNoOverflow();
        $this->bulanBanding = $bulanLalu->month;
        $this->tahunBanding = $bulanLalu->year;
        $this->periodeBanding = $bulanLalu->format('Y-m');
    }

    public function updatedBulanFilter(): void
    {
        $this->periode = sprintf(self::FORMAT_PERIODE, $this->tahunFilter, $this->bulanFilter);
    }

    public function updatedTahunFilter(): void
    {
        $this->periode = sprintf(self::FORMAT_PERIODE, $this->tahunFilter, $this->bulanFilter);
    }

    public function updatedBulanBanding(): void
    {
        $this->periodeBanding = sprintf(self::FORMAT_PERIODE, $this->tahunBanding, $this->bulanBanding);
    }

    public function updatedTahunBanding(): void
    {
        $this->periodeBanding = sprintf(self::FORMAT_PERIODE, $this->tahunBanding, $this->bulanBanding);
    }

    /**
     * UCIC UC-09 — tampilkan selisih jumlah antar dua periode bebas (bukan cuma bulan
     * berjalan vs bulan lalu). Dipanggil dari UI setelah user memilih kedua periode.
     */
    public function bandingkanPeriode(string $periodeA, string $periodeB): void
    {
        $this->periode = $periodeA;
        $this->periodeBanding = $periodeB;

        [$this->tahunFilter, $this->bulanFilter] = array_map('intval', explode('-', $periodeA));
        [$this->tahunBanding, $this->bulanBanding] = array_map('intval', explode('-', $periodeB));
    }

    /** kelompok_id adalah properti publik Livewire (bisa dimanipulasi lewat request) — validasi ulang di server. */
    public function updatedKelompokId(): void
    {
        $user = Auth::guard('web')->user();

        if (! $user->hasRole('admin-daerah') && $this->kelompok_id !== $user->kelompok_id) {
            $this->kelompok_id = $user->kelompok_id;
        }
    }

    /** Hitung on-the-fly bila snapshot periode ini belum ada (fallback, UCIC UC-09). */
    private function hitung(string $periode): array
    {
        $existing = SensusSnapshot::where('kelompok_id', $this->kelompok_id)->where('periode', $periode)->get();

        if ($existing->isEmpty() && $periode === now()->format('Y-m')) {
            HitungSensusSnapshotBulanan::dispatchSync($periode);
            $existing = SensusSnapshot::where('kelompok_id', $this->kelompok_id)->where('periode', $periode)->get();
        }

        $kategoriMap = Jenjang::kategoriUsiaMap();

        $perKategori = [];
        foreach ($existing as $row) {
            $kategori = $kategoriMap[$row->jenjang] ?? $row->jenjang;
            $perKategori[$kategori]['total'] = ($perKategori[$kategori]['total'] ?? 0) + $row->jumlah;
            $perKategori[$kategori][$row->status_domisili] = ($perKategori[$kategori][$row->status_domisili] ?? 0) + $row->jumlah;
        }

        return $perKategori;
    }

    public function render()
    {
        $bulanIni = $this->hitung($this->periode);
        $bulanLalu = $this->hitung($this->periodeBanding);

        $kategoriUrutan = Jenjang::kategoriUrutan();

        $jumlahGenerus = Generus::withoutGlobalScopes()->whereHas('kelas', fn ($q) => $q->where('kelompok_id', $this->kelompok_id))->where('status_aktif', true)->count();
        $jumlahPendidik = Pendidik::withoutGlobalScopes()->where('kelompok_id', $this->kelompok_id)->count();

        $bulanOptions = collect(range(1, 12))->mapWithKeys(fn ($bulan) => [$bulan => Carbon::create(2000, $bulan, 1)->translatedFormat('F')]);
        $tahunOptions = collect(range(now()->year - 2, now()->year + 1))->mapWithKeys(fn ($tahun) => [$tahun => $tahun]);

        $user = Auth::guard('web')->user();
        $kelompokOptions = $user->hasRole('admin-daerah')
            ? Kelompok::orderBy('nama')->pluck('nama', 'id')
            : Kelompok::whereKey($user->kelompok_id)->pluck('nama', 'id');

        return view('livewire.sensus.sensus-dashboard', [
            'kelompokOptions' => $kelompokOptions,
            'bulanOptions' => $bulanOptions,
            'tahunOptions' => $tahunOptions,
            'bulanIni' => $bulanIni,
            'bulanLalu' => $bulanLalu,
            'kategoriUrutan' => $kategoriUrutan,
            'jumlahGenerus' => $jumlahGenerus,
            'jumlahPendidik' => $jumlahPendidik,
            'rasio' => $jumlahPendidik > 0 ? round($jumlahGenerus / $jumlahPendidik, 1) : null,
        ]);
    }
}
