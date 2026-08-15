<?php

namespace App\Livewire\Musyawaroh;

use App\Models\Daerah;
use App\Models\JenisMusyawaroh;
use App\Models\Musyawaroh;
use App\Models\MusyawarohItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * UC-15 — Kelola Musyawaroh & Notulen. SRS §11, UCIC UC-15.
 * Sejak ekspansi model-role: multi-tingkat (Kelompok/Desa/Daerah, satu tingkat
 * tetap per role — bukan filter bebas) + pengesahan oleh Wanbin Daerah untuk
 * notulen tingkat Daerah. TANPA carry-over otomatis (fitur Fase 2).
 * "Jenis Musyawaroh" adalah master data (JenisMusyawaroh, dikelola Admin Daerah lewat
 * Referensi) scoped per tingkat, bukan ENUM hardcoded lagi.
 */
#[Layout('layouts.app')]
class KelolaMusyawaroh extends Component
{
    public string $mode = 'list';

    public ?int $viewingId = null;

    public string $filterJenis = '';

    public string $jenis = '';

    public string $tanggal = '';

    public ?int $jumlah_hadir = null;

    /** @var array<int, array{pokok_masalah: string, keputusan: string, pic: string, keterangan: string}> */
    public array $items = [];

    public function mount(): void
    {
        $this->authorize('musyawaroh.manage');
    }

    private function tingkatSaya(): string
    {
        $user = Auth::guard('web')->user();

        return match (true) {
            $user->hasRole('admin-daerah'), $user->hasRole('wanbin-daerah'), $user->hasRole('sekretaris-ppg') => 'DAERAH',
            $user->hasRole('wanbin-desa'), $user->hasRole('sekretaris-desa') => 'DESA',
            default => 'KELOMPOK', // pjp-kelompok, sekretaris-kbm, wanbin-kelompok
        };
    }

    /** @return array{0: string, 1: int|null} */
    private function penyelenggara(): array
    {
        $user = Auth::guard('web')->user();

        return match ($this->tingkatSaya()) {
            'KELOMPOK' => ['kelompok', $user->kelompok_id],
            'DESA' => ['desa', $user->desa_id],
            'DAERAH' => ['daerah', Daerah::value('id')],
        };
    }

    public function getDaftarProperty()
    {
        $tingkat = $this->tingkatSaya();
        [$penyelenggaraType, $penyelenggaraId] = $this->penyelenggara();

        return Musyawaroh::withCount('items')
            ->where('tingkat', $tingkat)
            ->where('penyelenggara_type', $penyelenggaraType)
            ->where('penyelenggara_id', $penyelenggaraId)
            ->when($this->filterJenis !== '', fn ($q) => $q->where('jenis_musyawaroh_id', $this->filterJenis))
            ->with('jenisMusyawaroh')
            ->orderByDesc('tanggal')
            ->get();
    }

    /** Opsi jenis musyawaroh untuk tingkat user saat ini (id => nama), dipakai form & filter. */
    public function getJenisOptionsProperty()
    {
        return JenisMusyawaroh::options($this->tingkatSaya());
    }

    /** Jenis yang dipilih mewajibkan Jumlah Hadir (dulu hardcoded `jenis === 'MUSTIN_LUPG'`). */
    public function getJenisPerluJumlahHadirProperty(): bool
    {
        return $this->jenis !== '' && (bool) JenisMusyawaroh::find($this->jenis)?->perlu_jumlah_hadir;
    }

    public function mulaiBaru(): void
    {
        $this->reset(['jenis', 'tanggal', 'jumlah_hadir', 'items']);
        $this->jenis = (string) ($this->jenisOptions->keys()->first() ?? '');
        $this->tanggal = now()->toDateString();
        $this->items = [['pokok_masalah' => '', 'keputusan' => '', 'pic' => '', 'keterangan' => '']];
        $this->mode = 'form';
    }

    public function lihatCetak(int $id): void
    {
        $this->viewingId = $id;
        $this->mode = 'cetak';
    }

    public function kembali(): void
    {
        $this->mode = 'list';
    }

    public function tambahItem(): void
    {
        $this->items[] = ['pokok_masalah' => '', 'keputusan' => '', 'pic' => '', 'keterangan' => ''];
    }

    public function hapusItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function simpan(): void
    {
        $tingkat = $this->tingkatSaya();

        $this->validate([
            'jenis' => ['required', Rule::exists('jenis_musyawaroh', 'id')->where('tingkat', $tingkat)],
            'tanggal' => ['required', 'date'],
            'jumlah_hadir' => [$this->jenisPerluJumlahHadir ? 'required' : 'nullable', 'nullable', 'integer', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.pokok_masalah' => ['required', 'string'],
        ]);

        $user = Auth::guard('web')->user();
        [$penyelenggaraType, $penyelenggaraId] = $this->penyelenggara();

        $musyawaroh = Musyawaroh::create([
            'kelompok_id' => $tingkat === 'KELOMPOK' ? $penyelenggaraId : null,
            'tingkat' => $tingkat,
            'penyelenggara_type' => $penyelenggaraType,
            'penyelenggara_id' => $penyelenggaraId,
            'jenis_musyawaroh_id' => $this->jenis,
            'tanggal' => $this->tanggal,
            'jumlah_hadir' => $this->jenisPerluJumlahHadir ? $this->jumlah_hadir : null,
        ]);

        foreach ($this->items as $item) {
            MusyawarohItem::create([
                'musyawaroh_id' => $musyawaroh->id,
                'pokok_masalah' => $item['pokok_masalah'],
                'keputusan' => $item['keputusan'] ?: null,
                'pic' => $item['pic'] ?: null,
                'keterangan' => $item['keterangan'] ?: null,
            ]);
        }

        activity('musyawaroh')->causedBy($user)->performedOn($musyawaroh)->log('Notulen musyawaroh disimpan');

        $this->dispatch('toast', variant: 'success', message: 'Notulen berhasil disimpan.');
        $this->mode = 'list';
    }

    /** Pengesahan notulen tingkat Daerah — kewenangan riil Wanbin Daerah (Kyai/Wakil Kyai), bukan sekadar notulis. */
    public function sahkan(int $id): void
    {
        $user = Auth::guard('web')->user();

        if (! $user->hasRole('wanbin-daerah')) {
            $this->dispatch('toast', variant: 'danger', message: 'Hanya Wanbin Daerah yang berwenang mengesahkan notulen ini.');

            return;
        }

        $musyawaroh = Musyawaroh::where('tingkat', 'DAERAH')
            ->where('penyelenggara_type', 'daerah')
            ->where('penyelenggara_id', Daerah::value('id'))
            ->findOrFail($id);

        $musyawaroh->update([
            'disahkan_oleh' => $user->id,
            'disahkan_pada' => now(),
        ]);

        activity('musyawaroh')->causedBy($user)->performedOn($musyawaroh)->log('Notulen musyawarah Daerah disahkan');
        $this->dispatch('toast', variant: 'success', message: 'Notulen berhasil disahkan.');
    }

    public function render()
    {
        [$penyelenggaraType, $penyelenggaraId] = $this->penyelenggara();

        $viewing = $this->viewingId
            ? Musyawaroh::with('items', 'disahkanOleh', 'jenisMusyawaroh')
                ->where('tingkat', $this->tingkatSaya())
                ->where('penyelenggara_type', $penyelenggaraType)
                ->where('penyelenggara_id', $penyelenggaraId)
                ->find($this->viewingId)
            : null;

        return view('livewire.musyawaroh.kelola-musyawaroh', [
            'jenisOptions' => $this->jenisOptions,
            'tingkatSaya' => $this->tingkatSaya(),
            'bisaSahkan' => Auth::guard('web')->user()->hasRole('wanbin-daerah'),
            'viewing' => $viewing,
        ]);
    }
}
