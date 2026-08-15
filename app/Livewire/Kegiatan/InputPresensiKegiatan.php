<?php

namespace App\Livewire\Kegiatan;

use App\Models\Generus;
use App\Models\Kegiatan;
use App\Models\KegiatanPeserta;
use App\Models\KegiatanPetugasPresensi;
use App\Models\Kelompok;
use App\Models\StatusPresensi;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * UC-23 — Input Presensi Kegiatan. SRS §18.1-§18.2, UCIC UC-23. Dipakai baik
 * lewat sesi web (petugas akun internal / Kegiatan tingkat Kelompok) maupun
 * rute token (Generus tanpa akun) — SRS §2.1.
 */
class InputPresensiKegiatan extends Component
{
    // Locked: hanya ditentukan sekali di mount() dari route/token, tidak ada wire:model
    // untuk properti ini — tanpa Locked, checksum Livewire tetap bisa dimanipulasi untuk
    // "meloloskan" pengecekan berwenang atau mengganti kelompok/kegiatan target di request berikutnya.
    #[Locked]
    public ?Kegiatan $kegiatan = null;

    #[Locked]
    public ?Kelompok $kelompok = null;

    #[Locked]
    public bool $viaToken = false;

    #[Locked]
    public bool $berwenang = false;

    /** @var array<int, string> */
    public array $daftar = [];

    public string $realisasiStatus = 'SESUAI_JADWAL';

    public string $realisasiCatatan = '';

    public function mount($kegiatan = null, $kelompok = null, $token = null): void
    {
        if ($token) {
            $petugas = KegiatanPetugasPresensi::with(['kegiatan', 'kelompok'])->where('token', $token)->firstOrFail();
            $this->kegiatan = $petugas->kegiatan;
            $this->kelompok = $petugas->kelompok;
            $this->viaToken = true;
            $this->berwenang = true;
        } else {
            $this->kegiatan = $kegiatan instanceof Kegiatan ? $kegiatan : Kegiatan::findOrFail($kegiatan);
            $this->kelompok = $kelompok instanceof Kelompok ? $kelompok : Kelompok::findOrFail($kelompok);

            $user = Auth::guard('web')->user();

            if ($this->kegiatan->tingkat === 'KELOMPOK') {
                // UCIC UC-23 prasyarat: Kegiatan tingkat KELOMPOK butuh permission
                // `kegiatan.manage` biasa (PJP Kelompok/Sekretaris KBM) — bukan cuma
                // keanggotaan kelompok_id, supaya Guru/BK-KBM di kelompok yang sama
                // tidak ikut bisa mencatat presensi Kegiatan.
                $this->berwenang = ($user->can('kegiatan.manage')
                        && ($user->hasRole('admin-daerah') || $user->kelompok_id === $this->kelompok->id))
                    || $this->guruBerwenangUntukKbm($user);
            } else {
                $this->berwenang = KegiatanPetugasPresensi::where('kegiatan_id', $this->kegiatan->id)
                    ->where('kelompok_id', $this->kelompok->id)
                    ->where('user_id', $user->id)
                    ->exists();
            }
        }

        if (! $this->berwenang) {
            return;
        }

        $existing = KegiatanPeserta::where('kegiatan_id', $this->kegiatan->id)
            ->where('kelompok_id', $this->kelompok->id)
            ->pluck('status_presensi', 'generus_id');

        $this->daftar = $this->kandidatQuery()
            ->orderBy('nama')
            ->pluck('id')
            ->mapWithKeys(fn ($id) => [$id => $existing[$id] ?? 'HADIR'])
            ->toArray();

        $this->realisasiStatus = $this->kegiatan->realisasi_status ?? 'SESUAI_JADWAL';
        $this->realisasiCatatan = (string) $this->kegiatan->realisasi_catatan;
    }

    /**
     * Carve-out untuk Kegiatan KBM (kurikulum_kalender_id tidak null) — Guru yang
     * mengajar Kelas target Kegiatan tsb berwenang mencatat presensi/realisasi
     * hariannya sendiri, berbeda dari Kegiatan Tambahan biasa yang sengaja membatasi
     * Guru (lihat komentar di atas). Pola scoping sama seperti InputPresensi lama.
     * docs/Visi-Konvergensi-Kurikulum-Kegiatan-Presensi.md §5.
     */
    private function guruBerwenangUntukKbm($user): bool
    {
        if ($this->kegiatan->kurikulum_kalender_id === null
            || ! $user->can('kegiatan-presensi.manage')
            || ! $user->hasRole('guru')
            || ! $user->pendidik_id) {
            return false;
        }

        $kelas = $this->kegiatan->targetKelas()->first();

        return $kelas !== null && $kelas->pendidik()->where('pendidik.id', $user->pendidik_id)->exists();
    }

    /**
     * Grid presensi mengikuti target_tipe Kegiatan (SRS-Fase-2 §2.4) — dua lapis filter:
     * target Kegiatan ∩ kelompok_id penugasan Petugas Presensi. SEMUA identik Fase 1.
     * Dipanggil dari mount() DAN simpan() (bukan di-cache) — pola defense-in-depth yang
     * sama dengan validasi kelompok_id yang sudah ada di kedua tempat itu.
     */
    private function kandidatQuery()
    {
        $query = Generus::withoutGlobalScopes()
            ->whereHas('kelas', fn ($q) => $q->where('kelompok_id', $this->kelompok->id))
            ->where('status_aktif', true);

        if ($this->kegiatan->target_tipe === 'JENJANG_KELAS') {
            $kelasIds = $this->kegiatan->targetKelas()->pluck('kelas.id');
            $query->whereIn('kelas_id', $kelasIds);
        } elseif ($this->kegiatan->target_tipe === 'INDIVIDU') {
            $generusIds = $this->kegiatan->targetIndividu()->pluck('generus.id');
            $query->whereIn('id', $generusIds);
        }

        return $query;
    }

    public function simpan(): void
    {
        if (! $this->berwenang) {
            $this->dispatch('toast', variant: 'danger', message: 'Anda tidak berwenang mencatat presensi untuk kelompok ini.');

            return;
        }

        if ($this->kegiatan->kurikulum_kalender_id !== null) {
            $this->validate([
                'realisasiStatus' => ['required', 'in:SESUAI_JADWAL,TIDAK_TERLAKSANA,PENGGANTI'],
                'realisasiCatatan' => [$this->realisasiStatus === 'SESUAI_JADWAL' ? 'nullable' : 'required', 'nullable', 'string'],
            ], [
                'realisasiCatatan.required' => 'Catatan wajib diisi untuk status ini.',
            ]);
        }

        $userId = $this->viaToken ? null : Auth::guard('web')->id();

        // generus_id di $daftar juga properti publik Livewire — pastikan tiap id benar-benar
        // anggota kelompok ini DAN masih dalam target Kegiatan, bukan disisipkan lewat
        // request yang dimanipulasi.
        $kandidat = $this->kandidatQuery()
            ->whereIn('id', array_keys($this->daftar))
            ->get(['id', 'kelas_id'])
            ->keyBy('id');

        $this->daftar = array_intersect_key($this->daftar, $kandidat->toArray());

        foreach ($this->daftar as $generusId => $status) {
            $peserta = KegiatanPeserta::updateOrCreate(
                ['kegiatan_id' => $this->kegiatan->id, 'generus_id' => $generusId],
                [
                    'kelompok_id' => $this->kelompok->id,
                    'kelas_id' => $kandidat[$generusId]->kelas_id,
                    'status_presensi' => $status,
                    'dicatat_oleh' => $userId,
                ]
            );

            if ($status === 'ALPHA') {
                \App\Events\PresensiAlphaDicatat::dispatch($peserta);
            }
        }

        if ($this->kegiatan->kurikulum_kalender_id !== null) {
            $this->kegiatan->update([
                'realisasi_status' => $this->realisasiStatus,
                'realisasi_catatan' => $this->realisasiCatatan ?: null,
            ]);
        }

        activity('kegiatan')
            ->causedBy($userId ? Auth::guard('web')->user() : null)
            ->withProperties(['kegiatan_id' => $this->kegiatan->id, 'kelompok_id' => $this->kelompok->id, 'via_token' => $this->viaToken])
            ->log('Presensi Kegiatan disimpan'.($this->viaToken ? ' (via token)' : ''));

        $this->dispatch('toast', variant: 'success', message: 'Presensi Kegiatan berhasil disimpan.');
    }

    public function render()
    {
        $daftarGenerus = $this->berwenang
            ? Generus::withoutGlobalScopes()->whereIn('id', array_keys($this->daftar))->orderBy('nama')->get()
            : collect();

        $view = view('livewire.kegiatan.input-presensi-kegiatan', [
            'daftarGenerus' => $daftarGenerus,
            'statusPresensiOptions' => StatusPresensi::options(),
        ]);

        return $this->viaToken ? $view->layout('layouts.kegiatan-token') : $view->layout('layouts.app');
    }
}
