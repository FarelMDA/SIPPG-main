<div>
    <x-page-header title="Impor Massal Data Awal" description="Migrasi data Generus/Pendidik existing dari Excel" />

    <x-card class="max-w-xl">
        <form wire:submit="unggah" class="space-y-4">
            <div>
                <span class="form-label">Tipe Impor <span class="text-danger-solid">*</span></span>
                <div class="flex gap-4">
                    <label class="flex items-center gap-2 text-sm"><input type="radio" wire:model="tipe_impor" value="GENERUS"> Generus</label>
                    <label class="flex items-center gap-2 text-sm"><input type="radio" wire:model="tipe_impor" value="PENDIDIK"> Pendidik</label>
                </div>
            </div>

            <a href="{{ route('sensus.impor.template', ['tipe' => strtolower($tipe_impor)]) }}" class="btn btn-sm btn-outline w-fit" wire:key="template-{{ $tipe_impor }}">
                <x-icon name="download" size="14" /> Unduh Template
            </a>

            <div>
                <label class="form-label">File Excel (.xlsx) <span class="text-danger-solid">*</span></label>
                <input type="file" wire:model="file" accept=".xlsx,.xls" class="form-control" />
                <div wire:loading wire:target="file" class="mt-1 text-xs text-ink-muted">Mengunggah...</div>
                @error('file') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <x-button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="unggah">
                <span wire:loading.remove wire:target="unggah">Unggah &amp; Impor</span>
                <span wire:loading wire:target="unggah">Memproses...</span>
            </x-button>
        </form>
    </x-card>

    @if($ringkasanSukses !== null)
        <x-card class="mt-6 max-w-2xl">
            <h3 class="mb-3 font-semibold text-ink-primary">Ringkasan Impor</h3>
            <div class="mb-4 flex gap-4">
                <x-badge variant="success">{{ $ringkasanSukses }} baris berhasil</x-badge>
                <x-badge variant="danger">{{ count($ringkasanGagal) }} baris gagal</x-badge>
            </div>

            @if(count($ringkasanGagal))
                <table class="data-table">
                    <thead><tr><th>Baris</th><th>Alasan</th></tr></thead>
                    <tbody>
                        @foreach($ringkasanGagal as $item)
                            <tr><td>{{ $item['baris'] }}</td><td>{{ $item['alasan'] }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </x-card>
    @endif
</div>
