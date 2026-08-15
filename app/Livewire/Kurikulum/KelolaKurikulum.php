<?php

namespace App\Livewire\Kurikulum;

use App\Models\Jenjang;
use App\Models\KurikulumKalender;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Kelola Kurikulum — breakdown materi per jenjang berbasis rentang tanggal kalender
 * literal (menggantikan ImporKalender/hari_ke lama). Ditampilkan sebagai kalender
 * bulanan — klik tanggal membuka panel input inline (bukan modal) — dipakai
 * App\Services\Kegiatan\GeneratorKegiatanDariJadwal untuk generate Kegiatan KBM
 * Reguler per Kelas — lihat docs/Visi-Konvergensi-Kurikulum-Kegiatan-Presensi.md §5.
 */
#[Layout('layouts.app')]
class KelolaKurikulum extends Component
{
    use WithFileUploads;

    #[Url]
    public string $filterJenjang = '';

    #[Url]
    public string $bulan = '';

    public ?string $selectedDate = null;

    public ?int $editingId = null;

    public string $jenjang = '';

    public string $tanggalMulai = '';

    public string $tanggalSelesai = '';

    public string $jenis = 'MATERI';

    public string $itemMateriText = '';

    public string $keterangan = '';

    public $file;

    public function mount(): void
    {
        $this->authorize('kurikulum.view');
        $this->bulan = now()->format('Y-m');
        $this->filterJenjang = $this->filterJenjang ?: (Jenjang::orderBy('urutan')->value('kode') ?? '');
    }

    public function updatedFilterJenjang(): void
    {
        $this->tutupPanel();
    }

    private function awalBulan(): Carbon
    {
        return Carbon::parse($this->bulan.'-01')->startOfMonth();
    }

    public function bulanSebelumnya(): void
    {
        $this->bulan = $this->awalBulan()->subMonthNoOverflow()->format('Y-m');
        $this->tutupPanel();
    }

    public function bulanBerikutnya(): void
    {
        $this->bulan = $this->awalBulan()->addMonthNoOverflow()->format('Y-m');
        $this->tutupPanel();
    }

    public function bulanIni(): void
    {
        $this->bulan = now()->format('Y-m');
        $this->tutupPanel();
    }

    /** Grid kalender bulan berjalan, disusun per pekan (Senin—Minggu) termasuk hari luber dari bulan sebelum/sesudahnya. */
    public function getMingguanProperty(): array
    {
        if (! $this->filterJenjang) {
            return [];
        }

        $awalBulan = $this->awalBulan();
        $akhirBulan = $awalBulan->copy()->endOfMonth();
        $mulaiGrid = $awalBulan->copy()->startOfWeek(Carbon::MONDAY);
        $akhirGrid = $akhirBulan->copy()->endOfWeek(Carbon::SUNDAY);

        $entries = KurikulumKalender::where('jenjang', $this->filterJenjang)
            ->where('tanggal_mulai', '<=', $akhirGrid->toDateString())
            ->where('tanggal_selesai', '>=', $mulaiGrid->toDateString())
            ->get();

        $minggu = [];
        $tanggal = $mulaiGrid->copy();

        while ($tanggal->lte($akhirGrid)) {
            $pekan = [];

            for ($i = 0; $i < 7; $i++) {
                $entry = $entries->first(fn (KurikulumKalender $e) => $tanggal->between($e->tanggal_mulai, $e->tanggal_selesai));

                $pekan[] = [
                    'tanggal' => $tanggal->toDateString(),
                    'hari' => $tanggal->day,
                    'dalamBulan' => $tanggal->month === $awalBulan->month,
                    'hariIni' => $tanggal->isToday(),
                    'entry' => $entry,
                ];

                $tanggal->addDay();
            }

            $minggu[] = $pekan;
        }

        return $minggu;
    }

    public function pilihTanggal(string $tanggal): void
    {
        $this->selectedDate = $tanggal;

        $entry = KurikulumKalender::where('jenjang', $this->filterJenjang)
            ->where('tanggal_mulai', '<=', $tanggal)
            ->where('tanggal_selesai', '>=', $tanggal)
            ->first();

        if ($entry) {
            $this->editingId = $entry->id;
            $this->jenjang = $entry->jenjang;
            $this->tanggalMulai = $entry->tanggal_mulai->toDateString();
            $this->tanggalSelesai = $entry->tanggal_selesai->toDateString();
            $this->jenis = $entry->jenis;
            $this->itemMateriText = implode("\n", $entry->item_materi ?? []);
            $this->keterangan = (string) $entry->keterangan;
        } else {
            $this->editingId = null;
            $this->jenjang = $this->filterJenjang;
            $this->tanggalMulai = $tanggal;
            $this->tanggalSelesai = $tanggal;
            $this->jenis = 'MATERI';
            $this->itemMateriText = '';
            $this->keterangan = '';
        }

        $this->resetErrorBag();
    }

    public function tutupPanel(): void
    {
        $this->selectedDate = null;
        $this->reset(['editingId', 'jenjang', 'tanggalMulai', 'tanggalSelesai', 'itemMateriText', 'keterangan']);
        $this->jenis = 'MATERI';
        $this->resetErrorBag();
    }

    private function tumpangTindih(): bool
    {
        return KurikulumKalender::where('jenjang', $this->jenjang)
            ->when($this->editingId, fn ($q) => $q->whereKeyNot($this->editingId))
            ->where('tanggal_mulai', '<=', $this->tanggalSelesai)
            ->where('tanggal_selesai', '>=', $this->tanggalMulai)
            ->exists();
    }

    public function simpan(): void
    {
        $this->authorize('kurikulum.manage');

        $this->validate([
            'jenjang' => ['required', 'string', 'in:'.implode(',', Jenjang::pluck('kode')->all())],
            'tanggalMulai' => ['required', 'date'],
            'tanggalSelesai' => ['required', 'date', 'after_or_equal:tanggalMulai'],
            'jenis' => ['required', 'in:MATERI,MUNAQOSAH'],
            'itemMateriText' => [$this->jenis === 'MATERI' ? 'required' : 'nullable', 'string'],
            'keterangan' => ['nullable', 'string'],
        ], [
            'itemMateriText.required' => 'Item materi wajib diisi untuk jenis Materi.',
        ]);

        if ($this->tumpangTindih()) {
            $this->addError('tanggalMulai', 'Rentang tanggal ini tumpang tindih dengan baris lain di jenjang yang sama.');

            return;
        }

        $itemMateri = array_values(array_filter(array_map('trim', explode("\n", $this->itemMateriText))));

        $baris = KurikulumKalender::updateOrCreate(
            ['id' => $this->editingId],
            [
                'jenjang' => $this->jenjang,
                'tanggal_mulai' => $this->tanggalMulai,
                'tanggal_selesai' => $this->tanggalSelesai,
                'jenis' => $this->jenis,
                'item_materi' => $itemMateri !== [] ? $itemMateri : null,
                'keterangan' => $this->keterangan ?: null,
                'dibuat_oleh' => $this->editingId ? KurikulumKalender::find($this->editingId)?->dibuat_oleh : Auth::guard('web')->id(),
            ]
        );

        activity('kurikulum')->causedBy(Auth::guard('web')->user())->performedOn($baris)->log('Kalender kurikulum disimpan');

        $this->dispatch('toast', variant: 'success', message: 'Kalender kurikulum berhasil disimpan.');
        $this->pilihTanggal($this->selectedDate);
    }

    public function hapus(int $id): void
    {
        $this->authorize('kurikulum.manage');

        KurikulumKalender::findOrFail($id)->delete();

        activity('kurikulum')->causedBy(Auth::guard('web')->user())->log('Kalender kurikulum dihapus');
        $this->dispatch('toast', variant: 'success', message: 'Baris kalender kurikulum dihapus.');
        $this->tutupPanel();
    }

    /**
     * Impor massal — template satu sheet per jenjang, kolom eksplisit: Tanggal Mulai |
     * Tanggal Selesai | Jenis | Item Materi (pisah `;`) | Keterangan. Menggantikan
     * template lama "10 sheet Bulan x hari_ke" (docs/Visi-Konvergensi-Kurikulum-Kegiatan-Presensi.md §5).
     */
    public function unggah(): void
    {
        $this->authorize('kurikulum.manage');

        $this->validate([
            'jenjang' => ['required', 'string', 'in:'.implode(',', Jenjang::pluck('kode')->all())],
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:5120'],
        ], [
            'file.mimes' => 'Format file tidak sesuai template kalender kurikulum.',
        ]);

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($this->file->getRealPath());
        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);

        $baris = [];
        foreach (array_slice($rows, 1) as $row) { // baris 1 = header
            $tanggalMulai = trim((string) ($row[0] ?? ''));

            if ($tanggalMulai === '') {
                continue;
            }

            $baris[] = [
                'jenjang' => $this->jenjang,
                'tanggal_mulai' => Carbon::parse($tanggalMulai)->toDateString(),
                'tanggal_selesai' => Carbon::parse((string) ($row[1] ?? $tanggalMulai))->toDateString(),
                'jenis' => strtoupper(trim((string) ($row[2] ?? 'MATERI'))) === 'MUNAQOSAH' ? 'MUNAQOSAH' : 'MATERI',
                'item_materi' => array_values(array_filter(array_map('trim', explode(';', (string) ($row[3] ?? ''))))),
                'keterangan' => trim((string) ($row[4] ?? '')) ?: null,
                'dibuat_oleh' => Auth::guard('web')->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::transaction(function () use ($baris) {
            KurikulumKalender::where('jenjang', $this->jenjang)->delete();
            DB::table('kurikulum_kalender')->insert($baris);
        });

        activity('kurikulum')->causedBy(Auth::guard('web')->user())->withProperties(['jenjang' => $this->jenjang, 'jumlah_baris' => count($baris)])->log('Kalender kurikulum diimpor');

        $this->reset('file');
        $this->dispatch('toast', variant: 'success', message: "Kalender kurikulum {$this->jenjang} berhasil diimpor.");
    }

    public function render()
    {
        return view('livewire.kurikulum.kelola-kurikulum', [
            'jenjangOptions' => Jenjang::kodeOptions(),
            'labelBulan' => $this->filterJenjang ? $this->awalBulan()->translatedFormat('F Y') : '',
        ]);
    }
}
