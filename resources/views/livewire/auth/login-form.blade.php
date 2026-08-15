<div>
    <h1 class="text-2xl font-semibold text-ink-primary">Masuk</h1>
    <p class="mt-1 text-sm text-ink-secondary">Aplikasi Internal SI-PPG</p>

    <form wire:submit="submit" class="mt-6 space-y-4">
        <x-input label="Username" name="username" wire:model="username" required autofocus :error="$errors->first('username')" />

        <div x-data="{ show: false }">
            <label for="password" class="form-label">Password <span class="text-danger-solid">*</span></label>
            <div class="relative">
                {{-- Dua input terpisah (bukan satu input dengan :type dinamis) — mengganti
                     atribut type="password"/"text" secara reaktif di elemen yang sama bisa
                     membuat browser mengosongkan nilainya, sehingga wire:model terkirim
                     kosong walau pengguna sudah mengetik. Tanpa atribut `required` HTML —
                     salah satu input selalu dalam keadaan display:none (x-show), dan
                     browser memblokir submit form bila field required tersembunyi tidak
                     bisa difokus. Validasi "wajib diisi" tetap ditegakkan di server oleh
                     Livewire (lihat pesan error di bawah). --}}
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

        <p class="text-center text-sm text-ink-muted">Lupa password? Hubungi pengurus Kelompok Anda.</p>

        <p class="text-center text-sm text-ink-muted">
            Orang tua? <a href="{{ route('portal.login') }}" class="text-brand-primary hover:underline">Masuk ke Portal Orang Tua</a>
        </p>
    </form>
</div>
