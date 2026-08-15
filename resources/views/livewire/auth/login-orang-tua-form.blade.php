<div>
    <h1 class="text-2xl font-semibold text-ink-primary">Portal Orang Tua</h1>
    <p class="mt-1 text-sm text-ink-secondary">Masuk untuk memantau data anak Anda</p>

    <form wire:submit="submit" class="mt-6 space-y-4">
        <x-input label="Nomor HP" name="nomor_hp" wire:model="nomor_hp" required autofocus :error="$errors->first('nomor_hp')" description="Nomor HP yang terdaftar sebagai kontak orang tua Generus" />

        <div x-data="{ show: false }">
            <label for="password" class="form-label">Password <span class="text-danger-solid">*</span></label>
            <div class="relative">
                {{-- Tanpa atribut `required` HTML — salah satu input selalu display:none
                     (x-show), dan browser memblokir submit bila field required tersembunyi
                     tidak bisa difokus. Validasi "wajib diisi" tetap di server (Livewire). --}}
                <input x-show="!show" type="password" id="password" wire:model="password" class="form-control pr-10" @if($errors->has('password')) aria-invalid="true" @endif />
                <input x-show="show" x-cloak type="text" wire:model="password" class="form-control pr-10" @if($errors->has('password')) aria-invalid="true" @endif />
                <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 flex items-center pr-3 text-ink-muted">
                    <x-icon x-show="!show" name="eye" size="16" />
                    <x-icon x-show="show" name="eye-off" size="16" x-cloak />
                </button>
            </div>
            @error('password') <p class="form-error">{{ $message }}</p> @enderror
        </div>

        <x-button type="submit" variant="primary" size="lg" class="w-full" wire:loading.attr="disabled" wire:target="submit">
            <span wire:loading.remove wire:target="submit">Masuk</span>
            <span wire:loading wire:target="submit">Memproses...</span>
        </x-button>

        <p class="text-center text-sm text-ink-muted">Lupa password? Hubungi Guru/Sekretaris KBM.</p>

        <p class="text-center text-sm text-ink-muted">
            Petugas/Pengurus? <a href="{{ route('login') }}" class="text-brand-primary hover:underline">Masuk ke Aplikasi Internal</a>
        </p>
    </form>
</div>
