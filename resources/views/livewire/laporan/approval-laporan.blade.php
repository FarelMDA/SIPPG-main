<div class="card p-4">
    <h2 class="mb-3 text-lg font-semibold text-ink-primary">Review Laporan</h2>
    <div class="flex gap-2">
        <x-button wire:click="setujui" wire:confirm="Setujui laporan ini?">Setuju</x-button>
        <x-button variant="secondary" wire:click="$set('showTolakModal', true)">Tolak / Minta Revisi</x-button>
    </div>

    <x-modal wire:model="showTolakModal" title="Tolak / Minta Revisi">
        <form wire:submit="tolak" class="space-y-4">
            <div>
                <label class="form-label" for="catatan">Catatan Revisi</label>
                <textarea id="catatan" wire:model="catatan" class="form-control" rows="4" required></textarea>
                @error('catatan') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <x-button type="button" variant="secondary" @click="show = false">Batal</x-button>
                <x-button type="submit" variant="danger">Tolak</x-button>
            </div>
        </form>
    </x-modal>
</div>
