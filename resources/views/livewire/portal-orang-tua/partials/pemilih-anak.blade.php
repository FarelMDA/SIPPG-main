{{-- UIUX §3.3.1 — disembunyikan sepenuhnya bila akun hanya tertaut 1 anak. --}}
@if($daftarAnak->count() > 1)
    <div class="card mb-4 p-3">
        <div class="flex flex-wrap gap-2">
            @foreach($daftarAnak as $item)
                <button
                    type="button"
                    wire:click="pilihAnak({{ $item->id }})"
                    class="rounded-full border px-3 py-1.5 text-sm font-medium {{ $item->id === ($anak->id ?? null) ? 'border-brand-primary bg-success-bg text-brand-primary' : 'border-border text-ink-secondary' }}"
                >
                    {{ $item->nama }} — {{ $item->kelas->nama }}
                </button>
            @endforeach
        </div>
    </div>
@endif
