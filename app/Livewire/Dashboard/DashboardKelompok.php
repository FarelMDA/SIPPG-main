<?php

namespace App\Livewire\Dashboard;

use App\Models\Generus;
use App\Models\JenisMusyawaroh;
use App\Models\Jenjang;
use App\Models\Kegiatan;
use App\Models\KegiatanPeserta;
use App\Models\KegiatanPetugasPresensi;
use App\Models\Kelas;
use App\Models\Kelompok;
use App\Models\Musyawaroh;
use App\Models\SensusSnapshot;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * UC-19 — Dashboard Kelompok. SRS §14, UCIC UC-19. Versi dasar, level Kelompok
 * saja — agregasi Desa/Daerah adalah Fase 2 (SRS §1.1).
 */
#[Layout('layouts.app')]
class DashboardKelompok extends Component
{
    #[Locked]
    public ?int $kelompok_id = null;

    /** Bound ke pemilih Kelompok (Admin Daerah/Wanbin Desa saja) — kelompok_id sendiri
     *  di-#[Locked], jadi perpindahannya lewat properti terpisah ini + validasi di
     *  updatedKelompokPilihan(), bukan diikat langsung ke kelompok_id (UCIC UC-19). */
    public ?int $kelompokPilihan = null;

    public function mount(): void
    {
        $this->authorize('dashboard.view');

        $user = Auth::guard('web')->user();

        // Wanbin Desa di-scope desa_id (bukan kelompok_id) — SRS §5.1/UCIC UC-19: perlu
        // pemilih Kelompok dalam desanya, defaultnya Kelompok pertama di desa tsb.
        $this->kelompok_id = match (true) {
            $user->hasRole('admin-daerah') => Kelompok::orderBy('nama')->value('id'),
            $user->hasRole('wanbin-desa') => Kelompok::where('desa_id', $user->desa_id)->orderBy('nama')->value('id'),
            default => $user->kelompok_id,
        };

        $this->kelompokPilihan = $this->kelompok_id;
    }

    /** kelompokPilihan adalah properti publik Livewire — validasi ulang cakupannya di server
     *  sebelum diterapkan ke kelompok_id (Admin Daerah bebas; Wanbin Desa cuma desanya). */
    public function updatedKelompokPilihan(): void
    {
        $user = Auth::guard('web')->user();

        $valid = match (true) {
            $user->hasRole('admin-daerah') => Kelompok::whereKey($this->kelompokPilihan)->exists(),
            $user->hasRole('wanbin-desa') => Kelompok::whereKey($this->kelompokPilihan)->where('desa_id', $user->desa_id)->exists(),
            default => false,
        };

        $this->kelompok_id = $valid ? $this->kelompokPilihan : $this->kelompok_id;
        $this->kelompokPilihan = $this->kelompok_id;
    }

    public function render()
    {
        $awal = now()->startOfMonth();
        $akhir = now()->endOfMonth();

        $kelasIds = Kelas::where('kelompok_id', $this->kelompok_id)->pluck('id');

        // Kehadiran KBM Reguler (Kegiatan ber-kurikulum_kalender_id) — menggantikan
        // Presensi lama, docs/Visi-Konvergensi-Kurikulum-Kegiatan-Presensi.md §5.
        $presensiBulanIni = KegiatanPeserta::where('kelompok_id', $this->kelompok_id)
            ->whereHas('kegiatan', fn ($q) => $q->whereNotNull('kurikulum_kalender_id')
                ->whereDate('tanggal', '>=', $awal->toDateString())
                ->whereDate('tanggal', '<=', $akhir->toDateString()))
            ->selectRaw('status_presensi as status, count(*) as jumlah')
            ->groupBy('status_presensi')
            ->pluck('jumlah', 'status');

        $totalPresensi = $presensiBulanIni->sum();
        $persentaseKehadiran = $totalPresensi > 0 ? round((($presensiBulanIni['HADIR'] ?? 0) / $totalPresensi) * 100) : null;

        $kategoriMap = Jenjang::kategoriUsiaMap();

        $sensusTerbaru = SensusSnapshot::where('kelompok_id', $this->kelompok_id)
            ->where('periode', now()->format('Y-m'))
            ->get()
            ->groupBy(fn ($row) => $kategoriMap[$row->jenjang] ?? $row->jenjang)
            ->map(fn ($rows) => $rows->sum('jumlah'));

        $statusMusyawaroh = JenisMusyawaroh::where('tingkat', 'KELOMPOK')->orderBy('urutan')->get()
            ->mapWithKeys(function (JenisMusyawaroh $jenisMusyawaroh) use ($awal, $akhir) {
                $ada = Musyawaroh::withoutGlobalScopes()
                    ->where('kelompok_id', $this->kelompok_id)
                    ->where('jenis_musyawaroh_id', $jenisMusyawaroh->id)
                    ->whereDate('tanggal', '>=', $awal->toDateString())
                    ->whereDate('tanggal', '<=', $akhir->toDateString())
                    ->exists();

                return [$jenisMusyawaroh->id => ['label' => $jenisMusyawaroh->nama, 'ada' => $ada]];
            });

        $kelasBelumPresensi = Kegiatan::whereNotNull('kurikulum_kalender_id')
            ->whereDate('tanggal', now()->toDateString())
            ->whereHas('targetKelas', fn ($q) => $q->where('kelompok_id', $this->kelompok_id))
            ->doesntHave('peserta')
            ->with('targetKelas')
            ->get()
            ->flatMap(fn (Kegiatan $k) => $k->targetKelas->map(fn ($kelas) => ['kelas' => $kelas, 'kegiatan' => $k]))
            ->unique(fn ($row) => $row['kelas']->id);

        $kelompok = Kelompok::find($this->kelompok_id);

        $user = Auth::guard('web')->user();
        $kelompokOptions = match (true) {
            $user->hasRole('admin-daerah') => Kelompok::orderBy('nama')->pluck('nama', 'id'),
            $user->hasRole('wanbin-desa') => Kelompok::where('desa_id', $user->desa_id)->orderBy('nama')->pluck('nama', 'id'),
            default => collect(),
        };

        $kegiatanMendatang = Kegiatan::where('status', 'TERJADWAL')
            ->whereDate('tanggal', '>=', now()->toDateString())
            ->where(function ($q) use ($kelompok) {
                $q->where(fn ($q2) => $q2->where('tingkat', 'KELOMPOK')->where('penyelenggara_id', $kelompok?->id))
                    ->orWhere(fn ($q2) => $q2->where('tingkat', 'DESA')->where('penyelenggara_id', $kelompok?->desa_id))
                    ->orWhere('tingkat', 'DAERAH');
            })
            ->orderBy('tanggal')
            ->limit(5)
            ->get()
            ->map(function ($k) use ($kelompok) {
                $sudahDitunjuk = $k->tingkat === 'KELOMPOK' || KegiatanPetugasPresensi::where('kegiatan_id', $k->id)->where('kelompok_id', $kelompok?->id)->exists();

                return ['kegiatan' => $k, 'sudah_ditunjuk' => $sudahDitunjuk];
            });

        return view('livewire.dashboard.dashboard-kelompok', [
            'persentaseKehadiran' => $persentaseKehadiran,
            'sensusTerbaru' => $sensusTerbaru,
            'totalGenerus' => Generus::withoutGlobalScopes()->whereIn('kelas_id', $kelasIds)->where('status_aktif', true)->count(),
            'statusMusyawaroh' => $statusMusyawaroh,
            'kelasBelumPresensi' => $kelasBelumPresensi,
            'kegiatanMendatang' => $kegiatanMendatang,
            'kelompokOptions' => $kelompokOptions,
        ]);
    }
}
