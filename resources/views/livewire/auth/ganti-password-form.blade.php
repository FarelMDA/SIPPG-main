<div>
    @if($this->dipaksa())
        <h1 class="text-2xl font-semibold text-ink-primary">Ganti Password</h1>
        <p class="mt-1 text-sm text-ink-secondary">Anda wajib mengganti password sebelum melanjutkan.</p>
    @endif

    <form wire:submit="submit" class="mt-6 max-w-sm space-y-4">
        @unless($this->dipaksa())
            <x-input label="Password Lama" name="password_lama" type="password" wire:model="password_lama" required :error="$errors->first('password_lama')" />
        @endunless

        <x-input label="Password Baru" name="password_baru" type="password" wire:model="password_baru" required description="Minimal 8 karakter" :error="$errors->first('password_baru')" />

        <x-input label="Konfirmasi Password Baru" name="password_konfirmasi" type="password" wire:model="password_konfirmasi" required :error="$errors->first('password_konfirmasi')" />

        <x-button type="submit" variant="primary" size="lg" class="w-full">Simpan Password</x-button>
    </form>
</div>
