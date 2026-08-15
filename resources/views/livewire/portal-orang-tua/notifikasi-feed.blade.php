<div>
    <h1 class="mb-4 text-xl font-semibold text-ink-primary">Notifikasi</h1>

    <div class="space-y-2">
        @forelse($notifikasi as $item)
            <div class="card flex items-start justify-between gap-3 p-4 {{ $item->dibaca_pada ? 'opacity-60' : '' }}">
                <div>
                    @if($tertautLebihSatu)
                        <x-badge variant="info">{{ $item->generus->nama }}</x-badge>
                    @endif
                    <p class="mt-1 text-sm text-ink-primary">{{ $item->pesan }}</p>
                    <p class="mt-0.5 text-xs text-ink-muted">{{ $item->created_at->diffForHumans() }}</p>
                </div>
                @unless($item->dibaca_pada)
                    <button type="button" wire:click="tandaiDibaca({{ $item->id }})" class="text-ink-muted hover:text-brand-primary" title="Tandai dibaca">
                        <x-icon name="check" size="16" />
                    </button>
                @endunless
            </div>
        @empty
            <x-empty-state title="Belum ada notifikasi" icon="bell" />
        @endforelse
    </div>
</div>
